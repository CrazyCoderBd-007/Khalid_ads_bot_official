<?php
header('Content-Type: application/json');
require 'functions.php';

// --- 1. Check Request Method ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// --- 2. Validate Inputs ---
if (!isset($_POST['user_id']) || !isset($_POST['amount'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$userId = trim($_POST['user_id']);
$amountStr = trim($_POST['amount']);

if (empty($userId) || !is_numeric($amountStr)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input provided.']);
    exit;
}

$amount = floatval($amountStr);
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Amount must be positive.']);
    exit;
}

// --- 3. Process Data Atomically ---
// FIXED: Use the robust addFundsToUser function.
if (addFundsToUser($userId, $amount)) {
    // NEW: Fetch the updated user data to send back the authoritative new balances.
    $user = getUserById($userId);
    if ($user) {
        echo json_encode([
            'success' => true,
            'message' => 'Balance updated successfully!',
            'new_balance' => (float)$user['balance'],
            'new_today_earnings' => (float)$user['today_earnings'],
            'new_total_earnings' => (float)$user['total_earnings']
        ]);
    } else {
        // This case is unlikely but handled for robustness.
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not retrieve updated balance.']);
    }
} else {
    // This now correctly implies the user was not found or a DB error occurred.
    http_response_code(404); // Not Found
    echo json_encode(['success' => false, 'message' => 'Update failed. User not found.']);
}
?>
