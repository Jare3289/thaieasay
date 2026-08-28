-- =====================================================================
-- Migration: ทำให้เครื่องมือสะท้อนคิดแยกตาม "หน่วยการเรียน" (หน่วยที่ 1 / 2)
-- เดิมทั้ง 3 ตารางเก็บ 1 แถวต่อนักเรียน (student_id เป็น PRIMARY KEY)
-- ทำให้บันทึกของหน่วยที่ 1 กับหน่วยที่ 2 ทับกัน และรายงานแสดงซ้ำผิด
-- แก้เป็นคีย์ผสม (student_id, task_unit) เพื่อเก็บแยกรายหน่วยได้
-- รันครั้งเดียวบนฐานข้อมูลเดิม (ปลอดภัยกับข้อมูลที่มีอยู่ = ถือเป็นหน่วยที่ 1)
--
-- สำคัญ: ห้ามแยก DROP PRIMARY KEY ออกจาก ADD PRIMARY KEY เป็นคนละคำสั่ง
-- เพราะทั้ง 3 ตารางมี FOREIGN KEY (student_id) และ PRIMARY เป็นอินเด็กซ์เดียว
-- ที่ขึ้นต้นด้วย student_id คำสั่ง DROP PRIMARY KEY เดี่ยว ๆ จึงล้มเหลวเสมอ
-- บน InnoDB (error 1553: Cannot drop index 'PRIMARY': needed in a foreign key constraint)
-- ผลคือคอลัมน์ task_unit ถูกเพิ่มแต่คีย์หลักไม่เปลี่ยน → บันทึกหน่วยที่ 2
-- ไปเขียนทับแถวของหน่วยที่ 1 (ข้อมูลหน่วยที่ 2 หาย หน้าแรกขึ้นว่ายังไม่ได้ทำ)
-- การรวม DROP + ADD ไว้ในคำสั่งเดียว MySQL จะยังเห็นอินเด็กซ์ที่ขึ้นต้นด้วย
-- student_id ตลอดการทำงาน จึงไม่ติด error 1553
--
-- หมายเหตุ: db_config.php ตรวจและซ่อมให้อัตโนมัติทุก request อยู่แล้ว
-- ไฟล์นี้มีไว้สำหรับติดตั้งใหม่/ซ่อมด้วยมือผ่าน phpMyAdmin เท่านั้น
-- =====================================================================

-- 1) ตารางปัญหาการเขียน (Writing Problems)
ALTER TABLE writing_problems
    ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id;
ALTER TABLE writing_problems
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (student_id, task_unit);

-- 2) ตารางตรวจสอบตนเอง (Self Checklists)
ALTER TABLE self_checklists
    ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id;
ALTER TABLE self_checklists
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (student_id, task_unit);

-- 3) ตารางสะท้อนการเรียนรู้ (Learning Reflections)
ALTER TABLE learning_reflections
    ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id;
ALTER TABLE learning_reflections
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (student_id, task_unit);

-- ตรวจผลหลังรัน: ทั้ง 3 ตารางต้องขึ้น task_unit อยู่ในคีย์ PRIMARY
-- SHOW KEYS FROM writing_problems     WHERE Key_name = 'PRIMARY';
-- SHOW KEYS FROM self_checklists      WHERE Key_name = 'PRIMARY';
-- SHOW KEYS FROM learning_reflections WHERE Key_name = 'PRIMARY';
