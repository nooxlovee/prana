<?php
// pages/edit_product.php
global $database;

// Сообщение об успехе
$success = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Справочники
$genders    = $database->query("SELECT * FROM genders")->fetchAll(PDO::FETCH_ASSOC);
$categories = $database->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Получаем ID товара
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    exit('Некорректный ID товара');
}

// Загружаем данные товара
$stmt = $database->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    exit('Товар не найден');
}

// Загружаем существующие изображения
$stmt = $database->prepare("SELECT * FROM images WHERE product_id = :pid");
$stmt->execute([':pid' => $id]);
$existingImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Инициализация переменных для формы
$title          = $product['title'];
$price          = $product['price'];
$outside_first  = $product['outside_first'];
$outside_second = $product['outside_second'];
$lining_first   = $product['lining_first'];
$lining_second  = $product['lining_second'];
$gender_id      = (int)$product['gender_id'];
$category_id    = (int)$product['category_id'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Собираем и чистим
    $title          = trim($_POST['title']          ?? '');
    $price          = trim($_POST['price']          ?? '');
    $price          = str_replace(' ', '', $price);
    $outside_first  = trim($_POST['outside_first']  ?? '');
    $outside_second = trim($_POST['outside_second'] ?? '');
    $lining_first   = trim($_POST['lining_first']   ?? '');
    $lining_second  = trim($_POST['lining_second']  ?? '');
    $gender_id      = (int)($_POST['gender_id']     ?? 0);
    $category_id    = (int)($_POST['category_id']   ?? 0);
    $toDelete       = array_map('intval', $_POST['remove_photos'] ?? []);

    // 1) Валидация
    if ($title === '') {
        $errors['title'] = 'Введите название товара';
    } else {
        // Уникальность, исключая текущий товар
        $uniq = $database->prepare(
            "SELECT COUNT(*) FROM products WHERE title = :t AND id <> :id"
        );
        $uniq->execute([':t' => $title, ':id' => $id]);
        if ($uniq->fetchColumn() > 0) {
            $errors['title'] = 'Товар с таким названием уже существует';
        }
    }

    if ($price === '' || !is_numeric($price)) {
        $errors['price'] = 'Введите корректную стоимость';
    }
    if ($outside_first === '' || $outside_second === '') {
        $errors['outside'] = 'Укажите оба параметра внешнего вида';
    }
    if ($lining_first === '' || $lining_second === '') {
        $errors['lining'] = 'Укажите оба параметра подкладки';
    }

    $validG = array_column($genders, 'id');
    if (!in_array($gender_id, $validG, true)) {
        $errors['gender'] = 'Выберите корректный пол';
    }
    $validC = array_column(
        array_filter($categories, fn($c) => $c['gender_id'] === $gender_id),
        'id'
    );
    if (!in_array($category_id, $validC, true)) {
        $errors['category'] = 'Выберите корректную категорию';
    }

    // 2) Считаем сколько останется после удаления
    $keepCount = count(array_filter(
        $existingImages,
        fn($img) => !in_array((int)$img['id'], $toDelete, true)
    ));

    // 3) Проверка новых файлов
    $newCount = (isset($_FILES['photos'])
        && $_FILES['photos']['name'][0] !== '')
        ? count($_FILES['photos']['name'])
        : 0;

    if ($keepCount + $newCount > 4) {
        $errors['photos'] = 'Всего может быть не более 4 изображений';
    } else {
        $allowed = ['image/jpeg','image/png','image/gif'];
        for ($i = 0; $i < $newCount; $i++) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                $errors['photos'] = 'Ошибка загрузки файла №'.($i+1);
                break;
            }
            if (!in_array($_FILES['photos']['type'][$i], $allowed, true)) {
                $errors['photos'] = 'Неподдерживаемый формат файла №'.($i+1);
                break;
            }
        }
    }

    // 4) Если нет ошибок — обновляем
    if (empty($errors)) {
        $database->beginTransaction();

        // 4.1 Обновляем поля товара
        $upd = $database->prepare("
            UPDATE products SET
              title           = :title,
              price           = :price,
              outside_first   = :of1,
              outside_second  = :of2,
              lining_first    = :lf1,
              lining_second   = :lf2,
              gender_id       = :gid,
              category_id     = :cid
            WHERE id = :id
        ");
        $upd->execute([
            ':title' => $title,
            ':price' => $price,
            ':of1'   => $outside_first,
            ':of2'   => $outside_second,
            ':lf1'   => $lining_first,
            ':lf2'   => $lining_second,
            ':gid'   => $gender_id,
            ':cid'   => $category_id,
            ':id'    => $id,
        ]);

        // 4.2 Удаляем помеченные фото
        if ($toDelete) {
            $delStmt = $database->prepare("DELETE FROM images WHERE id = :iid");
            foreach ($toDelete as $imgId) {
                // unlink файла
                foreach ($existingImages as $img) {
                    if ((int)$img['id'] === $imgId) {
                        @unlink(__DIR__.'/../uploads/products/'.$img['path']);
                        break;
                    }
                }
                // удаляем из БД
                $delStmt->execute([':iid' => $imgId]);
            }
        }

        // 4.3 Сохраняем новые фото
        $uploadDir = __DIR__.'/../uploads/products/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $uploadDir));
        }
        for ($i = 0; $i < $newCount; $i++) {
            $orig = $_FILES['photos']['name'][$i];
            $ext  = pathinfo($orig, PATHINFO_EXTENSION);
            $new  = uniqid('prd_', true) . '.' . $ext;
            move_uploaded_file(
                $_FILES['photos']['tmp_name'][$i],
                $uploadDir . $new
            );
            $ins = $database->prepare("
                INSERT INTO images (product_id, path) 
                VALUES (:pid, :path)
            ");
            $ins->execute([
                ':pid'  => $id,
                ':path' => $new,
            ]);
        }

        $database->commit();

        $_SESSION['message'] = 'Товар успешно обновлён';
        echo "<script>window.location.href = './?page=edit_product&id={$id}';</script>";
        exit;
    }
}
?>

<div class="admin_all_block container mt-115">
    <div class="adminpanel_block"><p>ПАНЕЛЬ АДМИНИСТРАТОРА</p></div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin_add_tovar">
            <h2 class="h2_admin">РЕДАКТИРОВАТЬ ТОВАР</h2>
            <div class="admin_add_product_block">
                <div class="admin-form-wrapper">

                    <?php if ($success): ?>
                        <div class="success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="error"><ul>
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul></div>
                    <?php endif; ?>

                    <form
                            method="post"
                            enctype="multipart/form-data"
                            class="admin-form"
                            action="?page=edit_product&id=<?= $id ?>"
                    >
                        <!-- Название -->
                        <div class="full-width">
                            <label for="title">Название:</label>
                            <input
                                    id="title"
                                    name="title"
                                    type="text"
                                    value="<?= htmlspecialchars($title) ?>"
                            >
                        </div>

                        <!-- Цена -->
                        <div class="full-width">
                            <label for="price">Стоимость:</label>
                            <input
                                    id="price"
                                    name="price"
                                    type="text"
                                    value="<?= htmlspecialchars(number_format((float)$price, 0, ' ', ' ')) ?>"
                            >
                        </div>

                        <!-- Внешний вид -->
                        <div>
                            <label>Внешний вид:</label>
                            <input
                                    name="outside_first"
                                    type="text"
                                    placeholder="Параметр 1"
                                    value="<?= htmlspecialchars($outside_first) ?>"
                            >
                            <input
                                    name="outside_second"
                                    type="text"
                                    placeholder="Параметр 2"
                                    value="<?= htmlspecialchars($outside_second) ?>"
                            >
                        </div>

                        <!-- Подкладка -->
                        <div>
                            <label>Подкладка:</label>
                            <input
                                    name="lining_first"
                                    type="text"
                                    placeholder="Параметр 1"
                                    value="<?= htmlspecialchars($lining_first) ?>"
                            >
                            <input
                                    name="lining_second"
                                    type="text"
                                    placeholder="Параметр 2"
                                    value="<?= htmlspecialchars($lining_second) ?>"
                            >
                        </div>

                        <!-- Пол -->
                        <div>
                            <label for="gender">Пол:</label>
                            <select id="gender" name="gender_id">
                                <option value="">— Выберите —</option>
                                <?php foreach ($genders as $g): ?>
                                    <option
                                            value="<?= $g['id'] ?>"
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
                                    <option
                                            value="<?= $c['id'] ?>"
                                            data-gender-id="<?= $c['gender_id'] ?>"
                                        <?= $c['id'] === $category_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Существующие фото -->
                        <?php if ($existingImages): ?>
                            <div class="full-width" style="margin-top:10px;">
                                <p>Текущие фотографии (чекнуть для удаления):</p>
                                <?php foreach ($existingImages as $img): ?>
                                    <label style="display:inline-block;position:relative;margin:5px;">
                                        <input
                                                type="checkbox"
                                                name="remove_photos[]"
                                                value="<?= $img['id'] ?>"
                                                style="position:absolute;top:0;left:0;">
                                        <img
                                                src="uploads/products/<?= htmlspecialchars($img['path']) ?>"
                                                style="width:100px;height:100px;object-fit:cover;border:1px solid #ccc;"
                                        >
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Новые фото -->
                        <div class="full-width" style="margin-top:10px;">
                            <label>Добавить фото (до 4 всего):</label>
                            <input
                                    type="file"
                                    name="photos[]"
                                    accept="image/jpeg,image/png,image/gif"
                                    multiple
                                    onchange="previewImages(this)"
                            >
                            <div
                                    id="image-preview"
                                    style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;"
                            ></div>
                        </div>

                        <button
                                type="submit"
                                class="black_btn"
                                style="margin-top:20px;"
                        >
                            СОХРАНИТЬ ИЗМЕНЕНИЯ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Фильтрация категорий по выбранному полу
    document.addEventListener('DOMContentLoaded', () => {
        const genderSel   = document.getElementById('gender');
        const categorySel = document.getElementById('category');
        const opts        = Array.from(categorySel.options);

        function filter() {
            const gid = genderSel.value;
            categorySel.innerHTML = '<option value="">— Выберите —</option>';
            opts.forEach(o => {
                if (!gid || o.dataset.genderId === gid) {
                    categorySel.appendChild(o);
                }
            });
        }
        genderSel.addEventListener('change', filter);
        filter();
    });

    // Предпросмотр новых изображений
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