import serial
import webbrowser
import time

# === CONFIGURE THESE ===
SERIAL_PORT = "COM5"      # Your Arduino port
BAUD_RATE = 9600
PHP_URL = "http://localhost:8012/FinalProject/index.php?uid="  # XAMPP is running on port 8012
# ======================

try:
    ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
    print(f"Listening to Arduino on {SERIAL_PORT}...")
except Exception as e:
    print("Cannot open serial port:", e)
    exit()

while True:
    try:
        line = ser.readline().decode().strip()
        if line == "":
            continue

        if "RFID" in line:
            uid = line.split(":")[-1].strip().replace(" ", "")
            print("Scanned UID:", uid)

            url = PHP_URL + uid
            webbrowser.open(url)

            time.sleep(2)

    except Exception as e:
        print("Error:", e)
        break