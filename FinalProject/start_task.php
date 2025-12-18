<?php
require 'db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id=?");
$stmt->execute([$id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($task && $task['status'] === 'pending') {
    $start = date('Y-m-d H:i:s');
    $duration = intval($task['duration_minutes']);
    $end = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));
    $stmt = $pdo->prepare("UPDATE tasks SET status='running', start_time=?, end_time=? WHERE id=?");
    $stmt->execute([$start, $end, $id]);

}

header("Location: index.php?uid=" . $task['uid']);
exit;
