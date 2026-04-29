<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /wanderstays/index.php'); exit; }

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = in_array($_POST['role'] ?? '', ['traveller','owner']) ? $_POST['role'] : 'traveller';

$errors = [];
if (strlen($fullName) < 2)             $errors[] = 'Full name required';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
if (strlen($password) < 6)             $errors[] = 'Password must be at least 6 characters';

if (!empty($errors)) {
    $msg = urlencode(implode('. ', $errors));
    header("Location: /wanderstays/index.php?msg=$msg&type=error"); exit;
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email); $stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    header('Location: /wanderstays/index.php?msg=' . urlencode('Email already registered') . '&type=error'); exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?,?)");
$stmt->bind_param('ssss', $fullName, $email, $hash, $role);
$stmt->execute();
$uid = $conn->insert_id;

$_SESSION['user_id'] = $uid;
$_SESSION['user']    = ['id'=>$uid,'full_name'=>$fullName,'email'=>$email,'role'=>$role];
header('Location: /wanderstays/index.php?msg=' . urlencode('Welcome to WanderStays!')); exit;
