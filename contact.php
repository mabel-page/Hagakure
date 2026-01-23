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

    /* ===== Mise en page contact ===== */
    .contact-grid{
      display:grid;
      grid-template-columns: minmax(340px,1fr) minmax(320px,0.95fr); /* gauche = form, droite = carte */
      gap:24px;
      align-items:start;
    }
    .map-embed{
      position:relative;
      padding-bottom:62%; /* ratio visuel de la carte */
      height:0;
      overflow:hidden;
      border-radius:12px;
      border:1px solid var(--border, #e5e7eb);
      box-shadow: 0 4px 16px rgba(0,0,0,.06);
      background:#fff;
    }
    .map-embed iframe{ position:absolute; inset:0; width:100%; height:100%; border:0; }
    .contact-points{
      list-style:none; margin:12px 0 0; padding:0 6px;
      display:grid; gap:8px; color:var(--muted,#4b5563);
    }
    @media (max-width: 900px){
      .contact-grid{ grid-template-columns:1fr; }
    }
  </style>
</head>
<body>

<div data-include="partials/header.html"></div>

<main id="main">
  <span class="eyebrow">Nous écrire</span>
  <h1>Contact</h1>

  <section class="contact-grid">
    <!-- Colonne GAUCHE : formulaire -->
    <section>
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
    </section>

    <!-- Colonne DROITE : carte puis infos -->
    <aside>
      <div class="map-embed">
        <!-- Remplace l’URL par l’embed Google Maps de ton club -->
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2528.239914470572!2d4.383619276235608!3d50.67837157092729!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c3d2004e5708b1%3A0xd7be4c0dd702355c!2sComplexe%20Sportif%20Gaston%20Reiff!5e0!3m2!1sfr!2sbe!4v1769189797480!5m2!1sfr!2sbe" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>"
      </div>

      <ul class="contact-points">
        <li>📍 Rue Ernest Laurent 215, 1420 Braine-l’Alleud</li>
        <li>Gare Braine-l’Alleud → ~12 min à pied</li>
        <li>tél. : 0496 77 98 74</li>
      </ul>
    </aside>
  </section>
</main>

<div data-include="partials/footer.html"></div>
<script type="module" src="assets/js/includes.js"></script>
</body></html>