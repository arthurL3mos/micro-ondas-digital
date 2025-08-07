<?php
header('Content-Type: application/json');
require_once 'config/config.php';
require_once 'src/Microwave/Auth.php';

$config = include 'config.php';
$auth = new Auth($config);

// Recebe dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

$token = $auth->login($data['username'], $data['password']);

if ($token) {
    echo json_encode([
        'success' => true,
        'token' => $token,
        'message' => 'Login successful'
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid credentials'
    ]);
}