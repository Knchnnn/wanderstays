<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$user = currentUser();
$curPage = $_GET['page'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WanderStays – <?= htmlspecialchars($pageTitle ?? 'Discover India') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/wanderstays/css/style.css">
</head>
<body>
<?php if ($user): ?>
<nav class="navbar">
  <a href="/wanderstays/index.php" class="brand">WanderStays</a>
  <div class="nav-links">
    <?php if ($user['role'] === 'traveller'): ?>
      <a href="/wanderstays/index.php" class="nav-link <?= $curPage===''?'active':'' ?>">Explore</a>
      <a href="/wanderstays/index.php?page=dashboard" class="nav-link <?= $curPage==='dashboard'?'active':'' ?>">My Trips</a>
    <?php else: ?>
      <a href="/wanderstays/index.php" class="nav-link <?= $curPage===''?'active':'' ?>">Dashboard</a>
      <a href="/wanderstays/index.php?page=add-place" class="nav-link <?= $curPage==='add-place'?'active':'' ?>">+ Destination</a>
      <a href="/wanderstays/index.php?page=add-property" class="nav-link <?= $curPage==='add-property'?'active':'' ?>">+ Property</a>
    <?php endif; ?>
    <a href="/wanderstays/static/how-it-works.html" class="nav-link" target="_blank">Guide</a>
    <a href="/wanderstays/static/contact.html" class="nav-link" target="_blank">Contact</a>
    <div class="nav-user">
      <div class="avatar"><?= strtoupper(substr($user['full_name'], 0, 1)) ?></div>
      <span class="nav-name sf"><?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?></span>
      <a href="/wanderstays/auth/logout.php" class="btn-outline" style="padding:6px 14px;font-size:12px">Logout</a>
    </div>
  </div>
</nav>
<?php endif; ?>
<div id="notif-area"></div>
