<?php
// --- SECURITY ADVISORY ---
// In a production environment, it is highly recommended to store these credentials
// outside of the web-accessible directory and load them as environment variables.
// This prevents them from being exposed if the server is misconfigured.

$host = 'sql100.hstn.me';
$dbname = 'if0_3333333333_db';
$username = 'if0_3333333333';
$password = 'RrChdhhtDyh';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // FIXED: Ensure subsequent fetches return associative arrays by default.
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a real app, you would log this error and show a generic error page.
    // die("Could not connect to the database. Please try again later.");
    die("Could not connect to the database: " . $e->getMessage());
}
?>
