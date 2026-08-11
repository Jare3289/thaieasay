<?php
/**
 * export.php — ศูนย์ส่งออกชุดข้อมูลรายงานเพื่อการวิเคราะห์
 * ---------------------------------------------------------------------------
 * สร้างรายงานจากทุกตารางในระบบประเมินการเขียนเรียงความ ทั้งแบบภาพรวม (overview)
 * และรายละเอียดย่อย (detail) ในรูปแบบที่พร้อมนำไปวิเคราะห์ต่อด้วย Excel / SPSS / R / Python
 *
 * พารามิเตอร์ (GET):
 *   report    = ชนิดรายงาน (ดู $REPORTS ด้านล่าง) หรือ 'all' = รวมทุกรายงานไว้ในไฟล์เดียว
 *   format    = 'csv' (ไฟล์เดียว) หรือ 'xls' (สมุดงานหลายชีต — ค่าเริ่มต้นเมื่อ report=all)
 *   group     = กรองเฉพาะกลุ่ม เช่น 'กลุ่มทดลอง' / 'กลุ่มตัวอย่าง' (ไม่ระบุ = ทุกกลุ่ม)
 *   classroom = กรองเฉพาะห้องเรียน เช่น '606' (ไม่ระบุ = ทุกห้อง)
 *
 * เข้าถึงได้เฉพาะคุณครูเท่านั้น (ข้อมูลทั้งชั้นเรียน)
 */

require_once 'auth_helper.php';

// ---------------------------------------------------------------------------
// 0) ตรวจสิทธิ์ — เฉพาะคุณครูเท่านั้น (เป็นผู้ดูแลข้อมูลวิจัยทั้งชั้น)
// ---------------------------------------------------------------------------
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ต้องเข้าสู่ระบบในฐานะคุณครูเพื่อส่งออกรายงานชุดข้อมูลนี้';
    exit;
}

$report          = isset($_GET['report']) ? trim($_GET['report']) : 'all';
$format          = isset($_GET['format']) ? trim($_GET['format']) : ($report === 'all' ? 'xls' : 'csv');
$groupFilter     = isset($_GET['group']) ? trim($_GET['group']) : '';
$classroomFilter = isset($_GET['classroom']) ? trim($_GET['classroom']) : '';

// ---------------------------------------------------------------------------
// 1) ตารางแปลรหัส → ข้อความอ่านง่าย (ใช้เป็นหัวคอลัมน์/ค่าในรายงาน)
// ---------------------------------------------------------------------------

// ป้ายชื่อเกณฑ์ย่อย 11 ด้าน (ตรงกับ evaluation.php)
$CRITERIA = [
    '1_1' => '1.1 ความตรงประเด็น',
    '1_2' => '1.2 แก่นเรื่องชัดเจน',
    '1_3' => '1.3 การขยายความและเหตุผล',
    '2_1' => '2.1 ความครบถ้วนขององค์ประกอบ',
    '2_2' => '2.2 การลำดับประเด็นเป็นระบบ',
    '3_1' => '3.1 การใช้ประโยคถูกต้อง',
    '3_2' => '3.2 การเลือกใช้คำ',
    '3_3' => '3.3 ระดับภาษาเหมาะสม',
    '4_1' => '4.1 การสะกดคำถูกต้อง',
    '4_2' => '4.2 การเว้นวรรค',
    '4_3' => '4.3 ความเรียบร้อย',
];

// ป้ายชื่อเกณฑ์ย่อยของการประเมินเพื่อน (มี 4 ด้านย่อยเพิ่มในด้าน 1 และ 3)
$PEER_CRITERIA = [
    '1_1' => '1.1 ความตรงประเด็น',
    '1_2' => '1.2 แก่นเรื่องชัดเจน',
    '1_3' => '1.3 การขยายความและเหตุผล',
    '1_4' => '1.4 เอกภาพของเนื้อหา',
    '2_1' => '2.1 ความครบถ้วนขององค์ประกอบ',
    '2_2' => '2.2 การลำดับประเด็นเป็นระบบ',
    '3_1' => '3.1 การใช้ประโยคถูกต้อง',
    '3_2' => '3.2 การเลือกใช้คำ',
    '3_3' => '3.3 ระดับภาษาเหมาะสม',
    '3_4' => '3.4 การใช้คำเชื่อม',
    '4_1' => '4.1 การสะกดคำถูกต้อง',
    '4_2' => '4.2 การเว้นวรรค',
    '4_3' => '4.3 ความเรียบร้อย',
];

// ป้ายชื่อ 4 ด้านหลัก
$DIMENSIONS = [
    'c' => 'ด้านเนื้อหาสาระ',
    's' => 'ด้านองค์ประกอบและการลำดับ',
    'l' => 'ด้านการใช้สำนวนภาษา',
    'm' => 'ด้านอักขรวิธีและกลไกการเขียน',
];

// แปลรหัสรอบการประเมิน/ภาระงาน
function phaseLabel($code) {
    $map = [
        'pretest'  => 'ก่อนเรียน (Pre-test)',
        'posttest' => 'หลังเรียน (Post-test)',
        'task1'    => 'ภาระงานหน่วยที่ 1',
        'task2'    => 'ภาระงานหน่วยที่ 2',
        'task1_d1' => 'ภาระงานหน่วยที่ 1 · ร่างที่ 1 (D1)',
        'task1_d2' => 'ภาระงานหน่วยที่ 1 · ร่างที่ 2 (D2)',
        'task2_d1' => 'ภาระงานหน่วยที่ 2 · ร่างที่ 1 (D1)',
        'task2_d2' => 'ภาระงานหน่วยที่ 2 · ร่างที่ 2 (D2)',
    ];
    return isset($map[$code]) ? $map[$code] : ($code === '' || $code === null ? '-' : $code);
}

// ---------------------------------------------------------------------------
// 2) ตัวช่วยดึงข้อมูลนักเรียน (พร้อมกรองกลุ่ม/ห้อง) — คืนเป็น map keyed by id
// ---------------------------------------------------------------------------
function loadStudents($pdo, $groupFilter, $classroomFilter) {
    $conds = [];
    $params = [];
    if ($groupFilter !== '') { $conds[] = 'student_group = ?'; $params[] = $groupFilter; }
    if ($classroomFilter !== '') { $conds[] = 'classroom = ?'; $params[] = $classroomFilter; }
    $sql = 'SELECT student_id, student_name, classroom, student_group FROM students';
    if (!empty($conds)) $sql .= ' WHERE ' . implode(' AND ', $conds);
    $sql .= ' ORDER BY classroom ASC, student_id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $map = [];
    while ($r = $stmt->fetch()) {
        $r['student_name'] = formatNamePrefix($r['student_name']);
        $map[$r['student_id']] = $r;
    }
    return $map;
}

$students = loadStudents($pdo, $groupFilter, $classroomFilter);

// ตัวช่วย: ค่าคอลัมน์ข้อมูลนักเรียนมาตรฐาน (ใช้ต้นแถวของทุกรายงาน)
function stdCols($students, $sid) {
    $s = isset($students[$sid]) ? $students[$sid] : null;
    return [
        $sid,
        $s ? $s['student_name'] : '(ไม่พบในรายชื่อที่กรอง)',
        $s ? ($s['classroom'] ?? '') : '',
        $s ? ($s['student_group'] ?? '') : '',
    ];
}
$STD_HEADERS = ['รหัสนักเรียน', 'ชื่อ-สกุล', 'ห้องเรียน', 'กลุ่ม'];

// ตัวเลข 2 ตำแหน่ง (คืนเป็น float เพื่อให้ XLS มองเป็นตัวเลข)
function num2($v) { return (float)number_format((float)$v, 2, '.', ''); }

// ---------------------------------------------------------------------------
// 3) ตัวสร้างรายงานแต่ละชนิด — คืน ['name'=>ชื่อชีต, 'title'=>คำอธิบาย, 'rows'=>[[...]]]
//    แถวแรกของ rows คือหัวคอลัมน์; เซลล์ตัวเลขให้ส่งเป็น (float/int) เพื่อ typing ที่ถูกต้อง
// ---------------------------------------------------------------------------

/* 3.1 [ภาพรวม] สรุปคะแนนรายบุคคล — คะแนนรวมจากผู้ประเมินแต่ละฝ่าย + ค่าเฉลี่ย 4 ด้าน */
function build_summary($pdo, $students, $STD_HEADERS) {
    // เตรียมโครงข้อมูลต่อคน
    $acc = [];
    foreach ($students as $sid => $s) {
        $acc[$sid] = ['self'=>null,'peer'=>null,'teacher'=>null,'sum'=>0,'cnt'=>0,
            'tc'=>null,'ts'=>null,'tl'=>null,'tm'=>null];
    }
    $stmt = $pdo->query('SELECT student_id, evaluator_type, total_score,
        score_1_1, score_1_2, score_1_3, score_2_1, score_2_2,
        score_3_1, score_3_2, score_3_3, score_4_1, score_4_2, score_4_3
        FROM evaluations');
    while ($r = $stmt->fetch()) {
        $sid = $r['student_id'];
        if (!isset($acc[$sid])) continue; // ไม่อยู่ในกลุ่ม/ห้องที่กรอง
        $sc = (float)$r['total_score'];
        $acc[$sid]['sum'] += $sc; $acc[$sid]['cnt']++;
        if ($r['evaluator_type'] === 'ตนเองประเมิน')      $acc[$sid]['self'] = $sc;
        else if ($r['evaluator_type'] === 'เพื่อนประเมิน')  $acc[$sid]['peer'] = $sc;
        else if ($r['evaluator_type'] === 'ครูประเมิน') {
            $acc[$sid]['teacher'] = $sc;
            $acc[$sid]['tc'] = (float)$r['score_1_1']+(float)$r['score_1_2']+(float)$r['score_1_3'];
            $acc[$sid]['ts'] = (float)$r['score_2_1']+(float)$r['score_2_2'];
            $acc[$sid]['tl'] = (float)$r['score_3_1']+(float)$r['score_3_2']+(float)$r['score_3_3'];
            $acc[$sid]['tm'] = (float)$r['score_4_1']+(float)$r['score_4_2']+(float)$r['score_4_3'];
        }
    }
    $rows = [];
    $rows[] = array_merge($STD_HEADERS, [
        'คะแนนตนเองประเมิน', 'คะแนนเพื่อนประเมิน', 'คะแนนครูประเมิน',
        'ค่าเฉลี่ยรวม (3 ฝ่าย)', 'จำนวนผู้ประเมิน',
        'ครู:ด้านเนื้อหา', 'ครู:ด้านองค์ประกอบ', 'ครู:ด้านภาษา', 'ครู:ด้านอักขรวิธี',
    ]);
    foreach ($students as $sid => $s) {
        $a = $acc[$sid];
        $avg = $a['cnt'] > 0 ? num2($a['sum'] / $a['cnt']) : '';
        $rows[] = array_merge(stdCols($students, $sid), [
            $a['self'] !== null ? num2($a['self']) : '',
            $a['peer'] !== null ? num2($a['peer']) : '',
            $a['teacher'] !== null ? num2($a['teacher']) : '',
            $avg,
            (int)$a['cnt'],
            $a['tc'] !== null ? num2($a['tc']) : '',
            $a['ts'] !== null ? num2($a['ts']) : '',
            $a['tl'] !== null ? num2($a['tl']) : '',
            $a['tm'] !== null ? num2($a['tm']) : '',
        ]);
    }
    return ['name'=>'ภาพรวมคะแนนรายบุคคล', 'title'=>'สรุปคะแนนรวมของนักเรียนแต่ละคนจากผู้ประเมิน 3 ฝ่าย', 'rows'=>$rows];
}

/* 3.2 [ภาพรวม] สถิติเชิงพรรณนาระดับชั้น — จำแนกตามรอบ × ฝ่ายประเมิน (N, Mean, SD, Min, Max) */
function build_class_stats($pdo, $students) {
    // รวมคะแนนตาม (test_phase, evaluator_type)
    $stmt = $pdo->query('SELECT student_id, evaluator_type, test_phase, total_score FROM evaluations');
    $groups = [];
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        $key = ($r['test_phase'] ?? 'posttest') . '||' . $r['evaluator_type'];
        if (!isset($groups[$key])) $groups[$key] = [];
        $groups[$key][] = (float)$r['total_score'];
    }
    ksort($groups);
    $rows = [];
    $rows[] = ['รอบการประเมิน', 'ฝ่ายผู้ประเมิน', 'จำนวน (N)', 'ค่าเฉลี่ย (Mean)',
        'ส่วนเบี่ยงเบนมาตรฐาน (SD)', 'ต่ำสุด (Min)', 'สูงสุด (Max)'];
    foreach ($groups as $key => $vals) {
        list($phase, $type) = explode('||', $key);
        $n = count($vals);
        $mean = $n ? array_sum($vals) / $n : 0;
        $sd = 0;
        if ($n > 1) {
            $sumSq = 0;
            foreach ($vals as $v) $sumSq += ($v - $mean) * ($v - $mean);
            $sd = sqrt($sumSq / ($n - 1)); // sample SD
        }
        $rows[] = [phaseLabel($phase), $type, (int)$n, num2($mean), num2($sd),
            num2(min($vals)), num2(max($vals))];
    }
    if (count($rows) === 1) $rows[] = ['(ยังไม่มีข้อมูลการประเมิน)', '', '', '', '', '', ''];
    return ['name'=>'สถิติภาพรวมระดับชั้น', 'title'=>'สถิติเชิงพรรณนาของคะแนนรวม จำแนกตามรอบและฝ่ายผู้ประเมิน', 'rows'=>$rows];
}

/* 3.3 [รายละเอียด] ผลการประเมินทุกรายการ พร้อมคะแนนย่อย 11 ด้าน */
function build_evaluations($pdo, $students, $STD_HEADERS, $CRITERIA) {
    $stmt = $pdo->query('SELECT * FROM evaluations ORDER BY student_id ASC, test_phase ASC, evaluator_type ASC');
    $rows = [];
    $critHeaders = [];
    foreach ($CRITERIA as $label) $critHeaders[] = $label;
    $rows[] = array_merge($STD_HEADERS, ['รอบการประเมิน', 'ฝ่ายผู้ประเมิน', 'ชื่อผู้ประเมิน'],
        $critHeaders, ['คะแนนรวม', 'ระดับคุณภาพ', 'บันทึกเมื่อ']);
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        $line = array_merge(stdCols($students, $r['student_id']), [
            phaseLabel($r['test_phase'] ?? ''), $r['evaluator_type'], $r['evaluator_name'],
        ]);
        foreach (array_keys($CRITERIA) as $k) $line[] = num2($r['score_' . $k]);
        $line[] = num2($r['total_score']);
        $line[] = $r['quality_level'];
        $line[] = $r['timestamp'] ?? '';
        $rows[] = $line;
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'รายละเอียดผลประเมิน', 'title'=>'ผลการประเมินทุกรายการ พร้อมคะแนนรายด้านย่อย 11 ด้าน', 'rows'=>$rows];
}

/* 3.4 [รายละเอียด] บันทึกปัญหาการเขียนและแนวทางแก้ (long format: 1 แถว = 1 เกณฑ์) */
function build_writing_problems($pdo, $students, $STD_HEADERS, $CRITERIA) {
    $stmt = $pdo->query('SELECT * FROM writing_problems');
    $rows = [];
    $rows[] = array_merge($STD_HEADERS, ['รหัสเกณฑ์', 'ชื่อเกณฑ์', 'ปัญหา/อุปสรรคที่พบ', 'แนวทางแก้ไข', 'บันทึกเมื่อ']);
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        foreach ($CRITERIA as $k => $label) {
            $prob = trim((string)($r['prob_' . $k] ?? ''));
            $sol  = trim((string)($r['sol_' . $k] ?? ''));
            if ($prob === '' && $sol === '') continue; // ข้ามเกณฑ์ที่ว่าง
            $rows[] = array_merge(stdCols($students, $r['student_id']),
                [str_replace('_', '.', $k), $label, $prob, $sol, $r['created_at'] ?? '']);
        }
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'ปัญหาการเขียน', 'title'=>'บันทึกปัญหา/อุปสรรคการเขียนและแนวทางแก้ไข (รายเกณฑ์)', 'rows'=>$rows];
}

/* 3.5 [รายละเอียด] รายการตรวจสอบตนเอง (long format) */
function build_self_checklists($pdo, $students, $STD_HEADERS, $CRITERIA) {
    $stmt = $pdo->query('SELECT * FROM self_checklists');
    $rows = [];
    $rows[] = array_merge($STD_HEADERS, ['รหัสเกณฑ์', 'ชื่อเกณฑ์', 'ผลการตรวจสอบตนเอง', 'หมายเหตุ (รวม)', 'บันทึกเมื่อ']);
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        $notes = trim((string)($r['notes'] ?? ''));
        foreach ($CRITERIA as $k => $label) {
            $val = (string)($r['check_' . $k] ?? '');
            $rows[] = array_merge(stdCols($students, $r['student_id']),
                [str_replace('_', '.', $k), $label, $val, $notes, $r['created_at'] ?? '']);
        }
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'ตรวจสอบตนเอง', 'title'=>'รายการตรวจสอบตนเองของนักเรียน (รายเกณฑ์)', 'rows'=>$rows];
}

/* 3.6 [รายละเอียด] การประเมินเพื่อนเชิงคุณภาพ (wide + ข้อความเชิงบรรยาย) */
function build_peer_reviews($pdo, $students, $STD_HEADERS, $PEER_CRITERIA) {
    $stmt = $pdo->query('SELECT * FROM peer_reviews');
    $rows = [];
    $critHeaders = array_values($PEER_CRITERIA);
    $rows[] = array_merge(
        ['รหัสเจ้าของผลงาน', 'ชื่อเจ้าของผลงาน', 'ห้องเรียน', 'กลุ่ม', 'รหัสเพื่อนผู้ประเมิน', 'ชื่อเพื่อนผู้ประเมิน'],
        $critHeaders,
        ['จุดแข็ง/สิ่งที่ประทับใจ', 'จุดที่ควรปรับปรุง', 'ข้อความให้กำลังใจ', 'บันทึกเมื่อ']
    );
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        $owner = stdCols($students, $r['student_id']);
        $rvId = $r['reviewer_id'];
        $rvName = isset($students[$rvId]) ? $students[$rvId]['student_name']
                  : (($n = getStudentName($students, $rvId)) ? $n : $rvId);
        $line = array_merge($owner, [$rvId, $rvName]);
        foreach (array_keys($PEER_CRITERIA) as $k) $line[] = (string)($r['score_' . $k] ?? '');
        $line[] = trim((string)($r['strength'] ?? ''));
        $line[] = trim((string)($r['improvement'] ?? ''));
        $line[] = trim((string)($r['encouragement'] ?? ''));
        $line[] = $r['created_at'] ?? '';
        $rows[] = $line;
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'ประเมินเพื่อน', 'title'=>'การประเมินผลงานเพื่อนเชิงคุณภาพ พร้อมข้อเสนอแนะเชิงบรรยาย', 'rows'=>$rows];
}

// ชื่อผู้ประเมินที่อาจไม่อยู่ในกลุ่มที่กรอง — ดึงชื่อจากตารางเต็มถ้าจำเป็น
function getStudentName($students, $sid) {
    return isset($students[$sid]) ? $students[$sid]['student_name'] : null;
}

/* 3.7 [รายละเอียด] การสะท้อนการเรียนรู้ */
function build_reflections($pdo, $students, $STD_HEADERS) {
    $stmt = $pdo->query('SELECT * FROM learning_reflections');
    $rows = [];
    $rows[] = array_merge($STD_HEADERS, [
        'ด้านเนื้อหาสาระและองค์ประกอบ', 'ด้านสำนวนภาษาและอักขรวิธี',
        'การนำข้อเสนอแนะไปปรับปรุงงาน', 'การประยุกต์ใช้และเป้าหมายในอนาคต', 'บันทึกเมื่อ',
    ]);
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        $rows[] = array_merge(stdCols($students, $r['student_id']), [
            trim((string)($r['content_structure'] ?? '')),
            trim((string)($r['language_mechanics'] ?? '')),
            trim((string)($r['feedback_applied'] ?? '')),
            trim((string)($r['future_goals'] ?? '')),
            $r['created_at'] ?? '',
        ]);
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'สะท้อนการเรียนรู้', 'title'=>'บันทึกการสะท้อนคิดการเรียนรู้ของนักเรียน', 'rows'=>$rows];
}

/* 3.8 [รายละเอียด] เรียงความนักเรียน (พร้อมจำนวนคำและเนื้อหาเต็ม) */
function build_essays($pdo, $students, $STD_HEADERS) {
    $stmt = $pdo->query('SELECT * FROM student_essays ORDER BY student_id ASC, essay_phase ASC');
    $topicsMap = essay_topics_map($pdo);
    $rows = [];
    $rows[] = array_merge($STD_HEADERS, [
        'รอบ/ภาระงาน', 'หัวข้อ (ครูกำหนด)', 'จำนวนคำ', 'ส่วนนำ (Introduction)', 'ส่วนเนื้อหา (Body)', 'ส่วนสรุป (Conclusion)', 'บันทึกเมื่อ', 'แก้ไขล่าสุด',
    ]);
    while ($r = $stmt->fetch()) {
        if (!isset($students[$r['student_id']])) continue;
        // เนื้อหาแยกส่วน: เนื้อหา (หลายย่อหน้า) เก็บเป็น JSON array — คลี่เป็นข้อความคั่นบรรทัดเพื่อส่งออก
        $bodyArr = json_decode((string)($r['body_content'] ?? ''), true);
        $bodyText = is_array($bodyArr) ? implode("\n\n", $bodyArr) : trim((string)($r['body_content'] ?? ''));
        $rows[] = array_merge(stdCols($students, $r['student_id']), [
            phaseLabel($r['essay_phase'] ?? ''),
            (string)($topicsMap[essay_topic_phase($r['essay_phase'] ?? '')] ?? ''),
            (int)($r['word_count'] ?? 0),
            trim((string)($r['intro_content'] ?? '')),
            $bodyText,
            trim((string)($r['conclusion_content'] ?? '')),
            $r['created_at'] ?? '', $r['updated_at'] ?? '',
        ]);
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'เรียงความนักเรียน', 'title'=>'เนื้อหาเรียงความที่นักเรียนส่ง จำแนกตามรอบ/ภาระงาน', 'rows'=>$rows];
}

/* 3.9 [รายละเอียด] การจับคู่ประเมินเพื่อน */
function build_peer_pairs($pdo, $students) {
    $stmt = $pdo->query('SELECT * FROM peer_pairs ORDER BY round ASC, student_code ASC');
    $rows = [];
    $rows[] = ['รอบการประเมิน', 'รหัสผู้ประเมิน', 'ชื่อผู้ประเมิน', 'รหัสคู่ที่ถูกประเมิน', 'ชื่อคู่ที่ถูกประเมิน', 'บันทึกเมื่อ'];
    while ($r = $stmt->fetch()) {
        // แสดงคู่ที่อย่างน้อยฝ่ายใดฝ่ายหนึ่งอยู่ในกลุ่มที่กรอง
        if (!isset($students[$r['student_code']]) && !isset($students[$r['partner_code']])) continue;
        $rows[] = [
            phaseLabel($r['round']),
            $r['student_code'], getStudentName($students, $r['student_code']) ?? $r['student_code'],
            $r['partner_code'], getStudentName($students, $r['partner_code']) ?? $r['partner_code'],
            $r['created_at'] ?? '',
        ];
    }
    if (count($rows) === 1) $rows[] = array_fill(0, count($rows[0]), '');
    return ['name'=>'การจับคู่ประเมินเพื่อน', 'title'=>'ตารางการจับคู่นักเรียนสำหรับการประเมินเพื่อนในแต่ละรอบ', 'rows'=>$rows];
}

// ---------------------------------------------------------------------------
// 4) ทะเบียนรายงาน — mapping ชนิด → callable
// ---------------------------------------------------------------------------
$REPORTS = [
    'summary'          => function() use ($pdo, $students, $STD_HEADERS) { return build_summary($pdo, $students, $STD_HEADERS); },
    'class_stats'      => function() use ($pdo, $students) { return build_class_stats($pdo, $students); },
    'evaluations'      => function() use ($pdo, $students, $STD_HEADERS, $CRITERIA) { return build_evaluations($pdo, $students, $STD_HEADERS, $CRITERIA); },
    'writing_problems' => function() use ($pdo, $students, $STD_HEADERS, $CRITERIA) { return build_writing_problems($pdo, $students, $STD_HEADERS, $CRITERIA); },
    'self_checklists'  => function() use ($pdo, $students, $STD_HEADERS, $CRITERIA) { return build_self_checklists($pdo, $students, $STD_HEADERS, $CRITERIA); },
    'peer_reviews'     => function() use ($pdo, $students, $STD_HEADERS, $PEER_CRITERIA) { return build_peer_reviews($pdo, $students, $STD_HEADERS, $PEER_CRITERIA); },
    'reflections'      => function() use ($pdo, $students, $STD_HEADERS) { return build_reflections($pdo, $students, $STD_HEADERS); },
    'essays'           => function() use ($pdo, $students, $STD_HEADERS) { return build_essays($pdo, $students, $STD_HEADERS); },
    'peer_pairs'       => function() use ($pdo, $students) { return build_peer_pairs($pdo, $students); },
];

// ---------------------------------------------------------------------------
// 5) ตัวเขียนไฟล์ผลลัพธ์
// ---------------------------------------------------------------------------

// กัน CSV/Excel formula injection: เติมช่องว่างนำหน้าค่าที่ขึ้นต้นด้วย = + - @ | tab | CR
function sanitizeCell($v) {
    if (is_int($v) || is_float($v)) return $v;
    $s = (string)$v;
    if ($s !== '' && preg_match('/^[=\-+@\t\r]/', $s)) {
        $s = "'" . $s;
    }
    return $s;
}

function safeFilename($name) {
    // ตัดอักขระต้องห้ามในชื่อไฟล์ออก (คงภาษาไทยไว้)
    return preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $name);
}

// 5.1 ส่งออกไฟล์ CSV เดียว (UTF-8 BOM เพื่อให้ Excel อ่านภาษาไทยถูกต้อง)
function outputCSV($report) {
    $fname = safeFilename('รายงาน_' . $report['name'] . '_' . date('Y-m-d') . '.csv');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"; filename*=UTF-8\'\'' . rawurlencode($fname));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM
    foreach ($report['rows'] as $row) {
        $clean = array_map('sanitizeCell', $row);
        fputcsv($out, $clean);
    }
    fclose($out);
}

// 5.2 ส่งออกสมุดงานหลายชีต (SpreadsheetML 2003) — เปิดใน Excel/LibreOffice ได้ทันที
function xmlesc($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function outputWorkbook($reports, $meta) {
    $fname = safeFilename('รายงานฉบับสมบูรณ์_' . date('Y-m-d') . '.xls');
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"; filename*=UTF-8\'\'' . rawurlencode($fname));
    header('Cache-Control: no-store, no-cache, must-revalidate');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
    echo ' xmlns:o="urn:schemas-microsoft-com:office:office"';
    echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
    echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"';
    echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

    // สไตล์: หัวคอลัมน์ตัวหนา, ข้อความตัดบรรทัด
    echo '<Styles>';
    echo '<Style ss:ID="hdr"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1F3A5F" ss:Pattern="Solid"/><Alignment ss:Vertical="Center" ss:WrapText="1"/></Style>';
    echo '<Style ss:ID="txt"><Alignment ss:Vertical="Top" ss:WrapText="1"/></Style>';
    echo '<Style ss:ID="titleCell"><Font ss:Bold="1" ss:Size="13"/></Style>';
    echo '</Styles>' . "\n";

    // ชีตสารบัญ/ข้อมูลอธิบาย
    echo '<Worksheet ss:Name="สารบัญรายงาน"><Table>';
    echo '<Row><Cell ss:StyleID="titleCell"><Data ss:Type="String">ชุดข้อมูลรายงานระบบประเมินการเขียนเรียงความ</Data></Cell></Row>';
    echo '<Row><Cell><Data ss:Type="String">ส่งออกเมื่อ</Data></Cell><Cell><Data ss:Type="String">' . xmlesc($meta['exported_at']) . '</Data></Cell></Row>';
    echo '<Row><Cell><Data ss:Type="String">กลุ่มที่กรอง</Data></Cell><Cell><Data ss:Type="String">' . xmlesc($meta['group']) . '</Data></Cell></Row>';
    echo '<Row><Cell><Data ss:Type="String">ห้องที่กรอง</Data></Cell><Cell><Data ss:Type="String">' . xmlesc($meta['classroom']) . '</Data></Cell></Row>';
    echo '<Row><Cell><Data ss:Type="String">จำนวนนักเรียนในชุดข้อมูล</Data></Cell><Cell><Data ss:Type="Number">' . (int)$meta['student_count'] . '</Data></Cell></Row>';
    echo '<Row></Row>';
    echo '<Row><Cell ss:StyleID="hdr"><Data ss:Type="String">ชีต</Data></Cell><Cell ss:StyleID="hdr"><Data ss:Type="String">คำอธิบาย</Data></Cell></Row>';
    foreach ($reports as $rep) {
        echo '<Row><Cell><Data ss:Type="String">' . xmlesc($rep['name']) . '</Data></Cell>';
        echo '<Cell ss:StyleID="txt"><Data ss:Type="String">' . xmlesc($rep['title']) . '</Data></Cell></Row>';
    }
    echo '</Table></Worksheet>' . "\n";

    // ชีตข้อมูลแต่ละรายงาน
    $cut = function($s, $len) {
        return function_exists('mb_substr') ? mb_substr($s, 0, $len, 'UTF-8') : substr($s, 0, $len);
    };
    $usedNames = [];
    foreach ($reports as $rep) {
        // ชื่อชีต Excel: ยาวไม่เกิน 31 ตัวอักษร และห้ามซ้ำ
        $sheetName = $cut($rep['name'], 31);
        $base = $sheetName; $i = 2;
        while (isset($usedNames[$sheetName])) { $sheetName = $cut($base, 28) . ' ' . $i; $i++; }
        $usedNames[$sheetName] = true;

        echo '<Worksheet ss:Name="' . xmlesc($sheetName) . '"><Table>';
        $first = true;
        foreach ($rep['rows'] as $row) {
            echo '<Row>';
            foreach ($row as $cell) {
                if ($first) {
                    echo '<Cell ss:StyleID="hdr"><Data ss:Type="String">' . xmlesc($cell) . '</Data></Cell>';
                } else if (is_int($cell) || is_float($cell)) {
                    echo '<Cell><Data ss:Type="Number">' . $cell . '</Data></Cell>';
                } else {
                    echo '<Cell ss:StyleID="txt"><Data ss:Type="String">' . xmlesc($cell) . '</Data></Cell>';
                }
            }
            echo '</Row>';
            $first = false;
        }
        echo '</Table></Worksheet>' . "\n";
    }
    echo '</Workbook>';
}

// ---------------------------------------------------------------------------
// 6) เดินเรื่องหลัก
// ---------------------------------------------------------------------------
try {
    $meta = [
        'exported_at'   => date('Y-m-d H:i:s'),
        'group'         => $groupFilter !== '' ? $groupFilter : 'ทุกกลุ่ม',
        'classroom'     => $classroomFilter !== '' ? $classroomFilter : 'ทุกห้อง',
        'student_count' => count($students),
    ];

    if ($report === 'all' || $format === 'xls') {
        // สร้างทุกรายงาน (หรือรายงานเดียวแต่เป็นสมุดงาน)
        if ($report === 'all') {
            $built = [];
            foreach ($REPORTS as $fn) $built[] = $fn();
        } else {
            if (!isset($REPORTS[$report])) { http_response_code(400); echo 'ไม่พบชนิดรายงาน'; exit; }
            $built = [$REPORTS[$report]()];
        }
        outputWorkbook($built, $meta);
    } else {
        // CSV รายงานเดียว
        if (!isset($REPORTS[$report])) { http_response_code(400); echo 'ไม่พบชนิดรายงาน'; exit; }
        outputCSV($REPORTS[$report]());
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'เกิดข้อผิดพลาดในการสร้างรายงาน: ' . $e->getMessage();
}
