<?php
// pages/catalog.php

global $database;

// 1) Загружаем все гендеры
$genders = $database
    ->query("SELECT id, title FROM genders ORDER BY title")
    ->fetchAll(PDO::FETCH_ASSOC);

// 2) Читаем GET-параметр gender (строка)
// 2) Читаем GET-параметр и сразу нормализуем
$genderParam = mb_strtolower(trim((string)($_GET['gender'] ?? '')), 'UTF-8');

// Ищем по нормализованному title
$gender = null;
foreach ($genders as $g) {
    if (mb_strtolower($g['title'], 'UTF-8') === $genderParam) {
        $gender = $g;
        break;
    }
}
// если не найден — первый
if (!$gender) {
    $gender = $genders[0] ?? null;
}
$gender_id = $gender['id'] ?? null;

// 3) Загружаем категории для этого гендера
$categories = [];
if ($gender_id) {
    $stmt = $database->prepare(
        "SELECT id, title FROM categories WHERE gender_id = :gid ORDER BY title"
    );
    $stmt->execute([':gid' => $gender_id]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Нормализуем category
$categoryParam = mb_strtolower(trim((string)($_GET['category'] ?? '')), 'UTF-8');

$category = null;
foreach ($categories as $c) {
    if (mb_strtolower($c['title'], 'UTF-8') === $categoryParam) {
        $category = $c;
        break;
    }
}
$category_id = $category['id'] ?? null;

// 5) Готовим и выполняем запрос товаров
$sql = "
    SELECT p.id, p.title, p.price, p.size_id,
           (SELECT path FROM images WHERE product_id = p.id LIMIT 1) AS image,
           CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
    FROM products p
    LEFT JOIN favorite f ON f.product_id = p.id AND f.user_id = :uid
    WHERE p.gender_id = :gid
";
$params = [':gid' => $gender_id, ':uid' => $_SESSION['user_id'] ?? 0];

if ($category_id) {
    $sql .= " AND p.category_id = :cid";
    $params[':cid'] = $category_id;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $database->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем все размеры
$sizes = $database->query("SELECT id, title FROM size ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && isset($_POST['product_id']) && isset($_POST['size_id'])) {
        $product_id = (int)$_POST['product_id'];
        $size_id = (int)$_POST['size_id'];

        try {
            // Проверяем, есть ли уже такой товар с таким размером в корзине
            $stmt = $database->prepare("SELECT * FROM carts WHERE product_id = :product_id AND user_id = :user_id AND size_id = :size_id");
            $stmt->execute([
                ':product_id' => $product_id,
                ':user_id' => $user_id,
                ':size_id' => $size_id
            ]);
            $cart = $stmt->fetch();

            if ($cart) {
                // Если товар уже есть в корзине, увеличиваем количество
                $stmt = $database->prepare("UPDATE carts SET count = count + 1 WHERE id = :cart_id");
                $stmt->execute([':cart_id' => $cart['id']]);
            } else {
                // Если товара нет в корзине, добавляем новый
                $stmt = $database->prepare("INSERT INTO carts (product_id, user_id, size_id, count) VALUES (:product_id, :user_id, :size_id, 1)");
                $stmt->execute([
                    ':product_id' => $product_id,
                    ':user_id' => $user_id,
                    ':size_id' => $size_id
                ]);
            }

            // Проверяем, не было ли вывода до этого места
            if (!headers_sent()) {
                header('Location: ./?page=basket');
                exit;
            } else {
                echo '<script>window.location.href = "./?page=basket";</script>';
                exit;
            }
        } catch (PDOException $e) {
            // Логируем ошибку
            error_log("Ошибка при добавлении товара в корзину: " . $e->getMessage());
            // Выводим сообщение пользователю
            echo '<div class="error-message">Произошла ошибка при добавлении товара в корзину. Пожалуйста, попробуйте позже.</div>';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];
        if (!empty($delete_id)) {
            unlink($product['image']);
            $sql = "DELETE FROM products WHERE id =:id";
            $stmt = $database->prepare($sql);
            $stmt->bindParam(':id', $delete_id);

            if ($stmt->execute()) {
                header("Location: ./");
                exit;
            } else {
                echo 'Ошибка удаления';
            }
        }
    }
} else {
    echo 'Войдите в аккаунт, чтобы добавлять товары в корзину';
}
?>

<!-- BANNER -->
<div class="banner_catalog container fixed" style="background-image: url('assets/media/image/catalog/banner/<?= mb_strtolower($gender['title']) === 'женщины' ? 'woman' : 'men' ?>.jpg'); background-size: cover; background-position: center; min-height: 645px; width: 100%; max-width: 1400px;">
    <h3 class="h3_catalog">НОВИНКИ</h3>
    <h1 class="h1_catalog"><?= htmlspecialchars($gender['title'] ?? '') ?></h1>
    <p>В коллекции <?= mb_strtolower(htmlspecialchars($gender['title'] ?? '')) ?> весна-лето 2025 представлена новая сумка на плечо, сумки и украшения, вдохновленные бамбуком, и модели с символом Horsebit.</p>
</div>

<!-- FILTERS: GENРER + CATEGORY -->
<div class="filtr_catalog container">
    <!-- Гендеры -->
    <?php foreach ($genders as $g): ?>
        <button class="btn_in_filtr_hidden">
            <a
                href="?page=catalog&gender=<?= urlencode($g['title']) ?>"
                class="<?= ($g['id'] === $gender_id) ? 'active' : '' ?>">
                <?= htmlspecialchars($g['title']) ?>
            </a>
        </button>
    <?php endforeach; ?>

    <span class="divider">|</span>

    <!-- Все категории -->
    <a
        href="?page=catalog&gender=<?= urlencode($gender['title']) ?>"
        class="<?= $category_id ? '' : 'active' ?>">
        Все категории
    </a>

    <!-- Конкретные категории -->
    <?php foreach ($categories as $c): ?>
        <a
            href="?page=catalog&gender=<?= urlencode($gender['title']) ?>&category=<?= urlencode($c['title']) ?>"
            class="<?= ($c['id'] === $category_id) ? 'active' : '' ?>">
            <?= htmlspecialchars($c['title']) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- CATALOG -->
<div class="cards_block_katalog container">
    <?php if (empty($products)): ?>
        <p>Товаров не найдено</p>
    <?php else: ?>
        <div class="cards_block_katalog">
            <?php foreach ($products as $p): ?>
                <div class="card_tovar">
                    <a href="?page=product&id=<?= $p['id'] ?>" class="card_thumb">
                        <?php if ($p['image']): ?>
                            <img src="uploads/products/<?= htmlspecialchars($p['image']) ?>"
                                alt="<?= htmlspecialchars($p['title']) ?>">
                        <?php else: ?>
                            <div class="no-photo">Нет фото</div>
                        <?php endif; ?>
                        <img src="assets/media/image/index/catalog/<?= $p['is_favorite'] ? 'heart-red.svg' : 'heart.svg' ?>"
                            class="heart favorite-btn"
                            data-product-id="<?= $p['id'] ?>"
                            onclick="event.preventDefault(); toggleFavorite(this);"
                            alt="">
                    </a>

                    <div class="info_card_tovar">
                        <h3><?= htmlspecialchars($p['title']) ?></h3>
                        
                        <div class="card_footer">
                            <p class="price"><?= number_format($p['price'], 0, ',', ' ') ?> ₽</p>
                            <div class="size">
                                <?php foreach ($sizes as $size): ?>
                                    <label class="size-btn">
                                        <input type="radio"
                                            name="size_<?= $p['id'] ?>"
                                            value="<?= $size['id'] ?>"
                                            required>
                                        <span><?= htmlspecialchars($size['title']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <form action="" method="post" class="add-to-cart-form">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="size_id" class="selected-size" value="">
                                <button type="submit" class="black_btn" disabled>В КОРЗИНУ</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>




<style>
    .size {
        display: flex;
        gap: 10px;
    }

    .size-btn {
        padding-top: 6px;
        display: inline-block;
        cursor: pointer;
        user-select: none;
        color: black;
    }

    .size-btn input[type="radio"] {
        display: none;
    }

    .size-btn input[type="radio"]:checked+span {
        font-weight: bold;
    }

    .size-btn span {
        display: block;
    }

    .black_btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }
</style>

<script>
    function toggleFavorite(btn) {
        if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            window.location.href = '?page=login';
            return;
        }

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

                // Toggle heart icon
                const isFavorite = data.status === 'added';
                btn.src = `assets/media/image/index/catalog/heart${isFavorite ? '-red' : ''}.svg`;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Произошла ошибка при обновлении избранного');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Обработка выбора размера
        document.querySelectorAll('.size-btn input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('.card_tovar').querySelector('.add-to-cart-form');
                const sizeInput = form.querySelector('.selected-size');
                const submitBtn = form.querySelector('button[type="submit"]');

                sizeInput.value = this.value;
                submitBtn.disabled = false;
            });
        });
    });
</script>