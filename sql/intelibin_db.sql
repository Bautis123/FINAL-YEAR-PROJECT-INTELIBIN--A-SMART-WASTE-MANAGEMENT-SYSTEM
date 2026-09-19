-- ============================================================
--  InteliBin Database Design
--  MySQL 8.0+
--  Project: Smart Waste Bin Monitoring System
-- ============================================================

CREATE DATABASE IF NOT EXISTS intelibin
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE intelibin;


-- ============================================================
--  TABLE: admin_users
--  Dashboard operators. Passwords are stored as PHP password_hash
--  values, not plaintext constants in source code.
-- ============================================================
CREATE TABLE admin_users (
  id              INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         VARCHAR(80)   NOT NULL UNIQUE,
  email           VARCHAR(160)  NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NOT NULL,
  active          TINYINT(1)    NOT NULL DEFAULT 1,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Default demo admin: admin / admin1234
INSERT INTO admin_users (user_id, email, password_hash)
VALUES ('admin', 'admin@intelibin.local', '$2y$10$qdiPDuJCU/YBMczsYENfwOJ1VVCTaFZmP.mEacWj.b96sRDdKd3qy');


-- ============================================================
--  TABLE: bins
--  One row per physical bin. Right now you have one bin,
--  but this lets you add more later without changing anything.
-- ============================================================
CREATE TABLE bins (
  id            INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL DEFAULT 'Bin 1',
  location      VARCHAR(255)  NOT NULL DEFAULT 'Unknown',  -- e.g. "Lab 3, UNZA"
  height_cm     TINYINT       UNSIGNED NOT NULL DEFAULT 30, -- physical bin height
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed your one bin
INSERT INTO bins (name, location, height_cm)
VALUES ('InteliBin #1', 'Computer Lab, UNZA', 30);


-- ============================================================
--  TABLE: readings
--  Every sensor reading the Arduino sends.
--  The Python serial script inserts one row per reading.
--  Readings come in every 5 minutes.
-- ============================================================
CREATE TABLE readings (
  id              BIGINT        UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bin_id          INT           UNSIGNED NOT NULL DEFAULT 1,
  fill_percent    TINYINT       UNSIGNED NOT NULL,          -- 0–100, integer
  distance_cm     DECIMAL(5,1)  NOT NULL,                   -- raw sensor value kept for debugging
  status          ENUM(
                    'ready',        -- 0–20%
                    'filling',      -- 20–50%
                    'almost_full',  -- 50–80%
                    'full'          -- 80–100%
                  ) NOT NULL,
  recorded_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_readings_bin
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,

  CONSTRAINT chk_fill_range
    CHECK (fill_percent BETWEEN 0 AND 100)
);

-- Index for the most common query: latest reading for a bin
CREATE INDEX idx_readings_bin_time ON readings (bin_id, recorded_at DESC);


-- ============================================================
--  TABLE: alerts
--  Logged whenever the bin hits 'full' (80%+).
--  Lets you track how often bins overflow and when staff responded.
-- ============================================================
CREATE TABLE alerts (
  id              INT           UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bin_id          INT           UNSIGNED NOT NULL DEFAULT 1,
  reading_id      BIGINT        UNSIGNED NOT NULL,           -- the reading that triggered it
  fill_percent    TINYINT       UNSIGNED NOT NULL,
  triggered_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at     TIMESTAMP     NULL DEFAULT NULL,           -- set when bin is emptied
  resolved        TINYINT(1)    NOT NULL DEFAULT 0,

  CONSTRAINT fk_alerts_bin
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE,
  CONSTRAINT fk_alerts_reading
    FOREIGN KEY (reading_id) REFERENCES readings(id) ON DELETE CASCADE
);

CREATE INDEX idx_alerts_bin ON alerts (bin_id, triggered_at DESC);


-- ============================================================
--  VIEW: latest_reading
--  What the PHP API queries. One row per bin, always current.
--  Dashboard hits this every 30 seconds.
-- ============================================================
CREATE VIEW latest_reading AS
  SELECT
    b.id          AS bin_id,
    b.name        AS bin_name,
    b.location,
    b.height_cm,
    r.id          AS reading_id,
    r.fill_percent,
    r.distance_cm,
    r.status,
    r.recorded_at
  FROM bins b
  JOIN readings r ON r.id = (
    SELECT id FROM readings
    WHERE bin_id = b.id
    ORDER BY recorded_at DESC
    LIMIT 1
  );


-- ============================================================
--  VIEW: daily_summary
--  Useful for the chart. Returns hourly averages per bin.
--  Query: SELECT * FROM daily_summary WHERE bin_id = 1
-- ============================================================
CREATE VIEW daily_summary AS
  SELECT
    bin_id,
    DATE(recorded_at)                         AS day,
    HOUR(recorded_at)                         AS hour,
    ROUND(AVG(fill_percent))                  AS avg_fill,
    MAX(fill_percent)                         AS max_fill,
    MIN(fill_percent)                         AS min_fill,
    COUNT(*)                                  AS reading_count
  FROM readings
  GROUP BY bin_id, DATE(recorded_at), HOUR(recorded_at);


-- ============================================================
--  STORED PROCEDURE: insert_reading
--  Called by the Python serial script instead of a raw INSERT.
--  Handles status calculation and auto-creates alerts.
--
--  Usage: CALL insert_reading(1, 75, 7.5);
-- ============================================================
DELIMITER $$

CREATE PROCEDURE insert_reading(
  IN p_bin_id       INT UNSIGNED,
  IN p_fill_percent TINYINT UNSIGNED,
  IN p_distance_cm  DECIMAL(5,1)
)
BEGIN
  DECLARE v_status  VARCHAR(20);
  DECLARE v_reading_id BIGINT UNSIGNED;

  -- Derive status from fill level
  SET v_status = CASE
    WHEN p_fill_percent >= 80 THEN 'full'
    WHEN p_fill_percent >= 50 THEN 'almost_full'
    WHEN p_fill_percent >= 20 THEN 'filling'
    ELSE 'ready'
  END;

  -- Insert the reading
  INSERT INTO readings (bin_id, fill_percent, distance_cm, status)
  VALUES (p_bin_id, p_fill_percent, p_distance_cm, v_status);

  SET v_reading_id = LAST_INSERT_ID();

  -- Auto-create alert if bin is full and no open alert exists
  IF v_status = 'full' THEN
    INSERT INTO alerts (bin_id, reading_id, fill_percent)
    SELECT p_bin_id, v_reading_id, p_fill_percent
    WHERE NOT EXISTS (
      SELECT 1 FROM alerts
      WHERE bin_id = p_bin_id AND resolved = 0
    );
  END IF;

  -- Auto-resolve open alerts if bin is no longer full
  IF v_status != 'full' THEN
    UPDATE alerts
    SET resolved = 1, resolved_at = NOW()
    WHERE bin_id = p_bin_id AND resolved = 0;
  END IF;

END$$

DELIMITER ;


-- ============================================================
--  EXAMPLE QUERIES
-- ============================================================

-- PHP API: get current reading for the dashboard
-- SELECT * FROM latest_reading WHERE bin_id = 1;

-- Chart data: last 24 hours of readings
-- SELECT fill_percent, recorded_at
-- FROM readings
-- WHERE bin_id = 1 AND recorded_at >= NOW() - INTERVAL 24 HOUR
-- ORDER BY recorded_at ASC;

-- How many times has the bin been full this week?
-- SELECT COUNT(*) FROM alerts
-- WHERE bin_id = 1 AND triggered_at >= NOW() - INTERVAL 7 DAY;

-- Average fill level per day
-- SELECT day, avg_fill FROM daily_summary
-- WHERE bin_id = 1 ORDER BY day DESC LIMIT 7;
