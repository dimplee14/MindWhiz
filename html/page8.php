<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/page8.css" />
    <title>Document</title>
  </head>
  <body>
    <?php
    session_start();
    require_once __DIR__ . '/../backend/dbconnect/db.php';
    require_once __DIR__ . '/../backend/config/test_config.php';

    if (empty($_SESSION['student_id']) || empty($_SESSION['attempt_id'])) {
      header('Location: page1.php');
      exit;
    }

    $student_id = (int) $_SESSION['student_id'];
    $attempt_id = (int) $_SESSION['attempt_id'];

    // Fetch current attempt info
    $attempt_stmt = mysqli_prepare($conn, "SELECT attempt_number, score, passed FROM student_attempts WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($attempt_stmt, "i", $attempt_id);
    mysqli_stmt_execute($attempt_stmt);
    $res = mysqli_stmt_get_result($attempt_stmt);
    $attempt_row = mysqli_fetch_assoc($res);

    $attempt_number = isset($attempt_row['attempt_number']) ? (int)$attempt_row['attempt_number'] : 1;

    // Prefer the stored correct count from session (set in save_answers.php). If missing, compute from DB.
    $correct_count = isset($_SESSION['correct']) ? (int)$_SESSION['correct'] : null;

    if ($correct_count === null) {
      $aw_stmt = mysqli_prepare($conn, "SELECT words, student_answers FROM attempt_words WHERE attempt_id = ? LIMIT 1");
      mysqli_stmt_bind_param($aw_stmt, "i", $attempt_id);
      mysqli_stmt_execute($aw_stmt);
      $aw_res = mysqli_stmt_get_result($aw_stmt);
      $aw_row = mysqli_fetch_assoc($aw_res);
      $correct_count = 0;
      if ($aw_row) {
        $words_array = json_decode($aw_row['words'], true);
        $answers_array = json_decode($aw_row['student_answers'], true);
        if (!is_array($words_array)) $words_array = [];
        if (!is_array($answers_array)) $answers_array = [];
        foreach ($words_array as $idx => $w) {
          if (isset($answers_array[$idx])) {
            if (mb_strtolower(trim($w)) === mb_strtolower(trim($answers_array[$idx]))) {
              $correct_count++;
            }
          }
        }
      }
    }

    // Determine pass state: prefer stored flag, else derive from correct_count
    $passed = null;
    if (isset($attempt_row['passed'])) {
      $passed = (int)$attempt_row['passed'];
    } elseif (isset($_SESSION['passed'])) {
      $passed = (int)$_SESSION['passed'];
    } else {
      $passed = ($correct_count >= PASSING_SCORE) ? 1 : 0;
    }

    // Count total attempts for this student
    $count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS attempt_count FROM student_attempts WHERE student_id = ?");
    mysqli_stmt_bind_param($count_stmt, "i", $student_id);
    mysqli_stmt_execute($count_stmt);
    $count_res = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_res);
    $attempt_count = isset($count_row['attempt_count']) ? (int)$count_row['attempt_count'] : $attempt_number;
    $remaining_attempts = MAX_ATTEMPTS - $attempt_count;

    // Check if student has passed in any previous attempt
    $passed_before_stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS passed_count FROM student_attempts WHERE student_id = ? AND passed = 1");
    mysqli_stmt_bind_param($passed_before_stmt, "i", $student_id);
    mysqli_stmt_execute($passed_before_stmt);
    $passed_before_res = mysqli_stmt_get_result($passed_before_stmt);
    $passed_before_row = mysqli_fetch_assoc($passed_before_res);
    $has_passed_before = isset($passed_before_row['passed_count']) && (int)$passed_before_row['passed_count'] > 0;

    // Localization
    $lang = $_SESSION['language'] ?? 'eng';
    if (!in_array($lang, ['eng','guj'])) $lang = 'eng';

    $strings = [
      'eng' => [
        'congrats_title' => 'Congratulation!<br>You are selected!',
        'try_title' => 'Try again!',
        'max_title' => 'Maximum attempts reached',
        'you_remembered' => 'You have written %d words correctly in sequence.',
        'need_at_least' => 'To pass the selection round of Gujarat Memory Championships, you need to remember at least %d words.',
        'attempts_remaining' => 'Attempts remaining: %d',
        'attempt_of' => 'Attempt %d of %d',
        'try_again' => 'Try Again',
        'start_over' => 'Start Over',
        'better_luck' => 'Better luck next time!',
        'contact_us' => 'Contact us for more attempts.',
        'contact_btn' => 'Contact Us'
      ],
      'guj' => [
        'congrats_title' => 'અભિનંદન!<br>તમે પાસ થયા છો!',
        'try_title' => 'ફરી પ્રયત્ન કરો!',
        'max_title' => 'તમે અધિકતમ પ્રયત્નો કરી ચુક્યા છો',
        'you_remembered' => 'તમે ક્રમમાં %d શબ્દો સાચા લખેલ છે.',
        'need_at_least' => 'તમારે Gujarat Memory Championships ના selection round માં પાસ થવા માટે ઓછામાં ઓછા %d શબ્દો યાદ રાખવા જરૂરી છે.',
        'attempts_remaining' => 'પ્રયત્નો બાકી: %d',
        'attempt_of' => 'પ્રયત્ન %d માંથી %d',
        'try_again' => 'ફરી પ્રયત્ન',
        'start_over' => 'ફરીથી શરૂ કરો',
        'better_luck' => 'ભવિષ્ય માટે શુભેચ્છાઓ!',
        'contact_us' => 'વધુ પ્રયત્ન માટે અમારો સંપર્ક કરો.',
        'contact_btn' => 'સંપર્ક કરો'
      ]
    ];

    $txt = $strings[$lang];

    ?>

    <main>
      <section id="page1">
        <div>
          <?php if ($passed): ?>
            <svg width="80" height="76" viewBox="0 0 80 76" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="40" cy="38" r="30" fill="white" stroke="#17A34B" stroke-width="6" />
              <polyline points="25,38 35,48 55,28" fill="none" stroke="#17A34B" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          <?php else: ?>
            <svg
              width="80"
              height="76"
              viewBox="0 0 80 76"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <g opacity="0.9">
                <path
                  d="M40 1.27724e-05C32.0887 1.27724e-05 24.3552 2.20521 17.7772 6.33675C11.1992 10.4683 6.07234 16.3406 3.04484 23.2111C0.0173313 30.0816 -0.774802 37.6417 0.768606 44.9354C2.31201 52.2291 6.12164 58.9288 11.7157 64.1872C17.3098 69.4457 24.4372 73.0267 32.1964 74.4775C39.9556 75.9283 47.9983 75.1837 55.3073 72.3379C62.6164 69.492 68.8635 64.6727 73.2588 58.4894C77.654 52.3061 80 45.0366 80 37.6C80.0043 32.6612 78.9726 27.77 76.964 23.2063C74.9553 18.6426 72.0091 14.496 68.2939 11.0037C64.5787 7.51142 60.1674 4.74198 55.3125 2.85385C50.4575 0.965714 45.2541 -0.00405954 40 1.27724e-05ZM40 66.3529C33.9502 66.3529 28.0363 64.6666 23.0061 61.5072C17.9759 58.3478 14.0553 53.8572 11.7402 48.6033C9.42502 43.3494 8.81927 37.5681 9.99952 31.9906C11.1798 26.4131 14.093 21.2898 18.3709 17.2686C22.6487 13.2474 28.099 10.509 34.0325 9.39955C39.9661 8.29011 46.1163 8.85951 51.7056 11.0358C57.2949 13.212 62.0721 16.8973 65.4332 21.6257C68.7943 26.3541 70.5882 31.9132 70.5882 37.6C70.5963 41.378 69.8106 45.1203 68.2763 48.6122C66.7419 52.1041 64.4891 55.2768 61.6471 57.9483C58.8051 60.6198 55.4299 62.7374 51.7151 64.1797C48.0004 65.622 44.0192 66.3605 40 66.3529Z"
                  fill="#DC2626"
                />
                <path
                  d="M53.4118 26.0988C52.9869 25.6796 52.4758 25.346 51.9095 25.118C51.3433 24.89 50.7337 24.7724 50.1177 24.7724C49.5017 24.7724 48.8921 24.89 48.3259 25.118C47.7597 25.346 47.2486 25.6796 46.8236 26.0988L40.0001 32.5129L33.4118 26.32C32.5352 25.5124 31.3549 25.0627 30.1275 25.0685C28.9001 25.0743 27.7247 25.5352 26.8567 26.3511C25.9888 27.167 25.4984 28.2718 25.4922 29.4256C25.486 30.5794 25.9645 31.6889 26.8236 32.5129L33.4118 38.7059L26.8236 44.8988C25.9645 45.7228 25.486 46.8323 25.4922 47.9861C25.4984 49.1399 25.9888 50.2447 26.8567 51.0606C27.7247 51.8765 28.9001 52.3374 30.1275 52.3432C31.3549 52.349 32.5352 51.8993 33.4118 51.0917L40.0001 44.8988L46.5883 51.0917C47.465 51.8993 48.6453 52.349 49.8727 52.3432C51.1001 52.3374 52.2755 51.8765 53.1434 51.0606C54.0114 50.2447 54.5017 49.1399 54.5079 47.9861C54.5141 46.8323 54.0357 45.7228 53.1765 44.8988L46.5883 38.7059L53.1765 32.5129C54.0589 31.6635 54.5721 30.5351 54.6156 29.3487C54.6591 28.1622 54.2298 27.0033 53.4118 26.0988Z"
                  fill="#DC2626"
                />
              </g>
            </svg>
          <?php endif; ?>
        </div>
        <div class="page1-text">
          <?php if ($passed): ?>
            <h1><?php echo $txt['congrats_title']; ?></h1>
            <p><?php echo sprintf(htmlspecialchars($txt['you_remembered'], ENT_QUOTES, 'UTF-8'), $correct_count); ?></p>
          <?php elseif ($remaining_attempts <= 0): ?>
            <h1><?php echo htmlspecialchars($txt['max_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>
              <?php echo sprintf(htmlspecialchars($txt['you_remembered'], ENT_QUOTES, 'UTF-8'), $correct_count); ?>
              <br />
              <?php echo htmlspecialchars($txt['better_luck'], ENT_QUOTES, 'UTF-8'); ?>
              <br />
              <?php echo htmlspecialchars($txt['contact_us'], ENT_QUOTES, 'UTF-8'); ?>
            </p>
          <?php else: ?>
            <h1><?php echo htmlspecialchars($txt['try_title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>
              <?php echo sprintf(htmlspecialchars($txt['you_remembered'], ENT_QUOTES, 'UTF-8'), $correct_count); ?>
              <?php echo ' ' . sprintf(htmlspecialchars($txt['need_at_least'], ENT_QUOTES, 'UTF-8'), PASSING_SCORE); ?>
              <br />
              <?php echo sprintf(htmlspecialchars($txt['attempts_remaining'], ENT_QUOTES, 'UTF-8'), max(0, $remaining_attempts)); ?>
            </p>
          <?php endif; ?>
        </div>
        <div>
          <span><?php echo sprintf(htmlspecialchars($txt['attempt_of'], ENT_QUOTES, 'UTF-8'), $attempt_number, MAX_ATTEMPTS); ?></span>
        </div>
        <div class="btn">
          <?php if ($passed): ?>
            <!-- Student passed, no buttons needed -->
          <?php elseif ($remaining_attempts <= 0): ?>
            <button id="contact-btn" onclick="window.open('https://wa.me/91<?php echo WHATSAPP_CONTACT_NUMBER; ?>', '_blank')"><?php echo htmlspecialchars($txt['contact_btn'], ENT_QUOTES, 'UTF-8'); ?></button>
          <?php else: ?>
            <button id="try-again-btn"><?php echo htmlspecialchars($txt['try_again'], ENT_QUOTES, 'UTF-8'); ?></button>
            <button id="start-over-btn"><?php echo htmlspecialchars($txt['start_over'], ENT_QUOTES, 'UTF-8'); ?></button>
          <?php endif; ?>
        </div>
      </section>
    </main>
    <script>
      const tryBtn = document.getElementById('try-again-btn');
      if (tryBtn) {
        tryBtn.addEventListener('click', function() {
          window.location.href = 'page3.php?reset=1';
        });
      }
      const startOverBtn = document.getElementById('start-over-btn');
      if (startOverBtn) {
        startOverBtn.addEventListener('click', function() {
          window.location.href = 'page1.php?clear_session=1';
        });
      }
    </script>
  </body>
</html>
