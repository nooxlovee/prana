<?php

$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM products";
$params = [];

if ($search !== '') {
    $sql .= " WHERE title LIKE ? ";
    $params[] = "%{$search}%";
}

$stmt = $database->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(2);

?>
<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php' ?>
        
        <div class="right_content_admin">
            <div class="h2_and_poisk_admin">
                <h2 class="h2_admin">ТОВАРЫ</h2>
                <form method="get" action="./" style="display:inline;">
                    <input type="hidden" name="page" value="admin_products">
                    <input
                        type="text"
                        name="search"
                        placeholder="Поиск по названию"
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </form>
            </div>
            <?php if (empty($products)): ?>
                <h1>Товаров нет</h1>
            <?php else: ?>
                <div class="product_grid">
                    <?php foreach ($products as $product): ?>
                        <div class="card_tovar">
                            <?php
                            $sql = 'SELECT * FROM images WHERE product_id =' . $product['id'];
                            $images = $database->query($sql)->fetchAll(2);
                            ?>

                            <?php if (!empty($images)): ?>
                                <img src="<?= '../uploads/products/' . htmlspecialchars($images[0]['path']) ?>" alt="" width="100%">
                            <?php endif; ?>


                            <a
                                    href="../actions/delete_admin.php?type=product&id=<?= $product['id'] ?>"
                                    onclick="return confirm('Вы уверены, что хотите удалить этот товар?');"
                            >
                                <img
                                        src="assets/media/image/favorite/catalog/delete.svg"
                                        alt="Удалить"
                                        class="heart"
                                >
                            </a>
                            <div class="info_card_tovar">
                                <h3><?= $product['title'] ?></h3>
                                <p><?= number_format($product['price'], 0, ',', ' ') ?> ₽</p>
                            </div>
                            <a href="./?page=edit_product&id=<?= $product['id'] ?>" class="black_btn">РЕДАКТИРОВАТЬ</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
