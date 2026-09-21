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
 *   {"event":"heartbeat","personDistance":35.2,"fillDistance":8.4,"fillPercentage":74,"lid":"closed"}
 *   {"event":"reading","personDistance":35.2,"fillDistance":8.4,"fillPercentage":74,"lid":"closed"}
 *   {"event":"person","personDistance":12.0,"detected":true,"fillDistance":8.4,"fillPercentage":74,"lid":"open"}
 *   {"event":"lid","personDistance":12.0,"fillDistance":8.4,"fillPercentage":74,"lid":"closed"}
 *
 * PC -> Arduino protocol:
 *   OPEN
 *   CLOSE
 *   RESET
 *
 * Fill readings:
 *   The firmware reads fill once at startup, then once after each lid close.
 *   Each fill reading is the median of 20 HC-SR04 samples.
 */

#include <Servo.h>

const int TRIG_PERSON = 9;
const int ECHO_PERSON = 10;
const int TRIG_FILL   = 4;
const int ECHO_FILL   = 5;
const int SERVO_PIN   = 6;

const int PERSON_THRESHOLD_CM = 15;
const int LID_CLOSED_ANGLE    = 90;
const int LID_OPEN_ANGLE      = 180;

const float EMPTY_DISTANCE_CM = 19.0;
const float FULL_DISTANCE_CM  = 4.0;
const int FILL_SAMPLE_COUNT   = 20;
const int MIN_VALID_SAMPLES   = 12;
const float MAX_SAMPLE_SPREAD_CM = 6.0;
const int MAX_FILL_DROP_PERCENT = 5;
const int EMPTY_DETECT_PERCENT = 10;
const float EMPTY_DETECT_DISTANCE_CM = 18.0;

const unsigned long HEARTBEAT_INTERVAL_MS = 5000UL;   // connection status
const unsigned long LID_OPEN_DURATION_MS  = 5000UL;   // 5 seconds
const unsigned long PERSON_POLL_MS        = 100UL;
const unsigned long FILL_SETTLE_DELAY_MS  = 5000UL;   // wait 5 seconds after closing lid

Servo lidServo;

bool lidIsOpen = false;
bool personInsideZone = false;
bool personCanTrigger = true;
bool binFull = false;

float lastPersonDistance = -1;
float lastFillDistance = -1;
int lastFillPercent = 0;

unsigned long lidCloseAt = 0;
unsigned long lastHeartbeatAt = 0;
unsigned long lastPersonPollAt = 0;
unsigned long fillReadAt = 0;

bool fillReadScheduled = false;

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
  int pct = (int)(((EMPTY_DISTANCE_CM - distanceCm) / (EMPTY_DISTANCE_CM - FULL_DISTANCE_CM)) * 100);
  return constrain(pct, 0, 100);
}

bool isValidFillDistance(float distanceCm) {
  return distanceCm > 0 && distanceCm <= EMPTY_DISTANCE_CM;
}

float medianOf(float values[], int size) {
  for (int i = 0; i < size - 1; i++) {
    for (int j = i + 1; j < size; j++) {
      if (values[j] < values[i]) {
        float temp = values[i];
        values[i] = values[j];
        values[j] = temp;
      }
    }
  }

  if (size % 2 == 0) {
    return (values[(size / 2) - 1] + values[size / 2]) / 2.0;
  }

  return values[size / 2];
}

bool readMedianDistance(int trigPin, int echoPin, float* distanceCm) {
  float readings[FILL_SAMPLE_COUNT];
  int validReadings = 0;
  float minDistance = 999;
  float maxDistance = -1;

  for (int i = 0; i < FILL_SAMPLE_COUNT; i++) {
    float distance = measureDistance(trigPin, echoPin);

    if (isValidFillDistance(distance)) {
      readings[validReadings] = distance;
      validReadings++;
      if (distance < minDistance) minDistance = distance;
      if (distance > maxDistance) maxDistance = distance;
    }

    delay(50);
  }

  if (validReadings < MIN_VALID_SAMPLES) {
    return false;
  }

  if ((maxDistance - minDistance) > MAX_SAMPLE_SPREAD_CM) {
    return false;
  }

  *distanceCm = medianOf(readings, validReadings);
  return true;
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

void scheduleFillRead() {
  fillReadScheduled = true;
  fillReadAt = millis() + FILL_SETTLE_DELAY_MS;
}

void closeLid(bool measureAfterClose) {
  if (lidIsOpen) {
    lidServo.write(LID_CLOSED_ANGLE);
    delay(500);
    lidIsOpen = false;
  }

  lidCloseAt = 0;
  sendStatus("lid", false, false);

  if (measureAfterClose) {
    scheduleFillRead();
  }
}

void resetFillState() {
  lastFillDistance = EMPTY_DISTANCE_CM;
  lastFillPercent = 0;
  binFull = false;
}

void processCommand(String cmd) {
  cmd.trim();
  cmd.toUpperCase();

  if (cmd == "OPEN") {
    openLid(false);
  } else if (cmd == "CLOSE") {
    closeLid(true);
  } else if (cmd == "RESET") {
    closeLid(false);
    resetFillState();
  }
}

void readFillLevel() {
  float medianDistance = -1;

  if (!readMedianDistance(TRIG_FILL, ECHO_FILL, &medianDistance)) {
    sendStatus("reading_error", false, false);
    return;
  }

  int measuredFillPercent = distanceToFillPercent(medianDistance);
  bool emptyDetected = measuredFillPercent <= EMPTY_DETECT_PERCENT && medianDistance >= EMPTY_DETECT_DISTANCE_CM;

  if (emptyDetected) {
    lastFillDistance = medianDistance;
    lastFillPercent = measuredFillPercent;
    binFull = false;
    sendStatus("reading", false, false);
    return;
  }

  if (measuredFillPercent < lastFillPercent - MAX_FILL_DROP_PERCENT) {
    sendStatus("reading_error", false, false);
    return;
  }

  if (measuredFillPercent < lastFillPercent) {
    sendStatus("reading", false, false);
    return;
  }

  lastFillDistance = medianDistance;
  lastFillPercent = measuredFillPercent;
  binFull = lastFillPercent >= 80;

  if (binFull && lidIsOpen) {
    closeLid(false);
  }

  sendStatus("reading", false, false);
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
  lastHeartbeatAt = millis();
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
    closeLid(true);
  }

  if (now - lastHeartbeatAt >= HEARTBEAT_INTERVAL_MS || lastHeartbeatAt == 0) {
    lastHeartbeatAt = now;
    sendStatus("heartbeat", false, false);
  }

  if (fillReadScheduled && now >= fillReadAt) {
    fillReadScheduled = false;
    readFillLevel();
  }
}
