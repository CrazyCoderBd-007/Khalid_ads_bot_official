<?php
$host = 'sql100.hstn.me';
$dbname = 'if0_3333333333_db';
$username = 'if0_3333333333';
$password = 'RrChdhhtDyh';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>
