<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'items' => []]);
    exit;
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT c.id AS cart_id, c.count, c.size_id,
           p.title, p.price,
           s.title as size_title,
           (SELECT path FROM images WHERE product_id = p.id LIMIT 1) AS image
    FROM carts c
    JOIN products p ON c.product_id = p.id
    JOIN size s ON s.id = c.size_id
    WHERE c.user_id = :user_id
";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'items' => $result]); 