<?php
require __DIR__.'/config.php';
require __DIR__.'/vendor/PHPMailer/src/PHPMailer.php';
require __DIR__.'/vendor/PHPMailer/src/SMTP.php';
require __DIR__.'/vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$sent = false; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name'] ?? '');
  $email = trim($_POST['_replyto'] ?? '');
  $msg   = trim($_POST['message'] ?? '');

  if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $msg) {
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host       = SMTP_HOST;
      $mail->Port       = SMTP_PORT;
      $mail->SMTPSecure = SMTP_SECURE;     // 'tls'
      $mail->SMTPAuth   = true;
      $mail->Username   = SMTP_USER;
      $mail->Password   = SMTP_PASS;

      // From = adresse de TON domaine/compte (Gmail ici)
      $mail->setFrom(SMTP_USER, 'Hagakure KC');
      $mail->addAddress(MAIL_TO);
      $mail->addReplyTo($email, $name);

      $mail->Subject = 'Nouveau message – Hagakure KC';
      $mail->Body    = "De : $name <$email>\n\n$msg\n";
      $mail->AltBody = $mail->Body;

      $mail->send();
      $sent = true;
    } catch (Exception $e) {
      $error = $mail->ErrorInfo ?: 'Erreur inconnue';
    }
  } else {
    $error = 'Champs invalides.';
  }
}
?><!DOCTYPE html><html lang="fr"><head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Contact – Hagakure KC</title>
  <link rel="icon" href="assets/img/logo.png">
  <link rel="stylesheet" href="assets/css/style.css"/>
  <meta name="description" content="Hagakure Karate Club : cours enfants & adultes, Shotokan JKA.">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Hagakure KC – Accueil">
  <meta property="og:description" content="Karaté Shotokan JKA à Braine-l’Alleud. Infos, horaires, galerie.">
  <meta property="og:image" content="assets/img/logo.png">
  <meta name="twitter:card" content="summary">
  <style>
    .notice{margin-top:10px;padding:8px 10px;border-radius:10px}
    .ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .ko{background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d}
  </style>
</head>
<body>

<div data-include="partials/header.html"></div>

<main id="main">
  <span class="eyebrow">Nous écrire</span>
  <h1>Contact</h1>

  <?php if ($sent): ?>
    <p class="notice ok">Merci, votre message a bien été envoyé.</p>
  <?php elseif ($error): ?>
    <p class="notice ko"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form method="post" class="form" novalidate>
    <label>Prénom et nom<br>
      <input type="text" name="name" required autocomplete="name">
    </label>

    <label>Email<br>
      <input type="email" name="_replyto" required autocomplete="email">
    </label>

    <label>Votre message<br>
      <textarea name="message" required></textarea>
    </label>

    <button class="btn" type="submit">Envoyer</button>
  </form>
</main>

<div data-include="partials/footer.html"></div>
<script type="module" src="assets/js/includes.js"></script>
</body></html>