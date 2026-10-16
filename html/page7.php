<?php
require_once __DIR__ . '/../backend/dbconnect/db.php';
require_once __DIR__ . '/../backend/config/test_config.php';
session_start();

// Validate session
if (empty($_SESSION['student_id']) || empty($_SESSION['attempt_id'])) {
  $_SESSION['error'] = 'Session expired or invalid attempt.';
  header('Location: ../page2.php');
  exit();
}

$student_id = (int) $_SESSION['student_id'];
$attempt_id = (int) $_SESSION['attempt_id'];
// Prevent caching so browser back/restore forces a reload and server checks flags
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Determine language preference
$lang = $_SESSION['language'] ?? 'eng';
if (!in_array($lang, ['eng','guj'])) $lang = 'eng';

$strings = [
  'eng' => [
    'review_title' => '📊 Review Your Answers',
    'words_filled' => 'Words filled: %d / %d',
    'word_label' => 'word #%d',
    'expected' => 'Expected:',
    'view_result' => 'View Final Result'
  ],
  'guj' => [
    'review_title' => '📊 તમારા જવાબોની સમીક્ષા',
    'words_filled' => 'ભરેલ શબ્દો: %d / %d',
    'word_label' => 'શબ્દ # %d',
    'expected' => 'અપેક્ષિત:',
    'view_result' => 'અંતિમ પરિણામ જુઓ'
  ]
];

$txt = $strings[$lang];

// Fetch attempt words and student answers
$sql = "SELECT aw.words, aw.student_answers, sa.score, sa.passed FROM attempt_words aw
  JOIN student_attempts sa ON sa.id = aw.attempt_id
  WHERE aw.attempt_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $attempt_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);

if (!$row) {
  $_SESSION['error'] = 'Attempt data not found.';
  header('Location: ../page2.php');
  exit();
}

$words = json_decode($row['words'], true) ?: [];
$answers = json_decode($row['student_answers'] ?? '[]', true) ?: [];
$correct_flags = [];
$correct_count = 0;
$filled_count = 0;
for ($i = 0; $i < TOTAL_WORDS; $i++) {
  $expected = isset($words[$i]) ? mb_strtolower(trim($words[$i])) : '';
  $given = isset($answers[$i]) ? mb_strtolower(trim($answers[$i])) : '';
  if ($given !== '') $filled_count++;
  $is_correct = ($expected !== '' && $given !== '' && $expected === $given);
  $correct_flags[$i] = $is_correct;
  if ($is_correct) $correct_count++;
}

// Keep DB 'passed' if available; fallback uses correct count threshold
$score_percent = (int) round(($correct_count / max(1, TOTAL_WORDS)) * 100);
$passed = isset($row['passed']) ? (int)$row['passed'] : ($correct_count >= PASSING_SCORE ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Review Answers</title>
    <link rel="stylesheet" href="../css/page7.css" />
    <link rel="stylesheet" href="../css/style.css" />
    <script>
      // Prevent user from going back - must load immediately without defer
      history.pushState(null, null, location.href);
      window.onpopstate = function() {
        history.go(1);
      };
    </script>
    <script src="../script/page7.js" defer></script>
  </head>
  <body>
    <section class="review-page">
      <div class="review-head">
          <div class="head">
            <h2><?php echo htmlspecialchars($txt['review_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
          </div>
          <div class="count-box"><?php echo sprintf(htmlspecialchars($txt['words_filled'], ENT_QUOTES, 'UTF-8'), $filled_count, (int)TOTAL_WORDS); ?></div>
      </div>
      <div class="review-body">
        <!-- <div class="summary">
          <p>Correct: <?php echo $correct_count; ?> / <?php echo (int)TOTAL_WORDS; ?></p>
          <p>Score: <?php echo $score_percent; ?>%</p>
          <p>Result: <?php echo $passed ? '<strong style="color:green">Passed</strong>' : '<strong style="color:#c00">Failed</strong>'; ?></p>
        </div> -->
        <div class="answered-boxes" id="answered-boxes">
          <?php for ($i = 0; $i < TOTAL_WORDS; $i++):
            $pos = $i + 1;
            $expected = $words[$i] ?? '';
            $given = $answers[$i] ?? '';
            $is_correct = !empty($correct_flags[$i]);
            $cls = $is_correct ? 'answers correct' : 'answers incorrect';
          ?>
            <div class="<?php echo $cls; ?>">
              <h3><?php echo htmlspecialchars(sprintf($txt['word_label'], $pos), ENT_QUOTES, 'UTF-8'); ?></h3>
              <div class="answer-box" id="answer-box-<?php echo $pos; ?>">
                <?php echo htmlspecialchars($given ?: '—', ENT_QUOTES, 'UTF-8'); ?>
              </div>

              <?php if ($is_correct): ?>
                <div class="result-icon">
                  <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_122_110)">
                      <path fill-rule="evenodd" clip-rule="evenodd" d="M1.50007 4.99999C1.4999 4.44115 1.63355 3.89039 1.88985 3.39379C2.14614 2.89718 2.51762 2.46917 2.97322 2.14555C3.42882 1.82192 3.95529 1.6121 4.50859 1.53363C5.0619 1.45515 5.62595 1.51031 6.15357 1.69449C6.27881 1.73812 6.41626 1.7302 6.53568 1.67249C6.65509 1.61478 6.74669 1.51199 6.79032 1.38674C6.83394 1.26149 6.82603 1.12404 6.76832 1.00463C6.7106 0.885214 6.60781 0.793618 6.48257 0.74999C5.56124 0.428561 4.56039 0.415316 3.63088 0.712251C2.70137 1.00919 1.89356 1.60021 1.32921 2.39624C0.76486 3.19228 0.474547 4.15018 0.50203 5.12558C0.529513 6.10098 0.873302 7.04102 1.48158 7.80401C2.08986 8.56701 2.92967 9.11161 3.87442 9.35573C4.81918 9.59986 5.81769 9.53028 6.71946 9.15748C7.62122 8.78469 8.37738 8.12888 8.87396 7.28889C9.37054 6.44891 9.58063 5.47028 9.47257 4.50049C9.46534 4.43522 9.44534 4.37202 9.41369 4.31448C9.38204 4.25695 9.33937 4.20621 9.28811 4.16517C9.23685 4.12412 9.17801 4.09358 9.11495 4.07527C9.05188 4.05697 8.98583 4.05127 8.92057 4.05849C8.8553 4.06571 8.79209 4.08572 8.73456 4.11737C8.67702 4.14902 8.62629 4.19169 8.58524 4.24295C8.5442 4.29421 8.51365 4.35305 8.49535 4.41611C8.47705 4.47917 8.47134 4.54522 8.47857 4.61049C8.53123 5.08344 8.4869 5.56216 8.34827 6.01739C8.20965 6.47262 7.97965 6.8948 7.67234 7.25813C7.36502 7.62146 6.98684 7.91831 6.56091 8.13053C6.13498 8.34274 5.67024 8.46587 5.19511 8.49239C4.71998 8.51891 4.24443 8.44826 3.79753 8.28476C3.35063 8.12126 2.94176 7.86835 2.59592 7.54147C2.25008 7.2146 1.97453 6.82063 1.78611 6.38365C1.59769 5.94667 1.50036 5.47586 1.50007 4.99999ZM8.87507 2.33099C8.96285 2.23153 9.00753 2.10128 8.99928 1.96888C8.99103 1.83648 8.93052 1.71278 8.83107 1.62499C8.73161 1.5372 8.60135 1.49252 8.46895 1.50077C8.33655 1.50902 8.21285 1.56953 8.12507 1.66899L4.90757 5.30899L3.31607 4.01249C3.26518 3.97099 3.20662 3.93992 3.14372 3.92106C3.08083 3.90219 3.01483 3.8959 2.94951 3.90254C2.81758 3.91595 2.69637 3.98122 2.61257 4.08399C2.57107 4.13488 2.54 4.19344 2.52113 4.25633C2.50227 4.31923 2.49598 4.38522 2.50262 4.45055C2.51602 4.58248 2.58129 4.70368 2.68407 4.78749L4.64857 6.38749C4.74865 6.46892 4.87633 6.50851 5.00493 6.49798C5.13352 6.48745 5.25306 6.42761 5.33857 6.33099L8.87507 2.33099Z" fill="#17A34B" stroke="white" stroke-width="0.00256"/>
                    </g>
                    <defs>
                      <clipPath id="clip0_122_110">
                        <rect width="10" height="10" fill="white"/>
                      </clipPath>
                    </defs>
                  </svg>
                </div>
              <?php else: ?>
                <div class="result-icon">
                  <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 1.6899e-06C4.01109 1.6899e-06 3.0444 0.29177 2.22215 0.838412C1.3999 1.38505 0.759042 2.16202 0.380604 3.07105C0.00216641 3.98008 -0.0968502 4.98035 0.0960758 5.94537C0.289002 6.9104 0.765206 7.79683 1.46447 8.49257C2.16373 9.18831 3.05464 9.66212 4.02455 9.85407C4.99445 10.046 5.99979 9.94751 6.91342 9.57098C7.82705 9.19444 8.60794 8.55681 9.15735 7.7387C9.70675 6.92059 10 5.95876 10 4.97483C10.0005 4.32138 9.87158 3.67423 9.6205 3.07041C9.36942 2.46659 9.00114 1.91796 8.53674 1.45589C8.07234 0.993831 7.52093 0.627409 6.91406 0.377591C6.30719 0.127773 5.65676 -0.000537116 5 1.6899e-06ZM5 8.77911C4.24378 8.77911 3.50454 8.556 2.87576 8.13798C2.24699 7.71996 1.75691 7.12581 1.46752 6.43067C1.17813 5.73553 1.10241 4.97061 1.24994 4.23265C1.39747 3.49469 1.76163 2.81684 2.29636 2.2848C2.83109 1.75276 3.51238 1.39044 4.25407 1.24365C4.99576 1.09686 5.76454 1.1722 6.4632 1.46013C7.16186 1.74807 7.75901 2.23568 8.17915 2.86129C8.59928 3.4869 8.82353 4.22242 8.82353 4.97483C8.82454 5.4747 8.72633 5.96984 8.53453 6.43185C8.34274 6.89386 8.06114 7.31364 7.70589 7.6671C7.35064 8.02056 6.92874 8.30075 6.46439 8.49157C6.00005 8.6824 5.5024 8.78012 5 8.77911Z" fill="#DC2626"/>
                    <path d="M6.67648 3.45311C6.62336 3.39765 6.55947 3.35351 6.48869 3.32334C6.41791 3.29317 6.34171 3.27762 6.26472 3.27762C6.18772 3.27762 6.11152 3.29317 6.04074 3.32334C5.96996 3.35351 5.90607 3.39765 5.85295 3.45311L5.00001 4.30176L4.17648 3.48238C4.0669 3.37553 3.91936 3.31602 3.76594 3.31679C3.61251 3.31756 3.46558 3.37855 3.35709 3.48649C3.2486 3.59444 3.18731 3.74062 3.18653 3.89328C3.18576 4.04594 3.24556 4.19273 3.35295 4.30176L4.17648 5.12114L3.35295 5.94053C3.24556 6.04956 3.18576 6.19635 3.18653 6.34901C3.18731 6.50166 3.2486 6.64785 3.35709 6.75579C3.46558 6.86374 3.61251 6.92472 3.76594 6.9255C3.91936 6.92627 4.0669 6.86676 4.17648 6.75991L5.00001 5.94053L5.82354 6.75991C5.93312 6.86676 6.08066 6.92627 6.23409 6.9255C6.38751 6.92472 6.53444 6.86374 6.64293 6.75579C6.75142 6.64785 6.81271 6.50166 6.81349 6.34901C6.81426 6.19635 6.75446 6.04956 6.64707 5.94053L5.82354 5.12114L6.64707 4.30176C6.75736 4.18938 6.82151 4.04008 6.82695 3.8831C6.83239 3.72613 6.77873 3.57278 6.67648 3.45311Z" fill="#DC2626"/>
                  </svg>
                </div>
              <?php endif; ?>

              <div class="expected-box"><?php echo htmlspecialchars($txt['expected'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($expected ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          <?php endfor; ?>
        </div>
        <div class="bottom-section">
          <button type="button" class="submit-btn" id="submit-btn"><?php echo htmlspecialchars($txt['view_result'], ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
      </div>
    </section>
  </body>
  </html>
