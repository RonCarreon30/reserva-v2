<?php
  // Start the session
  session_start();

  // Check if the user is logged in
  $isLoggedIn = isset($_SESSION['user_id']);
  $isAuthorized = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';

  // Determine the message and redirection based on authorization
  if (!$isLoggedIn) {
      $message = "You need to log in.";
      $redirectURL = "index";
  } elseif (!$isAuthorized) {
      $message = "You don't have the privilege to access this page.";
      $redirectURL = "back"; // Placeholder to use `window.history.back()`
  }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PLV: RESERVA</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
      <link rel="stylesheet" href="css/style.css">
  <style>
    /* Enhanced loader styling */
    .spinner {
      border: 4px solid #f3f3f3;
      border-top: 4px solid #3490dc;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-200">
  <div class="bg-white p-10 rounded-lg shadow-lg text-center">
    <img src="img/undraw_taken_re_yn20.svg" alt="Unauthorized Access" class="mx-auto mb-4 rounded w-1/2" />

    <h1 class="text-3xl font-bold text-red-500 mb-2">Unauthorized Access</h1>
    <p class="text-gray-600 mb-4">
      <?php echo $message; ?> Redirecting you shortly...
    </p>

    <!-- Loader Animation -->
    <div class="flex justify-center items-center mb-4">
      <div class="spinner"></div>
    </div>

    <!-- Countdown Timer -->
    <p class="text-gray-600">Redirecting in <span id="countdown">3</span> seconds...</p>
  </div>

  <script>
    let countdown = 3;
    const countdownElement = document.getElementById("countdown");

    // Countdown timer
    const interval = setInterval(() => {
      countdown--;
      countdownElement.textContent = countdown;
      countdownElement.classList.add("animate-pulse"); // Adds animation for a quick pulse effect
      setTimeout(() => countdownElement.classList.remove("animate-pulse"), 500);

      if (countdown === 0) {
        clearInterval(interval);

        // Dynamic redirection based on PHP's $redirectURL variable
        <?php if ($redirectURL === "back"): ?>
          window.history.back();
        <?php else: ?>
          window.location.href = "<?php echo $redirectURL; ?>";
        <?php endif; ?>
      }
    }, 1000);
  </script>
</body>
</html>
