<!-- HEADER START -->
<header class="background_white_header">
    <div class="header_menu_white container">
        <a href=".."> <img src="../assets/media/image/catalog/Prana.svg" alt="" class="prana-svg"></a>
        <div class="nav_menu">
            <?php if ($USER): ?>
                <a href="../?page=basket" id="basket-link"> <img src="../assets/media/image/index/header/basket-black.svg" alt="" class="header-icon"></a>
            <?php endif; ?>
            <!-- Мини-корзина -->
            <div id="mini-cart-overlay" class="mini-cart-overlay" style="display:none;"></div>
            <div id="mini-cart" class="mini-cart" style="display:none;">
                <div class="mini-cart-header">
                    <span>Ваша корзина</span>
                    <button id="close-mini-cart" type="button">&times;</button>
                </div>
                <div class="mini-cart-body">
                    <p>Корзина пуста</p>
                </div>
                <div class="mini-cart-footer">
                    <a href="../?page=basket" class="black_btn">Перейти в корзину</a>
                </div>
            </div>
        </div>
        <div class="user_menu">
            <img src="../assets/media/image/index/header/user-black.svg" alt="" class="header-icon">
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
            <img src="../assets/media/image/catalog/menu.svg" alt="" class="header-icon burger-icon">
            <span class="menu-text">Меню</span>
        </div>
    </div>
</header>

<script>
    function renderMiniCart(items) {
        const body = document.querySelector('.mini-cart-body');
        if (!items.length) {
            body.innerHTML = '<p>Корзина пуста</p>';
            return;
        }
        body.innerHTML = items.map(item => `
        <div class="mini-cart-item" style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
            <img src="uploads/products/${item.image || ''}" alt="${item.title}" width="50" style="border-radius:5px;object-fit:cover;">
            <div>
                <div style="font-weight:500;">${item.title}</div>
                <div style="font-size:13px;">Размер: ${item.size_title}</div>
                <div style="font-size:13px;">Кол-во: ${item.count}</div>
                <div style="font-size:13px;">Сумма: ${item.price * item.count} ₽</div>
            </div>
        </div>
    `).join('');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cartLink = document.getElementById('basket-link');
        const miniCart = document.getElementById('mini-cart');
        const miniCartOverlay = document.getElementById('mini-cart-overlay');
        const closeMiniCart = document.getElementById('close-mini-cart');

        if (cartLink) {
            cartLink.addEventListener('click', function(e) {
                e.preventDefault();
                fetch('actions/get_cart.php')
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
                    .then(data => {
                        console.log('Cart data:', data);
                        if (data.success) {
                            renderMiniCart(data.items);
                        } else {
                            console.error('Error in cart data:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching cart:', error);
                    });
                miniCart.style.display = 'block';
                miniCartOverlay.style.display = 'block';
            });
        }
        if (closeMiniCart) {
            closeMiniCart.addEventListener('click', function() {
                miniCart.style.display = 'none';
                miniCartOverlay.style.display = 'none';
            });
        }
        if (miniCartOverlay) {
            miniCartOverlay.addEventListener('click', function() {
                miniCart.style.display = 'none';
                miniCartOverlay.style.display = 'none';
            });
        }
    });
</script>

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
        <hr />
        <?php if ($USER): ?>
            <a href="./?page=account">Мой аккаунт</a>
            <a href="./?page=user_orders">Мои заказы</a>
            <a href="./?page=setting_account">Настройка аккаунта</a>
            <a href="./?page=favorite">Сохраненные элементы</a>
            <?php if (isset($USER['role']) && $USER['role'] === 'admin'): ?>
                <hr />
                <a href="./?page=add_product">Панель администратора</a>
            <?php endif; ?>
            <hr />
            <a href="?exit" class="exit">Выход</a>
        <?php else: ?>
            <!-- <a href="./?page=login">Войти</a>
            <a href="./?page=login">Мои заказы</a>
            <a href="./?page=login">Настройка аккаунта</a>
            <a href="./?page=login">Сохраненные элементы</a> -->
            <!-- <hr /> -->
        <?php endif; ?>
    </nav>
</div>
<!-- END Бургер-меню -->

<script>
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
<!-- HEADER END -->