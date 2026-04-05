<?php
/**
 * Утилиты для работы с заказами
 */

/**
 * Переводит статус заказа с английского на русский язык
 * @param string $status Статус заказа на английском языке
 * @return string Статус заказа на русском языке
 */
function translateOrderStatus($status) {
    $statusTranslations = [
        'issued' => 'Оформлен',
        ' assembled' => 'Собран',
        ' sent' => 'Отправлен',
        'completed' => 'Выполнен'
    ];
    
    return isset($statusTranslations[$status]) ? $statusTranslations[$status] : $status;
}

/**
 * Получает список всех возможных статусов заказов с их переводами
 * @return array Массив статусов в формате ['value' => 'label']
 */
function getOrderStatuses() {
    return [
        'issued' => 'Оформлен',
        ' assembled' => 'Собран',
        ' sent' => 'Отправлен',
        'completed' => 'Выполнен'
    ];
}
?> 