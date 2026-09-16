"""
serial_to_mysql.py  (Two-Way Version)
──────────────────────────────────────
Full two-way communication between Arduino and MySQL.

INBOUND  (Arduino → Python → MySQL):
  - Distance readings every 5 minutes  →  fill level stored
  - Person detection events            →  auto lid open/close triggered
  - Lid confirmation messages          →  command marked as executed

OUTBOUND (MySQL → Python → Arduino):
  - 'open'  command  →  sends "OPEN\n"  to Arduino
  - 'close' command  →  sends "CLOSE\n" to Arduino
  - 'reset' command  →  sends "CLOSE\n" + resolves alerts

Arduino serial protocol:
  Messages FROM Arduino:
    "DIST:12.5"   — distance reading in cm
    "PERSON:1"    — person detected near bin
    "PERSON:0"    — person left
    "LID:OPEN"    — lid confirmed open
    "LID:CLOSED"  — lid confirmed closed

  Messages TO Arduino:
    "OPEN\n"      — open the lid
    "CLOSE\n"     — close the lid

Install:
    pip install pyserial pymysql
"""

import serial
import pymysql
import time
import sys
import threading
from datetime import datetime

# ── Config ─────────────────────────────────────────────────────
SERIAL_PORT           = 'COM3'   # Windows: COM3, COM4. Linux: /dev/ttyUSB0
BAUD_RATE             = 9600
BIN_ID                = 1
BIN_HEIGHT            = 30       # cm

DB_HOST = 'localhost'
DB_NAME = 'intelibin'
DB_USER = 'root'
DB_PASS = ''

COMMAND_POLL_INTERVAL = 2        # seconds between DB command checks
COMMAND_TIMEOUT       = 15       # seconds before a command is marked failed


# ── Logging ────────────────────────────────────────────────────
def log(msg: str, level: str = 'INFO'):
    ts = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    print(f"[{ts}] [{level}] {msg}")


# ── DB connection ──────────────────────────────────────────────
def connect_db():
    return pymysql.connect(
        host=DB_HOST, database=DB_NAME,
        user=DB_USER, password=DB_PASS,
        autocommit=False, cursorclass=pymysql.cursors.DictCursor
    )


# ── Fill % from distance ───────────────────────────────────────
def distance_to_fill(distance_cm: float) -> int:
    fill = ((BIN_HEIGHT - distance_cm) / BIN_HEIGHT) * 100
    return max(0, min(100, round(fill)))


# ── Send to Arduino ────────────────────────────────────────────
def send_to_arduino(ser: serial.Serial, msg: str):
    ser.write((msg.upper().strip() + '\n').encode('utf-8'))
    log(f"→ Arduino: {msg.upper()}")


# ── Background command poller ──────────────────────────────────
class CommandPoller(threading.Thread):
    """
    Runs in a background thread.
    Every COMMAND_POLL_INTERVAL seconds it checks bin_state for a
    pending command, sends it to the Arduino, and watches for timeout.
    """
    def __init__(self, ser: serial.Serial):
        super().__init__(daemon=True)
        self.ser        = ser
        self.pending    = None
        self.pending_at = None
        self._stop      = threading.Event()

    def stop(self):
        self._stop.set()

    def run(self):
        while not self._stop.is_set():
            try:
                db = connect_db()
                with db.cursor() as cur:
                    cur.execute(
                        "SELECT command, command_by FROM bin_state "
                        "WHERE bin_id = %s AND command != 'none'",
                        (BIN_ID,)
                    )
                    row = cur.fetchone()

                if row:
                    cmd = row['command']
                    if self.pending != cmd:
                        # New command — send to Arduino
                        log(f"Command queued: '{cmd}' by '{row['command_by']}'")
                        self.pending    = cmd
                        self.pending_at = time.time()
                        arduino_msg = 'OPEN' if cmd == 'open' else 'CLOSE'
                        send_to_arduino(self.ser, arduino_msg)

                    elif time.time() - self.pending_at > COMMAND_TIMEOUT:
                        # Arduino never responded — mark failed
                        log(f"Command '{cmd}' timed out", 'WARN')
                        with db.cursor() as cur:
                            cur.callproc('confirm_command', (BIN_ID, cmd, 'failed'))
                        db.commit()
                        self.pending = None

                db.close()

            except Exception as e:
                log(f"Poller error: {e}", 'ERROR')

            time.sleep(COMMAND_POLL_INTERVAL)

    def on_arduino_confirmed(self, db, lid_state: str):
        """Called by main thread when Arduino sends LID:OPEN or LID:CLOSED."""
        if not self.pending:
            return
        log(f"Arduino confirmed '{self.pending}' → lid is {lid_state}")
        try:
            with db.cursor() as cur:
                cur.callproc('confirm_command', (BIN_ID, self.pending, 'success'))
            db.commit()
        except pymysql.Error as e:
            log(f"Failed to confirm command: {e}", 'ERROR')
        self.pending    = None
        self.pending_at = None


# ── Process one line from Arduino ─────────────────────────────
def process_line(line: str, db, poller: CommandPoller):
    if not line:
        return

    log(f"← Arduino: {line}")

    # Distance reading → save to DB
    if line.startswith('DIST:'):
        try:
            dist_cm      = float(line[5:])
            fill_percent = distance_to_fill(dist_cm)
            log(f"Distance: {dist_cm} cm → Fill: {fill_percent}%")
            with db.cursor() as cur:
                cur.callproc('insert_reading', (BIN_ID, fill_percent, dist_cm))
            db.commit()
            log("✓ Reading saved.")
        except ValueError:
            log(f"Bad DIST value: '{line}'", 'WARN')

    # Person near bin → update DB, auto-trigger open/close via stored procedure
    elif line.startswith('PERSON:'):
        detected = 1 if line[7:].strip() == '1' else 0
        log(f"Person {'detected' if detected else 'left'}")
        try:
            with db.cursor() as cur:
                cur.callproc('update_person_detection', (BIN_ID, detected))
            db.commit()
        except pymysql.Error as e:
            log(f"Person detection DB error: {e}", 'ERROR')

    # Arduino confirms lid opened
    elif line == 'LID:OPEN':
        poller.on_arduino_confirmed(db, 'open')

    # Arduino confirms lid closed
    elif line == 'LID:CLOSED':
        poller.on_arduino_confirmed(db, 'closed')

    else:
        log(f"Unknown message: '{line}'", 'WARN')


# ── Main ───────────────────────────────────────────────────────
def main():
    log(f"InteliBin starting — port: {SERIAL_PORT}, db: {DB_NAME}")

    try:
        ser = serial.Serial(SERIAL_PORT, BAUD_RATE, timeout=1)
        time.sleep(2)
        log("Serial connected.")
    except serial.SerialException as e:
        log(f"Cannot open serial port: {e}", 'ERROR')
        sys.exit(1)

    try:
        db = connect_db()
        log("MySQL connected.")
    except pymysql.Error as e:
        log(f"MySQL failed: {e}", 'ERROR')
        sys.exit(1)

    poller = CommandPoller(ser)
    poller.start()
    log("Ready. Listening for Arduino messages...\n")

    while True:
        try:
            line = ser.readline().decode('utf-8', errors='ignore').strip()
            process_line(line, db, poller)

        except pymysql.Error as e:
            log(f"DB error: {e}", 'ERROR')
            try:
                db = connect_db()
                log("Reconnected to MySQL.")
            except pymysql.Error:
                log("Reconnect failed. Waiting 10s...", 'ERROR')
                time.sleep(10)

        except serial.SerialException as e:
            log(f"Serial error: {e}", 'ERROR')
            poller.stop()
            sys.exit(1)

        except KeyboardInterrupt:
            log("Stopped.")
            poller.stop()
            ser.close()
            db.close()
            sys.exit(0)


if __name__ == '__main__':
    main()
