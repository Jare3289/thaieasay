<?php
/**
 * chapter45_data.php — ชั้นรวบรวมข้อมูลและคำนวณสถิติสำหรับ "บทที่ 4 และบทที่ 5"
 * ---------------------------------------------------------------------------
 * ไฟล์นี้ทำหน้าที่แปลงข้อมูลดิบในระบบ ให้กลายเป็น "ตัวเลขและหลักฐานทุกตัว"
 * ที่โครงบทที่ 4-5 ของวิทยานิพนธ์เว้นช่องไว้ให้เติม กล่าวคือ
 *
 *   ตาราง 12  ผลเปรียบเทียบก่อน–หลังเรียน (M, SD, t, p, ขนาดอิทธิพล) ภาพรวม + 4 ด้าน
 *             พร้อมผลทดสอบการแจกแจงปกติ (Shapiro-Wilk) และความเที่ยงระหว่างผู้ประเมิน
 *   ตาราง 13  สรุปภาพรวมการเปลี่ยนแปลง 4 ด้าน ระหว่างผลงานครั้งที่ 1 กับครั้งที่ 2 (ให้ AI เขียน)
 *   ตาราง 14  จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง 11 ตัวบ่งชี้ ในผลงาน 2 ครั้ง
 *   ตาราง 15-18  การเปลี่ยนแปลงรายตัวบ่งชี้ แยกตามองค์ประกอบ (ให้ AI เขียน)
 *   ตัวอย่าง (1)-(22)  ข้อความจริงจากผลงานนักเรียน 2 ชิ้นต่อ 1 ตัวบ่งชี้ (AI คัดจากคลังที่นี่)
 *   บทที่ 5   ตัวเลขทุกตัวในส่วนสรุปผล + ปัญหาจากบันทึกหลังสอนสำหรับเขียนข้อเสนอแนะ
 *
 * ไฟล์นี้ "อ่านอย่างเดียว" ไม่แก้ไขข้อมูลวิจัยใด ๆ
 * นิยามเชิงปฏิบัติการทุกข้อ (เช่น "ปรากฏข้อบกพร่อง" หมายถึงอะไร) รวมไว้ที่ ch45_meta()
 * เพื่อให้ผู้วิจัยเขียนอธิบายวิธีนับในบทที่ 3 ได้ตรงกับที่ระบบคำนวณจริง
 */

require_once 'ai_config.php';
require_once 'chapter45_stats.php';
require_once 'thai_text_utils.php';

/* =========================================================================
 * ส่วนที่ 1  โครงสร้างเกณฑ์การประเมิน (4 องค์ประกอบ 11 ตัวบ่งชี้)
 * ========================================================================= */

/** องค์ประกอบทั้ง 4 ด้าน พร้อมคะแนนเต็มและเลขตารางที่ใช้ในบทที่ 4 */
function ch45_domains() {
    return [
        'd1' => ['key' => 'd1', 'no' => 1, 'name' => 'เนื้อหาสาระ',
                 'max' => 27, 'table' => 15, 'section' => '2.1',
                 'indicators' => ['1.1', '1.2', '1.3']],
        'd2' => ['key' => 'd2', 'no' => 2, 'name' => 'องค์ประกอบและการลำดับเรื่อง',
                 'max' => 12, 'table' => 16, 'section' => '2.2',
                 'indicators' => ['2.1', '2.2']],
        'd3' => ['key' => 'd3', 'no' => 3, 'name' => 'การใช้สำนวนภาษา',
                 'max' => 15, 'table' => 17, 'section' => '2.3',
                 'indicators' => ['3.1', '3.2', '3.3']],
        'd4' => ['key' => 'd4', 'no' => 4, 'name' => 'อักขรวิธีและกลไกการเขียน',
                 'max' => 6,  'table' => 18, 'section' => '2.4',
                 'indicators' => ['4.1', '4.2', '4.3']],
    ];
}

/**
 * ตัวบ่งชี้ทั้ง 11 ข้อ พร้อมข้อมูลที่บทที่ 4 ต้องใช้
 *   defect     = ข้อความข้อบกพร่องตามที่ปรากฏในตาราง 14 (คงถ้อยคำเดิมของโครงวิทยานิพนธ์)
 *   genre_bound= true คือตัวบ่งชี้ที่ได้รับอิทธิพลจาก "ประเภทของงานเขียน" โดยตรง
 *                (การขยายความและเหตุผล, การเลือกใช้คำ) ต้องรายงานเป็น "การปรับกลวิธี"
 *                ไม่ใช่ "พัฒนาการ" เพื่อไม่ให้ตีความความต่างของประเภทงานเขียนว่าเป็นผลของการสอน
 *   ai_scored  = false คือข้อที่ AI ตรวจจากไฟล์พิมพ์แทนไม่ได้ (ความเรียบร้อย/ลายมือ)
 *   ex         = หมายเลขตัวอย่างคู่ที่ใช้ในบทที่ 4 เช่น [1,2] หมายถึงตัวอย่าง (1) และ (2)
 */
function ch45_indicators() {
    static $cache = null;
    if ($cache !== null) return $cache;

    $meta = [
        '1.1' => ['domain' => 'd1', 'sub' => '2.1.1', 'genre_bound' => false,
                  'defect' => 'ถ่ายทอดเนื้อหาออกนอกประเด็นหรือเกินขอบเขตที่กำหนด'],
        '1.2' => ['domain' => 'd1', 'sub' => '2.1.2', 'genre_bound' => false,
                  'defect' => 'ไม่ปรากฏแก่นเรื่อง หรือไม่รักษาแก่นเรื่องไว้ตลอดทั้งเรื่อง'],
        '1.3' => ['domain' => 'd1', 'sub' => '2.1.3', 'genre_bound' => true,
                  'defect' => 'ขยายความไม่เพียงพอ หรือให้เหตุผลสนับสนุนไม่หนักแน่น'],
        '2.1' => ['domain' => 'd2', 'sub' => '2.2.1', 'genre_bound' => false,
                  'defect' => 'เขียนย่อหน้านำหรือย่อหน้าสรุปไม่ทำหน้าที่ตามองค์ประกอบ'],
        '2.2' => ['domain' => 'd2', 'sub' => '2.2.2', 'genre_bound' => false,
                  'defect' => 'ลำดับประเด็นไม่เป็นระบบ ไม่ใช้ถ้อยคำเชื่อมโยงระหว่างย่อหน้า'],
        '3.1' => ['domain' => 'd3', 'sub' => '2.3.1', 'genre_bound' => false,
                  'defect' => 'เขียนประโยคยาวต่อเนื่องไม่แบ่งประโยค หรือละประธานจนความหมายกำกวม'],
        '3.2' => ['domain' => 'd3', 'sub' => '2.3.2', 'genre_bound' => true,
                  'defect' => 'ใช้คำซ้ำ หรือเลือกใช้คำไม่ตรงกับความหมายที่ต้องการสื่อ'],
        '3.3' => ['domain' => 'd3', 'sub' => '2.3.3', 'genre_bound' => false,
                  'defect' => 'ใช้ภาษาระดับกันเองปะปนกับภาษาระดับทางการ'],
        '4.1' => ['domain' => 'd4', 'sub' => '2.4.1', 'genre_bound' => false,
                  'defect' => 'เขียนสะกดคำไม่ถูกต้องตั้งแต่ 3 แห่งขึ้นไป'],
        '4.2' => ['domain' => 'd4', 'sub' => '2.4.2', 'genre_bound' => false,
                  'defect' => 'เว้นวรรคผิดตำแหน่ง หรือไม่เว้นวรรคระหว่างประโยค'],
        '4.3' => ['domain' => 'd4', 'sub' => '2.4.3', 'genre_bound' => false,
                  'defect' => 'ปรากฏการขีดฆ่าและเขียนแทรกจำนวนมาก หรือย่อหน้าไม่สม่ำเสมอ'],
    ];

    $cache = [];
    $i = 0;
    foreach (ai_rubric() as $it) {
        $id = $it['id'];
        if (!isset($meta[$id])) continue;
        $i++;
        $cache[$id] = array_merge($meta[$id], [
            'id'         => $id,
            'no'         => $i,
            'name'       => $it['name'],
            'col'        => 'score_' . str_replace('.', '_', $id),
            'multiplier' => (float)$it['multiplier'],
            'max'        => (float)$it['max'],
            'guide'      => $it['guide'],
            'ai_scored'  => !empty($it['ai']),
            'ex'         => [$i * 2 - 1, $i * 2],
        ]);
    }
    return $cache;
}

/** ตัวบ่งชี้ทั้งหมดขององค์ประกอบหนึ่ง */
function ch45_domain_indicators($domainKey) {
    $out = [];
    foreach (ch45_indicators() as $id => $ind) {
        if ($ind['domain'] === $domainKey) $out[$id] = $ind;
    }
    return $out;
}

/* =========================================================================
 * ส่วนที่ 2  ข้อมูลประจำงานวิจัย (ผู้วิจัยกรอกเองในหน้าเว็บ)
 * ========================================================================= */

/** คีย์การตั้งค่าทั้งหมดของโมดูลนี้ พร้อมค่าเริ่มต้นและคำอธิบายสำหรับหน้าตั้งค่า */
function ch45_meta_fields() {
    return [
        'school'        => ['label' => 'ชื่อสถานศึกษา',            'default' => 'โรงเรียนชัยนาทพิทยาคม', 'type' => 'text'],
        'academic_year' => ['label' => 'ปีการศึกษา',               'default' => '',   'type' => 'text'],
        'grade_level'   => ['label' => 'ระดับชั้น',                 'default' => 'มัธยมศึกษาปีที่ 5', 'type' => 'text'],
        'classroom'     => ['label' => 'ห้องที่เป็นตัวอย่างวิจัย',   'default' => '',   'type' => 'text'],
        'population_n'  => ['label' => 'จำนวนประชากร (คน)',        'default' => '',   'type' => 'number'],
        'sample_n'      => ['label' => 'จำนวนตัวอย่างวิจัย (คน)',   'default' => '40', 'type' => 'number'],
        'units'         => ['label' => 'จำนวนหน่วยการเรียนรู้',      'default' => '2',  'type' => 'number'],
        'periods'       => ['label' => 'จำนวนคาบรวม',               'default' => '12', 'type' => 'number'],
        'weeks'         => ['label' => 'ระยะเวลาทดลอง (สัปดาห์)',   'default' => '6',  'type' => 'number'],
        'work1_phase'   => ['label' => 'รอบงานที่ใช้เป็น "ผลงานครั้งที่ 1"', 'default' => 'task1_d2', 'type' => 'phase'],
        'work2_phase'   => ['label' => 'รอบงานที่ใช้เป็น "ผลงานครั้งที่ 2"', 'default' => 'task2_d2', 'type' => 'phase'],
        'work1_genre'   => ['label' => 'ประเภทงานเขียนครั้งที่ 1',   'default' => 'เรียงความเชิงบรรยาย', 'type' => 'text'],
        'work2_genre'   => ['label' => 'ประเภทงานเขียนครั้งที่ 2',   'default' => 'เรียงความเชิงวิจารณ์', 'type' => 'text'],
        'defect_cut'    => ['label' => 'เกณฑ์นับว่า "ปรากฏข้อบกพร่อง" (คะแนนดิบไม่เกิน)', 'default' => '2', 'type' => 'number'],
        'rater_types'   => ['label' => 'ผู้ประเมินที่นำมาคิดความเที่ยงระหว่างผู้ประเมิน', 'default' => 'ครูประเมิน,ผู้เชี่ยวชาญประเมิน', 'type' => 'text'],
        'score_source'  => ['label' => 'คะแนนที่ใช้วิเคราะห์เชิงปริมาณ', 'default' => 'mean', 'type' => 'source'],
        'word_target'   => ['label' => 'เกณฑ์ความยาวเรียงความ (คำ)',  'default' => '250-300', 'type' => 'text'],
    ];
}

/** อ่านข้อมูลประจำงานวิจัยจากตาราง app_settings (คีย์ขึ้นต้นด้วย ch45_) */
function ch45_meta(PDO $pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;

    $fields = ch45_meta_fields();
    $out = [];
    foreach ($fields as $k => $f) $out[$k] = $f['default'];

    try {
        // กรองด้วย PHP แทน LIKE เพราะการหนีอักขระ _ ใน LIKE ต่างกันไปตามชนิดฐานข้อมูล
        $stmt = $pdo->query('SELECT skey, svalue FROM app_settings');
        foreach ($stmt->fetchAll() as $row) {
            $skey = (string)$row['skey'];
            if (strpos($skey, 'ch45_') !== 0) continue;
            $k = substr($skey, 5);
            if (isset($fields[$k]) && $row['svalue'] !== null && $row['svalue'] !== '') {
                $out[$k] = (string)$row['svalue'];
            }
        }
    } catch (Exception $e) {
        // ยังไม่มีตาราง app_settings ก็ใช้ค่าเริ่มต้นไปก่อน
    }

    // ค่าที่ต้องเป็นตัวเลข/ต้องอยู่ในขอบเขตที่ระบบรู้จัก
    if (!in_array($out['work1_phase'], ai_all_phases(), true)) $out['work1_phase'] = 'task1_d2';
    if (!in_array($out['work2_phase'], ai_all_phases(), true)) $out['work2_phase'] = 'task2_d2';
    $cut = (int)$out['defect_cut'];
    $out['defect_cut'] = ($cut >= 0 && $cut <= 3) ? $cut : 2;

    // นิยามเชิงปฏิบัติการที่ใช้จริงในการคำนวณ — นำไปเขียนอธิบายวิธีนับได้ตรง ๆ
    $srcLabel = ['mean' => 'คะแนนเฉลี่ยจากผู้ประเมินทุกคนที่ตรวจผลงานชิ้นนั้น',
                 'teacher' => 'คะแนนของครูผู้สอน', 'expert' => 'คะแนนของผู้เชี่ยวชาญ'];
    if (!isset($srcLabel[$out['score_source']])) $out['score_source'] = 'mean';
    $out['score_source_label'] = $srcLabel[$out['score_source']];
    $out['defect_rule'] = 'นับว่า "ปรากฏข้อบกพร่อง" ในตัวบ่งชี้หนึ่ง เมื่อผลงานชิ้นนั้นได้คะแนนดิบของตัวบ่งชี้'
        . 'ไม่เกิน ' . $out['defect_cut'] . ' จากคะแนนเต็ม 4 ตามเกณฑ์ประเมินแบบแยกองค์ประกอบ '
        . '(ใช้' . $out['score_source_label'] . ')';
    $out['work1_label'] = 'ครั้งที่ 1 ' . $out['work1_genre'];
    $out['work2_label'] = 'ครั้งที่ 2 ' . $out['work2_genre'];
    $out['work1_eval_phase'] = ch45_eval_phase_of($out['work1_phase']);
    $out['work2_eval_phase'] = ch45_eval_phase_of($out['work2_phase']);

    $cache = $out;
    return $out;
}

/**
 * เขียนค่าตั้งค่าหนึ่งค่าลงตาราง app_settings
 *
 * ไม่เรียก ai_save_setting() เพราะฟังก์ชันนั้นมีรายการคีย์ที่อนุญาตเฉพาะของระบบ AI (ai_*)
 * การไปเพิ่มคีย์ ch45_* เข้าไปในรายการนั้นเท่ากับขยายขอบเขตที่ฟังก์ชันเดิมตั้งใจจำกัดไว้
 * จึงแยกตัวเขียนของโมดูลนี้ออกมา และจำกัดให้เขียนได้เฉพาะคีย์ที่ขึ้นต้นด้วย ch45_ เท่านั้น
 */
function ch45_save_setting(PDO $pdo, $key, $value) {
    if (strpos((string)$key, 'ch45_') !== 0) return false;
    $stmt = $pdo->prepare('
        INSERT INTO app_settings (skey, svalue) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = CURRENT_TIMESTAMP
    ');
    return $stmt->execute([$key, (string)$value]);
}

/** บันทึกข้อมูลประจำงานวิจัย (เฉพาะคีย์ที่รู้จักเท่านั้น) */
function ch45_save_meta(PDO $pdo, array $in) {
    $fields = ch45_meta_fields();
    $saved = [];
    foreach ($in as $k => $v) {
        if (!isset($fields[$k])) continue;
        if (ch45_save_setting($pdo, 'ch45_' . $k, trim((string)$v))) $saved[] = $k;
    }
    return $saved;
}

/** รอบการให้คะแนนในตาราง evaluations ที่ตรงกับรอบงานเขียน (task1_d2 → task1) */
function ch45_eval_phase_of($essayPhase) {
    $p = (string)$essayPhase;
    if (strpos($p, 'task1') === 0) return 'task1';
    if (strpos($p, 'task2') === 0) return 'task2';
    return $p; // pretest / posttest
}

/* =========================================================================
 * ส่วนที่ 3  ดึงข้อมูลดิบทั้งหมดมาไว้ในโครงสร้างเดียว
 * ========================================================================= */

/**
 * รวบรวมข้อมูลทุกอย่างที่บทที่ 4-5 ต้องใช้
 * $opt = ['group' => กลุ่มการวิจัย, 'classroom' => ห้องเรียน]
 *
 * ผลลัพธ์เป็นอาร์เรย์ที่ส่งต่อให้ ch45_quant / ch45_defects / ch45_evidence ได้ทันที
 */
function ch45_dataset(PDO $pdo, array $opt = []) {
    $meta = ch45_meta($pdo);
    $group     = isset($opt['group'])     ? trim((string)$opt['group'])     : '';
    $classroom = isset($opt['classroom']) ? trim((string)$opt['classroom']) : '';

    // ---- 1) รายชื่อนักเรียนในขอบเขตการวิเคราะห์ ----
    $conds = []; $params = [];
    if ($group === '__none__') {
        $conds[] = "(student_group IS NULL OR student_group = '')";
    } elseif ($group !== '') {
        $conds[] = 'student_group = ?';
        $params[] = $group;
    }
    if ($classroom !== '') {
        $conds[] = 'classroom = ?';
        $params[] = $classroom;
    }
    $sql = 'SELECT student_id, student_name, classroom, student_group FROM students';
    if ($conds) $sql .= ' WHERE ' . implode(' AND ', $conds);
    $sql .= ' ORDER BY student_id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $students = []; $sids = []; $no = 0;
    foreach ($stmt->fetchAll() as $row) {
        $sid = (string)$row['student_id'];
        $no++;
        $students[$sid] = [
            'id'        => $sid,
            'no'        => $no,                       // เลขนิรนามที่ใช้อ้างในบทที่ 4 ("นักเรียนคนที่ N")
            'name'      => (string)$row['student_name'],
            'classroom' => (string)($row['classroom'] ?? ''),
            'group'     => (string)($row['student_group'] ?? ''),
        ];
        $sids[] = $sid;
    }
    if (!$sids) {
        return ['meta' => $meta, 'students' => [], 'sids' => [], 'evals' => [],
                'essays' => [], 'topics' => [], 'ai' => [], 'reflect' => [], 'logs' => []];
    }

    $ph = implode(',', array_fill(0, count($sids), '?'));

    // ---- 2) คะแนนจากแบบประเมิน (ครู / ตนเอง / เพื่อน / ผู้เชี่ยวชาญ) ----
    $inds = ch45_indicators();
    $cols = [];
    foreach ($inds as $ind) $cols[] = $ind['col'];
    $stmt = $pdo->prepare('SELECT student_id, evaluator_type, evaluator_name, test_phase, total_score, '
        . implode(', ', $cols) . ' FROM evaluations WHERE student_id IN (' . $ph . ')');
    $stmt->execute($sids);

    $roleMap = ['ครูประเมิน' => 'teacher', 'ตนเองประเมิน' => 'self',
                'เพื่อนประเมิน' => 'peer', 'ผู้เชี่ยวชาญประเมิน' => 'expert'];
    $evals = [];   // [sid][phase][role][] = ['raw'=>..., 'weighted'=>..., 'total'=>..., 'rater'=>...]
    foreach ($stmt->fetchAll() as $row) {
        $sid   = (string)$row['student_id'];
        $phase = (string)$row['test_phase'];
        $role  = $roleMap[(string)$row['evaluator_type']] ?? 'other';
        $raw = []; $weighted = [];
        foreach ($inds as $id => $ind) {
            $w = $row[$ind['col']];
            if ($w === null || $w === '') { $raw[$id] = null; $weighted[$id] = null; continue; }
            $weighted[$id] = (float)$w;
            // ฐานข้อมูลเก็บคะแนน "หลังถ่วงน้ำหนัก" จึงต้องหารตัวคูณกลับเป็นคะแนนดิบ 0-4
            $raw[$id] = ($ind['multiplier'] > 0) ? round((float)$w / $ind['multiplier'], 4) : null;
        }
        $evals[$sid][$phase][$role][] = [
            'rater'    => (string)$row['evaluator_name'],
            'raw'      => $raw,
            'weighted' => $weighted,
            'total'    => ($row['total_score'] === null ? null : (float)$row['total_score']),
        ];
    }

    // ---- 3) เรียงความทุกรอบ ----
    $stmt = $pdo->prepare('SELECT student_id, essay_phase, intro_content, body_content, conclusion_content, '
        . 'word_count, updated_at FROM student_essays WHERE student_id IN (' . $ph . ')');
    $stmt->execute($sids);
    $essays = [];
    foreach ($stmt->fetchAll() as $row) {
        $body = json_decode((string)($row['body_content'] ?? ''), true);
        if (!is_array($body)) {
            $body = (trim((string)($row['body_content'] ?? '')) !== '') ? [(string)$row['body_content']] : [];
        }
        $body = array_values(array_filter(array_map('strval', $body), function ($p) { return trim($p) !== ''; }));
        $intro = (string)($row['intro_content'] ?? '');
        $concl = (string)($row['conclusion_content'] ?? '');
        $full  = trim($intro . "\n" . implode("\n", $body) . "\n" . $concl);
        $essays[(string)$row['student_id']][(string)$row['essay_phase']] = [
            'intro'      => $intro,
            'body'       => $body,
            'conclusion' => $concl,
            'text'       => $full,
            'word_count' => (int)($row['word_count'] ?? 0),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'has'        => ($full !== ''),
        ];
    }

    // ---- 4) หัวข้อเรียงความที่ครูกำหนดแต่ละรอบ ----
    $topics = [];
    try { $topics = essay_topics_map($pdo); } catch (Exception $e) { $topics = []; }

    // ---- 5) ผลตรวจของ AI รายฉบับ (ใช้เป็นข้อมูลประกอบเชิงคุณภาพ) ----
    $ai = [];
    try {
        $stmt = $pdo->prepare('SELECT student_id, essay_phase, overall_comment, strengths, improvements, '
            . 'scores, total_score, max_score, quality_level FROM essay_ai_feedback WHERE student_id IN (' . $ph . ')');
        $stmt->execute($sids);
        foreach ($stmt->fetchAll() as $row) {
            $ai[(string)$row['student_id']][(string)$row['essay_phase']] = [
                'overall'      => (string)($row['overall_comment'] ?? ''),
                'strengths'    => json_decode((string)($row['strengths'] ?? ''), true) ?: [],
                'improvements' => json_decode((string)($row['improvements'] ?? ''), true) ?: [],
                'scores'       => json_decode((string)($row['scores'] ?? ''), true) ?: [],
                'total'        => (float)($row['total_score'] ?? 0),
                'max'          => (float)($row['max_score'] ?? 0),
                'level'        => (string)($row['quality_level'] ?? ''),
            ];
        }
    } catch (Exception $e) { /* ยังไม่มีตาราง AI ก็วิเคราะห์ส่วนอื่นต่อได้ */ }

    // ---- 6) เครื่องมือสะท้อนคิดของนักเรียน (ใช้เป็นข้อมูลประกอบบทที่ 5) ----
    $reflect = ['problems' => [], 'checklists' => [], 'reflections' => []];
    foreach ([['writing_problems', 'problems'], ['self_checklists', 'checklists'],
              ['learning_reflections', 'reflections']] as $t) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM ' . $t[0] . ' WHERE student_id IN (' . $ph . ')');
            $stmt->execute($sids);
            foreach ($stmt->fetchAll() as $row) {
                $reflect[$t[1]][(string)$row['student_id']][(int)($row['task_unit'] ?? 1)] = $row;
            }
        } catch (Exception $e) { /* ข้ามตารางที่ยังไม่มี */ }
    }

    // ---- 7) บันทึกหลังสอนของผู้วิจัย (ใช้เขียนข้อเสนอแนะในบทที่ 5) ----
    $logs = ch45_teaching_logs($pdo);

    return [
        'meta'     => $meta,
        'students' => $students,
        'sids'     => $sids,
        'evals'    => $evals,
        'essays'   => $essays,
        'topics'   => $topics,
        'ai'       => $ai,
        'reflect'  => $reflect,
        'logs'     => $logs,
        'filter'   => ['group' => $group, 'classroom' => $classroom],
    ];
}

/** บทบาทผู้ประเมินที่นับเป็น "ผู้ประเมินผลงาน" (ไม่รวมการประเมินตนเองและการประเมินโดยเพื่อน) */
function ch45_rater_roles(array $meta) {
    $roleMap = ['ครูประเมิน' => 'teacher', 'ผู้เชี่ยวชาญประเมิน' => 'expert',
                'ตนเองประเมิน' => 'self', 'เพื่อนประเมิน' => 'peer'];
    $roles = [];
    foreach (explode(',', (string)($meta['rater_types'] ?? '')) as $w) {
        $w = trim($w);
        if (isset($roleMap[$w]) && !in_array($roleMap[$w], ['self', 'peer'], true)) $roles[] = $roleMap[$w];
    }
    return $roles ?: ['teacher', 'expert'];
}

/**
 * คะแนนที่ใช้วิเคราะห์เชิงปริมาณของนักเรียนหนึ่งคนในรอบหนึ่ง
 *
 * score_source = 'mean'    (ค่าเริ่มต้น) เฉลี่ยคะแนนของผู้ประเมินทุกคนที่ตรวจผลงานชิ้นนั้น
 *                          ตรงตามที่โครงบทที่ 4 ระบุว่า "ใช้คะแนนเฉลี่ยจากผู้ประเมินทั้งสองคนในการวิเคราะห์"
 *                          ถ้ารอบนั้นมีผู้ประเมินเพียงคนเดียว ก็จะได้คะแนนของคนนั้นตามปกติ
 *              = 'teacher' ใช้คะแนนของครูผู้สอนอย่างเดียว
 *              = 'expert'  ใช้คะแนนของผู้เชี่ยวชาญอย่างเดียว
 *
 * ไม่ว่าจะเลือกแบบใด ระบบจะไม่นำคะแนนประเมินตนเองและคะแนนจากเพื่อนมารวม
 * เพราะเป็นข้อมูลประกอบ ไม่ใช่คะแนนที่ใช้ตัดสินผลสัมฤทธิ์
 */
function ch45_scores_of(array $ds, $sid, $phase, $role = null) {
    $source = $role ?: ((string)($ds['meta']['score_source'] ?? '') ?: 'mean');
    $roles  = ($source === 'mean') ? ch45_rater_roles($ds['meta']) : [$source];

    $rows = [];
    foreach ($roles as $r) {
        foreach ($ds['evals'][$sid][$phase][$r] ?? [] as $one) $rows[] = $one;
    }
    if (!$rows) return null;

    $inds = ch45_indicators();
    $raw = []; $weighted = [];
    foreach ($inds as $id => $ind) {
        $rv = []; $wv = [];
        foreach ($rows as $r) {
            if ($r['raw'][$id] !== null)      $rv[] = $r['raw'][$id];
            if ($r['weighted'][$id] !== null) $wv[] = $r['weighted'][$id];
        }
        $raw[$id]      = $rv ? array_sum($rv) / count($rv) : null;
        $weighted[$id] = $wv ? array_sum($wv) / count($wv) : null;
    }
    $total = 0.0; $complete = true;
    foreach ($inds as $id => $ind) {
        if ($weighted[$id] === null) { $complete = false; continue; }
        $total += $weighted[$id];
    }
    return ['raw' => $raw, 'weighted' => $weighted,
            'total' => ($complete ? $total : null), 'complete' => $complete,
            'raters' => count($rows)];
}

/** คะแนนรวมรายด้าน (เต็ม 27/12/15/6) จากชุดคะแนนหลังถ่วงน้ำหนัก */
function ch45_domain_total(array $weighted) {
    $out = [];
    foreach (ch45_domains() as $k => $d) {
        $sum = 0.0; $ok = true;
        foreach ($d['indicators'] as $id) {
            if (!isset($weighted[$id]) || $weighted[$id] === null) { $ok = false; break; }
            $sum += $weighted[$id];
        }
        $out[$k] = $ok ? $sum : null;
    }
    return $out;
}

/* =========================================================================
 * ส่วนที่ 4  ตาราง 12 — ผลการเปรียบเทียบก่อนเรียนและหลังเรียน (เชิงปริมาณ)
 * ========================================================================= */

/**
 * คำนวณทุกตัวเลขที่ตาราง 12 และย่อหน้าบรรยายใต้ตารางต้องใช้
 *
 * คืนค่า:
 *   rows[]        แถวของตาราง 12 (ภาพรวม + 4 ด้าน) พร้อม M, SD ก่อน/หลัง, t, df, p, ขนาดอิทธิพล
 *   normality     ผลทดสอบ Shapiro-Wilk ของคะแนนผลต่าง (ภาพรวมและรายด้าน)
 *   interrater    ความเที่ยงระหว่างผู้ประเมิน (Pearson r ทุกคู่ + ICC) แยกตามรอบ
 *   ranking       ลำดับด้านที่เปลี่ยนแปลงมาก→น้อย ตามร้อยละของคะแนนเต็มและขนาดอิทธิพล
 *   pairs         รายชื่อนักเรียนที่มีคะแนนครบทั้งก่อนและหลังเรียน (ฐานของการทดสอบทีแบบจับคู่)
 */
function ch45_quant(array $ds) {
    $meta = $ds['meta'];
    $domains = ch45_domains();

    // ---- 1) จับคู่คะแนนก่อน–หลังเรียนรายคน ----
    $pairs = [];
    foreach ($ds['sids'] as $sid) {
        $pre  = ch45_scores_of($ds, $sid, 'pretest');
        $post = ch45_scores_of($ds, $sid, 'posttest');
        if (!$pre || !$post || !$pre['complete'] || !$post['complete']) continue;
        $pairs[] = [
            'sid'      => $sid,
            'no'       => $ds['students'][$sid]['no'],
            'pre'      => $pre,
            'post'     => $post,
            'pre_dom'  => ch45_domain_total($pre['weighted']),
            'post_dom' => ch45_domain_total($post['weighted']),
        ];
    }
    $n = count($pairs);

    // ---- 2) แถวภาพรวม + รายด้าน ----
    $rows = [];
    $series = [];   // เก็บคะแนนไว้ใช้ทดสอบการแจกแจงต่อ

    $preAll = []; $postAll = [];
    foreach ($pairs as $p) { $preAll[] = $p['pre']['total']; $postAll[] = $p['post']['total']; }
    $series['overall'] = ['pre' => $preAll, 'post' => $postAll];

    $tt = ch45_paired_t($preAll, $postAll);
    $rows[] = ch45_quant_row('overall', 'ภาพรวม', 60.0, $preAll, $postAll, $tt);

    foreach ($domains as $k => $d) {
        $pre = []; $post = [];
        foreach ($pairs as $p) {
            if ($p['pre_dom'][$k] === null || $p['post_dom'][$k] === null) continue;
            $pre[]  = $p['pre_dom'][$k];
            $post[] = $p['post_dom'][$k];
        }
        $series[$k] = ['pre' => $pre, 'post' => $post];
        $tt = ch45_paired_t($pre, $post);
        $rows[] = ch45_quant_row($k, 'ด้านที่ ' . $d['no'] . ' ' . $d['name'], (float)$d['max'], $pre, $post, $tt);
    }

    // ---- 3) การแจกแจงปกติของคะแนนผลต่าง (Shapiro-Wilk) ----
    $normality = [];
    foreach ($series as $k => $s) {
        $diff = [];
        $m = min(count($s['pre']), count($s['post']));
        for ($i = 0; $i < $m; $i++) $diff[] = $s['post'][$i] - $s['pre'][$i];
        $normality[$k] = ch45_shapiro_wilk($diff);
    }

    // ---- 4) ความเที่ยงระหว่างผู้ประเมิน ----
    $interrater = ch45_interrater($ds);

    // ---- 5) ลำดับด้านที่เปลี่ยนแปลงมากที่สุด ----
    // คะแนนเต็มแต่ละด้านไม่เท่ากัน จึงเรียงลำดับด้วย "ขนาดอิทธิพล" เป็นหลัก
    // และรายงานร้อยละของคะแนนเต็มควบคู่ไปตามที่โครงบทที่ 4 กำหนด
    $ranking = [];
    foreach ($rows as $r) {
        if ($r['key'] === 'overall') continue;
        $ranking[] = $r;
    }
    usort($ranking, function ($a, $b) {
        $x = ($a['dz'] === null) ? -INF : $a['dz'];
        $y = ($b['dz'] === null) ? -INF : $b['dz'];
        if ($x == $y) return 0;
        return ($x < $y) ? 1 : -1;
    });
    foreach ($ranking as $i => $r) $ranking[$i]['rank'] = $i + 1;

    return [
        'n'          => $n,
        'df'         => max(0, $n - 1),
        'rows'       => $rows,
        'by_key'     => array_column($rows, null, 'key'),
        'normality'  => $normality,
        'interrater' => $interrater,
        'ranking'    => $ranking,
        'pairs'      => $pairs,
        'alpha'      => 0.05,
    ];
}

/** ประกอบแถวหนึ่งของตาราง 12 */
function ch45_quant_row($key, $label, $max, array $pre, array $post, array $tt) {
    $preD  = ch45_describe($pre);
    $postD = ch45_describe($post);
    $prePct  = ($preD['mean']  === null || $max <= 0) ? null : $preD['mean']  * 100 / $max;
    $postPct = ($postD['mean'] === null || $max <= 0) ? null : $postD['mean'] * 100 / $max;
    return [
        'key'       => $key,
        'label'     => $label,
        'max'       => $max,
        'n'         => $tt['n'],
        'pre_mean'  => $preD['mean'],  'pre_sd'  => $preD['sd'],
        'post_mean' => $postD['mean'], 'post_sd' => $postD['sd'],
        'pre_pct'   => $prePct,        'post_pct' => $postPct,
        'gain'      => $tt['mean_diff'],
        'gain_pct'  => ($prePct === null || $postPct === null) ? null : ($postPct - $prePct),
        't'         => $tt['t'], 'df' => $tt['df'], 'p' => $tt['p'],
        'sig'       => ($tt['p'] !== null && $tt['p'] < 0.05),
        'dz'        => $tt['dz'], 'd_av' => $tt['d_av'],
        'effect'    => ch45_effect_label($tt['dz']),
        'ci_low'    => $tt['ci_low'], 'ci_high' => $tt['ci_high'],
        'sd_diff'   => $tt['sd_diff'],
    ];
}

/**
 * ความเที่ยงระหว่างผู้ประเมิน แยกตามรอบการประเมิน
 * ตรวจจากคะแนนรวมและรายด้าน ของผู้ประเมินทุกคนที่ตรวจผลงานชุดเดียวกัน
 * (โครงบทที่ 4 ต้องการค่าสหสัมพันธ์ระหว่างผู้ประเมิน 2 คน — ระบบรายงานทั้ง r รายคู่ และ ICC)
 */
function ch45_interrater(array $ds) {
    $roles = ch45_rater_roles($ds['meta']);

    $out = [];
    foreach (['pretest' => 'ก่อนเรียน', 'task1' => 'ภาระงานหน่วยที่ 1',
              'task2' => 'ภาระงานหน่วยที่ 2', 'posttest' => 'หลังเรียน'] as $phase => $phLabel) {

        // รวบรวม "ผู้ตรวจแต่ละคน" (บทบาท + ชื่อผู้ประเมิน) ที่ให้คะแนนในรอบนี้
        $raters = [];   // [raterKey][sid] = คะแนนรวม
        foreach ($ds['sids'] as $sid) {
            foreach ($roles as $role) {
                foreach ($ds['evals'][$sid][$phase][$role] ?? [] as $r) {
                    $key = $role . ':' . $r['rater'];
                    if ($r['total'] !== null) $raters[$key][$sid] = $r['total'];
                }
            }
        }
        if (count($raters) < 2) continue;

        // นักเรียนที่ผู้ตรวจ "ทุกคน" ให้คะแนนครบ = ฐานของการคำนวณ
        $keys = array_keys($raters);
        $common = null;
        foreach ($keys as $k) {
            $ids = array_keys($raters[$k]);
            $common = ($common === null) ? $ids : array_values(array_intersect($common, $ids));
        }
        if (!$common || count($common) < 3) continue;
        sort($common);

        $matrix = [];
        foreach ($common as $sid) {
            $row = [];
            foreach ($keys as $k) $row[] = $raters[$k][$sid];
            $matrix[] = $row;
        }

        $pairsR = [];
        for ($i = 0; $i < count($keys); $i++) {
            for ($j = $i + 1; $j < count($keys); $j++) {
                $a = []; $b = [];
                foreach ($common as $sid) { $a[] = $raters[$keys[$i]][$sid]; $b[] = $raters[$keys[$j]][$sid]; }
                $r = ch45_pearson($a, $b);
                $pairsR[] = ['rater_a' => $keys[$i], 'rater_b' => $keys[$j],
                             'r' => $r['r'], 'p' => $r['p'], 'n' => $r['n']];
            }
        }

        $icc = ch45_icc($matrix);
        $out[$phase] = [
            'phase' => $phase, 'label' => $phLabel,
            'raters' => $keys, 'k' => count($keys), 'n' => count($common),
            'pearson' => $pairsR,
            'icc' => $icc, 'icc_label' => ch45_icc_label($icc['iccK']),
        ];
    }
    return $out;
}

/* =========================================================================
 * ส่วนที่ 5  ตาราง 14 — จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง
 * ========================================================================= */

/**
 * นับจำนวนนักเรียนที่ปรากฏข้อบกพร่องรายตัวบ่งชี้ ในผลงาน 2 ครั้ง
 * นิยาม: ผลงานชิ้นนั้นได้คะแนนดิบของตัวบ่งชี้ "ไม่เกินเกณฑ์ตัด" (ค่าเริ่มต้น 2 จาก 4)
 *
 * คืนค่า rows[] เรียงตามลำดับในตาราง 14 พร้อมจำนวน/ร้อยละของทั้งสองครั้งและผลต่าง
 */
function ch45_defects(array $ds) {
    $meta = $ds['meta'];
    $cut  = (int)$meta['defect_cut'];
    $p1   = $meta['work1_eval_phase'];
    $p2   = $meta['work2_eval_phase'];
    $inds = ch45_indicators();

    // ฐานการนับ = นักเรียนที่มีคะแนนครบทั้งสองครั้ง เพื่อให้ร้อยละสองคอลัมน์เทียบกันได้จริง
    $base = [];
    foreach ($ds['sids'] as $sid) {
        $a = ch45_scores_of($ds, $sid, $p1);
        $b = ch45_scores_of($ds, $sid, $p2);
        if (!$a || !$b || !$a['complete'] || !$b['complete']) continue;
        $base[$sid] = ['w1' => $a, 'w2' => $b];
    }
    $n = count($base);

    $rows = [];
    foreach ($inds as $id => $ind) {
        $c1 = []; $c2 = [];
        foreach ($base as $sid => $b) {
            $r1 = $b['w1']['raw'][$id];
            $r2 = $b['w2']['raw'][$id];
            if ($r1 !== null && $r1 <= $cut + 1e-9) $c1[] = $sid;
            if ($r2 !== null && $r2 <= $cut + 1e-9) $c2[] = $sid;
        }
        $n1 = count($c1); $n2 = count($c2);
        $pct1 = $n ? $n1 * 100.0 / $n : null;
        $pct2 = $n ? $n2 * 100.0 / $n : null;

        // คะแนนดิบเฉลี่ยรายตัวบ่งชี้ของทั้งสองครั้ง (ใช้บรรยายทิศทางการเปลี่ยนแปลง)
        $m1 = []; $m2 = [];
        foreach ($base as $sid => $b) {
            if ($b['w1']['raw'][$id] !== null) $m1[] = $b['w1']['raw'][$id];
            if ($b['w2']['raw'][$id] !== null) $m2[] = $b['w2']['raw'][$id];
        }
        $tt = ch45_paired_t($m1, $m2);

        $rows[$id] = [
            'id' => $id, 'no' => $ind['no'], 'name' => $ind['name'],
            'domain' => $ind['domain'], 'defect' => $ind['defect'],
            'genre_bound' => $ind['genre_bound'], 'ai_scored' => $ind['ai_scored'],
            'n_base' => $n,
            'n1' => $n1, 'pct1' => $pct1, 'students1' => $c1,
            'n2' => $n2, 'pct2' => $pct2, 'students2' => $c2,
            'diff_n'   => $n2 - $n1,
            'diff_pct' => ($pct1 === null || $pct2 === null) ? null : ($pct2 - $pct1),
            'improved' => ($n2 < $n1),
            'mean1' => ch45_mean($m1), 'mean2' => ch45_mean($m2),
            'mean_gain' => $tt['mean_diff'], 't' => $tt['t'], 'p' => $tt['p'], 'dz' => $tt['dz'],
            // นักเรียนที่ครั้งที่ 1 มีข้อบกพร่อง แต่ครั้งที่ 2 ไม่มีแล้ว = ตัวอย่างที่เห็นการเปลี่ยนแปลงชัดที่สุด
            'resolved' => array_values(array_diff($c1, $c2)),
            'persist'  => array_values(array_intersect($c1, $c2)),
            'emerged'  => array_values(array_diff($c2, $c1)),
        ];
    }

    // ตัวบ่งชี้ที่ลดลงมากที่สุด/น้อยที่สุด (ใช้เขียนย่อหน้าใต้ตาราง 14)
    $ranked = array_values($rows);
    usort($ranked, function ($a, $b) {
        $x = ($a['diff_pct'] === null) ? INF : $a['diff_pct'];
        $y = ($b['diff_pct'] === null) ? INF : $b['diff_pct'];
        if ($x == $y) return 0;
        return ($x < $y) ? -1 : 1;   // ลดลงมากที่สุดอยู่ต้นรายการ (diff เป็นลบมากสุด)
    });

    $allDown = true; $anyDown = false;
    foreach ($rows as $r) {
        if ($r['diff_n'] < 0) $anyDown = true;
        if ($r['diff_n'] >= 0) $allDown = false;
    }

    return [
        'n' => $n, 'cut' => $cut, 'rule' => $meta['defect_rule'],
        'work1_phase' => $p1, 'work2_phase' => $p2,
        'rows' => $rows,
        'ranked' => $ranked,
        'most_improved'  => $ranked ? $ranked[0] : null,
        'least_improved' => $ranked ? $ranked[count($ranked) - 1] : null,
        'all_decreased'  => $allDown,
        'any_decreased'  => $anyDown,
        'summary_phrase' => $allDown ? 'ทุกตัวบ่งชี้' : ($anyDown ? 'เกือบทุกตัวบ่งชี้' : 'บางตัวบ่งชี้'),
    ];
}

/* =========================================================================
 * ส่วนที่ 6  ข้อมูลกลไกการเขียน (การสะกดคำ / การเว้นวรรค / ความยาว)
 * ========================================================================= */

/**
 * นับข้อผิดพลาดเชิงกลไกจาก "ตัวบทจริง" ของผลงานทั้งสองครั้ง
 * ใช้เติมช่องว่างในหัวข้อ 2.4.1 ("พบข้อผิดพลาดในการสะกดคำเฉลี่ย … แห่งต่อผลงานหนึ่งชิ้น")
 *
 * ข้อจำกัดที่ต้องระบุในบทที่ 4: ระบบตรวจการสะกดด้วยพจนานุกรมอัตโนมัติ
 * คำวิสามานยนามและคำใหม่บางคำอาจถูกนับเกินจริง จึงเป็น "ค่าประมาณ" ที่ผู้วิจัยควรสุ่มตรวจซ้ำ
 */
function ch45_mechanics(PDO $pdo, array $ds) {
    $meta = $ds['meta'];
    $confirmed = [];
    try { $confirmed = load_confirmed_thai_words($pdo); } catch (Exception $e) { $confirmed = []; }
    $dict = load_thai_dictionary();

    $out = [];
    foreach (['work1' => $meta['work1_phase'], 'work2' => $meta['work2_phase']] as $slot => $phase) {
        $spellCounts = []; $spellTypes = []; $wordCounts = []; $paraCounts = [];
        $maiyamok = []; $wordFreq = [];
        $pieces = 0;
        foreach ($ds['sids'] as $sid) {
            $e = $ds['essays'][$sid][$phase] ?? null;
            if (!$e || !$e['has']) continue;
            $pieces++;
            $wordCounts[] = $e['word_count'] ?: count_thai_words($e['text']);
            $paraCounts[] = 1 + count($e['body']) + 1;

            $occ = 0; $types = [];
            if ($dict) {
                foreach (thai_word_segments($e['text']) as $seg) {
                    if (empty($seg['isWord'])) continue;
                    $w = trim((string)$seg['text']);
                    if ($w === '' || mb_strlen($w, 'UTF-8') <= 1) continue;
                    if (!preg_match('/[\x{0E01}-\x{0E2E}]/u', $w)) continue;
                    if (is_known_thai_word($w, $dict, $confirmed)) continue;
                    $last = mb_substr($w, -1, 1, 'UTF-8');
                    if ($last === 'ๆ' || $last === 'ฯ') {
                        $stripped = mb_substr($w, 0, mb_strlen($w, 'UTF-8') - 1, 'UTF-8');
                        if ($stripped !== '' && is_known_thai_word($stripped, $dict, $confirmed)) continue;
                    }
                    $occ++;
                    $types[$w] = true;
                    $wordFreq[$w] = ($wordFreq[$w] ?? 0) + 1;
                }
            }
            $spellCounts[] = $occ;
            $spellTypes[]  = count($types);
            try {
                $maiyamok[] = count(find_maiyamok_spacing_errors($e['text'], $confirmed, 50));
            } catch (Exception $ex) { /* ข้ามได้ */ }
        }
        arsort($wordFreq);
        $out[$slot] = [
            'phase'        => $phase,
            'label'        => ($slot === 'work1' ? $meta['work1_label'] : $meta['work2_label']),
            'pieces'       => $pieces,
            'spell_mean'   => ch45_mean($spellCounts),
            'spell_sd'     => ch45_sd($spellCounts),
            'spell_total'  => array_sum($spellCounts),
            'spell_max'    => $spellCounts ? max($spellCounts) : null,
            'spell_types_mean' => ch45_mean($spellTypes),
            'spell_ge3'    => count(array_filter($spellCounts, function ($c) { return $c >= 3; })),
            'top_words'    => array_slice($wordFreq, 0, 25, true),
            'word_mean'    => ch45_mean($wordCounts),
            'word_sd'      => ch45_sd($wordCounts),
            'para_mean'    => ch45_mean($paraCounts),
            'maiyamok_mean'=> ch45_mean($maiyamok),
            'dict_ok'      => (bool)$dict,
        ];
    }

    $out['spell_change'] = ($out['work1']['spell_mean'] !== null && $out['work2']['spell_mean'] !== null)
        ? $out['work2']['spell_mean'] - $out['work1']['spell_mean'] : null;
    $out['note'] = 'จำนวนคำที่สะกดผิดนับด้วยพจนานุกรมอัตโนมัติ คำวิสามานยนามและคำเฉพาะบางคำ'
                 . 'อาจถูกนับเกินจริง ผู้วิจัยควรสุ่มตรวจยืนยันก่อนรายงานเป็นตัวเลขในวิทยานิพนธ์';
    return $out;
}

/* =========================================================================
 * ส่วนที่ 7  คลังตัวอย่างข้อความจริงจากผลงานนักเรียน (ตัวอย่าง (1)-(22))
 * ========================================================================= */

/** ตัดข้อความให้สั้นลงโดยไม่ตัดกลางคำ (ใช้กันไม่ให้คำสั่งที่ส่งให้ AI ยาวเกินไป) */
function ch45_trim_text($text, $maxChars = 2200) {
    $t = trim(preg_replace('/[ \t]+/u', ' ', (string)$text));
    if (mb_strlen($t, 'UTF-8') <= $maxChars) return $t;
    return mb_substr($t, 0, $maxChars, 'UTF-8') . ' …';
}

/**
 * คัดผลงานที่เหมาะจะยกเป็นตัวอย่างของตัวบ่งชี้หนึ่ง
 *
 * หลักการคัด (เรียงตามลำดับความสำคัญ):
 *   ครั้งที่ 1 — เลือกผลงานที่ได้คะแนนดิบต่ำที่สุดในตัวบ่งชี้นั้น เพราะเป็นผลงานที่
 *               "เห็นข้อบกพร่องชัดที่สุด" ตรงกับที่บทที่ 4 ต้องยกเป็นตัวอย่างข้อบกพร่อง
 *   ครั้งที่ 2 — ให้ความสำคัญกับนักเรียนคนเดิมที่เคยมีข้อบกพร่องแล้วแก้ได้ (resolved)
 *               เพื่อให้คู่ตัวอย่างแสดง "การเปลี่ยนแปลงของคนเดียวกัน" ซึ่งหนักแน่นกว่าการเทียบคนละคน
 *
 * ส่งข้อความเรียงความจริงไปด้วย เพื่อให้ AI ยกข้อความจากผลงานจริงเท่านั้น ห้ามแต่งเอง
 */
function ch45_evidence(array $ds, $indicatorId, array $defects, $perSlot = 3) {
    $meta = $ds['meta'];
    $row  = $defects['rows'][$indicatorId] ?? null;
    if (!$row) return ['work1' => [], 'work2' => []];

    $p1 = $meta['work1_phase']; $p2 = $meta['work2_phase'];
    $e1 = $meta['work1_eval_phase']; $e2 = $meta['work2_eval_phase'];

    $pick = function ($sids, $essayPhase, $evalPhase, $limit, $tag) use ($ds, $indicatorId) {
        $cand = [];
        foreach ($sids as $sid) {
            $essay = $ds['essays'][$sid][$essayPhase] ?? null;
            if (!$essay || !$essay['has']) continue;
            $sc = ch45_scores_of($ds, $sid, $evalPhase);
            $cand[] = [
                'sid'   => $sid,
                'no'    => $ds['students'][$sid]['no'],
                'raw'   => $sc ? $sc['raw'][$indicatorId] : null,
                'tag'   => $tag,
                'intro' => ch45_trim_text($essay['intro'], 700),
                'body'  => array_map(function ($p) { return ch45_trim_text($p, 700); }, $essay['body']),
                'conclusion' => ch45_trim_text($essay['conclusion'], 700),
                'text'  => ch45_trim_text($essay['text'], 2200),
                'words' => $essay['word_count'],
            ];
        }
        usort($cand, function ($a, $b) {
            $x = ($a['raw'] === null) ? 99 : $a['raw'];
            $y = ($b['raw'] === null) ? 99 : $b['raw'];
            if ($x == $y) return 0;
            return ($x < $y) ? -1 : 1;   // คะแนนต่ำสุดขึ้นก่อน = ข้อบกพร่องเด่นชัดที่สุด
        });
        return array_slice($cand, 0, $limit);
    };

    // ครั้งที่ 1: ผลงานที่ปรากฏข้อบกพร่อง (ถ้าไม่มีเลย ใช้ผลงานที่คะแนนต่ำสุดแทน)
    $src1 = $row['students1'] ?: $ds['sids'];
    $w1 = $pick($src1, $p1, $e1, $perSlot, $row['students1'] ? 'มีข้อบกพร่องในครั้งที่ 1' : 'คะแนนต่ำสุดในครั้งที่ 1');

    // ครั้งที่ 2: ให้น้ำหนักนักเรียนที่แก้ข้อบกพร่องได้แล้ว แล้วค่อยเติมด้วยคนอื่น
    $resolved = $row['resolved'];
    $w2 = [];
    if ($resolved) $w2 = $pick($resolved, $p2, $e2, $perSlot, 'ครั้งที่ 1 มีข้อบกพร่อง ครั้งที่ 2 แก้ได้แล้ว');
    if (count($w2) < $perSlot) {
        $rest = array_values(array_diff($ds['sids'], array_column($w2, 'sid')));
        // ครั้งที่ 2 ต้องการผลงานที่ "ทำได้ดี" จึงเรียงจากคะแนนสูงลงมา
        $more = $pick($rest, $p2, $e2, count($rest), 'ผลงานครั้งที่ 2');
        $more = array_reverse($more);
        foreach ($more as $m) {
            if (count($w2) >= $perSlot) break;
            $w2[] = $m;
        }
    }

    // จับคู่นักเรียนคนเดียวกันไว้ก่อน ถ้ามีผลงานทั้งสองครั้งและเคยมีข้อบกพร่องแล้วแก้ได้
    $sameStudent = null;
    foreach ($resolved as $sid) {
        if (!empty($ds['essays'][$sid][$p1]['has']) && !empty($ds['essays'][$sid][$p2]['has'])) {
            $sameStudent = $ds['students'][$sid]['no'];
            break;
        }
    }

    return [
        'indicator'    => $indicatorId,
        'work1'        => $w1,
        'work2'        => $w2,
        'same_student' => $sameStudent,
        'topic1'       => (string)($ds['topics'][essay_topic_phase($p1)] ?? ''),
        'topic2'       => (string)($ds['topics'][essay_topic_phase($p2)] ?? ''),
    ];
}

/* =========================================================================
 * ส่วนที่ 8  บันทึกหลังสอนของผู้วิจัย (ใช้เขียนข้อเสนอแนะในบทที่ 5)
 * ========================================================================= */

/** ขั้นของวงจรการจัดการเรียนรู้ตามแนวคิด POA */
function ch45_poa_stages() {
    return [
        'motivating' => 'ขั้นกระตุ้นความสนใจและกำหนดเป้าหมาย (Motivating)',
        'enabling'   => 'ขั้นส่งเสริมการเรียนรู้และผลิตผลงาน (Enabling)',
        'assessing'  => 'ขั้นประเมินและปรับปรุงงาน (Assessing)',
        'general'    => 'ภาพรวมของการจัดการเรียนการสอน',
    ];
}

/** อ่านบันทึกหลังสอนทั้งหมด เรียงตามหน่วยและขั้นของ POA */
function ch45_teaching_logs(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM ch45_teaching_logs ORDER BY task_unit ASC, id ASC');
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/** บันทึก/แก้ไขบันทึกหลังสอนหนึ่งรายการ */
function ch45_save_teaching_log(PDO $pdo, array $in, $by = '') {
    $stages = ch45_poa_stages();
    $stage = (string)($in['poa_stage'] ?? 'general');
    if (!isset($stages[$stage])) $stage = 'general';
    $unit    = (int)($in['task_unit'] ?? 0);
    $problem = trim((string)($in['problem'] ?? ''));
    $solution= trim((string)($in['solution'] ?? ''));
    $evidence= trim((string)($in['evidence'] ?? ''));
    $id      = (int)($in['id'] ?? 0);

    if ($problem === '') return ['ok' => false, 'error' => 'กรุณาระบุปัญหาที่พบระหว่างการจัดการเรียนการสอน'];

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE ch45_teaching_logs SET poa_stage = ?, task_unit = ?, problem = ?,
                               solution = ?, evidence = ? WHERE id = ?');
        $stmt->execute([$stage, $unit, $problem, $solution, $evidence, $id]);
        return ['ok' => true, 'id' => $id];
    }
    $stmt = $pdo->prepare('INSERT INTO ch45_teaching_logs (poa_stage, task_unit, problem, solution, evidence, created_by)
                           VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$stage, $unit, $problem, $solution, $evidence, $by]);
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}

/** ลบบันทึกหลังสอนหนึ่งรายการ */
function ch45_delete_teaching_log(PDO $pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM ch45_teaching_logs WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->rowCount() > 0;
}

/* =========================================================================
 * ส่วนที่ 9  ตรวจความพร้อมของข้อมูล — บอกว่ายังขาดอะไรก่อนเขียนบทที่ 4-5
 * ========================================================================= */

/**
 * ตรวจว่าข้อมูลในระบบพอจะเขียนบทที่ 4-5 ให้ครบทุกช่องหรือยัง
 * คืนรายการตรวจสอบพร้อมสถานะ ok / warn / missing และคำแนะนำว่าต้องทำอะไรต่อ
 */
function ch45_readiness(array $ds, array $quant, array $defects, array $mech) {
    $meta = $ds['meta'];
    $need = (int)($meta['sample_n'] ?: 0);
    $items = [];

    $add = function (&$items, $key, $label, $status, $detail, $fix = '') {
        $items[] = ['key' => $key, 'label' => $label, 'status' => $status,
                    'detail' => $detail, 'fix' => $fix];
    };

    // 1) จำนวนนักเรียนในขอบเขต
    $nStu = count($ds['sids']);
    $add($items, 'students', 'รายชื่อนักเรียนกลุ่มตัวอย่าง',
        ($need > 0 && $nStu < $need) ? 'warn' : ($nStu > 0 ? 'ok' : 'missing'),
        'พบนักเรียนในขอบเขตการวิเคราะห์ ' . $nStu . ' คน' . ($need ? ' (ระบุไว้ว่า ' . $need . ' คน)' : ''),
        'ปรับตัวกรองกลุ่ม/ห้องเรียนด้านบน หรือแก้ข้อมูลนักเรียนในหน้า "นักเรียน & จับคู่"');

    // 2) คะแนนก่อน–หลังเรียนครบคู่
    $add($items, 'prepost', 'คะแนนก่อนเรียนและหลังเรียนครบคู่ (ตาราง 12)',
        $quant['n'] >= 3 ? ($need && $quant['n'] < $need ? 'warn' : 'ok') : 'missing',
        'มีนักเรียนที่มีคะแนนครบทั้งก่อนและหลังเรียน ' . $quant['n'] . ' คน',
        'ให้คะแนนรอบ "ก่อนเรียน" และ "หลังเรียน" ให้ครบทั้ง 11 ข้อ ในหน้าประเมินให้คะแนน');

    // 3) การแจกแจงปกติ
    $norm = $quant['normality']['overall'] ?? null;
    if ($norm && $norm['W'] !== null) {
        $add($items, 'normality', 'การแจกแจงของคะแนนผลต่าง (Shapiro-Wilk)',
            $norm['normal'] ? 'ok' : 'warn',
            'W = ' . ch45_fmt_r($norm['W']) . ', p = ' . ch45_fmt_p($norm['p'])
              . ($norm['normal'] ? ' — ไม่แตกต่างจากการแจกแจงปกติ ใช้ t-test แบบจับคู่ได้'
                                 : ' — แตกต่างจากการแจกแจงปกติ ควรรายงานผลด้วยความระมัดระวัง หรือเพิ่มสถิติไร้พารามิเตอร์ (Wilcoxon)'),
            '');
    } else {
        $add($items, 'normality', 'การแจกแจงของคะแนนผลต่าง (Shapiro-Wilk)', 'missing',
            'ยังคำนวณไม่ได้ เพราะข้อมูลคู่ก่อน–หลังยังไม่พอ', 'ให้คะแนนก่อนเรียน/หลังเรียนเพิ่ม');
    }

    // 4) ความเที่ยงระหว่างผู้ประเมิน
    $ir = $quant['interrater'];
    $add($items, 'interrater', 'ความเที่ยงระหว่างผู้ประเมิน (ต้องรายงานในตาราง 12)',
        $ir ? 'ok' : 'missing',
        $ir ? ('คำนวณได้ ' . count($ir) . ' รอบ')
            : 'ยังไม่มีรอบใดที่มีผู้ประเมินตั้งแต่ 2 คนขึ้นไปให้คะแนนผลงานชุดเดียวกัน',
        'ให้ผู้ประเมินคนที่ 2 (ผู้เชี่ยวชาญ) เข้าให้คะแนนผลงานชุดเดียวกันในหน้าประเมินผลงาน');

    // 5) คะแนนผลงานระหว่างเรียน 2 ครั้ง
    $add($items, 'defects', 'คะแนนผลงานระหว่างเรียน 2 ครั้ง (ตาราง 14)',
        $defects['n'] >= 3 ? ($need && $defects['n'] < $need ? 'warn' : 'ok') : 'missing',
        'มีนักเรียนที่มีคะแนนครบทั้ง 2 ครั้ง ' . $defects['n'] . ' คน'
          . ' (ใช้รอบ ' . $defects['work1_phase'] . ' และ ' . $defects['work2_phase'] . ')',
        'ให้คะแนนรอบภาระงานหน่วยที่ 1 และหน่วยที่ 2 ให้ครบทุกคน');

    // 5.1) รอบงานทั้งสองต้องไม่ใช่รอบเดียวกัน มิฉะนั้นตาราง 14 จะเทียบข้อมูลชุดเดียวกับตัวเอง
    if ($meta['work1_phase'] === $meta['work2_phase']) {
        $add($items, 'work_phases', 'รอบงานที่ใช้เป็นผลงานครั้งที่ 1 และครั้งที่ 2', 'missing',
            'ตั้งไว้เป็นรอบเดียวกัน (' . ai_phase_label($meta['work1_phase']) . ') จึงเปรียบเทียบการเปลี่ยนแปลงไม่ได้',
            'เลือกรอบงานให้ต่างกันในกล่อง "ข้อมูลประจำงานวิจัย"');
    } else {
        $add($items, 'work_phases', 'รอบงานที่ใช้เป็นผลงานครั้งที่ 1 และครั้งที่ 2', 'ok',
            ai_phase_label($meta['work1_phase']) . ' → ' . ai_phase_label($meta['work2_phase']), '');
    }

    // 6) ตัวบทเรียงความจริง (ใช้ยกตัวอย่าง)
    $c1 = 0; $c2 = 0;
    foreach ($ds['sids'] as $sid) {
        if (!empty($ds['essays'][$sid][$meta['work1_phase']]['has'])) $c1++;
        if (!empty($ds['essays'][$sid][$meta['work2_phase']]['has'])) $c2++;
    }
    $add($items, 'essays', 'ตัวบทเรียงความจริง (ใช้ยกตัวอย่าง (1)-(22))',
        ($c1 >= 3 && $c2 >= 3) ? (($need && ($c1 < $need || $c2 < $need)) ? 'warn' : 'ok') : 'missing',
        'ครั้งที่ 1 มี ' . $c1 . ' ฉบับ · ครั้งที่ 2 มี ' . $c2 . ' ฉบับ',
        'ให้นักเรียนบันทึกเรียงความในระบบ หรือครูพิมพ์แทนได้ที่หน้า "เรียงความนักเรียน"');

    // 7) หัวข้อเรียงความที่ครูกำหนด
    $t1 = trim((string)($ds['topics'][essay_topic_phase($meta['work1_phase'])] ?? ''));
    $t2 = trim((string)($ds['topics'][essay_topic_phase($meta['work2_phase'])] ?? ''));
    $add($items, 'topics', 'หัวข้อเรียงความที่ครูกำหนดแต่ละรอบ',
        ($t1 !== '' && $t2 !== '') ? 'ok' : 'missing',
        'ครั้งที่ 1: ' . ($t1 !== '' ? $t1 : '(ยังไม่ระบุ)') . ' · ครั้งที่ 2: ' . ($t2 !== '' ? $t2 : '(ยังไม่ระบุ)'),
        'กำหนดหัวข้อของแต่ละรอบในหน้า "เรียงความนักเรียน" — บทที่ 4 ต้องอ้างชื่อหัวข้อจริง');

    // 8) พจนานุกรมสำหรับนับคำสะกดผิด
    $add($items, 'spelling', 'ข้อมูลการสะกดคำ (หัวข้อ 2.4.1)',
        !empty($mech['work1']['dict_ok']) ? 'ok' : 'warn',
        !empty($mech['work1']['dict_ok'])
            ? ('เฉลี่ยครั้งที่ 1 = ' . ch45_fmt($mech['work1']['spell_mean']) . ' แห่ง/ชิ้น · ครั้งที่ 2 = '
               . ch45_fmt($mech['work2']['spell_mean']) . ' แห่ง/ชิ้น')
            : 'ไม่พบพจนานุกรมภาษาไทยในระบบ จึงนับคำสะกดผิดอัตโนมัติไม่ได้',
        'ตัวเลขนี้เป็นค่าประมาณจากพจนานุกรม ผู้วิจัยควรสุ่มตรวจยืนยันก่อนนำไปเขียน');

    // 9) ข้อมูลประจำงานวิจัย
    $missMeta = [];
    foreach (['academic_year' => 'ปีการศึกษา', 'classroom' => 'ห้องที่เป็นตัวอย่าง',
              'population_n' => 'จำนวนประชากร'] as $k => $lab) {
        if (trim((string)$meta[$k]) === '') $missMeta[] = $lab;
    }
    $add($items, 'meta', 'ข้อมูลประจำงานวิจัย (ใช้ในย่อหน้าเปิดบทที่ 5)',
        $missMeta ? 'missing' : 'ok',
        $missMeta ? ('ยังไม่ได้กรอก: ' . implode(', ', $missMeta)) : 'กรอกครบแล้ว',
        'กรอกในกล่อง "ข้อมูลประจำงานวิจัย" ด้านล่างของหน้านี้');

    // 10) บันทึกหลังสอน
    $nLogs = count($ds['logs']);
    $add($items, 'logs', 'บันทึกหลังสอน (ใช้เขียนข้อเสนอแนะในบทที่ 5)',
        $nLogs >= 3 ? 'ok' : ($nLogs > 0 ? 'warn' : 'missing'),
        'บันทึกไว้แล้ว ' . $nLogs . ' รายการ',
        'โครงบทที่ 5 กำหนดให้ข้อเสนอแนะต้องเขียนจากปัญหาที่พบจริง ควรบันทึกอย่างน้อยขั้นละ 1 รายการ');

    $counts = ['ok' => 0, 'warn' => 0, 'missing' => 0];
    foreach ($items as $it) $counts[$it['status']]++;
    return ['items' => $items, 'counts' => $counts,
            'ready' => ($counts['missing'] === 0),
            'score' => count($items) ? round($counts['ok'] * 100 / count($items)) : 0];
}
