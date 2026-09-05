<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $userId = $_GET['userId'] ?? null;
        $sql = "SELECT * FROM tasks";
        $params = [];
        
        if ($userId) {
            $sql .= " WHERE userId = :userId";
            $params[':userId'] = $userId;
        }
        
        $sql .= " ORDER BY deadline ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();
        
        echo json_encode($tasks);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    try {
        $sql = "INSERT INTO tasks (userId, title, subject, category, priority, deadline, description, status) 
                VALUES (:userId, :title, :subject, :category, :priority, :deadline, :description, :status)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':userId' => $data['userId'] ?? null,
            ':title' => $data['title'] ?? '',
            ':subject' => $data['subject'] ?? '',
            ':category' => $data['category'] ?? '',
            ':priority' => $data['priority'] ?? 'Medium',
            ':deadline' => $data['deadline'] ?? date('Y-m-d'),
            ':description' => $data['description'] ?? '',
            ':status' => $data['status'] ?? 'Pending'
        ]);

        echo json_encode(["success" => true, "taskId" => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['taskId'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing taskId"]);
        exit();
    }

    try {
        $fields = [];
        $params = [':taskId' => $data['taskId']];

        foreach (['title', 'subject', 'category', 'priority', 'deadline', 'description', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (!empty($fields)) {
            $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE taskId = :taskId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
elseif ($method === 'DELETE') {
    $taskId = $_GET['taskId'] ?? null;
    if (!$taskId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing taskId"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE taskId = :taskId");
        $stmt->execute([':taskId' => $taskId]);
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>