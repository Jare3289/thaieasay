<?php
// ไฟล์กำหนดค่าเชื่อมต่อฐานข้อมูล MySQL ด้วย PDO

$db_host = 'sql309.infinityfree.com';
$db_name = 'if0_42376188_thaieasay';
$db_user = 'if0_42376188';
$db_pass = 'wEBv1Ea42V';

/**
 * แจ้งข้อผิดพลาดฐานข้อมูลแบบเหมาะกับชนิดของ Request
 * - ถ้าเป็นการเรียกผ่าน API/AJAX ให้ตอบเป็น JSON
 * - ถ้าเป็นการเปิดหน้าเว็บปกติ ให้แสดงข้อความภาษาไทยที่อ่านง่าย (ไม่ปล่อยให้เป็น 500 ดิบ ๆ)
 */
function db_fail_response($message) {
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $isAjax = (strpos($script, 'api.php') !== false)
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if (!headers_sent()) {
        header('Content-Type: ' . ($isAjax ? 'application/json' : 'text/html') . '; charset=utf-8');
    }

    if ($isAjax) {
        echo json_encode(['success' => false, 'error' => $message]);
    } else {
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
            . '<title>ระบบขัดข้องชั่วคราว</title></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:60px 20px;color:#333;">'
            . '<div style="font-size:48px;margin-bottom:16px;">🛠️</div>'
            . '<h2>ขออภัย ระบบเชื่อมต่อฐานข้อมูลไม่ได้ชั่วคราว</h2>'
            . '<p style="color:#666;">กรุณารีเฟรชหน้านี้อีกครั้งในอีกสักครู่ หากยังพบปัญหาโปรดแจ้งผู้ดูแลระบบ</p>'
            . '</body></html>';
    }
    exit;
}

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // ต่อฐานข้อมูลไม่ได้ → ตอบกลับให้เหมาะกับชนิด Request แล้วหยุด (ไม่ปล่อยเป็น 500)
    db_fail_response('Database connection failed: ' . $e->getMessage());
}

/**
 * ---------------------------------------------------------------------------
 * ตั้งค่าเซิร์ฟเวอร์ OCR (baidu/Unlimited-OCR)
 * ---------------------------------------------------------------------------
 * โมเดล Unlimited-OCR รันบนเซิร์ฟเวอร์ GPU และเปิดให้เรียกผ่าน API แบบ
 * OpenAI-compatible (ผ่าน vLLM หรือ SGLang) — เว็บ PHP นี้จะทำหน้าที่เป็น
 * "ตัวกลาง" ส่งรูปภาพไปถอดข้อความแล้วรับผลกลับมา
 *
 * ตั้งค่าได้ 2 วิธี (ไม่ต้องแก้โค้ด):
 *   1) ตั้งค่า Environment Variable: OCR_API_URL, OCR_API_KEY, OCR_MODEL
 *   2) แก้ค่า default ด้านล่างนี้ให้ตรงกับเซิร์ฟเวอร์ที่โฮสต์โมเดลไว้
 */
if (!defined('OCR_API_URL')) {
    // ปลายทาง endpoint แบบ OpenAI-compatible (chat completions)
    $ocr_url = getenv('OCR_API_URL');
    // ค่าเริ่มต้นตรงกับคำสั่งรันของ SGLang (port 10000) ตามคู่มือ Unlimited-OCR
    // ดูวิธีตั้งเซิร์ฟเวอร์ได้ที่ไฟล์ OCR_SETUP.md
    define('OCR_API_URL', $ocr_url !== false && $ocr_url !== ''
        ? $ocr_url
        : 'http://127.0.0.1:10000/v1/chat/completions');
}
if (!defined('OCR_API_KEY')) {
    $ocr_key = getenv('OCR_API_KEY');
    define('OCR_API_KEY', $ocr_key !== false ? $ocr_key : 'EMPTY');
}
if (!defined('OCR_MODEL')) {
    $ocr_model = getenv('OCR_MODEL');
    // ต้องตรงกับ --served-model-name ตอนรันเซิร์ฟเวอร์ (SGLang ตัวอย่างใช้ "Unlimited-OCR")
    define('OCR_MODEL', $ocr_model !== false && $ocr_model !== ''
        ? $ocr_model
        : 'Unlimited-OCR');
}

/**
 * ---------------------------------------------------------------------------
 * ตั้งค่า OCR.space (บริการ OCR ออนไลน์ คีย์ฟรี — แม่นกว่า Tesseract โดยเฉพาะภาษาไทย)
 * ---------------------------------------------------------------------------
 * วิธีใช้: สมัครคีย์ฟรีที่ https://ocr.space/ocrapi/freekey (ไม่ต้องใช้บัตร)
 * แล้วนำคีย์มาใส่ที่ OCRSPACE_API_KEY (หรือตั้งเป็น Environment Variable)
 *
 * - ถ้าใส่คีย์ไว้     → หน้าเว็บจะใช้ OCR.space อ่าน (แม่นขึ้น)
 * - ถ้าเว้นว่าง ('') → หน้าเว็บจะใช้ Tesseract อ่านในเครื่องแทนโดยอัตโนมัติ
 *
 * ดูรายละเอียดเพิ่มเติมได้ที่ไฟล์ OCR_SETUP.md
 */
if (!defined('OCRSPACE_API_KEY')) {
    $ocrspace_key = getenv('OCRSPACE_API_KEY');
    // ▼▼▼ ใส่คีย์ฟรีของคุณตรงนี้ (ในเครื่องหมายคำพูด) เพื่อเปิดใช้ OCR.space ▼▼▼
    define('OCRSPACE_API_KEY', $ocrspace_key !== false && $ocrspace_key !== ''
        ? $ocrspace_key
        : 'K83201554088957');
}
if (!defined('OCRSPACE_URL')) {
    $ocrspace_url = getenv('OCRSPACE_URL');
    define('OCRSPACE_URL', $ocrspace_url !== false && $ocrspace_url !== ''
        ? $ocrspace_url
        : 'https://api.ocr.space/parse/image');
}
if (!defined('OCRSPACE_LANGUAGE')) {
    $ocrspace_lang = getenv('OCRSPACE_LANGUAGE');
    // 'tha' = ภาษาไทย (รองรับใน OCR Engine 1)
    define('OCRSPACE_LANGUAGE', $ocrspace_lang !== false && $ocrspace_lang !== ''
        ? $ocrspace_lang
        : 'tha');
}
if (!defined('OCRSPACE_ENGINE')) {
    $ocrspace_engine = getenv('OCRSPACE_ENGINE');
    // Engine 1 รองรับภาษาไทย; Engine 2 เหมาะกับภาษาละติน
    define('OCRSPACE_ENGINE', $ocrspace_engine !== false && $ocrspace_engine !== ''
        ? $ocrspace_engine
        : '1');
}

/**
 * รันคำสั่ง DDL แบบปลอดภัย (ไม่ให้ล้มทั้งหน้าเมื่อคำสั่งใดคำสั่งหนึ่งผิดพลาด)
 */
function safe_ddl(PDO $pdo, $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // เงียบไว้ — โครงสร้างที่มีอยู่แล้ว/สิทธิ์บางอย่างบนโฮสต์ฟรีไม่ควรทำให้ทั้งเว็บพัง
    }
}

// ตรวจสอบแบบเบา ๆ ว่าโครงสร้างตารางถูกติดตั้งครบและมีข้อมูลนักเรียนแล้วหรือยัง
// ถ้าครบแล้วให้ข้ามขั้นตอน migration ทั้งหมด เพื่อลดภาระฐานข้อมูลในทุก request (สำคัญมากบนโฮสต์ฟรี)
$needs_migration = true;
try {
    // ใช้คอลัมน์ classroom (เวอร์ชันล่าสุด) เป็นตัวชี้วัด — ถ้ายังไม่มี แปลว่าต้องรัน migration เพื่อสร้าง/เพิ่มส่วนที่ขาด
    $check = $pdo->query("SHOW TABLES LIKE 'student_essays'");
    if ($check && $check->fetch()) {
        $col = $pdo->query("SHOW COLUMNS FROM students LIKE 'classroom'");
        $hasClassroom = $col && $col->fetch();
        $cnt = $pdo->query("SELECT COUNT(*) AS c FROM students")->fetch();
        if ($hasClassroom && $cnt && (int)$cnt['c'] > 0) {
            $needs_migration = false;
        }
    }
} catch (PDOException $e) {
    $needs_migration = true;
}

if ($needs_migration) {
    // 0) ตารางพื้นฐานที่ตารางอื่นอ้างอิงถึงด้วย FOREIGN KEY ต้องมีก่อนเสมอ
    safe_ddl($pdo, "
        CREATE TABLE IF NOT EXISTS students (
            student_id VARCHAR(10) PRIMARY KEY,
            student_name VARCHAR(150) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // เติมรายชื่อนักเรียนอัตโนมัติ (ไม่ต้อง import schema.sql เอง) — ใช้ ON DUPLICATE KEY เพื่อให้รันซ้ำได้ปลอดภัย
    safe_ddl($pdo, "
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
        ON DUPLICATE KEY UPDATE student_name = VALUES(student_name)
    ");

    safe_ddl($pdo, "
        CREATE TABLE IF NOT EXISTS evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            student_id VARCHAR(10) NOT NULL,
            evaluator_type VARCHAR(50) NOT NULL,
            evaluator_name VARCHAR(150) NOT NULL,
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
    ");

    // 1) ตารางเครื่องมือการประเมินและการสะท้อนคิดเพิ่มเติม (แยกรันทีละคำสั่งให้ปลอดภัย)
    safe_ddl($pdo, "
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
    ");

    safe_ddl($pdo, "
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
    ");

    safe_ddl($pdo, "
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
    ");

    safe_ddl($pdo, "
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

    // ตารางบันทึกเรียงความของนักเรียน (Essay Writer) — ใช้โดย essay_writer.php และ api.php (save_essay/get_essay)
    safe_ddl($pdo, "
        CREATE TABLE IF NOT EXISTS student_essays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(10) NOT NULL,
            essay_phase VARCHAR(20) NOT NULL DEFAULT 'task1',
            essay_title VARCHAR(255) DEFAULT NULL,
            essay_content LONGTEXT,
            word_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
            UNIQUE KEY unique_student_essay (student_id, essay_phase)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2) เพิ่มคอลัมน์คำแนะนำเชิงคุณภาพจากเพื่อน (ถ้ายังไม่มี)
    safe_ddl($pdo, "ALTER TABLE evaluations ADD COLUMN peer_strength TEXT NULL AFTER quality_level");
    safe_ddl($pdo, "ALTER TABLE evaluations ADD COLUMN peer_improvement TEXT NULL AFTER peer_strength");
    safe_ddl($pdo, "ALTER TABLE evaluations ADD COLUMN peer_encouragement TEXT NULL AFTER peer_improvement");

    // 3) เพิ่มคอลัมน์ test_phase และปรับ unique index unique_eval
    safe_ddl($pdo, "ALTER TABLE evaluations ADD COLUMN test_phase VARCHAR(20) DEFAULT 'posttest' AFTER evaluator_name");
    safe_ddl($pdo, "ALTER TABLE evaluations DROP INDEX unique_eval");
    safe_ddl($pdo, "ALTER TABLE evaluations ADD UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name, test_phase)");

    // 3.5) เพิ่มคอลัมน์ห้องเรียน + กลุ่ม (ทดลอง/ตัวอย่าง) ในตารางนักเรียน
    safe_ddl($pdo, "ALTER TABLE students ADD COLUMN classroom VARCHAR(20) DEFAULT NULL");
    safe_ddl($pdo, "ALTER TABLE students ADD COLUMN student_group VARCHAR(30) DEFAULT NULL");

    // 4) เพิ่มคอลัมน์ created_at สำหรับตารางเวอร์ชันเก่าที่ยังไม่มีคอลัมน์นี้
    safe_ddl($pdo, "ALTER TABLE writing_problems ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    safe_ddl($pdo, "ALTER TABLE self_checklists ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    safe_ddl($pdo, "ALTER TABLE peer_reviews ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    safe_ddl($pdo, "ALTER TABLE learning_reflections ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
}

// ตารางจับคู่ประเมินเพื่อน (peer_pairs) — ตรวจแยกจาก migration หลัก
// เพราะเป็นฟีเจอร์ที่เพิ่มภายหลัง ต้องสร้างให้ระบบที่ migration หลักผ่านไปแล้วด้วย
try {
    $has_pairs = $pdo->query("SHOW TABLES LIKE 'peer_pairs'");
    if (!$has_pairs || $has_pairs->rowCount() === 0) {
        safe_ddl($pdo, "
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
        ");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}
