<?php
/* ============================================================
   phantomwords/api.php — backend en PHP + SQLite
   No requiere configuración: crea el fichero de datos solo.
   Requisitos: PHP 7+ con extensión pdo_sqlite (estándar).
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$DB_FILE = __DIR__ . '/phantomwords.sqlite';

try {
  $db = new PDO('sqlite:' . $DB_FILE);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->exec('CREATE TABLE IF NOT EXISTS words (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    w  TEXT NOT NULL,
    t  INTEGER NOT NULL
  )');
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error' => 'db']);
  exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ---------- enviar palabras (público, anónimo) ---------- */
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $body  = json_decode(file_get_contents('php://input'), true);
  $words = $body['words'] ?? [];
  if (!is_array($words)) $words = [];
  $words = array_slice($words, 0, 50);            // máx. 50 palabras por envío
  $now = round(microtime(true) * 1000);
  $ins = $db->prepare('INSERT INTO words (w, t) VALUES (?, ?)');
  $count = 0;
  $db->beginTransaction();
  foreach ($words as $w) {
    if (!is_string($w)) continue;
    $w = trim($w);
    if (function_exists('mb_strtolower')) {
      $w = mb_strtolower($w, 'UTF-8');
      $w = mb_substr($w, 0, 40, 'UTF-8');
    } else {
      // la página del público ya envía en minúsculas; solo limitamos longitud
      $w = substr($w, 0, 60);
    }
    if ($w === '') continue;
    $ins->execute([$w, $now]);
    $count++;
  }
  $db->commit();
  echo json_encode(['ok' => true, 'sent' => $count]);
  exit;
}

/* ---------- leer novedades (página del artista) ---------- */
if ($action === 'poll') {
  $since = (int)($_GET['since'] ?? 0);
  $q = $db->prepare('SELECT id, w, t FROM words WHERE id > ? ORDER BY id ASC LIMIT 500');
  $q->execute([$since]);
  $rows = $q->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['rows' => $rows]);
  exit;
}

/* ---------- reset (borra todo) ---------- */
if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $db->exec('DELETE FROM words');
  $db->exec('DELETE FROM sqlite_sequence WHERE name="words"');
  echo json_encode(['ok' => true]);
  exit;
}

http_response_code(400);
echo json_encode(['error' => 'bad request']);
