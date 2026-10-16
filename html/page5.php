<?php
session_start();
// Prevent caching so browser back/restore forces a reload and server checks flags
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$lang = $_SESSION['language'] ?? 'eng';
$txt = [
  'eng' => [
    'title' => 'Test Instructions',
    'requirement' => 'Requirement',
    'requirement_text' => 'A computer with a physical keyboard and the Chrome web browser.',
    'controls' => 'Controls',
    'controls_char' => 'Char.keys : Type word in active cell.',
    'controls_enter' => 'Enter : Focus next cell.',
    'controls_tab' => 'Tab : Focus next cell.',
    'controls_backspace' => 'Backspace : Clear active cell',
    'controls_arrow_up' => 'Arrow Up : Move up in same column.',
    'controls_arrow_down' => 'Arrow Down : Move down in same column.',
    'controls_arrow_left' => 'Arrow Left : Move to previous column.',
    'controls_arrow_right' => 'Arrow Right : Move to next column.',
    'note_heading' => 'Note',
    'note_text' => 'You have selected Gujarati language. Before you start writing, make sure you have Gujarati keyboard downloaded on your mobile. After downloading the Gujarati keyboard, you will be able to write in Gujarati.',
    'button' => 'Start Memory Test'
  ],
  'guj' => [
    'title' => 'પરીક્ષણ સૂચનાઓ',
    'requirement' => 'આવશ્યકતા',
    'requirement_text' => 'Keyboard અને Chrome વેબ બ્રાઉઝર સાથે કમ્પ્યુટર. (નોંધ: જોડણી મહત્વપૂર્ણ છે)',
    'controls' => 'નિયંત્રણો',
    'controls_char' => 'Char.keys : સક્રિય કોષમાં શબ્દ ટાઇપ કરો.',
    'controls_enter' => 'Enter : આગલા કોષ પર ધ્યાન કેન્દ્રિત કરો.',
    'controls_tab' => 'Tab : આગલા કોષ પર ધ્યાન કેન્દ્રિત કરો.',
    'controls_backspace' => 'Backspace : સક્રિય કોષ સાફ કરો',
    'controls_arrow_up' => 'Arrow Up : સમાન કૉલમમાં ઉપર જાઓ.',
    'controls_arrow_down' => 'Arrow Down : સમાન કૉલમમાં નીચે જાઓ.',
    'controls_arrow_left' => 'Arrow Left : પાછલા કૉલમમાં જાઓ.',
    'controls_arrow_right' => 'Arrow Right : આગલા કૉલમમાં જાઓ.',
    'note_heading' => '📝નોંધ',
    'note_text' => 'તમે ગુજરાતી ભાષા પસંદ કરી છે. લખવાનું શરૂ કરતા પહેલાં ખાતરી કરો કે તમારા mobile માં ગુજરાતી keyboard download છે. ગુજરાતી keyboard download કર્યા પછી તમે ગુજરાતીમાં લખી શકશો.',
    'button' => 'મેમરી પરીક્ષણ શરૂ કરો'
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
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/page5.css" />
  </head>
  <body>
    <main>
      <section id="page3">
        <div class="instruction">
          <svg
            width="48"
            height="48"
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <g clip-path="url(#clip0_118_21)">
              <path d="M48 0H0V48H48V0Z" fill="white" fill-opacity="0.01" />
              <path
                d="M24 44C29.5228 44 34.5228 41.7614 38.1421 38.1421C41.7614 34.5228 44 29.5228 44 24C44 18.4772 41.7614 13.4772 38.1421 9.85786C34.5228 6.23858 29.5228 4 24 4C18.4772 4 13.4772 6.23858 9.85786 9.85786C6.23858 13.4772 4 18.4772 4 24C4 29.5228 6.23858 34.5228 9.85786 38.1421C13.4772 41.7614 18.4772 44 24 44Z"
                fill="white"
                stroke="#9333E8"
                stroke-width="4"
                stroke-linejoin="round"
              />
              <path
                d="M24 28.6249V24.6249C27.3137 24.6249 30 21.9386 30 18.6249C30 15.3112 27.3137 12.6249 24 12.6249C20.6863 12.6249 18 15.3112 18 18.6249"
                stroke="white"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M24 37.6249C25.3807 37.6249 26.5 36.5056 26.5 35.1249C26.5 33.7442 25.3807 32.6249 24 32.6249C22.6193 32.6249 21.5 33.7442 21.5 35.1249C21.5 36.5056 22.6193 37.6249 24 37.6249Z"
                fill="white"
              />
              <path
                d="M23.7857 35.0234C24.5899 35.0234 25.2419 34.3715 25.2419 33.5672C25.2419 32.763 24.5899 32.111 23.7857 32.111C22.9814 32.111 22.3295 32.763 22.3295 33.5672C22.3295 34.3715 22.9814 35.0234 23.7857 35.0234Z"
                fill="#9333E8"
              />
              <path
                d="M25.2689 15.3378C25.061 15.1202 24.7804 15 24.4789 15H23.0924C22.7908 15 22.5103 15.1202 22.3023 15.3378C22.0944 15.556 21.9873 15.8419 22.0012 16.1435L22.5754 28.4106C22.6056 29.0562 23.1357 29.5624 23.7828 29.5624H23.7886C24.4356 29.5624 24.9658 29.0562 24.996 28.4106L25.5702 16.1435C25.5839 15.8419 25.4768 15.5561 25.2689 15.3378Z"
                fill="#9333E8"
              />
            </g>
            <defs>
              <clipPath id="clip0_118_21">
                <rect width="48" height="48" fill="white" />
              </clipPath>
            </defs>
          </svg>

          <div><h1 id="inst"><?php echo htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8'); ?></h1></div>
          <div>
            <?php if ($lang === 'guj'): ?>
            <div class="info info1">
              <h3>
                
                ⚠️ મહત્વપૂર્ણ નોંધ
              </h3>
              <p style="line-height: 1.8;">
                <strong>જોડણી ખૂબ જ મહત્વપૂર્ણ છે!</strong><br><br>
                • દરેક શબ્દ <strong>બરાબર એ જ રીતે</strong> લખો જે રીતે તમને બતાવવામાં આવ્યા હતા<br>
                • માત્રા (ા, િ, ી, ુ, ૂ, ે, ૈ, ો, ૌ) નો સાચો ઉપયોગ કરો।<br>
                • અનુસ્વાર (ં) અને વિસર્ગ (ઃ) ધ્યાનથી લખો।<br>
                • નાની જોડણી ભૂલ પણ જવાબ ખોટો ગણાશે।<br><br>
                <em>ઉદાહરણ: "કામ" અને "કમ" અલગ છે - માત્રા ભૂલશો નહીં!</em>
              </p>
            </div>
            <?php endif; ?>

            <div class="info info2">
              <h3>
                <svg
                  width="18"
                  height="18"
                  viewBox="0 0 18 18"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    opacity="0.4"
                    d="M13.7025 2.5125C13.35 2.46 12.945 2.4375 12.375 2.4375H5.625C5.0625 2.4375 4.65 2.46 4.32 2.505C1.8075 2.7825 1.3125 4.275 1.3125 6.75V11.25C1.3125 13.725 1.8075 15.2175 4.2975 15.4875C4.65 15.54 5.055 15.5625 5.625 15.5625H12.375C12.9375 15.5625 13.35 15.54 13.68 15.495C16.1925 15.2175 16.6875 13.7325 16.6875 11.25V6.75C16.6875 4.275 16.1925 2.7825 13.7025 2.5125Z"
                    fill="#292D32"
                  />
                  <path
                    d="M12.75 8.0625H10.125C9.8175 8.0625 9.5625 7.8075 9.5625 7.5C9.5625 7.1925 9.8175 6.9375 10.125 6.9375H12.75C13.0575 6.9375 13.3125 7.1925 13.3125 7.5C13.3125 7.8075 13.0575 8.0625 12.75 8.0625Z"
                    fill="#292D32"
                  />
                  <path
                    d="M7.5747 8.25C7.16221 8.25 6.82471 7.9125 6.82471 7.5C6.82471 7.0875 7.15471 6.75 7.5747 6.75H7.5822C7.9947 6.75 8.3322 7.0875 8.3322 7.5C8.3322 7.9125 7.9947 8.25 7.5747 8.25Z"
                    fill="#292D32"
                  />
                  <path
                    d="M5.32471 8.25C4.91221 8.25 4.57471 7.9125 4.57471 7.5C4.57471 7.0875 4.90471 6.75 5.32471 6.75C5.73721 6.75 6.07471 7.0875 6.07471 7.5C6.07471 7.9125 5.74471 8.25 5.32471 8.25Z"
                    fill="#292D32"
                  />
                  <path
                    d="M12.7498 12.1875H5.26482C4.95732 12.1875 4.69482 11.9325 4.69482 11.625C4.69482 11.3175 4.94232 11.0625 5.24982 11.0625H12.7498C13.0573 11.0625 13.3123 11.3175 13.3123 11.625C13.3123 11.9325 13.0573 12.1875 12.7498 12.1875Z"
                    fill="#292D32"
                  />
                </svg>
                <?php echo ($lang === 'guj') ? ' નિયંત્રણો (લેપટોપ / PC માટે)' : ' Controls (For Laptop / PC)'; ?>
              </h3>
              <p style="margin-bottom: 8px;"><strong><?php echo ($lang === 'guj') ? '💻 આ કીબોર્ડ શૉર્ટકટ્સ ફક્ત લેપટોપ અથવા PC પર જ કામ કરશે' : '💻 These keyboard shortcuts work only on Laptop or PC'; ?></strong></p>
              <p><span>Char.keys : </span> <?php echo htmlspecialchars(explode('Char.keys : ', $t['controls_char'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Enter : </span> <?php echo htmlspecialchars(explode('Enter : ', $t['controls_enter'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Tab : </span> <?php echo htmlspecialchars(explode('Tab : ', $t['controls_tab'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Backspace : </span> <?php echo htmlspecialchars(explode('Backspace : ', $t['controls_backspace'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Arrow Up : </span> <?php echo htmlspecialchars(explode('Arrow Up : ', $t['controls_arrow_up'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Arrow Down : </span> <?php echo htmlspecialchars(explode('Arrow Down : ', $t['controls_arrow_down'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Arrow Left : </span> <?php echo htmlspecialchars(explode('Arrow Left : ', $t['controls_arrow_left'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
              <p><span>Arrow Right : </span> <?php echo htmlspecialchars(explode('Arrow Right : ', $t['controls_arrow_right'])[1], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <?php if ($lang === 'guj'): ?>
            <div class="info info3">
              <h3>

                
                <?php echo htmlspecialchars($t['note_heading'], ENT_QUOTES, 'UTF-8'); ?>
              </h3>
              <p><?php echo htmlspecialchars($t['note_text'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <?php endif; ?>

            <button class="start-btn"><?php echo htmlspecialchars($t['button'], ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </div>
      </section>
    </main>
    <script>
      document.querySelector('.start-btn').addEventListener('click', function() {
        window.location.href = 'page6.php';
      });

      // Prevent user from going back
      history.pushState(null, null, location.href);
      window.onpopstate = function() {
        history.go(1);
      };
    </script>
  </body>
</html>
