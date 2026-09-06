-- =============================================================================
-- Migration: ระบบวิเคราะห์บทที่ 4 และบทที่ 5
--
-- หมายเหตุ: ระบบมี auto-migration ใน db_config.php อยู่แล้ว (รันอัตโนมัติเมื่อเปิดเว็บ)
-- ไฟล์นี้มีไว้สำหรับรันมือผ่าน phpMyAdmin / mysql client หากต้องการควบคุมเอง
-- รันซ้ำได้อย่างปลอดภัย (ใช้ IF NOT EXISTS ทั้งหมด)
-- =============================================================================

-- 1) ผลวิเคราะห์ของระบบแยกตามหัวข้อของบทที่ 4-5 (หัวข้อละ 1 แถว วิเคราะห์ใหม่จะทับของเดิม)
CREATE TABLE IF NOT EXISTS ch45_analysis (
    job_key      VARCHAR(40) PRIMARY KEY,   -- quant_narrative, ind_1_1 … ind_4_3, domain_d1 … domain_d4,
                                            -- overview, defect_narrative, ch5_summary, ch5_discussion, ch5_recommend
    payload      LONGTEXT,                  -- JSON ผลวิเคราะห์ที่ผ่านการตรวจแล้ว
    raw_response LONGTEXT,                  -- คำตอบดิบของระบบไว้ตรวจย้อนหลัง
    provider     VARCHAR(30)  DEFAULT NULL,
    model        VARCHAR(100) DEFAULT NULL,
    requested_by VARCHAR(50)  DEFAULT NULL,
    input_hash   CHAR(40)     DEFAULT NULL, -- ลายนิ้วมือข้อมูลนำเข้า ใช้เตือนเมื่อผลวิเคราะห์ล้าสมัย
    warnings     TEXT,                      -- JSON array จุดที่ต้องตรวจสอบก่อนนำไปใช้
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) บันทึกหลังสอนของผู้วิจัย — แหล่งข้อมูลของ "ข้อเสนอแนะสำหรับการนำผลวิจัยไปใช้" ในบทที่ 5
CREATE TABLE IF NOT EXISTS ch45_teaching_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    poa_stage   VARCHAR(20) NOT NULL DEFAULT 'general', -- motivating / enabling / assessing / general
    poa_substep VARCHAR(10) DEFAULT NULL,               -- ขั้นย่อยของผู้วิจัยเอง เช่น 1.1 / 2.2 (ไม่บังคับ)
    task_unit   TINYINT     NOT NULL DEFAULT 0,         -- 0 = ไม่ระบุหน่วย
    problem     TEXT        NOT NULL,                   -- ปัญหาที่พบจริงระหว่างจัดการเรียนการสอน
    solution    TEXT,                                   -- แนวทางแก้ไขที่ใช้แล้วได้ผล
    evidence    TEXT,                                   -- ข้อสังเกต/หลักฐานประกอบ
    created_by  VARCHAR(50) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ch45_log_stage (poa_stage, task_unit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางที่สร้างไว้ก่อนหน้านี้แล้วจะไม่มีคอลัมน์ใหม่จาก CREATE TABLE ด้านบน (เพราะมี IF NOT EXISTS)
-- จึงต้องเพิ่มคอลัมน์ให้ตารางเดิมด้วย (ต้องการ MySQL 8.0.29+ หรือ MariaDB 10.2+)
ALTER TABLE ch45_teaching_logs
    ADD COLUMN IF NOT EXISTS poa_substep VARCHAR(10) DEFAULT NULL AFTER poa_stage;

-- 3) คลังอ้างอิงงานวิจัยที่เกี่ยวข้องที่ผู้วิจัยกรอกเอง (ตรวจสอบมาแล้วว่ามีอยู่จริง)
--    ใช้ให้ระบบ "จับคู่" กับผลจริงตอนเขียนอภิปรายผลบทที่ 5 เท่านั้น
--    ระบบห้ามอ้างอิงชื่อ/ปีที่ไม่อยู่ในตารางนี้โดยเด็ดขาด กันไม่ให้เกิดการอ้างอิงที่ไม่มีอยู่จริง
CREATE TABLE IF NOT EXISTS ch45_references (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    citation_label VARCHAR(190) NOT NULL, -- เช่น "Shi (2023)" หรือ "อรุณี ใจเที่ยง (2565)"
                                           -- ระบบต้องคัดลอกป้ายนี้คำต่อคำเท่านั้นเมื่ออ้างอิง
    key_finding    TEXT NOT NULL,         -- สิ่งที่งานนี้ค้นพบ/แนวคิดสำคัญโดยย่อ (คำพูดของผู้วิจัยเอง)
    full_citation  TEXT,                  -- รายการอ้างอิงฉบับเต็มสำหรับหน้าบรรณานุกรม (ถ้ามี)
    source_url     VARCHAR(500) DEFAULT '', -- ลิงก์แหล่งที่มา ไว้กดตรวจสอบซ้ำภายหลัง (ถ้ามี)
    source_type    VARCHAR(20) NOT NULL DEFAULT 'other', -- thesis / journal / book / other
    finding_key    VARCHAR(60) DEFAULT NULL, -- ประเด็นจากผลจริงที่งานนี้ใช้จับคู่ (คีย์จาก ch45_ai_findings)
    created_by     VARCHAR(50) DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) ข้อมูลประจำงานวิจัยเก็บในตาราง app_settings เดิม (คีย์ขึ้นต้นด้วย ch45_)
--    ไม่ต้องสร้างตารางเพิ่ม — ตัวอย่างการตั้งค่าด้วยมือ:
-- INSERT INTO app_settings (skey, svalue) VALUES
--   ('ch45_academic_year', '2568'), ('ch45_classroom', '5/6'),
--   ('ch45_population_n', '280'),   ('ch45_sample_n', '40')
-- ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- 5) ฐานข้อมูลที่สร้าง ch45_references ไว้ก่อนมีคอลัมน์ finding_key ให้เพิ่มคอลัมน์นี้
--    (ระบบเพิ่มให้อัตโนมัติอยู่แล้วใน db_config.php สั่งเองก็ได้ถ้าต้องการ)
-- ALTER TABLE ch45_references ADD COLUMN finding_key VARCHAR(60) DEFAULT NULL AFTER source_type;
