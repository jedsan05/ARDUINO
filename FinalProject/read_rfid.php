<?php
$port = "COM5";
$baud = "9600";

$fp = fopen("php://stdin", "r");

exec("mode $port BAUD=$baud PARITY=n DATA=8 STOP=1 xon=off");

$serial = fopen($port, "rb");

if (!$serial) {
    die("Cannot open serial port $port");
}

echo "<h2>Waiting for RFID scan...</h2>";

while (true) {
    $data = fgets($serial);

    if ($data && trim($data) !== "") {
        echo "<h3>RFID Data: $data</h3>";
        flush();
        ob_flush();
        break;
    }
}

fclose($serial);
?>
