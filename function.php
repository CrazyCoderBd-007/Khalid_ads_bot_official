<?php
require 'config.php';

// FIXED: Using prepared statements to prevent SQL injection.
function getUserById($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Log error in a real application
        return false;
    }
}

// FIXED: Using prepared statements.
function createUser($userId, $firstName, $lastName, $username) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO users (user_id, first_name, last_name, username, balance, today_earnings, total_earnings) VALUES (?, ?, ?, ?, 0, 0, 0)");
        return $stmt->execute([$userId, $firstName, $lastName, $username]);
    } catch (PDOException $e) {
        return false;
    }
}

// NEW: Atomically adds funds to user's balance and earnings trackers. This is safer.
function addFundsToUser($userId, $amount) {
    global $pdo;
    $amount = floatval($amount);
    if ($amount <= 0) return false;

    try {
        $stmt = $pdo->prepare(
            "UPDATE users SET 
                balance = balance + ?, 
                today_earnings = today_earnings + ?, 
                total_earnings = total_earnings + ? 
            WHERE user_id = ?"
        );
        $stmt->execute([$amount, $amount, $amount, $userId]);
        return $stmt->rowCount() > 0; // Returns true if a row was updated
    } catch (PDOException $e) {
        return false;
    }
}

// NEW: Atomically deducts funds for withdrawals.
function deductFundsForWithdrawal($userId, $amount) {
    global $pdo;
    $amount = floatval($amount);
    if ($amount <= 0) return false;

    try {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE user_id = ? AND balance >= ?");
        $stmt->execute([$amount, $userId, $amount]);
        return $stmt->rowCount() > 0; // Returns true if the update was successful
    } catch (PDOException $e) {
        return false;
    }
}


// FIXED: Using prepared statements.
function banUser($userId, $reason) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_banned = TRUE, ban_reason = ? WHERE user_id = ?");
        $stmt->execute([$reason, $userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// FIXED: Using prepared statements.
function unbanUser($userId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_banned = FALSE, ban_reason = NULL WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// FIXED: No user input, but good practice to maintain consistency.
function getAllUsers() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT user_id, first_name, last_name, balance, is_banned, ban_reason FROM users ORDER BY id DESC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return []; // Return an empty array on failure
    }
}

// NEW: Function to log withdrawal requests.
function createWithdrawalRequest($userId, $amount, $method, $details) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, method, account_details) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $amount, $method, $details]);
    } catch (PDOException $e) {
        return false;
    }
}
?>
