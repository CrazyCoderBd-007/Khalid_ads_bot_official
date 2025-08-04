<?php
// Set the content type of the response to JSON
header('Content-Type: application/json');

// It is CRITICAL that 'functions.php' contains the addFundsToUser function
// as described below, using prepared statements.
require 'functions.php';

// --- 1. Only allow POST requests ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method. Please use POST.']);
    exit;
}

// --- 2. Rigorous Input Validation ---
// Check that all required fields are present
if (!isset($_POST['user_id']) || !isset($_POST['amount'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields: user_id and amount.']);
    exit;
}

// Sanitize and validate the inputs
$userId = trim($_POST['user_id']);
$amountStr = trim($_POST['amount']);

// Ensure user_id is not empty
if (empty($userId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'User ID cannot be empty.']);
    exit;
}

// Ensure amount is a valid number and is positive
if (!is_numeric($amountStr)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Amount must be a valid number.']);
    exit;
}

$amount = floatval($amountStr);

if ($amount <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Amount must be a positive value.']);
    exit;
}


// --- 3. Process the Data Atomically ---
// The `addFundsToUser` function should perform an atomic update.
// It should return true if the update was successful (1 row affected),
// and false otherwise (0 rows affected or DB error).

$result = addFundsToUser($userId, $amount);

if ($result) {
    // On success, send a clear confirmation message.
    echo json_encode([
        'status' => 'success', 
        'message' => 'Successfully added ' . number_format($amount, 2) . ' to user ' . htmlspecialchars($userId)
    ]);
} else {
    // If the function fails, it's most likely because the user doesn't exist.
    // Respond with a "Not Found" error, which is more specific.
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Update failed. User ID not found.']);
}

?>
