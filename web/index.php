<?php
require_once __DIR__ . '/common.php';

$page = $_GET['page'] ?? (current_user() ? 'catalog' : 'login');
$routes = [
    'login'          => 'pages/login.php',
    'register'       => 'pages/register.php',
    'logout'         => 'pages/logout.php',
    'orders'         => 'pages/orders.php',
    'product_edit'   => 'pages/product_edit.php',
    'product_delete' => 'pages/product_delete.php',
    'order_edit'     => 'pages/order_edit.php',
    'order_delete'   => 'pages/order_delete.php',
];
if (isset($routes[$page])) { require __DIR__ . '/' . $routes[$page]; return; }

$manage = can_manage();

$where  = [];
$args   = [];
$search = trim($_GET['q'] ?? '');
$cat    = $_GET['cat'] ?? '';
$sort   = $_GET['sort'] ?? '';

if ($manage) {
    if ($search !== '') { $where[] = 'g.name LIKE ?'; $args[] = '%' . $search . '%'; }
    if ($cat !== '' && ctype_digit((string)$cat)) { $where[] = 'g.category_id = ?'; $args[] = (int)$cat; }
}
$order = 'g.name';
if ($manage && $sort === 'price_asc')  $order = 'g.price ASC';
if ($manage && $sort === 'price_desc') $order = 'g.price DESC';

$sql = "SELECT g.*, c.name cat_name, s.name sup_name, b.name brand_name, u.name unit_name
        FROM goods g
        JOIN categories c ON c.id = g.category_id
        JOIN suppliers  s ON s.id = g.supplier_id
        JOIN brands     b ON b.id = g.brand_id
        JOIN units      u ON u.id = g.unit_id";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= " ORDER BY $order";
$st = db()->prepare($sql);
$st->execute($args);
$products = $st->fetchAll();

$cats = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

layout_header('Каталог товаров');

if ($manage): ?>
  <form class="fbar" method="get">
    <label>Поиск
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="название товара…" style="width:180px;">
    </label>
    <label>Категория
      <select name="cat">
        <option value="">— все —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ((string)$cat === (string)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Сортировка
      <select name="sort">
        <option value="">— без сортировки —</option>
        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Цена ↑</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена ↓</option>
      </select>
    </label>
    <div style="display:flex;gap:6px;align-items:flex-end;">
      <button class="btn ok" type="submit">Найти</button>
      <a class="btn" href="index.php?page=catalog">Сбросить</a>
    </div>
  </form>
<?php endif; ?>

<table class="tbl">
  <tr>
    <th style="width:80px;">Фото</th>
    <th>Наименование</th>
    <th>Категория</th>
    <th style="width:130px;">Цена</th>
    <th style="width:70px;">Склад</th>
    <?php if (is_admin()): ?><th style="width:110px;">Действия</th><?php endif; ?>
  </tr>
  <?php if (!$products): ?>
    <tr><td colspan="<?= is_admin() ? 6 : 5 ?>" style="text-align:center;padding:20px;color:#8a7f72;">Товары не найдены.</td></tr>
  <?php endif; ?>
  <?php foreach ($products as $g):
      $discount = (int)$g['discount'];
      $price    = (float)$g['price'];
      $final    = round($price * (1 - $discount / 100), 2);
      $outStock = (int)$g['stock'] === 0;
      $rowClass = $outStock ? 'oos' : ($discount > 17 ? 'sale' : '');
  ?>
  <tr class="<?= $rowClass ?>">
    <td style="text-align:center;">
      <img src="<?= e(photo_src($g['photo'])) ?>" alt="" style="width:72px;height:56px;object-fit:contain;background:#f5f0e8;display:block;">
    </td>
    <td>
      <strong><?= e($g['name']) ?></strong><br>
      <span style="font-size:11px;color:#8a7f72;"><?= e($g['descr']) ?></span><br>
      <span style="font-size:11px;">Арт.: <b><?= e($g['sku']) ?></b> &bull; Пост.: <?= e($g['sup_name']) ?> &bull; Бренд: <?= e($g['brand_name']) ?></span>
    </td>
    <td style="white-space:nowrap;"><?= e($g['cat_name']) ?></td>
    <td>
      <?php if ($discount > 0): ?>
        <span class="p-old"><?= number_format($price, 2, ',', ' ') ?> ₽</span>
        <span class="p-new"><?= number_format($final, 2, ',', ' ') ?> ₽</span>
        <span style="font-size:11px;color:#8a7f72;">–<?= $discount ?>%</span>
      <?php else: ?>
        <span class="p-new"><?= number_format($price, 2, ',', ' ') ?> ₽</span>
      <?php endif; ?>
    </td>
    <td style="text-align:center;"><?= (int)$g['stock'] ?> <?= e($g['unit_name']) ?></td>
    <?php if (is_admin()): ?>
    <td style="white-space:nowrap;">
      <a class="btn" href="index.php?page=product_edit&sku=<?= e($g['sku']) ?>">Изм.</a>
      <a class="btn del" href="index.php?page=product_delete&sku=<?= e($g['sku']) ?>"
         onclick="return confirm('Удалить «<?= e(addslashes($g['name'])) ?>»?');">Удал.</a>
    </td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
</table>

<?php layout_footer();
