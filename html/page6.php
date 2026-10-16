<?php
require_once __DIR__ . '/../backend/dbconnect/db.php';
require_once __DIR__ . '/../backend/config/test_config.php';
session_start();
if (empty($_SESSION['csrf_token'])) {
  try { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } catch (Exception $e) { $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32)); }
}
// Prevent caching so browser back/restore forces a reload and server checks flags
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (empty($_SESSION['student_id']) || empty($_SESSION['attempt_id'])) {
  $_SESSION['error'] = 'Session expired. Please re-enter details.';
  header('Location: ../page2.php');
  exit();
}

$attempt_id = (int)$_SESSION['attempt_id'];

// If recall was previously marked expired for this attempt, don't allow re-entry
// If recall was previously marked expired for this attempt, don't allow re-entry
if (isset($_SESSION['attempt_state'][$attempt_id]['recall_expired']) && $_SESSION['attempt_state'][$attempt_id]['recall_expired']) {
  header('Location: page7.php');
  exit();
}

// Also check persistent DB flag table (attempt_flags)
$flag_stmt = mysqli_prepare($conn, "SELECT recall_expired FROM attempt_flags WHERE attempt_id = ? LIMIT 1");
mysqli_stmt_bind_param($flag_stmt, "i", $attempt_id);
mysqli_stmt_execute($flag_stmt);
$flag_res = mysqli_stmt_get_result($flag_stmt);
$flag_row = mysqli_fetch_assoc($flag_res);
if ($flag_row && !empty($flag_row['recall_expired'])) {
  header('Location: page7.php');
  exit();
}

// Localization
$lang = $_SESSION['language'] ?? 'eng';
$txt = [
  'eng' => [
    'heading' => '✍️ Write the vertical line you remembered in sequence',
    'instruction' => 'Write the words in the EXACT sequence. Each box represents one word position.',
    'counter' => 'Words filled: {count} / {total}',
    'button' => 'Submit'
  ],
  'guj' => [
    'heading' => '✍️ તમે જે ઊભી line યાદ રાખી છે તે ક્રમ મુજબ લખો',
    'instruction' => 'શબ્દો ચોક્કસ ક્રમમાં લખો. દરેક બોક્સ એક શબ્દની સ્થિતિ દર્શાવે છે.',
    'counter' => 'શબ્દો ભરેલા: {count} / {total}',
    'button' => 'સબમિટ કરો'
  ]
];
$t = $txt[$lang] ?? $txt['eng'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>

    <!-- main css link -->

    <link rel="stylesheet" href="../css/page6.css" />
    <link rel="stylesheet" href="../css/style.css" />

    <!-- js -->

    <script src="../script/page6.js" defer></script>
  </head>
  <body>
    <!-- recall page  -->

    <section class="recall-page">
      <!-- RECALL HEAD -->
      <div class="recall-head">
        <div class="head">
          <h2><?php echo htmlspecialchars($t['heading'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="timer" id="timer"> <svg
              width="64"
              height="64"
              viewBox="0 0 64 64"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M32 56C45.2549 56 56 45.2549 56 32C56 18.7452 45.2549 8 32 8C18.7452 8 8 18.7452 8 32C8 45.2549 18.7452 56 32 56Z"
                stroke="#9333EA"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M32 16V32"
                stroke="#9333EA"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M43.3067 43.3067L32 32"
                stroke="#9333EA"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg><span id="time-left">00:00</span></div>
      </div>
      <!-- RECALL BODY -->
      <div class="recall-body">
        <p>
          <?php echo htmlspecialchars($t['instruction'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <form id="recall-form" method="POST" action="../backend/db_store/save_answers.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="input-boxes" id="input-boxes">
            <!-- input boxes will be generated here -->
          </div>
          <div class="bottom-section">
            <div class="count-box">
              <span id="word-count">Words filled: 0 / <?php echo (int)TOTAL_WORDS; ?></span>
            </div>
            <button type="submit" class="submit-btn" id="submit-btn"><?php echo htmlspecialchars($t['button'], ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </div>
    </section>
    <script>
      const TOTAL_WORDS = <?php echo (int)TOTAL_WORDS; ?>;
      const RECALL_TIME = <?php echo (int)RECALL_TIME; ?>;
      const ATTEMPT_ID = <?php echo (int)$attempt_id; ?>;
      const CSRF_TOKEN = '<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, "UTF-8"); ?>';

      // Prevent user from going back
      history.pushState(null, null, location.href);
      window.onpopstate = function() {
        history.go(1);
      };
    </script>
  </body>
</html>
