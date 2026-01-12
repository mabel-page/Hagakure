<?php
// (à retirer après debug)
// ini_set('display_errors',1); error_reporting(E_ALL);

// --- Config basique ---
$UPLOADS = __DIR__ . '/uploads';   // dossiers d’albums
$UURL    = 'uploads';              // URL relative depuis la racine du site

function is_img($n){ return preg_match('~\.(jpe?g|png|webp)$~i', $n); }

function list_albums($UPLOADS,$UURL){
  $out = array();
  foreach(scandir($UPLOADS) as $d){
    if($d==='.'||$d==='..') continue;
    if(!is_dir("$UPLOADS/$d")) continue;
    $files = array_values(array_filter(scandir("$UPLOADS/$d"), 'is_img'));
    $cover = isset($files[0]) ? $files[0] : '';
    if ($cover){
      $thumbPath = "$UPLOADS/$d/_thumbs/$cover";
      $coverUrl  = file_exists($thumbPath) ? "$UURL/$d/_thumbs/$cover" : "$UURL/$d/$cover";
    } else {
      $coverUrl = '';
    }
    $out[] = array(
      'slug'=>$d,
      'title'=>ucwords(str_replace('-',' ',$d)),
      'count'=>count($files),
      'cover'=>$coverUrl
    );
  }
  usort($out, function($a,$b){ return strcmp($a['title'],$b['title']); });
  return $out;
}

function list_images($slug,$UPLOADS,$UURL){
  $dir = "$UPLOADS/$slug"; if(!is_dir($dir)) return array();
  $files = array_values(array_filter(scandir($dir), 'is_img'));
  $out = array();
  foreach($files as $f){
    $thumbPath = "$UPLOADS/$slug/_thumbs/$f";
    $thumbUrl  = file_exists($thumbPath) ? "$UURL/$slug/_thumbs/$f" : "$UURL/$slug/$f";
    $out[] = array('file'=>"$UURL/$slug/$f",'thumb'=>$thumbUrl,'name'=>$f);
  }
  return $out;
}

$slug   = isset($_GET['album']) ? basename($_GET['album']) : '';
$albums = $slug ? array() : (is_dir($UPLOADS) ? list_albums($UPLOADS,$UURL) : array());
$images = $slug ? list_images($slug,$UPLOADS,$UURL) : array();
?><!DOCTYPE html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Galerie photos<?= $slug ? ' – '.htmlspecialchars(ucwords(str_replace('-',' ',$slug))) : '' ?></title>
<link rel="icon" href="assets/img/logo.png">
<link rel="stylesheet" href="assets/css/style.css"/>
</head>
<body>

<div data-include="partials/header.html"></div>

<main id="main">
  <span class="eyebrow">Galerie</span>
  <?php if(!$slug): ?>
    <h1>Galerie</h1>
    <p class="small">Albums récents. Cliquez pour ouvrir un album.</p>

    <div class="cards">
      <?php foreach($albums as $a): ?>
        <article class="card">
          <?php if($a['cover']): ?>
            <div class="card__media">
              <img src="<?=htmlspecialchars($a['cover'])?>" alt="" loading="lazy" decoding="async">
            </div>
          <?php endif; ?>
          <h3><?=htmlspecialchars($a['title'])?></h3>
          <p class="small"><?= (int)$a['count'] ?> photo(s)</p>
          <p><a class="btn btn--slim" href="galerie.php?album=<?=urlencode($a['slug'])?>">Ouvrir</a></p>
        </article>
      <?php endforeach; ?>
      <?php if(empty($albums)): ?>
        <article class="card"><p>Aucun album pour l’instant.</p></article>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <p><a href="galerie.php" class="small">← Retour aux albums</a></p>
    <h1><?=htmlspecialchars(ucwords(str_replace('-',' ',$slug)))?></h1>

    <div class="gallery" id="albumGallery">
      <?php foreach($images as $k=>$im): ?>
        <img class="js-lb"
             src="<?=htmlspecialchars($im['thumb'])?>"
             data-full="<?=htmlspecialchars($im['file'])?>"
             alt="Photo <?= $k+1 ?>">
      <?php endforeach; ?>
      <?php if(empty($images)): ?>
        <p class="small">Aucune photo dans cet album.</p>
      <?php endif; ?>
    </div>

    <!-- Lightbox -->
    <div class="lb-backdrop" id="lb" aria-hidden="true">
      <img class="lb-img" id="lbImg" alt="">
      <button class="lb-close" id="lbClose" aria-label="Fermer">✕</button>
      <button class="lb-prev"  id="lbPrev"  aria-label="Précédent">◀</button>
      <button class="lb-next"  id="lbNext"  aria-label="Suivant">▶</button>
    </div>

    <script>
    (function(){
      var thumbs = Array.prototype.slice.call(document.querySelectorAll('.js-lb'));
      if (!thumbs.length) return;

      var lb   = document.getElementById('lb');
      var img  = document.getElementById('lbImg');
      var prev = document.getElementById('lbPrev');
      var next = document.getElementById('lbNext');
      var close= document.getElementById('lbClose');
      var i = 0;

      function show(idx){
        i = (idx + thumbs.length) % thumbs.length;
        img.src = thumbs[i].getAttribute('data-full');
        lb.classList.add('open');
      }
      function hide(){
        lb.classList.remove('open');
        img.removeAttribute('src');
      }

      thumbs.forEach(function(t, idx){
        t.addEventListener('click', function(e){
          e.preventDefault();
          e.stopPropagation();
          show(idx);
        }, true);
      });

      close.addEventListener('click', function(e){ e.stopPropagation(); hide(); });
      prev .addEventListener('click', function(e){ e.stopPropagation(); show(i - 1); });
      next .addEventListener('click', function(e){ e.stopPropagation(); show(i + 1); });
      lb.addEventListener('click', function(e){ if (e.target === lb) hide(); });

      document.addEventListener('keydown', function(e){
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') hide();
        if (e.key === 'ArrowLeft')  show(i - 1);
        if (e.key === 'ArrowRight') show(i + 1);
      });
    })();
    </script>
  <?php endif; ?>
</main>

<div data-include="partials/footer.html"></div>
<script type="module" src="assets/js/includes.js"></script>
</body></html>