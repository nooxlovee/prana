<?php

// 👉 Включаем показ ошибок (только для разработки!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Prana</title>
    <link rel="shortcut icon" href="assets/media/image/index/logo/PR.svg" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php
require_once __DIR__ . '/database/connect.php';
require_once __DIR__ . '/includes/head.php';

// --- Списки страниц ---
// страницы админа
$adminPages = [
    'add_product',
    'add_category',
    'add_gender',
    'admin_products',
    'admin_categories',
    'admin_users',
    'admin_genders',
    'edit_gender',
    'edit_product',
    'edit_category',
    'orders'
    ];
// страницы авторизованного
$userPages = ['account', 'user_orders', 'setting_account', 'favorite', 'basket', 'order_success'];
// страницы для гостя
$guestPages = ['register', 'login'];
// главная страница
$allStatusPages = ['main', 'catalog','product'];
$allPages = array_merge($adminPages, $userPages, $guestPages, $allStatusPages);

// --- Определяем страницу ---
if (array_key_exists('page', $_GET)) {
    // Параметр есть, но проверяем, что он не пустой и в списке
    $raw = filter_input(INPUT_GET, 'page');
    if ($raw === null || $raw === '' || !in_array($raw, $allPages, true)) {
        http_response_code(404);
        include_once __DIR__ . '/includes/header_white.php';
        include_once __DIR__ . '/pages/404.php';
        include_once __DIR__ . '/includes/footer.php';
        exit;
    }
    $page = $raw;
} else {
    // Параметр отсутствует — корень сайта
    $page = 'main';
}

// --- Проверки доступа ---
$userRole = $USER['role'] ?? null;
if (in_array($page, $adminPages, true) && $userRole !== 'admin') {
    http_response_code(403);
    exit('Доступ запрещён');
}
if (in_array($page, $userPages, true) && $userRole === null) {
    header('Location: ?page=login');
    exit;
}
if (in_array($page, $guestPages, true) && $userRole !== null) {
    header('Location: ./');
    exit;
}

////var_dump($USER);
//// --- Выбор хедера ---
//$headerFile = $page === 'main'
//    ? null
//    :
?>


<?php

if ($page !== 'main') {
    include_once __DIR__ . '/includes/header_white.php';
}

include_once __DIR__ . '/pages/' . $page . '.php';
include_once __DIR__ . '/includes/footer.php';
?>
<script>
    // Бургер-меню
    const burgerIcon = document.querySelector('.burger-icon');
    const menuText = document.querySelector('.menu-text');
    const burgerMenu = document.querySelector('.burger-menu');
    const burgerOverlay = document.querySelector('.burger-menu-overlay');
    const closeBurger = document.querySelector('.close-burger');
    const burgerLinks = document.querySelectorAll('.burger-nav a');

    function toggleBurgerMenu() {
        burgerMenu.classList.toggle('active');
        burgerOverlay.classList.toggle('active');
        document.body.style.overflow = burgerMenu.classList.contains('active') ? 'hidden' : '';
    }

    burgerIcon.addEventListener('click', toggleBurgerMenu);
    menuText.addEventListener('click', toggleBurgerMenu);
    closeBurger.addEventListener('click', toggleBurgerMenu);
    burgerOverlay.addEventListener('click', toggleBurgerMenu);

    // Закрытие меню при клике на ссылки
    burgerLinks.forEach(link => {
        link.addEventListener('click', toggleBurgerMenu);
    });
</script>
</body>
</html>