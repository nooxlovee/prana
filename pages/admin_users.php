<?php
// pages/admin_users.php
/** @var PDO $database */
global $database;

// Включаем буферизацию вывода
ob_start();

// Готовим переменную текущей страницы (для редиректов)
$page = 'admin_users';

// 1) Бан/разбан
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['user_id'], $_POST['toggle_ban'])
) {
    $userId    = (int) $_POST['user_id'];
    $newStatus = $_POST['toggle_ban'] === 'ban' ? 'banned' : 'not_banned';

    $stmtUp = $database->prepare("UPDATE users SET banned = :status WHERE id = :id");
    $stmtUp->execute([
        ':status' => $newStatus,
        ':id'     => $userId,
    ]);

    // Редирект обратно с тем же поиском
    $search   = trim($_GET['search'] ?? '');
    $location = "?page={$page}" . ($search !== '' ? "&search=" . urlencode($search) : '');
    echo "<script>window.location.href = '{$location}';</script>";
    exit;
}

// 2) Читаем search-параметр
$search = trim($_GET['search'] ?? '');

// 3) Строим SQL и массив параметров
$sql    = "SELECT id, username, surname, email, banned FROM users";
$params = [];

if ($search !== '') {
    // Позиционные плейсхолдеры вместо именованных
    $sql .= "
      WHERE username LIKE ?
         OR surname  LIKE ?
         OR CONCAT(username, ' ', surname) LIKE ?
         OR email    LIKE ?
    ";
    $term = "%{$search}%";
    // Передаём параметр четыре раза
    $params = [$term, $term, $term, $term];
}

$sql .= " ORDER BY surname, username";

// 4) Выполняем запрос
$stmt  = $database->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>

        <div class="right_content_admin">
            <div class="h2_and_poisk_admin">
                <h2 class="h2_admin">ПОЛЬЗОВАТЕЛИ</h2>

                <!-- Поисковая форма -->
                <form method="get" action="./" style="display:inline;">
                    <input type="hidden" name="page" value="admin_users">
                    <input
                            type="text"
                            name="search"
                            placeholder="Поиск по имени или e-mail"
                            value="<?= htmlspecialchars($search) ?>"
                    >
                </form>
            </div>

            <div class="users_admin_block">
                <?php if (empty($users)): ?>
                    <h1>Нет пользователей</h1>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <div class="one_user_admin">
                            <p>
                                <?= htmlspecialchars("{$user['surname']} {$user['username']}") ?>
                                <?php if ($user['banned'] === 'banned'): ?>
                                    <span style="color:red;">(Забанен)</span>
                                <?php endif; ?>
                            </p>
                            <p><?= htmlspecialchars($user['email']) ?></p>

                            <!-- Бан/разбан -->
                            <form
                                    method="post"
                                    action="?page=admin_users<?= $search!==''?'&search='.urlencode($search):'' ?>"
                                    style="display:inline"
                            >
                                <input type="hidden" name="user_id"    value="<?= $user['id'] ?>">
                                <input type="hidden" name="toggle_ban"
                                       value="<?= $user['banned'] === 'banned' ? 'unban' : 'ban' ?>">
                                <button type="submit" class="black_btn">
                                    <?= $user['banned'] === 'banned' ? 'Разбанить' : 'Забанить' ?>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>