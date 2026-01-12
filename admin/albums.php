<?php
/* ====== Config ====== */
$UPLOADS = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
if (!is_dir($UPLOADS)) mkdir($UPLOADS, 0755, true);

/* URL relative (depuis /admin) vers /uploads */
$UURL = '../uploads';

/* ====== Auth basique ====== */
session_start();
// si pas connecté → page de login
if (empty($_SESSION['hkc_admin'])) {
  // on peut renvoyer vers l’album ouvert après login
  $to = 'albums.php' . (!empty($_GET['album']) ? ('?album='.urlencode($_GET['album'])) : '');
  header('Location: login.php?to='.$to);
  exit;
}


/* ====== Utils ====== */
function slugify($s){
  $s = iconv('UTF-8','ASCII//TRANSLIT',$s);
  $s = preg_replace('~[^a-zA-Z0-9]+~','-',$s);
  return strtolower(trim($s,'-')) ?: 'album';
}
function album_dir($slug){ global $UPLOADS; return $UPLOADS . '/' . $slug; }
function thumbs_dir($slug){ return album_dir($slug) . '/_thumbs'; }
function ensure_album($slug){
  @mkdir(album_dir($slug), 0755, true);
  @mkdir(thumbs_dir($slug), 0755, true);
}
function is_img($name){ return preg_match('~\.(jpe?g|png|webp)$~i', $name); }

/* Suppression récursive d’un dossier */
function rrmdir($dir){
  if (!is_dir($dir)) return;
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
  );
  foreach ($it as $f){
    $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
  }
  @rmdir($dir);
}

/* === Resize (GD) → JPEG (fallback si GD absent) === */
function save_jpeg_resized($srcPath, $dstPath, $max){
  $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
  if($ext==='jpg' || $ext==='jpeg')      $im = @imagecreatefromjpeg($srcPath);
  elseif($ext==='png')                   $im = @imagecreatefrompng($srcPath);
  elseif($ext==='webp' && function_exists('imagecreatefromwebp')) $im = @imagecreatefromwebp($srcPath);
  else return false;
  if(!$im) return false;
  $w = imagesx($im); $h = imagesy($im);
  $scale = min(1.0, $max / max($w,$h));
  $nw = max(1, (int)round($w * $scale));
  $nh = max(1, (int)round($h * $scale));
  $out = imagecreatetruecolor($nw,$nh);
  imagecopyresampled($out,$im,0,0,0,0,$nw,$nh,$w,$h);
  $ok = imagejpeg($out, $dstPath, 82);
  imagedestroy($im); imagedestroy($out);
  return $ok;
}

/* ====== Actions ====== */
$album = isset($_GET['album']) ? basename($_GET['album']) : '';
$msg = '';

if(isset($_POST['create'])){
  $slug = slugify($_POST['albumName'] ?? 'Album');
  ensure_album($slug);
  header('Location: ?album='.$slug); exit;
}

if(isset($_POST['rename']) && $album){
  $new = slugify($_POST['newSlug'] ?? $album);
  if($new && $new !== $album && !is_dir(album_dir($new))){
    rename(album_dir($album), album_dir($new));
    $album = $new;
    $msg = "Album renommé en <b>$new</b>";
  } else $msg = "Impossible de renommer (existe déjà ?)";
}

/* Supprimer une image */
if(isset($_POST['deleteImage']) && $album){
  $file = basename($_POST['file']);
  @unlink(album_dir($album).'/'.$file);
  @unlink(thumbs_dir($album).'/'.$file);
  $msg = "Image supprimée.";
}

/* Supprimer un album (liste ou page album) */
if(isset($_POST['deleteAlbum'])){
  $target = basename($_POST['album'] ?? '');
  if ($target){
    rrmdir(album_dir($target));
    if ($album === $target) $album = '';
    $msg = "Album « $target » supprimé.";
  }
}

/* Upload */
if(isset($_POST['upload']) && $album){
  ensure_album($album);
  foreach($_FILES['photos']['error'] as $i => $err){
    if($err !== UPLOAD_ERR_OK) continue;
    $tmp  = $_FILES['photos']['tmp_name'][$i];
    $name = pathinfo($_FILES['photos']['name'][$i], PATHINFO_FILENAME);
    $base = preg_replace('~[^a-zA-Z0-9_-]+~','-', $name);

    $full = album_dir($album)."/{$base}.jpg";
    $thm  = thumbs_dir($album)."/{$base}.jpg";

    $okFull = save_jpeg_resized($tmp, $full, 1600);
    if(!$okFull) @move_uploaded_file($tmp, $full);

    $okThm = save_jpeg_resized($full, $thm, 420);
    if(!$okThm) $okThm = save_jpeg_resized($tmp,  $thm, 420);
    if(!$okThm) @copy($full, $thm);
  }
  $msg = "Upload terminé.";
}

/* ====== Listing ====== */
function list_albums(){
  global $UPLOADS, $UURL;
  $out = array();
  foreach(scandir($UPLOADS) as $d){
    if($d==='.'||$d==='..') continue;
    if(!is_dir("$UPLOADS/$d")) continue;

    $files = array_values(array_filter(scandir("$UPLOADS/$d"), 'is_img'));
    $cover = isset($files[0]) ? $files[0] : '';
    $coverUrl = '';
    if ($cover){
      $thumbPath = "$UPLOADS/$d/_thumbs/$cover";
      $coverUrl  = file_exists($thumbPath) ? "$UURL/$d/_thumbs/$cover" : "$UURL/$d/$cover";
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
function list_images($slug){
  global $UPLOADS, $UURL;
  $dir = album_dir($slug);
  if(!is_dir($dir)) return array();
  $files = array_values(array_filter(scandir($dir), 'is_img'));
  $out = array();
  foreach($files as $f){
    $thumbPath = "$UPLOADS/$slug/_thumbs/$f";
    $thumbUrl  = file_exists($thumbPath) ? "$UURL/$slug/_thumbs/$f" : "$UURL/$slug/$f";
    $out[] = array(
      'file'=>"$UURL/$slug/$f",
      'thumb'=>$thumbUrl,
      'name'=>$f
    );
  }
  return $out;
}

$albums = list_albums();
$images = $album ? list_images($album) : array();
?>
<!doctype html><meta charset="utf-8">
<title>Admin Albums</title>
<style>
body{font-family:system-ui;margin:20px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.card{border:1px solid #ddd;border-radius:10px;padding:10px;background:#fff}
.thumb{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:8px;border:1px solid #eee}
.row{display:flex;gap:8px;flex-wrap:wrap;margin:8px 0}
button,input[type=submit]{padding:6px 10px;border-radius:8px;border:1px solid #ccc;cursor:pointer;background:#f6f7f9}
small{color:#666}
.notice{background:#fff3cd;border:1px solid #ffe69c;padding:8px 10px;border-radius:8px;margin:10px 0}
h1,h2{margin:.2rem 0 .6rem}
form.inline{display:inline}
</style>

<h1>Albums</h1>

<?php if($msg): ?><div class="notice"><?= $msg ?></div><?php endif; ?>

<form method="post" class="row">
  <input name="albumName" placeholder="Nom de l’album (ex: Championnat 2025)" required>
  <input type="submit" name="create" value="Créer l’album">
</form>

<div class="grid">
<?php foreach($albums as $a): ?>
  <div class="card">
    <?php if($a['cover']): ?><img class="thumb" src="<?=htmlspecialchars($a['cover'])?>" alt=""><?php endif; ?>
    <h3><?=htmlspecialchars($a['title'])?></h3>
    <small><?=htmlspecialchars($a['slug'])?> — <?=$a['count']?> photo(s)</small><br>
    <a href="?album=<?=urlencode($a['slug'])?>"><button>Ouvrir</button></a>
    <form method="post" class="inline" onsubmit="return confirm('Supprimer l’album « <?=htmlspecialchars($a['title'])?> » ?');">
      <input type="hidden" name="album" value="<?=htmlspecialchars($a['slug'])?>">
      <input type="submit" name="deleteAlbum" value="Supprimer">
    </form>
  </div>
<?php endforeach; ?>
</div>

<?php if($album): ?>
<hr>
<h2>Album : <?=htmlspecialchars(ucwords(str_replace('-',' ',$album)))?> <small>(<?=htmlspecialchars($album)?>)</small></h2>

<form method="post" class="row">
  <input type="hidden" name="album" value="<?=htmlspecialchars($album)?>">
  <input name="newSlug" placeholder="Nouveau slug (ex: championnat-2025)" required>
  <input type="submit" name="rename" value="Renommer">
</form>

<form method="post" enctype="multipart/form-data" class="row">
  <input type="hidden" name="album" value="<?=htmlspecialchars($album)?>">
  <input type="file" name="photos[]" multiple accept="image/*">
  <input type="submit" name="upload" value="Ajouter des photos">
</form>

<form method="post" onsubmit="return confirm('Supprimer TOUT l’album (photos incluses) ?');" class="row">
  <input type="hidden" name="album" value="<?=htmlspecialchars($album)?>">
  <input type="submit" name="deleteAlbum" value="🗑 Supprimer l’album" style="background:#fee2e2;border-color:#fca5a5">
</form>

<div class="grid" id="images">
<?php foreach($images as $im): ?>
  <div class="card">
    <img class="thumb" src="<?=htmlspecialchars($im['thumb'])?>" alt="">
    <div class="row">
      <a href="<?=htmlspecialchars($im['file'])?>" target="_blank">Voir</a>
      <form method="post" onsubmit="return confirm('Supprimer cette image ?');">
        <input type="hidden" name="album" value="<?=htmlspecialchars($album)?>">
        <input type="hidden" name="file" value="<?=htmlspecialchars($im['name'])?>">
        <input type="submit" name="deleteImage" value="Supprimer">
      </form>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>