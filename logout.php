<?php
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Logout successful. Please discard your token.'
]);