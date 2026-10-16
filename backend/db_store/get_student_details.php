<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../dbconnect/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing id']);
  exit;
}

// Get the primary student record
$s = mysqli_prepare($conn, "SELECT id, name, surname, email, phone, school, city, parent_name, standard, language, created_at FROM students WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($s, 'i', $id);
mysqli_stmt_execute($s);
$r = mysqli_stmt_get_result($s);
$student = mysqli_fetch_assoc($r);
if (!$student) {
  http_response_code(404);
  echo json_encode(['error' => 'Student not found']);
  exit;
}

// Get all students with the same name (to show all related emails)
$all_students = [];
$all_s = mysqli_prepare($conn, "SELECT id, name, surname, email, phone, school, city, parent_name, standard, language, created_at FROM students WHERE name = ? ORDER BY created_at ASC");
mysqli_stmt_bind_param($all_s, 's', $student['name']);
mysqli_stmt_execute($all_s);
$all_r = mysqli_stmt_get_result($all_s);
while ($row = mysqli_fetch_assoc($all_r)) {
  $all_students[] = [
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'surname' => $row['surname'],
    'email' => $row['email'],
    'phone' => $row['phone'],
    'school' => $row['school'],
    'city' => $row['city'],
    'parent_name' => $row['parent_name'],
    'standard' => $row['standard'],
    'language' => $row['language'],
    'created_at' => $row['created_at']
  ];
}

// Get attempts for the primary student
$attempts = [];
$a = mysqli_prepare($conn, "SELECT id, attempt_number, score, passed, attempt_at FROM student_attempts WHERE student_id = ? ORDER BY attempt_at DESC");
mysqli_stmt_bind_param($a, 'i', $id);
mysqli_stmt_execute($a);
$ra = mysqli_stmt_get_result($a);
while ($row = mysqli_fetch_assoc($ra)) {
  $attempts[] = [
    'id' => (int)$row['id'],
    'attempt_number' => (int)$row['attempt_number'],
    'score' => $row['score'] === null ? null : (int)$row['score'],
    'passed' => $row['passed'] === null ? null : (int)$row['passed'],
    'attempt_at' => $row['attempt_at']
  ];
}

echo json_encode([
  'student' => [
    'id' => (int)$student['id'],
    'name' => $student['name'],
    'surname' => $student['surname'],
    'email' => $student['email'],
    'phone' => $student['phone'],
    'school' => $student['school'],
    'city' => $student['city'],
    'parent_name' => $student['parent_name'],
    'standard' => $student['standard'],
    'language' => $student['language'],
    'created_at' => $student['created_at']
  ],
  'all_students' => $all_students,
  'attempts' => $attempts
]);
exit;

?>
