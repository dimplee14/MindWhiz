<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document</title>
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/page2.css" />

  <script src="../script/page2.js" defer></script>
</head>

<body>
  <?php
  // Initialize session to store CSRF token and surface server-side errors
  session_start();

  // Check if email has been verified
  if (empty($_SESSION['verified_email'])) {
    header('Location: email_verify.php');
    exit();
  }

  $verified_email = $_SESSION['verified_email'];

  // Create a per-session CSRF token if one is not already present
  if (empty($_SESSION['csrf_token'])) {
    try {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
      $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
  }
  // Pull any error message set by the backend (e.g., duplicate email or attempt limit)
  if (!empty($_SESSION['error'])) {
    $err = $_SESSION['error'];
    unset($_SESSION['error']);
  }
  ?>
  <main>
    <section id="page2">
      <?php if (!empty($err)): ?>
        <!-- Server-side validation/limit errors surface here for the user -->
        <div class="form-error" style="color:#b91c1c;margin:12px 0;padding:12px;background:#fee2e2;border-radius:6px;">
          <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form action="../backend/db_store/save_students.php" method="POST" autocomplete="off">
        <!-- CSRF token pairs with backend validation in save_students.php -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($verified_email, ENT_QUOTES, 'UTF-8'); ?>">
        <div>
          <h1 id="student-info">Student Information</h1>
        </div>

        <div class="form-group">
          <label for="name">Name*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter your first name</small>
          <input
            type="text"
            name="name"
            placeholder="e.g., Raj"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>



        <div class="form-group">
          <label for="surname">Surname*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter your family/last name</small>
          <input
            type="text"
            name="surname"
            placeholder="e.g., Patel"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>

        <div class="form-group">
          <label for="parentname">Parent Name*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter your father's or mother's first name only</small>
          <input
            type="text"
            name="parentname"
            placeholder="e.g., Ramesh"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>

        <div class="form-group">
          <label for="email">Email*</label><br />
          <div style="padding: 10px; flex-wrap: wrap; background: #e0f2fe; border: 1px solid #0284c7; border-radius: 4px; color: #0c4a6e; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <strong>✓ Verified:</strong> <?php echo htmlspecialchars($verified_email, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <button type="button" id="change-email-btn" onclick="handleChangeEmail(event)" style="background: #0284c7; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">Change Email</button>
          </div>
          <small style="color: #666; margin-top: 4px; margin-bottom: 0; display: block; font-size: 12px;">Your email has been verified</small>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter mobile number with or without country code</small>
          <input
            type="tel"
            name="phone"
            placeholder="e.g., 9876543210 or +91 9876543210"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>

        <div class="form-group">
          <label for="Standard">Standard*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter your current class/grade</small>
          <input
            type="text"
            name="standard"
            placeholder="e.g., 10th, 12th"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>

        <div class="form-group">
          <label for="school">School name*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter your school's full name</small>
          <input
            type="text"
            name="school"
            placeholder="e.g., St. Xavier's High School"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>

        <div class="form-group">
          <label for="city">City*</label>
          <small style="color: #666; display: block; font-size: 12px; margin-bottom: 5px;">Enter the city where you live</small>
          <input
            type="text"
            name="city"
            placeholder="e.g., Ahmedabad"
            class="input-field"
            autocomplete="off"
            required />
          <span class="error-message"></span>
        </div>

        <div class="form-group">
          <label for="language">Language*</label><br />
          <select name="language" class="input-field" required>
            <option value="eng" selected>English</option>
            <option value="guj">ગુજરાતી</option>
          </select>
          <span class="error-message"></span>
        </div>

        <div>
          <!-- Submit is initially disabled; page2.js handles client-side validation and enabling -->
          <input type="submit" class="page-2-start-btn" value="Start Test" disabled />
        </div>
      </form>
    </section>
  </main>

  <script>
    // Handle change email button click
    function handleChangeEmail(event) {
      event.preventDefault();
      if (confirm('Do you want to verify a different email address? You will need to verify the new email.')) {
        window.location.href = '../html/email_verify.php?change=true';
      }
    }

    // Clear all form fields on page load to prevent autocomplete and stale data
    window.addEventListener('pageshow', function(event) {
      const form = document.querySelector('form');
      const inputs = form.querySelectorAll('input[type="text"], input[type="tel"]');
      inputs.forEach(input => {
        if (input.name !== 'csrf_token' && input.name !== 'email') {
          input.value = '';
        }
      });
      // Reset select to English by default
      const languageSelect = form.querySelector('select[name="language"]');
      if (languageSelect) {
        languageSelect.value = 'eng';
      }
    });
  </script>

</body>

</html>