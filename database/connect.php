<?php

$host   = 'localhost';
$db     = 'prana';
$charset= 'utf8';
$user   = 'root';
$pass   = '';
$dsn    = "mysql:host={$host};dbname={$db};charset={$charset}";

// Опции PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // выбрасывать исключения
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // ассоциативный массив по умолчанию
    PDO::ATTR_EMULATE_PREPARES   => false,                  // отключить эмуляцию подготовленных запросов
];

try {
    $database = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] Произошла какая-то ошибка: ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/database.log');
    exit();
}