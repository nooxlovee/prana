<?php
global $database;
session_start();
require_once __DIR__ . '/../database/connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../');
    exit;
}

$type = $_GET['type'] ?? null;
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$type || !$id) {
    die('Недостаточно данных для удаления');
}

$allowedTypes = [
    'product'  => ['table' => 'products',   'redirect' => '../?page=admin_products'],
    'category' => ['table' => 'categories', 'redirect' => '../?page=admin_categories'],
    'gender'   => ['table' => 'genders',    'redirect' => '../?page=admin_genders'],
];

if (!isset($allowedTypes[$type])) {
    die('Неверный тип объекта');
}

$table    = $allowedTypes[$type]['table'];
$redirect = $allowedTypes[$type]['redirect'];

// Только для товара удаляем связанные изображения
if ($type === 'product') {
    $imagesStmt = $database->prepare("SELECT * FROM images WHERE product_id = ?");
    $imagesStmt->execute([$id]);
    $images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($images as $image) {
        $filePath = __DIR__ . '/../uploads/products/' . $image['path'];
        if (is_file($filePath)) {
            unlink($filePath);
        }
        // удаляем запись о картинке
        $delImg = $database->prepare("DELETE FROM images WHERE id = ?");
        $delImg->execute([$image['id']]);
    }
}

// Удаляем сам объект (товар, категорию или пол)
$delStmt = $database->prepare("DELETE FROM `{$table}` WHERE id = ?");
$deleted = $delStmt->execute([$id]);

if ($deleted) {
    header("Location: {$redirect}");
    exit;
}

echo "Ошибка при удалении из таблицы {$table}";