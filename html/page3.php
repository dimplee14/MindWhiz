<?php
// Page 3: Instruction screen shown after registration.
// Purpose: Display localized test overview, timers, pass criteria, and remaining attempts.
// Security: Requires active session and CSRF token; redirects if student session is missing.
require_once '../backend/dbconnect/db.php';
require_once '../backend/config/test_config.php';
session_start();

// Allow a clean retry when arriving with reset=1 (e.g., from the Try Again button)
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
  unset(
    $_SESSION['attempt_id'],
    $_SESSION['attempt_state'],
    $_SESSION['page3_visited'],
    $_SESSION['correct'],
    $_SESSION['passed']
  );
}

// Prevent caching to stop back button from showing cached page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Generate CSRF token if not present for subsequent form submission.
if (empty($_SESSION['csrf_token'])) {
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
  }
}
// Redirect to student info page if session not set (must come from registration/page2).
if (!isset($_SESSION['student_id'])) {
  header("Location: page2.php");
  exit();
}

// If user has already started the test (page3_visited flag set), prevent re-entry
if (isset($_SESSION['page3_visited']) && $_SESSION['page3_visited'] === true) {
  // If they already have an attempt, redirect to page 4
  if (isset($_SESSION['attempt_id'])) {
    header("Location: page4.php");
    exit();
  }
}

// Determine language preference

$lang = $_SESSION['language'] ?? 'eng';
// Fallback to English if language is not supported.
if (!in_array($lang, ['eng', 'guj'])) $lang = 'eng';

// Localized strings

// Localized string resources for English and Gujarati UI copy.
$strings = [
  'eng' => [
    'title' => 'Test Instructions',
    'overview_h' => '📋 Test Overview',
    'overview' => 'You will be given %d words in sequence with 3 rows, each row containing 20 words.',
    'time_h' => '⏱ Time Limit',
    'time' => 'You will be given %d minutes to memorize the words.',
    'writing_h' => '✍ Writing Phase',
    'writing' => 'After memorizing, you have %d minutes (%d seconds) to write the words in separate boxes.',
    'pass_h' => '✅ Passing Criteria',
    'pass' => 'To pass, you need to remember any one row correctly in sequence.',
    'attempts' => 'You have %d attempt(s) remaining.',
    'attempts_h' => '🔄 Attempts',
    'start' => 'Start Memory Test'
  ],
  'guj' => [
    'title' => 'પરીક્ષણ સૂચનાઓ',
    'overview_h' => '📋 પરીક્ષા સારાંશ',
    'overview' => 'અહીં તમને ક્રમમાં %d શબ્દો આપવામાં આવશે જેમાં 3 row હશે દરેક row માં 20 શબ્દો હશે.',
    'time_h' => '⏱ સમય મર્યાદા',
    'time' => 'તમને શબ્દો યાદ રાખવા માટે %d મિનિટ આપવામાં આવશે.',
    'writing_h' => '✍ લખવાની સરત',
    'writing' => 'યાદ કર્યા પછી, શબ્દો લખવા માટે તમારા પાસે %d મિનિટ (%d સેકન્ડ) છે.',
    'pass_h' => '✅ પાસ થવાનો માપદંડ',
    'pass' => 'પાસ થવા માટે કોઈ પણ row માંથી એક row ક્રમમાં સાચી રીતે યાદ રાખવી જરૂરી છે.',
    'attempts' => 'તમારી પાસે %d પ્રયત્નો બાકી છે.',
    'attempts_h' => '🔄 પ્રયત્નો',
    'start' => 'મેમરી ટેસ્ટ શરૂ કરો'
  ]
];

// Select strings based on language

// Select localized strings for current language.
$txt = $strings[$lang];

// Use PASSING_SCORE from config as the required number of correct words to pass the test.
$required_words = (int) PASSING_SCORE;

// Calculate remaining attempts

// Compute remaining attempts: count existing rows in student_attempts for this student.
$student_id = $_SESSION['student_id'];
$count_query = "SELECT COUNT(*) AS attempt_count FROM student_attempts WHERE student_id = ?";
$count_stmt = mysqli_prepare($conn, $count_query);
mysqli_stmt_bind_param($count_stmt, "i", $student_id);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_data = mysqli_fetch_assoc($count_result);
$attempt_count = $count_data['attempt_count'];
// Remaining attempts are bounded by MAX_ATTEMPTS in configuration.
$remaining_attempts = MAX_ATTEMPTS - $attempt_count;


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/page3.css">

</head>

<body>
  <!-- Main content container: instructions and start button -->
  <main>
    <section id="page3">
      <div class="instruction">
        <!-- Instruction icon (decorative) -->
        <svg
          width="48"
          height="48"
          viewBox="0 0 48 48"
          fill="none"
          xmlns="http://www.w3.org/2000/svg">
          <g clip-path="url(#clip0_118_21)">
            <path d="M48 0H0V48H48V0Z" fill="white" fill-opacity="0.01" />
            <path
              d="M24 44C29.5228 44 34.5228 41.7614 38.1421 38.1421C41.7614 34.5228 44 29.5228 44 24C44 18.4772 41.7614 13.4772 38.1421 9.85786C34.5228 6.23858 29.5228 4 24 4C18.4772 4 13.4772 6.23858 9.85786 9.85786C6.23858 13.4772 4 18.4772 4 24C4 29.5228 6.23858 34.5228 9.85786 38.1421C13.4772 41.7614 18.4772 44 24 44Z"
              fill="white"
              stroke="#9333E8"
              stroke-width="4"
              stroke-linejoin="round" />
            <path
              d="M24 28.6249V24.6249C27.3137 24.6249 30 21.9386 30 18.6249C30 15.3112 27.3137 12.6249 24 12.6249C20.6863 12.6249 18 15.3112 18 18.6249"
              stroke="white"
              stroke-width="4"
              stroke-linecap="round"
              stroke-linejoin="round" />
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M24 37.6249C25.3807 37.6249 26.5 36.5056 26.5 35.1249C26.5 33.7442 25.3807 32.6249 24 32.6249C22.6193 32.6249 21.5 33.7442 21.5 35.1249C21.5 36.5056 22.6193 37.6249 24 37.6249Z"
              fill="white" />
            <path
              d="M23.7857 35.0234C24.5899 35.0234 25.2419 34.3715 25.2419 33.5672C25.2419 32.763 24.5899 32.111 23.7857 32.111C22.9814 32.111 22.3295 32.763 22.3295 33.5672C22.3295 34.3715 22.9814 35.0234 23.7857 35.0234Z"
              fill="#9333E8" />
            <path
              d="M25.2689 15.3378C25.061 15.1202 24.7804 15 24.4789 15H23.0924C22.7908 15 22.5103 15.1202 22.3023 15.3378C22.0944 15.556 21.9873 15.8419 22.0012 16.1435L22.5754 28.4106C22.6056 29.0562 23.1357 29.5624 23.7828 29.5624H23.7886C24.4356 29.5624 24.9658 29.0562 24.996 28.4106L25.5702 16.1435C25.5839 15.8419 25.4768 15.5561 25.2689 15.3378Z"
              fill="#9333E8" />
          </g>
          <defs>
            <clipPath id="clip0_118_21">
              <rect width="48" height="48" fill="white" />
            </clipPath>
          </defs>
        </svg>

        <div>
          <h1 id="inst"><?php echo htmlspecialchars($txt['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div>
          <div class="info info1">
            <!-- Overview: total words pulled from TOTAL_WORDS config -->
            <h3><?php echo htmlspecialchars($txt['overview_h'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars(sprintf($txt['overview'], (int)TOTAL_WORDS), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <div class="info info4">
            <!-- Passing criteria: minimum correct words from PASSING_SCORE -->
            <h3><?php echo htmlspecialchars($txt['pass_h'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars(sprintf($txt['pass'], (int)PASSING_SCORE), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <div class="info info2">
            <!-- Memorization timer: minutes and seconds derived from MEMORIZE_TIME -->
            <h3><?php echo htmlspecialchars($txt['time_h'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars(sprintf($txt['time'], (int)(MEMORIZE_TIME / 60), MEMORIZE_TIME), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <div class="info info3">
            <!-- Recall timer: minutes and seconds derived from RECALL_TIME -->
            <h3><?php echo htmlspecialchars($txt['writing_h'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo htmlspecialchars(sprintf($txt['writing'], (int)(RECALL_TIME / 60), RECALL_TIME), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>



          <div class="info info5">
            <!-- Remaining attempts: computed from student_attempts count and MAX_ATTEMPTS -->
            <h3><?php echo htmlspecialchars($txt['attempts_h'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p><?php echo sprintf(htmlspecialchars($txt['attempts'], ENT_QUOTES, 'UTF-8'), $remaining_attempts); ?></p>
          </div>
          <!-- Start test form: posts to word generation component with CSRF protection -->
          <form method="post" action="../backend/component/generate_words.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <!-- Disable Start if no remaining attempts -->
            <button class="start-btn" type="submit" <?php if ($remaining_attempts <= 0) echo 'disabled'; ?>><?php echo htmlspecialchars($txt['start'], ENT_QUOTES, 'UTF-8'); ?></button>
          </form>
        </div>
      </div>
    </section>
  </main>

  <script>
    // Aggressive back button prevention
    (function() {
      // Method 1: Replace history entry
      if (window.history && window.history.pushState) {
        window.history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function() {
          window.history.pushState(null, null, window.location.href);
        });
      }

      // Method 2: Prevent backspace navigation
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
          e.preventDefault();
          return false;
        }
      });

      // Method 3: Block all back navigation attempts
      window.onbeforeunload = function() {
        window.history.forward();
      };

      // Method 4: Force forward on page show (handles browser cache)
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          window.location.reload();
        }
      });

      // Method 5: Continuous history push
      setInterval(function() {
        window.history.pushState(null, null, window.location.href);
      }, 100);
    })();

    // Mark that start button was clicked to prevent returning
    document.querySelector('form').addEventListener('submit', function() {
      sessionStorage.setItem('test_started', 'true');
    });
  </script>
</body>

</html>