<?php
$errors = [];
$fio    = trim($_POST['full_name'] ?? '');
$login  = trim($_POST['login'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass  = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');

    if ($fio === '')                                   $errors[] = 'Укажите ФИО.';
    if (!filter_var($login, FILTER_VALIDATE_EMAIL))    $errors[] = 'Укажите корректный e-mail.';
    if (strlen($pass) < 6)                             $errors[] = 'Пароль — минимум 6 символов.';
    if ($pass !== $pass2)                              $errors[] = 'Пароли не совпадают.';

    if (!$errors) {
        $exists = db()->prepare('SELECT 1 FROM accounts WHERE login = ?');
        $exists->execute([$login]);
        if ($exists->fetch()) $errors[] = 'Пользователь с таким e-mail уже существует.';
    }

    if (!$errors) {
        $role = db()->prepare('SELECT id FROM roles WHERE name = ?');
        $role->execute([ROLE_CLIENT]);
        $roleId = (int)$role->fetch()['id'];

        $ins = db()->prepare('INSERT INTO accounts (role_id, full_name, login, password) VALUES (?,?,?,?)');
        $ins->execute([$roleId, $fio, $login, $pass]);

        $_SESSION['user'] = [
            'id'        => (int)db()->lastInsertId(),
            'full_name' => $fio,
            'login'     => $login,
            'role'      => ROLE_CLIENT,
        ];
        header('Location: index.php');
        exit;
    }
}

layout_header('Регистрация');
?>
<div class="fbox" style="max-width:360px;">
  <?php foreach ($errors as $err): ?><div class="notice"><?= e($err) ?></div><?php endforeach; ?>
  <p style="font-size:12px;color:#8a7f72;margin-bottom:14px;">Регистрация создаёт учётную запись <b>авторизированного клиента</b>.</p>
  <form method="post">
    <div class="frow">
      <label>ФИО</label>
      <input type="text" name="full_name" required value="<?= e($fio) ?>">
    </div>
    <div class="frow">
      <label>E-mail (логин)</label>
      <input type="email" name="login" required value="<?= e($login) ?>">
    </div>
    <div class="f2col">
      <div class="frow">
        <label>Пароль</label>
        <input type="password" name="password" required minlength="6">
      </div>
      <div class="frow">
        <label>Повтор пароля</label>
        <input type="password" name="password2" required minlength="6">
      </div>
    </div>
    <div class="factions">
      <button class="btn ok" type="submit">Зарегистрироваться</button>
    </div>
  </form>
  <p style="margin-top:12px;font-size:12px;">
    Уже есть аккаунт? <a href="index.php?page=login" style="color:#8a7f72;">Войти</a>
  </p>
</div>
<?php layout_footer();
