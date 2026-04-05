<?php
session_start();
require_once __DIR__ . '/../database/connect.php'; // в $database лежит PDO

header('Content-Type: application/json; charset=utf-8');

// Разрешены только AJAX-POST запросы с меткой ajax
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ajax'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad request']);
    exit;
}

// Входные данные
$step      = $_POST['step']      ?? 'email';
$email     = trim((string)($_POST['email']     ?? ''));
$password  =          $_POST['password']  ?? '';
$password2 =          $_POST['password2'] ?? '';
$username  = trim((string)($_POST['username']  ?? ''));
$surname   = trim((string)($_POST['surname']   ?? ''));

$errors = [];

// 1) Валидация email (всегда)
if ($email === '') {
    $errors['email'] = 'Введите e-mail';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Неверный формат e-mail';
}

if (!empty($errors)) {
    echo json_encode(['step' => 'email', 'errors' => $errors]);
    exit;
}

// 2) Шаг email — решаем, login или register
if ($step === 'email') {
    $stmt = $database->prepare(
        'SELECT id FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $userExists = (bool)$stmt->fetchColumn();
    $nextStep   = $userExists ? 'login' : 'register';

    echo json_encode(['step' => $nextStep]);
    exit;
}

// 3) Шаг login — проверяем пароль
if ($step === 'login') {
    if ($password === '') {
        $errors['password'] = 'Введите пароль';
    } else {
        $stmt = $database->prepare(
            'SELECT id, password, role FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = 'Неверный логин или пароль';
        } else {
            // Успешный вход
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role'] = $user['role'];

            echo json_encode(['step' => 'success']);
            exit;
        }
    }

    echo json_encode(['step' => 'login', 'errors' => $errors]);
    exit;
}

// 4) Шаг register — создаём пользователя
if ($step === 'register') {
    // Валидация имён
    if ($username === '') {
        $errors['username'] = 'Введите имя';
    }
    if ($surname === '') {
        $errors['surname'] = 'Введите фамилию';
    }
    // Пароль
    if ($password === '' || $password2 === '') {
        $errors['password'] = 'Введите пароль и повторите его';
    } elseif ($password !== $password2) {
        $errors['password_mismatch'] = 'Пароли не совпадают';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Пароль минимум 6 символов';
    }

    if (!empty($errors)) {
        echo json_encode(['step' => 'register', 'errors' => $errors]);
        exit;
    }

    // Вставка
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $database->prepare(
        'INSERT INTO users (email, password, username, surname) 
         VALUES (:email, :password, :username, :surname)'
    );
    $ok = $stmt->execute([
        ':email'    => $email,
        ':password' => $hash,
        ':username' => $username,
        ':surname'  => $surname,
    ]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode([
            'step'   => 'register',
            'errors' => ['general' => 'Ошибка при создании пользователя']
        ]);
        exit;
    }

    // Сохранение в сессию
    $newId = (int)$database->lastInsertId();
    $_SESSION['user_id'] = $newId;

    // Подхватываем роль (если есть)
    $roleStmt = $database->prepare(
        'SELECT role FROM users WHERE id = :id'
    );
    $roleStmt->execute([':id' => $newId]);
    $_SESSION['role'] = $roleStmt->fetchColumn() ?: null;

    echo json_encode(['step' => 'success']);
    exit;
}

// Если сюда попали — некорректный шаг
http_response_code(400);
echo json_encode(['error' => 'Unknown step']);