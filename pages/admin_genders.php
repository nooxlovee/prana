<?php
// pages/admin_genders.php
// Убедитесь, что $database и $USER уже доступны из роутера

// Получаем все записи из таблицы genders
$sql     = "SELECT * FROM genders ORDER BY id";
$stmt    = $database->query($sql);
$genders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .info_card_tovar {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        height: 10%;
    }
</style>

<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin">
            <div class="h2_and_poisk_admin">
                <h2 class="h2_admin">ПОЛЫ</h2>
            </div>
            <div class="product_grid">
                <?php if (empty($genders)): ?>
                    <p>Полов нет</p>
                <?php else: ?>
                    <?php foreach ($genders as $gender): ?>
                        <div class="card_tovar">
                            <div class="info_card_tovar">
                                <h3><?= htmlspecialchars($gender['title']) ?></h3>
                            </div>
                            <a
                                href="./?page=edit_gender&id=<?= $gender['id'] ?>"
                                class="black_btn"
                            >
                                РЕДАКТИРОВАТЬ
                            </a>
                            <a
                                href="../actions/delete_admin.php?type=gender&id=<?= $gender['id'] ?>"
                                class="black_btn"
                                style="margin-top: 10px; text-align: center"
                                onclick="return confirm('Вы уверены, что хотите удалить этот пол?');"
                            >
                                УДАЛИТЬ
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>