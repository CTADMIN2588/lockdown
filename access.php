<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DBI Infomatics | Agent Portal</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      color: #e2e8f0;
      position: relative;
      overflow: hidden;
    }

    /* Animated gradient background */
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(14, 165, 233, 0.1) 0%, transparent 50%);
      animation: gradientShift 15s ease infinite;
      z-index: -1;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      animation: slideUp 0.8s ease-out;
    }

    .login-card {
      background: rgba(30, 41, 59, 0.8);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.3),
        0 1px 0 rgba(255, 255, 255, 0.1) inset,
        0 -1px 0 rgba(0, 0, 0, 0.5) inset;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.1);
      transform: translateY(0);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .login-card:hover {
      transform: translateY(-5px);
      box-shadow: 
        0 12px 40px rgba(0, 0, 0, 0.4),
        0 1px 0 rgba(255, 255, 255, 0.15) inset,
        0 -1px 0 rgba(0, 0, 0, 0.6) inset;
    }

    /* Header Section */
    .login-header {
      padding: 40px 40px 20px;
      text-align: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(15, 23, 42, 0.6);
      position: relative;
      overflow: hidden;
    }

    /* Floating particles background */
    .particles {
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: 0;
    }

    .particle {
      position: absolute;
      background: rgba(59, 130, 246, 0.2);
      border-radius: 50%;
      animation: float 20s infinite linear;
      box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }

    .particle:nth-child(1) {
      width: 6px;
      height: 6px;
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }

    .particle:nth-child(2) {
      width: 4px;
      height: 4px;
      top: 60%;
      left: 80%;
      animation-delay: -5s;
    }

    .particle:nth-child(3) {
      width: 8px;
      height: 8px;
      top: 80%;
      left: 20%;
      animation-delay: -10s;
    }

    .particle:nth-child(4) {
      width: 5px;
      height: 5px;
      top: 40%;
      left: 90%;
      animation-delay: -15s;
    }

    /* DBI Logo Container */
    .dbi-logo-container {
      position: relative;
      height: 80px;
      margin-bottom: 15px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Enhanced Circle Animation */
    .minimal-circle {
      position: absolute;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 1.5px solid rgba(59, 130, 246, 0.3);
      z-index: 1;
      opacity: 0;
      animation: circleAppear 1.2s ease-out 0.3s forwards;
      box-shadow: 0 0 30px rgba(59, 130, 246, 0.2);
    }

    .circle-dot {
      position: absolute;
      width: 8px;
      height: 8px;
      background: linear-gradient(45deg, #3b82f6, #8b5cf6);
      border-radius: 50%;
      top: -4px;
      left: 50%;
      transform: translateX(-50%);
      animation: rotateMinimal 8s linear infinite;
      box-shadow: 0 0 15px rgba(59, 130, 246, 0.8);
    }

    /* DBI Logo with enhanced animations */
    .dbi-logo {
      text-align: center;
      position: relative;
      z-index: 2;
    }

    .dbi-main {
      font-size: 24px;
      font-weight: 700;
      letter-spacing: 1px;
      margin-bottom: 4px;
      background: linear-gradient(90deg, #e2e8f0, #94a3b8, #e2e8f0);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      opacity: 0;
      animation: textGlow 1s ease-out 0.8s forwards, pulseSoft 3s infinite 2s;
      text-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
    }

    .dbi-sub {
      font-size: 12px;
      font-weight: 500;
      letter-spacing: 2px;
      color: #94a3b8;
      margin-bottom: 6px;
      opacity: 0;
      animation: textAppear 0.8s ease-out 1s forwards, floatSubtitle 6s ease-in-out infinite 3s;
    }

    /* Form Section with animations */
    .login-form {
      padding: 30px 40px;
      animation: fadeInForm 1s ease-out 1.6s forwards;
      opacity: 0;
    }

    .form-title {
      color: #e2e8f0;
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 24px;
      text-align: center;
      position: relative;
      padding-bottom: 10px;
    }

    .form-title::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 2px;
      background: linear-gradient(to right, transparent, #3b82f6, transparent);
      animation: lineExpand 1.2s ease-out 2s forwards;
      transform-origin: center;
      transform: scaleX(0);
    }

    .form-group {
      margin-bottom: 20px;
      animation: slideInRight 0.6s ease-out forwards;
      opacity: 0;
      transform: translateX(-20px);
    }

    .form-group:nth-child(1) {
      animation-delay: 2.2s;
    }

    .form-group:nth-child(2) {
      animation-delay: 2.4s;
    }

    .form-label {
      display: block;
      color: #cbd5e1;
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 6px;
      transform: translateY(0);
      transition: transform 0.3s ease, color 0.3s ease;
    }

    .form-label:hover {
      transform: translateX(5px);
      color: #e2e8f0;
    }

    .input-with-icon {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .form-control {
      width: 100%;
      padding: 12px 12px 12px 42px;
      border: 1px solid #475569;
      border-radius: 8px;
      font-size: 14px;
      color: #e2e8f0;
      transition: all 0.3s ease;
      background: rgba(15, 23, 42, 0.6);
    }

    .form-control:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
      transform: translateY(-1px);
      background: rgba(15, 23, 42, 0.8);
    }

    .form-control:focus + .input-icon {
      color: #3b82f6;
      transform: translateY(-50%) scale(1.1);
      text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
    }

    .form-control::placeholder {
      color: #64748b;
      transition: color 0.3s ease;
    }

    .form-control:focus::placeholder {
      color: #94a3b8;
    }

    .password-toggle {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #64748b;
      cursor: pointer;
      font-size: 16px;
      padding: 0;
      transition: all 0.3s ease;
    }

    .password-toggle:hover {
      color: #3b82f6;
      transform: translateY(-50%) scale(1.1);
      text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
    }

    /* FIXED: Enhanced Login Button - Removed visibility issues */
    .login-btn {
      width: 100%;
      background: linear-gradient(90deg, #3b82f6, #2563eb);
      color: white;
      border: none;
      padding: 14px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      animation: slideInUp 0.8s ease-out 2.6s forwards;
      opacity: 0;
      transform: translateY(20px);
      box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }

    /* REMOVED the problematic ::before pseudo-element that was causing invisibility */
    /* .login-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: 0.5s;
    } */

    .login-btn:hover {
      background: linear-gradient(90deg, #2563eb, #1d4ed8);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(37, 99, 235, 0.5);
    }

    /* REMOVED this problematic hover effect */
    /* .login-btn:hover::before {
      left: 100%;
    } */

    .login-btn:active {
      transform: translateY(0);
      box-shadow: 0 2px 10px rgba(37, 99, 235, 0.3);
    }

    /* NEW: Add shine effect without visibility issues */
    .login-btn::after {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(
        to right,
        rgba(255, 255, 255, 0) 0%,
        rgba(255, 255, 255, 0.1) 50%,
        rgba(255, 255, 255, 0) 100%
      );
      transform: rotate(30deg);
      transition: transform 0.6s;
      opacity: 0;
    }

    .login-btn:hover::after {
      opacity: 1;
      transform: rotate(30deg) translateX(100%);
    }

    .btn-loader {
      display: none;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s linear infinite;
    }

    .login-btn.loading .btn-text {
      visibility: hidden;
    }

    .login-btn.loading .btn-loader {
      display: block;
      position: absolute;
    }

    /* Enhanced Status Message */
    .status-message {
      padding: 12px 16px;
      border-radius: 8px;
      margin: 16px 0;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      animation: shake 0.5s ease-out;
      transform-origin: center;
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .status-message.error {
      color: #fca5a5;
      border-color: rgba(220, 38, 38, 0.3);
      box-shadow: 0 0 15px rgba(220, 38, 38, 0.2);
    }

    .status-message.success {
      color: #86efac;
      border-color: rgba(34, 197, 94, 0.3);
      box-shadow: 0 0 15px rgba(34, 197, 94, 0.2);
      animation: bounceIn 0.6s ease-out;
    }

    .status-message i {
      font-size: 16px;
    }

    /* Footer */
    .login-footer {
      text-align: center;
      padding: 20px 40px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: #94a3b8;
      font-size: 12px;
      animation: fadeIn 1s ease-out 2.8s forwards;
      opacity: 0;
      background: rgba(15, 23, 42, 0.6);
    }

    .copyright {
      opacity: 0.7;
      transition: opacity 0.3s ease, color 0.3s ease;
    }

    .copyright:hover {
      opacity: 1;
      color: #cbd5e1;
    }

    /* Animation Keyframes */
    @keyframes gradientShift {
      0%, 100% {
        transform: scale(1) rotate(0deg);
      }
      25% {
        transform: scale(1.05) rotate(5deg);
      }
      50% {
        transform: scale(1) rotate(0deg);
      }
      75% {
        transform: scale(1.05) rotate(-5deg);
      }
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.6;
      }
      25% {
        transform: translateY(-15px) rotate(90deg);
        opacity: 0.8;
      }
      50% {
        transform: translateY(0) rotate(180deg);
        opacity: 0.6;
      }
      75% {
        transform: translateY(15px) rotate(270deg);
        opacity: 0.8;
      }
    }

    @keyframes circleAppear {
      0% {
        opacity: 0;
        transform: scale(0.8) rotate(-180deg);
      }
      100% {
        opacity: 1;
        transform: scale(1) rotate(0);
      }
    }

    @keyframes rotateMinimal {
      0% {
        transform: translateX(-50%) rotate(0deg) translateY(40px) rotate(0deg);
      }
      100% {
        transform: translateX(-50%) rotate(360deg) translateY(40px) rotate(-360deg);
      }
    }

    @keyframes textGlow {
      0% {
        opacity: 0;
        transform: translateY(10px);
        filter: blur(5px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
        filter: blur(0);
      }
    }

    @keyframes textAppear {
      from {
        opacity: 0;
        transform: translateY(5px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes floatSubtitle {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-3px);
      }
    }

    @keyframes pulseSoft {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: 0.9;
      }
    }

    @keyframes fadeInForm {
      to {
        opacity: 1;
      }
    }

    @keyframes lineExpand {
      to {
        transform: translateX(-50%) scaleX(1);
      }
    }

    @keyframes slideInRight {
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes slideInUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    @keyframes fadeIn {
      to {
        opacity: 1;
      }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    @keyframes bounceIn {
      0% { 
        transform: scale(0.3); 
        opacity: 0; 
      }
      50% { 
        transform: scale(1.05); 
      }
      70% { 
        transform: scale(0.9); 
      }
      100% { 
        transform: scale(1); 
        opacity: 1; 
      }
    }

    /* Responsive Design */
    @media (max-width: 480px) {
      .login-card {
        border-radius: 14px;
      }

      .login-header {
        padding: 30px 20px 15px;
      }

      .login-form {
        padding: 25px 20px;
      }

      .dbi-main {
        font-size: 22px;
      }

      .minimal-circle {
        width: 70px;
        height: 70px;
      }

      .login-footer {
        padding: 15px 20px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <!-- Header Section -->
      <div class="login-header">
        <!-- Floating particles -->
        <div class="particles">
          <div class="particle"></div>
          <div class="particle"></div>
          <div class="particle"></div>
          <div class="particle"></div>
        </div>
        
        <div class="dbi-logo-container">
          <!-- Minimal Circle Animation -->
          <div class="minimal-circle">
            <div class="circle-dot"></div>
          </div>
          
          <!-- DBI Logo -->
          <div class="dbi-logo">
            <div class="dbi-main">Firewall Login</div>
          </div>
        </div>
      </div>

      <!-- Form Section -->
      <div class="login-form">

        
        <form action="inc/code.php" method="post" id="loginForm">
          <!-- Username Field -->
          <div class="form-group">
            <label class="form-label" for="user">Username</label>
            <div class="input-with-icon">
              <i class="fas fa-user input-icon"></i>
              <input 
                type="text" 
                id="user" 
                name="user" 
                class="form-control" 
                placeholder="Enter your username" 
                required
              >
            </div>
          </div>

          <!-- Password Field -->
          <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="input-with-icon">
              <i class="fas fa-lock input-icon"></i>
              <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control" 
                placeholder="Enter your password" 
                required
              >
              <button type="button" class="password-toggle" id="togglePassword">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button type="submit" name="submit" class="login-btn">
            <span class="btn-text">
              <i class="fas fa-sign-in-alt"></i>
              Sign In
            </span>
            <div class="btn-loader"></div>
          </button>

          <!-- Status Message -->
          <?php if(isset($_SESSION['status'])): ?>
            <div class="status-message <?php echo strpos($_SESSION['status'], 'success') !== false ? 'success' : 'error'; ?>">
              <i class="fas <?php echo strpos($_SESSION['status'], 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
              <p><?php echo $_SESSION['status']; ?></p>
            </div>
          <?php 
            unset($_SESSION['status']);
            endif; 
          ?>
        </form>
      </div>

      <!-- Footer -->

    </div>
  </div>

  <script>
    // Enhanced animations on page load
    document.addEventListener('DOMContentLoaded', function() {
      // Auto-focus on username field with delay
      setTimeout(() => {
        document.getElementById('user').focus();
      }, 2000);
      
      // Restart circle animation
      const minimalCircle = document.querySelector('.minimal-circle');
      minimalCircle.style.animation = 'none';
      setTimeout(() => {
        minimalCircle.style.animation = 'circleAppear 1.2s ease-out 0.3s forwards';
      }, 10);
      
      // Add typing effect to inputs on focus
      const inputs = document.querySelectorAll('.form-control');
      inputs.forEach((input, index) => {
        // Add focus effects
        input.addEventListener('focus', function() {
          this.parentElement.querySelector('.input-icon').style.color = '#3b82f6';
          this.style.animation = 'none';
          setTimeout(() => {
            this.style.animation = 'pulseSoft 2s infinite';
          }, 10);
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.querySelector('.input-icon').style.color = '#64748b';
          this.style.animation = 'none';
        });
        
        // Add click ripple effect
        input.addEventListener('click', function(e) {
          const ripple = document.createElement('span');
          const rect = this.getBoundingClientRect();
          const size = Math.max(rect.width, rect.height);
          const x = e.clientX - rect.left - size / 2;
          const y = e.clientY - rect.top - size / 2;
          
          ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            width: ${size}px;
            height: ${size}px;
            top: ${y}px;
            left: ${x}px;
            pointer-events: none;
          `;
          
          this.appendChild(ripple);
          setTimeout(() => ripple.remove(), 600);
        });
      });
      
      // Add ripple effect to login button
      const loginBtn = document.querySelector('.login-btn');
      loginBtn.addEventListener('click', function(e) {
        if (this.classList.contains('loading')) return;
        
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
          position: absolute;
          border-radius: 50%;
          background: rgba(255, 255, 255, 0.4);
          transform: scale(0);
          animation: ripple 0.6s linear;
          width: ${size}px;
          height: ${size}px;
          top: ${y}px;
          left: ${x}px;
          pointer-events: none;
        `;
        
        this.appendChild(ripple);
        setTimeout(() => ripple.remove(), 600);
      });
    });

    // Password toggle functionality with animation
    document.getElementById('togglePassword').addEventListener('click', function() {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');
      
      // Add bounce animation
      this.style.transform = 'translateY(-50%) scale(1.2)';
      setTimeout(() => {
        this.style.transform = 'translateY(-50%) scale(1)';
      }, 200);
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    });

    // Form validation and loading animation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const username = document.getElementById('user').value.trim();
      const password = document.getElementById('password').value.trim();
      const loginBtn = document.querySelector('.login-btn');
      
      if (username === '' || password === '') {
        e.preventDefault();
        // Add shake animation to empty fields
        if (username === '') {
          document.getElementById('user').style.animation = 'shake 0.5s';
          setTimeout(() => {
            document.getElementById('user').style.animation = '';
          }, 500);
        }
        if (password === '') {
          document.getElementById('password').style.animation = 'shake 0.5s';
          setTimeout(() => {
            document.getElementById('password').style.animation = '';
          }, 500);
        }
        return;
      }
      
      // Show loading animation
      loginBtn.classList.add('loading');
      
      // Simulate network delay
      setTimeout(() => {
        loginBtn.classList.remove('loading');
      }, 2000);
    });

    // Add CSS for ripple animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes ripple {
        to {
          transform: scale(4);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>
