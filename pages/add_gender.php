<?php
// pages/add_gender.php
global $database;   // Ваш PDO из router'а

$errors  = [];
$success = $_SESSION['message'] ?? null;

// Сразу очистим сообщение, чтобы оно не дублировалось
if ($success) {
    unset($_SESSION['message']);
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');

    // Валидация: не пустое
    if ($title === '') {
        $errors[] = 'Название пола не может быть пустым';
    }

    // Проверка на уникальность
    if (empty($errors)) {
        $stmt = $database->prepare("SELECT COUNT(*) FROM genders WHERE title = :title");
        $stmt->execute([':title' => $title]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Такой пол уже существует';
        }
    }

    // Вставка
    if (empty($errors)) {
        $stmt = $database->prepare("INSERT INTO genders (title) VALUES (:title)");
        if ($stmt->execute([':title' => $title])) {
            $_SESSION['message'] = 'Пол «' . htmlspecialchars($title) . '» успешно добавлен';
            header('Location: ?page=add_gender');
            exit;
        } else {
            $errors[] = 'Не удалось сохранить в базу. Попробуйте позже.';
        }
    }
}
?>
<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin_add_tovar">
            <h2 class="h2_admin">ДОБАВИТЬ ПОЛ</h2>
            <div class="admin_add_product_block">
                <div class="admin-form-wrapper">

                    <!-- Успех -->
                    <?php if ($success): ?>
                        <div class="success"><?= $success ?></div>
                    <?php endif; ?>

                    <!-- Форма -->
                    <form class="admin-form" method="post" action="?page=add_gender">
                        <div class="full-width">
                            <label for="title">Название пола:</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                            >
                        </div>

                        <!-- Ошибки -->
                        <?php if (!empty($errors)): ?>
                            <div class="error">
                                <ul>
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="black_btn">ДОБАВИТЬ ПОЛ</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>