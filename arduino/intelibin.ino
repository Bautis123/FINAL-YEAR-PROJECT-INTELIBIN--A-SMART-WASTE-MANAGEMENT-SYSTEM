/*
 * InteliBin — Arduino Sketch (Two-Way Communication)
 * ====================================================
 * Hardware:
 *   - HC-SR04 ultrasonic sensor  (TRIG: pin 9, ECHO: pin 10)
 *   - SG90 servo motor           (signal: pin 6)
 *   - Optional: second HC-SR04 for person detection (TRIG: pin 7, ECHO: pin 8)
 *
 * Serial protocol (9600 baud):
 *
 *   SENDS to PC:
 *     "DIST:12.5\n"   — distance to waste surface in cm (every 5 min)
 *     "PERSON:1\n"    — someone is within PERSON_THRESHOLD cm
 *     "PERSON:0\n"    — person has moved away
 *     "LID:OPEN\n"    — lid has finished opening
 *     "LID:CLOSED\n"  — lid has finished closing
 *
 *   RECEIVES from PC:
 *     "OPEN\n"        — open the lid
 *     "CLOSE\n"       — close the lid
 */

#include <Servo.h>

// ── Pin definitions ───────────────────────────────────────────
const int TRIG_FILL   = 9;    // Fill sensor trigger
const int ECHO_FILL   = 10;   // Fill sensor echo
const int TRIG_PERSON = 7;    // Person detection trigger
const int ECHO_PERSON = 8;    // Person detection echo
const int SERVO_PIN   = 6;    // Servo signal

// ── Config ────────────────────────────────────────────────────
const int  BIN_HEIGHT_CM      = 30;    // Physical bin height
const int  PERSON_THRESHOLD   = 40;   // cm — closer than this = person detected
const int  LID_OPEN_ANGLE     = 90;   // degrees
const int  LID_CLOSED_ANGLE   = 0;    // degrees
const long READ_INTERVAL_MS   = 300000UL; // 5 minutes in milliseconds

// ── State ─────────────────────────────────────────────────────
Servo     lidServo;
bool      lidIsOpen         = false;
bool      personPresent     = false;
bool      binFull           = false;    // set when fill >= 80%
unsigned long lastReadTime  = 0;

// ── Measure distance with HC-SR04 ─────────────────────────────
float measureDistance(int trigPin, int echoPin) {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duration = pulseIn(echoPin, HIGH, 30000); // 30ms timeout
  if (duration == 0) return -1; // sensor error or out of range

  return (duration * 0.0343) / 2.0; // cm
}

// ── Open lid ──────────────────────────────────────────────────
void openLid() {
  if (lidIsOpen) {
    Serial.println("LID:OPEN");
    return;
  }
  lidServo.write(LID_OPEN_ANGLE);
  delay(500); // give servo time to reach position
  lidIsOpen = true;
  Serial.println("LID:OPEN");
}

// ── Close lid ─────────────────────────────────────────────────
void closeLid() {
  if (!lidIsOpen) {
    Serial.println("LID:CLOSED");
    return;
  }
  lidServo.write(LID_CLOSED_ANGLE);
  delay(500);
  lidIsOpen = false;
  Serial.println("LID:CLOSED");
}

// ── Process command from PC ────────────────────────────────────
void processCommand(String cmd) {
  cmd.trim();
  if (cmd == "OPEN") {
    openLid();
  } else if (cmd == "CLOSE") {
    closeLid();
  }
}

// ── Setup ─────────────────────────────────────────────────────
void setup() {
  Serial.begin(9600);

  pinMode(TRIG_FILL,   OUTPUT);
  pinMode(ECHO_FILL,   INPUT);
  pinMode(TRIG_PERSON, OUTPUT);
  pinMode(ECHO_PERSON, INPUT);

  lidServo.attach(SERVO_PIN);
  lidServo.write(LID_CLOSED_ANGLE); // start closed
  delay(500);

  Serial.println("INTELIBIN:READY");
}

// ── Loop ──────────────────────────────────────────────────────
void loop() {

  // ── 1. Check for commands from PC ─────────────────────────
  if (Serial.available() > 0) {
    String cmd = Serial.readStringUntil('\n');
    processCommand(cmd);
  }

  // ── 2. Person detection (check every loop cycle ~100ms) ───
  float personDist = measureDistance(TRIG_PERSON, ECHO_PERSON);

  if (personDist > 0 && personDist < PERSON_THRESHOLD) {
    if (!personPresent) {
      personPresent = true;
      Serial.println("PERSON:1");

      // Auto-open only if bin is not full
      if (!binFull) {
        openLid();
      }
    }
  } else {
    if (personPresent) {
      personPresent = false;
      Serial.println("PERSON:0");

      // Auto-close when person leaves (only if we auto-opened)
      if (lidIsOpen) {
        closeLid();
      }
    }
  }

  // ── 3. Fill level reading (every 5 minutes) ───────────────
  unsigned long now = millis();
  if (now - lastReadTime >= READ_INTERVAL_MS || lastReadTime == 0) {
    lastReadTime = now;

    float distance = measureDistance(TRIG_FILL, ECHO_FILL);

    if (distance > 0) {
      // Report raw distance — Python converts to fill %
      Serial.print("DIST:");
      Serial.println(distance, 1); // e.g. "DIST:12.5"

      // Update local full flag
      int fillPercent = (int)(((BIN_HEIGHT_CM - distance) / (float)BIN_HEIGHT_CM) * 100);
      fillPercent = constrain(fillPercent, 0, 100);
      binFull = (fillPercent >= 80);

      // If bin just became full, close and lock lid
      if (binFull && lidIsOpen) {
        closeLid();
      }
    }
  }

  delay(100); // small delay — keeps loop responsive without hammering the sensor
}
