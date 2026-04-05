<?php
if (!isset($_GET['order_number'])) {
    header('Location: ./?page=basket');
    exit;
}

$order_number = $_GET['order_number'];
?>

<div class="order-success container">
    <div class="success-message">
        <h1>Заказ успешно оформлен!</h1>
        <p>Номер вашего заказа: <strong><?= htmlspecialchars($order_number) ?></strong></p>
        <p>Мы отправим вам уведомление о статусе заказа на указанный email.</p>
        <div class="success-actions">
            <a href="./" class="black_btn">Вернуться на главную</a>
        </div>
    </div>
</div>

<style>
.order-success {
    padding: 40px 20px;
    text-align: center;
}

.success-message {
    max-width: 600px;
    margin: 0 auto;
    padding: 40px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.success-message h1 {
    color: #28a745;
    margin-bottom: 20px;
}

.success-message p {
    margin: 10px 0;
    font-size: 18px;
}

.success-actions {
    margin-top: 30px;
}

.success-actions .black_btn {
    display: inline-block;
    text-decoration: none;
}
</style> 