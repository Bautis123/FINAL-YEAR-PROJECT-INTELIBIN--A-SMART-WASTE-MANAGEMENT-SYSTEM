-- ============================================================
--  InteliBin — Two-Way Communication Additions
--  Run this AFTER the original intelibin_db.sql
--  MySQL 8.0+
-- ============================================================

USE intelibin;


-- ============================================================
--  TABLE: bin_state
--  One row per bin. Tracks the live physical state of the bin:
--  lid position, command queue, and person detection.
--  Python script reads and writes this. Dashboard reads it.
-- ============================================================
CREATE TABLE bin_state (
  bin_id              INT           UNSIGNED PRIMARY KEY,
  lid_status          ENUM('closed','open','locked') NOT NULL DEFAULT 'closed',
  -- closed  = normal, servo at 0°
  -- open    = lid up, servo at 90°
  -- locked  = bin full, auto-open disabled

  command             ENUM('none','open','close','reset') NOT NULL DEFAULT 'none',
  -- Set by PHP API when operator clicks a button.
  -- Python script reads this, acts on it, then sets it back to 'none'.

  command_by          VARCHAR(100) NULL DEFAULT NULL,   -- who issued the command
  command_at          TIMESTAMP    NULL DEFAULT NULL,   -- when it was issued
  command_executed_at TIMESTAMP    NULL DEFAULT NULL,   -- when Python confirmed it
  person_detected     TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = someone is near the bin
  last_updated        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_binstate_bin
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE
);

-- Seed initial state for bin 1
INSERT INTO bin_state (bin_id) VALUES (1);


-- ============================================================
--  TABLE: command_log
--  Audit trail of every command issued and its outcome.
--  Good for your supervisor demo — shows the full loop.
-- ============================================================
CREATE TABLE command_log (
  id              INT       UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bin_id          INT       UNSIGNED NOT NULL,
  command         VARCHAR(20) NOT NULL,
  issued_by       VARCHAR(100) NULL,
  issued_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  executed_at     TIMESTAMP NULL DEFAULT NULL,
  outcome         ENUM('pending','success','timeout','failed') NOT NULL DEFAULT 'pending',
  note            VARCHAR(255) NULL,

  CONSTRAINT fk_cmdlog_bin
    FOREIGN KEY (bin_id) REFERENCES bins(id) ON DELETE CASCADE
);

CREATE INDEX idx_cmdlog_bin ON command_log (bin_id, issued_at DESC);


-- ============================================================
--  Update latest_reading view to include bin_state
-- ============================================================
DROP VIEW IF EXISTS latest_reading;

CREATE VIEW latest_reading AS
  SELECT
    b.id            AS bin_id,
    b.name          AS bin_name,
    b.location,
    b.height_cm,
    r.id            AS reading_id,
    r.fill_percent,
    r.distance_cm,
    r.status,
    r.recorded_at,
    bs.lid_status,
    bs.command,
    bs.person_detected,
    bs.last_updated  AS state_updated_at
  FROM bins b
  LEFT JOIN readings r ON r.id = (
    SELECT id FROM readings
    WHERE bin_id = b.id
    ORDER BY recorded_at DESC
    LIMIT 1
  )
  LEFT JOIN bin_state bs ON bs.bin_id = b.id;


-- ============================================================
--  STORED PROCEDURE: issue_command
--  Called by PHP API when operator clicks Open / Close / Reset.
--  Rejects command if bin is full and command is 'open'
--  (operator override bypasses this — see p_override flag).
--
--  Usage:
--    CALL issue_command(1, 'open',  'Operator', 0);  -- normal open
--    CALL issue_command(1, 'open',  'Operator', 1);  -- force open full bin
--    CALL issue_command(1, 'reset', 'Operator', 0);  -- after emptying
-- ============================================================
DELIMITER $$

CREATE PROCEDURE issue_command(
  IN p_bin_id    INT UNSIGNED,
  IN p_command   VARCHAR(20),
  IN p_issued_by VARCHAR(100),
  IN p_override  TINYINT       -- 1 = ignore full-bin lock
)
BEGIN
  DECLARE v_lid      VARCHAR(20);
  DECLARE v_fill     TINYINT UNSIGNED;
  DECLARE v_log_id   INT UNSIGNED;

  -- Get current state
  SELECT bs.lid_status, r.fill_percent
  INTO   v_lid, v_fill
  FROM   bin_state bs
  LEFT JOIN readings r ON r.id = (
    SELECT id FROM readings WHERE bin_id = p_bin_id ORDER BY recorded_at DESC LIMIT 1
  )
  WHERE  bs.bin_id = p_bin_id;

  -- Block auto-open if bin is full and no override
  IF p_command = 'open' AND v_fill >= 80 AND p_override = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Bin is full. Use override to force open.';
  END IF;

  -- Queue the command
  UPDATE bin_state
  SET    command      = p_command,
         command_by   = p_issued_by,
         command_at   = NOW(),
         command_executed_at = NULL
  WHERE  bin_id = p_bin_id;

  -- Log it
  INSERT INTO command_log (bin_id, command, issued_by)
  VALUES (p_bin_id, p_command, p_issued_by);

  SELECT LAST_INSERT_ID() INTO v_log_id;

  -- Return the log ID so PHP can poll for completion
  SELECT v_log_id AS log_id;
END$$


-- ============================================================
--  STORED PROCEDURE: confirm_command
--  Called by Python script AFTER it has sent the command to
--  the Arduino and received confirmation back.
--
--  Usage: CALL confirm_command(1, 'open', 'success');
-- ============================================================
CREATE PROCEDURE confirm_command(
  IN p_bin_id   INT UNSIGNED,
  IN p_command  VARCHAR(20),
  IN p_outcome  VARCHAR(20)    -- 'success' or 'failed'
)
BEGIN
  -- Update bin_state
  UPDATE bin_state
  SET command             = 'none',
      command_executed_at = NOW(),
      lid_status          = CASE
        WHEN p_command = 'open'  AND p_outcome = 'success' THEN 'open'
        WHEN p_command = 'close' AND p_outcome = 'success' THEN 'closed'
        WHEN p_command = 'reset' AND p_outcome = 'success' THEN 'closed'
        ELSE lid_status   -- no change on failure
      END
  WHERE bin_id = p_bin_id;

  -- Update the most recent pending log entry
  UPDATE command_log
  SET    executed_at = NOW(),
         outcome     = p_outcome
  WHERE  bin_id  = p_bin_id
    AND  outcome  = 'pending'
  ORDER BY issued_at DESC
  LIMIT 1;
END$$


-- ============================================================
--  STORED PROCEDURE: update_person_detection
--  Called by Python script when Arduino reports someone nearby.
--
--  Usage: CALL update_person_detection(1, 1);  -- person present
--         CALL update_person_detection(1, 0);  -- person left
-- ============================================================
CREATE PROCEDURE update_person_detection(
  IN p_bin_id          INT UNSIGNED,
  IN p_person_detected TINYINT
)
BEGIN
  DECLARE v_fill    TINYINT UNSIGNED;
  DECLARE v_command VARCHAR(20);

  SELECT r.fill_percent, bs.command
  INTO   v_fill, v_command
  FROM   bin_state bs
  LEFT JOIN readings r ON r.id = (
    SELECT id FROM readings WHERE bin_id = p_bin_id ORDER BY recorded_at DESC LIMIT 1
  )
  WHERE  bs.bin_id = p_bin_id;

  -- Update detection flag
  UPDATE bin_state SET person_detected = p_person_detected WHERE bin_id = p_bin_id;

  -- Auto-open lid if person detected AND bin not full AND no command pending
  IF p_person_detected = 1 AND (v_fill IS NULL OR v_fill < 80) AND v_command = 'none' THEN
    UPDATE bin_state
    SET command    = 'open',
        command_by = 'auto',
        command_at = NOW()
    WHERE bin_id = p_bin_id;
  END IF;

  -- Auto-close when person leaves (only if lid was auto-opened)
  IF p_person_detected = 0 THEN
    UPDATE bin_state
    SET command    = 'close',
        command_by = 'auto',
        command_at = NOW()
    WHERE bin_id = p_bin_id AND lid_status = 'open';
  END IF;
END$$

DELIMITER ;


-- ============================================================
--  EXAMPLE QUERIES
-- ============================================================

-- Operator opens bin remotely (normal):
-- CALL issue_command(1, 'open', 'Admin', 0);

-- Operator force-opens full bin:
-- CALL issue_command(1, 'open', 'Admin', 1);

-- Operator resets after emptying:
-- CALL issue_command(1, 'reset', 'Admin', 0);

-- Python confirms Arduino executed the command:
-- CALL confirm_command(1, 'open', 'success');

-- Python reports person near bin:
-- CALL update_person_detection(1, 1);

-- Dashboard gets current state:
-- SELECT * FROM latest_reading WHERE bin_id = 1;

-- Recent command history:
-- SELECT * FROM command_log WHERE bin_id = 1 ORDER BY issued_at DESC LIMIT 10;
