<?php
/**
 * Student registration handler for the Memory Test application.
 *
 * Responsibilities:
 * - Validates CSRF token to prevent cross-site request forgery.
 * - Reads form inputs and uses prepared statements for database safety.
 * - Checks existing student by email and enforces attempt limits (MAX_ATTEMPTS).
 * - Sets session variables (`student_id`, `language`) and redirects to next phase.
 */

// Database connection and test configuration
require_once '../dbconnect/db.php';
require_once '../config/test_config.php';

// Start or resume session for CSRF validation and user tracking
session_start();


// Only handle POST submissions from the registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // CSRF protection: ensure the token from the form matches the session token
  if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid request (CSRF). Please try again.';
    header('Location: ../../html/page2.php');
    exit();
  }
  // Get and sanitize form data
  // Note: We trim inputs for consistency; SQL injection is mitigated by prepared statements below
  $name        = trim($_POST['name']);
  $parent_name = trim($_POST['parentname']);
  $surname     = trim($_POST['surname']);
  $email       = trim($_POST['email']);
  $phone       = trim($_POST['phone']);
  $standard    = trim($_POST['standard']);
  $school      = trim($_POST['school']);
  $city        = trim($_POST['city']);
  $language    = trim($_POST['language']);


  // Check if SAME student already exists (email + name combination)
  // Allows multiple people with same email on same device, but prevents duplicate registrations
  // by the same person (same email AND name)
  $check_query = "SELECT id FROM students WHERE email = ? AND name = ?";
  $check_stmt = mysqli_prepare($conn, $check_query);
  mysqli_stmt_bind_param($check_stmt, "ss", $email, $name);
  mysqli_stmt_execute($check_stmt);
  $result = mysqli_stmt_get_result($check_stmt);

  if (mysqli_num_rows($result) > 0) {
    // Same person found (same email + name) → use existing record and enforce attempt limits
    // Do NOT create new record or update existing data
    $student_data = mysqli_fetch_assoc($result);
    $student_id = $student_data['id'];

    // Count prior attempts for this student
    $count_query = "SELECT COUNT(*) AS attempt_count FROM student_attempts WHERE student_id = ?";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "i", $student_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);  // Fetch result set which contains the count
    $count_data = mysqli_fetch_assoc($count_result); // Get the count value from the result
    $attempt_count = $count_data['attempt_count']; // Number of attempts made so far by this student 
    // Block if attempts meet or exceed MAX_ATTEMPTS (from config)
    if ($attempt_count >= MAX_ATTEMPTS) {
      $_SESSION['error'] = "You have already completed " . MAX_ATTEMPTS . " attempts. No more attempts allowed.";
      header('Location: ../../html/page2.php');
      exit();
    } else {
      // Allowed to continue: store identity and language preference in session
      $_SESSION['student_id'] = $student_id;
      $_SESSION['language'] = $language;
      // Clear any old attempt data from a previous student
      unset($_SESSION['attempt_id']);
      unset($_SESSION['attempt_state']);
      unset($_SESSION['page3_visited']);
      unset($_SESSION['correct']);
      unset($_SESSION['passed']);
      header("Location: ../../html/page3.php");
      exit();
    }
  } else {
    // Insert into database
    // New student: insert record using prepared statement
    $query = "INSERT INTO students (name, email, phone, school, city, parent_name, surname, standard, language) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $query);
    // Bind all fields as strings matching the placeholders
    mysqli_stmt_bind_param($insert_stmt, "sssssssss", $name, $email, $phone, $school, $city, $parent_name, $surname, $standard, $language);
    if (mysqli_stmt_execute($insert_stmt)) {

      $student_id = mysqli_insert_id($conn);
      // Persist identity and language for subsequent pages in the test flow
      $_SESSION['student_id'] = $student_id;
      $_SESSION['language'] = $language;
      // Clear any old attempt data from a previous student
      unset($_SESSION['attempt_id']);
      unset($_SESSION['attempt_state']);
      unset($_SESSION['page3_visited']);
      unset($_SESSION['correct']);
      unset($_SESSION['passed']);
      header("Location: ../../html/page3.php");
      exit();
    } else {
      $_SESSION['error'] = 'An error occurred while creating your account. Please try again.';
      header('Location: ../../html/page2.php');
      exit();
    }
  }
}
?>
