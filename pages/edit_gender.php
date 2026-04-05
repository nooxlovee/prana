<?php
// pages/edit_gender.php
global $database;
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    exit('Неверный ID');
}
// Получаем текущий пол
$stmt = $database->prepare("SELECT * FROM genders WHERE id = ?");
$stmt->execute([$id]);
$gender = $stmt->fetch();
if (!$gender) {
    exit('Пол не найден');
}

$errors  = [];
$title   = $gender['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        $errors[] = 'Название не может быть пустым';
    } else {
        // Проверка на уникальность (кроме текущей)
        $chk = $database->prepare(
            "SELECT COUNT(*) FROM genders WHERE title = :t AND id <> :id"
        );
        $chk->execute([':t'=>$title,':id'=>$id]);
        if ($chk->fetchColumn() > 0) {
            $errors[] = 'Такой пол уже существует';
        }
    }
    if (empty($errors)) {
        $upd = $database->prepare(
            "UPDATE genders SET title = :t WHERE id = :id"
        );
        $upd->execute([':t'=>$title,':id'=>$id]);
        header('Location: ./?page=admin_genders');
        exit;
    }
}
?>
<div class="admin_all_block mt-115">
    <div class="adminpanel_block"><p>ПАНЕЛЬ АДМИНИСТРАТОРА</p></div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin_add_tovar">
            <h2 class="h2_admin">РЕДАКТИРОВАТЬ ПОЛ</h2>
            <div class="admin_add_product_block">
                <div class="admin-form-wrapper">
                    <?php if ($errors): ?>
                        <div class="error"><ul>
                                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                            </ul></div>
                    <?php endif; ?>

                    <form method="post" class="admin-form" action="?page=edit_gender&id=<?= $id ?>">
                        <div class="full-width">
                            <label for="title">Название пола:</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                value="<?= htmlspecialchars($title) ?>"
                            >
                        </div>
                        <button type="submit" class="black_btn">СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>