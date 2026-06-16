<?php
require_admin();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $st = db()->prepare('DELETE FROM orders WHERE id = ?');
    $st->execute([$id]);
}
header('Location: index.php?page=orders');
exit;
