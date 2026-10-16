<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="login.css" />
</head>
<body>
  <main class="auth">
    <section class="card" aria-labelledby="login-title">
      <div class="brand">
        <div class="logo" aria-hidden="true">🔐</div>
        <h1 id="login-title">Admin Panel</h1>
        <p class="subtitle">Sign in to continue</p>
      </div>

      <?php
      // Build base path dynamically so form action works in any folder
      $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
      $basePath = preg_replace('#/(admin_panel|backend)(/.*)?$#', '', $scriptDir);
      $basePath = ($basePath === '') ? '' : $basePath;
      $loginAction = $basePath . '/backend/admin/login.php';
      ?>

        <?php
        if (session_status() !== PHP_SESSION_ACTIVE) {
          session_start();
        }
        if (!empty(
          
          
          
      
      
      
      $_SESSION['admin_error'])) {
        echo '<div style="color:#ffdddd; padding:8px; background:#661111; border-radius:6px; margin-bottom:12px;">' . htmlspecialchars($_SESSION['admin_error']) . '</div>';
        unset($_SESSION['admin_error']);
      }
      ?>

      <form class="form" action="<?= htmlspecialchars($loginAction) ?>" method="post" novalidate autocomplete="off">
        <div class="field">
          <label for="email">Email or Username</label>
          <input
            type="text"
            id="email"
            name="email"
            inputmode="email"
            autocomplete="off"
            placeholder="you@example.com"
            required
            aria-required="true"
          />
        </div>

        <div class="field">
          <div class="label-row">
            <label for="password">Password</label>
            <!-- <a class="link" href="#" aria-label="Forgot password (not implemented)">Forgot?</a> -->
          </div>
          <input
            type="password"
            id="password"
            name="password"
            autocomplete="off"
            placeholder="••••••••"
            minlength="6"
            required
            aria-required="true"
          />
        </div>

        

        <button class="submit" type="submit" aria-label="Sign in">Sign In</button>

      </form>
    </section>
  </main>
</body>
</html>
