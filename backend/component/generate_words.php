<?php
// Component: Generate Words for Memorization (Page 4)
// Purpose: Validates request and session, selects a language-specific word list,
//          generates a randomized set of TOTAL_WORDS, persists attempt + words,
//          and navigates the student to the memorization page.
require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/../config/test_config.php';
session_start();

// CSRF protection
// CSRF validation: ensure POST, token present, and matches session token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
  $_SESSION['error'] = 'Invalid request (CSRF).';
  header('Location: ../../html/page3.php');
  exit();
}

// Session verification: student must be logged/registered before proceeding
if (!isset($_SESSION['student_id'])) {
  $_SESSION['error'] = 'Session expired. Please re-enter your details.';
  header('Location: ../../html/page2.php');
  exit();
}

// Determine current attempt count for this student to enforce MAX_ATTEMPTS
$student_id = (int) $_SESSION['student_id'];
$count_sql = "SELECT COUNT(*) AS cnt FROM student_attempts WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
$attempt_count = $row['cnt'];

// Block further attempts if the configured limit has been reached
if ($attempt_count >= MAX_ATTEMPTS) {
  $_SESSION['error'] = 'You have reached the maximum attempts.';
  header('Location: ../../html/page2.php');
  exit();
}
$attempt_number = $attempt_count + 1;


// Choose word file based on session language; defaults to English if not set
$lang = $_SESSION['language'] ?? 'eng';
$words_file = ($lang === 'guj') ? WORDS_FILE_GUJ : WORDS_FILE_ENG;


// Validate word list file presence before reading
if (!file_exists($words_file)) {
  $_SESSION['error'] = 'Word list not found.';
  header('Location: ../../html/page3.php');
  exit();
}

// Load all words (one per line) and ensure there are at least TOTAL_WORDS available
$all_words = file($words_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (count($all_words) < TOTAL_WORDS) {
  $_SESSION['error'] = 'Not enough words in the word list.';
  header('Location: ../../html/page1.php');
  exit();
}

// Randomize the list and select the first TOTAL_WORDS entries
shuffle($all_words);
$selected_words = array_slice($all_words, 0, TOTAL_WORDS);
$words_json = json_encode($selected_words, JSON_UNESCAPED_UNICODE);

// Begin transaction: ensure attempt record and its word list are stored atomically


mysqli_begin_transaction($conn);

try{
  // Insert new attempt
  $ins_attempt = "INSERT INTO student_attempts (student_id, attempt_number, attempt_at) VALUES (?, ?, NOW())";
  $s1 = mysqli_prepare($conn, $ins_attempt);
  mysqli_stmt_bind_param($s1, "ii", $student_id, $attempt_number);
  if(!mysqli_stmt_execute($s1)) throw new Exception('Failed to record attempt:' . mysqli_error($conn));
  $attempt_id = mysqli_insert_id($conn);

  // Insert words for this attempt
  $ins_words = "INSERT INTO attempt_words (attempt_id, words) VALUES (?, ?)";
  $s2 = mysqli_prepare($conn, $ins_words);
  mysqli_stmt_bind_param($s2, "is", $attempt_id, $words_json);
  if (!mysqli_stmt_execute($s2)) throw new Exception('Failed to insert words: ' . mysqli_error($conn));

  // Commit transaction
  mysqli_commit($conn);

  // Set session and redirect to page4.php
  // Store attempt id in session; page4.php will render the memorization UI for these words
  $_SESSION['attempt_id'] = $attempt_id;
  $_SESSION['page3_visited'] = true;  // Mark that user has started the test
  header('Location: ../../html/page4.php');
  exit();

}
catch(Exception $e){
  // Rollback on any failure and inform the user; retry from instruction page
  mysqli_rollback($conn);
  $_SESSION['error'] = 'An error occurred. Please try again.';
  header('Location: ../../html/page3.php');
  exit();
}