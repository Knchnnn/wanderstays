<?php require_once __DIR__ . '/../includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>WanderStays</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/wanderstays/css/style.css">
</head>
<body>
<div id="notif-area"></div>
<div class="auth-page">
  <div class="auth-glow"></div>
  <div class="auth-box">
    <div style="text-align:center;margin-bottom:36px">
      <p class="sf" style="font-size:11px;letter-spacing:5px;color:var(--gold);text-transform:uppercase;margin-bottom:10px">Discover India</p>
      <h1 style="font-size:42px;letter-spacing:-1.5px">WanderStays</h1>
      <p class="sf" style="color:var(--dim);font-size:13px;margin-top:8px">Extraordinary stays across incredible India</p>
    </div>
    <div class="auth-card">
      <div class="auth-tabs">
        <button class="auth-tab active" data-mode="login">Sign In</button>
        <button class="auth-tab" data-mode="register">Create Account</button>
      </div>
      <div class="auth-section" id="auth-login">
        <form action="/wanderstays/auth/login.php" method="POST">
          <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="your@email.com" required></div>
          <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Your password" required></div>
          <button type="submit" class="btn-gold btn-full" style="padding:13px;margin-top:6px">Sign In</button>
        </form>
      </div>
      <div class="auth-section" id="auth-register" style="display:none">
        <form action="/wanderstays/auth/register.php" method="POST">
          <div class="form-group"><label>Full Name</label><input type="text" name="full_name" placeholder="Your full name" required></div>
          <div class="form-group"><label>Email Address</label><input type="email" name="email" placeholder="your@email.com" required></div>
          <div class="form-group"><label>Password (min 6 characters)</label><input type="password" name="password" placeholder="Create a password" required></div>
          <div class="form-group"><label>I am a</label>
            <select name="role">
              <option value="traveller">Traveller — looking to explore places</option>
              <option value="owner">Property Owner — want to list my property</option>
            </select>
          </div>
          <button type="submit" class="btn-gold btn-full" style="padding:13px;margin-top:6px">Create Account</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="/wanderstays/js/main.js"></script>
</body></html>
