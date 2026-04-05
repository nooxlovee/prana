<?php
ob_start(); // Буферизация, чтобы избежать неожиданных выводов
// session_start() у вас уже вызывается в роутере
global $database;

$errors  = [];
$success = $_SESSION['message'] ?? null;

// Получаем список полов
$genders = $database
    ->query("SELECT id, title FROM genders")
    ->fetchAll(PDO::FETCH_ASSOC);

$title     = '';
$gender_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $gender_id = isset($_POST['gender_id']) ? (int)$_POST['gender_id'] : null;

    // Валидация
    if ($title === '') {
        $errors[] = 'Название категории не может быть пустым';
    }
    $valid_ids = array_map('intval', array_column($genders, 'id'));
    if (!in_array($gender_id, $valid_ids, true)) {
        $errors[] = 'Выбранный пол некорректен';
    }

    // Проверка на дубликат
    if (empty($errors)) {
        $stmt = $database->prepare("
            SELECT COUNT(*) 
            FROM categories 
            WHERE title = :title AND gender_id = :gender_id
        ");
        $stmt->execute([
            ':title'     => $title,
            ':gender_id' => $gender_id,
        ]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Категория с таким названием уже существует для выбранного пола';
        }
    }

    // Сохранение и клиентский редирект
    if (empty($errors)) {
        $stmt = $database->prepare("
            INSERT INTO categories (title, gender_id) 
            VALUES (:title, :gender_id)
        ");
        if ($stmt->execute([
            ':title'     => $title,
            ':gender_id' => $gender_id,
        ])) {
            $_SESSION['message'] = 'Категория «'
                . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                . '» успешно добавлена';
            // Клиентский редирект вместо header()
            echo '<script type="text/javascript">
                    window.location.replace("./?page=add_category");
                  </script>
                  <noscript>
                    <meta http-equiv="refresh" content="0;url=?page=add_category">
                  </noscript>';
            exit;
        }
        $errors[] = 'Не удалось сохранить в базу. Попробуйте позже.';
    }
}

// После exit редиректа идёт только HTML
require_once __DIR__ . '/../includes/header_white.php';
?>
<div class="admin_all_block container mt-115">
    <div class="adminpanel_block"><p>ПАНЕЛЬ АДМИНИСТРАТОРА</p></div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin_add_tovar">
            <h2 class="h2_admin">ДОБАВИТЬ КАТЕГОРИЮ</h2>
            <div class="admin_add_product_block">
                <div class="admin-form-wrapper">

                    <?php if ($success): ?>
                        <div class="success">
                            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <form class="admin-form" method="post" novalidate>
                        <div class="full-width">
                            <label for="title">Название:</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                required
                                value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
                            >

                            <label for="gender">Пол:</label>
                            <select id="gender" name="gender_id" required>
                                <option value="">— Выберите пол —</option>
                                <?php foreach ($genders as $g): ?>
                                    <?php $gid = (int)$g['id']; ?>
                                    <option
                                        value="<?= $gid ?>"
                                        <?= $gid === $gender_id ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($g['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="error">
                                <ul>
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php require_once __DIR__ . '/../includes/alert.php'; ?>

                        <button type="submit" class="black_btn">
                            ДОБАВИТЬ КАТЕГОРИЮ
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
