<?php
global $database;
global $USER;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(htmlspecialchars($_POST['username'] ?? ''));
    $surname = trim(htmlspecialchars($_POST['surname'] ?? ''));

    if ($username === '') {
        $errors['username'] = 'Имя не может оставаться пустым';
    }
    if ($surname === '') {
        $errors['surname'] = 'Фамилия не может оставаться пустой';
    }

    if (empty($errors) && ($username !== $USER['username'] || $surname !== $USER['surname'])) {
        $sql = "UPDATE users SET username = :username, surname = :surname WHERE id = :id";
        $stmt = $database->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':surname', $surname);
        $stmt->bindParam(':id', $USER['id']);

        if ($stmt->execute()) {
            echo '<script>window.location.href = "./?page=account";</script>';
            exit();
        }
        $errors['db'] = 'Ошибка при сохранении в базу';

    }
}
?>
<!-- HTML-форма -->
<h1 class="h1_setting_account container">МОИ ЛИЧНЫЕ ДАННЫЕ</h1>
<form action="" method="post" class="setting_account_form container">
    <input
            type="text"
            name="username"
            placeholder="ИМЯ"
            value="<?= htmlspecialchars($_POST['username'] ?? $USER['username']) ?>"
    >
    <?php if (isset($errors['username'])): ?>
        <div class="error"><?= $errors['username'] ?></div>
    <?php endif; ?>

    <input
            type="text"
            name="surname"
            placeholder="ФАМИЛИЯ"
            value="<?= htmlspecialchars($_POST['surname'] ?? $USER['surname']) ?>"
    >
    <?php if (isset($errors['surname'])): ?>
        <div class="error"><?= $errors['surname'] ?></div>
    <?php endif; ?>

    <input
            type="email"
            placeholder="EMAIL"
            value="<?= htmlspecialchars($USER['email']) ?>"
            disabled
    >

    <?php if (isset($errors['db'])): ?>
        <div class="error"><?= $errors['db'] ?></div>
    <?php endif; ?>

    <div class="setting_btns">
        <button type="submit" class="black_btn">СОХРАНИТЬ ИЗМЕНЕНИЯ</button>
    </div>
</form>