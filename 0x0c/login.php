<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = $_SESSION['admin_login_error'] ?? '';
unset($_SESSION['admin_login_error']);
$token = csrfToken();
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Giris</title>
  <link rel="stylesheet" href="../assets/app.css">
</head>
<body class="admin-dark">
  <div class="container" style="max-width: 460px;">
    <div class="card">
      <h2>Admin Panel Giris</h2>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= esc($error) ?></div>
      <?php endif; ?>

      <form method="post" action="authenticate.php" class="form-grid">
        <input type="hidden" name="csrf" value="<?= esc($token) ?>">
        <div class="field">
          <label for="username">Kullanici Adi</label>
          <input id="username" name="username" required>
        </div>
        <div class="field">
          <label for="password">Sifre</label>
          <input id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-primary" type="submit">Giris Yap</button>
      </form>
    </div>
  </div>
</body>
</html>

