<?php
global $database;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

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
    }

    $sql = "SELECT * FROM products WHERE id = '$id'";
    $stmt = $database->query($sql);
    $stmt->execute();

    $product = $stmt->fetch();

    $sqlImages = "SELECT * FROM images WHERE product_id = '$id'";
    $images = $database->query($sqlImages)->fetchAll();
}

// Получаем все размеры
$sizes = $database->query("SELECT id, title FROM size ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

?>




<div class="slider-wrapper">
    <div class="slider" id="slider">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <div class="slide">
                    <img src="<?= '../uploads/products/' . htmlspecialchars($image['path']) ?>" alt="">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="product-info-overlay">
        <div class="product-info-content">
            <h2><?= $product['title'] ?></h2>
            <p class="size-info">Размер и рост модели: S · 176 см</p>
            <div class="materials">
                <div class="materials-section">
                    <p class="section-title">Внешний вид</p>
                    <p><?= $product['outside_first'] ?></p>
                    <p><?= $product['outside_second'] ?></p>
                </div>
                <div class="materials-section">
                    <p class="section-title">Подкладка</p>
                    <p><?= $product['lining_first'] ?></p>
                    <p><?= $product['lining_second'] ?></p>
                </div>
            </div>
            <form action="?page=product&id=<?= $product['id'] ?>" method="post" class="add-to-cart-form">
                <div class="size">
                    <p class="section-title">Размер</p>
                    <?php foreach ($sizes as $size): ?>
                        <label class="size-btn">
                            <input type="radio" name="size_id" value="<?= $size['id'] ?>" required>
                            <span><?= htmlspecialchars($size['title']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <button type="submit" class="add-to-cart" disabled>В КОРЗИНУ</button>
            </form>
        </div>
    </div>

    <div class="slider-buttons container">
        <button id="prev" class="slider-btn">
            <img src="../assets/media/image/tovar/prev.svg" alt="prev">
        </button>
        <button id="next" class="slider-btn">
            <img src="../assets/media/image/tovar/next.svg" alt="next">
        </button>
    </div>
</div>

<style>
    /* Ваши существующие стили остаются без изменений */
    html,
    body {
        margin: 0;
        padding: 0;
    }

    .slider-wrapper {
        width: 100%;
        height: 105vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        /* Добавляем для позиционирования overlay */
    }

    .container {
        position: relative;
        width: 100%;
        max-width: 1400px;
        height: 100%;
        margin: 0 auto;
        overflow: hidden;
    }

    .slider {
        display: flex;
        width: 100%;
        transition: transform 0.5s ease;
        position: relative;
    }

    .slide {
        min-width: 50%;
        height: 100%;
        box-sizing: border-box;
    }

    .slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .slider-buttons {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transform: translateY(-50%);
        pointer-events: none;
        z-index: 10;
    }

    .slider-btn {
        pointer-events: all;
        background: none;
        border: none;
        padding: 0;
        margin: 0;
        width: 60px;
        height: 60px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .slider-btn img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .slider-btn:hover {
        transform: scale(1.35);
    }

    /* Новые стили для overlay блока */
    .product-info-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 5;
        background-color: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 10px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .product-info-content {
        text-align: center;
    }


    .product-info-content h2 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 24px;
        color: #333;
    }

    .size-info {
        margin-bottom: 20px;
        color: #666;
        font-size: 16px;
    }

    .materials {
        display: flex;
        justify-content: space-around;
        margin-bottom: 25px;
    }

    .materials-section {
        text-align: center;
    }

    .section-title {
        font-weight: bold;
        margin-bottom: 5px;
        color: #444;
    }

    .size {
        margin-bottom: 20px;
    }

    .size .section-title {
        margin-bottom: 10px;
    }

    .size-btn {
        display: inline-block;
        margin-right: 10px;
        cursor: pointer;
    }

    .size-btn input[type="radio"] {
        display: none;
    }

    .size-btn span {
        display: block;
        padding: 5px 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .size-btn input[type="radio"]:checked + span {
        background-color: #000;
        color: #fff;
        border-color: #000;
    }

    .add-to-cart {
        background-color: #000;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 16px;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s;
        width: 100%;
    }

    .add-to-cart:hover {
        background-color: #333;
    }

    .add-to-cart:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- SLIDER SCRIPT ---
        const slider = document.getElementById('slider');
        if (slider) {
            const originalSlides = Array.from(slider.querySelectorAll('.slide'));
            const hasEnoughSlides = originalSlides.length > 2;

            if (hasEnoughSlides) {
                // Клонируем по 2 слайда с начала и конца для бесконечной прокрутки
                for (let i = 0; i < 2; i++) {
                    const cloneFirst = originalSlides[i].cloneNode(true);
                    const cloneLast = originalSlides[originalSlides.length - 1 - i].cloneNode(true);
                    slider.appendChild(cloneFirst);
                    slider.insertBefore(cloneLast, slider.firstChild);
                }
            }

            let currentIndex = hasEnoughSlides ? 2 : 0;
            let isTransitioning = false;

            const getSlideWidth = () => {
                const slide = slider.querySelector('.slide');
                return slide ? slide.offsetWidth : 0;
            };

            const updateTransform = (animate = true) => {
                const width = getSlideWidth();
                if (width === 0) return;
                slider.style.transition = animate ? 'transform 0.5s ease' : 'none';
                slider.style.transform = `translateX(-${width * currentIndex}px)`;
            };

            const moveSlider = (direction) => {
                if (isTransitioning || !hasEnoughSlides) return;
                isTransitioning = true;
                currentIndex += direction;
                updateTransform(true);
            };

            const handleLoop = () => {
                if (!hasEnoughSlides) {
                    isTransitioning = false;
                    return;
                }

                const slides = slider.querySelectorAll('.slide');
                if (currentIndex >= slides.length - 2) {
                    currentIndex = 2;
                    setTimeout(() => updateTransform(false), 20);
                }

                if (currentIndex < 2) {
                    currentIndex = slides.length - 4;
                    setTimeout(() => updateTransform(false), 20);
                }

                setTimeout(() => isTransitioning = false, 50);
            };

            slider.addEventListener('transitionend', handleLoop);
            document.getElementById('next').addEventListener('click', () => moveSlider(1));
            document.getElementById('prev').addEventListener('click', () => moveSlider(-1));
            
            window.addEventListener('resize', () => updateTransform(false));
            // Изначальная установка позиции
            updateTransform(false);
        }

        // --- ADD TO CART SCRIPT ---
        document.querySelectorAll('.size-btn input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const form = this.closest('.add-to-cart-form');
                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                }
            });
        });
    });
</script>