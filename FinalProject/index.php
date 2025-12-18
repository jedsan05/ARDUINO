<?php
require 'db.php';

$uid = $_GET['uid'] ?? '';
if (!$uid) {
    echo "<h2>No UID scanned</h2>";
    exit;
}

// ---------------- ADD TASK ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $duration = intval($_POST['duration']);

    if ($name && $duration > 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO tasks (uid, name, duration_minutes, status) VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([$uid, $name, $duration]);
        header("Location: index.php?uid=" . urlencode($uid));
        exit;
    }
}

// ---------------- FETCH TASKS ----------------
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE uid = ? ORDER BY id DESC");
$stmt->execute([$uid]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------- TOTAL POINTS ----------------
$stmt = $pdo->prepare("SELECT total_points FROM users WHERE uid = ?");
$stmt->execute([$uid]);
$totalPoints = $stmt->fetchColumn() ?: 0;

// ---------------- SEPARATE TASKS ----------------
$activeTasks = array_filter($tasks, fn($t) => !in_array($t['status'], ['completed', 'canceled']));
$historyTasks = array_filter($tasks, fn($t) => in_array($t['status'], ['completed', 'canceled']));
?>

<h2>Tasks for UID: <?= htmlspecialchars($uid) ?></h2>

<p><strong>Total Points:</strong> <?= $totalPoints ?></p>

<!-- Add Task -->
<form method="post">
    <input type="text" name="name" placeholder="Task name" required>
    <input type="number" name="duration" id="taskDuration" placeholder="Duration (minutes)" min="1" required>
    <span id="pointsPreview">Points: 0</span>
    <button type="submit">Add Task</button>
</form>

<hr>

<!-- ================= ACTIVE TASKS ================= -->
<h3>🟢 Active Tasks</h3>

<?php if (empty($activeTasks)): ?>
    <p style="color:#666;">No active tasks.</p>
<?php else: ?>
<table border="1">
    <tr>
        <th>Name</th>
        <th>Duration</th>
        <th>Status</th>
        <th>Time Remaining</th>
        <th>Action</th>
    </tr>

    <?php foreach ($activeTasks as $task): ?>
        <tr>
            <td><?= htmlspecialchars($task['name']) ?></td>
            <td><?= $task['duration_minutes'] ?> min</td>
            <td>
                <?php if ($task['status'] === 'running'): ?>
                    <span style="background:#28a745;color:white;padding:4px 8px;border-radius:4px;">ACTIVE</span>
                <?php else: ?>
                    <span style="background:#6c757d;color:white;padding:4px 8px;border-radius:4px;">PENDING</span>
                <?php endif; ?>
            </td>
            <td class="countdown"
                data-end="<?= $task['status'] === 'running' ? date('c', strtotime($task['end_time'])) : '' ?>"
                data-task-id="<?= $task['id'] ?>">--:--:--</td>
            <td>
                <?php if ($task['status'] === 'pending'): ?>
                    <a href="start_task.php?id=<?= $task['id'] ?>&uid=<?= $uid ?>">Start</a> |
                    <a href="cancel_task.php?id=<?= $task['id'] ?>&uid=<?= $uid ?>">Cancel</a>
                <?php elseif ($task['status'] === 'running'): ?>
                    <a href="cancel_task.php?id=<?= $task['id'] ?>&uid=<?= $uid ?>">Cancel</a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<hr>

<!-- ================= TASK HISTORY ================= -->
<h3>✅ Task History</h3>

<?php if (empty($historyTasks)): ?>
    <p style="color:#666;">No completed or canceled tasks.</p>
<?php else: ?>
<table border="1">
    <tr>
        <th>Name</th>
        <th>Duration</th>
        <th>Finished At</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php foreach ($historyTasks as $task): ?>
        <tr>
            <td><?= htmlspecialchars($task['name']) ?></td>
            <td><?= $task['duration_minutes'] ?> min</td>
            <td><?= $task['end_time'] ? date('m/d/Y H:i', strtotime($task['end_time'])) : '-' ?></td>
            <td>
                <?php if ($task['status'] === 'completed'): ?>
                    <span style="background:#007bff;color:white;padding:4px 8px;border-radius:4px;">COMPLETED</span>
                <?php else: ?>
                    <span style="background:#dc3545;color:white;padding:4px 8px;border-radius:4px;">CANCELED</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="delete_task.php?id=<?= $task['id'] ?>&uid=<?= $uid ?>" onclick="return confirm('Delete this task?');">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<!-- ================= JAVASCRIPT ================= -->
<script>
const durationInput = document.getElementById('taskDuration');
const pointsPreview = document.getElementById('pointsPreview');
const pointsPerMinute = 50; // 50 points per minute for testing
const pointsThreshold = 50;  // threshold to dispense treat

// Live points preview
durationInput.addEventListener('input', () => {
    const duration = parseInt(durationInput.value) || 0;
    const points = duration * pointsPerMinute;
    pointsPreview.innerText = `Points: ${points}`;
});

// Countdown & auto-complete
function updateCountdown() {
    document.querySelectorAll('.countdown').forEach(cell => {
        const endTime = cell.dataset.end;
        if (!endTime) return;

        const end = new Date(endTime).getTime();
        const now = Date.now();
        const distance = end - now;

        if (distance <= 0) {
            cell.innerText = "00:00:00";
            const taskId = cell.dataset.taskId;
            if (!taskId || cell.dataset.done) return;

            cell.dataset.done = "1";

            fetch('servo_motor_controller.php?id=' + taskId)
                .then(res => res.json())
                .then(data => {
                    console.log("Task completed:", data);
                    // Auto-dispense treat if threshold reached
                    if (data.servo_triggered) {
                        alert("Treat dispensed automatically!");
                    }
                    setTimeout(() => location.reload(), 500);
                })
                .catch(() => {
                    setTimeout(() => location.reload(), 500);
                });

        } else {
            const h = Math.floor(distance / 3600000);
            const m = Math.floor((distance % 3600000) / 60000);
            const s = Math.floor((distance % 60000) / 1000);
            cell.innerText =
                String(h).padStart(2,'0') + ':' +
                String(m).padStart(2,'0') + ':' +
                String(s).padStart(2,'0');
        }
    });
}

updateCountdown();
setInterval(updateCountdown, 1000);
</script>
