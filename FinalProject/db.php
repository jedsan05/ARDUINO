<?php
$pdo = new PDO("mysql:host=localhost;dbname=tasks_scheduler_projectdb", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>
