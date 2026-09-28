<?php
require_once __DIR__ . '/includes/db.php';
try {
    $video = $pdo->query("SELECT id, title, slug FROM videos WHERE status = 'published' ORDER BY RAND() LIMIT 1")->fetch();
} catch (Exception $e) {
    $video = false;
}
if ($video) {
    header('Location: ' . video_url($video));
} else {
    header('Location: ' . url(''));
}
exit;
