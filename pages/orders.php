<?php
// 1) Включаем отображение всех ошибок и буферизацию
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/../database/connect.php';
require_once __DIR__ . '/../includes/order_utils.php';
global $database;

// 2) Обработка изменения статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int) $_POST['order_id'];
    // Берём статус «как есть» — если в БД он хранится с пробелами
    $new_status = $_POST['status'];

    $stmt = $database->prepare("
        UPDATE orders
           SET status    = :status
         WHERE id        = :order_id
    ");
    $success = $stmt->execute([
        ':status'    => $new_status,
        ':order_id'  => $order_id,
    ]);

    if (!$success) {
        $errorInfo = $stmt->errorInfo();
        die('Ошибка при обновлении статуса: ' . print_r($errorInfo, true));
    }

    // 3) Очищаем буфер, чтобы убрать любой вывод до редиректа
    if (ob_get_length() !== false) {
        ob_end_clean();
    }

    // 4) Делаем редирект: если заголовки ещё не отправлены, — через header,
    // иначе — через JS-перенаправление
    if (!headers_sent()) {
        header('Location: ./?page=orders');
        exit;
    } else {
        echo '<script>window.location.href = "?page=orders";</script>';
        exit;
    }
}

// 5) Подключаем шапку и выводим список заказов
include 'includes/header_white.php';
?>
<div class="admin_all_block mt-115">
    <div class="adminpanel_block">
        <p>ПАНЕЛЬ АДМИНИСТРАТОРА</p>
    </div>
    <div class="admin_block">
        <?php require_once __DIR__ . '/../includes/left_content_admin.php'; ?>
        <div class="right_content_admin">
<?php
// Получение списка заказов
$sql = "
    SELECT
      o.*,
      u.email      AS user_email,
      COUNT(oi.id) AS items_count
    FROM orders o
    JOIN users u        ON o.user_id    = u.id
    LEFT JOIN order_items oi ON o.id       = oi.order_id
    GROUP BY o.id
";
$orders = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
    <h2 class="h2_admin">ЗАКАЗЫ</h2>
    <div class="orders-list">
        <?php foreach (
            $orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <h3>Заказ #<?= htmlspecialchars($order['order_number'], ENT_QUOTES) ?></h3>
                    <span class="order-date">
                        <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?>
                    </span>
                </div>
                <div class="order-info">
                    <p>Покупатель: <?= htmlspecialchars($order['user_email'], ENT_QUOTES) ?></p>
                    <p>Количество товаров: <?= $order['items_count'] ?></p>
                    <p>Сумма заказа: <?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</p>
                    <p>Текущий статус: <strong><?= translateOrderStatus($order['status']) ?></strong></p>

                    <form method="post" class="status-form">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <select name="status" onchange="this.form.submit()">
                            <option value="issued"     <?= $order['status'] === 'issued'      ? 'selected' : '' ?>>Оформлен</option>
                            <option value=" assembled" <?= $order['status'] === ' assembled'  ? 'selected' : '' ?>>Собран</option>
                            <option value=" sent"      <?= $order['status'] === ' sent'       ? 'selected' : '' ?>>Отправлен</option>
                            <option value="completed"  <?= $order['status'] === 'completed'   ? 'selected' : '' ?>>Выполнен</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                    </form>
                </div>
                <div class="order-items">
                    <?php
                    $items_sql = "
                        SELECT
                          oi.*,
                          p.title AS product_title,
                          s.title AS size_title
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        JOIN size     s ON oi.size_id    = s.id
                        WHERE oi.order_id = :order_id
                    ";
                    $stmt = $database->prepare($items_sql);
                    $stmt->execute([':order_id' => $order['id']]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Товар</th>
                                <th>Размер</th>
                                <th>Количество</th>
                                <th>Цена</th>
                                <th>Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_title'], ENT_QUOTES) ?></td>
                                    <td><?= htmlspecialchars($item['size_title'],    ENT_QUOTES) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format($item['price'], 0, ',', ' ') ?> ₽</td>
                                    <td><?= number_format($item['price'] * $item['quantity'], 0, ',', ' ') ?> ₽</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
        </div>
    </div>
</div>
