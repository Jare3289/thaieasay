-- ==========================================================================
-- คิวรีอ้างอิงสำหรับงานวิจัย: Inter-rater Reliability (ICC) และ Paired t-test
-- ดึงข้อมูลจากตารางที่มีอยู่แล้ว (students, evaluations) ไม่ต้องสร้างตารางใหม่
--
-- กลุ่มทดลอง (Experimental Group) ของงานวิจัยชุดนี้ = นักเรียนห้อง 606
-- (ตั้งค่าคอลัมน์ classroom = '606' ให้นักเรียนแต่ละคนได้ที่หน้า "จัดการข้อมูลนักเรียน")
--
-- ใช้ประกอบรายงานวิจัย / ตรวจทานตัวเลขที่หน้า research_analysis.php
-- หรือรันตรงผ่านเครื่องมือจัดการฐานข้อมูล (phpMyAdmin ฯลฯ) เพื่อส่งออกดิบไปวิเคราะห์ต่อใน SPSS/Excel
-- ==========================================================================

USE `if0_42376188_thaieasay`;

-- --------------------------------------------------------------------------
-- ตั้งค่าห้องเรียนให้นักเรียนทุกคนในฐานข้อมูลเป็นห้อง 606 (กลุ่มทดลอง)
-- รันครั้งเดียวเพื่ออัปเดตนักเรียนทุกแถว ไม่ว่าตอนนี้ classroom จะเป็นค่าอะไรหรือว่างอยู่ก็ตาม
-- --------------------------------------------------------------------------
UPDATE students
SET classroom = '606';

-- --------------------------------------------------------------------------
-- ลบคำนำหน้าชื่อ (นาย/นาง/นางสาว/ด.ช./ด.ญ./เด็กชาย/เด็กหญิง ฯลฯ) ออกจาก student_name
-- ของนักเรียนทุกคนแบบถาวรในฐานข้อมูล (ใช้ pattern เดียวกับฟังก์ชัน formatNamePrefix() ใน auth_helper.php
-- ที่แอปใช้ตัดคำนำหน้าตอนแสดงผลอยู่แล้ว แต่คำสั่งนี้แก้ไขข้อมูลดิบในตารางโดยตรง)
--
-- คำเตือน: เป็นการแก้ไขถาวร ย้อนกลับไม่ได้ — สำรองข้อมูลตาราง students ก่อนรัน
-- ต้องการ MariaDB/MySQL ที่รองรับ REGEXP_REPLACE (MariaDB 10.0.5+ / MySQL 8.0+)
-- --------------------------------------------------------------------------
UPDATE students
SET student_name = TRIM(
    REGEXP_REPLACE(
        student_name,
        '^(นางสาว|นาย|นาง|ด\\.ช\\.|ด\\.ญ\\.|เด็กชาย|เด็กหญิง)[[:space:]]*',
        ''
    )
);

-- --------------------------------------------------------------------------
-- 0) รายชื่อนักเรียนกลุ่มทดลอง (ห้อง 606)
-- --------------------------------------------------------------------------
SELECT student_id, student_name, classroom, student_group
FROM students
WHERE classroom = '606'
ORDER BY student_id ASC;

-- --------------------------------------------------------------------------
-- 1) ข้อมูลดิบสำหรับคำนวณ ICC (Inter-rater Reliability)
--    คะแนนรวม + 4 ด้าน ที่ให้โดยผู้ตรวจ 3 คน (ครูผู้สอน + ผู้เชี่ยวชาญ 2 ท่าน)
--    เฉพาะนักเรียนห้อง 606 ที่มีผลตรวจครบทั้ง 3 คนในภารงานเดียวกัน
--    เปลี่ยนค่า test_phase ด้านล่างเป็น 'task2' เพื่อดูภารงานหน่วยที่ 2
-- --------------------------------------------------------------------------
SELECT
    s.student_id,
    s.student_name,

    -- คะแนนรวม (เต็ม 60) ของผู้ตรวจแต่ละคน
    MAX(CASE WHEN e.evaluator_type = 'ครูประเมิน' THEN e.total_score END)                                        AS teacher_total,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 1', 'admin1') THEN e.total_score END)                        AS expert1_total,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 2', 'admin2') THEN e.total_score END)                        AS expert2_total,

    -- ด้านเนื้อหา (1.1+1.2+1.3, เต็ม 27) ของผู้ตรวจแต่ละคน
    MAX(CASE WHEN e.evaluator_type = 'ครูประเมิน' THEN e.score_1_1 + e.score_1_2 + e.score_1_3 END)               AS teacher_content,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 1', 'admin1') THEN e.score_1_1 + e.score_1_2 + e.score_1_3 END) AS expert1_content,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 2', 'admin2') THEN e.score_1_1 + e.score_1_2 + e.score_1_3 END) AS expert2_content,

    -- ด้านโครงสร้าง (2.1+2.2, เต็ม 12) ของผู้ตรวจแต่ละคน
    MAX(CASE WHEN e.evaluator_type = 'ครูประเมิน' THEN e.score_2_1 + e.score_2_2 END)                             AS teacher_structure,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 1', 'admin1') THEN e.score_2_1 + e.score_2_2 END)             AS expert1_structure,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 2', 'admin2') THEN e.score_2_1 + e.score_2_2 END)             AS expert2_structure,

    -- ด้านการใช้ภาษา (3.1+3.2+3.3, เต็ม 15) ของผู้ตรวจแต่ละคน
    MAX(CASE WHEN e.evaluator_type = 'ครูประเมิน' THEN e.score_3_1 + e.score_3_2 + e.score_3_3 END)               AS teacher_language,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 1', 'admin1') THEN e.score_3_1 + e.score_3_2 + e.score_3_3 END) AS expert1_language,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 2', 'admin2') THEN e.score_3_1 + e.score_3_2 + e.score_3_3 END) AS expert2_language,

    -- ด้านอักขรวิธี (4.1+4.2+4.3, เต็ม 6) ของผู้ตรวจแต่ละคน
    MAX(CASE WHEN e.evaluator_type = 'ครูประเมิน' THEN e.score_4_1 + e.score_4_2 + e.score_4_3 END)               AS teacher_mechanics,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 1', 'admin1') THEN e.score_4_1 + e.score_4_2 + e.score_4_3 END) AS expert1_mechanics,
    MAX(CASE WHEN e.evaluator_name IN ('ผู้เชี่ยวชาญ 2', 'admin2') THEN e.score_4_1 + e.score_4_2 + e.score_4_3 END) AS expert2_mechanics

FROM students s
JOIN evaluations e ON e.student_id = s.student_id
WHERE s.classroom = '606'
  AND e.test_phase = 'task1'                      -- เปลี่ยนเป็น 'task2' สำหรับภารงานหน่วยที่ 2
  AND e.evaluator_type IN ('ครูประเมิน', 'ผู้เชี่ยวชาญประเมิน')
GROUP BY s.student_id, s.student_name
HAVING teacher_total IS NOT NULL
   AND expert1_total IS NOT NULL
   AND expert2_total IS NOT NULL                   -- เก็บเฉพาะแถวที่ผู้ตรวจครบทั้ง 3 คน (ใช้คำนวณ ICC ได้จริง)
ORDER BY s.student_id ASC;

-- --------------------------------------------------------------------------
-- 2) ข้อมูลดิบสำหรับ Paired t-test (One-Group Pretest-Posttest Design)
--    คะแนนรวมของคุณครูผู้สอน ก่อนเรียน (Pretest) เทียบ หลังเรียน (Posttest)
--    เฉพาะนักเรียนห้อง 606 ที่มีผลประเมินครบทั้งสองรอบ
-- --------------------------------------------------------------------------
SELECT
    s.student_id,
    s.student_name,
    MAX(CASE WHEN e.test_phase = 'pretest'  THEN e.total_score END) AS pretest_score,
    MAX(CASE WHEN e.test_phase = 'posttest' THEN e.total_score END) AS posttest_score,
    MAX(CASE WHEN e.test_phase = 'posttest' THEN e.total_score END)
      - MAX(CASE WHEN e.test_phase = 'pretest' THEN e.total_score END) AS gain_score
FROM students s
JOIN evaluations e ON e.student_id = s.student_id
WHERE s.classroom = '606'
  AND e.evaluator_type = 'ครูประเมิน'
  AND e.test_phase IN ('pretest', 'posttest')
GROUP BY s.student_id, s.student_name
HAVING pretest_score IS NOT NULL
   AND posttest_score IS NOT NULL                  -- เก็บเฉพาะคู่ที่มีทั้งก่อนเรียนและหลังเรียน (จับคู่ได้จริง)
ORDER BY s.student_id ASC;

-- --------------------------------------------------------------------------
-- 3) สรุปตัวเลขพื้นฐานของ Paired t-test (N, Mean, SD) ต่อยอดจากคิวรีข้อ 2
--    หมายเหตุ: ค่าสถิติ t และ p-value ยังต้องคำนวณต่อ (เช่นในหน้า research_analysis.php
--    หรือโปรแกรมสถิติภายนอกอย่าง SPSS/R) เพราะ SQL มาตรฐานไม่มีฟังก์ชัน paired t-test ในตัว
-- --------------------------------------------------------------------------
SELECT
    COUNT(*)                                    AS n,
    ROUND(AVG(pretest_score), 2)                AS mean_pretest,
    ROUND(AVG(posttest_score), 2)               AS mean_posttest,
    ROUND(AVG(posttest_score - pretest_score), 2) AS mean_gain,
    ROUND(STDDEV_SAMP(posttest_score - pretest_score), 2) AS sd_gain
FROM (
    SELECT
        s.student_id,
        MAX(CASE WHEN e.test_phase = 'pretest'  THEN e.total_score END) AS pretest_score,
        MAX(CASE WHEN e.test_phase = 'posttest' THEN e.total_score END) AS posttest_score
    FROM students s
    JOIN evaluations e ON e.student_id = s.student_id
    WHERE s.classroom = '606'
      AND e.evaluator_type = 'ครูประเมิน'
      AND e.test_phase IN ('pretest', 'posttest')
    GROUP BY s.student_id
    HAVING pretest_score IS NOT NULL AND posttest_score IS NOT NULL
) paired;
