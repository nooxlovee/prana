<?php

$sql = "SELECT c.*, g.title AS gender_title FROM categories c JOIN genders g ON c.gender_id = g.id";
$stmt = $database->query($sql);
$categories = $stmt->fetchAll(2);

?>
<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php' ?>
        <div class="right_content_admin">
            <div class="category_grid">
                <?php if (empty($categories)): ?>
                    <p>Категории отсутствуют</p>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="card_category">
                            <div class="info_card_category">
                                <h3><?= $category['title'] ?> <span class="category-gender">(<?= $category['gender_title'] ?>)</span></h3>
                            </div>
                            <div class="category-btns">
                                <a href="./?page=edit_category&id=<?= $category['id'] ?>" class="black_btn">РЕДАКТИРОВАТЬ</a>
                                <a href="../actions/delete_admin.php?type=category&id=<?= $category['id'] ?>"
                                   onclick="return confirm('Вы уверены, что хотите удалить этот товар?');" class="black_btn"
                                   style="margin-top: 10px;text-align: center">УДАЛИТЬ</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>