<?php
/**
 * Email Verification Page
 * Students must verify their email with a code before proceeding to registration
 */
session_start();
require_once __DIR__ . '/../backend/config/test_config.php';

// Handle email change from page2
if (isset($_GET['change']) && $_GET['change'] === 'true') {
    // Clear previous verification so user can enter a new email
    unset($_SESSION['verified_email']);
    unset($_SESSION['verification_email']);
    unset($_SESSION['verification_code_id']);
}

// Check if verification already completed
if (!empty($_SESSION['verified_email']) && !isset($_GET['change'])) {
    header('Location: page2.php');
    exit();
}

// Display any error/success messages
$message = '';
$message_type = '';
if (!empty($_SESSION['verify_message'])) {
    $message = $_SESSION['verify_message'];
    $message_type = $_SESSION['verify_message_type'] ?? 'error';
    unset($_SESSION['verify_message']);
    unset($_SESSION['verify_message_type']);
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Check if code was just sent
$showStep2 = false;
$sentEmail = '';
if (isset($_GET['sent']) && $_GET['sent'] == '1' && !empty($_GET['email'])) {
    $showStep2 = true;
    $sentEmail = $_GET['email'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Email Verification</title>
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/email_verify.css" />
</head>
<body class="verify-page">
    <main class="verify-viewport">
        <section class="verify-card">
            <div class="verify-header">
                <div class="verify-icon">📧</div>
                <div>
                    <p class="verify-kicker">Secure access</p>
                    <h1 class="verify-title">Verify your email</h1>
                    <p class="verify-subtitle">We'll send a verification code to confirm this is your email address.</p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Step 1: Send Code -->
            <div id="step-1" class="verify-step <?php echo $showStep2 ? 'is-hidden' : 'is-active'; ?>">
                <form class="verify-form" action="../backend/email_handler/send_code.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    
                    <div class="form-group">
                        <label for="email">Email Address*</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="your.email@example.com"
                            value="<?php echo htmlspecialchars($sentEmail, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        />
                        <span class="error-message"></span>
                        <div class="help-text">We'll send a verification code to this email</div>
                    </div>

                    <button type="submit" class="submit-btn" id="send-btn">Send verification code</button>
                </form>
            </div>

            <!-- Step 2: Verify Code -->
            <div id="step-2" class="verify-step <?php echo $showStep2 ? 'is-active' : 'is-hidden'; ?>">
                <form class="verify-form" action="../backend/email_handler/verify_code.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="email" id="hidden-email" value="<?php echo htmlspecialchars($sentEmail, ENT_QUOTES, 'UTF-8'); ?>">

                    <p class="verify-note">
                        A 6-digit code has been sent to <strong id="display-email"><?php echo htmlspecialchars($sentEmail, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </p>

                    <div class="form-group">
                        <label for="code">Verification Code*</label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            placeholder="000000"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            required
                        />
                        <span class="error-message"></span>
                        <div class="help-text">Enter the 6-digit code sent to your email</div>
                    </div>

                    <div class="verify-actions">
                        <button type="submit" class="submit-btn">Verify &amp; continue</button>
                        <button type="button" class="secondary-btn" id="back-btn">Back</button>
                    </div>

                    <div class="resend-section">
                        <p class="resend-text">Didn't receive the code?</p>
                        <button type="button" class="resend-btn" id="resend-btn">Resend code</button>
                        <div class="resend-timer" id="resend-timer"></div>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        const emailInput = document.getElementById('email');
        const sendBtn = document.getElementById('send-btn');
        const step1 = document.getElementById('step-1');
        const step2 = document.getElementById('step-2');
        const backBtn = document.getElementById('back-btn');
        const resendBtn = document.getElementById('resend-btn');
        const codeInput = document.getElementById('code');
        const hiddenEmail = document.getElementById('hidden-email');
        const displayEmail = document.getElementById('display-email');
        const codeExpiryDisplay = document.getElementById('code-expiry');
        const resendTimerDisplay = document.getElementById('resend-timer');

        let codeExpiryTime = null;
        let resendCooldown = 0;

        // Validate email format
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        // Start code expiry timer (10 minutes)
        function startCodeTimer() {
            codeExpiryTime = Date.now() + (10 * 60 * 1000);
            
            const timerInterval = setInterval(() => {
                const remaining = codeExpiryTime - Date.now();
                if (remaining <= 0) {
                    clearInterval(timerInterval);
                    codeExpiryDisplay.textContent = '0:00';
                    codeInput.disabled = true;
                    return;
                }
                
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                codeExpiryDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }

        // Start resend cooldown timer (2 minutes between resends)
        function startResendCooldown() {
            resendCooldown = 120;
            resendBtn.disabled = true;
            
            const cooldownInterval = setInterval(() => {
                resendCooldown--;
                if (resendCooldown <= 0) {
                    clearInterval(cooldownInterval);
                    resendBtn.disabled = false;
                    resendTimerDisplay.textContent = '';
                    return;
                }
                
                resendTimerDisplay.textContent = `Resend available in ${resendCooldown}s`;
            }, 1000);
        }

        // Email input validation
        emailInput.addEventListener('input', () => {
            const isValid = validateEmail(emailInput.value.trim());
            const errorSpan = emailInput.nextElementSibling;
            
            if (isValid) {
                emailInput.classList.remove('error');
                errorSpan.classList.remove('show');
                errorSpan.textContent = '';
                sendBtn.disabled = false;
            } else if (emailInput.value.trim() !== '') {
                emailInput.classList.add('error');
                errorSpan.classList.add('show');
                errorSpan.textContent = 'Please enter a valid email address';
                sendBtn.disabled = true;
            } else {
                emailInput.classList.remove('error');
                errorSpan.classList.remove('show');
                sendBtn.disabled = true;
            }
        });

        // Code input - only allow digits
        codeInput.addEventListener('input', () => {
            codeInput.value = codeInput.value.replace(/[^0-9]/g, '');
        });

        function setStep(activeStep) {
            if (activeStep === 1) {
                step1.classList.add('is-active');
                step1.classList.remove('is-hidden');
                step2.classList.remove('is-active');
                step2.classList.add('is-hidden');
            } else {
                step2.classList.add('is-active');
                step2.classList.remove('is-hidden');
                step1.classList.remove('is-active');
                step1.classList.add('is-hidden');
            }
        }

        // Back button
        backBtn.addEventListener('click', () => {
            setStep(1);
            emailInput.value = hiddenEmail.value;
            codeInput.value = '';
        });

        // Resend code button
        resendBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const email = hiddenEmail.value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            
            if (!email) {
                alert('Email not found');
                return;
            }

            try {
                const response = await fetch('../backend/email_handler/resend_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken) + 
                          '&email=' + encodeURIComponent(email)
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    startResendCooldown();
                    codeInput.value = '';
                    codeInput.focus();
                } else {
                    alert(data.message || 'Error resending code');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        });

        // Handle form submission for sending code
        step1.querySelector('form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            if (!validateEmail(email)) {
                const errorSpan = emailInput.nextElementSibling;
                errorSpan.classList.add('show');
                errorSpan.textContent = 'Please enter a valid email address';
                return;
            }

            // Submit form to send email
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';
            
            const formData = new FormData(step1.querySelector('form'));
            
            try {
                const response = await fetch('../backend/email_handler/send_code.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Redirect to show message and step 2
                window.location.href = 'email_verify.php?email=' + encodeURIComponent(email) + '&sent=1';
                
            } catch (error) {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send verification code';
                alert('Error sending code. Please try again.');
            }
        });

        // Initialize on load
        window.addEventListener('DOMContentLoaded', () => {
            // If step 2 is visible, start timers
            if (step2.classList.contains('is-active')) {
                startCodeTimer();
                startResendCooldown();
                codeInput.focus();
            }
            
            // Initially disable send button
            sendBtn.disabled = true;
        });
    </script>
</body>
</html>
