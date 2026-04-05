<?php
global $database;

// Проверка прав
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../');
    exit;
}

// Получаем ID из GET
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    exit('Неверный ID категории');
}

// Загружаем справочник полов
$genders = $database
    ->query("SELECT * FROM genders ORDER BY title")
    ->fetchAll(PDO::FETCH_ASSOC);

// Загружаем текущую категорию
$stmt = $database->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute([':id' => $id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$category) {
    exit('Категория не найдена');
}

// Переменные для формы
$title     = $category['title'];
$gender_id = (int)$category['gender_id'];

$errors  = [];
$success = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Сбор и очистка
    $title     = trim($_POST['title'] ?? '');
    $gender_id = filter_input(INPUT_POST, 'gender_id', FILTER_VALIDATE_INT);

    // Валидация
    if ($title === '') {
        $errors['title'] = 'Название категории не может быть пустым';
    }
    $validG = array_column($genders, 'id');
    if (!in_array($gender_id, $validG, true)) {
        $errors['gender'] = 'Выбранный пол некорректен';
    }
    // Уникальность (кроме этой же записи)
    if (!$errors) {
        $uniq = $database->prepare(
            "SELECT COUNT(*) FROM categories 
             WHERE title = :title AND id <> :id"
        );
        $uniq->execute([':title' => $title, ':id' => $id]);
        if ($uniq->fetchColumn() > 0) {
            $errors['title'] = 'Категория с таким названием уже существует';
        }
    }

    // Если всё ОК — обновляем
    if (!$errors) {
        $upd = $database->prepare("            
            UPDATE categories 
               SET title = :title, gender_id = :gid 
             WHERE id = :id
        ");
        $ok = $upd->execute([
            ':title' => $title,
            ':gid'   => $gender_id,
            ':id'    => $id,
        ]);
        if ($ok) {
            $_SESSION['message'] = 'Категория успешно обновлена';
            // Клиентский редирект через JavaScript
            echo '<script>window.location.href = "../?page=admin_categories";</script>';
            exit;
        } else {
            $errors['general'] = 'Ошибка при сохранении в базу. Попробуйте позже.';
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
            <h2 class="h2_admin">РЕДАКТИРОВАТЬ КАТЕГОРИЮ</h2>
            <div class="admin_add_product_block">
                <div class="admin-form-wrapper">

                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if ($errors): ?>
                        <div class="error">
                            <ul>
                                <?php foreach ($errors as $msg): ?>
                                    <li><?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div><br>
                    <?php endif; ?>

                    <form class="admin-form" method="post" action="?page=edit_category&id=<?= $id ?>">
                        <div class="full-width">
                            <label for="title">Название категории:</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?= htmlspecialchars($title) ?>">

                            <label for="gender">Пол:</label>
                            <select id="gender" name="gender_id">
                                <?php foreach ($genders as $g): ?>
                                    <option
                                        value="<?= $g['id'] ?>"
                                        <?= $g['id'] === $gender_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="black_btn">
                            СОХРАНИТЬ ИЗМЕНЕНИЯ
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>