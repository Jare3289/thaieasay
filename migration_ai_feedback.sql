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
    total_score     DECIMAL(6,2) NOT NULL DEFAULT 0,
    max_score       DECIMAL(6,2) NOT NULL DEFAULT 0,
    quality_level   VARCHAR(50)  DEFAULT NULL,
    provider        VARCHAR(30)  DEFAULT NULL,
    model           VARCHAR(100) DEFAULT NULL,
    requested_by    VARCHAR(50)  DEFAULT NULL,
    requested_role  VARCHAR(20)  DEFAULT NULL,
    raw_response    LONGTEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_ai_feedback (student_id, essay_phase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
