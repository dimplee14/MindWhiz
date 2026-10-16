const input = document.querySelector(".input-boxes");
const storageKey = `answers_${ATTEMPT_ID}`;
const lastFocusKey = `lastFocus_${ATTEMPT_ID}`;
const timerElement = document.getElementById('timer');
const wordCountDisplay = document.getElementById('word-count');
const submitBtn = document.getElementById('submit-btn');
const form = document.getElementById('recall-form');

// Create 60 input boxes
for (let i = 0; i < TOTAL_WORDS; i++) {
  let index = i + 1;
  let h2 = document.createElement("h2");
  h2.innerHTML = `word #${index}`;
  let inputBox = document.createElement("input");
  inputBox.type = "text";
  inputBox.className = "input-box";
  inputBox.name = `answer_${index}`;
  inputBox.id = `word-${index}`;
  inputBox.placeholder = `word ${index}`;
  let wordBox = document.createElement("div");
  wordBox.className = "word"; 
  wordBox.appendChild(h2);
  wordBox.appendChild(inputBox);
  input.appendChild(wordBox);
  
  // Auto-save on input change (debounced)
  inputBox.addEventListener('input', debounce(saveAnswers, 500));
  
  // Track last focused input
  inputBox.addEventListener('focus', () => {
    localStorage.setItem(lastFocusKey, index);
  });
  
  // Move to next box on Enter
  inputBox.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const nextInput = document.getElementById(`word-${index + 1}`);
      if (nextInput) {
        nextInput.focus();
      }
    }
    // Arrow Right = same row, next column (1→21→41)
    else if (e.key === 'ArrowRight') {
      e.preventDefault();
      if (index <= 40) { // Can move right if not in last column
        const nextInput = document.getElementById(`word-${index + 20}`);
        if (nextInput) {
          nextInput.focus();
        }
      }
    }
    // Arrow Left = same row, previous column (41→21→1)
    else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      if (index > 20) { // Can move left if not in first column
        const prevInput = document.getElementById(`word-${index - 20}`);
        if (prevInput) {
          prevInput.focus();
        }
      }
    }
    // Arrow Down = next word in same column
    else if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (index % 20 !== 0) { // Not at bottom of column
        const nextInput = document.getElementById(`word-${index + 1}`);
        if (nextInput) {
          nextInput.focus();
        }
      }
    }
    // Arrow Up = previous word in same column
    else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (index % 20 !== 1) { // Not at top of column
        const prevInput = document.getElementById(`word-${index - 1}`);
        if (prevInput) {
          prevInput.focus();
        }
      }
    }
  });
}

// Load saved answers from localStorage on page load
function loadAnswers() {
  const saved = localStorage.getItem(storageKey);
  if (saved) {
    try {
      const answers = JSON.parse(saved);
      answers.forEach((answer, idx) => {
        const input = document.getElementById(`word-${idx + 1}`);
        if (input) input.value = answer;
      });
    } catch (e) {
      console.error('Error loading answers:', e);
    }
  }
}

// Save all answers to localStorage
function saveAnswers() {
  const answers = [];
  for (let i = 1; i <= TOTAL_WORDS; i++) {
    const input = document.getElementById(`word-${i}`);
    answers.push(input ? input.value.trim() : '');
  }
  localStorage.setItem(storageKey, JSON.stringify(answers));
  updateWordCount();
}

// Update word counter
function updateWordCount() {
  let filled = 0;
  for (let i = 1; i <= TOTAL_WORDS; i++) {
    const input = document.getElementById(`word-${i}`);
    if (input && input.value.trim() !== '') filled++;
  }
  wordCountDisplay.textContent = `Words filled: ${filled} / ${TOTAL_WORDS}`;
}

// Debounce helper
function debounce(func, delay) {
  let timeoutId;
  return function() {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(func, delay);
  };
}

// Timer countdown
function startTimer(seconds) {
  const storageTimerKey = `timer_recall_start_${ATTEMPT_ID}`;
  let startTime = localStorage.getItem(storageTimerKey);
  
  if (!startTime) {
    startTime = Date.now();
    localStorage.setItem(storageTimerKey, startTime);
  } else {
    startTime = parseInt(startTime);
  }
  
  const elapsed = Math.floor((Date.now() - startTime) / 1000);
  let remaining = seconds - elapsed;
  
  if (remaining <= 0) {
    submitForm();
    return;
  }
  
  function updateDisplay() {
    const mins = Math.floor(remaining / 60);
    const secs = remaining % 60;
    const display = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    
    if (timerElement) {
      const svgElement = timerElement.querySelector('svg');
      timerElement.textContent = '';
      if (svgElement) timerElement.appendChild(svgElement);
      timerElement.appendChild(document.createTextNode(display));
    }
    
    if (remaining <= 0) {
      clearInterval(timerInterval);
      submitForm();
      return;
    }
    
    remaining--;
  }
  
  updateDisplay();
  const timerInterval = setInterval(updateDisplay, 1000);
}

// Submit form
function submitForm() {
  saveAnswers(); // Save before submit
  try {
    navigator.sendBeacon('../backend/db_store/mark_phase.php', new URLSearchParams({attempt_id: ATTEMPT_ID, phase: 'recall', csrf_token: CSRF_TOKEN}));
  } catch (e) {}
  if (form) form.submit();
  else window.location.href = 'page7.php';
}

// Event listeners
if (submitBtn) {
  submitBtn.addEventListener('click', function(e) {
    e.preventDefault();
    submitForm();
  });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  console.log('Page6 loaded. TOTAL_WORDS:', TOTAL_WORDS, 'RECALL_TIME:', RECALL_TIME);
  loadAnswers();
  updateWordCount();
  startTimer(RECALL_TIME);
  
  // Restore focus to last focused word or default to word #1
  const lastFocus = localStorage.getItem(lastFocusKey);
  const focusWordId = lastFocus ? `word-${lastFocus}` : 'word-1';
  const focusInput = document.getElementById(focusWordId);
  if (focusInput) {
    focusInput.focus();
  }
});

// If page is restored from bfcache, reload to ensure server-side checks run
window.addEventListener('pageshow', function(e) {
  if (e.persisted) {
    try { location.reload(); } catch (err) { /* ignore */ }
  }
});
