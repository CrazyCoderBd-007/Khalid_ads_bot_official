<?php
// Set the content type of the response to JSON for consistent API behavior
header('Content-Type: application/json');

// It is critical that 'functions.php' connects to the database securely
// and that the unbanUser function uses prepared statements to prevent SQL injection.
require 'functions.php';

// --- 1. Check if the request method is POST ---
// This endpoint modifies data, so it should only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Respond with an appropriate HTTP error code and a clear message
    http_response_code(405); // 405 Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Please use POST.']);
    exit; // Stop script execution
}

// --- 2. Validate the input ---
// Ensure the 'user_id' is provided and is not just empty spaces.
if (!isset($_POST['user_id']) || empty(trim($_POST['user_id']))) {
    // Respond with a bad request error code
    http_response_code(400); // 400 Bad Request
    echo json_encode(['status' => 'error', 'message' => 'User ID is required.']);
    exit; // Stop script execution
}

// --- 3. Process the data ---
// Sanitize the input by trimming whitespace
$userId = trim($_POST['user_id']);

// Call the unbanUser function. This function should be robust and
// return true on success and false on any kind of failure (e.g., user not found, DB error).
if (unbanUser($userId)) {
    // On success, send a success status and a confirmation message.
    // Use htmlspecialchars for security if you echo user input back.
    echo json_encode(['status' => 'success', 'message' => 'User ' . htmlspecialchars($userId) . ' has been unbanned.']);
} else {
    // If the function fails, it indicates a server-side problem.
    // Respond with a generic but appropriate server error.
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Failed to unban the user. The user may not exist or an internal error occurred.']);
}

?>
