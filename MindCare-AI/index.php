<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MindCare AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      height: 100vh; min-height: 100svh;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      font-family: 'Segoe UI', sans-serif;
      overflow: hidden;
      position: relative;
      color: #fff;
      padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
    }

    /* Background image with blur */
    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: url('assets/img/kev.jpg') no-repeat center center/cover;
      filter: blur(2px);
      z-index: -2;
    }

    /* Dark overlay */
    body::after {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background-color: rgba(0, 0, 0, 0.6);
      z-index: -1;
    }

   

    /* Logo inside */
    .circle img {
      width: clamp(160px, 45vw, 300px);
      height: auto;
      z-index: 2;
    }

    /* Bubble animations */
    .bubble {
      position: absolute;
      bottom: -50px;
      width: 15px;
      height: 15px;
      background: rgba(0, 200, 255, 0.3);
      border-radius: 50%;
      animation: rise 6s infinite ease-in;
    }

    @keyframes rise {
      0% {
        transform: translateY(0) scale(1);
        opacity: 0.6;
      }
      100% {
        transform: translateY(-120vh) scale(0.5);
        opacity: 0;
      }
    }

    /* Spin animation */
    @keyframes spin {
      0% { transform: rotate(0); }
      100% { transform: rotate(360deg); }
    }

    /* Text styles */
    h1 {
      margin-top: 20px;
      font-size: clamp(22px, 5vw, 32px);
      color: #fff;
      font-weight: 700;
    }

    p {
      font-size: clamp(14px, 3.8vw, 16px);
      color: #ddd;
      margin-top: 8px;
    }

    /* Loading dots animation */
    .dots::after {
      content: "";
      animation: dots 1.5s steps(4, end) infinite;
    }

    @keyframes dots {
      0% { content: ""; }
      25% { content: "."; }
      50% { content: ".."; }
      75% { content: "..."; }
      100% { content: ""; }
    }

    /* Modern Welcome Box */
    .welcome-box {
      background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
      border-radius: 24px;
      padding: 40px;
      width: 100%;
      max-width: min(92vw, 420px);
      text-align: center;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.1);
      animation: fadeIn 1s cubic-bezier(0.4, 0, 0.2, 1);
      backdrop-filter: blur(12px);
      margin: 16px;
      position: relative;
      overflow: hidden;
    }

    .welcome-box::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
      opacity: 0.5;
      z-index: 0;
    }

    .welcome-box::after {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      top: -50%;
      left: -50%;
      background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 70%);
      opacity: 0.5;
      animation: rotate 10s linear infinite;
      pointer-events: none;
    }

    .welcome-box > * {
      position: relative;
      z-index: 1;
    }

    .welcome-box h2 {
      font-size: 32px;
      font-weight: 700;
      margin-bottom: 16px;
      color: #fff;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .welcome-box p {
      color: rgba(255,255,255,0.8);
      font-size: 16px;
      margin-bottom: 32px;
      line-height: 1.5;
    }

    .btn {
      border-radius: 16px;
      font-weight: 600;
      font-size: 15px;
      padding: 14px 20px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
      transform: translateX(-100%);
      transition: transform 0.6s ease;
    }

    .btn:hover::before {
      transform: translateX(100%);
    }

    .btn-login {
      background: #fff;
      color: #000;
      box-shadow: 0 8px 16px rgba(255,255,255,0.2);
    }

    .btn-login:hover {
      background: #f8f9fa;
      color: #000;
      transform: translateY(-2px);
      box-shadow: 0 12px 20px rgba(255,255,255,0.3);
    }

    .btn-signup {
      background: transparent;
      border: 2px solid rgba(255,255,255,0.8);
      color: #fff;
    }

    .btn-signup:hover {
      background: rgba(255,255,255,0.1);
      border-color: #fff;
      color: #fff;
      transform: translateY(-2px);
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Reduced motion preference */
    @media (prefers-reduced-motion: reduce) {
      .bubble { animation: none !important; }
      .dots::after { animation: none !important; content: '...'; }
    }

    @media (max-width: 576px) {
      .bubble { width: 10px; height: 10px; }
    }

    @media (max-width: 480px) {
      .welcome-box {
        padding: 28px 20px;
      }
      h1 { margin-top: 12px; }
    }
  </style>
</head>
<body>

  <!-- Loading screen -->
  <div class="circle">
    <img src="assets/img/brain2.png" alt="MindCare-AI Logo">
  </div>
  <h1>MindCare-AI</h1>
  <p class="dots">Loading</p>

  <!-- Floating bubbles -->
  <div class="bubble" style="left: 20%; animation-delay: 0s; animation-duration: 5s;"></div>
  <div class="bubble" style="left: 40%; animation-delay: 2s; animation-duration: 6s;"></div>
  <div class="bubble" style="left: 60%; animation-delay: 1s; animation-duration: 7s;"></div>
  <div class="bubble" style="left: 80%; animation-delay: 3s; animation-duration: 4s;"></div>
  <div class="bubble" style="left: 50%; animation-delay: 4s; animation-duration: 6s;"></div>

  <?php if (!isset($_SESSION['user_id'])): ?>
    <script>
      // Show welcome screen after loading animation
      setTimeout(function() {
        document.body.innerHTML = `
          <div class="welcome-box">
            <h2>Welcome Back!</h2>
            <p>You are not alone. Let's talk..</p>
            <div class="d-grid gap-3">
              <a href="auth/login.php" class="btn btn-login">Log In</a>
              <a href="auth/register.php" class="btn btn-signup">Sign Up for Free</a>
            </div>
          </div>`;
      }, 4000);
    </script>
  <?php else: ?>
    <script>
      // Redirect logged-in users to dashboard after animation
      setTimeout(function() {
        window.location.href = "dashboard/user_dashboard.php";
      }, 4000);
    </script>
  <?php endif; ?>

</body>
</html>
