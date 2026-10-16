<?php
require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/../config/test_config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

// Support both normal POST and sendBeacon/raw body (URL-encoded)
$attempt_id = 0;
$phase = '';
// CSRF token variable for validation
$csrf = '';
if (!empty($_POST)) {
  $attempt_id = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;
  $phase = isset($_POST['phase']) ? trim($_POST['phase']) : '';
  $csrf = isset($_POST['csrf_token']) ? trim($_POST['csrf_token']) : '';
} else {
  $raw = file_get_contents('php://input');
  if ($raw) {
    parse_str($raw, $pdata);
    if (isset($pdata['attempt_id'])) $attempt_id = (int)$pdata['attempt_id'];
    if (isset($pdata['phase'])) $phase = trim($pdata['phase']);
    if (isset($pdata['csrf_token'])) $csrf = trim($pdata['csrf_token']);
  }
}

if (!$attempt_id || !in_array($phase, ['memorize','recall'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid parameters']);
  exit;
}

// CSRF validation for beacon and POST
if (empty($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
  http_response_code(403);
  echo json_encode(['error' => 'CSRF validation failed']);
  exit;
}

// mark in session to prevent returning to the page
if (!isset($_SESSION['attempt_state'])) $_SESSION['attempt_state'] = [];
if (!isset($_SESSION['attempt_state'][$attempt_id])) $_SESSION['attempt_state'][$attempt_id] = [];
$_SESSION['attempt_state'][$attempt_id][$phase . '_expired'] = 1;

// Ensure persistent DB-backed flag table exists
$create_tbl = "CREATE TABLE IF NOT EXISTS attempt_flags (
  attempt_id BIGINT NOT NULL PRIMARY KEY,
  memorize_expired TINYINT(1) DEFAULT 0,
  recall_expired TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $create_tbl);

// Persist flag to DB so it applies across sessions/browsers
if ($phase === 'memorize') {
  $up = "INSERT INTO attempt_flags (attempt_id, memorize_expired) VALUES (?, 1) ON DUPLICATE KEY UPDATE memorize_expired = 1";
  $s3 = mysqli_prepare($conn, $up);
  mysqli_stmt_bind_param($s3, "i", $attempt_id);
  @mysqli_stmt_execute($s3);
} elseif ($phase === 'recall') {
  $up = "INSERT INTO attempt_flags (attempt_id, recall_expired) VALUES (?, 1) ON DUPLICATE KEY UPDATE recall_expired = 1";
  $s3 = mysqli_prepare($conn, $up);
  mysqli_stmt_bind_param($s3, "i", $attempt_id);
  @mysqli_stmt_execute($s3);
}

// If recall expired, mark attempt as completed with zero answers/score (server-side fallback)
if ($phase === 'recall') {
  // set empty answers in attempt_words if not already set
  $empty_answers = json_encode(array_fill(0, TOTAL_WORDS, ''), JSON_UNESCAPED_UNICODE);
  // Only write empty answers if student_answers is still NULL (i.e. not submitted yet)
  $upd_aw = "UPDATE attempt_words SET student_answers = ? WHERE attempt_id = ? AND student_answers IS NULL";
  $s1 = mysqli_prepare($conn, $upd_aw);
  mysqli_stmt_bind_param($s1, "si", $empty_answers, $attempt_id);
  @mysqli_stmt_execute($s1);
  $aw_affected = mysqli_stmt_affected_rows($s1);

  // Only set score/passed if score is still NULL (i.e. not graded yet)
  $upd_att = "UPDATE student_attempts SET score = 0, passed = 0 WHERE id = ? AND score IS NULL";
  $s2 = mysqli_prepare($conn, $upd_att);
  mysqli_stmt_bind_param($s2, "i", $attempt_id);
  @mysqli_stmt_execute($s2);
  $att_affected = mysqli_stmt_affected_rows($s2);

  // Only update session values if we actually modified the DB (meaning there was nothing saved yet)
  if ($aw_affected > 0 || $att_affected > 0) {
    $_SESSION['score'] = 0;
    $_SESSION['passed'] = 0;
    $_SESSION['correct'] = 0;
  }
}

echo json_encode(['ok' => true]);
exit;

?>
