/*
 * InteliBin - Arduino Uno hardware integration
 * ===========================================
 *
 * Hardware contract:
 *   Person sensor HC-SR04: TRIG D9, ECHO D10
 *   Fill sensor HC-SR04:   TRIG D4, ECHO D5
 *   Servo signal:          D6
 *   USB serial baud:       9600
 *
 * Arduino -> PC protocol: newline-delimited JSON.
 *   {"event":"reading","personDistance":35.2,"fillDistance":12.5,"fillPercentage":58,"lid":"closed"}
 *   {"event":"person","personDistance":12.0,"detected":true,"fillDistance":12.5,"fillPercentage":58,"lid":"open"}
 *   {"event":"lid","personDistance":12.0,"fillDistance":12.5,"fillPercentage":58,"lid":"closed"}
 *
 * PC -> Arduino protocol:
 *   OPEN
 *   CLOSE
 */

#include <Servo.h>

const int TRIG_PERSON = 9;
const int ECHO_PERSON = 10;
const int TRIG_FILL   = 4;
const int ECHO_FILL   = 5;
const int SERVO_PIN   = 6;

const int BIN_HEIGHT_CM       = 30;
const int PERSON_THRESHOLD_CM = 15;
const int LID_OPEN_ANGLE      = 90;
const int LID_CLOSED_ANGLE    = 0;

const unsigned long FILL_READ_INTERVAL_MS = 300000UL; // 5 minutes
const unsigned long LID_OPEN_DURATION_MS  = 5000UL;   // 5 seconds
const unsigned long PERSON_POLL_MS        = 100UL;

Servo lidServo;

bool lidIsOpen = false;
bool personInsideZone = false;
bool personCanTrigger = true;
bool binFull = false;

float lastPersonDistance = -1;
float lastFillDistance = -1;
int lastFillPercent = 0;

unsigned long lidCloseAt = 0;
unsigned long lastFillReadAt = 0;
unsigned long lastPersonPollAt = 0;

float measureDistance(int trigPin, int echoPin) {
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);

  long duration = pulseIn(echoPin, HIGH, 30000);
  if (duration == 0) return -1;

  return (duration * 0.0343) / 2.0;
}

int distanceToFillPercent(float distanceCm) {
  if (distanceCm < 0) return lastFillPercent;
  int pct = (int)(((BIN_HEIGHT_CM - distanceCm) / (float)BIN_HEIGHT_CM) * 100);
  return constrain(pct, 0, 100);
}

void printNullableDistance(float value) {
  if (value < 0) {
    Serial.print("null");
    return;
  }
  Serial.print(value, 1);
}

void sendStatus(const char* eventName, bool includeDetected, bool detected) {
  Serial.print("{\"event\":\"");
  Serial.print(eventName);
  Serial.print("\",\"personDistance\":");
  printNullableDistance(lastPersonDistance);
  Serial.print(",\"fillDistance\":");
  printNullableDistance(lastFillDistance);
  Serial.print(",\"fillPercentage\":");
  Serial.print(lastFillPercent);
  Serial.print(",\"lid\":\"");
  Serial.print(lidIsOpen ? "open" : "closed");
  Serial.print("\"");
  if (includeDetected) {
    Serial.print(",\"detected\":");
    Serial.print(detected ? "true" : "false");
  }
  Serial.println("}");
}

void openLid(bool timed) {
  if (!lidIsOpen) {
    lidServo.write(LID_OPEN_ANGLE);
    delay(500);
    lidIsOpen = true;
  }

  if (timed) {
    lidCloseAt = millis() + LID_OPEN_DURATION_MS;
  }

  sendStatus("lid", false, false);
}

void closeLid() {
  if (lidIsOpen) {
    lidServo.write(LID_CLOSED_ANGLE);
    delay(500);
    lidIsOpen = false;
  }

  lidCloseAt = 0;
  sendStatus("lid", false, false);
}

void processCommand(String cmd) {
  cmd.trim();
  cmd.toUpperCase();

  if (cmd == "OPEN") {
    openLid(false);
  } else if (cmd == "CLOSE") {
    closeLid();
  }
}

void readFillLevel() {
  lastFillDistance = measureDistance(TRIG_FILL, ECHO_FILL);
  if (lastFillDistance > 0) {
    lastFillPercent = distanceToFillPercent(lastFillDistance);
    binFull = lastFillPercent >= 80;

    if (binFull && lidIsOpen) {
      closeLid();
    }

    sendStatus("reading", false, false);
  }
}

void pollPersonSensor() {
  lastPersonDistance = measureDistance(TRIG_PERSON, ECHO_PERSON);
  bool detected = lastPersonDistance > 0 && lastPersonDistance <= PERSON_THRESHOLD_CM;

  if (detected && !personInsideZone) {
    personInsideZone = true;
    sendStatus("person", true, true);

    if (personCanTrigger && !binFull) {
      personCanTrigger = false;
      openLid(true);
    }
  }

  if (!detected && personInsideZone) {
    personInsideZone = false;
    personCanTrigger = true;
    sendStatus("person", true, false);
  }
}

void setup() {
  Serial.begin(9600);

  pinMode(TRIG_PERSON, OUTPUT);
  pinMode(ECHO_PERSON, INPUT);
  pinMode(TRIG_FILL, OUTPUT);
  pinMode(ECHO_FILL, INPUT);

  lidServo.attach(SERVO_PIN);
  lidServo.write(LID_CLOSED_ANGLE);
  delay(500);

  readFillLevel();
  lastFillReadAt = millis();
  sendStatus("ready", false, false);
}

void loop() {
  if (Serial.available() > 0) {
    String cmd = Serial.readStringUntil('\n');
    processCommand(cmd);
  }

  unsigned long now = millis();

  if (now - lastPersonPollAt >= PERSON_POLL_MS) {
    lastPersonPollAt = now;
    pollPersonSensor();
  }

  if (lidIsOpen && lidCloseAt > 0 && now >= lidCloseAt) {
    closeLid();
  }

  if (now - lastFillReadAt >= FILL_READ_INTERVAL_MS || lastFillReadAt == 0) {
    lastFillReadAt = now;
    readFillLevel();
  }
}
