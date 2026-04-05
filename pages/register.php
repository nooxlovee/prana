<?php

if(isset($_SESSION['user_id'])) {
    header('Location: ./');
}

$flag = true;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
}

?>



    <!-- FORM START -->
    <div class="avtoreg_block container">
        <h1 class="avtoreg_h1">РЕГИСТРАЦИЯ</h1>
        <form action="#" class="avtoreg_form" method="post">
            <input type="email" placeholder="Электронная почта" name="email" value="<?=isset($_POST['email']) ? $_POST['email'] : '' ?>"> 
            <?php
            if(isset($_POST['email'])) {
                if(empty($_POST['email'])) {
                    $flag = false;
                    echo 'Заполните E-mail';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $flag = false;
                    echo 'E-mail не валиден';
                } else {
                    $user = $database->query("SELECT * FROM users WHERE email = '$email'")->fetch();

                    if($user) {
                        $flag = false;
                        echo 'Пользователь существует';
                    }
                }
            }
            ?>
            <br>
            <input type="password" placeholder="Пароль" name="password"> 
            <ul>
                <li>Пожалуйста, введите не менее 8 символов</li>
                <li>Пожалуйста, введите хотя бы один номер</li>
                <li>Пожалуйста, введите один специальный символ (!+,-./:;<=>?@)</li>
            </ul>
            <?php
            if(isset($_POST['password'])) {
                if(empty($_POST['password'])) {
                    $flag = false;
                    echo 'Введите пароль';
                } elseif(strlen($password) < 8) {
                    $flag = false;
                    echo 'Введите не менее 8 символов';
                }
            }
            ?>
            <br>
            <input type="password" placeholder="Повторите пароль" name="password_confirm"> <br>
            <?php
            if(isset($_POST['password_confirm'])) {
                if(empty($_POST['password_confirm'])) {
                    $flag = false;
                    echo 'Введите повторный пароль';
                } elseif($password != $password_confirm) {
                    $flag = false;
                    echo 'Пароли не совпадают';
                }
            }
            ?>
            <br>
            <input type="text" placeholder="Имя" name="username" value="<?=isset($_POST['username']) ? $_POST['username'] : '' ?>"> <br>
            <?php
            if(isset($_POST['username'])) {
                if(empty($_POST['username'])) {
                    $flag = false;
                    echo 'Введите имя';
                }
            }
            ?>
            <br>
            <input type="text" placeholder="Фамилия" name="surname" value="<?=isset($_POST['surname']) ? $_POST['surname'] : '' ?>"> <br>
            <?php
            if(isset($_POST['surname'])) {
                if(empty($_POST['surname'])) {
                    $flag = false;
                    echo 'Введите фамилию';
                }
            }
            ?>
            <br>
            <p>Нажимая “Создать аккаунт”, вы подтверждаете, что согласны с нашими <a href="#">Условиями использования</a> и нашей <a href="">политикой конфиденциальности</a> и что вы хотите создать свой профиль в Prana</p>
            <button class="black_btn" type="submit">ВОЙТИ</button>

            <?php
            if($_SERVER['REQUEST_METHOD'] === "POST") {
                if($flag) {
                    $password = password_hash($password, PASSWORD_DEFAULT);
                    $database->query("INSERT INTO users (username, surname, email, password) VALUES ('$username', '$surname', '$email', '$password')");
                    header('Location: ./?page=account');
                }
            }
            ?>
        </form>
    </div>

    <!-- FORM END -->

