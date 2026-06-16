<?php
require_admin();

$sku = $_GET['sku'] ?? '';
if ($sku === '') { header('Location: index.php'); exit; }

$used = db()->prepare('SELECT COUNT(*) c FROM order_lines WHERE sku = ?');
$used->execute([$sku]);
if ((int)$used->fetch()['c'] > 0) {
    layout_header('Удаление товара');
    echo '<div class="notice">Нельзя удалить товар: он используется в существующих заказах.</div>';
    echo '<a class="btn" href="index.php">Назад к товарам</a>';
    layout_footer();
    return;
}

$st = db()->prepare('DELETE FROM goods WHERE sku = ?');
$st->execute([$sku]);
header('Location: index.php');
exit;
