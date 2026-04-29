<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$user = currentUser();

// Not logged in → show auth page
if (!$user) {
    $pageTitle = 'Sign In';
    include __DIR__ . '/pages/auth.php';
    exit;
}

$page = $_GET['page'] ?? '';

// Route
$allowed_traveller = ['', 'dashboard', 'place', 'property', 'payment'];
$allowed_owner     = ['', 'add-place', 'add-property'];

if ($user['role'] === 'traveller' && !in_array($page, $allowed_traveller)) $page = '';
if ($user['role'] === 'owner'     && !in_array($page, $allowed_owner))     $page = '';

switch ($page) {
    case 'dashboard':   include __DIR__ . '/pages/dashboard.php'; break;
    case 'place':       include __DIR__ . '/pages/place.php'; break;
    case 'property':    include __DIR__ . '/pages/property.php'; break;
    case 'payment':     include __DIR__ . '/pages/payment.php'; break;
    case 'add-place':   include __DIR__ . '/pages/add-place.php'; break;
    case 'add-property':include __DIR__ . '/pages/add-property.php'; break;
    default:
        if ($user['role'] === 'owner') include __DIR__ . '/pages/owner-home.php';
        else include __DIR__ . '/pages/explore.php';
}
