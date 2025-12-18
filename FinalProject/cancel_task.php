<?php
require 'db.php';

$id = $_GET['id'] ?? '';
$uid = $_GET['uid'] ?? '';

if ($id) {
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'canceled', end_time = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: index.php?uid=" . urlencode($uid));
exit;
