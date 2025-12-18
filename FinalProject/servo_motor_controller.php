<?php
require 'db.php';

$taskId = $_GET['id'] ?? 0;
if (!$taskId) exit(json_encode(['status'=>'error', 'message'=>'No task ID provided']));

// Fetch task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) exit(json_encode(['status'=>'error', 'message'=>'Task not found']));

// ---------------- ASSIGN POINTS ----------------
// 50 points per minute for testing
$points = $task['duration_minutes'] * 50;

// Mark task as completed
$stmt = $pdo->prepare("
    UPDATE tasks 
    SET status='completed', end_time=NOW(), points_earned=? 
    WHERE id=?
");
$stmt->execute([$points, $taskId]);

// Update user total points
$stmt = $pdo->prepare("
    INSERT INTO users(uid, total_points) 
    VALUES (?, ?) 
    ON DUPLICATE KEY UPDATE total_points = total_points + ?
");
$stmt->execute([$task['uid'], $points, $points]);

// Fetch total points after adding
$stmt = $pdo->prepare("SELECT total_points FROM users WHERE uid=?");
$stmt->execute([$task['uid']]);
$totalPoints = $stmt->fetchColumn();

$servo_triggered = false;

// ---------------- AUTO-DISPENSE ----------------
if ($totalPoints >= 50) {
    // Send command directly to Arduino COM port (Windows)
    $comPort = "COM5:"; // replace with your Arduino COM port
    $fp = @fopen($comPort, "w");
    if ($fp) {
        fwrite($fp, "TREAT\n");
        fclose($fp);
        $servo_triggered = true;
    } else {
        error_log("Cannot open COM port $comPort. Make sure Arduino IDE is closed.");
    }

    // Reset total points after dispensing
    $stmt = $pdo->prepare("UPDATE users SET total_points=0 WHERE uid=?");
    $stmt->execute([$task['uid']]);
}

// ---------------- RETURN JSON ----------------
echo json_encode([
    'status' => 'success',
    'servo_triggered' => $servo_triggered,
    'total_points' => $servo_triggered ? 0 : $totalPoints
]);
