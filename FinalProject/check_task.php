<?php
require "db.php";

$uid = $_GET['uid'] ?? null;

if (!$uid) exit;

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE uid=? AND status='running'");
$stmt->execute([$uid]);
$task = $stmt->fetch();

$response = ["action" => "none"];

if ($task) {

    $now = time();
    $end = strtotime($task['end_time']);

    if ($now >= $end) {
        $response["action"] = "reward";

        $pdo->prepare("UPDATE tasks SET status='completed' WHERE id=?")
            ->execute([$task['id']]);
    }
}

echo json_encode($response);
