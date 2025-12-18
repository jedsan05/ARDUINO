<?php
require 'db.php';

$id = $_GET['id'] ?? 0;
$uid = $_GET['uid'] ?? '';

if (!$id) {
    echo "Invalid request.";
    exit;
}

// Delete the task regardless of status
$stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->execute([$id]);

// Redirect back to the UID page
if ($uid) {
    header("Location: index.php?uid=" . urlencode($uid));
} else {
    header("Location: index.php");
}
exit;
?>