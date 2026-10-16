<?php
require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/../config/test_config.php';
require_once __DIR__ . '/../email_handler/MailSender.php';
session_start();
// CSRF protection
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
  $_SESSION['error'] = 'Invalid request (CSRF).';
  header('Location: ../../html/page2.php');
  exit();
}

// Validate session
if (empty($_SESSION['student_id']) || empty($_SESSION['attempt_id'])) {
  $_SESSION['error'] = 'Session expired. Please re-enter details.';
  header('Location: ../../html/page2.php');
  exit();
}

$student_id = (int)$_SESSION['student_id'];
$attempt_id = (int)$_SESSION['attempt_id'];

// Collect answers from POST
$answers = [];
for ($i = 1; $i <= TOTAL_WORDS; $i++) {
  $key = "answer_" . $i;
  $answers[] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$answers_json = json_encode($answers, JSON_UNESCAPED_UNICODE);

// Fetch stored words from DB
$words_sql = "SELECT words FROM attempt_words WHERE attempt_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $words_sql);
mysqli_stmt_bind_param($stmt, "i", $attempt_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
  $_SESSION['error'] = 'Attempt not found.';
  header('Location: ../../html/page2.php');
  exit();
}

$words_array = json_decode($row['words'], true);
if (!is_array($words_array)) {
  $_SESSION['error'] = 'Words data corrupted.';
  header('Location: ../../html/page2.php');
  exit();
}

// Calculate score (case-insensitive, trimmed comparison)
$correct = 0;
foreach ($answers as $idx => $answer) {
  if (isset($words_array[$idx])) {
    $storedWord = mb_strtolower(trim($words_array[$idx]));
    $userAnswer = mb_strtolower(trim($answer));
    if ($storedWord === $userAnswer) {
      $correct++;
    }
  }
}

// Store exact correct count as score (no percentage rounding)
$score = $correct;
// Interpret PASSING_SCORE as minimum correct answers required to pass
$passed = ($correct >= PASSING_SCORE) ? 1 : 0;

// Start transaction
mysqli_begin_transaction($conn);

try {
  // Update attempt_words with student_answers
  $upd_answers = "UPDATE attempt_words SET student_answers = ? WHERE attempt_id = ?";
  $s1 = mysqli_prepare($conn, $upd_answers);
  mysqli_stmt_bind_param($s1, "si", $answers_json, $attempt_id);
  if (!mysqli_stmt_execute($s1)) {
    throw new Exception('Failed to update answers: ' . mysqli_error($conn));
  }

  // Update student_attempts with score and passed
  $upd_attempt = "UPDATE student_attempts SET score = ?, passed = ? WHERE id = ?";
  $s2 = mysqli_prepare($conn, $upd_attempt);
  mysqli_stmt_bind_param($s2, "iii", $score, $passed, $attempt_id);
  if (!mysqli_stmt_execute($s2)) {
    throw new Exception('Failed to update attempt: ' . mysqli_error($conn));
  }

  // Commit transaction
  mysqli_commit($conn);

  // Set session variables for page7
  $_SESSION['score'] = $score;
  $_SESSION['passed'] = $passed;
  $_SESSION['correct'] = $correct;

  // If student passed, send congratulations email
  if ($passed === 1) {
    // Get student details for email
    $student_query = "SELECT name, surname, email FROM students WHERE id = ?";
    $stmt_student = mysqli_prepare($conn, $student_query);
    mysqli_stmt_bind_param($stmt_student, "i", $student_id);
    mysqli_stmt_execute($stmt_student);
    $student_result = mysqli_stmt_get_result($stmt_student);
    
    if ($student_row = mysqli_fetch_assoc($student_result)) {
      $student_email = $student_row['email'];
      $student_full_name = trim($student_row['name'] . ' ' . ($student_row['surname'] ?? ''));
      
      // Send congratulations email (non-blocking, errors are logged but don't stop flow)
      try {
        MailSender::sendCongratulationsEmail($student_email, $student_full_name);
      } catch (Exception $email_exception) {
        // Log the error but don't interrupt the user flow
        error_log("Failed to send congratulations email to {$student_email}: " . $email_exception->getMessage());
      }
    }
  }

  // Redirect to page7
  header('Location: ../../html/page7.php');
  exit();

} catch (Exception $e) {
  mysqli_rollback($conn);
  $_SESSION['error'] = 'Error saving answers: ' . $e->getMessage();
  header('Location: ../../html/page6.php');
  exit();
}
