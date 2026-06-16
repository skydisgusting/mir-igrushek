<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

session_start();

const ROLE_ADMIN   = 'Администратор';
const ROLE_MANAGER = 'Менеджер';
const ROLE_CLIENT  = 'Авторизированный клиент';

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function role(): string         { return current_user()['role'] ?? 'Гость'; }
function is_admin(): bool        { return role() === ROLE_ADMIN; }
function is_manager(): bool      { return role() === ROLE_MANAGER; }
function can_manage(): bool      { return is_admin() || is_manager(); }

function require_admin(): void {
    if (!is_admin()) { http_response_code(403); exit('Доступ закрыт.'); }
}
function require_manage(): void {
    if (!can_manage()) { http_response_code(403); exit('Доступ закрыт.'); }
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function photo_src(?string $photo): string {
    if ($photo && is_file(__DIR__ . '/images/' . $photo)) return 'images/' . rawurlencode($photo);
    return 'images/picture.png';
}

function layout_header(string $title): void {
    $u  = current_user();
    $pg = $_GET['page'] ?? 'catalog';
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" href="images/icon.png">
<title><?= e($title) ?> | МирИгрушек</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 13px; color: #2c2723; background: #efe7d8; }

#topbar {
  background: #DEB887;
  border-bottom: 3px solid #c9a06a;
  padding: 7px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
#topbar .brand { display: flex; align-items: center; gap: 9px; font-size: 16px; font-weight: bold; }
#topbar .brand img { height: 30px; }
#topbar .uinfo { font-size: 12px; background: #fff8ee; border: 1px solid #c9a06a; padding: 3px 10px; }

#shell { display: flex; min-height: calc(100vh - 90px); }

#sidenav {
  width: 170px;
  min-width: 170px;
  background: #F5DEB3;
  border-right: 2px solid #DEB887;
  padding: 12px 0 20px;
}
#sidenav a {
  display: block;
  padding: 9px 15px;
  color: #2c2723;
  text-decoration: none;
  font-size: 13px;
  border-bottom: 1px solid #DEB887;
}
#sidenav a:hover { background: #DEB887; }
#sidenav a.active { background: #DEB887; font-weight: bold; }
#sidenav .nav-sep { height: 2px; background: #c9a06a; margin: 6px 0; }
#sidenav .nav-lbl {
  padding: 8px 15px 3px;
  font-size: 10px;
  text-transform: uppercase;
  color: #8a7f72;
  letter-spacing: 0.5px;
}

#pagebody { flex: 1; padding: 18px 22px; background: #fdf8f0; overflow: auto; }

.pg-head {
  font-size: 15px;
  font-weight: bold;
  margin-bottom: 14px;
  padding-bottom: 7px;
  border-bottom: 2px solid #DEB887;
}

.tbl { width: 100%; border-collapse: collapse; background: #fff; }
.tbl th { background: #DEB887; padding: 7px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; border: 1px solid #c9a06a; white-space: nowrap; }
.tbl td { padding: 7px 10px; border: 1px solid #efe7d8; vertical-align: middle; }
.tbl tr:nth-child(even) td { background: #fdf6ec; }
.tbl tr.sale td { background: #FFDEAD !important; }
.tbl tr.oos  td { background: #D6ECFF !important; }

.btn { display: inline-block; padding: 4px 12px; background: #F5DEB3; border: 1px solid #c9a06a; color: #2c2723; text-decoration: none; cursor: pointer; font-size: 12px; font-family: Arial, sans-serif; }
.btn:hover { background: #DEB887; }
.btn.ok { background: #DEB887; border-color: #b8916a; font-weight: bold; }
.btn.ok:hover { background: #c9a06a; }
.btn.del { border-color: #b03020; color: #b03020; }
.btn.del:hover { background: #fdecea; }

.fbox { background: #fff; border: 1px solid #c9a06a; padding: 18px 20px; }
.frow { margin-bottom: 11px; }
.frow label { display: block; font-weight: bold; font-size: 12px; margin-bottom: 3px; }
.frow input, .frow select, .frow textarea {
  width: 100%; padding: 6px 9px; border: 1px solid #DEB887;
  font-size: 13px; font-family: Arial, sans-serif; color: #2c2723; background: #fff;
}
.frow input:focus, .frow select:focus, .frow textarea:focus { outline: 2px solid #DEB887; }
.f2col { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
.factions { margin-top: 14px; display: flex; gap: 8px; }

.fbar { background: #fff; border: 1px solid #c9a06a; padding: 8px 12px; margin-bottom: 14px; display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
.fbar label { font-size: 12px; font-weight: bold; display: flex; flex-direction: column; gap: 3px; }
.fbar input, .fbar select { padding: 5px 8px; border: 1px solid #DEB887; font-size: 12px; font-family: Arial, sans-serif; }

.notice { background: #FFDEAD; border-left: 4px solid #DEB887; padding: 8px 12px; margin-bottom: 12px; font-size: 13px; }

.p-old { color: #e0322f; text-decoration: line-through; font-size: 11px; display: block; }
.p-new { font-weight: bold; font-size: 13px; }
</style>
</head>
<body>
<div id="topbar">
  <div class="brand">
    <img src="images/icon.png" alt="">
    ООО «МирИгрушек»
  </div>
  <div class="uinfo">
    <?php if ($u): ?>
      <?= e($u['full_name']) ?> &nbsp;&mdash;&nbsp; <b><?= e($u['role']) ?></b>
    <?php else: ?>
      Вы не вошли в систему
    <?php endif; ?>
  </div>
</div>
<div id="shell">
  <div id="sidenav">
    <div class="nav-lbl">Каталог</div>
    <a href="index.php?page=catalog" class="<?= in_array($pg, ['catalog', '']) ? 'active' : '' ?>">Список товаров</a>
    <?php if (can_manage()): ?>
      <div class="nav-lbl">Управление</div>
      <a href="index.php?page=orders" class="<?= $pg === 'orders' ? 'active' : '' ?>">Заказы</a>
    <?php endif; ?>
    <?php if (is_admin()): ?>
      <a href="index.php?page=product_edit">Добавить товар</a>
    <?php endif; ?>
    <div class="nav-sep"></div>
    <div class="nav-lbl">Аккаунт</div>
    <?php if ($u): ?>
      <a href="index.php?page=logout">Выйти из системы</a>
    <?php else: ?>
      <a href="index.php?page=login" class="<?= $pg === 'login' ? 'active' : '' ?>">Войти</a>
      <a href="index.php?page=register" class="<?= $pg === 'register' ? 'active' : '' ?>">Регистрация</a>
    <?php endif; ?>
  </div>
  <div id="pagebody">
    <div class="pg-head"><?= e($title) ?></div>
<?php
}

function layout_footer(): void {
    echo '  </div><!-- #pagebody -->
</div><!-- #shell -->
<div style="background:#DEB887;border-top:2px solid #c9a06a;padding:5px 18px;font-size:11px;color:#5a4535;text-align:right;">
  ИС «МирИгрушек» &mdash; ДЭ 09.02.07-2-2026, вариант В4
</div>
</body></html>';
}
