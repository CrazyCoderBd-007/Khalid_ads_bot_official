<?php
header('Content-Type: application/json');
require 'functions.php';

// 1. Check Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// 2. Validate Inputs
$userId = $_POST['user_id'] ?? null;
$amount = $_POST['amount'] ?? null;
$method = $_POST['method'] ?? null;
$accountDetails = $_POST['account_details'] ?? null;

if (empty($userId) || !is_numeric($amount) || empty($method) || empty($accountDetails)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid withdrawal information.']);
    exit;
}

$amount = floatval($amount);
if ($amount < 1.0) { // Minimum withdrawal amount check
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Minimum withdrawal amount is $1.00.']);
    exit;
}

// 3. Process Withdrawal (Transactionally)
global $pdo;
try {
    $pdo->beginTransaction();

    // Check user's current balance (server-side check is crucial)
    $user = getUserById($userId);
    if (!$user) {
        throw new Exception("User not found.", 404);
    }
    if ($user['balance'] < $amount) {
        throw new Exception("Insufficient balance for this withdrawal.", 400);
    }

    // Deduct funds from user's account
    $deductionSuccess = deductFundsForWithdrawal($userId, $amount);
    if (!$deductionSuccess) {
        throw new Exception("Failed to update user balance. Please try again.");
    }

    // Create a record in the withdrawals table
    $requestSuccess = createWithdrawalRequest($userId, $amount, $method, $accountDetails);
    if (!$requestSuccess) {
        throw new Exception("Failed to log withdrawal request.");
    }

    // If everything is successful, commit the changes
    $pdo->commit();

    // Fetch the final new balance
    $finalUser = getUserById($userId);

    echo json_encode([
        'success' => true,
        'message' => 'Withdrawal request submitted successfully!',
        'new_balance' => (float)$finalUser['balance']
    ]);

} catch (Exception $e) {
    // If any step fails, roll back the entire transaction
    $pdo->rollBack();
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
