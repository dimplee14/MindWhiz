<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../admin/session_check.php';
require_once __DIR__ . '/../dbconnect/db.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid id']);
  exit;
}

// begin transaction
mysqli_begin_transaction($conn);
try {
  // delete attempts (attempt_words will cascade)
  $stmt = mysqli_prepare($conn, 'DELETE FROM student_attempts WHERE student_id = ?');
  if ($stmt === false) throw new Exception(mysqli_error($conn));
  mysqli_stmt_bind_param($stmt, 'i', $id);
  mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  // delete student
  $stmt = mysqli_prepare($conn, 'DELETE FROM students WHERE id = ?');
  if ($stmt === false) throw new Exception(mysqli_error($conn));
  mysqli_stmt_bind_param($stmt, 'i', $id);
  mysqli_stmt_execute($stmt);
  $affected = mysqli_stmt_affected_rows($stmt);
  mysqli_stmt_close($stmt);

  if ($affected <= 0) {
    throw new Exception('No student deleted');
  }

  mysqli_commit($conn);
  echo json_encode(['success' => true]);
  exit;
} catch (Exception $ex) {
  mysqli_rollback($conn);
  http_response_code(500);
  echo json_encode(['error' => $ex->getMessage()]);
  exit;
}

?>
