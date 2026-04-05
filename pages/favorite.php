<?php

global $database;

// Проверяем авторизацию
if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=login');
    exit;
}

// Получаем избранные товары пользователя
$sql = "
    SELECT p.id, p.title, p.price,
           (SELECT path FROM images WHERE product_id = p.id LIMIT 1) AS image
    FROM products p
    INNER JOIN favorite f ON f.product_id = p.id
    WHERE f.user_id = :uid
    ORDER BY p.id DESC
";

$stmt = $database->prepare($sql);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$favorite_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <!-- FAVORITE START -->
    <div class="favorite_block container">
        <div class="title_and_line_favorite">
            <h3 class="h3_favorite">СОХРАНЕННЫЕ ЭЛЕМЕНТЫ</h3>
            <hr class="line">
            <p>У вас есть <?= count($favorite_products) ?> элементов в разделе сохраненные элементы</p>
        </div>

        <?php if (empty($favorite_products)): ?>
            <div class="empty_favorite">
                <a href="?page=catalog" class="black_btn">ПЕРЕЙТИ В КАТАЛОГ</a>
            </div>
            <h4 class="h4_favorite">ВАМ ТАКЖЕ МОЖЕТ ПОНРАВИТЬСЯ</h4>
            <div class="cards_block_katalog">
                <?php
                // Получаем 4 случайных товара
                $stmt = $database->query("
                    SELECT p.id, p.title, p.price, 
                        (SELECT path FROM images WHERE product_id = p.id LIMIT 1) AS image
                    FROM products p
                    ORDER BY RAND()
                    LIMIT 4
                ");
                $random_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($random_products as $p): ?>
                    <a href="?page=product&id=<?= $p['id'] ?>">
                        <div class="card_tovar">
                            <?php if ($p['image']): ?>
                                <img src="uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                            <?php else: ?>
                                <div class="no-photo">Нет фото</div>
                            <?php endif; ?>
                            <img
                                src="assets/media/image/index/catalog/heart.svg"
                                alt=""
                                class="heart favorite-btn"
                                data-product-id="<?= $p['id'] ?>"
                                onclick="event.preventDefault(); toggleFavorite(this);"
                            >
                            <div class="info_card_tovar">
                                <h3><?= htmlspecialchars($p['title']) ?></h3>
                                <p><?= number_format($p['price'], 0, ',', ' ') ?> ₽</p>
                            </div>
                            <button class="black_btn">В КОРЗИНУ</button>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cards_block_katalog">
                <?php foreach ($favorite_products as $p): ?>
                    <a href="?page=product&id=<?= $p['id'] ?>">
                        <div class="card_tovar">
                            <?php if ($p['image']): ?>
                                <img src="uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>">
                            <?php else: ?>
                                <div class="no-photo">Нет фото</div>
                            <?php endif; ?>
                            <img
                                src="assets/media/image/index/catalog/heart-red.svg"
                                alt="" 
                                class="heart favorite-btn"
                                data-product-id="<?= $p['id'] ?>"
                                onclick="event.preventDefault(); toggleFavorite(this);"
                            >
                            <div class="info_card_tovar">
                                <h3><?= htmlspecialchars($p['title']) ?></h3>
                                <p><?= number_format($p['price'], 0, ',', ' ') ?> ₽</p>
                                <div class="size">
                                    <p>XS</p>
                                    <p>S</p>
                                    <p>M</p>
                                    <p>L</p>
                                    <p>XL</p>
                                </div>
                            </div>
                            <button class="black_btn">В КОРЗИНУ</button>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <!-- FAVORITE END -->

<script>
function toggleFavorite(btn) {
    const productId = btn.dataset.productId;
    const formData = new FormData();
    formData.append('product_id', productId);

    fetch('actions/toggle_favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        if (data.status === 'removed' || data.status === 'added') {
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при обновлении избранного');
    });
}
</script>

