<?php
$host = getenv('MYSQLHOST') ?: '127.0.0.1';
$db   = getenv('MYSQLDATABASE') ?: 'studytrack_db';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$port = getenv('MYSQLPORT') ?: '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);

     // Automatically create users table if it doesn't exist
     $pdo->exec("CREATE TABLE IF NOT EXISTS users (
         userId INT AUTO_INCREMENT PRIMARY KEY,
         username VARCHAR(50) NOT NULL,
         email VARCHAR(100) NOT NULL,
         full_name VARCHAR(100) NOT NULL,
         password VARCHAR(255) NOT NULL,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     )");

     // Automatically create tasks table if it doesn't exist
     $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
         taskId INT AUTO_INCREMENT PRIMARY KEY,
         userId INT NOT NULL,
         title VARCHAR(255) NOT NULL,
         subject VARCHAR(100),
         category VARCHAR(50),
         priority VARCHAR(20),
         deadline DATE,
         description TEXT,
         status VARCHAR(50),
         FOREIGN KEY (userId) REFERENCES users(userId) ON DELETE CASCADE
     )");

} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>