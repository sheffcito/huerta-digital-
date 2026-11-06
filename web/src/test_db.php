<?php
header('Content-Type: application/json; charset=utf-8');

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'HUERTA_DIGITAL';
$user = getenv('DB_USER') ?: 'huerta_user';
$pass = getenv('DB_PASSWORD') ?: 'huerta_password';

$response = [
    'status' => 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => $db,
    'host' => $host,
    'message' => '',
    'products_count' => 0
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);

    $stmt = $pdo->query("SELECT COUNT(*) FROM productos");
    $count = $stmt->fetchColumn();

    $response['status'] = 'ok';
    $response['message'] = 'Conexión a MySQL exitosa';
    $response['products_count'] = (int)$count;

    http_response_code(200);
} catch (PDOException $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
