<?php
require "db.php";

$uid = $_POST['uid'] ?? '';
$name = $_POST['task_name'] ?? $_POST['name'] ?? '';
$duration = intval($_POST['duration'] ?? 0);

$stmt = $pdo->prepare("INSERT INTO tasks (uid, name, duration_minutes, status) VALUES (?, ?, ?, 'pending')");
$stmt->execute([$uid, $name, $duration]);

echo "Task created! <a href='index.php?uid=" . urlencode($uid) . "'>Back</a>";
