-- สคริปต์สร้างฐานข้อมูลระบบประเมินการเขียนเรียงความสำหรับระบบเซิร์ฟเวอร์จริง
USE `if0_42376188_thaieasay`;

-- 1. ตารางรายชื่อนักเรียน
CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(10) PRIMARY KEY,
    student_name VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ตารางเก็บผลการประเมิน
CREATE TABLE IF NOT EXISTS evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    student_id VARCHAR(10) NOT NULL,
    evaluator_type VARCHAR(50) NOT NULL, -- 'ตนเองประเมิน', 'เพื่อนประเมิน', 'ครูประเมิน'
    evaluator_name VARCHAR(150) NOT NULL,
    -- คะแนนแต่ละมิติก่อนถ่วงน้ำหนัก (ดิบ) หรือ คะแนนหลังถ่วงน้ำหนัก?
    -- ในโค้ดเดิมบันทึกเป็นคะแนนหลังถ่วงน้ำหนัก (คะแนนดิบ * multiplier)
    -- ดังนั้นเราจะเก็บคะแนนหลังคูณแล้วตามแบบเดิมเพื่อไม่ให้ต้องแก้สูตรหน้าบ้าน
    score_1_1 DECIMAL(5,2) NOT NULL,
    score_1_2 DECIMAL(5,2) NOT NULL,
    score_1_3 DECIMAL(5,2) NOT NULL,
    score_2_1 DECIMAL(5,2) NOT NULL,
    score_2_2 DECIMAL(5,2) NOT NULL,
    score_3_1 DECIMAL(5,2) NOT NULL,
    score_3_2 DECIMAL(5,2) NOT NULL,
    score_3_3 DECIMAL(5,2) NOT NULL,
    score_4_1 DECIMAL(5,2) NOT NULL,
    score_4_2 DECIMAL(5,2) NOT NULL,
    score_4_3 DECIMAL(5,2) NOT NULL,
    total_score DECIMAL(5,2) NOT NULL,
    quality_level VARCHAR(50) NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. นำข้อมูลนักเรียน 39 คนเข้าสู่ระบบ
INSERT INTO students (student_id, student_name) VALUES
('34317', 'นางสาว กชพร จันทร์พิลา'),
('34318', 'นางสาว กรวรรณ เรืองกาญจนชัย'),
('34355', 'นาย ธีธานนท์ ตุ้มทอง'),
('34356', 'นาย ปราณ สุขพิทักษ์'),
('34366', 'นางสาว เจวลิน ศรีบุญรักษ์'),
('34376', 'นางสาว ภภรกัญ คำวิเศษ'),
('34384', 'นาย พีรดนย์ พลอยกระจ่าง'),
('34415', 'นางสาว รติมา ผาสุข'),
('34425', 'นาย วีรภัทร บุญเย็น'),
('34490', 'นางสาว ฐิตารีย์ เรืองแสน'),
('34513', 'นางสาว ณัฐรวดี ใยกูล'),
('34540', 'นางสาว เพชรลดา ก้อนคำ'),
('34570', 'นางสาว ธนัชพร มากุญ'),
('34589', 'นาย ณัฐธนวัฒน์ ตรีเดชวงษ์ด้วง'),
('34591', 'นาย ธนกฤต พูม่วง'),
('34593', 'นาย ธนาวุฒิ เขียนสาร์'),
('34595', 'นาย รักษ์พงษ์ เพลาชัย'),
('34598', 'นาย ศุภรักษ์ นาคปั้น'),
('34599', 'นาย อัครวินท์ ชนาภาวรพงศ์'),
('34600', 'นางสาว กัญญาณัฐ ฝอยทอง'),
('34605', 'นางสาว ชินณิชา คำมุงคุล'),
('34607', 'นางสาว ทักษิกานต์ หรั่งบุรี'),
('34612', 'นางสาว ปาณิสรา บุตรเมือง'),
('34614', 'นางสาว พิชญ์สินี มหัคคะประทีป'),
('34619', 'นางสาว สิตานัน พวงแย้ม'),
('34621', 'นางสาว สุชานาถ พูลมี'),
('34622', 'นางสาว หฤทชนัน เหลาอ่อน'),
('34624', 'นางสาว อมิตรา ตันธีระพงศ์'),
('34625', 'นางสาว อันนากาญจน์ ขำสอาด'),
('34626', 'นาย กฤติพงศ์ ศรีสุข'),
('34652', 'นางสาว ปุณฑริกา จิ๋วไขว้'),
('34655', 'นางสาว มัณยาภา สนิทชาติ'),
('34732', 'นางสาว ธนพร ป้อมคำ'),
('34742', 'นางสาว สุกานต์ณัฐฐ์ ดิษฐประยูร'),
('34745', 'นางสาว อริสา สีสด'),
('36291', 'นาย ชนะวิต บุญคุ้มครอง'),
('36292', 'นางสาว กัญญาณัฐ กำจาย'),
('36293', 'นางสาว พัชราภา หลีเกษม'),
('36294', 'นางสาว เพ็ญสิริ เอี่ยมสำอางค์')
ON DUPLICATE KEY UPDATE student_name = VALUES(student_name);

-- 4. ตารางบันทึกปัญหาการเขียน (Writing Obstacles Record)
CREATE TABLE IF NOT EXISTS writing_problems (
    student_id VARCHAR(10) PRIMARY KEY,
    prob_1_1 TEXT, sol_1_1 TEXT,
    prob_1_2 TEXT, sol_1_2 TEXT,
    prob_1_3 TEXT, sol_1_3 TEXT,
    prob_2_1 TEXT, sol_2_1 TEXT,
    prob_2_2 TEXT, sol_2_2 TEXT,
    prob_3_1 TEXT, sol_3_1 TEXT,
    prob_3_2 TEXT, sol_3_2 TEXT,
    prob_3_3 TEXT, sol_3_3 TEXT,
    prob_4_1 TEXT, sol_4_1 TEXT,
    prob_4_2 TEXT, sol_4_2 TEXT,
    prob_4_3 TEXT, sol_4_3 TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ตารางรายการตรวจสอบตนเอง (Self-Checklist)
CREATE TABLE IF NOT EXISTS self_checklists (
    student_id VARCHAR(10) PRIMARY KEY,
    check_1_1 VARCHAR(50) NOT NULL, -- 'ครบถ้วน', 'บางส่วน', 'ต้องปรับปรุง'
    check_1_2 VARCHAR(50) NOT NULL,
    check_1_3 VARCHAR(50) NOT NULL,
    check_2_1 VARCHAR(50) NOT NULL,
    check_2_2 VARCHAR(50) NOT NULL,
    check_3_1 VARCHAR(50) NOT NULL,
    check_3_2 VARCHAR(50) NOT NULL,
    check_3_3 VARCHAR(50) NOT NULL,
    check_4_1 VARCHAR(50) NOT NULL,
    check_4_2 VARCHAR(50) NOT NULL,
    check_4_3 VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ตารางประเมินผลงานโดยเพื่อนพร้อมความคิดเห็นเชิงบรรยาย (Peer Qualitative Reviews)
CREATE TABLE IF NOT EXISTS peer_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(10) NOT NULL,  -- เจ้าของผลงาน (Owner)
    reviewer_id VARCHAR(10) NOT NULL, -- เพื่อนผู้ประเมิน (Reviewer)
    score_1_1 VARCHAR(50) NOT NULL, -- 'ดีมาก', 'ดี', 'ปานกลาง', 'พอใช้', 'ปรับปรุง'
    score_1_2 VARCHAR(50) NOT NULL,
    score_1_3 VARCHAR(50) NOT NULL,
    score_1_4 VARCHAR(50) NOT NULL, -- เอกภาพของเนื้อหา
    score_2_1 VARCHAR(50) NOT NULL,
    score_2_2 VARCHAR(50) NOT NULL,
    score_3_1 VARCHAR(50) NOT NULL,
    score_3_2 VARCHAR(50) NOT NULL,
    score_3_3 VARCHAR(50) NOT NULL,
    score_3_4 VARCHAR(50) NOT NULL, -- การใช้คำเชื่อม
    score_4_1 VARCHAR(50) NOT NULL,
    score_4_2 VARCHAR(50) NOT NULL,
    score_4_3 VARCHAR(50) NOT NULL,
    strength TEXT,      -- ส่วนที่ประทับใจและจุดแข็งของเรียงความ
    improvement TEXT,   -- จุดที่ควรปรับปรุงและข้อเสนอแนะ
    encouragement TEXT, -- ข้อความให้กำลังใจ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_peer_review (student_id, reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ตารางบันทึกการสะท้อนการเรียนรู้ (Learning Reflections)
CREATE TABLE IF NOT EXISTS learning_reflections (
    student_id VARCHAR(10) PRIMARY KEY,
    content_structure TEXT,  -- ด้านเนื้อหาสาระและองค์ประกอบ
    language_mechanics TEXT, -- ด้านการใช้สำนวนภาษาและอักขรวิธี
    feedback_applied TEXT,   -- การนำข้อเสนอแนะไปปรับปรุงงาน
    future_goals TEXT,       -- การประยุกต์ใช้และเป้าหมายในอนาคต
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ตารางจับคู่นักเรียนสำหรับการประเมินเพื่อน (Peer Pairing per round)
-- round: รอบการประเมิน เช่น 'pretest', 'task1', 'task2', 'posttest'
-- student_code: รหัสนักเรียนผู้ประเมิน (ผู้ให้คะแนน)
-- partner_code: รหัสนักเรียนคู่ที่ถูกประเมิน (เจ้าของผลงาน)
CREATE TABLE IF NOT EXISTS peer_pairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round VARCHAR(20) NOT NULL,
    student_code VARCHAR(10) NOT NULL,
    partner_code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_code) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (partner_code) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_peer_pair (round, student_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ตารางคำขอจับคู่ประเมินเพื่อน (Peer Matching Requests per round)
-- นักเรียนจับคู่กันเอง: ฝ่ายหนึ่งส่งคำขอ อีกฝ่ายกดรับ แล้วระบบสร้างคู่ไป-กลับใน peer_pairs อัตโนมัติ
-- requester_code: ผู้ส่งคำขอ (A)   target_code: ผู้รับคำขอ (B)
-- status: 'pending' (รอตอบรับ), 'accepted' (รับแล้ว), 'declined' (ปฏิเสธ), 'cancelled' (ยกเลิก)
-- แยกตามรอบ/หน่วย (round) — พอขึ้นรอบใหม่ต้องส่งคำขอกันใหม่
CREATE TABLE IF NOT EXISTS peer_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round VARCHAR(20) NOT NULL,
    requester_code VARCHAR(10) NOT NULL,
    target_code VARCHAR(10) NOT NULL,
    status VARCHAR(10) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (requester_code) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (target_code) REFERENCES students(student_id) ON DELETE CASCADE,
    UNIQUE KEY unique_peer_request (round, requester_code, target_code),
    INDEX idx_req_round_target (round, target_code),
    INDEX idx_req_round_requester (round, requester_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

