<?php
session_start();

// Detectar se estamos em um subdiretório
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = dirname($_SERVER['SCRIPT_NAME']);

// Normalizar o caminho base
if ($script_name === '\\' || $script_name === '/') {
    $base_path = '/';
} else {
    $base_path = $script_name . '/';
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . 'pages/login.php');
    exit();
}

// Redirect to dashboard if authenticated
header('Location: ' . $base_path . 'pages/dashboard.php');
exit();
?>
