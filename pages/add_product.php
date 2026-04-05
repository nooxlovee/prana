<?php
// pages/add_product.php
global $database;

// Сообщение об успехе
$success = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Получаем справочные данные
$genders = $database->query("SELECT * FROM genders")->fetchAll(PDO::FETCH_ASSOC);
$categories = $database->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Переменные для формы (чтобы сохранить введённые значения)
$title = '';
$price = '';
$outside_first = '';
$outside_second = '';
$lining_first = '';
$lining_second = '';
$gender_id = '';
$category_id = '';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Собираем и чистим данные
    $title = trim($_POST['title'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $price = str_replace(' ', '', $price);
    $outside_first = trim($_POST['outside_first'] ?? '');
    $outside_second = trim($_POST['outside_second'] ?? '');
    $lining_first = trim($_POST['lining_first'] ?? '');
    $lining_second = trim($_POST['lining_second'] ?? '');
    $gender_id = (int)($_POST['gender_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);

    // 1. Валидация текстовых полей
    if ($title === '') {
        $errors['title'] = 'Введите название товара';
    } elseif (mb_strlen($title) > 40) {
        $errors['title'] = 'Название товара не должно превышать 35 символов';
    }

    // 1.1. Проверка уникальности названия
    if (empty($errors['title'])) {
        $uniqStmt = $database->prepare("SELECT COUNT(*) FROM products WHERE title = :title");
        $uniqStmt->execute([':title' => $title]);
        if ($uniqStmt->fetchColumn() > 0) {
            $errors['title'] = 'Товар с таким названием уже существует';
        }
    }

    // 2. Валидация цены
    if ($price === '' || !is_numeric($price)) {
        $errors['price'] = 'Введите корректную стоимость';
    }
    if ($outside_first === '' || $outside_second === '') {
        $errors['outside'] = 'Укажите оба параметра внешнего вида';
    }
    if ($lining_first === '' || $lining_second === '') {
        $errors['lining'] = 'Укажите оба параметра подкладки';
    }
    // 2. Валидация селектов
    $validGenderIds = array_column($genders, 'id');
    $validCategoryIds = array_column(
        array_filter($categories, fn($c) => $c['gender_id'] === $gender_id),
        'id'
    );
    if (!in_array($gender_id, $validGenderIds, true)) {
        $errors['gender'] = 'Выберите корректный пол';
    }
    if (!in_array($category_id, $validCategoryIds, true)) {
        $errors['category'] = 'Выберите корректную категорию';
    }
    // 3. Валидация фотографий
    if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['name']) || $_FILES['photos']['name'][0] === '') {
        $errors['photos'] = 'Добавьте хотя бы одно фото';
    } else {
        $count = count($_FILES['photos']['name']);
        if ($count > 4) {
            $errors['photos'] = 'Максимум 4 изображения';
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/avif'];
            for ($i = 0; $i < $count; $i++) {
                $err = $_FILES['photos']['error'][$i];
                $type = $_FILES['photos']['type'][$i];
                if ($err !== UPLOAD_ERR_OK) {
                    $errors['photos'] = 'Ошибка загрузки фото №' . ($i + 1);
                    break;
                }
                if (!in_array($type, $allowed, true)) {
                    $errors['photos'] = 'Неподдерживаемый формат фото №' . ($i + 1);
                    break;
                }
            }
        }
    }

    // 4. Если нет ошибок — сохраняем
    if (empty($errors)) {
        $stmt = $database->prepare("
            INSERT INTO products
              (title, price, outside_first, outside_second, lining_first, lining_second, gender_id, category_id, created_at)
            VALUES
              (:title, :price, :of1, :of2, :lf1, :lf2, :gid, :cid, NOW())
        ");
        $stmt->execute([
            ':title' => $title,
            ':price' => $price,
            ':of1' => $outside_first,
            ':of2' => $outside_second,
            ':lf1' => $lining_first,
            ':lf2' => $lining_second,
            ':gid' => $gender_id,
            ':cid' => $category_id,
        ]);
        $product_id = $database->lastInsertId();

        // 5. Сохраняем фото
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }
        foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
            $origName = $_FILES['photos']['name'][$i];
            $ext = pathinfo($origName, PATHINFO_EXTENSION);
            $newName = uniqid('prd_', true) . '.' . $ext;
            if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                $stmtImg = $database->prepare("
                    INSERT INTO images (product_id, path)
                    VALUES (:pid, :path)
                ");
                $stmtImg->execute([':pid' => $product_id, ':path' => $newName]);
            }
        }

        $_SESSION['message'] = 'Товар успешно добавлен';
        echo '<script>window.location.href = "./?page=admin_products";</script>';
        exit;
    }
}
?>
<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin_add_tovar">
            <h2 class="h2_admin">ДОБАВИТЬ ТОВАР</h2>
            <div class="admin_add_product_block">
                <div class="admin-form-wrapper">

                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="error">
                            <ul>
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" class="admin-form">
                        <!-- Название -->
                        <div class="full-width">
                            <label for="title">Название:</label>
                            <input id="title" name="title" type="text"
                                value="<?= htmlspecialchars($title) ?>" maxlength="50">
                        </div>

                        <!-- Цена -->
                        <div class="full-width">
                            <label for="price">Стоимость:</label>
                            <input id="price" name="price" type="text"
                                value="<?= htmlspecialchars(number_format((float)$price, 0, ' ', ' ')) ?>">
                        </div>

                        <!-- Внешний вид -->
                        <div>
                            <label>Внешний вид:</label>
                            <input name="outside_first" type="text"
                                placeholder="Параметр 1"
                                value="<?= htmlspecialchars($outside_first) ?>">
                            <input name="outside_second" type="text"
                                placeholder="Параметр 2"
                                value="<?= htmlspecialchars($outside_second) ?>">
                        </div>

                        <!-- Подкладка -->
                        <div>
                            <label>Подкладка:</label>
                            <input name="lining_first" type="text"
                                placeholder="Параметр 1"
                                value="<?= htmlspecialchars($lining_first) ?>">
                            <input name="lining_second" type="text"
                                placeholder="Параметр 2"
                                value="<?= htmlspecialchars($lining_second) ?>">
                        </div>

                        <!-- Пол -->
                        <div>
                            <label for="gender">Пол:</label>
                            <select id="gender" name="gender_id">
                                <option value="">— Выберите —</option>
                                <?php foreach ($genders as $g): ?>
                                    <option value="<?= $g['id'] ?>"
                                        <?= $g['id'] === $gender_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Категория -->
                        <div>
                            <label for="category">Категория:</label>
                            <select id="category" name="category_id">
                                <option value="">— Выберите —</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        data-gender-id="<?= $c['gender_id'] ?>"
                                        <?= $c['id'] === $category_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Фото -->
                        <div class="full-width" style="margin-top:20px;">
                            <label>Фотографии (до 4 шт):</label>
                            <input type="file"
                                name="photos[]"
                                accept="image/jpeg,image/png,image/gif,image/avif"
                                multiple
                                onchange="previewImages(this)">
                            <small>Максимум 4 изображения</small>
                            <div id="image-preview"
                                style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;"></div>
                        </div>

                        <button type="submit" class="black_btn" style="margin-top:20px;">
                            ДОБАВИТЬ ТОВАР
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Фильтрация категорий по полу -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const genderSelect = document.getElementById('gender');
        const categorySelect = document.getElementById('category');
        const opts = Array.from(categorySelect.options);

        function filter() {
            const gid = genderSelect.value;
            categorySelect.innerHTML = '<option value="">— Выберите —</option>';
            opts.forEach(o => {
                if (!gid || o.dataset.genderId === gid) {
                    categorySelect.appendChild(o);
                }
            });
        }

        genderSelect.addEventListener('change', filter);
        filter();
    });
</script>

<!-- Предпросмотр изображений -->
<script>
    function previewImages(input) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';
        const files = Array.from(input.files);

        if (files.length > 4) {
            alert('Можно загрузить не более 4 изображений');
            input.value = '';
            return;
        }
        files.forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.cssText = 'width:100px;height:100px;object-fit:cover;border:1px solid #ccc;border-radius:4px;';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var priceInputs = document.querySelectorAll('input[name="price"]');
        priceInputs.forEach(function(input) {
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
                this.value = value;
            });
        });
    });
</script>