(function() {
  // Expect WORDS, MEMORIZE_TIME, ATTEMPT_ID from page4.php inline script
  if (typeof WORDS === 'undefined' || !Array.isArray(WORDS)) {
    console.error('WORDS not defined or not an array');
    return;
  }

  const wordBoxesContainer = document.getElementById('word-boxes');
  const timerElement = document.getElementById('timer');
  
  // Initialize: render all words with .w-1 structure
  function renderWords(words) {
    wordBoxesContainer.innerHTML = '';
    const frag = document.createDocumentFragment();
    
    words.forEach((word, index) => {
      const wordBox = document.createElement('div');
      wordBox.className = 'w-1';
      
      const h3 = document.createElement('h3');
      h3.textContent = `#${index + 1}`;
      
      const wordT = document.createElement('div');
      wordT.className = 'w';
      wordT.textContent = word;
      
      wordBox.appendChild(h3);
      wordBox.appendChild(wordT);
      frag.appendChild(wordBox);
    });
    
    wordBoxesContainer.appendChild(frag);
  }

  // Timer countdown
  function startTimer(seconds) {
    const storageKey = `timer_start_${ATTEMPT_ID}`;
    let startTime = localStorage.getItem(storageKey);
    
    // If no start time stored, store current timestamp
    if (!startTime) {
      startTime = Date.now();
      localStorage.setItem(storageKey, startTime);
    } else {
      startTime = parseInt(startTime);
    }
    
    // Calculate elapsed time and remaining seconds
    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    let remaining = seconds - elapsed;
    
    // If time already expired, redirect immediately
    if (remaining <= 0) {
      window.location.href = 'page5.php';
      return;
    }
    
    function updateDisplay() {
      const mins = Math.floor(remaining / 60);
      const secs = remaining % 60;
      const display = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
      
      // Update timer text (replace the "00:00" in timer div)
      if (timerElement) {
        const svgElement = timerElement.querySelector('svg');
        timerElement.textContent = '';
        if (svgElement) timerElement.appendChild(svgElement);
        timerElement.appendChild(document.createTextNode(display));
      }
      
      if (remaining <= 0) {
        clearInterval(timerInterval);
        // Don't remove localStorage - keep it so back navigation knows time expired
        // Redirect to recall page
        // Notify server that memorize phase expired (so the page cannot be revisited)
        try {
          navigator.sendBeacon('../backend/db_store/mark_phase.php', new URLSearchParams({attempt_id: ATTEMPT_ID, phase: 'memorize', csrf_token: CSRF_TOKEN}));
        } catch (e) {}
        window.location.href = 'page5.php';
        return;
      }
      
      remaining--;
    }
    
    updateDisplay(); // Show initial time
    const timerInterval = setInterval(updateDisplay, 1000);
  }

  // Submit button manual navigation
  const submitBtn = document.getElementById('submit-btn');
  if (submitBtn) {
    submitBtn.addEventListener('click', function() {
      // User manually moves on — mark memorize finished so they can't return
      try { navigator.sendBeacon('../backend/db_store/mark_phase.php', new URLSearchParams({attempt_id: ATTEMPT_ID, phase: 'memorize', csrf_token: CSRF_TOKEN})); } catch(e) {}
      window.location.href = 'page5.php';
    });
  }

  // On page load
  console.log('Page4 loaded. WORDS count:', WORDS.length, 'MEMORIZE_TIME:', MEMORIZE_TIME);
  renderWords(WORDS);
  startTimer(MEMORIZE_TIME);
  // If page is restored from bfcache, reload to ensure server-side checks run
  window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
      try { location.reload(); } catch (err) { /* ignore */ }
    }
  });
})();