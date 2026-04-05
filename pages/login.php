<style>

    .js-invisible {
        display: none !important;
    }


    .avtoreg_form {
        width: 65%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 25px;
        align-items: stretch;
        box-sizing: border-box;
    }


    .avtoreg_form .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        box-sizing: border-box;
    }



    #loginGroup,
    #registerGroup {
        width: 100%;
        box-sizing: border-box;
    }


    .avtoreg_form input,
    .avtoreg_form button {
        width: 100%;
        padding: 15px 20px;
        box-sizing: border-box;
    }
</style>

<div class="avtoreg_block container">
    <h1 class="avtoreg_h1">МОЙ АККАУНТ</h1>
    <p>Введите e-mail и продолжите:</p>

    <form id="authForm" class="avtoreg_form">
        <input type="hidden" id="stepField" value="email">

        <!-- E-mail -->
        <div id="emailGroup" class="form-group">
            <input type="email" id="emailInput" name="email" placeholder="Электронная почта">
            <div class="error" id="emailError"></div>
        </div>

        <!-- Вход -->
        <div id="loginGroup" class="form-group js-invisible">
            <input type="password" id="passwordInput" name="password" placeholder="Пароль">
            <div class="error" id="passwordError"></div>
        </div>

        <!-- Регистрация -->
        <div id="registerGroup" class="form-group js-invisible">
            <input type="text" id="usernameInput" name="username" placeholder="Имя">
            <div class="error" id="usernameError"></div>

            <input type="text" id="surnameInput" name="surname" placeholder="Фамилия">
            <div class="error" id="surnameError"></div>

            <input type="password" id="regPasswordInput" name="password" placeholder="Пароль">
            <div class="error" id="regPasswordError"></div>

            <input type="password" id="regPassword2Input" name="password2" placeholder="Повторите пароль">
            <div class="error" id="regPassword2Error"></div>

            <p>
                Нажимая “Создать аккаунт”, вы подтверждаете, что согласны с
                <a href="#">Условиями использования</a> и
                <a href="#">Политикой конфиденциальности</a>.
            </p>
        </div>

        <!-- Общая ошибка и кнопка -->
        <div class="error" id="generalError"></div>
        <button type="submit" class="black_btn" id="submitBtn">Продолжить</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('authForm');
        const stepField = document.getElementById('stepField');
        const submitBtn = document.getElementById('submitBtn');
        const hideClass = 'js-invisible';

        const groups = {
            email: document.getElementById('emailGroup'),
            login: document.getElementById('loginGroup'),
            register: document.getElementById('registerGroup'),
        };

        const errorsMap = {
            email: 'emailError',
            password: 'passwordError',
            username: 'usernameError',
            surname: 'surnameError',
            password_mismatch: 'regPasswordError',
            general: 'generalError'
        };

        form.addEventListener('submit', async e => {
            e.preventDefault();
            // Сброс старых ошибок
            Object.values(errorsMap).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });

            // Сбор данных
            const data = new FormData(form);
            data.set('ajax', '1');
            data.set('step', stepField.value);
            const pwdId = stepField.value === 'register' ?
                'regPasswordInput' :
                'passwordInput';
            data.set('password', document.getElementById(pwdId).value);

            // AJAX
            const resp = await fetch('../actions/auth_ajax.php', {
                method: 'POST',
                body: data,
                headers: {
                    'Accept': 'application/json'
                }
            });
            const json = await resp.json();

            if (json.step === 'success') {
                window.location.href = '?page=account';
                return;
            }

            // Переключаем шаг
            stepField.value = json.step;
            Object.entries(groups).forEach(([key, el]) => {
                el.classList.toggle(hideClass, key !== json.step);
            });

            // Обновляем текст кнопки
            submitBtn.textContent =
                json.step === 'login' ? 'Войти' :
                json.step === 'register' ? 'Создать профиль' :
                'Продолжить';

            // Куда выводить ошибку пароля
            errorsMap.password = json.step === 'register' ?
                'regPasswordError' :
                'passwordError';

            // Показываем ошибки
            for (const [field, msg] of Object.entries(json.errors || {})) {
                const errId = errorsMap[field] || 'generalError';
                document.getElementById(errId).textContent = msg;
            }
        });
    });
</script>