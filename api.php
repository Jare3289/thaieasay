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
            // ถ้าส่ง classmates=1 และผู้ใช้เป็นนักเรียน → คืนเฉพาะเพื่อนห้องเดียวกัน
            // (ใช้ในหน้าประเมินเพื่อน/ตนเอง เพื่อจำกัดรายชื่อเป้าหมายให้อยู่ในห้องของตนเอง)
            $onlyClassmates = isset($_GET['classmates']) && $_GET['classmates'] == '1'
                && isset($_SESSION['user']) && $_SESSION['user']['role'] === 'student';
            if ($onlyClassmates) {
                $meStmt = $pdo->prepare('SELECT classroom FROM students WHERE student_id = ?');
                $meStmt->execute([$_SESSION['user']['id']]);
                $myRoom = $meStmt->fetchColumn();
                if ($myRoom !== false && $myRoom !== null && trim($myRoom) !== '') {
                    $stmt = $pdo->prepare('SELECT student_id, student_name FROM students WHERE classroom = ? ORDER BY student_id ASC');
                    $stmt->execute([$myRoom]);
                } else {
                    // ยังไม่ได้กำหนดห้องให้นักเรียนคนนี้ → คืนทั้งหมดเพื่อไม่ให้รายชื่อว่างเปล่า
                    $stmt = $pdo->query('SELECT student_id, student_name FROM students ORDER BY student_id ASC');
                }
            } else {
                $stmt = $pdo->query('SELECT student_id, student_name FROM students ORDER BY student_id ASC');
            }
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
            $stmt->execute([
                $studentId,
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
            $stmt = $pdo->prepare('SELECT * FROM writing_problems WHERE student_id = ?');
            $stmt->execute([$studentId]);
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
            $stmt->execute([
                $studentId,
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
            $stmt = $pdo->prepare('SELECT * FROM self_checklists WHERE student_id = ?');
            $stmt->execute([$studentId]);
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
        //  ระบบจับคู่ประเมินเพื่อน (Peer Pairing)  รอบ: pretest/task1/task2/posttest
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
            // จำกัดเฉพาะกลุ่มที่ต้องการได้ (ถ้าส่ง group มา) มิฉะนั้นใช้ทุกคน
            $group = isset($request_data['group']) ? trim($request_data['group']) : '';
            if ($group !== '') {
                $stmt = $pdo->prepare('SELECT student_id FROM students WHERE student_group = ? ORDER BY student_id ASC');
                $stmt->execute([$group]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $ids = $pdo->query('SELECT student_id FROM students ORDER BY student_id ASC')->fetchAll(PDO::FETCH_COLUMN);
            }

            if (count($ids) < 2) {
                echo json_encode(['success' => false, 'error' => 'มีนักเรียนไม่พอสำหรับการจับคู่ (ต้องมีอย่างน้อย 2 คน)']);
                exit;
            }

            // สุ่มลำดับแล้วจับคู่แบบผู้ประเมิน→ถูกประเมิน เป็นวงกลม (A→B, B→C, ..., last→A)
            // วิธีนี้รับประกันว่าทุกคนได้เป็นทั้งผู้ประเมินและถูกประเมิน และไม่มีใครประเมินตนเอง
            shuffle($ids);
            $n = count($ids);
            $result = [];
            for ($i = 0; $i < $n; $i++) {
                $result[] = ['student_code' => $ids[$i], 'partner_code' => $ids[($i + 1) % $n]];
            }

            $pdo->beginTransaction();
            try {
                $del = $pdo->prepare('DELETE FROM peer_pairs WHERE round = ?');
                $del->execute([$round]);
                $ins = $pdo->prepare('INSERT INTO peer_pairs (round, student_code, partner_code) VALUES (?, ?, ?)');
                foreach ($result as $r) {
                    $ins->execute([$round, $r['student_code'], $r['partner_code']]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }

            // ส่งกลับเป็น map student_code → partner_code เพื่อให้หน้าเว็บอัปเดตทันที
            $pairsMap = [];
            foreach ($result as $r) { $pairsMap[$r['student_code']] = $r['partner_code']; }
            echo json_encode(['success' => true, 'pairs' => $pairsMap, 'count' => $n]);
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
            $stmt->execute([$studentId, $cs, $lm, $fa, $fg]);
            echo json_encode(['success' => true]);
            break;

        case 'get_learning_reflection':
            $studentId = isset($_GET['studentId']) ? $_GET['studentId'] : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID is required']);
                exit;
            }
            $stmt = $pdo->prepare('SELECT * FROM learning_reflections WHERE student_id = ?');
            $stmt->execute([$studentId]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'data' => $row ? $row : null]);
            break;

        case 'get_reflection_summary':
            $total_students_res = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
            
            $prob_count = $pdo->query('SELECT COUNT(*) FROM writing_problems')->fetchColumn();
            $chk_count = $pdo->query('SELECT COUNT(*) FROM self_checklists')->fetchColumn();
            $peer_count = $pdo->query('SELECT COUNT(DISTINCT student_id) FROM peer_reviews')->fetchColumn();
            $ref_count = $pdo->query('SELECT COUNT(*) FROM learning_reflections')->fetchColumn();
            
            // ปัญหาล่าสุด
            $stmt_probs = $pdo->query('
                SELECT wp.*, s.student_name 
                FROM writing_problems wp
                JOIN students s ON wp.student_id = s.student_id
                ORDER BY wp.created_at DESC LIMIT 5
            ');
            $recent_problems = $stmt_probs->fetchAll();
            
            // รีวิวล่าสุด
            $stmt_peers = $pdo->query('
                SELECT pr.*, s.student_name, r.student_name AS reviewer_name 
                FROM peer_reviews pr
                JOIN students s ON pr.student_id = s.student_id
                JOIN students r ON pr.reviewer_id = r.student_id
                ORDER BY pr.created_at DESC LIMIT 5
            ');
            $recent_peers = $stmt_peers->fetchAll();

            // ข้อมูลของนักเรียนทุกคนสำหรับภาพรวมชั้นเรียน
            $stmt_all = $pdo->query('
                SELECT 
                    s.student_id, 
                    s.student_name,
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
                LEFT JOIN writing_problems wp ON s.student_id = wp.student_id
                LEFT JOIN self_checklists chk ON s.student_id = chk.student_id
                LEFT JOIN learning_reflections ref ON s.student_id = ref.student_id
                ORDER BY s.student_id ASC
            ');
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

        // N. ดึงข้อมูลบันทึกการสะท้อนการเรียนรู้ (Learning Reflection)
        case 'get_reflection_data':
            $studentId = isset($_GET['studentId']) ? trim($_GET['studentId']) : '';
            if (empty($studentId)) {
                echo json_encode(['success' => false, 'error' => 'Student ID required']);
                exit;
            }
            $stmt = $pdo->prepare('SELECT * FROM learning_reflections WHERE student_id = ?');
            $stmt->execute([$studentId]);
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
            $essayPhase   = isset($request_data['essay_phase'])   ? trim($request_data['essay_phase'])   : 'task1';
            $essayTitle   = isset($request_data['essay_title'])   ? trim($request_data['essay_title'])   : '';
            $essayContent = isset($request_data['essay_content']) ? trim($request_data['essay_content']) : '';
            // นับจำนวนคำ (แยกด้วยช่องว่างและขึ้นบรรทัดใหม่)
            $wordCount    = $essayContent ? count(preg_split('/[\s\n\r]+/u', $essayContent, -1, PREG_SPLIT_NO_EMPTY)) : 0;

            $stmt = $pdo->prepare('
                INSERT INTO student_essays (student_id, essay_phase, essay_title, essay_content, word_count)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    essay_title   = VALUES(essay_title),
                    essay_content = VALUES(essay_content),
                    word_count    = VALUES(word_count),
                    updated_at    = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$studentId, $essayPhase, $essayTitle, $essayContent, $wordCount]);
            echo json_encode(['success' => true, 'word_count' => $wordCount]);
            break;

        // Essay: ดึงเรียงความของนักเรียนคนนั้น (นักเรียนดูของตัวเอง)
        case 'get_essay':
            if (!isset($_SESSION['user'])) {
                echo json_encode(['success' => false, 'error' => 'Not logged in']);
                exit;
            }
            $studentId  = isset($_GET['studentId'])  ? trim($_GET['studentId'])  : $_SESSION['user']['id'];
            $essayPhase = isset($_GET['essay_phase']) ? trim($_GET['essay_phase']) : 'task1';
            $stmt = $pdo->prepare('SELECT * FROM student_essays WHERE student_id = ? AND essay_phase = ?');
            $stmt->execute([$studentId, $essayPhase]);
            $row = $stmt->fetch();
            echo json_encode(['success' => true, 'found' => (bool)$row, 'data' => $row ?: null]);
            break;

        // Essay: ดึงเรียงความทั้งชั้น (สำหรับครู/แดชบอร์ด)
        case 'get_all_essays':
            if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'expert'])) {
                echo json_encode(['success' => false, 'error' => 'ต้องเป็นครูหรือผู้เชี่ยวชาญ']);
                exit;
            }
            $stmt = $pdo->query('
                SELECT se.*, s.student_name
                FROM student_essays se
                JOIN students s ON se.student_id = s.student_id
                ORDER BY se.essay_phase ASC, s.student_id ASC
            ');
            $essays = $stmt->fetchAll();
            // apply formatNamePrefix
            foreach ($essays as &$e) {
                $e['student_name'] = formatNamePrefix($e['student_name']);
            }
            unset($e);
            echo json_encode(['success' => true, 'essays' => $essays]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action not found']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
