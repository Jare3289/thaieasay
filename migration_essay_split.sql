-- =============================================================================
-- Migration: แยกเนื้อหาเรียงความเป็นคอลัมน์ + หัวข้อที่ครูกำหนด + ร่าง D1/D2
-- ใช้กับตาราง student_essays และตารางใหม่ essay_topics
--
-- หมายเหตุ: ระบบมี auto-migration ใน db_config.php อยู่แล้ว (รันอัตโนมัติเมื่อเปิดเว็บ)
-- ไฟล์นี้มีไว้สำหรับรันมือผ่าน phpMyAdmin / mysql client หากต้องการควบคุมเอง
-- รันซ้ำได้อย่างปลอดภัย (ใช้ IF NOT EXISTS / INSERT IGNORE / UPDATE แบบมีเงื่อนไข)
-- ต้องการ MySQL 5.7+ หรือ MariaDB 10.2+ (ใช้ฟังก์ชัน JSON ในขั้นตอน backfill)
-- =============================================================================

-- 1) ภาระงานรุ่นเก่า (ร่างเดียว) → ร่างที่ 1 (D1)
--    หมายเหตุ: UPDATE IGNORE จะ "ข้าม" แถวที่ชนกับแถว _d1 ที่มีอยู่แล้ว (unique student_id+essay_phase)
--    แถวเหล่านั้นจะค้างเป็น 'task1'/'task2' ต่อไป ให้ตรวจด้วยคำสั่งท้ายไฟล์นี้
--    (auto-migration ใน db_config.php จะจัดการให้อัตโนมัติ: ถ้าแถว _d1 ยังไม่มีเนื้อหาจะลบทิ้งแล้วย้ายของเดิมเข้าไปแทน)
UPDATE IGNORE student_essays SET essay_phase = 'task1_d1' WHERE essay_phase = 'task1';
UPDATE IGNORE student_essays SET essay_phase = 'task2_d1' WHERE essay_phase = 'task2';

-- ตรวจว่ายังมีแถวรุ่นเก่าค้างอยู่หรือไม่ (ควรได้ 0 แถว)
-- SELECT student_id, essay_phase FROM student_essays WHERE essay_phase IN ('task1','task2');

-- 2) เพิ่มคอลัมน์แยกส่วนเนื้อหา
--    intro_content = ส่วนนำ, body_content = ส่วนเนื้อหา (หลายย่อหน้าเก็บเป็น JSON array), conclusion_content = ส่วนสรุป
ALTER TABLE student_essays
    ADD COLUMN IF NOT EXISTS intro_content      TEXT     NULL AFTER essay_phase,
    ADD COLUMN IF NOT EXISTS body_content       LONGTEXT NULL AFTER intro_content,
    ADD COLUMN IF NOT EXISTS conclusion_content TEXT     NULL AFTER body_content;

-- 3) ย้ายข้อมูลเดิมจาก essay_content (JSON) มาใส่คอลัมน์ใหม่ (เฉพาะแถวที่ยังว่าง)
--    ถ้าฐานข้อมูลไม่มีคอลัมน์ essay_content แล้ว ให้ข้ามขั้นตอนนี้
UPDATE student_essays
SET
    intro_content      = JSON_UNQUOTE(JSON_EXTRACT(essay_content, '$.introduction')),
    body_content       = JSON_EXTRACT(essay_content, '$.body'),
    conclusion_content = JSON_UNQUOTE(JSON_EXTRACT(essay_content, '$.conclusion'))
WHERE body_content IS NULL
    AND essay_content IS NOT NULL
    AND essay_content <> ''
    AND JSON_VALID(essay_content)
    AND JSON_EXTRACT(essay_content, '$.introduction') IS NOT NULL;

-- 4) ตารางหัวข้อเรียงความที่ครูกำหนดต่อรอบ (นักเรียนไม่ต้องตั้งชื่อเรื่องเอง)
CREATE TABLE IF NOT EXISTS essay_topics (
    phase      VARCHAR(20)  PRIMARY KEY,
    topic      VARCHAR(500) DEFAULT NULL,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO essay_topics (phase, topic) VALUES
    ('pretest', NULL), ('task1', NULL), ('task2', NULL), ('posttest', NULL);

-- 5) (ทางเลือก) เมื่อยืนยันว่าข้อมูลย้ายครบแล้ว จะลบคอลัมน์เดิมที่ไม่ใช้แล้วก็ได้
--    ยกเลิกคอมเมนต์เพื่อรัน:
-- ALTER TABLE student_essays DROP COLUMN essay_title;
-- ALTER TABLE student_essays DROP COLUMN essay_content;
