<?php
require_admin();

$sku    = $_GET['sku'] ?? '';
$isEdit = $sku !== '';
$errors = [];

$cats  = db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$sups  = db()->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll();
$brnds = db()->query('SELECT id,name FROM brands ORDER BY name')->fetchAll();
$units = db()->query('SELECT id,name FROM units ORDER BY name')->fetchAll();

if ($isEdit) {
    $st = db()->prepare('SELECT * FROM goods WHERE sku = ?');
    $st->execute([$sku]);
    $p = $st->fetch();
    if (!$p) { exit('Товар не найден.'); }
} else {
    $p = ['sku'=>'', 'name'=>'', 'unit_id'=>$units[0]['id'] ?? 1, 'price'=>'',
          'supplier_id'=>$sups[0]['id'] ?? 1, 'brand_id'=>$brnds[0]['id'] ?? 1,
          'category_id'=>$cats[0]['id'] ?? 1, 'discount'=>0, 'stock'=>0,
          'descr'=>'', 'photo'=>null];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p['name']        = trim($_POST['name'] ?? '');
    $p['unit_id']     = (int)($_POST['unit_id'] ?? 0);
    $p['price']       = $_POST['price'] ?? '';
    $p['supplier_id'] = (int)($_POST['supplier_id'] ?? 0);
    $p['brand_id']    = (int)($_POST['brand_id'] ?? 0);
    $p['category_id'] = (int)($_POST['category_id'] ?? 0);
    $p['discount']    = (int)($_POST['discount'] ?? 0);
    $p['stock']       = (int)($_POST['stock'] ?? 0);
    $p['descr']       = trim($_POST['descr'] ?? '');

    if ($p['name'] === '')                                   $errors[] = 'Укажите наименование.';
    if (!is_numeric($p['price']) || (float)$p['price'] < 0) $errors[] = 'Цена не может быть отрицательной.';
    if ($p['stock'] < 0)                                     $errors[] = 'Количество не может быть отрицательным.';
    if ($p['discount'] < 0)                                  $errors[] = 'Скидка не может быть отрицательной.';

    $photoName = $p['photo'];
    if (!empty($_FILES['photo']['tmp_name'])) {
        $info = @getimagesize($_FILES['photo']['tmp_name']);
        if (!$info) {
            $errors[] = 'Файл не является изображением.';
        } elseif ($info[0] < 300 || $info[1] < 200) {
            $errors[] = 'Минимальный размер изображения 300×200 пикселей (загружено '
                        . $info[0] . '×' . $info[1] . ').';
        } else {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            $photoName = 'p_' . bin2hex(random_bytes(5)) . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../images/' . $photoName);
        }
    }

    if (!$errors) {
        if ($isEdit) {
            $st = db()->prepare(
                'UPDATE goods SET name=?,unit_id=?,price=?,supplier_id=?,brand_id=?,
                        category_id=?,discount=?,stock=?,descr=?,photo=? WHERE sku=?');
            $st->execute([$p['name'],$p['unit_id'],$p['price'],$p['supplier_id'],$p['brand_id'],
                          $p['category_id'],$p['discount'],$p['stock'],$p['descr'],$photoName,$sku]);
        } else {
            do {
                $newSku = strtoupper(bin2hex(random_bytes(3)));
                $chk = db()->prepare('SELECT 1 FROM goods WHERE sku=?'); $chk->execute([$newSku]);
            } while ($chk->fetch());
            $st = db()->prepare(
                'INSERT INTO goods (sku,name,unit_id,price,supplier_id,brand_id,category_id,discount,stock,descr,photo)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$newSku,$p['name'],$p['unit_id'],$p['price'],$p['supplier_id'],$p['brand_id'],
                          $p['category_id'],$p['discount'],$p['stock'],$p['descr'],$photoName]);
        }
        header('Location: index.php');
        exit;
    }
}

layout_header($isEdit ? 'Редактирование товара' : 'Новый товар');
?>
<div class="fbox" style="max-width:640px;">
  <?php foreach ($errors as $err): ?><div class="notice"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" enctype="multipart/form-data">
    <div class="frow">
      <label>Наименование товара</label>
      <input type="text" name="name" required value="<?= e($p['name']) ?>">
    </div>
    <div class="frow">
      <label>Описание</label>
      <textarea name="descr" rows="3"><?= e($p['descr']) ?></textarea>
    </div>
    <div class="f2col">
      <div class="frow">
        <label>Категория</label>
        <select name="category_id"><?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $c['id']==$p['category_id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="frow">
        <label>Единица измерения</label>
        <select name="unit_id"><?php foreach ($units as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= $u['id']==$p['unit_id']?'selected':'' ?>><?= e($u['name']) ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="frow">
        <label>Поставщик</label>
        <select name="supplier_id"><?php foreach ($sups as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $s['id']==$p['supplier_id']?'selected':'' ?>><?= e($s['name']) ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="frow">
        <label>Бренд (производитель)</label>
        <select name="brand_id"><?php foreach ($brnds as $b): ?>
          <option value="<?= (int)$b['id'] ?>" <?= $b['id']==$p['brand_id']?'selected':'' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?></select>
      </div>
      <div class="frow">
        <label>Цена, ₽</label>
        <input type="number" step="0.01" min="0" name="price" required value="<?= e((string)$p['price']) ?>">
      </div>
      <div class="frow">
        <label>Кол-во на складе</label>
        <input type="number" min="0" name="stock" value="<?= (int)$p['stock'] ?>">
      </div>
      <div class="frow">
        <label>Скидка, %</label>
        <input type="number" min="0" max="100" name="discount" value="<?= (int)$p['discount'] ?>">
      </div>
      <div class="frow">
        <label>Фото (мин. 300×200 px)</label>
        <input type="file" name="photo" accept="image/*">
      </div>
    </div>
    <?php if ($isEdit && $p['photo']): ?>
      <div style="margin-bottom:10px;">
        <img src="<?= e(photo_src($p['photo'])) ?>" style="max-width:180px;border:1px solid #c9a06a;">
      </div>
    <?php endif; ?>
    <div class="factions">
      <button class="btn ok" type="submit"><?= $isEdit ? 'Сохранить' : 'Добавить товар' ?></button>
      <a class="btn" href="index.php">Отмена</a>
    </div>
  </form>
</div>
<?php layout_footer();
