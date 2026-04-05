<?php
require_once __DIR__ . '/../database/connect.php';


session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {

    $stmt = $database->prepare("SELECT id FROM favorite WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $favorite = $stmt->fetch();

    if ($favorite) {

        $stmt = $database->prepare("DELETE FROM favorite WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'removed']);
    } else {

        $stmt = $database->prepare("INSERT INTO favorite (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['status' => 'added']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 