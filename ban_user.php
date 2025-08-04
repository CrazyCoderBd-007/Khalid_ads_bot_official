<?php
// Set the content type of the response to JSON
header('Content-Type: application/json');

// It's crucial that this file handles database connections securely
// and that the banUser function uses prepared statements to prevent SQL injection.
require 'functions.php';

// --- 1. Check if the request method is POST ---
// Only allow POST requests for this action, as it modifies data.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Send an error response and stop the script
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Please use POST.']);
    exit;
}

// --- 2. Validate the input ---
// Check if the required fields are present and not empty.
if (!isset($_POST['user_id']) || !isset($_POST['reason']) || empty(trim($_POST['user_id']))) {
    // Send an error response and stop the script
    http_response_code(400); // 400 Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: user_id and reason.']);
    exit;
}

// --- 3. Process the data ---
// Trim whitespace from the input
$userId = trim($_POST['user_id']);
$reason = trim($_POST['reason']);

// Call the function to ban the user.
// The banUser function should return true on success and false on failure.
if (banUser($userId, $reason)) {
    // Send a success response
    echo json_encode(['status' => 'success', 'message' => 'User ' . htmlspecialchars($userId) . ' has been banned.']);
} else {
    // Send a generic server error response
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to ban the user. An internal error occurred.']);
}
?>
