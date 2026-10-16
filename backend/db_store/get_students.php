<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../dbconnect/db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

$params = [];
$where_clause = '';
if ($q !== '') {
  $where_clause = "WHERE (s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR s.school LIKE ? OR s.city LIKE ? )";
  $like = "%" . $q . "%";
  $params = [$like, $like, $like, $like, $like];
}

// First, get all unique combinations of name + parent_name + surname
$names_sql = "SELECT DISTINCT s.name, s.parent_name, s.surname
              FROM students s 
              $where_clause 
              GROUP BY s.name, s.parent_name, s.surname
              ORDER BY MAX(s.created_at) DESC";

$names_stmt = mysqli_prepare($conn, str_replace('WHERE', 'WHERE', $names_sql));
if ($names_stmt === false) {
  http_response_code(500);
  echo json_encode(['error' => mysqli_error($conn)]);
  exit;
}

// Bind search params if provided
if (!empty($params)) {
  $types = str_repeat('s', count($params));
  $refArr = [];
  foreach ($params as $k => $v) {
    $refArr[$k] = &$params[$k];
  }
  array_unshift($refArr, $types);
  call_user_func_array([$names_stmt, 'bind_param'], $refArr);
}

mysqli_stmt_execute($names_stmt);
$names_result = mysqli_stmt_get_result($names_stmt);
$name_combos = [];
while ($row = mysqli_fetch_assoc($names_result)) {
  $name_combos[] = ['name' => $row['name'], 'parent_name' => $row['parent_name'], 'surname' => $row['surname']];
}

// For each combination, get the right student to show
$out = [];
$processed_count = 0;
foreach ($name_combos as $combo) {
  if ($processed_count >= $limit) break;
  if ($processed_count < $offset) {
    $processed_count++;
    continue;
  }
  
  // Check if any student with this combination has passed
  $passed_check = mysqli_prepare($conn, 
    "SELECT s.id FROM students s 
     WHERE s.name = ? AND s.parent_name = ? AND s.surname = ? 
     AND EXISTS(SELECT 1 FROM student_attempts sa WHERE sa.student_id = s.id AND sa.passed = 1)
     LIMIT 1");
  mysqli_stmt_bind_param($passed_check, "sss", $combo['name'], $combo['parent_name'], $combo['surname']);
  mysqli_stmt_execute($passed_check);
  $passed_result = mysqli_stmt_get_result($passed_check);
  
  if ($passed_row = mysqli_fetch_assoc($passed_result)) {
    // If someone passed, use that student's ID
    $selected_id = $passed_row['id'];
  } else {
    // If no one passed, get the first student with this combination
    $first_check = mysqli_prepare($conn, 
      "SELECT id FROM students WHERE name = ? AND parent_name = ? AND surname = ? ORDER BY created_at ASC LIMIT 1");
    mysqli_stmt_bind_param($first_check, "sss", $combo['name'], $combo['parent_name'], $combo['surname']);
    mysqli_stmt_execute($first_check);
    $first_result = mysqli_stmt_get_result($first_check);
    $first_row = mysqli_fetch_assoc($first_result);
    $selected_id = $first_row['id'];
  }
  
  // Now fetch the selected student's data
  $student_sql = "SELECT s.id, s.name, s.surname, s.parent_name, s.phone, s.school, s.city, s.standard, s.language,
    IFNULL((SELECT COUNT(*) FROM student_attempts sa WHERE sa.student_id = s.id),0) AS attempts,
    (SELECT sa.score FROM student_attempts sa WHERE sa.student_id = s.id ORDER BY sa.attempt_at DESC LIMIT 1) AS last_score,
    (SELECT sa.passed FROM student_attempts sa WHERE sa.student_id = s.id ORDER BY sa.attempt_at DESC LIMIT 1) AS last_passed,
    (SELECT COUNT(*) FROM student_attempts sa WHERE sa.student_id = s.id AND sa.passed = 1) AS ever_passed,
    (SELECT sa.score FROM student_attempts sa WHERE sa.student_id = s.id AND sa.passed = 1 ORDER BY sa.attempt_at DESC LIMIT 1) AS last_passing_score,
    (SELECT sa.attempt_at FROM student_attempts sa WHERE sa.student_id = s.id AND sa.passed = 1 ORDER BY sa.attempt_at DESC LIMIT 1) AS last_passing_at,
    (SELECT sa.attempt_number FROM student_attempts sa WHERE sa.student_id = s.id AND sa.passed = 1 ORDER BY sa.attempt_at DESC LIMIT 1) AS last_passing_attempt_number,
    (SELECT sa.attempt_at FROM student_attempts sa WHERE sa.student_id = s.id ORDER BY sa.attempt_at DESC LIMIT 1) AS last_attempt_at
    FROM students s WHERE s.id = ?";
    
  $student_stmt = mysqli_prepare($conn, $student_sql);
  mysqli_stmt_bind_param($student_stmt, "i", $selected_id);
  mysqli_stmt_execute($student_stmt);
  $student_result = mysqli_stmt_get_result($student_stmt);
  
  if ($student_row = mysqli_fetch_assoc($student_result)) {
    // Get all emails for this name + parent_name + surname combination
    $emails_stmt = mysqli_prepare($conn, "SELECT DISTINCT email FROM students WHERE name = ? AND parent_name = ? AND surname = ? ORDER BY email");
    mysqli_stmt_bind_param($emails_stmt, "sss", $student_row['name'], $student_row['parent_name'], $student_row['surname']);
    mysqli_stmt_execute($emails_stmt);
    $emails_res = mysqli_stmt_get_result($emails_stmt);
    $emails = [];
    while ($email_row = mysqli_fetch_assoc($emails_res)) {
      $emails[] = $email_row['email'];
    }
    
    $out[] = [
      'id' => (int)$student_row['id'],
      'name' => $student_row['name'],
      'surname' => $student_row['surname'],
      'parent_name' => $student_row['parent_name'],
      'email' => implode(', ', $emails),
      'emails' => $emails,
      'phone' => $student_row['phone'],
      'school' => $student_row['school'],
      'city' => $student_row['city'],
      'standard' => $student_row['standard'],
      'language' => $student_row['language'],
      'attempts' => (int)$student_row['attempts'],
      'last_score' => $student_row['last_score'] === null ? null : (int)$student_row['last_score'],
      'last_passed' => $student_row['last_passed'] === null ? null : (int)$student_row['last_passed'],
      'ever_passed' => $student_row['ever_passed'] === null ? 0 : (int)$student_row['ever_passed'],
      'last_passing_score' => $student_row['last_passing_score'] === null ? null : (int)$student_row['last_passing_score'],
      'last_passing_at' => $student_row['last_passing_at'] ?? null,
      'last_passing_attempt_number' => $student_row['last_passing_attempt_number'] === null ? null : (int)$student_row['last_passing_attempt_number'],
      'last_attempt_at' => $student_row['last_attempt_at']
    ];
  }
  
  $processed_count++;
}

echo json_encode(['students' => $out]);
exit;

?>
