<?php
require_once 'config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    // Plain text comparison
    $success = ($password === ADMIN_PASSWORD);
    if ($success) {
        $_SESSION['admin_logged_in'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
    exit;
}

// If accessed directly without POST, redirect to home
header('Location: index.php');
?>