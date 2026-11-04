<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /wanderstays/index.php'); exit; }

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
$stmt->bind_param('s', $email); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password'])) {
    header('Location: /wanderstays/index.php?msg=' . urlencode('Incorrect email or password') . '&type=error'); exit;
}

unset($user['password']);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user']    = $user;
header('Location: /wanderstays/index.php?msg=' . urlencode('Welcome back, ' . explode(' ', $user['full_name'])[0] . '!')); exit;
