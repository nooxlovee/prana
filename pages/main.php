<?php
global $database;
global $USER;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];

    if (empty($username) || empty($email)) {
        echo 'Заполните пустые поля';
    } else {
        $sqlRassilka = "INSERT INTO rassilka (username, email) VALUES ('$username', '$email')";
        $stmt = $database->query($sqlRassilka);
    }
}

?>

<style>
    header {
        background-color: transparent;
    }

    .header-row,
    .header-center,
    .header-right {
        display: flex;
        align-items: center;
    }

    .header_menu_white {
        padding: 0;
    }
</style>

<div class="fon_header_banner">
    <!-- HEADER START -->
    <header>
        <div class="header_menu container header-row" id="main-header">
            <div class="header-center" id="header-logo-placeholder">
                <!-- Логотип появится здесь при скролле -->
            </div>
            <div class="header-right">
                <?php if ($USER): ?>
                    <a href="../?page=basket"> <img src="../assets/media/image/index/header/1.png" alt="" class="header-icon"></a>
                <?php endif; ?>
                <div class="user_menu">
                    <img src="../assets/media/image/index/header/2.png" alt="" class="header-icon">
                    <div class="dropdown_menu">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="./?page=account">Мой аккаунт</a>
                            <a href="./?page=user_orders">Мои заказы</a>
                            <a href="./?page=setting_account">Настройка аккаунта</a>
                            <a href="./?page=favorite">Сохраненные элементы</a>
                            <?php if (isset($USER['role']) && $USER['role'] === 'admin'): ?>
                                <div class="dropdown_line"></div>
                                <a href="./?page=add_product">Панель администратора</a>
                            <?php endif; ?>
                            <div class="dropdown_line"></div>
                            <a href="?exit" class="exit">Выход</a>
                        <?php else: ?>
                            <a href="./?page=login">Войти</a>
                            <a href="./?page=login">Мои заказы</a>
                            <a href="./?page=login">Настройка аккаунта</a>
                            <a href="./?page=login">Сохраненные элементы</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="menu_burger">
                    <img src="../assets/media/image/index/header/4.png" alt="" class="header-icon burger-icon">
                    <span class="menu-text">Меню</span>
                </div>
            </div>
        </div>
    </header>
    <!-- HEADER END -->
    <!-- BANNER START -->
    <div class="banner">
        <div class="banner_block container">
            <a href="#" id="banner-logo-link"> <img src="../assets/media/image/index/banner/Prana.svg" alt="" class="banner-logo" id="banner-logo"></a>
            <div class="btns_in_katalog_her_him">
                <div class="btns_her_him">
                    <a href="./?page=catalog&gender=Женщины">
                        <button class="white_btn">Для нее</button>
                    </a>
                    <a href="./?page=catalog&gender=Мужчины">
                        <button class="white_btn">Для него</button>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- BANNER END -->
</div>

<style>
    .header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 80px;
        width: 100%;
        position: relative;
        z-index: 10;
    }

    .header-center {
        flex: 1 1 0%;
        display: flex;
        justify-content: center;
        align-items: center;
        min-width: 180px;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 28px;
    }

    .header_menu {
        background: transparent !important;
        transition: background 0.3s, box-shadow 0.3s;
        box-shadow: none;
        padding: 0 24px;
    }

    .header_menu.scrolled {
        background: #fff !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .header-icon {
        height: 28px;
        width: auto;
        display: inline-block;
    }

    .menu_burger {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .menu-text {
        font-size: 18px;
        font-family: inherit;
        letter-spacing: 0.5px;
    }

    @media (max-width: 900px) {
        .header-row {
            min-height: 60px;
        }

        .header-center {
            min-width: 120px;
        }

        .header-right {
            gap: 16px;
        }

        .menu-text {
            font-size: 16px;
        }
    }
</style>

<script>
    // Перемещение логотипа и смена фона шапки при скролле
    window.addEventListener('DOMContentLoaded', function() {
        const header = document.getElementById('main-header');
        const bannerLogo = document.getElementById('banner-logo');
        const headerLogoPlaceholder = document.getElementById('header-logo-placeholder');
        let logoMoved = false;

        function onScroll() {
            if (window.scrollY > 25) {
                header.classList.add('scrolled');
                if (!logoMoved) {
                    // Клонируем логотип и вставляем в шапку
                    const logoClone = bannerLogo.cloneNode(true);
                    logoClone.id = 'header-logo';
                    logoClone.style.height = '40px';
                    logoClone.style.transition = 'all 0.3s';
                    headerLogoPlaceholder.innerHTML = '';
                    headerLogoPlaceholder.appendChild(logoClone);
                    bannerLogo.style.opacity = '0';
                    logoMoved = true;
                }
            } else {
                header.classList.remove('scrolled');
                headerLogoPlaceholder.innerHTML = '';
                bannerLogo.style.opacity = '1';
                logoMoved = false;
            }
        }
        window.addEventListener('scroll', onScroll);
    });
</script>

<!-- KATALOG START -->
<div class="katalog container mt-115" id="new">
    <h2>НОВИНКИ</h2>
    <div class="cards_block_katalog">
        <?php
        // Получаем 4 случайных товара
        $stmt = $database->prepare("
                SELECT p.id, p.title, p.price, 
                    (SELECT path FROM images WHERE product_id = p.id LIMIT 1) AS image,
                    CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
                FROM products p
                LEFT JOIN favorite f ON f.product_id = p.id AND f.user_id = :uid
                ORDER BY RAND()
                LIMIT 4
            ");
        $stmt->execute([':uid' => $_SESSION['user_id'] ?? 0]);
        $random_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($random_products as $p): ?>
            <a href="?page=product&id=<?= $p['id'] ?>">
                <div class="card_tovar">
                    <?php if ($p['image']): ?>
                        <img src="uploads/products/<?= htmlspecialchars($p['image']) ?>"
                            alt="<?= htmlspecialchars($p['title']) ?>">
                    <?php else: ?>
                        <div class="no-photo">Нет фото</div>
                    <?php endif; ?>
                    <img src="assets/media/image/index/catalog/<?= $p['is_favorite'] ? 'heart-red.svg' : 'heart.svg' ?>"
                        alt=""
                        class="heart favorite-btn"
                        data-product-id="<?= $p['id'] ?>"
                        onclick="event.preventDefault(); toggleFavorite(this);">
                    <div class="info_card_tovar">
                        <h3><?= htmlspecialchars($p['title']) ?></h3>
                        <div>
                            <p><?= number_format($p['price'], 0, ',', ' ') ?> ₽</p>
                            <div class="size">
                                <p>XS</p>
                                <p>S</p>
                                <p>M</p>
                                <p>L</p>
                                <p>XL</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<!-- KATALOG END -->

<!-- NEW-COLLECTION START -->
<div class="new-collection mt-115" id="new-collection">
    <div class="container">
        <div class="new-collection_block">
            <h2>НОВАЯ КОЛЛЕКЦИЯ</h2>
            <p>Не упустите возможность обновить свой стиль с нашей новой коллекцией! Откройте для себя итальянскую
                моду и создайте образы, которые будут вдохновлять вас каждый день. Заказывайте прямо сейчас и
                наслаждайтесь бесплатной доставкой на первые покупки!</p>
            <a href="./?page=catalog">
                <button class="black_btn">В каталог</button>
            </a>
        </div>
    </div>
</div>
<!-- NEW-COLLECTION END -->

<!-- RASSILKA START -->
<div class="rassilka" id="rassilka">
    <div class="container">
        <div class="rassilka_block">
            <h2>Получите скидку 10%</h2>
            <p>Подпишитесь на нашу рассылку, и вы получите скидку 10% на следующую покупку, доступ к эксклюзивным
                акциям и многому другому!</p>
            <form action="" method="post" class="form_rassilka">
                <input type="text" placeholder="Имя" name="username">
                <input type="email" name="email" placeholder="E-mail">
                <button type="submit" class="white_btn">Подписаться</button>
            </form>
        </div>
    </div>
</div>
<!-- RASSILKA END -->

<!-- ABOUT START -->
<div class="about mt-115" id="about">
    <h2>О НАС</h2>
    <div class="about_block container">
        <img src="../assets/media/image/index/about_us/1.jpg" alt="">
        <div class="rigth_content_about_block">
            <p>Prana - это воплощение итальянской страсти к моде и стилю. Наша компания родилась в самом сердце
                Италии, где традиции высокого качества и безупречного вкуса передаются из поколения в поколение.
                <br>
                <br>
                Мы с гордостью представляем коллекции одежды, созданные лучшими итальянскими дизайнерами. Каждая
                модель пропитана духом средиземноморской элегантности и непревзойденной эстетики. Мы используем
                только самые лучшие натуральные ткани и материалы, чтобы обеспечить комфорт и долговечность наших
                изделий.
            </p>
        </div>
    </div>
</div>
<!-- ABOUT END -->


<!-- Бургер-меню -->
<div class="burger-menu-overlay"></div>
<div class="burger-menu">
    <button class="close-burger">
        <img src="../assets/media/image/index/header/otmena.svg" alt="Закрыть">
    </button>
    <nav class="burger-nav">
        <a href="#new">Новинки</a>
        <a href="#new-collection">Новая коллекция</a>
        <a href="#rassilka">Подписаться на рассылку</a>
        <a href="./?page=catalog&gender=Мужчины">Мужчинам</a>
        <a href="./?page=catalog&gender=Женщины">Женщинам</a>
        <a href="#about">О нас</a>
        <a href="#footer">Контакты</a>

    </nav>
</div>

<script>
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        const logo = document.querySelector('.banner-logo');
        const icons = document.querySelectorAll('.header-icon');
        const menuText = document.querySelector('.menu-text');
        const navMenu = document.querySelector('.nav_menu');
        const headerMenu = document.querySelector('.header_menu');

        // Проверяем ширину экрана
        // const isMobile = window.innerWidth <= 390; // Logic should apply on all screen sizes

        if (window.scrollY > 25) {
            header.classList.add('header_menu_white');
            logo.classList.add('scrolled');
            headerMenu.classList.add('header_menu_white');

            // В мобильной версии иконки остаются скрытыми
            // if (!isMobile) {
            icons.forEach(icon => icon.style.filter = 'brightness(0)');
            // }

            menuText.style.color = 'black';

            // В мобильной версии не показываем иконки в бургер-меню
            // if (!isMobile) {
            navMenu.classList.add('scrolled');
            // }
        } else {
            header.classList.remove('header_menu_white');
            logo.classList.remove('scrolled');
            headerMenu.classList.remove('header_menu_white');

            // В мобильной версии иконки остаются скрытыми
            // if (!isMobile) {
            icons.forEach(icon => icon.style.filter = 'none');
            // }

            menuText.style.color = 'white';

            // В мобильной версии не показываем иконки в бургер-меню
            // if (!isMobile) {
            navMenu.classList.remove('scrolled');
            // }
        }
    });

    // Бургер-меню
    const burgerIcon = document.querySelector('.burger-icon');
    const menuText = document.querySelector('.menu-text');
    const burgerMenu = document.querySelector('.burger-menu');
    const burgerOverlay = document.querySelector('.burger-menu-overlay');
    const closeBurger = document.querySelector('.close-burger');
    const burgerLinks = document.querySelectorAll('.burger-nav a');

    function toggleBurgerMenu() {
        burgerMenu.classList.toggle('active');
        burgerOverlay.classList.toggle('active');
        document.body.style.overflow = burgerMenu.classList.contains('active') ? 'hidden' : '';
    }

    burgerIcon.addEventListener('click', toggleBurgerMenu);
    menuText.addEventListener('click', toggleBurgerMenu);
    closeBurger.addEventListener('click', toggleBurgerMenu);
    burgerOverlay.addEventListener('click', toggleBurgerMenu);

    // Закрытие меню при клике на ссылки
    burgerLinks.forEach(link => {
        link.addEventListener('click', toggleBurgerMenu);
    });

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
</script>
</body>

</html>