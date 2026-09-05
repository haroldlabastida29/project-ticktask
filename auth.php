<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';
$inputEmailOrUser = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$fullName = trim($data['fullName'] ?? '');
$username = trim($data['username'] ?? '');

if (empty($inputEmailOrUser) || empty($password)) {
    http_response_code(400);
    echo json_encode(["error" => "Email/Username and password are required."]);
    exit();
}

if ($action === 'register') {
    if (empty($fullName) || empty($username)) {
        http_response_code(400);
        echo json_encode(["error" => "Full name and username are required."]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $inputEmailOrUser, ':username' => $username]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(["error" => "Email or username is already registered."]);
            exit();
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, username, full_name, password) VALUES (:email, :username, :fullName, :password)");
        $stmt->execute([
            ':email' => $inputEmailOrUser,
            ':username' => $username,
            ':fullName' => $fullName, 
            ':password' => $hashedPassword
        ]);

        echo json_encode([
            "success" => true, 
            "userId" => $pdo->lastInsertId(),
            "email" => $inputEmailOrUser,
            "fullName" => $fullName
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Registration error: " . $e->getMessage()]);
    }
} 
elseif ($action === 'login') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $inputEmailOrUser, ':username' => $inputEmailOrUser]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode([
                "success" => true, 
                "userId" => $user['userId'],
                "email" => $user['email'],
                "fullName" => $user['full_name']
            ]);
        } else {
            http_response_code(401);
            $reason = !$user ? "Account not found. Please register first." : "Incorrect password.";
            echo json_encode(["error" => $reason]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Login error: " . $e->getMessage()]);
    }
}
else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid action."]);
}
?>