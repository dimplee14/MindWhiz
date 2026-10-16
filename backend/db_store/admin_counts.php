<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../dbconnect/db.php';

$data = [
  'total_students' => 0,
  'passed_students' => 0,
  'attempts_today' => 0
];

// Total students
$q1 = "SELECT COUNT(*) AS cnt FROM students";
if ($r1 = mysqli_query($conn, $q1)) {
  $row = mysqli_fetch_assoc($r1);
  $data['total_students'] = (int)($row['cnt'] ?? 0);
}

// Students passed (distinct students who have at least one passed attempt)
$q2 = "SELECT COUNT(DISTINCT student_id) AS cnt FROM student_attempts WHERE passed = 1";
if ($r2 = mysqli_query($conn, $q2)) {
  $row = mysqli_fetch_assoc($r2);
  $data['passed_students'] = (int)($row['cnt'] ?? 0);
}

// Attempts today
$q3 = "SELECT COUNT(*) AS cnt FROM student_attempts WHERE DATE(attempt_at) = CURDATE()";
if ($r3 = mysqli_query($conn, $q3)) {
  $row = mysqli_fetch_assoc($r3);
  $data['attempts_today'] = (int)($row['cnt'] ?? 0);
}

echo json_encode($data);
exit;

?>
