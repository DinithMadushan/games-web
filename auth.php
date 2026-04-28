<?php
session_start();
require_once 'db.php';

$action = $_POST['action'] ?? '';

if ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$email || !$password) {
        redirect('index.php', 'error', 'All fields required');
    }
    if (strlen($password) < 6) {
        redirect('index.php', 'error', 'Password must be at least 6 characters');
    }

    try {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        $id = $db->lastInsertId();
        $_SESSION['user_id']  = $id;
        $_SESSION['username'] = $username;
        redirect('index.php', 'success', 'Welcome, ' . $username . '!');
    } catch (Exception $e) {
        redirect('index.php', 'error', 'Username or email already exists');
    }
}

if ($action === 'login') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('index.php', 'success', 'Welcome back, ' . $user['username'] . '!');
        } else {
            redirect('index.php', 'error', 'Invalid email or password');
        }
    } catch (Exception $e) {
        redirect('index.php', 'error', 'Login failed');
    }
}

if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

function redirect($page, $type, $msg) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_msg']  = $msg;
    header('Location: ' . $page);
    exit;
}
?>
