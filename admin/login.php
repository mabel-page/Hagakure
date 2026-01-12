<?php
session_start();

// ⚙️ Identifiants (mets les tiens)
$ADMIN_USER = 'admin';
$ADMIN_PASS = 'changeme';

if (!empty($_POST['user']) && !empty($_POST['pass'])) {
  if ($_POST['user'] === $ADMIN_USER && $_POST['pass'] === $ADMIN_PASS) {
    $_SESSION['hkc_admin'] = $_POST['user'];
    // redirige vers l’admin (ou vers l’album demandé)
    $to = !empty($_GET['to']) ? $_GET['to'] : 'albums.php';
    header('Location: ' . $to);
    exit;
  }
  $error = "Identifiants invalides.";
}

if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: login.php');
  exit;
}
?>
<!doctype html><meta charset="utf-8">
<title>Connexion — HKC Admin</title>
<style>
  body{font-family:system-ui;display:grid;place-items:center;height:100vh;background:#f6f7f9;margin:0}
  form{background:#fff;padding:24px 22px;border:1px solid #e6e8eb;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.06);min-width:300px}
  h1{margin:.2rem 0 1rem;font-size:1.2rem}
  .row{display:grid;gap:8px;margin:10px 0}
  input{padding:10px 12px;border:1px solid #d9dde2;border-radius:10px;font:inherit}
  button{padding:10px 12px;border-radius:10px;border:1px solid #b45309;background:#b45309;color:#fff;font-weight:600;cursor:pointer}
  .err{background:#fff1f2;border:1px solid #fecaca;color:#991b1b;padding:8px 10px;border-radius:8px;margin:0 0 10px}
</style>

<form method="post" action="">
  <h1>HKC — Connexion</h1>
  <?php if(!empty($error)): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <div class="row">
    <label>Utilisateur
      <input name="user" autocomplete="username" required>
    </label>
    <label>Mot de passe
      <input name="pass" type="password" autocomplete="current-password" required>
    </label>
  </div>
  <button type="submit">Se connecter</button>
</form>