<?php
global $database;

if (!isset($_SESSION['user_id'])) {
    header('Location: ./?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем товары в корзине с информацией о размерах
$sql = "
    SELECT c.id AS cart_id, c.count, c.size_id,
           p.title, p.price,
           s.title as size_title,
           (SELECT path FROM images WHERE product_id = p.id LIMIT 1) AS image
    FROM carts c
    JOIN products p ON c.product_id = p.id
    JOIN size s ON s.id = c.size_id
    WHERE c.user_id = :user_id
";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach ($result as $item) {
    $total += $item['price'] * $item['count'];
}

// Получаем все размеры
$sizes = $database->query("SELECT id, title FROM size ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

// Обработка изменения количества
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['increment'])) {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $database->prepare("UPDATE carts SET count = count + 1 WHERE id = :cart_id AND user_id = :user_id");
        $stmt->execute([':cart_id' => $cart_id, ':user_id' => $user_id]);
        header('Location: ./?page=basket');
        exit;
    }
    
    if (isset($_POST['decrement'])) {
        $cart_id = (int)$_POST['cart_id'];
        $count = (int)$_POST['count'];
        
        if ($count > 1) {
            $stmt = $database->prepare("UPDATE carts SET count = count - 1 WHERE id = :cart_id AND user_id = :user_id");
            $stmt->execute([':cart_id' => $cart_id, ':user_id' => $user_id]);
        } else {
            $stmt = $database->prepare("DELETE FROM carts WHERE id = :cart_id AND user_id = :user_id");
            $stmt->execute([':cart_id' => $cart_id, ':user_id' => $user_id]);
        }
        header('Location: ./?page=basket');
        exit;
    }
    
    if (isset($_POST['remove'])) {
        try {
            $cart_id = (int)$_POST['cart_id'];
            
            // Проверяем существование товара в корзине
            $check = $database->prepare("SELECT id FROM carts WHERE id = :cart_id AND user_id = :user_id");
            $check->execute([':cart_id' => $cart_id, ':user_id' => $user_id]);
            
            if (!$check->fetch()) {
                throw new Exception('Товар не найден в корзине');
            }

            // Удаляем товар
            $stmt = $database->prepare("DELETE FROM carts WHERE id = :cart_id AND user_id = :user_id");
            $result = $stmt->execute([':cart_id' => $cart_id, ':user_id' => $user_id]);

            if (!$result) {
                throw new Exception('Ошибка при удалении товара');
            }

            // Проверяем, не были ли отправлены заголовки
            if (!headers_sent()) {
                header('Location: ./?page=basket');
                exit;
            } else {
                echo '<script>window.location.href = "./?page=basket";</script>';
                exit;
            }
        } catch (Exception $e) {
            // Логируем ошибку
            error_log("Ошибка при удалении товара из корзины: " . $e->getMessage());
            // Выводим сообщение пользователю
            echo '<div class="error-message" style="color: red; margin: 10px 0;">Произошла ошибка при удалении товара. Пожалуйста, попробуйте позже.</div>';
        }
    }

    // Обработка изменения товара через модальное окно
    if (isset($_POST['edit_save'])) {
        try {
            $cart_id = (int)$_POST['cart_id'];
            $size_id = (int)$_POST['size_id'];
            $count = max(1, (int)$_POST['count']);

            // Проверяем существование товара в корзине
            $check = $database->prepare("SELECT id FROM carts WHERE id = :cart_id AND user_id = :user_id");
            $check->execute([':cart_id' => $cart_id, ':user_id' => $user_id]);
            
            if (!$check->fetch()) {
                throw new Exception('Товар не найден в корзине');
            }
        
            // Обновляем данные
            $stmt = $database->prepare("UPDATE carts SET size_id = :size_id, count = :count WHERE id = :cart_id AND user_id = :user_id");
            $result = $stmt->execute([
                ':size_id' => $size_id,
                ':count' => $count,
                ':cart_id' => $cart_id,
                ':user_id' => $user_id
            ]);
            if (!$result) {
                throw new Exception('Ошибка при обновлении данных');
            }

            // Проверяем, не были ли отправлены заголовки
            if (!headers_sent()) {
                header('Location: ./?page=basket');
                exit;
            } else {
                echo '<script>window.location.href = "./?page=basket";</script>';
                exit;
            }
        } catch (Exception $e) {
            // Логируем ошибку
            error_log("Ошибка при редактировании товара в корзине: " . $e->getMessage());
            // Выводим сообщение пользователю
            echo '<div class="error-message" style="color: red; margin: 10px 0;">Произошла ошибка при обновлении товара. Пожалуйста, попробуйте позже.</div>';
        }
    }

    // Обработка оформления заказа
    if (isset($_POST['create_order'])) {
        try {
            $database->beginTransaction();

            // Создаем номер заказа
            $order_number = uniqid() . $user_id;

            // Создаем заказ
            $stmt = $database->prepare("
                INSERT INTO orders (user_id, order_number, total_amount) 
                VALUES (:user_id, :order_number, :total_amount)
            ");
            $stmt->execute([
                ':user_id' => $user_id,
                ':order_number' => $order_number,
                ':total_amount' => $total + 2450
            ]);
            $order_id = $database->lastInsertId();

            // Получаем все товары из корзины пользователя
            $stmt = $database->prepare("SELECT c.product_id, c.size_id, c.count, p.price FROM carts c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('[' . date('Y-m-d H:i:s') . '] Произошла какая-то ошибка: ' . $cart_items . PHP_EOL, 3, __DIR__ . '/../logs/database.log');

            // Вставляем каждый товар в order_items
            $stmt_insert = $database->prepare("INSERT INTO order_items (order_id, product_id, size_id, quantity, price) VALUES (:order_id, :product_id, :size_id, :quantity, :price)");

            foreach ($cart_items as $item) {
                $stmt_insert->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['product_id'],
                    ':size_id' => $item['size_id'],
                    ':quantity' => $item['count'],
                    ':price' => $item['price']
                ]);
            }

            // Очищаем корзину
            $stmt = $database->prepare("DELETE FROM carts WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);

            $database->commit();

            // Перенаправляем на страницу успешного оформления заказа
                echo "<script>window.location.href='./?page=order_success&order_number=" . urlencode($order_number) . "';</script>";
                exit;
            
        } catch (Exception $e) {
            $database->rollBack();
            error_log('[' . date('Y-m-d H:i:s') . '] Произошла какая-то ошибка: ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../logs/database.log');
            error_log("Ошибка при оформлении заказа: " . $e->getMessage());
            echo '<div class="error-message" style="color: red; margin: 10px 0;">Произошла ошибка при оформлении заказа. Пожалуйста, попробуйте позже.</div>';
        }
    }
}

// Подсчет общей суммы
$total = 0;
foreach ($result as $item) {
    $total += $item['price'] * $item['count'];
}
?>

<!-- BASKET START -->
<div class="basket_block container">
    <span>Ваша корзина</span>
    <div class="left_right_blocks_basket">
        <div class="left_content_basket">
            <hr>
            <?php if (!empty($result)): ?>
                <?php foreach ($result as $cart): ?>
                    <div class="card_tovar_in_basket">
                        <?php if ($cart['image']): ?>
                            <img src="uploads/products/<?= htmlspecialchars($cart['image']) ?>" alt="<?= htmlspecialchars($cart['title']) ?>" width="100px">
                        <?php else: ?>
                            <div class="no-photo">Нет фото</div>
                        <?php endif; ?>
                        <div class="info_card_in_basket">
                            <h3 class="h3_basket"><?= htmlspecialchars($cart['title']) ?></h3>
                            <p>Размер: <?= htmlspecialchars($cart['size_title']) ?></p>
                            <p>Количество: <?= $cart['count'] ?></p>
                            <p>Стоимость: <span><?= number_format($cart['price'] * $cart['count'], 0, ',', ' ') ?> ₽</span></p>
                            <div class="btns_card_in_basket">
                                <a href="#" class="edit-btn"
                                   data-cart-id="<?= $cart['cart_id'] ?>"
                                   data-title="<?= htmlspecialchars($cart['title']) ?>"
                                   data-image="<?= htmlspecialchars($cart['image']) ?>"
                                   data-size-id="<?= $cart['size_id'] ?>"
                                   data-size-title="<?= htmlspecialchars($cart['size_title']) ?>"
                                   data-count="<?= $cart['count'] ?>"
                                   data-price="<?= $cart['price'] ?>"
                                >Редактировать</a>
                                <form action="" method="post" style="display: inline;">
                                    <input type="hidden" name="cart_id" value="<?= $cart['cart_id'] ?>">
                                    <button type="submit" name="remove" class="btn-link">УДАЛИТЬ</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <hr>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Ваша корзина пуста</p>
            <?php endif; ?>
        </div>
        <div class="right_content_basket">
            <span>КРАТКОЕ ОПИСАНИЕ ЗАКАЗА</span>
            <p>Номер заказа: <?= date('YmdHis') ?></p>
            <hr>
            <div class="info_about_basket">
                <div class="stroke_info_basket">
                    <span>Итого</span>
                    <p><?= number_format($total, 0, ',', ' ') ?> ₽</p>
                </div>
                <div class="stroke_info_basket">
                    <span>Доставка</span>
                    <p>2 450 ₽</p>
                </div>
                <div class="stroke_info_basket">
                    <span>ВСЕГО</span>
                    <p class="all_price_basket"><?= number_format($total + 2450, 0, ',', ' ') ?> ₽</p>
                </div>
            </div>
            <form method="post">
                <button type="submit" name="create_order" class="black_btn">ОФОРМИТЬ ЗАКАЗ</button>
            </form>
            <hr>
            <span class="help_text_basket">МОЖЕМ ЛИ МЫ ПОМОЧЬ?</span>
            <div class="kontacts_help_basket">
                <img src="./assets/media/image/basket/phone.svg" alt="">
                <a href="tel:+8 800 777-7-777">8 800 777-7-777</a>
            </div>
            <div class="kontacts_help_basket">
                <img src="./assets/media/image/basket/email.svg" alt="">
                <a href="mailto:pranashop@mail.ru">pranashop@mail.ru</a>
            </div>
        </div>
    </div>
</div>
<!-- BASKET END -->

<!-- Модальное окно -->
<div id="editModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-body" style="display:flex;gap:40px;align-items:center;justify-content:center;">
            <div class="modal-image" style="text-align:center;">
                <img id="modalProductImage" src="" alt="" style="max-width:200px;display:block;margin:0 auto;">
                <div style="margin-top:15px;">
                    <div id="modalProductTitle" style="font-size:18px;font-weight:500;"></div>
                    <div id="modalProductPrice" style="font-size:16px;color:black;margin-top:5px;"></div>
                </div>
            </div>
            <div class="modal-info">
                <form id="editForm" method="post">
                    <input type="hidden" name="cart_id" id="modalCartId">
                    <label>Размер</label>
                    <select name="size_id" id="modalSizeId">
                        <?php foreach ($sizes as $size): ?>
                            <option value="<?= $size['id'] ?>"><?= htmlspecialchars($size['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Количество</label>
                    <input type="number" name="count" id="modalCount" min="1" value="1">
                    <button type="submit" name="edit_save" class="black_btn" style="margin-top:20px;">СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
                    <button type="button" class="modal-cancel" style="margin-top:20px;">ОТМЕНА</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('modalProductImage').src = this.dataset.image ? 'uploads/products/' + this.dataset.image : '';
        document.getElementById('modalProductTitle').textContent = this.dataset.title;
        document.getElementById('modalProductPrice').textContent = this.dataset.price + ' ₽';
        document.getElementById('modalCartId').value = this.dataset.cartId;
        document.getElementById('modalCount').value = this.dataset.count;
        // Установить выбранный размер
        let sizeSelect = document.getElementById('modalSizeId');
        sizeSelect.value = this.dataset.sizeId;
    });
});
document.querySelector('.close').onclick = function() {
    document.getElementById('editModal').style.display = 'none';
};
document.querySelector('.modal-cancel').onclick = function() {
    document.getElementById('editModal').style.display = 'none';
};
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        document.getElementById('editModal').style.display = 'none';
    }
};
</script>

<style>
.btn-link {
    background: none;
    border: none;
    color: #000;
    text-decoration: underline;
    cursor: pointer;
    padding: 0 5px;
}

.btn-link:hover {
    color: #666;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0; top: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: #fff;
    padding: 40px;
    border-radius: 10px;
    min-width: 400px;
    max-width: 700px;
    position: relative;
    margin: auto;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
}
.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    cursor: pointer;
}
.modal-body label {
    display: block;
    margin-top: 15px;
    margin-bottom: 5px;
}
.modal-body input[type="number"],
.modal-body select {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    font-size: 16px;
}
.modal-cancel {
    background: none;
    border: 1px solid #aaa;
    color: #333;
    padding: 10px 20px;
    margin-left: 10px;
    cursor: pointer;
}
</style>