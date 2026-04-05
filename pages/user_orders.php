<?php
global $database;

// Подключаем утилиты для работы с заказами
require_once __DIR__ . '/../includes/order_utils.php';

// Проверка, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header('Location: ./?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получение списка заказов для текущего пользователя
$sql = "
    SELECT o.*, u.email as user_email,
           COUNT(oi.id) as items_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = :user_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
";
$stmt = $database->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="py-5 container">
    <h1>Мои заказы</h1>

    <?php if (empty($orders)): ?>
        <div class="w-full mt-[100px] text-center py-[50px] flex flex-col gap-5">
            <span class="block w-full text-base font-bold">ВАШИ ЗАКАЗЫ БУДУТ ОТОБРАЖАТЬСЯ ЗДЕСЬ</span>
            <p>Как только вы оформите заказ, вы сможете следить за его доставкой на каждом этапе.</p>
            <a href="./?page=catalog" class="bg-black text-white border border-transparent py-[13px] px-[29px] text-lg hover:bg-white hover:text-black hover:border-black hover:scale-[1.01] transition-all duration-[600ms] cursor-pointer inline-block">ПЕРЕЙТИ В КАТАЛОГ</a>
        </div>
    <?php else: ?>
        <div>
            <?php foreach ($orders as $order): ?>
                <div class="bg-[#f9f9f9] border border-[#ddd] rounded-lg mb-5 p-5 shadow-[0_2px_8px_rgba(0,0,0,0.03)]">
                    <div class="flex justify-between items-center mb-[15px] border-b border-[#eee] pb-[15px]">
                        <h3>Заказ #<?= htmlspecialchars($order['order_number']) ?></h3>
                        <span class="text-[#666]"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></span>
                    </div>

                    <div class="mb-[15px]">
                        <p class="my-[5px]">Статус: <strong><?= translateOrderStatus($order['status']) ?></strong></p>
                        <p class="my-[5px]">Количество товаров: <?= $order['items_count'] ?></p>
                        <p class="my-[5px]">Сумма заказа: <?= number_format($order['total_amount'], 0, ',', ' ') ?> ₽</p>
                    </div>

                    <div>
                        <h4 class="mt-5 mb-[10px]">Состав заказа:</h4>
                        <?php
                        $items_sql = "
                            SELECT oi.*, p.title as product_title, s.title as size_title,
                                   (SELECT path FROM images WHERE product_id = p.id LIMIT 1) as image_path
                            FROM order_items oi
                            JOIN products p ON oi.product_id = p.id
                            JOIN size s ON oi.size_id = s.id
                            WHERE oi.order_id = :order_id
                        ";
                        $stmt_items = $database->prepare($items_sql);
                        $stmt_items->execute([':order_id' => $order['id']]);
                        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <table class="w-full border-collapse mt-[15px]">
                            <thead>
                                <tr>
                                    <th class="p-[10px] text-left border-b border-[#ddd] bg-[#f5f5f5] font-medium">Товар</th>
                                    <th class="p-[10px] text-left border-b border-[#ddd] bg-[#f5f5f5] font-medium"></th>
                                    <th class="p-[10px] text-left border-b border-[#ddd] bg-[#f5f5f5] font-medium">Размер</th>
                                    <th class="p-[10px] text-left border-b border-[#ddd] bg-[#f5f5f5] font-medium">Количество</th>
                                    <th class="p-[10px] text-left border-b border-[#ddd] bg-[#f5f5f5] font-medium">Цена</th>
                                    <th class="p-[10px] text-left border-b border-[#ddd] bg-[#f5f5f5] font-medium">Сумма</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="p-[10px] text-left border-b border-[#ddd]">
                                            <?php if ($item['image_path']): ?>
                                                <img src="uploads/products/<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['product_title']) ?>" width="50">
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-[10px] text-left border-b border-[#ddd]"><?= htmlspecialchars($item['product_title']) ?></td>
                                        <td class="p-[10px] text-left border-b border-[#ddd]"><?= htmlspecialchars($item['size_title']) ?></td>
                                        <td class="p-[10px] text-left border-b border-[#ddd]"><?= $item['quantity'] ?></td>
                                        <td class="p-[10px] text-left border-b border-[#ddd]"><?= number_format($item['price'], 0, ',', ' ') ?> ₽</td>
                                        <td class="p-[10px] text-left border-b border-[#ddd]"><?= number_format($item['price'] * $item['quantity'], 0, ',', ' ') ?> ₽</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

