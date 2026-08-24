<?php
// ไฟล์กำหนดค่าเชื่อมต่อฐานข้อมูล MySQL ด้วย PDO
//
// ค่าลับ (host/name/user/pass) เก็บไว้ในไฟล์ db_secrets.php ที่ "ไม่ถูก commit ขึ้น git"
// (ดู .gitignore) — เวลาย้ายโฮสต์/ติดตั้งใหม่ ให้คัดลอกไฟล์ db_secrets.sample.php
// เป็น db_secrets.php แล้วกรอกค่าจริงของเซิร์ฟเวอร์ลงไป
$db_host = $db_name = $db_user = $db_pass = '';
if (file_exists(__DIR__ . '/db_secrets.php')) {
    require __DIR__ . '/db_secrets.php';
}

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

// ยังไม่ได้ตั้งค่าฐานข้อมูล (ไม่มีไฟล์ db_secrets.php หรือกรอกไม่ครบ) → แจ้งเตือนแบบเข้าใจง่าย
if ($db_name === '' || $db_user === '') {
    db_fail_response('ยังไม่ได้ตั้งค่าฐานข้อมูล: กรุณาสร้างไฟล์ db_secrets.php '
        . '(คัดลอกจาก db_secrets.sample.php) แล้วกรอกค่าเชื่อมต่อจากเซิร์ฟเวอร์');
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
 * รันคำสั่ง DDL แบบปลอดภัย (ไม่ให้ล้มทั้งหน้าเมื่อคำสั่งใดคำสั่งหนึ่งผิดพลาด)
 */
function safe_ddl(PDO $pdo, $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // เงียบไว้ — โครงสร้างที่มีอยู่แล้ว/สิทธิ์บางอย่างบนโฮสต์ฟรีไม่ควรทำให้ทั้งเว็บพัง
    }
}

// ---- ตัวช่วยจับคู่ประเมินเพื่อน (นักเรียนจับคู่กันเอง) ----

// สร้างคู่ประเมินแบบไป-กลับ (A↔B) ในรอบที่กำหนด: A ตรวจ B และ B ตรวจ A
// พร้อมทำเครื่องหมายคำขอที่เกี่ยวข้องเป็น accepted และยกเลิกคำขอค้างอื่น ๆ ของทั้งสองคน
// เรียกจากภายใน transaction ที่เปิดไว้แล้ว (ไม่ commit เอง)
function peer_match_create_pair(PDO $pdo, $round, $a, $b) {
    // จับคู่ไป-กลับ (ทับของเดิมถ้ามี ด้วย unique key round+student_code)
    $ins = $pdo->prepare('
        INSERT INTO peer_pairs (round, student_code, partner_code)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE partner_code = VALUES(partner_code)
    ');
    $ins->execute([$round, $a, $b]);
    $ins->execute([$round, $b, $a]);

    // คำขอระหว่างสองคนนี้ → accepted
    $acc = $pdo->prepare('
        UPDATE peer_requests SET status = "accepted", responded_at = NOW()
        WHERE round = ? AND status = "pending"
          AND ((requester_code = ? AND target_code = ?) OR (requester_code = ? AND target_code = ?))
    ');
    $acc->execute([$round, $a, $b, $b, $a]);

    // ยกเลิกคำขอค้างอื่น ๆ ที่เกี่ยวกับสองคนนี้ (ทั้งที่ส่งออกและเข้ามา) เพราะจับคู่แล้ว
    $canc = $pdo->prepare('
        UPDATE peer_requests SET status = "cancelled", responded_at = NOW()
        WHERE round = ? AND status = "pending"
          AND (requester_code IN (?, ?) OR target_code IN (?, ?))
    ');
    $canc->execute([$round, $a, $b, $a, $b]);
}

// ---- ตัวช่วยเกี่ยวกับเรียงความ (คอลัมน์แยกส่วน + หัวข้อที่ครูกำหนด) ----

// แผนที่รอบเรียงความ → รอบหัวข้อ: ภาระงานมีร่าง D1/D2 แต่ใช้หัวข้อเดียวกันต่อหน่วย (task1_d1/task1_d2 → task1)
function essay_topic_phase($phase) {
    $phase = (string)$phase;
    if (strpos($phase, 'task1') === 0) return 'task1';
    if (strpos($phase, 'task2') === 0) return 'task2';
    return $phase; // pretest / posttest
}

// ประกอบเนื้อหาเรียงความจากคอลัมน์แยกส่วน (ส่วนนำ/เนื้อหา(หลายย่อหน้า)/สรุป) กลับเป็น JSON รูปแบบเดิม
// เพื่อความเข้ากันได้กับส่วนแสดงผล/ค้นหา/ส่งออกที่ยังอ่าน essay_content แบบ JSON
function essay_compose_content($intro, $bodyJson, $conclusion) {
    $body = [];
    if ($bodyJson !== null && $bodyJson !== '') {
        $decoded = json_decode((string)$bodyJson, true);
        if (is_array($decoded)) {
            $body = $decoded;
        } elseif (trim((string)$bodyJson) !== '') {
            $body = [(string)$bodyJson];
        }
    }
    $body = array_values(array_filter(array_map('strval', $body), function ($p) { return trim($p) !== ''; }));
    return json_encode([
        'introduction' => (string)($intro ?? ''),
        'body'         => $body,
        'conclusion'   => (string)($conclusion ?? ''),
    ], JSON_UNESCAPED_UNICODE);
}

// ดึงหัวข้อที่ครูกำหนดทุกรอบเป็น map [phase => topic] (มี cache ต่อ 1 request)
function essay_topics_map(PDO $pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = ['pretest' => '', 'task1' => '', 'task2' => '', 'posttest' => ''];
    try {
        $rows = $pdo->query("SELECT phase, topic FROM essay_topics")->fetchAll();
        foreach ($rows as $r) { $cache[$r['phase']] = (string)($r['topic'] ?? ''); }
    } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง */ }
    return $cache;
}

// ดึงสถานะเปิด/ปิดรับการส่งเรียงความของแต่ละรอบเป็น map [phase => bool]
// ค่าเริ่มต้น = เปิดรับ (true) เพื่อไม่ให้ระบบเดิมที่ยังไม่มีคอลัมน์ is_open ปิดกั้นการส่งโดยไม่ตั้งใจ
function essay_open_map(PDO $pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = ['pretest' => true, 'task1' => true, 'task2' => true, 'posttest' => true];
    try {
        $rows = $pdo->query("SELECT phase, is_open FROM essay_topics")->fetchAll();
        foreach ($rows as $r) {
            if (array_key_exists($r['phase'], $cache)) $cache[$r['phase']] = ((int)$r['is_open'] === 1);
        }
    } catch (Exception $e) { /* ตาราง/คอลัมน์อาจยังไม่ถูกสร้าง — ถือว่าเปิดรับทั้งหมด */ }
    return $cache;
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
            student_id VARCHAR(10) NOT NULL,
            task_unit TINYINT NOT NULL DEFAULT 1,
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
            PRIMARY KEY (student_id, task_unit),
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    safe_ddl($pdo, "
        CREATE TABLE IF NOT EXISTS self_checklists (
            student_id VARCHAR(10) NOT NULL,
            task_unit TINYINT NOT NULL DEFAULT 1,
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
            PRIMARY KEY (student_id, task_unit),
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
            student_id VARCHAR(10) NOT NULL,
            task_unit TINYINT NOT NULL DEFAULT 1,
            content_structure TEXT,
            language_mechanics TEXT,
            feedback_applied TEXT,
            future_goals TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (student_id, task_unit),
            FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // ตารางบันทึกเรียงความของนักเรียน (Essay Writer) — ใช้โดย essay_writer.php และ api.php (save_essay/get_essay)
    safe_ddl($pdo, "
        CREATE TABLE IF NOT EXISTS student_essays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(10) NOT NULL,
            essay_phase VARCHAR(20) NOT NULL DEFAULT 'task1_d1',
            intro_content TEXT NULL,
            body_content LONGTEXT NULL,
            conclusion_content TEXT NULL,
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

// ให้ตารางเครื่องมือสะท้อนคิด 3 ตารางมีอยู่เสมอ — ตรวจแยกจาก migration หลัก
// (สำคัญมาก: การสร้างตารางเหล่านี้เดิมอยู่ในบล็อก $needs_migration เท่านั้น
//  ถ้าฐานข้อมูลผ่าน migration หลักไปแล้วแต่ตารางเหล่านี้ยังไม่ถูกสร้าง/เคยถูกลบ
//  จะไม่มีวันถูกสร้างอีกเลย ทำให้ INSERT ตอนนักเรียนกดบันทึกล้มเหลว = ข้อมูลไม่เข้า DB)
//  ใช้ CREATE TABLE IF NOT EXISTS จึงปลอดภัยกับข้อมูลเดิม รันซ้ำได้
safe_ddl($pdo, "
    CREATE TABLE IF NOT EXISTS writing_problems (
        student_id VARCHAR(10) NOT NULL,
        task_unit TINYINT NOT NULL DEFAULT 1,
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
        PRIMARY KEY (student_id, task_unit),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
safe_ddl($pdo, "
    CREATE TABLE IF NOT EXISTS self_checklists (
        student_id VARCHAR(10) NOT NULL,
        task_unit TINYINT NOT NULL DEFAULT 1,
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
        PRIMARY KEY (student_id, task_unit),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
safe_ddl($pdo, "
    CREATE TABLE IF NOT EXISTS learning_reflections (
        student_id VARCHAR(10) NOT NULL,
        task_unit TINYINT NOT NULL DEFAULT 1,
        content_structure TEXT,
        language_mechanics TEXT,
        feedback_applied TEXT,
        future_goals TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (student_id, task_unit),
        FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// เพิ่มมิติ "หน่วยการเรียน" (task_unit) ให้เครื่องมือสะท้อนคิด — ตรวจแยกจาก migration หลัก
// (สำคัญมาก: ต้องรันแม้ระบบเดิมที่ migration หลักผ่านไปแล้ว มิฉะนั้นคิวรีที่อ้าง task_unit จะพัง)
// ทำ DDL จริงเพียงครั้งเดียว โดยเช็ค SHOW COLUMNS ก่อน แล้วเปลี่ยนคีย์หลักเป็น (student_id, task_unit)
foreach (['writing_problems', 'self_checklists', 'learning_reflections'] as $__reflTbl) {
    try {
        $__tblExists = $pdo->query("SHOW TABLES LIKE '" . $__reflTbl . "'");
        if (!$__tblExists || $__tblExists->rowCount() === 0) continue; // ตารางยังไม่ถูกสร้าง — ข้ามไปก่อน
        $__hasUnit = $pdo->query("SHOW COLUMNS FROM `$__reflTbl` LIKE 'task_unit'")->fetch();
        if (!$__hasUnit) {
            safe_ddl($pdo, "ALTER TABLE `$__reflTbl` ADD COLUMN task_unit TINYINT NOT NULL DEFAULT 1 AFTER student_id");
            // เปลี่ยนคีย์หลักจาก student_id เดี่ยว ๆ เป็นคีย์ผสม (student_id, task_unit) ในคำสั่งเดียวเพื่อความปลอดภัย
            // (ข้อมูลเดิมทั้งหมดถูกตั้งเป็นหน่วยที่ 1 โดยอัตโนมัติจากค่า DEFAULT 1)
            safe_ddl($pdo, "ALTER TABLE `$__reflTbl` DROP PRIMARY KEY, ADD PRIMARY KEY (student_id, task_unit)");
        }
    } catch (PDOException $e) { /* เงียบไว้ ไม่ให้กระทบการทำงานหลัก */ }
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

// ตารางคำขอจับคู่ประเมินเพื่อน (peer_requests) — นักเรียนขอจับคู่กันเอง
// นักเรียนฝ่ายหนึ่งส่งคำขอ (requester) อีกฝ่ายกดรับ (target) แล้วระบบจะสร้างคู่ไป-กลับ
// ใน peer_pairs ให้อัตโนมัติ แยกตามรอบ/หน่วย พอขึ้นรอบใหม่ต้องขอกันใหม่
try {
    $has_req = $pdo->query("SHOW TABLES LIKE 'peer_requests'");
    if (!$has_req || $has_req->rowCount() === 0) {
        safe_ddl($pdo, "
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
        ");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// Migration ย่อย: รองรับร่าง D1/D2 ของภาระงาน (Task Unit Drafts) — ตรวจแยกจาก migration หลัก
// ภาระงานรุ่นเก่าถูกเก็บเป็น 'task1'/'task2' (มีร่างเดียว) จึงย้ายให้เป็น "ร่างที่ 1 (D1)" = task1_d1 / task2_d1
// เพื่อให้เข้ากับคอลัมน์ D1/D2 ใหม่ในหน้า Essay Viewer และตัวเขียนเรียงความ
// ใช้ SELECT ... LIMIT 1 ตรวจก่อน จึงเบามาก — เมื่อย้ายครบแล้วจะไม่ทำงานอีก
try {
    $legacyEssay = $pdo->query("SELECT 1 FROM student_essays WHERE essay_phase IN ('task1','task2') LIMIT 1");
    if ($legacyEssay && $legacyEssay->fetch()) {
        // UPDATE IGNORE กันชนกับแถว _d1 ที่อาจมีอยู่แล้ว (unique student_id+essay_phase)
        safe_ddl($pdo, "UPDATE IGNORE student_essays SET essay_phase = 'task1_d1' WHERE essay_phase = 'task1'");
        safe_ddl($pdo, "UPDATE IGNORE student_essays SET essay_phase = 'task2_d1' WHERE essay_phase = 'task2'");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// Migration ย่อย: แยกเนื้อหาเรียงความออกเป็นคอลัมน์ ส่วนนำ / เนื้อหา / สรุป
// - intro_content        = ส่วนนำ (Introduction)
// - body_content         = ส่วนเนื้อหา (เก็บทุกย่อหน้าเป็น JSON array ไว้ในคอลัมน์เดียว)
// - conclusion_content   = ส่วนสรุป (Conclusion)
// พร้อมย้ายข้อมูลเดิมจากคอลัมน์ essay_content (JSON) มาใส่คอลัมน์ใหม่ (backfill ด้วย PHP เพื่อความเข้ากันได้ทุกโฮสต์)
try {
    $col = $pdo->query("SHOW COLUMNS FROM student_essays LIKE 'body_content'");
    if (!$col || $col->rowCount() === 0) {
        safe_ddl($pdo, "ALTER TABLE student_essays ADD COLUMN intro_content TEXT NULL AFTER essay_phase");
        safe_ddl($pdo, "ALTER TABLE student_essays ADD COLUMN body_content LONGTEXT NULL AFTER intro_content");
        safe_ddl($pdo, "ALTER TABLE student_essays ADD COLUMN conclusion_content TEXT NULL AFTER body_content");

        // backfill จาก essay_content เดิม (ถ้ามีคอลัมน์นั้น)
        try {
            $hasOld = $pdo->query("SHOW COLUMNS FROM student_essays LIKE 'essay_content'");
            if ($hasOld && $hasOld->rowCount() > 0) {
                $rows = $pdo->query("SELECT id, essay_content FROM student_essays WHERE essay_content IS NOT NULL AND essay_content <> ''")->fetchAll();
                $up = $pdo->prepare("UPDATE student_essays SET intro_content = ?, body_content = ?, conclusion_content = ? WHERE id = ?");
                foreach ($rows as $r) {
                    $obj = json_decode((string)$r['essay_content'], true);
                    if (is_array($obj) && isset($obj['introduction'])) {
                        $intro = (string)($obj['introduction'] ?? '');
                        $body  = (isset($obj['body']) && is_array($obj['body'])) ? array_values($obj['body']) : [];
                        $conc  = (string)($obj['conclusion'] ?? '');
                    } else {
                        // ข้อความล้วนรุ่นเก่า → ใส่ไว้ที่ส่วนนำ
                        $intro = (string)$r['essay_content']; $body = []; $conc = '';
                    }
                    $up->execute([$intro, json_encode($body, JSON_UNESCAPED_UNICODE), $conc, $r['id']]);
                }
            }
        } catch (Exception $e) { /* เงียบไว้ */ }
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// Migration ย่อย: คำนวณ word_count ของเรียงความที่บันทึกไว้แล้วใหม่ทั้งหมด
// ด้วยอัลกอริทึมตัดคำภาษาไทยที่ถูกต้อง (ICU word break iterator)
// เดิมนับคำด้วยการแยกช่องว่าง/ขึ้นบรรทัดใหม่เท่านั้น ซึ่งนับผิดมากสำหรับภาษาไทยที่ไม่มีช่องว่างคั่นคำ
// (เช่นทั้งย่อหน้าอาจถูกนับเป็นแค่ 1-2 คำ) ใช้คอลัมน์ word_count_recalculated เป็นตัวกันไม่ให้รันซ้ำ
try {
    $col = $pdo->query("SHOW COLUMNS FROM student_essays LIKE 'word_count_recalculated'");
    if (!$col || $col->rowCount() === 0) {
        safe_ddl($pdo, "ALTER TABLE student_essays ADD COLUMN word_count_recalculated TINYINT(1) NOT NULL DEFAULT 0 AFTER word_count");

        require_once __DIR__ . '/thai_text_utils.php';
        $rows = $pdo->query("SELECT id, intro_content, body_content, conclusion_content FROM student_essays")->fetchAll();
        $up = $pdo->prepare("UPDATE student_essays SET word_count = ?, word_count_recalculated = 1 WHERE id = ?");
        foreach ($rows as $r) {
            $bodyArr  = json_decode((string)$r['body_content'], true);
            $bodyText = is_array($bodyArr) ? implode("\n", $bodyArr) : (string)$r['body_content'];
            $allText  = trim(($r['intro_content'] ?? '') . "\n" . $bodyText . "\n" . ($r['conclusion_content'] ?? ''));
            $up->execute([count_thai_words($allText), $r['id']]);
        }
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// ตารางหัวข้อเรียงความที่ครูกำหนดต่อรอบ (ก่อนเรียน/หน่วยที่ 1/หลังเรียน)
// นักเรียนไม่ต้องกรอกชื่อเรื่องเอง — ใช้หัวข้อที่ครูกำหนดของแต่ละงานแทน
// คอลัมน์ is_open = ครูเปิด/ปิดรับการส่งเรียงความของรอบนั้น (1 = เปิดรับ, 0 = ปิดรับ)
try {
    $tp = $pdo->query("SHOW TABLES LIKE 'essay_topics'");
    if (!$tp || $tp->rowCount() === 0) {
        safe_ddl($pdo, "
            CREATE TABLE IF NOT EXISTS essay_topics (
                phase VARCHAR(20) PRIMARY KEY,
                topic VARCHAR(500) DEFAULT NULL,
                is_open TINYINT NOT NULL DEFAULT 1,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        // เตรียมแถวว่างของรอบหลักไว้ล่วงหน้า (ครูมากรอกหัวข้อภายหลัง)
        safe_ddl($pdo, "INSERT IGNORE INTO essay_topics (phase, topic) VALUES ('pretest', NULL), ('task1', NULL), ('task2', NULL), ('posttest', NULL)");
    } else {
        // ฐานข้อมูลเดิม: เพิ่มคอลัมน์ is_open หากยังไม่มี
        $hasOpen = $pdo->query("SHOW COLUMNS FROM essay_topics LIKE 'is_open'");
        if (!$hasOpen || $hasOpen->rowCount() === 0) {
            safe_ddl($pdo, "ALTER TABLE essay_topics ADD COLUMN is_open TINYINT NOT NULL DEFAULT 1");
        }
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}
