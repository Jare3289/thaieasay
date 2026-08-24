<?php
// บังคับให้เริ่มเปิดใช้งาน Session และระบุประเภทการตอบกลับเป็น JSON
require_once 'auth_helper.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// ดึงข้อมูลที่ส่งมาจากกล่อง Request (กรณีส่งมาเป็น Raw JSON)
$input_raw = file_get_contents('php://input');
$request_data = json_decode($input_raw, true);

// ค้นหา action ที่ต้องการเรียกใช้งาน
$action = isset($_GET['action']) ? $_GET['action'] : (isset($request_data['action']) ? $request_data['action'] : '');

if (empty($action)) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

try {
    switch ($action) {
        
        // 1. ตรวจสอบการเข้าสู่ระบบ
        case 'check_login':
            $role = isset($request_data['role']) ? $request_data['role'] : '';
            $loginId = isset($request_data['loginId']) ? trim($request_data['loginId']) : '';
            $remember = isset($request_data['remember']) ? (bool)$request_data['remember'] : false;
            
            if ($role === 'teacher') {
                if ($loginId === 'admin') {
                    $_SESSION['user'] = [
                        'id' => 'admin',
                        'name' => 'ครูผู้สอน',
                        'role' => 'teacher'
                    ];
                    
                    if ($remember) {
                        $cookie_val = json_encode(['role' => $role, 'loginId' => $loginId]);
                        setcookie('remember_user', $cookie_val, time() + 30 * 24 * 60 * 60, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
                    }
                    
                    echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'รหัสผ่านคุณครูไม่ถูกต้อง']);
                }
            } else if ($role === 'expert') {
                if ($loginId === 'admin1' || $loginId === 'admin2') {
                    $expertNum = ($loginId === 'admin1') ? '1' : '2';
                    $_SESSION['user'] = [
                        'id' => $loginId,
                        'name' => 'ผู้เชี่ยวชาญ ' . $expertNum,
                        'role' => 'expert'
                    ];
                    
                    if ($remember) {
                        $cookie_val = json_encode(['role' => $role, 'loginId' => $loginId]);
                        setcookie('remember_user', $cookie_val, time() + 30 * 24 * 60 * 60, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
                    }
                    
                    echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'รหัสประจำตัวผู้เชี่ยวชาญไม่ถูกต้อง']);
                }
            } else if ($role === 'student') {
                // ค้นหารหัสนักเรียนในตาราง SQL
                $stmt = $pdo->prepare('SELECT * FROM students WHERE student_id = ?');
                $stmt->execute([$loginId]);
                $student = $stmt->fetch();
                
                if ($student) {
                    $_SESSION['user'] = [
                        'id' => $student['student_id'],
                        'name' => formatNamePrefix($student['student_name']),
                        'role' => 'student'
                    ];
                    
                    if ($remember) {
                        $cookie_val = json_encode(['role' => $role, 'loginId' => $loginId]);
                        setcookie('remember_user', $cookie_val, time() + 30 * 24 * 60 * 60, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
                    }
                    
                    echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสประจำตัวนักเรียนนี้ในระบบ']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'บทบาทการใช้งานไม่ถูกต้อง']);
            }
            break;
            
        // 2. ออกจากระบบ (ทำลาย Session)
        case 'logout':
            if (isset($_COOKIE['remember_user'])) {
                setcookie('remember_user', '', time() - 3600, '/');
            }
            session_destroy();
            echo json_encode(['success' => true]);
            break;
            
        // 3. ดึงสถานะปัจจุบันของสมาชิกที่ล็อกอินอยู่
        case 'get_current_user':
            if (isset($_SESSION['user'])) {
                echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
            }
            break;

        // 4. ดึงรายชื่อนักเรียนทั้งหมด
        case 'get_students_list':
            $conds = [];
            $params = [];

            // ผู้เชี่ยวชาญเห็นเฉพาะ "กลุ่มทดลอง" เสมอ (บังคับฝั่งเซิร์ฟเวอร์ ไม่ให้ client ข้าม)
            $forceGroup = null;
            if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'expert') {
                $forceGroup = 'กลุ่มทดลอง';
            }

            // classmates=1 สำหรับนักเรียน → คืนเฉพาะเพื่อนห้องเดียวกัน
            $onlyClassmates = isset($_GET['classmates']) && $_GET['classmates'] == '1'
                && isset($_SESSION['user']) && $_SESSION['user']['role'] === 'student';
            if ($onlyClassmates) {
                $meStmt = $pdo->prepare('SELECT classroom FROM students WHERE student_id = ?');
                $meStmt->execute([$_SESSION['user']['id']]);
                $myRoom = $meStmt->fetchColumn();
                if ($myRoom !== false && $myRoom !== null && trim($myRoom) !== '') {
                    $conds[] = 'classroom = ?';
                    $params[] = $myRoom;
                }
            }

            // กรองตามกลุ่ม (ทดลอง/ตัวอย่าง) — ผู้เชี่ยวชาญถูกบังคับเป็นกลุ่มทดลอง
            $groupParam = $forceGroup !== null ? $forceGroup : (isset($_GET['group']) ? trim($_GET['group']) : '');
            if ($groupParam !== '') {
                $conds[] = 'student_group = ?';
                $params[] = $groupParam;
            }

            $sql = 'SELECT student_id, student_name FROM students';
            if (!empty($conds)) $sql .= ' WHERE ' . implode(' AND ', $conds);
            $sql .= ' ORDER BY student_id ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $students = [];
            while ($row = $stmt->fetch()) {
                $students[$row['student_id']] = formatNamePrefix($row['student_name']);
            }
            echo json_encode(['success' => true, 'students' => $students]);
            break;

        // 4.1 ดึงรายชื่อนักเรียนแบบเต็ม (รหัส/ชื่อ/ห้อง/กลุ่ม) สำหรับหน้าจัดการนักเรียน (ครูเท่านั้น)
        case 'get_students_full':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $rows = $pdo->query('SELECT student_id, student_name, classroom, student_group FROM students ORDER BY classroom ASC, student_id ASC')->fetchAll();
            foreach ($rows as &$r) { $r['student_name'] = formatNamePrefix($r['student_name']); }
            unset($r);
            echo json_encode(['success' => true, 'students' => $rows]);
            break;

        // 4.4 ตั้งนักเรียนที่ยังไม่ระบุกลุ่ม ให้เป็น 'กลุ่มทดลอง' (ครูเท่านั้น)
        case 'set_ungrouped_experimental':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $stmt = $pdo->query("UPDATE students SET student_group = 'กลุ่มทดลอง' WHERE student_group IS NULL OR student_group = ''");
            echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
            break;

        // 4.2 เพิ่ม/แก้ไขนักเรียนทีละคน (ครูเท่านั้น)
        case 'save_student':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $sid   = isset($request_data['student_id'])   ? trim($request_data['student_id'])   : '';
            $sname = isset($request_data['student_name']) ? trim($request_data['student_name']) : '';
            $sroom = isset($request_data['classroom'])    ? trim($request_data['classroom'])    : '';
            $sgrp  = isset($request_data['student_group'])? trim($request_data['student_group']): '';
            if ($sid === '' || $sname === '') {
                echo json_encode(['success' => false, 'error' => 'ต้องระบุรหัสและชื่อนักเรียน']);
                exit;
            }
            $stmt = $pdo->prepare('INSERT INTO students (student_id, student_name, classroom, student_group)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE student_name=VALUES(student_name), classroom=VALUES(classroom), student_group=VALUES(student_group)');
            $stmt->execute([$sid, $sname, ($sroom !== '' ? $sroom : null), ($sgrp !== '' ? $sgrp : null)]);
            echo json_encode(['success' => true]);
            break;

        // 4.2.1 ลบนักเรียนทีละคน (ครูเท่านั้น) — ข้อมูลที่เกี่ยวข้องถูกลบตาม FK ON DELETE CASCADE
        case 'delete_student':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $sid = isset($request_data['student_id']) ? trim($request_data['student_id']) : '';
            if ($sid === '') {
                echo json_encode(['success' => false, 'error' => 'ต้องระบุรหัสนักเรียน']);
                exit;
            }
            $stmt = $pdo->prepare('DELETE FROM students WHERE student_id = ?');
            $stmt->execute([$sid]);
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            break;

        // 4.3 นำเข้ารายชื่อนักเรียนจากไฟล์ CSV (ครูเท่านั้น) — คอลัมน์: รหัส, ชื่อ, ห้อง, กลุ่ม
        case 'import_students_csv':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $b64 = isset($request_data['csv_base64']) ? $request_data['csv_base64'] : '';
            $raw = base64_decode($b64, true);
            if ($raw === false || $raw === '') {
                echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลไฟล์ CSV']);
                exit;
            }
            // ตัด BOM และแปลงเป็น UTF-8 (รองรับไฟล์ Excel ที่เป็น Windows-874/TIS-620)
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
            if (!mb_check_encoding($raw, 'UTF-8')) {
                $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-874,TIS-620,UTF-8');
            }
            $lines = preg_split('/\r\n|\r|\n/', trim($raw));

            $stmt = $pdo->prepare('INSERT INTO students (student_id, student_name, classroom, student_group)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE student_name=VALUES(student_name), classroom=VALUES(classroom), student_group=VALUES(student_group)');

            $imported = 0; $skipped = 0; $errors = [];
            foreach ($lines as $i => $line) {
                if (trim($line) === '') continue;
                $cols = str_getcsv($line);
                $sid  = isset($cols[0]) ? trim($cols[0]) : '';
                $name = isset($cols[1]) ? trim($cols[1]) : '';
                $room = isset($cols[2]) ? trim($cols[2]) : '';
                $grp  = isset($cols[3]) ? trim($cols[3]) : '';
                // ข้ามแถวหัวตาราง (รหัสไม่ใช่ตัวเลข หรือเป็นคำว่า 'รหัส'/'student_id')
                if ($sid === '' || $name === '') { $skipped++; continue; }
                if (!preg_match('/^\d+$/', $sid)) { $skipped++; continue; }
                try {
                    $stmt->execute([$sid, $name, ($room !== '' ? $room : null), ($grp !== '' ? $grp : null)]);
                    $imported++;
                } catch (PDOException $e) {
                    $skipped++;
                    if (count($errors) < 5) $errors[] = "แถว " . ($i + 1) . ": " . $e->getMessage();
                }
            }
            echo json_encode(['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]);
            break;

        // 5. บันทึกผลการประเมิน (Insert / Update)
        case 'save_evaluation':
            // ป้องกันการแอบบันทึกโดยไม่ล็อกอิน
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'error' => 'ต้องเข้าสู่ระบบก่อนบันทึกข้อมูล']);
                exit;
            }
            
            $data = isset($request_data['data']) ? $request_data['data'] : null;
            if (!$data) {
                echo json_encode(['success' => false, 'error' => 'No data provided']);
                exit;
            }
            
            $studentId = $data['studentId'];
            $studentName = $data['studentName'];
            $evaluatorType = $data['evaluatorType'];
            $evaluatorName = $data['evaluatorName'];
            $testPhase = isset($data['testPhase']) ? $data['testPhase'] : 'posttest';
            $scores = $data['scores'];
            $totalScore = $data['totalScore'];
            $qualityLevel = $data['qualityLevel'];
            // คำแนะนำเชิงคุณภาพจากเพื่อน (ใช้เฉพาะโหมด 'เพื่อนประเมิน')
            $peerStrength    = isset($data['peerStrength'])    ? trim($data['peerStrength'])    : null;
            $peerImprovement = isset($data['peerImprovement']) ? trim($data['peerImprovement']) : null;
            $peerEncouragement = isset($data['peerEncouragement']) ? trim($data['peerEncouragement']) : null;
            
            // เตรียมคิวรีและทำการ Upsert
            $sql = 'INSERT INTO evaluations (
                        student_id, evaluator_type, evaluator_name, test_phase,
                        score_1_1, score_1_2, score_1_3,
                        score_2_1, score_2_2,
                        score_3_1, score_3_2, score_3_3,
                        score_4_1, score_4_2, score_4_3,
                        total_score, quality_level,
                        peer_strength, peer_improvement, peer_encouragement
                    ) VALUES (
                        :student_id, :evaluator_type, :evaluator_name, :test_phase,
                        :s1_1, :s1_2, :s1_3,
                        :s2_1, :s2_2,
                        :s3_1, :s3_2, :s3_3,
                        :s4_1, :s4_2, :s4_3,
                        :total_score, :quality_level,
                        :peer_strength, :peer_improvement, :peer_encouragement
                    )
                    ON DUPLICATE KEY UPDATE
                        score_1_1 = VALUES(score_1_1),
                        score_1_2 = VALUES(score_1_2),
                        score_1_3 = VALUES(score_1_3),
                        score_2_1 = VALUES(score_2_1),
                        score_2_2 = VALUES(score_2_2),
                        score_3_1 = VALUES(score_3_1),
                        score_3_2 = VALUES(score_3_2),
                        score_3_3 = VALUES(score_3_3),
                        score_4_1 = VALUES(score_4_1),
                        score_4_2 = VALUES(score_4_2),
                        score_4_3 = VALUES(score_4_3),
                        total_score = VALUES(total_score),
                        quality_level = VALUES(quality_level),
                        peer_strength = VALUES(peer_strength),
                        peer_improvement = VALUES(peer_improvement),
                        peer_encouragement = VALUES(peer_encouragement),
                        timestamp = CURRENT_TIMESTAMP';
             
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':student_id'          => $studentId,
                ':evaluator_type'      => $evaluatorType,
                ':evaluator_name'      => $evaluatorName,
                ':test_phase'          => $testPhase,
                ':s1_1'                => $scores['1.1'],
                ':s1_2'                => $scores['1.2'],
                ':s1_3'                => $scores['1.3'],
                ':s2_1'                => $scores['2.1'],
                ':s2_2'                => $scores['2.2'],
                ':s3_1'                => $scores['3.1'],
                ':s3_2'                => $scores['3.2'],
                ':s3_3'                => $scores['3.3'],
                ':s4_1'                => $scores['4.1'],
                ':s4_2'                => $scores['4.2'],
                ':s4_3'                => $scores['4.3'],
                ':total_score'         => $totalScore,
                ':quality_level'       => $qualityLevel,
                ':peer_strength'       => $peerStrength,
                ':peer_improvement'    => $peerImprovement,
                ':peer_encouragement'  => $peerEncouragement
            ]);
            
            echo json_encode(['success' => true]);
            break;

        // 6. ดึงข้อมูลคะแนนสะสม 3 ทิศทางของนักเรียน 1 คน
        case 'get_student_scores':
            $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : '';
            $testPhase = isset($_GET['testPhase']) ? $_GET['testPhase'] : 'posttest';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                exit;
            }
            
            // ดึงข้อมูลการประเมินพร้อมชื่อนักเรียน
            $stmt = $pdo->prepare('
                SELECT e.*, s.student_name 
                FROM evaluations e
                JOIN students s ON e.student_id = s.student_id
                WHERE e.student_id = ? AND e.test_phase = ?
            ');
            $stmt->execute([$studentId, $testPhase]);
            $rows = $stmt->fetchAll();
            
            $results = [];
            foreach ($rows as $row) {
                $results[] = [
                    'timestamp'     => $row['timestamp'],
                    'studentId'     => $row['student_id'],
                    'studentName'   => $row['student_name'],
                    'evaluatorType' => $row['evaluator_type'],
                    'evaluatorName' => $row['evaluator_name'],
                    'totalScore'    => (float)$row['total_score'],
                    'qualityLevel'  => $row['quality_level'],
                    'rawScores'     => [
                        'content'   => (float)$row['score_1_1'] + (float)$row['score_1_2'] + (float)$row['score_1_3'],
                        'structure' => (float)$row['score_2_1'] + (float)$row['score_2_2'],
                        'language'  => (float)$row['score_3_1'] + (float)$row['score_3_2'] + (float)$row['score_3_3'],
                        'mechanics' => (float)$row['score_4_1'] + (float)$row['score_4_2'] + (float)$row['score_4_3'],
                        'details'   => [
                            '1.1' => (float)$row['score_1_1'], '1.2' => (float)$row['score_1_2'], '1.3' => (float)$row['score_1_3'],
                            '2.1' => (float)$row['score_2_1'], '2.2' => (float)$row['score_2_2'],
                            '3.1' => (float)$row['score_3_1'], '3.2' => (float)$row['score_3_2'], '3.3' => (float)$row['score_3_3'],
                            '4.1' => (float)$row['score_4_1'], '4.2' => (float)$row['score_4_2'], '4.3' => (float)$row['score_4_3']
                        ]
                    ]
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $results]);
            break;

        // 7. ดึงการประเมินแบบเจาะจง 1 รายการเพื่อเอาคะแนนไปแสดงคืนค่าไว้บนฟอร์ม
        case 'get_single_evaluation':
            $studentId = isset($request_data['studentId']) ? $request_data['studentId'] : '';
            $evaluatorType = isset($request_data['evaluatorType']) ? $request_data['evaluatorType'] : '';
            $evaluatorName = isset($request_data['evaluatorName']) ? $request_data['evaluatorName'] : '';
            $testPhase = isset($request_data['testPhase']) ? $request_data['testPhase'] : 'posttest';
            
            if (empty($studentId) || empty($evaluatorType) || empty($evaluatorName)) {
                echo json_encode(['success' => false, 'error' => 'Missing parameters']);
                exit;
            }
            
            $stmt = $pdo->prepare('
                SELECT * FROM evaluations 
                WHERE student_id = ? AND evaluator_type = ? AND evaluator_name = ? AND test_phase = ?
            ');
            $stmt->execute([$studentId, $evaluatorType, $evaluatorName, $testPhase]);
            $row = $stmt->fetch();
            
            if ($row) {
                echo json_encode([
                    'success' => true,
                    'found'   => true,
                    'scores'  => [
                        '1.1' => (float)$row['score_1_1'], '1.2' => (float)$row['score_1_2'], '1.3' => (float)$row['score_1_3'],
                        '2.1' => (float)$row['score_2_1'], '2.2' => (float)$row['score_2_2'],
                        '3.1' => (float)$row['score_3_1'], '3.2' => (float)$row['score_3_2'], '3.3' => (float)$row['score_3_3'],
                        '4.1' => (float)$row['score_4_1'], '4.2' => (float)$row['score_4_2'], '4.3' => (float)$row['score_4_3']
                    ],
                    'totalScore'   => (float)$row['total_score'],
                    'qualityLevel' => $row['quality_level'],
                    'peerFeedback' => [
                        'strength'     => $row['peer_strength']     ?? '',
                        'improvement'  => $row['peer_improvement']  ?? '',
                        'encouragement'=> $row['peer_encouragement'] ?? ''
                    ]
                ]);
            } else {
                echo json_encode(['success' => true, 'found' => false]);
            }
            break;

        // ============================================================
        //  รายการ "สิ่งที่ยังไม่ได้ทำ" ของนักเรียนที่ล็อกอินอยู่ (ใช้ในหน้าเมนูหลัก index.php)
        //  รวมสถานะทุกด้าน (เขียนเรียงความ / ประเมินตนเอง / ประเมินเพื่อน / ครูประเมิน / สะท้อนคิด)
        //  แยกตามรอบ (ก่อนเรียน/หน่วย 1/หน่วย 2/หลังเรียน) ในครั้งเดียว ให้หน้าเว็บลิงก์ไปทำงานที่ค้างได้ตรงจุด
        // ============================================================
        case 'get_my_todo_status':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $myId = $_SESSION['user']['id'];
            $myName = $_SESSION['user']['name'];

            $phases = ['pretest', 'task1', 'task2', 'posttest'];
            // essay_phase ที่ใช้ตรวจว่าเขียนเรียงความของแต่ละรอบครบหรือยัง — ภาระงานตรวจที่ร่างที่ 2 (D2) เพราะเป็นร่างที่ใช้ให้คะแนนจริง
            $essayPhaseKeyMap = ['pretest' => 'pretest', 'task1' => 'task1_d2', 'task2' => 'task2_d2', 'posttest' => 'posttest'];

            // 1) เรียงความที่เขียนแล้ว (มีคำอย่างน้อย 1 คำ) ของแต่ละรอบ
            $essayWordCounts = [];
            $stmtEssay = $pdo->prepare('SELECT word_count FROM student_essays WHERE student_id = ? AND essay_phase = ?');
            foreach ($essayPhaseKeyMap as $phase => $essayPhaseKey) {
                $stmtEssay->execute([$myId, $essayPhaseKey]);
                $wc = $stmtEssay->fetchColumn();
                $essayWordCounts[$phase] = ($wc !== false) ? (int)$wc : 0;
            }

            // 2) การประเมินทั้งหมดของนักเรียนคนนี้ (ทุกผู้ประเมิน ทุกรอบ) ดึงครั้งเดียวแล้วแยกตามรอบ
            $stmtEval = $pdo->prepare('SELECT test_phase, evaluator_type FROM evaluations WHERE student_id = ?');
            $stmtEval->execute([$myId]);
            $evalByPhase = [];
            foreach ($phases as $phase) { $evalByPhase[$phase] = ['self' => false, 'teacher' => false]; }
            while ($erow = $stmtEval->fetch()) {
                $ph = $erow['test_phase'];
                if (!isset($evalByPhase[$ph])) continue;
                if ($erow['evaluator_type'] === 'ตนเองประเมิน') $evalByPhase[$ph]['self'] = true;
                if ($erow['evaluator_type'] === 'ครูประเมิน')   $evalByPhase[$ph]['teacher'] = true;
            }

            // 3) คู่ประเมินเพื่อนของแต่ละรอบ + สถานะว่าประเมินคู่ตนเองไปแล้วหรือยัง
            $stmtPartner  = $pdo->prepare('SELECT partner_code FROM peer_pairs WHERE round = ? AND student_code = ?');
            $stmtPeerDone = $pdo->prepare("SELECT 1 FROM evaluations WHERE student_id = ? AND evaluator_type = 'เพื่อนประเมิน' AND evaluator_name = ? AND test_phase = ?");
            $peerByPhase = [];
            foreach ($phases as $phase) {
                $stmtPartner->execute([$phase, $myId]);
                $partnerId = $stmtPartner->fetchColumn();
                $partnerId = ($partnerId !== false && $partnerId !== null && $partnerId !== '') ? $partnerId : null;
                $peerDone = false;
                if ($partnerId) {
                    $stmtPeerDone->execute([$partnerId, $myName, $phase]);
                    $peerDone = (bool)$stmtPeerDone->fetchColumn();
                }
                $peerByPhase[$phase] = ['partnerId' => $partnerId, 'peerDone' => $peerDone];
            }
            // ชื่อคู่ (ถ้ามี) เอาไว้แสดงในหน้าเว็บ
            $partnerNames = [];
            $partnerIds = array_unique(array_filter(array_column($peerByPhase, 'partnerId')));
            if (!empty($partnerIds)) {
                $placeholders = implode(',', array_fill(0, count($partnerIds), '?'));
                $stmtNames = $pdo->prepare("SELECT student_id, student_name FROM students WHERE student_id IN ($placeholders)");
                $stmtNames->execute(array_values($partnerIds));
                while ($nrow = $stmtNames->fetch()) { $partnerNames[$nrow['student_id']] = $nrow['student_name']; }
            }

            // 4) สะท้อนคิดการเรียนรู้ (เฉพาะหน่วย 1/2 — ไม่มีในรอบก่อน/หลังเรียน)
            $stmtRefl = $pdo->prepare('SELECT 1 FROM learning_reflections WHERE student_id = ? AND task_unit = ?');
            $reflectionByUnit = [];
            foreach ([1, 2] as $unit) {
                $stmtRefl->execute([$myId, $unit]);
                $reflectionByUnit[$unit] = (bool)$stmtRefl->fetchColumn();
            }

            // ประกอบผลลัพธ์ต่อรอบ
            $todoResult = [];
            foreach ($phases as $phase) {
                $todoResult[$phase] = [
                    'essayDone'     => $essayWordCounts[$phase] > 0,
                    'wordCount'     => $essayWordCounts[$phase],
                    'essayPhaseKey' => $essayPhaseKeyMap[$phase],
                    'selfDone'      => $evalByPhase[$phase]['self'],
                    'teacherDone'   => $evalByPhase[$phase]['teacher'],
                    'partnerId'     => $peerByPhase[$phase]['partnerId'],
                    'partnerName'   => $peerByPhase[$phase]['partnerId'] ? ($partnerNames[$peerByPhase[$phase]['partnerId']] ?? null) : null,
                    'peerDone'      => $peerByPhase[$phase]['peerDone']
                ];
            }
            $todoResult['task1']['reflectionDone'] = $reflectionByUnit[1];
            $todoResult['task2']['reflectionDone'] = $reflectionByUnit[2];

            echo json_encode(['success' => true, 'phases' => $todoResult]);
            break;

        // 8. ดึงภาพรวมแดชบอร์ดความคืบหน้าของห้องเรียน
        case 'get_all_students_summary':
            // ดึงรายชื่อนักเรียนทั้งหมด
            $stmt_std = $pdo->query('SELECT student_id, student_name FROM students ORDER BY student_id ASC');
            $summary = [];
            while ($row = $stmt_std->fetch()) {
                $summary[$row['student_id']] = [
                    'self'            => false,
                    'peer'            => false,
                    'teacher'         => false,
                    'selfScore'       => null,
                    'peerScore'       => null,
                    'teacherScore'    => null,
                    'avgScore'        => 0,
                    'count'           => 0,
                    'totalScoreAcc'   => 0,
                    // สะสมด้านย่อย 11 ด้าน
                    's11_acc'         => 0, 's12_acc' => 0, 's13_acc' => 0,
                    's21_acc'         => 0, 's22_acc' => 0,
                    's31_acc'         => 0, 's32_acc' => 0, 's33_acc' => 0,
                    's41_acc'         => 0, 's42_acc' => 0, 's43_acc' => 0,
                    // ค่าเฉลี่ยหลัก 4 ด้าน
                    'avg_c'           => 0,
                    'avg_s'           => 0,
                    'avg_l'           => 0,
                    'avg_m'           => 0,
                    // ค่าเฉลี่ยย่อย 11 ด้าน
                    'avg_1_1'         => 0, 'avg_1_2' => 0, 'avg_1_3' => 0,
                    'avg_2_1'         => 0, 'avg_2_2' => 0,
                    'avg_3_1'         => 0, 'avg_3_2' => 0, 'avg_3_3' => 0,
                    'avg_4_1'         => 0, 'avg_4_2' => 0, 'avg_4_3' => 0,
                    'teacher_c'       => null,
                    'teacher_s'       => null,
                    'teacher_l'       => null,
                    'teacher_m'       => null
                ];
            }
            
            // ดึงผลคะแนนประเมินที่มีอยู่ทั้งหมด พร้อมคะแนนรายด้านย่อย
            $stmt_eval = $pdo->query('SELECT student_id, evaluator_type, total_score,
                score_1_1, score_1_2, score_1_3,
                score_2_1, score_2_2,
                score_3_1, score_3_2, score_3_3,
                score_4_1, score_4_2, score_4_3
                FROM evaluations');
            while ($row = $stmt_eval->fetch()) {
                $sId = $row['student_id'];
                $type = $row['evaluator_type'];
                $score = (float)$row['total_score'];
                
                if (isset($summary[$sId])) {
                    if ($type === 'ตนเองประเมิน') {
                        $summary[$sId]['self'] = true;
                        $summary[$sId]['selfScore'] = $score;
                    } else if ($type === 'เพื่อนประเมิน') {
                        $summary[$sId]['peer'] = true;
                        $summary[$sId]['peerScore'] = $score;
                    } else if ($type === 'ครูประเมิน') {
                        $summary[$sId]['teacher'] = true;
                        $summary[$sId]['teacherScore'] = $score;
                        $summary[$sId]['teacher_c'] = (float)$row['score_1_1'] + (float)$row['score_1_2'] + (float)$row['score_1_3'];
                        $summary[$sId]['teacher_s'] = (float)$row['score_2_1'] + (float)$row['score_2_2'];
                        $summary[$sId]['teacher_l'] = (float)$row['score_3_1'] + (float)$row['score_3_2'] + (float)$row['score_3_3'];
                        $summary[$sId]['teacher_m'] = (float)$row['score_4_1'] + (float)$row['score_4_2'] + (float)$row['score_4_3'];
                    }
                    
                    $summary[$sId]['totalScoreAcc'] += $score;
                    $summary[$sId]['s11_acc'] += (float)$row['score_1_1'];
                    $summary[$sId]['s12_acc'] += (float)$row['score_1_2'];
                    $summary[$sId]['s13_acc'] += (float)$row['score_1_3'];
                    $summary[$sId]['s21_acc'] += (float)$row['score_2_1'];
                    $summary[$sId]['s22_acc'] += (float)$row['score_2_2'];
                    $summary[$sId]['s31_acc'] += (float)$row['score_3_1'];
                    $summary[$sId]['s32_acc'] += (float)$row['score_3_2'];
                    $summary[$sId]['s33_acc'] += (float)$row['score_3_3'];
                    $summary[$sId]['s41_acc'] += (float)$row['score_4_1'];
                    $summary[$sId]['s42_acc'] += (float)$row['score_4_2'];
                    $summary[$sId]['s43_acc'] += (float)$row['score_4_3'];
                    $summary[$sId]['count']++;
                }
            }
            
            // คำนวณหาค่าเฉลี่ยสะสมและค่าเฉลี่ยสะสมรายด้านย่อย
            foreach ($summary as $key => &$data) {
                if ($data['count'] > 0) {
                    $data['avgScore'] = (float)number_format($data['totalScoreAcc'] / $data['count'], 2, '.', '');
                    $data['avg_1_1'] = (float)number_format($data['s11_acc'] / $data['count'], 2, '.', '');
                    $data['avg_1_2'] = (float)number_format($data['s12_acc'] / $data['count'], 2, '.', '');
                    $data['avg_1_3'] = (float)number_format($data['s13_acc'] / $data['count'], 2, '.', '');
                    $data['avg_2_1'] = (float)number_format($data['s21_acc'] / $data['count'], 2, '.', '');
                    $data['avg_2_2'] = (float)number_format($data['s22_acc'] / $data['count'], 2, '.', '');
                    $data['avg_3_1'] = (float)number_format($data['s31_acc'] / $data['count'], 2, '.', '');
                    $data['avg_3_2'] = (float)number_format($data['s32_acc'] / $data['count'], 2, '.', '');
                    $data['avg_3_3'] = (float)number_format($data['s33_acc'] / $data['count'], 2, '.', '');
                    $data['avg_4_1'] = (float)number_format($data['s41_acc'] / $data['count'], 2, '.', '');
                    $data['avg_4_2'] = (float)number_format($data['s42_acc'] / $data['count'], 2, '.', '');
                    $data['avg_4_3'] = (float)number_format($data['s43_acc'] / $data['count'], 2, '.', '');
                    
                    // คำนวณค่าเฉลี่ยด้านหลักจากผลรวมเฉลี่ยด้านย่อยแบบทศนิยมแม่นยำ
                    $data['avg_c'] = (float)number_format(($data['s11_acc'] + $data['s12_acc'] + $data['s13_acc']) / $data['count'], 2, '.', '');
                    $data['avg_s'] = (float)number_format(($data['s21_acc'] + $data['s22_acc']) / $data['count'], 2, '.', '');
                    $data['avg_l'] = (float)number_format(($data['s31_acc'] + $data['s32_acc'] + $data['s33_acc']) / $data['count'], 2, '.', '');
                    $data['avg_m'] = (float)number_format(($data['s41_acc'] + $data['s42_acc'] + $data['s43_acc']) / $data['count'], 2, '.', '');
                }
            }
            
            echo json_encode(['success' => true, 'data' => $summary]);
            break;

        // ==========================================
        // เครื่องมือสะท้อนคิดและการประเมินเพิ่มเติม (Reflection Tools Actions)
        // ==========================================

        case 'bulk_save_writing_problems':
            $list = isset($request_data['list']) ? $request_data['list'] : [];
            if (empty($list)) {
                echo json_encode(['success' => false, 'error' => 'No data list provided']);
                exit;
            }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO writing_problems (
                        student_id,
                        prob_1_1, sol_1_1, prob_1_2, sol_1_2, prob_1_3, sol_1_3,
                        prob_2_1, sol_2_1, prob_2_2, sol_2_2,
                        prob_3_1, sol_3_1, prob_3_2, sol_3_2, prob_3_3, sol_3_3,
                        prob_4_1, sol_4_1, prob_4_2, sol_4_2, prob_4_3, sol_4_3
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        prob_1_1 = VALUES(prob_1_1), sol_1_1 = VALUES(sol_1_1),
                        prob_1_2 = VALUES(prob_1_2), sol_1_2 = VALUES(sol_1_2),
                        prob_1_3 = VALUES(prob_1_3), sol_1_3 = VALUES(sol_1_3),
                        prob_2_1 = VALUES(prob_2_1), sol_2_1 = VALUES(sol_2_1),
                        prob_2_2 = VALUES(prob_2_2), sol_2_2 = VALUES(sol_2_2),
                        prob_3_1 = VALUES(prob_3_1), sol_3_1 = VALUES(sol_3_1),
                        prob_3_2 = VALUES(prob_3_2), sol_3_2 = VALUES(sol_3_2),
                        prob_3_3 = VALUES(prob_3_3), sol_3_3 = VALUES(sol_3_3),
                        prob_4_1 = VALUES(prob_4_1), sol_4_1 = VALUES(sol_4_1),
                        prob_4_2 = VALUES(prob_4_2), sol_4_2 = VALUES(sol_4_2),
                        prob_4_3 = VALUES(prob_4_3), sol_4_3 = VALUES(sol_4_3)
                ');
                foreach ($list as $row) {
                    $p = isset($row['problems']) ? $row['problems'] : [];
                    $stmt->execute([
                        $row['studentId'],
                        isset($p['prob_1_1']) ? $p['prob_1_1'] : null, isset($p['sol_1_1']) ? $p['sol_1_1'] : null,
                        isset($p['prob_1_2']) ? $p['prob_1_2'] : null, isset($p['sol_1_2']) ? $p['sol_1_2'] : null,
                        isset($p['prob_1_3']) ? $p['prob_1_3'] : null, isset($p['sol_1_3']) ? $p['sol_1_3'] : null,
                        isset($p['prob_2_1']) ? $p['prob_2_1'] : null, isset($p['sol_2_1']) ? $p['sol_2_1'] : null,
                        isset($p['prob_2_2']) ? $p['prob_2_2'] : null, isset($p['sol_2_2']) ? $p['sol_2_2'] : null,
                        isset($p['prob_3_1']) ? $p['prob_3_1'] : null, isset($p['sol_3_1']) ? $p['sol_3_1'] : null,
                        isset($p['prob_3_2']) ? $p['prob_3_2'] : null, isset($p['sol_3_2']) ? $p['sol_3_2'] : null,
                        isset($p['prob_3_3']) ? $p['prob_3_3'] : null, isset($p['sol_3_3']) ? $p['sol_3_3'] : null,
                        isset($p['prob_4_1']) ? $p['prob_4_1'] : null, isset($p['sol_4_1']) ? $p['sol_4_1'] : null,
                        isset($p['prob_4_2']) ? $p['prob_4_2'] : null, isset($p['sol_4_2']) ? $p['sol_4_2'] : null,
                        isset($p['prob_4_3']) ? $p['prob_4_3'] : null, isset($p['sol_4_3']) ? $p['sol_4_3'] : null
                    ]);
                }
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'bulk_save_self_checklists':
            $list = isset($request_data['list']) ? $request_data['list'] : [];
            if (empty($list)) {
                echo json_encode(['success' => false, 'error' => 'No data list provided']);
                exit;
            }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO self_checklists (
                        student_id,
                        check_1_1, check_1_2, check_1_3,
                        check_2_1, check_2_2,
                        check_3_1, check_3_2, check_3_3,
                        check_4_1, check_4_2, check_4_3,
                        notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        check_1_1 = VALUES(check_1_1), check_1_2 = VALUES(check_1_2), check_1_3 = VALUES(check_1_3),
                        check_2_1 = VALUES(check_2_1), check_2_2 = VALUES(check_2_2),
                        check_3_1 = VALUES(check_3_1), check_3_2 = VALUES(check_3_2), check_3_3 = VALUES(check_3_3),
                        check_4_1 = VALUES(check_4_1), check_4_2 = VALUES(check_4_2), check_4_3 = VALUES(check_4_3),
                        notes = VALUES(notes)
                ');
                foreach ($list as $row) {
                    $c = isset($row['checklist']) ? $row['checklist'] : [];
                    $stmt->execute([
                        $row['studentId'],
                        isset($c['1.1']) ? $c['1.1'] : 'ต้องปรับปรุง',
                        isset($c['1.2']) ? $c['1.2'] : 'ต้องปรับปรุง',
                        isset($c['1.3']) ? $c['1.3'] : 'ต้องปรับปรุง',
                        isset($c['2.1']) ? $c['2.1'] : 'ต้องปรับปรุง',
                        isset($c['2.2']) ? $c['2.2'] : 'ต้องปรับปรุง',
                        isset($c['3.1']) ? $c['3.1'] : 'ต้องปรับปรุง',
                        isset($c['3.2']) ? $c['3.2'] : 'ต้องปรับปรุง',
                        isset($c['3.3']) ? $c['3.3'] : 'ต้องปรับปรุง',
                        isset($c['4.1']) ? $c['4.1'] : 'ต้องปรับปรุง',
                        isset($c['4.2']) ? $c['4.2'] : 'ต้องปรับปรุง',
                        isset($c['4.3']) ? $c['4.3'] : 'ต้องปรับปรุง',
                        isset($row['notes']) ? $row['notes'] : ''
                    ]);
                }
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'bulk_save_peer_reviews':
            $list = isset($request_data['list']) ? $request_data['list'] : [];
            if (empty($list)) {
                echo json_encode(['success' => false, 'error' => 'No data list provided']);
                exit;
            }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO peer_reviews (
                        student_id, reviewer_id,
                        score_1_1, score_1_2, score_1_3, score_1_4,
                        score_2_1, score_2_2,
                        score_3_1, score_3_2, score_3_3, score_3_4,
                        score_4_1, score_4_2, score_4_3,
                        strength, improvement, encouragement
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        score_1_1 = VALUES(score_1_1), score_1_2 = VALUES(score_1_2), score_1_3 = VALUES(score_1_3), score_1_4 = VALUES(score_1_4),
                        score_2_1 = VALUES(score_2_1), score_2_2 = VALUES(score_2_2),
                        score_3_1 = VALUES(score_3_1), score_3_2 = VALUES(score_3_2), score_3_3 = VALUES(score_3_3), score_3_4 = VALUES(score_3_4),
                        score_4_1 = VALUES(score_4_1), score_4_2 = VALUES(score_4_2), score_4_3 = VALUES(score_4_3),
                        strength = VALUES(strength), improvement = VALUES(improvement), encouragement = VALUES(encouragement)
                ');
                foreach ($list as $row) {
                    $s = isset($row['scores']) ? $row['scores'] : [];
                    $stmt->execute([
                        $row['studentId'],
                        $row['reviewerId'],
                        isset($s['1.1']) ? $s['1.1'] : 'ปรับปรุง',
                        isset($s['1.2']) ? $s['1.2'] : 'ปรับปรุง',
                        isset($s['1.3']) ? $s['1.3'] : 'ปรับปรุง',
                        isset($s['1.4']) ? $s['1.4'] : 'ปรับปรุง',
                        isset($s['2.1']) ? $s['2.1'] : 'ปรับปรุง',
                        isset($s['2.2']) ? $s['2.2'] : 'ปรับปรุง',
                        isset($s['3.1']) ? $s['3.1'] : 'ปรับปรุง',
                        isset($s['3.2']) ? $s['3.2'] : 'ปรับปรุง',
                        isset($s['3.3']) ? $s['3.3'] : 'ปรับปรุง',
                        isset($s['3.4']) ? $s['3.4'] : 'ปรับปรุง',
                        isset($s['4.1']) ? $s['4.1'] : 'ปรับปรุง',
                        isset($s['4.2']) ? $s['4.2'] : 'ปรับปรุง',
                        isset($s['4.3']) ? $s['4.3'] : 'ปรับปรุง',
                        isset($row['strength']) ? $row['strength'] : '',
                        isset($row['improvement']) ? $row['improvement'] : '',
                        isset($row['encouragement']) ? $row['encouragement'] : ''
                    ]);
                }
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'bulk_save_learning_reflections':
            $list = isset($request_data['list']) ? $request_data['list'] : [];
            if (empty($list)) {
                echo json_encode(['success' => false, 'error' => 'No data list provided']);
                exit;
            }
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO learning_reflections (
                        student_id, content_structure, language_mechanics, feedback_applied, future_goals
                    ) VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        content_structure = VALUES(content_structure),
                        language_mechanics = VALUES(language_mechanics),
                        feedback_applied = VALUES(feedback_applied),
                        future_goals = VALUES(future_goals)
                ');
                foreach ($list as $row) {
                    $stmt->execute([
                        $row['studentId'],
                        isset($row['content_structure']) ? $row['content_structure'] : '',
                        isset($row['language_mechanics']) ? $row['language_mechanics'] : '',
                        isset($row['feedback_applied']) ? $row['feedback_applied'] : '',
                        isset($row['future_goals']) ? $row['future_goals'] : ''
                    ]);
                }
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'save_writing_problems':
            $studentId = isset($request_data['studentId']) ? $request_data['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $p = isset($request_data['problems']) ? $request_data['problems'] : [];
            $unit = (isset($request_data['unit']) && intval($request_data['unit']) === 2) ? 2 : 1;

            $stmt = $pdo->prepare('
                INSERT INTO writing_problems (
                    student_id, task_unit,
                    prob_1_1, sol_1_1, prob_1_2, sol_1_2, prob_1_3, sol_1_3,
                    prob_2_1, sol_2_1, prob_2_2, sol_2_2,
                    prob_3_1, sol_3_1, prob_3_2, sol_3_2, prob_3_3, sol_3_3,
                    prob_4_1, sol_4_1, prob_4_2, sol_4_2, prob_4_3, sol_4_3
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    prob_1_1 = VALUES(prob_1_1), sol_1_1 = VALUES(sol_1_1),
                    prob_1_2 = VALUES(prob_1_2), sol_1_2 = VALUES(sol_1_2),
                    prob_1_3 = VALUES(prob_1_3), sol_1_3 = VALUES(sol_1_3),
                    prob_2_1 = VALUES(prob_2_1), sol_2_1 = VALUES(sol_2_1),
                    prob_2_2 = VALUES(prob_2_2), sol_2_2 = VALUES(sol_2_2),
                    prob_3_1 = VALUES(prob_3_1), sol_3_1 = VALUES(sol_3_1),
                    prob_3_2 = VALUES(prob_3_2), sol_3_2 = VALUES(sol_3_2),
                    prob_3_3 = VALUES(prob_3_3), sol_3_3 = VALUES(sol_3_3),
                    prob_4_1 = VALUES(prob_4_1), sol_4_1 = VALUES(sol_4_1),
                    prob_4_2 = VALUES(prob_4_2), sol_4_2 = VALUES(sol_4_2),
                    prob_4_3 = VALUES(prob_4_3), sol_4_3 = VALUES(sol_4_3)
            ');
            $stmt->execute([
                $studentId, $unit,
                isset($p['prob_1_1']) ? $p['prob_1_1'] : null, isset($p['sol_1_1']) ? $p['sol_1_1'] : null,
                isset($p['prob_1_2']) ? $p['prob_1_2'] : null, isset($p['sol_1_2']) ? $p['sol_1_2'] : null,
                isset($p['prob_1_3']) ? $p['prob_1_3'] : null, isset($p['sol_1_3']) ? $p['sol_1_3'] : null,
                isset($p['prob_2_1']) ? $p['prob_2_1'] : null, isset($p['sol_2_1']) ? $p['sol_2_1'] : null,
                isset($p['prob_2_2']) ? $p['prob_2_2'] : null, isset($p['sol_2_2']) ? $p['sol_2_2'] : null,
                isset($p['prob_3_1']) ? $p['prob_3_1'] : null, isset($p['sol_3_1']) ? $p['sol_3_1'] : null,
                isset($p['prob_3_2']) ? $p['prob_3_2'] : null, isset($p['sol_3_2']) ? $p['sol_3_2'] : null,
                isset($p['prob_3_3']) ? $p['prob_3_3'] : null, isset($p['sol_3_3']) ? $p['sol_3_3'] : null,
                isset($p['prob_4_1']) ? $p['prob_4_1'] : null, isset($p['sol_4_1']) ? $p['sol_4_1'] : null,
                isset($p['prob_4_2']) ? $p['prob_4_2'] : null, isset($p['sol_4_2']) ? $p['sol_4_2'] : null,
                isset($p['prob_4_3']) ? $p['prob_4_3'] : null, isset($p['sol_4_3']) ? $p['sol_4_3'] : null
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'get_writing_problems':
            $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $unit = (isset($_GET['unit']) && intval($_GET['unit']) === 2) ? 2 : 1;
            $stmt = $pdo->prepare('SELECT * FROM writing_problems WHERE student_id = ? AND task_unit = ?');
            $stmt->execute([$studentId, $unit]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $row ? $row : null]);
            break;

        case 'save_self_checklist':
            $studentId = isset($request_data['studentId']) ? $request_data['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $c = isset($request_data['checklist']) ? $request_data['checklist'] : [];
            $notes = isset($request_data['notes']) ? $request_data['notes'] : '';
            $unit = (isset($request_data['unit']) && intval($request_data['unit']) === 2) ? 2 : 1;

            $stmt = $pdo->prepare('
                INSERT INTO self_checklists (
                    student_id, task_unit,
                    check_1_1, check_1_2, check_1_3,
                    check_2_1, check_2_2,
                    check_3_1, check_3_2, check_3_3,
                    check_4_1, check_4_2, check_4_3,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    check_1_1 = VALUES(check_1_1), check_1_2 = VALUES(check_1_2), check_1_3 = VALUES(check_1_3),
                    check_2_1 = VALUES(check_2_1), check_2_2 = VALUES(check_2_2),
                    check_3_1 = VALUES(check_3_1), check_3_2 = VALUES(check_3_2), check_3_3 = VALUES(check_3_3),
                    check_4_1 = VALUES(check_4_1), check_4_2 = VALUES(check_4_2), check_4_3 = VALUES(check_4_3),
                    notes = VALUES(notes)
            ');
            $stmt->execute([
                $studentId, $unit,
                isset($c['1.1']) ? $c['1.1'] : 'ต้องปรับปรุง',
                isset($c['1.2']) ? $c['1.2'] : 'ต้องปรับปรุง',
                isset($c['1.3']) ? $c['1.3'] : 'ต้องปรับปรุง',
                isset($c['2.1']) ? $c['2.1'] : 'ต้องปรับปรุง',
                isset($c['2.2']) ? $c['2.2'] : 'ต้องปรับปรุง',
                isset($c['3.1']) ? $c['3.1'] : 'ต้องปรับปรุง',
                isset($c['3.2']) ? $c['3.2'] : 'ต้องปรับปรุง',
                isset($c['3.3']) ? $c['3.3'] : 'ต้องปรับปรุง',
                isset($c['4.1']) ? $c['4.1'] : 'ต้องปรับปรุง',
                isset($c['4.2']) ? $c['4.2'] : 'ต้องปรับปรุง',
                isset($c['4.3']) ? $c['4.3'] : 'ต้องปรับปรุง',
                $notes
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'get_self_checklist':
            $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $unit = (isset($_GET['unit']) && intval($_GET['unit']) === 2) ? 2 : 1;
            $stmt = $pdo->prepare('SELECT * FROM self_checklists WHERE student_id = ? AND task_unit = ?');
            $stmt->execute([$studentId, $unit]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $row ? $row : null]);
            break;

        case 'save_peer_review':
            $studentId = isset($request_data['studentId']) ? $request_data['studentId'] : '';
            $reviewerId = isset($request_data['reviewerId']) ? $request_data['reviewerId'] : '';
            if (empty($studentId) || empty($reviewerId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID and Reviewer ID are required']);
                exit;
            }
            $s = isset($request_data['scores']) ? $request_data['scores'] : [];
            $strength = isset($request_data['strength']) ? $request_data['strength'] : '';
            $improvement = isset($request_data['improvement']) ? $request_data['improvement'] : '';
            $encouragement = isset($request_data['encouragement']) ? $request_data['encouragement'] : '';
            
            $stmt = $pdo->prepare('
                INSERT INTO peer_reviews (
                    student_id, reviewer_id,
                    score_1_1, score_1_2, score_1_3, score_1_4,
                    score_2_1, score_2_2,
                    score_3_1, score_3_2, score_3_3, score_3_4,
                    score_4_1, score_4_2, score_4_3,
                    strength, improvement, encouragement
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    score_1_1 = VALUES(score_1_1), score_1_2 = VALUES(score_1_2), score_1_3 = VALUES(score_1_3), score_1_4 = VALUES(score_1_4),
                    score_2_1 = VALUES(score_2_1), score_2_2 = VALUES(score_2_2),
                    score_3_1 = VALUES(score_3_1), score_3_2 = VALUES(score_3_2), score_3_3 = VALUES(score_3_3), score_3_4 = VALUES(score_3_4),
                    score_4_1 = VALUES(score_4_1), score_4_2 = VALUES(score_4_2), score_4_3 = VALUES(score_4_3),
                    strength = VALUES(strength), improvement = VALUES(improvement), encouragement = VALUES(encouragement)
            ');
            $stmt->execute([
                $studentId, $reviewerId,
                isset($s['1.1']) ? $s['1.1'] : 'ปรับปรุง',
                isset($s['1.2']) ? $s['1.2'] : 'ปรับปรุง',
                isset($s['1.3']) ? $s['1.3'] : 'ปรับปรุง',
                isset($s['1.4']) ? $s['1.4'] : 'ปรับปรุง',
                isset($s['2.1']) ? $s['2.1'] : 'ปรับปรุง',
                isset($s['2.2']) ? $s['2.2'] : 'ปรับปรุง',
                isset($s['3.1']) ? $s['3.1'] : 'ปรับปรุง',
                isset($s['3.2']) ? $s['3.2'] : 'ปรับปรุง',
                isset($s['3.3']) ? $s['3.3'] : 'ปรับปรุง',
                isset($s['3.4']) ? $s['3.4'] : 'ปรับปรุง',
                isset($s['4.1']) ? $s['4.1'] : 'ปรับปรุง',
                isset($s['4.2']) ? $s['4.2'] : 'ปรับปรุง',
                isset($s['4.3']) ? $s['4.3'] : 'ปรับปรุง',
                $strength, $improvement, $encouragement
            ]);
            echo json_encode(['success' => true]);
            break;

        case 'get_peer_reviews':
            $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $stmt = $pdo->prepare('
                SELECT pr.*, s.student_name AS reviewer_name 
                FROM peer_reviews pr
                JOIN students s ON pr.reviewer_id = s.student_id
                WHERE pr.student_id = ?
            ');
            $stmt->execute([$studentId]);
            $rows = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        // ============================================================
        //  ระบบจับคู่ประเมินเพื่อน (Peer Pairing)  รอบ: pretest/task1/posttest
        // ============================================================

        // ดึงคู่ของนักเรียนที่ล็อกอินอยู่ ตามรอบที่เลือก (ใช้ในหน้า evaluation.php?mode=peer)
        case 'get_my_peer_partner':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $round = isset($_GET['round']) ? trim($_GET['round']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $myId = $_SESSION['user']['id'];
            $stmt = $pdo->prepare('SELECT partner_code FROM peer_pairs WHERE round = ? AND student_code = ?');
            $stmt->execute([$round, $myId]);
            $prow = $stmt->fetch();
            if ($prow && !empty($prow['partner_code'])) {
                echo json_encode(['success' => true, 'partner' => $prow['partner_code']]);
            } else {
                // ยังไม่มีการจับคู่ในรอบนี้ → ให้หน้าเว็บ fallback ไปใช้ dropdown เดิม
                echo json_encode(['success' => true, 'partner' => null]);
            }
            break;

        // ดึงคู่ทั้งหมดของรอบที่เลือก (ครูเท่านั้น) สำหรับหน้าจัดการ peer_pairing.php
        case 'get_peer_pairs':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $round = isset($_GET['round']) ? trim($_GET['round']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $stmt = $pdo->prepare('SELECT student_code, partner_code FROM peer_pairs WHERE round = ?');
            $stmt->execute([$round]);
            $pairs = [];
            while ($row = $stmt->fetch()) {
                $pairs[$row['student_code']] = $row['partner_code'];
            }
            echo json_encode(['success' => true, 'pairs' => $pairs]);
            break;

        // บันทึกคู่ทั้งชุดของรอบที่เลือก (ครูเท่านั้น) — แทนที่ข้อมูลเดิมของรอบนั้นทั้งหมด
        case 'save_peer_pairs':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $round = isset($request_data['round']) ? trim($request_data['round']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $pairs = isset($request_data['pairs']) && is_array($request_data['pairs']) ? $request_data['pairs'] : [];

            $pdo->beginTransaction();
            try {
                // ลบคู่เดิมของรอบนี้ก่อน แล้วบันทึกใหม่ทั้งชุด
                $del = $pdo->prepare('DELETE FROM peer_pairs WHERE round = ?');
                $del->execute([$round]);

                $ins = $pdo->prepare('INSERT INTO peer_pairs (round, student_code, partner_code) VALUES (?, ?, ?)');
                $saved = 0;
                foreach ($pairs as $p) {
                    $sc = isset($p['student_code']) ? trim($p['student_code']) : '';
                    $pc = isset($p['partner_code']) ? trim($p['partner_code']) : '';
                    // ข้ามคู่ที่ยังไม่ได้กำหนด หรือจับคู่กับตนเอง
                    if ($sc === '' || $pc === '' || $sc === $pc) continue;
                    $ins->execute([$round, $sc, $pc]);
                    $saved++;
                }
                $pdo->commit();
                echo json_encode(['success' => true, 'saved' => $saved]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // จับคู่แบบสุ่มอัตโนมัติสำหรับรอบที่เลือก (ครูเท่านั้น) — บันทึกแล้วส่งผลคู่กลับไป
        case 'auto_pair_students':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }
            $round = isset($request_data['round']) ? trim($request_data['round']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            // จำกัดเฉพาะกลุ่มที่ต้องการได้ (ถ้าส่ง group มา) มิฉะนั้นใช้ทุกคน — ดึงห้องมาด้วยเพื่อจับคู่ภายในห้อง
            $group = isset($request_data['group']) ? trim($request_data['group']) : '';
            if ($group !== '') {
                $stmt = $pdo->prepare('SELECT student_id, classroom FROM students WHERE student_group = ? ORDER BY classroom ASC, student_id ASC');
                $stmt->execute([$group]);
                $rows = $stmt->fetchAll();
            } else {
                $rows = $pdo->query('SELECT student_id, classroom FROM students ORDER BY classroom ASC, student_id ASC')->fetchAll();
            }

            // จัดกลุ่มนักเรียนตามห้อง (เฉพาะคนที่มีห้อง) เพื่อจับคู่ภายในห้องเดียวกัน
            $byRoom = [];
            foreach ($rows as $r) {
                $room = ($r['classroom'] === null) ? '' : trim($r['classroom']);
                if ($room === '') continue; // ข้ามนักเรียนที่ยังไม่ได้กำหนดห้อง
                $byRoom[$room][] = $r['student_id'];
            }

            // จับคู่แบบไป-กลับ (A↔B) ภายในแต่ละห้อง ถ้าจำนวนคนเป็นเลขคี่ ให้ 3 คนสุดท้ายเป็นวง (A→B→C→A)
            $result = []; // [student_code => partner_code]
            foreach ($byRoom as $room => $ids) {
                $n = count($ids);
                if ($n < 2) continue; // ห้องมีคนเดียว จับคู่ไม่ได้
                shuffle($ids);
                $limit = ($n % 2 === 0) ? $n : $n - 3;
                for ($i = 0; $i < $limit; $i += 2) {
                    $result[$ids[$i]]     = $ids[$i + 1];
                    $result[$ids[$i + 1]] = $ids[$i];
                }
                if ($n % 2 === 1) {
                    // สามคนสุดท้ายจับเป็นวง เพื่อไม่ให้มีใครถูกทิ้ง
                    $a = $ids[$n - 3]; $b = $ids[$n - 2]; $c = $ids[$n - 1];
                    $result[$a] = $b;
                    $result[$b] = $c;
                    $result[$c] = $a;
                }
            }

            if (empty($result)) {
                echo json_encode(['success' => false, 'error' => 'ไม่พบห้องที่มีนักเรียนตั้งแต่ 2 คนขึ้นไปสำหรับการจับคู่ (โปรดกำหนดห้องเรียนให้นักเรียนก่อน)']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                $del = $pdo->prepare('DELETE FROM peer_pairs WHERE round = ?');
                $del->execute([$round]);
                $ins = $pdo->prepare('INSERT INTO peer_pairs (round, student_code, partner_code) VALUES (?, ?, ?)');
                foreach ($result as $sc => $pc) {
                    $ins->execute([$round, $sc, $pc]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }

            // ส่งกลับเป็น map student_code → partner_code เพื่อให้หน้าเว็บอัปเดตทันที
            echo json_encode(['success' => true, 'pairs' => $result, 'count' => count($result)]);
            break;

        // ============================================================
        //  ระบบให้นักเรียนจับคู่ประเมินเพื่อนกันเอง (Student-initiated Peer Matching)
        //  นักเรียนฝ่ายหนึ่งส่งคำขอ อีกฝ่ายกดรับ → ระบบสร้างคู่ไป-กลับใน peer_pairs ให้อัตโนมัติ
        //  แยกตามรอบ/หน่วย — พอขึ้นรอบใหม่ต้องส่งคำขอกันใหม่
        // ============================================================

        // ดึงสถานะการจับคู่ของนักเรียนที่ล็อกอินอยู่สำหรับรอบที่เลือก
        // คืนค่า: คู่ปัจจุบัน (ถ้ามี), คำขอที่ส่งออก (รอตอบรับ), คำขอที่เข้ามา, และรายชื่อเพื่อนในห้องที่ขอได้
        case 'get_peer_match_status':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $round = isset($_GET['round']) ? trim($_GET['round']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $myId = $_SESSION['user']['id'];

            // ข้อมูลห้อง/กลุ่มของฉัน (จับคู่ได้เฉพาะเพื่อนในห้องเดียวกัน)
            $meStmt = $pdo->prepare('SELECT classroom, student_group FROM students WHERE student_id = ?');
            $meStmt->execute([$myId]);
            $me = $meStmt->fetch();
            $myRoom = ($me && $me['classroom'] !== null) ? trim($me['classroom']) : '';

            // คู่ปัจจุบันของฉัน (ถ้าจับคู่แล้ว)
            $pStmt = $pdo->prepare('SELECT partner_code FROM peer_pairs WHERE round = ? AND student_code = ?');
            $pStmt->execute([$round, $myId]);
            $pRow = $pStmt->fetch();
            $partner = ($pRow && !empty($pRow['partner_code'])) ? $pRow['partner_code'] : null;

            // ผู้ที่จับคู่แล้วในรอบนี้ทั้งหมด (ใช้กรองว่าใครยังว่าง)
            $pairedSet = [];
            $allPaired = $pdo->prepare('SELECT student_code FROM peer_pairs WHERE round = ?');
            $allPaired->execute([$round]);
            while ($r = $allPaired->fetch()) { $pairedSet[$r['student_code']] = true; }

            // คำขอที่ค้างอยู่ (pending) ที่เกี่ยวกับฉันในรอบนี้
            $reqStmt = $pdo->prepare('
                SELECT r.requester_code, r.target_code, s.student_name, s.classroom
                FROM peer_requests r
                JOIN students s ON s.student_id = CASE WHEN r.requester_code = ? THEN r.target_code ELSE r.requester_code END
                WHERE r.round = ? AND r.status = "pending" AND (r.requester_code = ? OR r.target_code = ?)
            ');
            $reqStmt->execute([$myId, $round, $myId, $myId]);
            $incoming = [];   // คำขอที่คนอื่นส่งมาหาฉัน (ฉันเป็น target)
            $outgoing = null; // คำขอที่ฉันส่งออกไป (ฉันเป็น requester)
            while ($r = $reqStmt->fetch()) {
                if ($r['requester_code'] === $myId) {
                    $outgoing = ['code' => $r['target_code'], 'name' => $r['student_name']];
                } else {
                    $incoming[] = ['code' => $r['requester_code'], 'name' => $r['student_name']];
                }
            }

            // รายชื่อเพื่อนร่วมห้อง (ยกเว้นตนเอง) พร้อมสถานะแต่ละคน
            $classmates = [];
            if ($myRoom !== '') {
                $cmStmt = $pdo->prepare('SELECT student_id, student_name FROM students WHERE classroom = ? AND student_id != ? ORDER BY student_id ASC');
                $cmStmt->execute([$myRoom, $myId]);
                $outCode = $outgoing ? $outgoing['code'] : null;
                $incCodes = array_column($incoming, 'code');
                while ($c = $cmStmt->fetch()) {
                    $cid = $c['student_id'];
                    if ($partner !== null) {
                        $state = ($cid === $partner) ? 'paired_with_me' : 'unavailable';
                    } elseif ($cid === $outCode) {
                        $state = 'outgoing_pending';
                    } elseif (in_array($cid, $incCodes, true)) {
                        $state = 'incoming_pending';
                    } elseif (isset($pairedSet[$cid])) {
                        $state = 'paired_other';
                    } else {
                        $state = 'available';
                    }
                    $classmates[] = ['code' => $cid, 'name' => $c['student_name'], 'state' => $state];
                }
            }

            echo json_encode([
                'success'    => true,
                'round'      => $round,
                'myRoom'     => $myRoom,
                'partner'    => $partner,
                'incoming'   => $incoming,
                'outgoing'   => $outgoing,
                'classmates' => $classmates,
            ]);
            break;

        // นักเรียนส่งคำขอจับคู่ไปยังเพื่อน (target) ในรอบที่เลือก
        // ถ้าเพื่อนคนนั้นเคยส่งคำขอหาเราอยู่แล้ว (pending) → ถือว่าตกลงกันทั้งคู่ จับคู่ให้ทันที
        case 'send_peer_request':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $round  = isset($request_data['round']) ? trim($request_data['round']) : '';
            $target = isset($request_data['target']) ? trim($request_data['target']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $myId = $_SESSION['user']['id'];
            if ($target === '' || $target === $myId) {
                echo json_encode(['success' => false, 'error' => 'กรุณาเลือกเพื่อนที่ต้องการจับคู่ (ไม่ใช่ตนเอง)']);
                exit;
            }

            // ตรวจว่าเป้าหมายมีอยู่จริงและอยู่ห้องเดียวกัน
            $meStmt = $pdo->prepare('SELECT classroom FROM students WHERE student_id = ?');
            $meStmt->execute([$myId]);
            $meRow = $meStmt->fetch();
            $myRoom = ($meRow && $meRow['classroom'] !== null) ? trim($meRow['classroom']) : '';
            $tStmt = $pdo->prepare('SELECT classroom FROM students WHERE student_id = ?');
            $tStmt->execute([$target]);
            $tRow = $tStmt->fetch();
            if (!$tRow) {
                echo json_encode(['success' => false, 'error' => 'ไม่พบเพื่อนที่ระบุ']);
                exit;
            }
            $tRoom = ($tRow['classroom'] !== null) ? trim($tRow['classroom']) : '';
            if ($myRoom === '' || $tRoom === '' || $myRoom !== $tRoom) {
                echo json_encode(['success' => false, 'error' => 'จับคู่ได้เฉพาะเพื่อนที่อยู่ห้องเดียวกันเท่านั้น']);
                exit;
            }

            // ตรวจว่าฉันหรือเป้าหมายจับคู่ไปแล้วหรือยังในรอบนี้
            $chk = $pdo->prepare('SELECT student_code FROM peer_pairs WHERE round = ? AND student_code IN (?, ?)');
            $chk->execute([$round, $myId, $target]);
            $pairedNow = $chk->fetchAll(PDO::FETCH_COLUMN);
            if (in_array($myId, $pairedNow, true)) {
                echo json_encode(['success' => false, 'error' => 'คุณจับคู่ในรอบนี้ไปแล้ว']);
                exit;
            }
            if (in_array($target, $pairedNow, true)) {
                echo json_encode(['success' => false, 'error' => 'เพื่อนคนนี้จับคู่กับคนอื่นในรอบนี้ไปแล้ว']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                // ถ้าเป้าหมายเคยส่งคำขอหาเราอยู่แล้ว (pending) → จับคู่ให้ทันที (ตกลงกันทั้งสองฝ่าย)
                $recip = $pdo->prepare('SELECT id FROM peer_requests WHERE round = ? AND requester_code = ? AND target_code = ? AND status = "pending"');
                $recip->execute([$round, $target, $myId]);
                $matchedNow = false;
                if ($recip->fetch()) {
                    peer_match_create_pair($pdo, $round, $myId, $target);
                    $matchedNow = true;
                } else {
                    // ยกเลิกคำขอที่ฉันเคยส่งค้างไว้ก่อนหน้า (ให้ค้างได้ครั้งละหนึ่งคน)
                    $canc = $pdo->prepare('UPDATE peer_requests SET status = "cancelled", responded_at = NOW() WHERE round = ? AND requester_code = ? AND status = "pending"');
                    $canc->execute([$round, $myId]);
                    // บันทึกคำขอใหม่ (ถ้าเคยมีแถวเดิมกับคนนี้ที่ถูกปฏิเสธ/ยกเลิก ให้เปิดใหม่เป็น pending)
                    $ins = $pdo->prepare('
                        INSERT INTO peer_requests (round, requester_code, target_code, status)
                        VALUES (?, ?, ?, "pending")
                        ON DUPLICATE KEY UPDATE status = "pending", created_at = NOW(), responded_at = NULL
                    ');
                    $ins->execute([$round, $myId, $target]);
                }
                $pdo->commit();
                echo json_encode(['success' => true, 'matched' => $matchedNow]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // นักเรียนตอบคำขอที่เข้ามา: accept (รับ → จับคู่ไป-กลับ) หรือ decline (ปฏิเสธ)
        case 'respond_peer_request':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $round     = isset($request_data['round']) ? trim($request_data['round']) : '';
            $requester = isset($request_data['requester']) ? trim($request_data['requester']) : '';
            $decision  = isset($request_data['decision']) ? trim($request_data['decision']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            if (!in_array($decision, ['accept', 'decline'], true)) {
                echo json_encode(['success' => false, 'error' => 'คำสั่งไม่ถูกต้อง']);
                exit;
            }
            $myId = $_SESSION['user']['id'];

            // ต้องมีคำขอ pending ที่คนนี้ส่งมาหาฉันจริง
            $find = $pdo->prepare('SELECT id FROM peer_requests WHERE round = ? AND requester_code = ? AND target_code = ? AND status = "pending"');
            $find->execute([$round, $requester, $myId]);
            $reqRow = $find->fetch();
            if (!$reqRow) {
                echo json_encode(['success' => false, 'error' => 'ไม่พบคำขอนี้ (อาจถูกยกเลิกไปแล้ว)']);
                exit;
            }

            if ($decision === 'decline') {
                $upd = $pdo->prepare('UPDATE peer_requests SET status = "declined", responded_at = NOW() WHERE id = ?');
                $upd->execute([$reqRow['id']]);
                echo json_encode(['success' => true, 'matched' => false]);
                break;
            }

            // accept: ตรวจว่าทั้งสองฝ่ายยังว่างในรอบนี้ แล้วจับคู่ไป-กลับ
            $chk = $pdo->prepare('SELECT student_code FROM peer_pairs WHERE round = ? AND student_code IN (?, ?)');
            $chk->execute([$round, $myId, $requester]);
            $pairedNow = $chk->fetchAll(PDO::FETCH_COLUMN);
            if (in_array($myId, $pairedNow, true)) {
                echo json_encode(['success' => false, 'error' => 'คุณจับคู่ในรอบนี้ไปแล้ว']);
                exit;
            }
            if (in_array($requester, $pairedNow, true)) {
                echo json_encode(['success' => false, 'error' => 'เพื่อนคนนี้จับคู่กับคนอื่นไปแล้ว']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                peer_match_create_pair($pdo, $round, $myId, $requester);
                $pdo->commit();
                echo json_encode(['success' => true, 'matched' => true, 'partner' => $requester]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        // นักเรียนยกเลิกคำขอที่ตนเองส่งออกไป (ยังไม่ถูกตอบรับ)
        case 'cancel_peer_request':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $round  = isset($request_data['round']) ? trim($request_data['round']) : '';
            $target = isset($request_data['target']) ? trim($request_data['target']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $myId = $_SESSION['user']['id'];
            $upd = $pdo->prepare('UPDATE peer_requests SET status = "cancelled", responded_at = NOW() WHERE round = ? AND requester_code = ? AND target_code = ? AND status = "pending"');
            $upd->execute([$round, $myId, $target]);
            echo json_encode(['success' => true]);
            break;

        // นักเรียนยกเลิกการจับคู่ที่ตกลงกันแล้ว (ปลดคู่ทั้งสองฝั่งในรอบนี้ เพื่อขอใหม่ได้)
        case 'unpair_peer_match':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นนักเรียน']);
                exit;
            }
            $round = isset($request_data['round']) ? trim($request_data['round']) : '';
            if (!in_array($round, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            $myId = $_SESSION['user']['id'];
            $pStmt = $pdo->prepare('SELECT partner_code FROM peer_pairs WHERE round = ? AND student_code = ?');
            $pStmt->execute([$round, $myId]);
            $pRow = $pStmt->fetch();
            if (!$pRow || empty($pRow['partner_code'])) {
                echo json_encode(['success' => false, 'error' => 'คุณยังไม่มีคู่ในรอบนี้']);
                exit;
            }
            $partner = $pRow['partner_code'];
            $pdo->beginTransaction();
            try {
                $del = $pdo->prepare('DELETE FROM peer_pairs WHERE round = ? AND student_code IN (?, ?)');
                $del->execute([$round, $myId, $partner]);
                // คืนคำขอที่จับคู่กันไว้ให้เป็น cancelled เพื่อให้ส่งขอใหม่ได้
                $upd = $pdo->prepare('UPDATE peer_requests SET status = "cancelled", responded_at = NOW() WHERE round = ? AND status = "accepted" AND ((requester_code = ? AND target_code = ?) OR (requester_code = ? AND target_code = ?))');
                $upd->execute([$round, $myId, $partner, $partner, $myId]);
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'save_learning_reflection':
            $studentId = isset($request_data['studentId']) ? $request_data['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $cs = isset($request_data['content_structure']) ? $request_data['content_structure'] : '';
            $lm = isset($request_data['language_mechanics']) ? $request_data['language_mechanics'] : '';
            $fa = isset($request_data['feedback_applied']) ? $request_data['feedback_applied'] : '';
            $fg = isset($request_data['future_goals']) ? $request_data['future_goals'] : '';
            $unit = (isset($request_data['unit']) && intval($request_data['unit']) === 2) ? 2 : 1;

            $stmt = $pdo->prepare('
                INSERT INTO learning_reflections (
                    student_id, task_unit, content_structure, language_mechanics, feedback_applied, future_goals
                ) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    content_structure = VALUES(content_structure),
                    language_mechanics = VALUES(language_mechanics),
                    feedback_applied = VALUES(feedback_applied),
                    future_goals = VALUES(future_goals)
            ');
            $stmt->execute([$studentId, $unit, $cs, $lm, $fa, $fg]);
            echo json_encode(['success' => true]);
            break;

        case 'get_learning_reflection':
            $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $unit = (isset($_GET['unit']) && intval($_GET['unit']) === 2) ? 2 : 1;
            $stmt = $pdo->prepare('SELECT * FROM learning_reflections WHERE student_id = ? AND task_unit = ?');
            $stmt->execute([$studentId, $unit]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $row ? $row : null]);
            break;

        case 'get_reflection_summary':
            // ตัวกรองกลุ่มการวิจัย (ทดลอง/ตัวอย่าง) — ถ้าไม่ส่งมา = รวมทุกกลุ่ม
            $refGroup = isset($_GET['group']) ? trim($_GET['group']) : '';
            $hasRefGroup = ($refGroup !== '');

            // ตัวกรองหน่วยการเรียน (task_unit) — แดชบอร์ดครูดูแยกหน่วยที่ 1 / หน่วยที่ 2 ได้ (ค่าเริ่มต้น = 1)
            $refUnit = (isset($_GET['unit']) && intval($_GET['unit']) === 2) ? 2 : 1;

            // ตัวช่วยสร้างเงื่อนไข WHERE ตามกลุ่มบนตาราง students ที่ระบุ alias
            $grpWhere = function($alias) use ($hasRefGroup) {
                return $hasRefGroup ? (" WHERE {$alias}.student_group = ?") : '';
            };
            $grpParam = $hasRefGroup ? [$refGroup] : [];

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM students s' . $grpWhere('s'));
            $stmt->execute($grpParam);
            $total_students_res = $stmt->fetchColumn();

            // ตัวช่วยสร้าง WHERE ที่รวมเงื่อนไขหน่วยการเรียน (task_unit) + กลุ่ม สำหรับตารางสะท้อนคิด
            // $col = ชื่อคอลัมน์ task_unit พร้อม alias (เช่น 'wp.task_unit')
            $unitGrpWhere = function($col) use ($hasRefGroup) {
                return " WHERE {$col} = ?" . ($hasRefGroup ? ' AND s.student_group = ?' : '');
            };
            $unitGrpParam = array_merge([$refUnit], $grpParam);

            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT wp.student_id) FROM writing_problems wp JOIN students s ON wp.student_id = s.student_id' . $unitGrpWhere('wp.task_unit'));
            $stmt->execute($unitGrpParam);
            $prob_count = $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT chk.student_id) FROM self_checklists chk JOIN students s ON chk.student_id = s.student_id' . $unitGrpWhere('chk.task_unit'));
            $stmt->execute($unitGrpParam);
            $chk_count = $stmt->fetchColumn();

            // การประเมินเพื่อน (peer_reviews) ไม่ได้แยกตามหน่วยการเรียน — นับรวมทุกหน่วยตามเดิม
            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT pr.student_id) FROM peer_reviews pr JOIN students s ON pr.student_id = s.student_id' . $grpWhere('s'));
            $stmt->execute($grpParam);
            $peer_count = $stmt->fetchColumn();

            $stmt = $pdo->prepare('SELECT COUNT(DISTINCT lr.student_id) FROM learning_reflections lr JOIN students s ON lr.student_id = s.student_id' . $unitGrpWhere('lr.task_unit'));
            $stmt->execute($unitGrpParam);
            $ref_count = $stmt->fetchColumn();

            // ปัญหาล่าสุด
            $stmt_probs = $pdo->prepare('
                SELECT wp.*, s.student_name
                FROM writing_problems wp
                JOIN students s ON wp.student_id = s.student_id' . $grpWhere('s') . '
                ORDER BY wp.created_at DESC LIMIT 5
            ');
            $stmt_probs->execute($grpParam);
            $recent_problems = $stmt_probs->fetchAll();

            // รีวิวล่าสุด
            $stmt_peers = $pdo->prepare('
                SELECT pr.*, s.student_name, r.student_name AS reviewer_name
                FROM peer_reviews pr
                JOIN students s ON pr.student_id = s.student_id
                JOIN students r ON pr.reviewer_id = r.student_id' . $grpWhere('s') . '
                ORDER BY pr.created_at DESC LIMIT 5
            ');
            $stmt_peers->execute($grpParam);
            $recent_peers = $stmt_peers->fetchAll();

            // ข้อมูลของนักเรียนทุกคนสำหรับภาพรวมชั้นเรียน
            $stmt_all = $pdo->prepare('
                SELECT
                    s.student_id,
                    s.student_name,
                    s.student_group,
                    wp.prob_1_1, wp.sol_1_1, wp.prob_1_2, wp.sol_1_2, wp.prob_1_3, wp.sol_1_3,
                    wp.prob_2_1, wp.sol_2_1, wp.prob_2_2, wp.sol_2_2,
                    wp.prob_3_1, wp.sol_3_1, wp.prob_3_2, wp.sol_3_2, wp.prob_3_3, wp.sol_3_3,
                    wp.prob_4_1, wp.sol_4_1, wp.prob_4_2, wp.sol_4_2, wp.prob_4_3, wp.sol_4_3,
                    chk.check_1_1, chk.check_1_2, chk.check_1_3,
                    chk.check_2_1, chk.check_2_2,
                    chk.check_3_1, chk.check_3_2, chk.check_3_3,
                    chk.check_4_1, chk.check_4_2, chk.check_4_3,
                    chk.notes AS checklist_notes,
                    ref.content_structure, ref.language_mechanics, ref.feedback_applied, ref.future_goals
                FROM students s
                LEFT JOIN writing_problems wp ON s.student_id = wp.student_id AND wp.task_unit = ?
                LEFT JOIN self_checklists chk ON s.student_id = chk.student_id AND chk.task_unit = ?
                LEFT JOIN learning_reflections ref ON s.student_id = ref.student_id AND ref.task_unit = ?' . $grpWhere('s') . '
                ORDER BY s.student_id ASC
            ');
            $stmt_all->execute(array_merge([$refUnit, $refUnit, $refUnit], $grpParam));
            $students_details = $stmt_all->fetchAll();

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_students' => (int)$total_students_res,
                    'problems_completed' => (int)$prob_count,
                    'checklists_completed' => (int)$chk_count,
                    'peer_reviews_completed' => (int)$peer_count,
                    'reflections_completed' => (int)$ref_count
                ],
                'recent_problems' => $recent_problems,
                'recent_peers' => $recent_peers,
                'students_details' => $students_details
            ]);
            break;

        // API วิเคราะห์คำสำคัญ (Keyword Frequency) สำหรับทำ Word Cloud ในหน้าครู
        // คืนความถี่ของคำที่พบบ่อยในแบบบันทึกอุปสรรคการเขียนและบทสะท้อนคิด
        case 'get_reflection_keywords':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครูเพื่อเข้าถึงข้อมูลนี้']);
                exit;
            }

            // คำสำคัญที่ "สื่อถึงปัญหา/ทักษะการเขียนตามเกณฑ์" เท่านั้น (คัดมาแล้ว ไม่นับคำทั่วไป)
            // เลือกให้ไม่ซ้อนทับกันเป็น substring เพื่อไม่ให้ตัวเลขความถี่บวมเกินจริง
            $targetKeywords = [
                // ด้านเนื้อหาสาระ
                'ประเด็น', 'ใจความ', 'แก่นเรื่อง', 'เนื้อหา', 'สาระ',
                'ขยายความ', 'เหตุผล', 'ตัวอย่าง', 'รายละเอียด',
                // ด้านองค์ประกอบและการลำดับ
                'โครงเรื่อง', 'โครงสร้าง', 'องค์ประกอบ', 'คำนำ', 'สรุป', 'ย่อหน้า', 'ลำดับ', 'เชื่อมโยง', 'เรียบเรียง',
                // ด้านสำนวนภาษา
                'ประโยค', 'ไวยากรณ์', 'คำศัพท์', 'คำเชื่อม', 'ระดับภาษา', 'ภาษาพูด', 'สำนวน', 'คำซ้ำ', 'เลือกใช้คำ',
                // ด้านอักขรวิธีและกลไกการเขียน
                'สะกด', 'เว้นวรรค', 'วรรคตอน', 'เครื่องหมาย', 'ลายมือ', 'เรียบร้อย', 'สะอาด',
                // คำที่บ่งชี้ว่าเป็นปัญหา/อุปสรรคโดยตรง
                'ปัญหา', 'อุปสรรค', 'ยาก', 'สับสน', 'ผิดพลาด', 'ไม่เข้าใจ', 'กังวล', 'เวลา'
            ];

            // นับเฉพาะคำสำคัญที่คัดไว้ (substring) — ไม่แยกคำทั่วไป เพื่อให้ Word Cloud
            // แสดงเฉพาะคำที่เกี่ยวกับปัญหา/เกณฑ์การเขียนจริง ไม่ใช่คำที่ไม่สื่อความหมาย
            $analyzeKw = function ($texts) use ($targetKeywords) {
                $counts = [];
                foreach ($targetKeywords as $kw) { $counts[$kw] = 0; }
                foreach ($texts as $text) {
                    if (!$text) continue;
                    $lower = mb_strtolower($text, 'UTF-8');
                    foreach ($targetKeywords as $kw) {
                        $c = mb_substr_count($lower, $kw);
                        if ($c > 0) $counts[$kw] += $c;
                    }
                }
                $arr = [];
                foreach ($counts as $k => $v) {
                    if ($v > 0) $arr[] = ['keyword' => $k, 'count' => $v];
                }
                usort($arr, function ($a, $b) { return $b['count'] - $a['count']; });
                return array_slice($arr, 0, 25);
            };

            // รวมข้อความจากแบบบันทึกอุปสรรค (prob_/sol_) ทุกด้าน
            $obTexts = [];
            $probCols = ['prob_1_1','sol_1_1','prob_1_2','sol_1_2','prob_1_3','sol_1_3',
                'prob_2_1','sol_2_1','prob_2_2','sol_2_2',
                'prob_3_1','sol_3_1','prob_3_2','sol_3_2','prob_3_3','sol_3_3',
                'prob_4_1','sol_4_1','prob_4_2','sol_4_2','prob_4_3','sol_4_3'];
            $probRows = $pdo->query('SELECT * FROM writing_problems')->fetchAll();
            foreach ($probRows as $r) {
                foreach ($probCols as $c) {
                    if (!empty($r[$c]) && trim($r[$c]) !== '') $obTexts[] = $r[$c];
                }
            }

            // บทสะท้อนคิด: แยกตามคำถามแต่ละข้อ (per-field) และรวมทั้งหมด (combined)
            $refCols = ['content_structure', 'language_mechanics', 'feedback_applied', 'future_goals'];
            $refRows = $pdo->query('SELECT content_structure, language_mechanics, feedback_applied, future_goals FROM learning_reflections')->fetchAll();
            $refByField = [];
            $refTexts = [];
            foreach ($refCols as $c) {
                $fieldTexts = [];
                foreach ($refRows as $r) {
                    if (!empty($r[$c]) && trim($r[$c]) !== '') {
                        $fieldTexts[] = $r[$c];
                        $refTexts[] = $r[$c];
                    }
                }
                $refByField[$c] = $analyzeKw($fieldTexts);
            }

            echo json_encode([
                'success'             => true,
                'obstacles'           => $analyzeKw($obTexts),
                'reflections'         => $analyzeKw($refTexts),
                'reflections_by_field' => $refByField
            ]);
            break;

        // N. ดึงข้อมูลบันทึกการสะท้อนการเรียนรู้ (Learning Reflection)
        case 'get_reflection_data':
            $studentId = isset($_GET['studentId']) ? trim($_GET['studentId']) : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                exit;
            }
            $unit = (isset($_GET['unit']) && intval($_GET['unit']) === 2) ? 2 : 1;
            $stmt = $pdo->prepare('SELECT * FROM learning_reflections WHERE student_id = ? AND task_unit = ?');
            $stmt->execute([$studentId, $unit]);
            $row = $stmt->fetch();
            if ($row) {
                echo json_encode([
                    'success' => true,
                    'found'   => true,
                    'data' => [
                        'content_structure'  => $row['content_structure']  ?? '',
                        'language_mechanics' => $row['language_mechanics'] ?? '',
                        'feedback_applied'   => $row['feedback_applied']   ?? '',
                        'future_goals'       => $row['future_goals']       ?? ''
                    ]
                ]);
            } else {
                echo json_encode(['success' => true, 'found' => false, 'data' => null]);
            }
            break;

        // ดึงข้อมูลภาพรวมวิจัยเชิงลึก (การประเมินสถิติและเชิงคุณภาพ)
        case 'get_classroom_research_data':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครูเพื่อเข้าถึงข้อมูลนี้']);
                exit;
            }
            
            // 1. ดึงรายชื่อนักเรียนทั้งหมด (พร้อมห้อง + กลุ่ม)
            $stmt_std = $pdo->query('SELECT student_id, student_name, classroom, student_group FROM students ORDER BY student_id ASC');
            $students = $stmt_std->fetchAll();
            foreach ($students as &$st) { $st['student_name'] = formatNamePrefix($st['student_name']); }
            unset($st);
            
            // 2. ดึงข้อมูลการประเมินทั้งหมด
            $stmt_eval = $pdo->query('SELECT * FROM evaluations ORDER BY student_id ASC, timestamp DESC');
            $evaluations = $stmt_eval->fetchAll();
            
            // 3. ดึงข้อมูล writing_problems
            $stmt_prob = $pdo->query('SELECT * FROM writing_problems');
            $problems = $stmt_prob->fetchAll();
            
            // 4. ดึงข้อมูล self_checklists
            $stmt_chk = $pdo->query('SELECT * FROM self_checklists');
            $checklists = $stmt_chk->fetchAll();
            
            // 5. ดึงข้อมูล peer_reviews
            $stmt_peer = $pdo->query('SELECT * FROM peer_reviews');
            $peer_reviews = $stmt_peer->fetchAll();
            
            // 6. ดึงข้อมูล learning_reflections
            $stmt_ref = $pdo->query('SELECT * FROM learning_reflections');
            $reflections = $stmt_ref->fetchAll();
            
            echo json_encode([
                'success' => true,
                'students' => $students,
                'evaluations' => $evaluations,
                'problems' => $problems,
                'checklists' => $checklists,
                'peer_reviews' => $peer_reviews,
                'reflections' => $reflections
            ]);
            break;

        // Essay: บันทึกเรียงความของนักเรียน
        case 'save_essay':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
                echo json_encode(['success' => false, 'error' => 'ต้องเข้าสู่ระบบในฐานะนักเรียนก่อนบันทึกเรียงความ']);
                exit;
            }
            $studentId    = $_SESSION['user']['id'];
            $essayPhase   = isset($request_data['essay_phase'])   ? trim($request_data['essay_phase'])   : 'task1_d1';

            // ครูต้อง "เปิดรับ" รอบนั้นก่อน นักเรียนจึงจะส่งได้ (กันการส่งผิดรอบ)
            // essay_phase เช่น task1_d2 จับคู่กับรอบหัวข้อ task1 เพื่อตรวจสถานะเปิด/ปิดรับ
            $openMap = essay_open_map($pdo);
            $topicPhaseForSave = essay_topic_phase($essayPhase);
            if (array_key_exists($topicPhaseForSave, $openMap) && $openMap[$topicPhaseForSave] === false) {
                echo json_encode(['success' => false, 'error' => 'คุณครูยังไม่เปิดรับการส่งงานรอบนี้ กรุณาตรวจสอบรอบที่เปิดรับอีกครั้ง']);
                exit;
            }

            // เนื้อหาแยกเป็น 3 ส่วน: ส่วนนำ / เนื้อหา (หลายย่อหน้า) / สรุป — ไม่มีชื่อเรื่องจากนักเรียนแล้ว
            $intro      = isset($request_data['introduction']) ? trim((string)$request_data['introduction']) : '';
            $bodyArr    = (isset($request_data['body']) && is_array($request_data['body'])) ? $request_data['body'] : null;
            $conclusion = isset($request_data['conclusion']) ? trim((string)$request_data['conclusion']) : '';

            // รองรับ payload รูปแบบเดิม (essay_content เป็น JSON) เผื่อไคลเอนต์เก่า
            if ($bodyArr === null && isset($request_data['essay_content'])) {
                $obj = json_decode((string)$request_data['essay_content'], true);
                if (is_array($obj)) {
                    if ($intro === '')      $intro = (string)($obj['introduction'] ?? '');
                    $bodyArr = (isset($obj['body']) && is_array($obj['body'])) ? $obj['body'] : [];
                    if ($conclusion === '') $conclusion = (string)($obj['conclusion'] ?? '');
                }
            }
            if ($bodyArr === null) $bodyArr = [];
            // ตัดย่อหน้าว่างทิ้ง และเก็บทุกย่อหน้าเป็น JSON array ไว้ในคอลัมน์เนื้อหาคอลัมน์เดียว
            $bodyArr  = array_values(array_filter(array_map(function ($p) { return trim((string)$p); }, $bodyArr), function ($p) { return $p !== ''; }));
            $bodyJson = json_encode($bodyArr, JSON_UNESCAPED_UNICODE);

            // นับจำนวนคำจากทั้ง 3 ส่วนรวมกัน
            $allText   = trim($intro . "\n" . implode("\n", $bodyArr) . "\n" . $conclusion);
            $wordCount = $allText !== '' ? count(preg_split('/[\s\n\r]+/u', $allText, -1, PREG_SPLIT_NO_EMPTY)) : 0;

            $stmt = $pdo->prepare('
                INSERT INTO student_essays (student_id, essay_phase, intro_content, body_content, conclusion_content, word_count)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    intro_content      = VALUES(intro_content),
                    body_content       = VALUES(body_content),
                    conclusion_content = VALUES(conclusion_content),
                    word_count         = VALUES(word_count),
                    updated_at         = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$studentId, $essayPhase, $intro, $bodyJson, $conclusion, $wordCount]);
            echo json_encode(['success' => true, 'word_count' => $wordCount]);
            break;

        // Essay: หัวข้อเรียงความที่ครูกำหนดต่อรอบ (ก่อนเรียน/หน่วยที่ 1/หลังเรียน)
        case 'get_essay_topics':
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                exit;
            }
            echo json_encode(['success' => true, 'topics' => essay_topics_map($pdo), 'open' => essay_open_map($pdo)]);
            break;

        // Essay: ครูบันทึกหัวข้อเรียงความของรอบใดรอบหนึ่ง
        case 'save_essay_topic':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'เฉพาะครูเท่านั้นที่กำหนดหัวข้อได้']);
                exit;
            }
            $tPhase = isset($request_data['phase']) ? trim((string)$request_data['phase']) : '';
            $tTopic = isset($request_data['topic']) ? trim((string)$request_data['topic']) : '';
            if (!in_array($tPhase, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบไม่ถูกต้อง']);
                exit;
            }
            if (mb_strlen($tTopic, 'UTF-8') > 500) { $tTopic = mb_substr($tTopic, 0, 500, 'UTF-8'); }
            $stmt = $pdo->prepare('
                INSERT INTO essay_topics (phase, topic) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE topic = VALUES(topic), updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$tPhase, $tTopic]);
            echo json_encode(['success' => true]);
            break;

        // Essay: ครูเปิด/ปิดรับการส่งเรียงความของรอบใดรอบหนึ่ง (นักเรียนส่งได้เฉพาะรอบที่เปิดรับ)
        case 'save_essay_phase_open':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'เฉพาะครูเท่านั้นที่กำหนดการเปิด/ปิดรับได้']);
                exit;
            }
            $oPhase = isset($request_data['phase']) ? trim((string)$request_data['phase']) : '';
            $oOpen  = !empty($request_data['is_open']) ? 1 : 0;
            if (!in_array($oPhase, ['pretest', 'task1', 'task2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบไม่ถูกต้อง']);
                exit;
            }
            // สร้างแถวรอบนี้ไว้ก่อนหากยังไม่มี แล้วอัปเดตสถานะเปิด/ปิดรับ
            $stmt = $pdo->prepare('
                INSERT INTO essay_topics (phase, is_open) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE is_open = VALUES(is_open), updated_at = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$oPhase, $oOpen]);
            echo json_encode(['success' => true]);
            break;

        // Essay: ดึงเรียงความของนักเรียนคนนั้น (นักเรียนดูของตัวเอง)
        case 'get_essay':
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                exit;
            }
            $studentId  = isset($_GET['studentId'])  ? trim($_GET['studentId'])  : $_SESSION['user']['id'];
            $essayPhase = isset($_GET['essay_phase']) ? trim($_GET['essay_phase']) : 'task1_d1';
            // ดึงข้อมูลเจ้าของผลงาน (ชื่อ/ชั้น) มาด้วย เพื่อแสดงหัวกระดาษแบบข้อสอบ
            $stmt = $pdo->prepare('
                SELECT se.*, s.student_name, s.classroom
                FROM student_essays se
                LEFT JOIN students s ON se.student_id = s.student_id
                WHERE se.student_id = ? AND se.essay_phase = ?
            ');
            $stmt->execute([$studentId, $essayPhase]);
            $row = $stmt->fetch();
            if ($row) {
                if (isset($row['student_name'])) { $row['student_name'] = formatNamePrefix($row['student_name']); }
                // ประกอบ essay_content (JSON) จากคอลัมน์แยกส่วน + เติมหัวข้อที่ครูกำหนดเป็น essay_title
                $row['essay_content'] = essay_compose_content($row['intro_content'] ?? null, $row['body_content'] ?? null, $row['conclusion_content'] ?? null);
                $topics = essay_topics_map($pdo);
                $row['essay_title'] = $topics[essay_topic_phase($row['essay_phase'])] ?? '';
            }
            echo json_encode(['success' => true, 'found' => (bool)$row, 'data' => $row ?: null]);
            break;

        // Essay: ดึงเรียงความทั้งชั้น (สำหรับครู/แดชบอร์ด)
        // light=1 : โหมดเบา — คืนเฉพาะสถานะการส่ง (ไม่รวมเนื้อหาเต็ม) เพื่อให้หน้า Essay Viewer โหลดเร็ว
        case 'get_all_essays':
            if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'expert'])) {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นครูหรือผู้เชี่ยวชาญ']);
                exit;
            }
            // โหมดเบา: ไม่ดึงคอลัมน์เนื้อหา (intro/body/conclusion ที่เป็น LONGTEXT) ลดขนาดข้อมูลลงมาก
            if (isset($_GET['light']) && $_GET['light'] == '1') {
                $stmt = $pdo->query('
                    SELECT se.student_id, se.essay_phase, se.word_count, se.updated_at, se.created_at,
                           s.student_name, s.classroom, s.student_group
                    FROM student_essays se
                    LEFT JOIN students s ON se.student_id = s.student_id
                    ORDER BY s.classroom ASC, se.essay_phase ASC, s.student_id ASC
                ');
                $essays = $stmt->fetchAll();
                foreach ($essays as &$e) { $e['student_name'] = formatNamePrefix($e['student_name']); }
                unset($e);
                echo json_encode(['success' => true, 'essays' => $essays, 'light' => true]);
                break;
            }
            $stmt = $pdo->query('
                SELECT se.*, s.student_name, s.classroom, s.student_group
                FROM student_essays se
                LEFT JOIN students s ON se.student_id = s.student_id
                ORDER BY s.classroom ASC, se.essay_phase ASC, s.student_id ASC
            ');
            $essays = $stmt->fetchAll();
            $topics = essay_topics_map($pdo);
            // เติมชื่อ + ประกอบ essay_content จากคอลัมน์แยกส่วน + หัวข้อที่ครูกำหนด (เพื่อความเข้ากันได้กับส่วนแสดงผลเดิม)
            foreach ($essays as &$e) {
                $e['student_name']  = formatNamePrefix($e['student_name']);
                $e['essay_content'] = essay_compose_content($e['intro_content'] ?? null, $e['body_content'] ?? null, $e['conclusion_content'] ?? null);
                $e['essay_title']   = $topics[essay_topic_phase($e['essay_phase'])] ?? '';
            }
            unset($e);
            echo json_encode(['success' => true, 'essays' => $essays]);
            break;

        // Essay: ครูเพิ่ม/แก้ไขเรียงความของนักเรียนคนใดก็ได้ (ทุกรอบ) — ครูเท่านั้น
        // ใช้ในหน้า Essay Viewer เพื่อให้คุณครูจัดการเนื้อหาเรียงความแทนนักเรียนได้
        case 'admin_save_essay':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'เฉพาะครูเท่านั้นที่แก้ไขเรียงความได้']);
                exit;
            }
            $aStudentId = isset($request_data['student_id'])  ? trim((string)$request_data['student_id'])  : '';
            $aPhase     = isset($request_data['essay_phase']) ? trim((string)$request_data['essay_phase']) : '';
            if ($aStudentId === '') {
                echo json_encode(['success' => false, 'error' => 'กรุณาระบุนักเรียน']);
                exit;
            }
            if (!in_array($aPhase, ['pretest', 'task1_d1', 'task1_d2', 'task2_d1', 'task2_d2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'รอบการประเมินไม่ถูกต้อง']);
                exit;
            }
            // ต้องมีนักเรียนคนนี้อยู่จริงในระบบก่อน จึงจะบันทึกเรียงความให้ได้
            $chk = $pdo->prepare('SELECT student_id FROM students WHERE student_id = ?');
            $chk->execute([$aStudentId]);
            if (!$chk->fetch()) {
                echo json_encode(['success' => false, 'error' => 'ไม่พบนักเรียนรหัสนี้ในระบบ']);
                exit;
            }

            // เนื้อหาแยกเป็น 3 ส่วน: ส่วนนำ / เนื้อหา (หลายย่อหน้า) / สรุป — เหมือน save_essay ของนักเรียน
            $aIntro      = isset($request_data['introduction']) ? trim((string)$request_data['introduction']) : '';
            $aBodyArr    = (isset($request_data['body']) && is_array($request_data['body'])) ? $request_data['body'] : [];
            $aConclusion = isset($request_data['conclusion']) ? trim((string)$request_data['conclusion']) : '';
            $aBodyArr  = array_values(array_filter(array_map(function ($p) { return trim((string)$p); }, $aBodyArr), function ($p) { return $p !== ''; }));
            $aBodyJson = json_encode($aBodyArr, JSON_UNESCAPED_UNICODE);

            $aAllText   = trim($aIntro . "\n" . implode("\n", $aBodyArr) . "\n" . $aConclusion);
            $aWordCount = $aAllText !== '' ? count(preg_split('/[\s\n\r]+/u', $aAllText, -1, PREG_SPLIT_NO_EMPTY)) : 0;

            $stmt = $pdo->prepare('
                INSERT INTO student_essays (student_id, essay_phase, intro_content, body_content, conclusion_content, word_count)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    intro_content      = VALUES(intro_content),
                    body_content       = VALUES(body_content),
                    conclusion_content = VALUES(conclusion_content),
                    word_count         = VALUES(word_count),
                    updated_at         = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$aStudentId, $aPhase, $aIntro, $aBodyJson, $aConclusion, $aWordCount]);
            echo json_encode(['success' => true, 'word_count' => $aWordCount]);
            break;

        // Essay: ครูลบเรียงความของนักเรียน (รอบใดรอบหนึ่ง) — ครูเท่านั้น
        case 'admin_delete_essay':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'เฉพาะครูเท่านั้นที่ลบเรียงความได้']);
                exit;
            }
            $dStudentId = isset($request_data['student_id'])  ? trim((string)$request_data['student_id'])  : '';
            $dPhase     = isset($request_data['essay_phase']) ? trim((string)$request_data['essay_phase']) : '';
            if ($dStudentId === '' || !in_array($dPhase, ['pretest', 'task1_d1', 'task1_d2', 'task2_d1', 'task2_d2', 'posttest'], true)) {
                echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง']);
                exit;
            }
            $stmt = $pdo->prepare('DELETE FROM student_essays WHERE student_id = ? AND essay_phase = ?');
            $stmt->execute([$dStudentId, $dPhase]);
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            break;

        // ==========================================
        // รายงานการส่งงานรายบุคคล (Submission Report) — ครูเท่านั้น
        // แสดงสถานะการส่งงานแต่ละชิ้น: ก่อนเรียน / D1.1 / D1.2 / เครื่องมือสะท้อนคิด / D2.1 / D2.2 / หลังเรียน
        // ==========================================
        case 'get_submission_report':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นคุณครู']);
                exit;
            }

            // กรองตามกลุ่มการวิจัย (ทดลอง/ตัวอย่าง) — ค่าว่าง = ทุกกลุ่ม
            $groupParam = isset($_GET['group']) ? trim($_GET['group']) : '';
            $stuSql = 'SELECT student_id, student_name, classroom, student_group FROM students';
            $stuParams = [];
            if ($groupParam !== '') {
                $stuSql .= ' WHERE student_group = ?';
                $stuParams[] = $groupParam;
            }
            $stuSql .= ' ORDER BY classroom ASC, student_id ASC';
            $stuStmt = $pdo->prepare($stuSql);
            $stuStmt->execute($stuParams);
            $stuRows = $stuStmt->fetchAll();

            // ชุดรหัสนักเรียนที่ "ส่งแล้ว" ของแต่ละชิ้นงาน (ดึงทีเดียวแล้วแมปในหน่วยความจำ ประหยัด query)
            // เรียงความ: นับว่าส่งแล้วเมื่อมีเนื้อหาอย่างน้อยหนึ่งส่วน หรือ word_count > 0
            $essaySet = [
                'pretest' => [], 'task1_d1' => [], 'task1_d2' => [],
                'task2_d1' => [], 'task2_d2' => [], 'posttest' => [],
            ];
            $esStmt = $pdo->query("
                SELECT student_id, essay_phase FROM student_essays
                WHERE COALESCE(word_count,0) > 0
                   OR COALESCE(intro_content,'') <> ''
                   OR COALESCE(body_content,'') <> ''
                   OR COALESCE(conclusion_content,'') <> ''
            ");
            while ($r = $esStmt->fetch()) {
                $ph = $r['essay_phase'];
                if (isset($essaySet[$ph])) $essaySet[$ph][$r['student_id']] = true;
            }

            // เครื่องมือสะท้อนคิด — แยกตามหน่วยการเรียน (หน่วยที่ 1 / หน่วยที่ 2)
            $flagSet = [
                'problems'   => [1 => [], 2 => []],
                'checklist'  => [1 => [], 2 => []],
                'reflection' => [1 => [], 2 => []],
            ];
            foreach ([
                'problems'   => 'writing_problems',
                'checklist'  => 'self_checklists',
                'reflection' => 'learning_reflections',
            ] as $key => $tbl) {
                try {
                    $q = $pdo->query("SELECT student_id, task_unit FROM `$tbl`");
                    while ($r = $q->fetch()) {
                        // แยกบันทึกตามหน่วย (ค่าอื่นที่ไม่ใช่ 2 ถือเป็นหน่วยที่ 1 ตามค่าเริ่มต้น)
                        $u = ((int)$r['task_unit'] === 2) ? 2 : 1;
                        $flagSet[$key][$u][$r['student_id']] = true;
                    }
                } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง — ปล่อยว่าง */ }
            }

            $report = [];
            foreach ($stuRows as $s) {
                $sid = $s['student_id'];
                $report[] = [
                    'student_id'    => $sid,
                    'student_name'  => formatNamePrefix($s['student_name']),
                    'classroom'     => $s['classroom'],
                    'student_group' => $s['student_group'],
                    'pretest'       => isset($essaySet['pretest'][$sid]),
                    'd1_1'          => isset($essaySet['task1_d1'][$sid]),
                    'd1_2'          => isset($essaySet['task1_d2'][$sid]),
                    // สะท้อนคิดหน่วยที่ 1
                    'problems1'     => isset($flagSet['problems'][1][$sid]),
                    'checklist1'    => isset($flagSet['checklist'][1][$sid]),
                    'reflection1'   => isset($flagSet['reflection'][1][$sid]),
                    // ภาระงาน + สะท้อนคิดหน่วยที่ 2
                    'd2_1'          => isset($essaySet['task2_d1'][$sid]),
                    'd2_2'          => isset($essaySet['task2_d2'][$sid]),
                    'problems2'     => isset($flagSet['problems'][2][$sid]),
                    'checklist2'    => isset($flagSet['checklist'][2][$sid]),
                    'reflection2'   => isset($flagSet['reflection'][2][$sid]),
                    'posttest'      => isset($essaySet['posttest'][$sid]),
                ];
            }
            echo json_encode(['success' => true, 'report' => $report]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action not found']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
