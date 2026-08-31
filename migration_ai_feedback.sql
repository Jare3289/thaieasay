-- =============================================================================
-- Migration: ระบบให้ข้อเสนอแนะเรียงความอัตโนมัติด้วย AI
--
-- หมายเหตุ: ระบบมี auto-migration ใน db_config.php อยู่แล้ว (รันอัตโนมัติเมื่อเปิดเว็บ)
-- ไฟล์นี้มีไว้สำหรับรันมือผ่าน phpMyAdmin / mysql client หากต้องการควบคุมเอง
-- รันซ้ำได้อย่างปลอดภัย (ใช้ IF NOT EXISTS ทั้งหมด)
-- =============================================================================

-- 1) ค่าตั้งค่าทั่วไปของระบบ (ครูกรอกผ่านหน้า "ตั้งค่า AI" ในเว็บ)
--    คีย์ที่ใช้: ai_provider, ai_model, ai_base_url, ai_api_key,
--               ai_enabled, ai_student_enabled, ai_student_phases
CREATE TABLE IF NOT EXISTS app_settings (
    skey       VARCHAR(64) PRIMARY KEY,
    svalue     TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) ผลการตรวจของ AI ต่อเรียงความ 1 ฉบับ (นักเรียน 1 คน ต่อ 1 รอบ = 1 แถว)
--    ตรวจใหม่จะทับของเดิม เพื่อให้เห็นผลล่าสุดเสมอ
--    เก็บแยกจากตาราง evaluations ของงานวิจัย จึงไม่ปะปนกับคะแนนครู/เพื่อน/ตนเอง
CREATE TABLE IF NOT EXISTS essay_ai_feedback (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    student_id      VARCHAR(10)  NOT NULL,
    essay_phase     VARCHAR(20)  NOT NULL,
    overall_comment TEXT,
    strengths       TEXT,
    improvements    LONGTEXT,
    next_steps      TEXT,
    encouragement   TEXT,
    scores          TEXT,
    score_overrides LONGTEXT,
    teacher_scores  TEXT,
    teacher_total   DECIMAL(6,2) NOT NULL DEFAULT 0,
    teacher_by      VARCHAR(50)  DEFAULT NULL,
    teacher_scored_at DATETIME   DEFAULT NULL,
    total_score     DECIMAL(6,2) NOT NULL DEFAULT 0,
    max_score       DECIMAL(6,2) NOT NULL DEFAULT 0,
    quality_level   VARCHAR(50)  DEFAULT NULL,
    provider        VARCHAR(30)  DEFAULT NULL,
    model           VARCHAR(100) DEFAULT NULL,
    requested_by    VARCHAR(50)  DEFAULT NULL,
    requested_role  VARCHAR(20)  DEFAULT NULL,
    raw_response    LONGTEXT,
    essay_hash      CHAR(40)     DEFAULT NULL,
    recheck_needed  TINYINT(1)   NOT NULL DEFAULT 0,
    recheck_marked_at DATETIME   DEFAULT NULL,
    review_round    INT          NOT NULL DEFAULT 1,
    baseline_phase  VARCHAR(20)  DEFAULT NULL,
    baseline_snapshot LONGTEXT,
    draft_comment   TEXT,
    draft_changes   LONGTEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_ai_feedback (student_id, essay_phase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1) ฐานข้อมูลที่สร้างตารางนี้ไว้ก่อนหน้า ให้เพิ่มคอลัมน์ "คะแนนที่ครูให้เอง" ด้วยคำสั่งด้านล่าง
--      (ระบบ auto-migration ใน db_config.php ทำให้อัตโนมัติอยู่แล้ว — รันซ้ำจะขึ้น error ว่ามีคอลัมน์อยู่แล้ว ข้ามได้เลย)
-- ALTER TABLE essay_ai_feedback ADD COLUMN teacher_scores TEXT NULL AFTER scores;
-- ALTER TABLE essay_ai_feedback ADD COLUMN teacher_total DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER teacher_scores;
-- ALTER TABLE essay_ai_feedback ADD COLUMN teacher_by VARCHAR(50) DEFAULT NULL AFTER teacher_total;
-- ALTER TABLE essay_ai_feedback ADD COLUMN teacher_scored_at DATETIME DEFAULT NULL AFTER teacher_by;

-- 2.2) คะแนนที่ "ถูกปรับรายข้อ" หลัง AI ตรวจเสร็จ — ครูปรับเอง หรือสั่งให้ AI ตรวจเฉพาะข้อนั้นใหม่
--      เก็บแยกจากคอลัมน์ scores เพื่อให้คะแนนดั้งเดิมของ AI ยังตรวจสอบย้อนหลังได้เสมอ
--      (auto-migration ใน db_config.php เติมให้อัตโนมัติ)
-- ALTER TABLE essay_ai_feedback ADD COLUMN score_overrides LONGTEXT NULL AFTER scores;

-- 3) บันทึกการเรียกใช้ AI — ใช้จำกัดโควตารายวันและให้ครูตรวจสอบย้อนหลังได้
CREATE TABLE IF NOT EXISTS ai_usage_log (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       VARCHAR(50) NOT NULL,
    user_role     VARCHAR(20) DEFAULT NULL,
    student_id    VARCHAR(10) DEFAULT NULL,
    essay_phase   VARCHAR(20) DEFAULT NULL,
    success       TINYINT(1) NOT NULL DEFAULT 1,
    error_message VARCHAR(400) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ai_usage_user_day (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- คิวตรวจใหม่: เมื่อนักเรียนแก้ไขต้นฉบับหลังจาก AI ตรวจไปแล้ว
-- (db_config.php รันให้อัตโนมัติเมื่อเปิดเว็บ — บรรทัดด้านล่างไว้รันมือกรณีจำเป็น)
-- ---------------------------------------------------------------------------
-- ALTER TABLE essay_ai_feedback ADD COLUMN essay_hash CHAR(40) DEFAULT NULL AFTER raw_response;
-- ALTER TABLE essay_ai_feedback ADD COLUMN recheck_needed TINYINT(1) NOT NULL DEFAULT 0 AFTER essay_hash;
-- ALTER TABLE essay_ai_feedback ADD COLUMN recheck_marked_at DATETIME DEFAULT NULL AFTER recheck_needed;

-- ---------------------------------------------------------------------------
-- ตัวนับ "ตรวจฉบับนี้เป็นครั้งที่เท่าไร"
-- (db_config.php รันให้อัตโนมัติเมื่อเปิดเว็บ — บรรทัดด้านล่างไว้รันมือกรณีจำเป็น)
--
-- หมายเหตุ: ระบบเคยมีคอลัมน์ prev_round / progress_comment / resolved_points ไว้เทียบผลตรวจ
-- ข้ามครั้ง ตอนนี้เลิกใช้แล้ว (เทียบตามคู่ที่ครูกำหนดอย่างเดียว) ฐานข้อมูลเดิมที่มีคอลัมน์เหล่านี้
-- ปล่อยไว้ได้เลย ระบบไม่อ่านไม่เขียนแล้ว
-- ---------------------------------------------------------------------------
-- ALTER TABLE essay_ai_feedback ADD COLUMN review_round INT NOT NULL DEFAULT 1 AFTER recheck_marked_at;

-- ---------------------------------------------------------------------------
-- เทียบกับฉบับตั้งต้นตามคู่ที่ครูกำหนด: D1.2 เทียบ D1.1 · D2.2 เทียบ D2.1 · หลังเรียน เทียบ ก่อนเรียน
-- (db_config.php รันให้อัตโนมัติเมื่อเปิดเว็บ — บรรทัดด้านล่างไว้รันมือกรณีจำเป็น)
-- ---------------------------------------------------------------------------
-- ALTER TABLE essay_ai_feedback ADD COLUMN baseline_phase VARCHAR(20) DEFAULT NULL AFTER review_round;
-- ALTER TABLE essay_ai_feedback ADD COLUMN baseline_snapshot LONGTEXT NULL AFTER baseline_phase;
-- ALTER TABLE essay_ai_feedback ADD COLUMN draft_comment TEXT NULL AFTER baseline_snapshot;
-- ALTER TABLE essay_ai_feedback ADD COLUMN draft_changes LONGTEXT NULL AFTER draft_comment;

-- ---------------------------------------------------------------------------
-- ภาพรวมการนำเสนอรายรอบงาน (ทั้งชั้น) — AI สรุปหลังตรวจครบทั้งรอบ
-- 1 รอบงาน = 1 แถว สร้างใหม่ทับของเดิม
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS essay_ai_phase_summary (
    essay_phase      VARCHAR(20) PRIMARY KEY,
    overview         TEXT,
    themes           LONGTEXT,
    interesting      LONGTEXT,
    common_strengths LONGTEXT,
    common_problems  LONGTEXT,
    observations     LONGTEXT,
    teaching_notes   LONGTEXT,
    stats            LONGTEXT,
    essay_count      INT NOT NULL DEFAULT 0,
    provider         VARCHAR(30)  DEFAULT NULL,
    model            VARCHAR(100) DEFAULT NULL,
    generated_by     VARCHAR(50)  DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
