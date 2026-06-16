<?php
require_manage();

$rows = db()->query(
    "SELECT o.id, o.placed_on, o.deliver_on, o.claim_code,
            pp.address   AS spot_addr,
            a.full_name  AS client,
            st.name      AS status,
            GROUP_CONCAT(CONCAT(ol.sku, ' ×', ol.qty) ORDER BY ol.sku SEPARATOR ', ') AS items
     FROM orders o
     LEFT JOIN pickup_pts pp ON pp.id = o.spot_id
     LEFT JOIN accounts   a  ON a.id  = o.user_id
     JOIN      statuses   st ON st.id = o.status_id
     LEFT JOIN order_lines ol ON ol.order_id = o.id
     GROUP BY o.id
     ORDER BY o.id"
)->fetchAll();

layout_header('Список заказов');
?>
<?php if (is_admin()): ?>
  <p style="margin-bottom:12px;"><a class="btn ok" href="index.php?page=order_edit">+ Новый заказ</a></p>
<?php endif; ?>
<table class="tbl">
  <tr>
    <th>№</th>
    <th>Статус</th>
    <th>Клиент</th>
    <th>Состав</th>
    <th>Размещён</th>
    <th>Доставка</th>
    <th>Пункт выдачи</th>
    <th>Код</th>
    <?php if (is_admin()): ?><th>Действия</th><?php endif; ?>
  </tr>
  <?php if (!$rows): ?>
    <tr><td colspan="<?= is_admin() ? 9 : 8 ?>" style="text-align:center;padding:16px;color:#8a7f72;">Заказов нет.</td></tr>
  <?php endif; ?>
  <?php foreach ($rows as $o): ?>
  <tr>
    <td><?= (int)$o['id'] ?></td>
    <td><?= e($o['status']) ?></td>
    <td><?= e($o['client']) ?></td>
    <td style="font-size:12px;"><?= e($o['items']) ?></td>
    <td><?= e($o['placed_on'] ?: '—') ?></td>
    <td><?= e($o['deliver_on'] ?: '—') ?></td>
    <td style="font-size:12px;"><?= e($o['spot_addr']) ?></td>
    <td><?= e($o['claim_code']) ?></td>
    <?php if (is_admin()): ?>
    <td style="white-space:nowrap;">
      <a class="btn" href="index.php?page=order_edit&id=<?= (int)$o['id'] ?>">Изм.</a>
      <a class="btn del" href="index.php?page=order_delete&id=<?= (int)$o['id'] ?>"
         onclick="return confirm('Удалить заказ №<?= (int)$o['id'] ?>?');">Удал.</a>
    </td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
</table>
<?php layout_footer();
