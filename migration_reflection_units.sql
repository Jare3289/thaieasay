-- =====================================================================
-- Migration: ทำให้เครื่องมือสะท้อนคิดแยกตาม "หน่วยการเรียน" (หน่วยที่ 1 / 2)
-- เดิมทั้ง 3 ตารางเก็บ 1 แถวต่อนักเรียน (student_id เป็น PRIMARY KEY)
-- ทำให้บันทึกของหน่วยที่ 1 กับหน่วยที่ 2 ทับกัน และรายงานแสดงซ้ำผิด
-- แก้เป็นคีย์ผสม (student_id, task_unit) เพื่อเก็บแยกรายหน่วยได้
-- รันครั้งเดียวบนฐานข้อมูลเดิม (ปลอดภัยกับข้อมูลที่มีอยู่ = ถือเป็นหน่วยที่ 1)
-- =====================================================================

-- 1) ตารางปัญหาการเขียน (Writing Problems)
ALTER TABLE writing_problems
    ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id;
ALTER TABLE writing_problems DROP PRIMARY KEY;
ALTER TABLE writing_problems ADD PRIMARY KEY (student_id, task_unit);

-- 2) ตารางตรวจสอบตนเอง (Self Checklists)
ALTER TABLE self_checklists
    ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id;
ALTER TABLE self_checklists DROP PRIMARY KEY;
ALTER TABLE self_checklists ADD PRIMARY KEY (student_id, task_unit);

-- 3) ตารางสะท้อนการเรียนรู้ (Learning Reflections)
ALTER TABLE learning_reflections
    ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id;
ALTER TABLE learning_reflections DROP PRIMARY KEY;
ALTER TABLE learning_reflections ADD PRIMARY KEY (student_id, task_unit);
