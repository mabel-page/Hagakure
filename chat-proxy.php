<?php
$ORIGIN = 'https://zahramzoudi.app.n8n.cloud';
$PAGE   = $ORIGIN . '/webhook/adb9ec7b-add9-4aae-8d91-779b1e809c51/chat';

$ch = curl_init($PAGE);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_USERAGENT      => 'HKC-Chat-Proxy',
  CURLOPT_CONNECTTIMEOUT => 6,
  CURLOPT_TIMEOUT        => 12,
  // si l’hébergeur a un souci de chaines SSL
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_SSL_VERIFYHOST => 0,
]);
$html  = curl_exec($ch);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'text/html; charset=utf-8';
$err   = curl_error($ch);
curl_close($ch);

if ($html === false) {
  http_response_code(502);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Chat indisponible pour le moment.\n$err";
  exit;
}

/* --- Réécritures pour que les ressources/API pointent vers n8n --- */
$html = preg_replace('/<head([^>]*)>/i', '<head$1><base href="'.$ORIGIN.'/">', $html, 1);
$html = str_replace(
  ['src="/','href="/','action="/','fetch("/','xhr.open("GET","/','xhr.open("POST","/'],
  ['src="'.$ORIGIN.'/','href="'.$ORIGIN.'/','action="'.$ORIGIN.'/','fetch("'.$ORIGIN.'/','xhr.open("GET","'.$ORIGIN.'/','xhr.open("POST","'.$ORIGIN.'/'],
  $html
);

/* --- Autorise l’embed côté TON domaine --- */
header('Content-Type: '.$ctype);
header('X-Frame-Options: ALLOWALL');
header_remove('Content-Security-Policy');

$customCss = <<<CSS
<style>
  /* ajuste les sélecteurs selon l’UI réelle du chat */
  .chat-layout .chat-header { display: none !important; }
</style>
CSS;

$html = str_replace('</head>', $customCss.'</head>', $html);

echo $html;