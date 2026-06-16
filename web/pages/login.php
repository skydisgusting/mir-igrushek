<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $st = db()->prepare(
        'SELECT a.id, a.full_name, a.login, a.password, r.name AS role
         FROM accounts a JOIN roles r ON r.id = a.role_id
         WHERE a.login = ?'
    );
    $st->execute([$login]);
    $u = $st->fetch();
    if ($u && hash_equals($u['password'], $pass)) {
        unset($u['password']);
        $_SESSION['user'] = $u;
        header('Location: index.php');
        exit;
    }
    $error = 'Неверный логин или пароль.';
}

layout_header('Вход в систему');
?>
<div class="fbox" style="max-width:360px;">
  <?php if ($error): ?><div class="notice"><?= e($error) ?></div><?php endif; ?>
  <p style="font-size:12px;color:#8a7f72;margin-bottom:14px;">Введите e-mail и пароль для входа в систему.</p>
  <form method="post">
    <div class="frow">
      <label>E-mail (логин)</label>
      <input type="text" name="login" required autofocus value="<?= e($_POST['login'] ?? '') ?>">
    </div>
    <div class="frow">
      <label>Пароль</label>
      <input type="password" name="password" required>
    </div>
    <div class="factions">
      <button class="btn ok" type="submit">Войти</button>
      <a class="btn" href="index.php?page=catalog">Войти как гость</a>
    </div>
  </form>
  <p style="margin-top:12px;font-size:12px;">
    Нет аккаунта? <a href="index.php?page=register" style="color:#8a7f72;">Зарегистрироваться</a>
  </p>
</div>
<?php layout_footer();
