<?php
header('Content-Type: application/json');
require 'functions.php';
session_start(); // NEW: Start session to access CSRF token

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- 2. CSRF Token Validation ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token. Request denied.']);
    exit;
}

// --- 3. Validate Input ---
if (empty(trim($_POST['user_id']))) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
    exit;
}

// --- 4. Process Data ---
$userId = trim($_POST['user_id']);

if (unbanUser($userId)) {
    echo json_encode(['status' => 'success', 'message' => 'User ' . htmlspecialchars($userId) . ' has been unbanned.']);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to unban the user. The user may not exist or an internal error occurred.']);
}
?>
