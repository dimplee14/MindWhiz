<?php
require_once __DIR__ . '/../dbconnect/db.php';
require_once __DIR__ . '/session_check.php';
header('Content-Type: application/json; charset=utf-8');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$like = '%' . $search . '%';

$query = "SELECT s.id, s.name, s.email, s.phone, s.school, s.parent_name, s.standard, s.language, s.created_at,
    (SELECT COUNT(*) FROM student_attempts sa WHERE sa.student_id = s.id) AS attempts,
    (SELECT MAX(attempt_at) FROM student_attempts sa WHERE sa.student_id = s.id) AS last_attempt_at,
    (SELECT score FROM student_attempts sa WHERE sa.student_id = s.id ORDER BY attempt_at DESC LIMIT 1) AS last_score
FROM students s
WHERE s.name LIKE ? OR s.email LIKE ?
ORDER BY s.created_at DESC
LIMIT 200";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $r['attempts'] = (int)$r['attempts'];
    $r['last_score'] = is_null($r['last_score']) ? null : (int)$r['last_score'];
    $r['last_attempt_at'] = $r['last_attempt_at'];
    $r['passed_last'] = null;
    if (!is_null($r['last_score'])) {
        // optional: include passing logic if config exists
        $passing = 30; // fallback
        if (file_exists(__DIR__ . '/../config/test_config.php')) {
            @include_once __DIR__ . '/../config/test_config.php';
            if (defined('PASSING_SCORE')) $passing = PASSING_SCORE;
        }
        $r['passed_last'] = ($r['last_score'] >= $passing) ? 1 : 0;
    }
    $rows[] = $r;
}

echo json_encode(['success' => true, 'data' => $rows]);
exit();
?>