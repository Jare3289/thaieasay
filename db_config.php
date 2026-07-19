<?php
// ไฟล์กำหนดค่าเชื่อมต่อฐานข้อมูล MySQL ด้วย PDO

$db_host = 'sql309.infinityfree.com';
$db_name = 'if0_42376188_thaieasay';
$db_user = 'if0_42376188';
$db_pass = 'wEBv1Ea42V';

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // ย้ายโครงสร้างข้อมูลแบบอัตโนมัติ (Auto Migration) สำหรับเครื่องมือการประเมินและการสะท้อนคิดเพิ่มเติม
    $pdo->exec("
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

        CREATE TABLE IF NOT EXISTS self_checklists (
            student_id VARCHAR(10) PRIMARY KEY,
            check_1_1 VARCHAR(50) NOT NULL,
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

        CREATE TABLE IF NOT EXISTS peer_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(10) NOT NULL,
            reviewer_id VARCHAR(10) NOT NULL,
            score_1_1 VARCHAR(50) NOT NULL,
            score_1_2 VARCHAR(50) NOT NULL,
            score_1_3 VARCHAR(50) NOT NULL,
            score_1_4 VARCHAR(50) NOT NULL,
            score_2_1 VARCHAR(50) NOT NULL,
            score_2_2 VARCHAR(50) NOT NULL,
            score_3_1 VARCHAR(50) NOT NULL,
            score_3_2 VARCHAR(50) NOT NULL,
            score_3_3 VARCHAR(50) NOT NULL,
            score_3_4 VARCHAR(50) NOT NULL,
            score_4_1 VARCHAR(50) NOT NULL,
            score_4_2 VARCHAR(50) NOT NULL,
            score_4_3 VARCHAR(50) NOT NULL,
            strength TEXT,
            improvement TEXT,
            encouragement TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id) REFERENCES students(student_id) ON DELETE CASCADE,
            UNIQUE KEY unique_peer_review (student_id, reviewer_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS learning_reflections (
            student_id VARCHAR(10) PRIMARY KEY,
            content_structure TEXT,
            language_mechanics TEXT,
            feedback_applied TEXT,
            future_goals TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // เพิ่มคอลัมน์คำแนะนำเชิงคุณภาพจากเพื่อน (ถ้ายังไม่มี)
    try { $pdo->exec("ALTER TABLE evaluations ADD COLUMN peer_strength TEXT NULL AFTER quality_level"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE evaluations ADD COLUMN peer_improvement TEXT NULL AFTER peer_strength"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE evaluations ADD COLUMN peer_encouragement TEXT NULL AFTER peer_improvement"); } catch (PDOException $e) {}

    // Add test_phase column and modify unique index unique_eval
    try { $pdo->exec("ALTER TABLE evaluations ADD COLUMN test_phase VARCHAR(20) DEFAULT 'posttest' AFTER evaluator_name"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE evaluations DROP INDEX unique_eval"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE evaluations ADD UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name, test_phase)"); } catch (PDOException $e) {}

    // เพิ่มคอลัมน์ created_at ในกรณีที่ตารางถูกสร้างในเวอร์ชันเก่าที่ยังไม่มีคอลัมน์นี้
    try { $pdo->exec("ALTER TABLE writing_problems ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE self_checklists ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE peer_reviews ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE learning_reflections ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"); } catch (PDOException $e) {}
} catch (PDOException $e) {
    // ส่งข้อมูลข้อผิดพลาดกลับเป็น JSON กรณีเรียกใช้ผ่าน AJAX
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}
