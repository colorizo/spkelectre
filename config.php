<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db   = 'db_spk_electre';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    error_log('Database connection successful');
} catch (\PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?> 