<?php
require_admin();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$errors = [];

$statuses = db()->query('SELECT id,name FROM statuses ORDER BY id')->fetchAll();
$spots    = db()->query('SELECT id,address FROM pickup_pts ORDER BY id')->fetchAll();
$clients  = db()->query(
    "SELECT a.id, a.full_name FROM accounts a JOIN roles r ON r.id=a.role_id
     WHERE r.name = '" . ROLE_CLIENT . "' ORDER BY a.full_name")->fetchAll();
$skuList  = db()->query('SELECT sku FROM goods ORDER BY sku')->fetchAll(PDO::FETCH_COLUMN);

$o = ['placed_on'=>'','deliver_on'=>'','spot_id'=>$spots[0]['id']??null,
      'user_id'=>$clients[0]['id']??null,'claim_code'=>'','status_id'=>$statuses[0]['id']??1];
$itemsText = '';

if ($isEdit) {
    $st = db()->prepare('SELECT * FROM orders WHERE id=?'); $st->execute([$id]);
    $o = $st->fetch();
    if (!$o) { exit('Заказ не найден.'); }
    $it = db()->prepare('SELECT sku, qty FROM order_lines WHERE order_id=?'); $it->execute([$id]);
    $itemsText = implode("\n", array_map(fn($r) => $r['sku'].' '.$r['qty'], $it->fetchAll()));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $o['placed_on']  = $_POST['placed_on']  ?: null;
    $o['deliver_on'] = $_POST['deliver_on'] ?: null;
    $o['spot_id']    = (int)$_POST['spot_id'];
    $o['user_id']    = (int)$_POST['user_id'];
    $o['claim_code'] = trim($_POST['claim_code'] ?? '');
    $o['status_id']  = (int)$_POST['status_id'];
    $itemsText       = trim($_POST['items'] ?? '');

    $items = [];
    foreach (preg_split('/\r?\n/', $itemsText) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = preg_split('/[\s,×x]+/u', $line);
        $art = strtoupper(trim($parts[0] ?? ''));
        $qty = (int)($parts[1] ?? 0);
        if (!in_array($art, $skuList, true)) { $errors[] = "Неизвестный артикул: $art"; continue; }
        if ($qty <= 0) { $errors[] = "Количество должно быть > 0 (артикул $art)"; continue; }
        $items[] = [$art, $qty];
    }
    if (!$items) $errors[] = 'Добавьте хотя бы одну позицию заказа.';

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($isEdit) {
                $st = $pdo->prepare('UPDATE orders SET placed_on=?,deliver_on=?,spot_id=?,user_id=?,claim_code=?,status_id=? WHERE id=?');
                $st->execute([$o['placed_on'],$o['deliver_on'],$o['spot_id'],$o['user_id'],$o['claim_code'],$o['status_id'],$id]);
                $pdo->prepare('DELETE FROM order_lines WHERE order_id=?')->execute([$id]);
                $oid = $id;
            } else {
                $st = $pdo->prepare('INSERT INTO orders (placed_on,deliver_on,spot_id,user_id,claim_code,status_id) VALUES (?,?,?,?,?,?)');
                $st->execute([$o['placed_on'],$o['deliver_on'],$o['spot_id'],$o['user_id'],$o['claim_code'],$o['status_id']]);
                $oid = (int)$pdo->lastInsertId();
            }
            $ins = $pdo->prepare('INSERT INTO order_lines (order_id,sku,qty) VALUES (?,?,?)');
            foreach ($items as [$art,$qty]) $ins->execute([$oid,$art,$qty]);
            $pdo->commit();
        } catch (Throwable $ex) { $pdo->rollBack(); $errors[] = 'Ошибка сохранения: '.$ex->getMessage(); }
        if (!$errors) { header('Location: index.php?page=orders'); exit; }
    }
}

layout_header($isEdit ? "Редактирование заказа №$id" : 'Новый заказ');
?>
<div class="fbox" style="max-width:640px;">
  <?php foreach ($errors as $err): ?><div class="notice"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <div class="f2col">
      <div class="frow">
        <label>Статус заказа</label>
        <select name="status_id"><?php foreach ($statuses as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $s['id']==$o['status_id']?'selected':'' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="frow">
        <label>Клиент</label>
        <select name="user_id"><?php foreach ($clients as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $c['id']==$o['user_id']?'selected':'' ?>><?= e($c['full_name']) ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="frow">
        <label>Дата размещения</label>
        <input type="date" name="placed_on" value="<?= e($o['placed_on']) ?>">
      </div>
      <div class="frow">
        <label>Дата доставки</label>
        <input type="date" name="deliver_on" value="<?= e($o['deliver_on']) ?>">
      </div>
      <div class="frow">
        <label>Код выдачи</label>
        <input type="text" name="claim_code" value="<?= e($o['claim_code']) ?>">
      </div>
    </div>
    <div class="frow">
      <label>Пункт выдачи</label>
      <select name="spot_id"><?php foreach ($spots as $sp): ?>
        <option value="<?= (int)$sp['id'] ?>" <?= $sp['id']==$o['spot_id']?'selected':'' ?>><?= e($sp['address']) ?></option>
      <?php endforeach; ?></select>
    </div>
    <div class="frow">
      <label>Позиции заказа (по строке: <b>АРТИКУЛ КОЛИЧЕСТВО</b>)</label>
      <textarea name="items" rows="5" placeholder="PMEZMH 2&#10;BPV4MM 1"><?= e($itemsText) ?></textarea>
    </div>
    <small style="display:block;margin-bottom:10px;color:#8a7f72;">Доступные артикулы: <?= e(implode(', ', $skuList)) ?></small>
    <div class="factions">
      <button class="btn ok" type="submit"><?= $isEdit ? 'Сохранить' : 'Создать заказ' ?></button>
      <a class="btn" href="index.php?page=orders">Отмена</a>
    </div>
  </form>
</div>
<?php layout_footer();
