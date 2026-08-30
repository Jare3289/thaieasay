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

// ตรวจว่าเรียงความแถวนี้ "มีเนื้อหาจริง" หรือไม่ — ใช้เป็นกติกาเดียวกันทุกที่ที่นับว่าส่งงานแล้ว
//
// สำคัญ: body_content เก็บทุกย่อหน้าเป็น JSON array ค่าว่างจึงถูกบันทึกเป็นสตริง '[]' ไม่ใช่สตริงว่าง
// การเช็คด้วย SQL แบบ body_content <> '' จึงเป็นจริงเสมอ ทำให้ "แถวเปล่า" ถูกนับว่าส่งงานแล้ว
// (หน้ารายงานของครูขึ้นว่าส่งแล้ว แต่หน้าแรกของนักเรียนขึ้นว่ายังไม่ได้ทำ = ความคืบหน้าไม่ตรงกับข้อมูล)
function essay_has_content($intro, $bodyJson, $conclusion) {
    if (trim((string)($intro ?? '')) !== '')      return true;
    if (trim((string)($conclusion ?? '')) !== '') return true;

    $bodyJson = (string)($bodyJson ?? '');
    if (trim($bodyJson) === '') return false;
    $decoded = json_decode($bodyJson, true);
    if (is_array($decoded)) {
        foreach ($decoded as $para) {
            if (trim((string)$para) !== '') return true;
        }
        return false;
    }
    // ข้อมูลรุ่นเก่าที่ไม่ได้เก็บเป็น JSON — ถือเป็นข้อความล้วน
    return trim($bodyJson) !== '';
}

// รายชื่อคอลัมน์ปัญหา/แนวทางแก้ของตาราง writing_problems (11 เกณฑ์ย่อย)
function reflection_problem_columns() {
    $keys = ['1_1', '1_2', '1_3', '2_1', '2_2', '3_1', '3_2', '3_3', '4_1', '4_2', '4_3'];
    $cols = [];
    foreach ($keys as $k) { $cols[] = 'prob_' . $k; $cols[] = 'sol_' . $k; }
    return $cols;
}

// ตรวจว่าแถวบันทึกอุปสรรคปัญหาการเขียน "มีเนื้อหาจริง" หรือไม่
// (ฟอร์มเดิมกดบันทึกได้ทั้งที่ไม่ได้ติ๊กแถวใดเลย → ได้แถวที่ทุกช่องเป็น NULL
//  ถ้านับแค่ "มีแถว" ความคืบหน้าจะขึ้นว่าทำแล้วทั้งที่เปิดเข้าไปดูแล้วไม่มีข้อมูล)
function reflection_problems_has_content($row) {
    if (!is_array($row)) return false;
    foreach (reflection_problem_columns() as $col) {
        if (isset($row[$col]) && trim((string)$row[$col]) !== '') return true;
    }
    return false;
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
            test_phase VARCHAR(20) NOT NULL DEFAULT 'posttest',
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
            UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name, test_phase)
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
    safe_ddl($pdo, "ALTER TABLE evaluations ADD COLUMN test_phase VARCHAR(20) NOT NULL DEFAULT 'posttest' AFTER evaluator_name");
    // ต้องรวม DROP + ADD ไว้ในคำสั่งเดียว มิฉะนั้น InnoDB จะปฏิเสธ (error 1553)
    // เพราะ unique_eval เป็นอินเด็กซ์เดียวที่ FOREIGN KEY (student_id) ใช้อยู่
    safe_ddl($pdo, "ALTER TABLE evaluations DROP INDEX unique_eval, ADD UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name, test_phase)");

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

// ---------------------------------------------------------------------
// ซ่อมคีย์ unique ของตาราง evaluations ให้แยกตาม "รอบการประเมิน" (test_phase)
// ---------------------------------------------------------------------
// อาการของบั๊ก: คีย์เดิมคือ (student_id, evaluator_type, evaluator_name) ไม่มี test_phase
// เมื่อนักเรียน/เพื่อนคนเดิมประเมินคนเดิมในหน่วยที่ 2 คำสั่ง INSERT ... ON DUPLICATE KEY UPDATE
// จะไปชนแถวของหน่วยที่ 1 แล้วเขียนทับคะแนน โดย test_phase ยังเป็น 'task1' เหมือนเดิม
// ผลคือ "บันทึกแล้วคะแนนหน่วยที่ 2 หายไป" และรายการสิ่งที่ยังไม่ได้ทำไม่ติ๊กว่าเสร็จ
//
// migration เดิมอยู่ในบล็อก $needs_migration (ฐานข้อมูลที่ติดตั้งครบแล้วจะไม่มีวันรัน)
// และยังสั่ง DROP INDEX แยกจาก ADD UNIQUE ซึ่งล้มเหลวเสมอบน InnoDB
// เพราะ unique_eval เป็นอินเด็กซ์เดียวที่ขึ้นต้นด้วย student_id ซึ่ง FOREIGN KEY ใช้อยู่
// (MySQL error 1553) — safe_ddl กลืน error ไว้ ทำให้ไม่มีใครรู้ว่าคีย์ไม่เคยถูกแก้
// จึงต้องตรวจ + ซ่อมนอกบล็อก migration และรวมเป็นคำสั่ง ALTER เดียว
try {
    // คิวรีเดียวต่อ 1 request (metadata อย่างเดียว เบามาก): มี test_phase อยู่ในคีย์ unique_eval แล้วหรือยัง
    $__idxEval = $pdo->query("SHOW INDEX FROM evaluations WHERE Key_name = 'unique_eval' AND Column_name = 'test_phase'");
    if ($__idxEval && $__idxEval->rowCount() === 0) {
        // ยังไม่มี → ตรวจคอลัมน์ test_phase ก่อน (ฐานข้อมูลเก่าบางชุดอาจยังไม่มีคอลัมน์นี้)
        $__hasPhaseCol = $pdo->query("SHOW COLUMNS FROM evaluations LIKE 'test_phase'")->fetch();
        if (!$__hasPhaseCol) {
            safe_ddl($pdo, "ALTER TABLE evaluations ADD COLUMN test_phase VARCHAR(20) NOT NULL DEFAULT 'posttest' AFTER evaluator_name");
        }
        // DROP + ADD ในคำสั่งเดียว: ระหว่างทาง MySQL ยังเห็นอินเด็กซ์ที่ขึ้นต้นด้วย student_id
        // ให้ FOREIGN KEY ใช้ได้ตลอด จึงไม่ติด error 1553 เหมือนการสั่งแยกสองคำสั่ง
        try {
            $pdo->exec("ALTER TABLE evaluations
                DROP INDEX unique_eval,
                ADD UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name, test_phase)");
        } catch (PDOException $e) {
            // เผื่อฐานข้อมูลที่ไม่มีอินเด็กซ์ชื่อ unique_eval อยู่แล้ว → สร้างใหม่อย่างเดียว
            safe_ddl($pdo, "ALTER TABLE evaluations ADD UNIQUE KEY unique_eval (student_id, evaluator_type, evaluator_name, test_phase)");
        }
    }
} catch (PDOException $e) { /* ตารางยังไม่ถูกสร้าง/สิทธิ์ไม่พอ — เงียบไว้ ไม่ให้กระทบการทำงานหลัก */ }

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

// ตารางคำที่ถูก "ยืนยันว่าสะกดถูกต้อง" โดยนักเรียนหรือครู (ใช้กับระบบตรวจคำผิดของเรียงความ)
// คำในพจนานุกรมมาตรฐานมักไม่มีชื่อคน/คำสแลง/ศัพท์เฉพาะทาง ทำให้ถูกฟ้องว่า "อาจสะกดผิด" ทั้งที่ถูก
// เมื่อมีใครยืนยันคำใดคำหนึ่งแล้ว คำนั้นจะไม่ถูกฟ้องซ้ำอีกทั้งระบบ (รายการกลาง ใช้ร่วมกันทุกคน)
try {
    $tw = $pdo->query("SHOW TABLES LIKE 'thai_confirmed_words'");
    if (!$tw || $tw->rowCount() === 0) {
        safe_ddl($pdo, "
            CREATE TABLE IF NOT EXISTS thai_confirmed_words (
                word VARCHAR(191) PRIMARY KEY,
                confirmed_by VARCHAR(100) DEFAULT NULL,
                confirmed_role VARCHAR(20) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// ---------------------------------------------------------------------------
// ตารางของระบบ "ให้ข้อเสนอแนะเรียงความอัตโนมัติด้วย AI"
// ---------------------------------------------------------------------------
// 1) app_settings        : ค่าตั้งค่าทั่วไปของระบบ (ครูกรอกผ่านหน้าเว็บ) เช่น ผู้ให้บริการ AI / โมเดล / API key
// 2) essay_ai_feedback   : ผลการตรวจของ AI ต่อเรียงความ 1 ฉบับ (1 คน 1 รอบ = 1 แถว ตรวจใหม่ทับของเดิม)
// 3) ai_usage_log        : บันทึกการเรียกใช้ AI ไว้จำกัดโควตารายวันและไว้ให้ครูตรวจสอบย้อนหลัง
//
// สำคัญ: คะแนนจาก AI เก็บแยกจากตาราง evaluations ของงานวิจัย จึงไม่ปะปนกับคะแนนครู/เพื่อน/ตนเอง
try {
    // ตรวจจากตารางที่ถูกสร้าง "เป็นลำดับสุดท้าย" — ถ้าตารางก่อนหน้าสร้างไม่สำเร็จ ครั้งต่อไปจะลองใหม่ทั้งชุด
    $tAi = $pdo->query("SHOW TABLES LIKE 'ai_usage_log'");
    if (!$tAi || $tAi->rowCount() === 0) {
        safe_ddl($pdo, "
            CREATE TABLE IF NOT EXISTS app_settings (
                skey       VARCHAR(64) PRIMARY KEY,
                svalue     TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        safe_ddl($pdo, "
            CREATE TABLE IF NOT EXISTS essay_ai_feedback (
                id              INT AUTO_INCREMENT PRIMARY KEY,
                student_id      VARCHAR(10)  NOT NULL,
                essay_phase     VARCHAR(20)  NOT NULL,
                overall_comment TEXT,
                strengths       TEXT,          -- JSON array ของจุดแข็ง
                improvements    LONGTEXT,      -- JSON array ของ {criterion, issue, suggestion, example}
                next_steps      TEXT,          -- JSON array ของสิ่งที่ควรทำต่อ
                encouragement   TEXT,
                scores          TEXT,          -- JSON map รหัสเกณฑ์ => {raw, weighted, max, name, reason}
                teacher_scores  TEXT,          -- JSON map ของข้อที่ครูให้คะแนนเอง (AI ตรวจแทนไม่ได้)
                teacher_total   DECIMAL(6,2) NOT NULL DEFAULT 0,
                teacher_by      VARCHAR(50)  DEFAULT NULL,
                teacher_scored_at DATETIME   DEFAULT NULL,
                total_score     DECIMAL(6,2) NOT NULL DEFAULT 0,
                max_score       DECIMAL(6,2) NOT NULL DEFAULT 0,
                quality_level   VARCHAR(50)  DEFAULT NULL,
                provider        VARCHAR(30)  DEFAULT NULL,
                model           VARCHAR(100) DEFAULT NULL,
                requested_by    VARCHAR(50)  DEFAULT NULL,
                requested_role  VARCHAR(20)  DEFAULT NULL,
                raw_response    LONGTEXT,
                essay_hash      CHAR(40)     DEFAULT NULL,  -- ลายนิ้วมือเนื้อหาเรียงความ ณ ตอนที่ AI ตรวจ
                recheck_needed  TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = ต้นฉบับถูกแก้หลังตรวจ รอตรวจใหม่
                recheck_marked_at DATETIME   DEFAULT NULL,
                review_round    INT          NOT NULL DEFAULT 1,  -- ตรวจฉบับนี้เป็นครั้งที่เท่าไร (1 = ตรวจครั้งแรก)
                baseline_phase  VARCHAR(20)  DEFAULT NULL,  -- รอบงานฉบับตั้งต้นที่ใช้เทียบ (D1.2→D1.1, D2.2→D2.1, หลังเรียน→ก่อนเรียน)
                baseline_snapshot LONGTEXT,    -- JSON คะแนน/จุดที่ต้องแก้ของฉบับตั้งต้น ไว้เทียบว่าคะแนนดีขึ้นจริงไหม
                draft_comment   TEXT,          -- AI สรุปว่าฉบับนี้ต่างจากฉบับตั้งต้นอย่างไร
                draft_changes   LONGTEXT,      -- JSON array ของ {criterion, before, after, verdict, note}
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
                UNIQUE KEY unique_ai_feedback (student_id, essay_phase)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        safe_ddl($pdo, "
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
        ");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// เพิ่มคอลัมน์ "คะแนนที่ครูให้เอง" ให้ตาราง essay_ai_feedback (ฐานข้อมูลที่สร้างไว้ก่อนหน้านี้)
// ข้อ 4.3 ความเรียบร้อย/ลายมือ AI ตรวจจากไฟล์พิมพ์ไม่ได้ ครูจึงต้องกรอกคะแนนเองให้ครบเต็ม 60
try {
    $colTs = $pdo->query("SHOW COLUMNS FROM essay_ai_feedback LIKE 'teacher_scores'");
    if ($colTs && $colTs->rowCount() === 0) {
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN teacher_scores TEXT NULL AFTER scores");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN teacher_total DECIMAL(6,2) NOT NULL DEFAULT 0 AFTER teacher_scores");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN teacher_by VARCHAR(50) DEFAULT NULL AFTER teacher_total");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN teacher_scored_at DATETIME DEFAULT NULL AFTER teacher_by");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// เพิ่มคอลัมน์ "คิวตรวจใหม่" ให้ตาราง essay_ai_feedback (ฐานข้อมูลที่สร้างไว้ก่อนหน้านี้)
// เมื่อนักเรียนแก้ไขต้นฉบับหลัง AI ตรวจไปแล้ว ระบบจะทำเครื่องหมายไว้ให้คุณครูสั่งตรวจใหม่
try {
    $colRc = $pdo->query("SHOW COLUMNS FROM essay_ai_feedback LIKE 'recheck_needed'");
    if ($colRc && $colRc->rowCount() === 0) {
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN essay_hash CHAR(40) DEFAULT NULL AFTER raw_response");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN recheck_needed TINYINT(1) NOT NULL DEFAULT 0 AFTER essay_hash");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN recheck_marked_at DATETIME DEFAULT NULL AFTER recheck_needed");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// เพิ่มคอลัมน์ตัวนับ "ตรวจฉบับนี้เป็นครั้งที่เท่าไร" ให้ตาราง essay_ai_feedback (ฐานข้อมูลที่สร้างไว้ก่อนหน้านี้)
// หมายเหตุ: ระบบเคยเก็บผลตรวจครั้งก่อนไว้เทียบข้ามครั้ง (prev_round, progress_comment, resolved_points)
// ตอนนี้เลิกใช้แล้ว — การเทียบใช้ "คู่ที่ครูกำหนด" อย่างเดียว คอลัมน์เก่าในฐานข้อมูลที่มีอยู่ปล่อยไว้เฉย ๆ ไม่ได้ลบทิ้ง
try {
    $colRr = $pdo->query("SHOW COLUMNS FROM essay_ai_feedback LIKE 'review_round'");
    if ($colRr && $colRr->rowCount() === 0) {
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN review_round INT NOT NULL DEFAULT 1 AFTER recheck_marked_at");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// เพิ่มคอลัมน์ "เทียบกับฉบับตั้งต้น" ให้ตาราง essay_ai_feedback (ฐานข้อมูลที่สร้างไว้ก่อนหน้านี้)
// คู่ที่ต้องเทียบกันเสมอตามที่คุณครูกำหนด: ร่างที่ 2 เทียบร่างที่ 1 ของหน่วยเดียวกัน และหลังเรียนเทียบก่อนเรียน
// เก็บผลตรวจของฉบับตั้งต้นไว้ 1 ชุด เพื่อบอกได้ว่าคะแนน "ดีขึ้นจริงไหม" และต่างกันตรงไหน
try {
    $colBp = $pdo->query("SHOW COLUMNS FROM essay_ai_feedback LIKE 'baseline_phase'");
    if ($colBp && $colBp->rowCount() === 0) {
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN baseline_phase VARCHAR(20) DEFAULT NULL AFTER review_round");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN baseline_snapshot LONGTEXT NULL AFTER baseline_phase");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN draft_comment TEXT NULL AFTER baseline_snapshot");
        safe_ddl($pdo, "ALTER TABLE essay_ai_feedback ADD COLUMN draft_changes LONGTEXT NULL AFTER draft_comment");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}

// ---------------------------------------------------------------------------
// ตารางของระบบ "วิเคราะห์บทที่ 4 และบทที่ 5"
// ---------------------------------------------------------------------------
// 1) ch45_analysis      : ผลวิเคราะห์ของ AI แยกตามหัวข้อ (job) หัวข้อละ 1 แถว ทำซ้ำจะทับของเดิม
// 2) ch45_teaching_logs : บันทึกหลังสอนของผู้วิจัย — ใช้เขียน "ข้อเสนอแนะสำหรับการนำผลวิจัยไปใช้"
//                         ซึ่งโครงบทที่ 5 กำหนดว่าต้องเขียนจากปัญหาที่พบจริง ไม่ใช่จากตัวเลข
//
// ทั้งสองตารางแยกจากข้อมูลวิจัยเดิมทั้งหมด การวิเคราะห์จึงไม่แตะคะแนนหรือผลงานของนักเรียน
try {
    $tCh45 = $pdo->query("SHOW TABLES LIKE 'ch45_teaching_logs'");
    if (!$tCh45 || $tCh45->rowCount() === 0) {
        safe_ddl($pdo, "
            CREATE TABLE IF NOT EXISTS ch45_analysis (
                job_key      VARCHAR(40) PRIMARY KEY,   -- เช่น quant_narrative, ind_1_1, domain_d1, ch5_summary
                payload      LONGTEXT,                  -- JSON ผลวิเคราะห์ที่ผ่านการตรวจแล้ว
                raw_response LONGTEXT,                  -- คำตอบดิบของ AI ไว้ตรวจย้อนหลัง
                provider     VARCHAR(30)  DEFAULT NULL,
                model        VARCHAR(100) DEFAULT NULL,
                requested_by VARCHAR(50)  DEFAULT NULL,
                input_hash   CHAR(40)     DEFAULT NULL, -- ลายนิ้วมือข้อมูลนำเข้า ใช้เตือนเมื่อผลล้าสมัย
                warnings     TEXT,                      -- JSON array ข้อควรตรวจสอบก่อนนำไปใช้
                created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        safe_ddl($pdo, "
            CREATE TABLE IF NOT EXISTS ch45_teaching_logs (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                poa_stage  VARCHAR(20) NOT NULL DEFAULT 'general', -- motivating / enabling / assessing / general
                task_unit  TINYINT     NOT NULL DEFAULT 0,         -- 0 = ไม่ระบุหน่วย
                problem    TEXT        NOT NULL,                   -- ปัญหาที่พบจริงระหว่างจัดการเรียนการสอน
                solution   TEXT,                                   -- แนวทางแก้ไขที่ใช้แล้วได้ผล
                evidence   TEXT,                                   -- ข้อสังเกต/หลักฐานประกอบ
                created_by VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_ch45_log_stage (poa_stage, task_unit)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
} catch (Exception $e) {
    // เงียบไว้ ไม่ให้กระทบการทำงานหลักของระบบ
}
