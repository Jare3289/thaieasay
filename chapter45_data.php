<?php
/**
 * chapter45_data.php — ชั้นรวบรวมข้อมูลและคำนวณสถิติสำหรับ "บทที่ 4 และบทที่ 5"
 * ---------------------------------------------------------------------------
 * ไฟล์นี้ทำหน้าที่แปลงข้อมูลดิบในระบบ ให้กลายเป็น "ตัวเลขและหลักฐานทุกตัว"
 * ที่โครงบทที่ 4-5 ของวิทยานิพนธ์เว้นช่องไว้ให้เติม กล่าวคือ
 *
 *   ตาราง 12  ผลเปรียบเทียบก่อน–หลังเรียน (M, SD, t, p, ขนาดอิทธิพล) ภาพรวม + 4 ด้าน
 *             พร้อมผลทดสอบการแจกแจงปกติ (Shapiro-Wilk) และความเที่ยงระหว่างผู้ประเมิน
 *   ตาราง 13  สรุปภาพรวมการเปลี่ยนแปลง 4 ด้าน ระหว่างผลงานครั้งที่ 1 กับครั้งที่ 2 (ให้ระบบเขียน)
 *   ตาราง 14  จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง 11 ตัวบ่งชี้ ในผลงาน 2 ครั้ง
 *   ตาราง 15-18  การเปลี่ยนแปลงรายตัวบ่งชี้ แยกตามองค์ประกอบ (ให้ระบบเขียน)
 *   ตัวอย่าง (1)-(N)  ข้อความจริงจากผลงานนักเรียน 1-3 คู่ต่อ 1 ตัวบ่งชี้ (ระบบคัดจากคลังที่นี่
 *                      หมายเลขกำกับคำนวณใหม่ทุกครั้งตามจำนวนคู่ที่มีอยู่จริง ดู ch45_assign_example_numbers())
 *   บทที่ 5   ตัวเลขทุกตัวในส่วนสรุปผล + ปัญหาจากบันทึกหลังสอนสำหรับเขียนข้อเสนอแนะ
 *
 * ไฟล์นี้ "อ่านอย่างเดียว" ไม่แก้ไขข้อมูลวิจัยใด ๆ
 * นิยามเชิงปฏิบัติการทุกข้อ (เช่น "ปรากฏข้อบกพร่อง" หมายถึงอะไร) รวมไว้ที่ ch45_meta()
 * เพื่อให้ผู้วิจัยเขียนอธิบายวิธีนับในบทที่ 3 ได้ตรงกับที่ระบบคำนวณจริง
 */

require_once 'writing_check_config.php';
require_once 'chapter45_stats.php';
require_once 'thai_text_utils.php';

/**
 * ชื่อ "กลุ่มการวิจัย" (student_group) ที่มีการตรวจซ้ำโดยครู + ผู้เชี่ยวชาญ 2 ท่าน
 * เพื่อคำนวณความเที่ยงระหว่างผู้ประเมิน (ICC) — ต้องดึงข้อมูล ICC จากกลุ่มนี้เสมอ
 * ไม่ว่าผู้ใช้จะเลือกดูกลุ่มไหนอยู่บนตัวกรองบน topbar (ค่าเริ่มต้นของตัวกรองคือ "กลุ่มตัวอย่าง"
 * ซึ่งเป็นคนละกลุ่มกัน) เพราะข้อมูลตรวจซ้ำมีอยู่แค่กลุ่มทดลองกลุ่มเดียวในระบบ
 */
define('CH45_ICC_GROUP', 'กลุ่มทดลอง');

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
 *   ai_scored  = false คือข้อที่ระบบตรวจจากไฟล์พิมพ์แทนไม่ได้ (ความเรียบร้อย/ลายมือ)
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

/**
 * ระดับคุณภาพเชิงคุณภาพของคะแนนดิบ 0-4 — ใช้ระดับเดียวกับที่ตัวบ่งชี้ 4.3 (ความเรียบร้อย) ใช้อยู่แล้ว
 * เพื่อให้ทุกตัวบ่งชี้ตีความคะแนนดิบในมาตรฐานเดียวกัน แม้เนื้อหาของแต่ละระดับจะบรรยายไว้ต่างกันไปตาม
 * เกณฑ์ของแต่ละตัวบ่งชี้เอง (ดู guide ใน ai_rubric()) — ใช้แปลงเลขให้เป็นคำที่เข้าใจง่ายในหน้าตั้งค่า
 * แทนการให้ผู้ใช้กรอกตัวเลขดิบซึ่งดูเหมือนไม่มีมาตรฐานเดียวกันระหว่างตัวบ่งชี้
 */
function ch45_score_levels() {
    return [4 => 'ดีมาก', 3 => 'ดี', 2 => 'ปานกลาง', 1 => 'พอใช้', 0 => 'ปรับปรุง'];
}

/**
 * แปลงคะแนนดิบ 0-4 เป็นชื่อระดับโดยไม่ปัดค่าเฉลี่ยจากผู้ประเมินหลายคนให้กลายเป็นระดับเดียว
 * เช่น 2.5 รายงานว่าอยู่ระหว่างระดับปานกลางและดี ซึ่งตรงกับคะแนนจริงมากกว่า
 * การปัดเป็นระดับดีหรือปานกลางเพียงระดับเดียว
 */
function ch45_score_level_label($raw) {
    if ($raw === null || !is_numeric($raw)) return '—';
    $v = max(0.0, min(4.0, (float)$raw));
    $levels = ch45_score_levels();
    $nearest = (int)round($v);
    if (abs($v - $nearest) < 0.00001) return $levels[$nearest];
    $lo = (int)floor($v);
    $hi = (int)ceil($v);
    return 'ระหว่างระดับ' . $levels[$lo] . 'และ' . $levels[$hi];
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
        'defect_cut'    => ['label' => 'เกณฑ์นับว่า "ปรากฏข้อบกพร่อง" — คะแนนดิบอยู่ระดับใดลงมา (ตั้งแยกแต่ละตัวบ่งชี้ได้)',
                             'default' => json_encode(array_fill_keys(array_keys(ch45_indicators()), 2)),
                             'type' => 'level_per_indicator',
                             'hint' => 'ตั้งเกณฑ์แยกแต่ละตัวบ่งชี้ได้ เพราะความหมายของแต่ละระดับในแต่ละตัวบ่งชี้ไม่เหมือนกัน '
                                     . '(ดูเกณฑ์การให้คะแนนของแต่ละตัวบ่งชี้ที่หน้าตั้งค่าระบบตรวจ) '
                                     . 'เลือก "ปานกลาง" (ค่าเริ่มต้นของทุกตัวบ่งชี้) หมายถึง นักเรียนที่ได้คะแนนดิบระดับปานกลางหรือต่ำกว่า '
                                     . '(ปานกลาง/พอใช้/ปรับปรุง) จะถูกนับว่า "ปรากฏข้อบกพร่อง" ด้านนั้น '
                                     . '— เลือกระดับสูงขึ้นจะยิ่งนับว่ามีข้อบกพร่องง่ายขึ้น (เกณฑ์หลวมขึ้น)'],
        'good_example_min' => ['label' => 'เกณฑ์ตัวอย่างผลงาน "ที่ทำได้ดี" สำหรับยกเปรียบเทียบ — ต้องอยู่ระดับใดขึ้นไป',
                             'default' => '2', 'type' => 'level',
                             'hint' => 'ใช้ตอนเลือกตัวอย่างผลงานครั้งที่ 2 มาเปรียบเทียบในบทที่ 4 กรณีไม่มีนักเรียนที่แก้ข้อบกพร่อง '
                                     . 'ได้พอดี เพื่อไม่ให้หยิบผลงานที่จริง ๆ ยังทำได้ไม่ดีมายกเป็นตัวอย่างว่า "ทำได้ดีแล้ว" '
                                     . 'เลือก "ปานกลาง" (ค่าเริ่มต้น) หมายถึง ต้องได้คะแนนดิบระดับปานกลางขึ้นไป (ปานกลาง/ดี/ดีมาก) '
                                     . 'จึงจะถูกหยิบมาเป็นตัวอย่างเปรียบเทียบได้'],
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

    // defect_cut เก็บเป็น JSON string {รหัสตัวบ่งชี้: ระดับ} เพื่อตั้งแยกแต่ละตัวบ่งชี้ได้
    // (ค่าเก่าก่อนมีฟีเจอร์นี้เป็นเลขเดี่ยว เช่น "2" — json_decode ได้ตัวเลขธรรมดา ไม่ใช่ array
    // จึงถือเป็นค่าเดียวที่เคยตั้งไว้ แล้วใช้ค่านั้นเป็นค่าเริ่มต้นของทุกตัวบ่งชี้แทน เพื่อไม่ให้เกณฑ์เปลี่ยนไปจากเดิมโดยไม่ได้ตั้งใจ)
    $decoded = json_decode((string)$out['defect_cut'], true);
    $uniform = is_numeric($decoded) ? (int)$decoded : null;
    $rawMap  = is_array($decoded) ? $decoded : [];
    $cutMap  = [];
    foreach (ch45_indicators() as $id => $ind) {
        $v = array_key_exists($id, $rawMap) ? (int)$rawMap[$id] : ($uniform !== null ? $uniform : 2);
        $cutMap[$id] = ($v >= 0 && $v <= 3) ? $v : 2;
    }
    $out['defect_cut'] = $cutMap;

    $gem = (int)($out['good_example_min'] ?? 2);
    $out['good_example_min'] = ($gem >= 0 && $gem <= 4) ? $gem : 2;

    // นิยามเชิงปฏิบัติการที่ใช้จริงในการคำนวณ — นำไปเขียนอธิบายวิธีนับได้ตรง ๆ
    $srcLabel = ['mean' => 'คะแนนเฉลี่ยจากผู้ประเมินทุกคนที่ตรวจผลงานชิ้นนั้น',
                 'teacher' => 'คะแนนของครูผู้สอน', 'expert' => 'คะแนนของผู้เชี่ยวชาญ'];
    if (!isset($srcLabel[$out['score_source']])) $out['score_source'] = 'mean';
    $out['score_source_label'] = $srcLabel[$out['score_source']];
    $levels = ch45_score_levels();
    $uniqueCuts = array_unique(array_values($cutMap));
    if (count($uniqueCuts) === 1) {
        $lv = reset($uniqueCuts);
        $out['defect_rule'] = 'นับว่า "ปรากฏข้อบกพร่อง" ในตัวบ่งชี้หนึ่ง เมื่อผลงานชิ้นนั้นได้คะแนนดิบของตัวบ่งชี้'
            . 'อยู่ระดับ' . ($levels[$lv] ?? $lv) . 'หรือต่ำกว่า (คะแนนดิบไม่เกิน ' . $lv . ' จากเต็ม 4) '
            . 'ตามเกณฑ์ประเมินแบบแยกองค์ประกอบ (ใช้' . $out['score_source_label'] . ')';
    } else {
        $parts = [];
        foreach (ch45_indicators() as $id => $ind) {
            $lv = $cutMap[$id];
            $parts[] = $ind['name'] . ' = ' . ($levels[$lv] ?? $lv) . 'หรือต่ำกว่า';
        }
        $out['defect_rule'] = 'นับว่า "ปรากฏข้อบกพร่อง" เมื่อผลงานได้คะแนนดิบของตัวบ่งชี้นั้นอยู่ในระดับที่ตั้งไว้เฉพาะตัวบ่งชี้นั้นหรือต่ำกว่า '
            . '(ใช้' . $out['score_source_label'] . ') ได้แก่ ' . implode(', ', $parts);
    }
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
 * ไม่เรียก ai_save_setting() เพราะฟังก์ชันนั้นมีรายการคีย์ที่อนุญาตเฉพาะของระบบตรวจอัตโนมัติ (ai_*)
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
                'essays' => [], 'topics' => [], 'ai' => [], 'reflect' => [], 'logs' => [],
                'references' => ch45_references($pdo)];
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
            // ต้นฉบับตามที่นักเรียนพิมพ์จริง — เก็บไว้เสมอ ใช้กับตัวชี้วัดที่ต้องนับ "ความผิดพลาดของนักเรียนเอง"
            // เช่น การเว้นวรรคไม้ยมก ซึ่งถ้าไปนับจากฉบับจัดวรรคแล้วจะได้ศูนย์เสมอ (ระบบจัดให้ถูกไปแล้ว)
            'raw_intro'      => $intro,
            'raw_body'       => $body,
            'raw_conclusion' => $concl,
            'raw_text'       => $full,
            'normalized' => false,
            'space_edits'=> 0,
            'word_count' => (int)($row['word_count'] ?? 0),
            'updated_at' => (string)($row['updated_at'] ?? ''),
            'has'        => ($full !== ''),
        ];
    }

    // ---- 3.1) ฉบับ "จัดเว้นวรรค/แบ่งประโยคแล้ว" ----
    // ภาษาไทยเขียนติดกัน การเว้นวรรคจึงเป็นตัวบอกขอบเขตของความ งานเขียนที่เว้นวรรคผิดที่
    // ทำให้ตัวตัดคำอัตโนมัติแบ่งคำผิดและทำให้ระบบที่อ่านตัวบทตีความใจความคลาดเคลื่อน
    // บทที่ 4-5 จึงวิเคราะห์จากฉบับจัดวรรคแล้วเมื่อมี (ถ้อยคำทุกตัวเหมือนต้นฉบับเป๊ะ ต่างกันแค่ช่องว่าง)
    // ฉบับที่นักเรียนแก้ต้นฉบับหลังจัดวรรค (ลายนิ้วมือไม่ตรง) ถือว่าใช้ไม่ได้ ให้กลับไปใช้ต้นฉบับ
    $normMap = [];
    try { $normMap = ai_norm_map($pdo, $sids); } catch (Exception $e) { $normMap = []; }
    foreach ($normMap as $nSid => $byPhase) {
        foreach ($byPhase as $nPhase => $n) {
            if (!isset($essays[$nSid][$nPhase]) || !$essays[$nSid][$nPhase]['has']) continue;
            $cur = $essays[$nSid][$nPhase];
            $srcHash = ai_essay_hash($cur['raw_intro'], $cur['raw_body'], $cur['raw_conclusion']);
            if ($n['hash'] === '' || $n['hash'] !== $srcHash) continue;   // ต้นฉบับเปลี่ยนไปแล้ว ใช้ไม่ได้
            if (trim($n['text']) === '') continue;
            $essays[$nSid][$nPhase]['intro']       = $n['intro'];
            $essays[$nSid][$nPhase]['body']        = $n['body'];
            $essays[$nSid][$nPhase]['conclusion']  = $n['conclusion'];
            $essays[$nSid][$nPhase]['text']        = $n['text'];
            $essays[$nSid][$nPhase]['normalized']  = true;
            $essays[$nSid][$nPhase]['space_edits'] = (int)$n['space_edits'];
        }
    }

    // ---- 4) หัวข้อเรียงความที่ครูกำหนดแต่ละรอบ ----
    $topics = [];
    try { $topics = essay_topics_map($pdo); } catch (Exception $e) { $topics = []; }

    // ---- 5) ผลตรวจของระบบรายฉบับ (ใช้เป็นข้อมูลประกอบเชิงคุณภาพ) ----
    $ai = [];
    try {
        $aiOvCol = ai_feedback_has_override_column($pdo);
        $stmt = $pdo->prepare('SELECT student_id, essay_phase, overall_comment, strengths, improvements, '
            . 'scores, ' . ($aiOvCol ? 'score_overrides, ' : '')
            . 'total_score, max_score, quality_level FROM essay_ai_feedback WHERE student_id IN (' . $ph . ')');
        $stmt->execute($sids);
        foreach ($stmt->fetchAll() as $row) {
            // ผลตรวจที่ระบบให้คะแนนไม่ครบ = ตรวจไม่ผ่าน ถือว่ายังไม่ได้ตรวจ ไม่นำมาใช้ในบทที่ 4-5
            $aiScores = json_decode((string)($row['scores'] ?? ''), true);
            if (!is_array($aiScores) || !$aiScores) continue;
            // คะแนนที่ผ่านการตรวจทานของครูแล้วคือคะแนนที่ใช้จริงในบทที่ 4-5
            // (ครูปรับเอง หรือสั่งให้ระบบตรวจข้อนั้นใหม่) ส่วนคะแนนดั้งเดิมของระบบแนบไว้ใน ai_scores
            $aiEff = ai_apply_score_overrides([
                'overall'       => (string)($row['overall_comment'] ?? ''),
                'strengths'     => json_decode((string)($row['strengths'] ?? ''), true) ?: [],
                'improvements'  => json_decode((string)($row['improvements'] ?? ''), true) ?: [],
                'scores'        => $aiScores,
                'total_score'   => (float)($row['total_score'] ?? 0),
                'max_score'     => (float)($row['max_score'] ?? 0),
                'quality_level' => (string)($row['quality_level'] ?? ''),
            ], $aiOvCol ? ($row['score_overrides'] ?? '') : '');
            $ai[(string)$row['student_id']][(string)$row['essay_phase']] = [
                'overall'      => $aiEff['overall'],
                'strengths'    => $aiEff['strengths'],
                'improvements' => $aiEff['improvements'],
                'scores'       => $aiEff['scores'],
                'total'        => $aiEff['total_score'],
                'max'          => $aiEff['max_score'],
                'level'        => $aiEff['quality_level'],
                // คะแนนดั้งเดิมของระบบก่อนครูตรวจทาน — ใช้รายงานว่าครูปรับไปกี่ข้อ ต่างกันเท่าไร
                'ai_scores'      => $aiEff['ai_scores'],
                'ai_total'       => $aiEff['ai_total_score'],
                'override_count' => $aiEff['override_count'],
            ];
        }
    } catch (Exception $e) { /* ยังไม่มีตารางผลตรวจ ก็วิเคราะห์ส่วนอื่นต่อได้ */ }

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

    // ---- 8) คลังอ้างอิงงานวิจัยที่เกี่ยวข้อง (ใช้จับคู่ตอนเขียนอภิปรายผลในบทที่ 5) ----
    $references = ch45_references($pdo);

    return [
        'meta'       => $meta,
        'students'   => $students,
        'sids'       => $sids,
        'evals'      => $evals,
        'essays'     => $essays,
        'topics'     => $topics,
        'ai'         => $ai,
        'reflect'    => $reflect,
        'logs'       => $logs,
        'references' => $references,
        'filter'     => ['group' => $group, 'classroom' => $classroom],
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
 *                 คำนวณจากกลุ่ม CH45_ICC_GROUP เสมอ (ดูพารามิเตอร์ $iccDs) ไม่ใช่จาก $ds
 *   ranking       ลำดับด้านที่เปลี่ยนแปลงมาก→น้อย ตามร้อยละของคะแนนเต็มและขนาดอิทธิพล
 *   pairs         รายชื่อนักเรียนที่มีคะแนนครบทั้งก่อนและหลังเรียน (ฐานของการทดสอบทีแบบจับคู่)
 *
 * $iccDs = ชุดข้อมูลที่กรองเฉพาะกลุ่มที่มีการตรวจซ้ำ (CH45_ICC_GROUP) ใช้คำนวณความเที่ยงระหว่างผู้ประเมิน
 *          โดยเฉพาะ — แยกจาก $ds เพราะผู้ใช้อาจกำลังดูข้อมูลกลุ่มอื่นอยู่บนหน้าจอ (ค่าเริ่มต้น: ใช้ $ds เดิม
 *          ถ้าไม่ได้ส่งมา เพื่อความเข้ากันได้กับโค้ดเดิม)
 */
function ch45_quant(array $ds, ?array $iccDs = null) {
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

    // ---- 4) ความเที่ยงระหว่างผู้ประเมิน (คำนวณจากกลุ่มทดลองเสมอ ไม่ใช่กลุ่มที่กำลังดูอยู่บนหน้าจอ) ----
    $interrater = ch45_interrater($iccDs ?? $ds);

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
    $meta   = $ds['meta'];
    $cutMap = is_array($meta['defect_cut'] ?? null) ? $meta['defect_cut'] : [];
    $p1     = $meta['work1_eval_phase'];
    $p2     = $meta['work2_eval_phase'];
    $inds   = ch45_indicators();

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
        $cut = (int)($cutMap[$id] ?? 2);
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

    // "ดีขึ้นมากที่สุด/น้อยที่สุด" มีความหมายเฉพาะตัวบ่งชี้ที่เคยมีคนปรากฏข้อบกพร่องจริงในครั้งที่ 1 (n1 > 0)
    // เท่านั้น — ตัวบ่งชี้ที่ไม่มีใครปรากฏข้อบกพร่องเลยทั้งสองครั้ง (0 คนทั้งคู่) ไม่ได้ "ดีขึ้น" หรือ "แย่ลง"
    // แต่เป็น "ไม่เคยเป็นปัญหา" จึงไม่ควรถูกเลือกมาเป็นตัวแทนอันดับดีที่สุด/แย่ที่สุด (เอา 0 ไปเทียบ 0 ไม่มีความหมาย)
    $rankedWithProblem = array_values(array_filter($ranked, function ($r) { return (int)$r['n1'] > 0; }));

    $allDown = true; $anyDown = false;
    foreach ($rows as $r) {
        if ($r['diff_n'] < 0) $anyDown = true;
        if ($r['diff_n'] >= 0) $allDown = false;
    }

    return [
        'n' => $n, 'cut' => $cutMap, 'rule' => $meta['defect_rule'],
        'work1_phase' => $p1, 'work2_phase' => $p2,
        'rows' => $rows,
        'ranked' => $ranked,
        'most_improved'  => $rankedWithProblem ? $rankedWithProblem[0] : null,
        'least_improved' => $rankedWithProblem ? $rankedWithProblem[count($rankedWithProblem) - 1] : null,
        'all_decreased'  => $allDown,
        'any_decreased'  => $anyDown,
        'summary_phrase' => $allDown ? 'ทุกตัวบ่งชี้' : ($anyDown ? 'เกือบทุกตัวบ่งชี้' : 'บางตัวบ่งชี้'),
    ];
}

/**
 * จำนวนนักเรียนที่ได้แต่ละระดับคุณภาพ (0-4) รายตัวบ่งชี้ แยกครั้งที่ 1 และครั้งที่ 2
 *
 * ต่างจาก ch45_defects() ตรงที่นับแยกอิสระของแต่ละครั้ง ไม่บังคับว่าต้องมีคะแนนครบทั้งสองครั้ง
 * (สนใจแค่การกระจายคะแนนของครั้งนั้น ๆ เอง ไม่ได้เปรียบเทียบนักเรียนคนเดียวกันข้ามครั้ง)
 * คะแนนที่มาจากการเฉลี่ยหลายผู้ประเมิน (score_source = mean) อาจเป็นเลขทศนิยม จึงปัดเข้าระดับที่ใกล้ที่สุด
 */
function ch45_level_distribution(array $ds) {
    $meta = $ds['meta'];
    $p1   = $meta['work1_eval_phase'];
    $p2   = $meta['work2_eval_phase'];
    $inds = ch45_indicators();

    $emptyLevels = [4 => 0, 3 => 0, 2 => 0, 1 => 0, 0 => 0];
    $rows = [];
    foreach ($inds as $id => $ind) {
        $rows[$id] = ['id' => $id, 'no' => $ind['no'], 'domain' => $ind['domain'], 'name' => $ind['name'],
                      'levels1' => $emptyLevels, 'n1' => 0, 'levels2' => $emptyLevels, 'n2' => 0];
    }

    foreach ($ds['sids'] as $sid) {
        $a = ch45_scores_of($ds, $sid, $p1);
        $b = ch45_scores_of($ds, $sid, $p2);
        foreach ($inds as $id => $ind) {
            if ($a !== null && $a['raw'][$id] !== null) {
                $lv = max(0, min(4, (int)round($a['raw'][$id])));
                $rows[$id]['levels1'][$lv]++;
                $rows[$id]['n1']++;
            }
            if ($b !== null && $b['raw'][$id] !== null) {
                $lv = max(0, min(4, (int)round($b['raw'][$id])));
                $rows[$id]['levels2'][$lv]++;
                $rows[$id]['n2']++;
            }
        }
    }

    return ['work1_phase' => $p1, 'work2_phase' => $p2, 'rows' => $rows];
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
        $maiyamok = []; $wordFreq = []; $perStudentWords = [];
        $pieces = 0;
        $normPieces = 0; $spaceFix = [];
        foreach ($ds['sids'] as $sid) {
            $e = $ds['essays'][$sid][$phase] ?? null;
            if (!$e || !$e['has']) continue;
            $pieces++;
            $wordCounts[] = $e['word_count'] ?: count_thai_words($e['text']);
            $paraCounts[] = 1 + count($e['body']) + 1;
            if (!empty($e['normalized'])) { $normPieces++; $spaceFix[] = (int)$e['space_edits']; }

            $occ = 0; $types = [];
            if ($dict) {
                // หัวข้อ 2.4.1 ต้องตรวจจากต้นฉบับดิบเดียวกับที่จะยกเป็นหลักฐาน ไม่ใช้ฉบับที่ระบบ
                // จัดเว้นวรรคแล้ว เพื่อให้จำนวนคำผิด รายการคำผิด และข้อความตัวอย่างอ้างถึงฉบับเดียวกัน
                $spellingSource = $e['raw_text'] ?? $e['text'];
                foreach (thai_word_segments($spellingSource) as $seg) {
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
            // เก็บคำที่ตรวจพบว่าสะกดผิดไว้ "รายคน" ด้วย (ไม่ใช่แค่รวมทั้งชั้น) เพื่อให้ ch45_evidence()
            // นำไปกำกับตัวอย่างของตัวบ่งชี้ 4.1 ว่าต้องยกข้อความที่มีคำเหล่านี้จริง แทนที่จะปล่อยให้ระบบ
            // ต้องไปหาคำสะกดผิดเองจากตัวบทดิบ ซึ่งเป็นจุดที่ตัวอย่างมักยกมาไม่ตรงกับข้อบกพร่องจริง
            $perStudentWords[$sid] = array_keys($types);
            try {
                // ต้องนับจาก "ต้นฉบับที่นักเรียนพิมพ์เอง" เท่านั้น ถ้านับจากฉบับจัดวรรคแล้วจะได้ศูนย์เสมอ
                // เพราะระบบจัดเว้นวรรคไม้ยมกให้ถูกไปแล้ว ซึ่งไม่ใช่ความสามารถของนักเรียน
                $maiyamok[] = count(find_maiyamok_spacing_errors($e['raw_text'] ?? $e['text'], $confirmed, 50));
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
            'per_student_misspelled' => $perStudentWords,
            'word_mean'    => ch45_mean($wordCounts),
            'word_sd'      => ch45_sd($wordCounts),
            'para_mean'    => ch45_mean($paraCounts),
            'maiyamok_mean'=> ch45_mean($maiyamok),
            'dict_ok'      => (bool)$dict,
            // จำนวนฉบับที่วิเคราะห์จาก "ฉบับจัดวรรคแล้ว" และจุดเว้นวรรคที่ระบบปรับเฉลี่ยต่อชิ้น
            'norm_pieces'  => $normPieces,
            'space_fix_mean' => ch45_mean($spaceFix),
        ];
    }

    $out['spell_change'] = ($out['work1']['spell_mean'] !== null && $out['work2']['spell_mean'] !== null)
        ? $out['work2']['spell_mean'] - $out['work1']['spell_mean'] : null;
    $normAll = (int)$out['work1']['norm_pieces'] + (int)$out['work2']['norm_pieces'];
    $out['norm_pieces'] = $normAll;
    $out['note'] = 'จำนวนคำที่สะกดผิดนับด้วยพจนานุกรมอัตโนมัติ คำวิสามานยนามและคำเฉพาะบางคำ'
                 . 'อาจถูกนับเกินจริง ผู้วิจัยควรสุ่มตรวจยืนยันก่อนรายงานเป็นตัวเลขในวิทยานิพนธ์'
                 . ($normAll > 0
                    ? ' · การนับคำและการอ่านใจความใช้ "ฉบับจัดเว้นวรรคแล้ว" ' . $normAll . ' ฉบับ '
                      . 'ซึ่งมีถ้อยคำทุกตัวเหมือนต้นฉบับของนักเรียน ต่างกันเฉพาะตำแหน่งการเว้นวรรค '
                      . 'เพื่อให้เครื่องมือตัดคำอัตโนมัติแบ่งคำได้ถูกต้อง ส่วนการนับข้อผิดพลาดเรื่องการเว้นวรรค'
                      . 'ยังนับจากต้นฉบับที่นักเรียนพิมพ์เองเสมอ'
                    : '');
    return $out;
}

/* =========================================================================
 * ส่วนที่ 7  คลังตัวอย่างข้อความจริงจากผลงานนักเรียน (ตัวอย่าง (1)-(22))
 * ========================================================================= */

/** ตัดข้อความให้สั้นลงโดยไม่ตัดกลางคำ (ใช้กันไม่ให้คำสั่งที่ส่งให้ระบบยาวเกินไป) */
function ch45_trim_text($text, $maxChars = 2200, $preserveOriginalSpacing = false) {
    $t = $preserveOriginalSpacing
        ? trim((string)$text)
        : trim(preg_replace('/[ \t]+/u', ' ', (string)$text));
    if (mb_strlen($t, 'UTF-8') <= $maxChars) return $t;
    // ต้นฉบับดิบห้ามเติมแม้แต่เครื่องหมายบอกการตัด เพราะ AI อาจคัดเครื่องหมายนั้นไปเป็นส่วนหนึ่ง
    // ของตัวอย่าง ทั้งที่ไม่ได้ปรากฏอยู่ในงานของนักเรียนจริง
    return mb_substr($t, 0, $maxChars, 'UTF-8') . ($preserveOriginalSpacing ? '' : ' …');
}

/**
 * คัดผลงานที่เหมาะจะยกเป็นตัวอย่างของตัวบ่งชี้หนึ่ง
 *
 * หลักการคัด (เรียงตามลำดับความสำคัญ):
 *   ครั้งที่ 1 — เลือกผลงานที่ได้คะแนนดิบต่ำที่สุดในตัวบ่งชี้นั้น เพราะเป็นผลงานที่
 *               "เห็นข้อบกพร่องชัดที่สุด" ตรงกับที่บทที่ 4 ต้องยกเป็นตัวอย่างข้อบกพร่อง
 *   ครั้งที่ 2 — ให้ความสำคัญกับนักเรียนคนเดิมที่เคยมีข้อบกพร่องแล้วแก้ได้ (resolved)
 *               เพื่อให้คู่ตัวอย่างแสดง "การเปลี่ยนแปลงของคนเดียวกัน" ซึ่งหนักแน่นกว่าการเทียบคนละคน
 *   ความหลากหลายของตัวอย่างตลอด 11 หัวข้อ — ในกลุ่มที่คะแนนเสมอกัน ให้นักเรียนที่ยังไม่เคย
 *               ถูกยกเป็นตัวอย่างในหัวข้ออื่นมาก่อน (ตามที่ระบุใน $usedW1/$usedW2) ขึ้นก่อนนักเรียน
 *               ที่ถูกยกไปแล้ว เพื่อไม่ให้บทที่ 4 ยกตัวอย่างคนเดิมซ้ำทุกหัวข้อ แต่ยังคง
 *               ยึดคะแนน/สถานะ resolved เป็นเกณฑ์หลักเสมอ — ถ้าไม่มีคนอื่นให้เลือกจริง ๆ
 *               ก็ยังใช้คนเดิมได้ ไม่ตัดออกทั้งหมด
 *
 * ส่งข้อความเรียงความจริงไปด้วย เพื่อให้ระบบยกข้อความจากผลงานจริงเท่านั้น ห้ามแต่งเอง
 */
function ch45_evidence(array $ds, $indicatorId, array $defects, $perSlot = 3, array $usedW1 = [], array $usedW2 = []) {
    $meta = $ds['meta'];
    $row  = $defects['rows'][$indicatorId] ?? null;
    if (!$row) return ['work1' => [], 'work2' => []];

    // ไม่มีนักเรียนคนใดปรากฏข้อบกพร่องนี้เลยทั้งสองครั้ง — ไม่มีอะไรจริงให้ยกเป็นตัวอย่างเปรียบเทียบ
    // (เดิมเคยหยิบ "คะแนนต่ำสุดของทั้งชั้น" มาแทนเมื่อไม่มีใครเข้าเกณฑ์ ทำให้ได้คู่เปรียบเทียบปลอมที่ไม่มี
    // ข้อบกพร่องจริงทั้งสองฝั่ง อ่านแล้วเหมือนเปรียบเทียบ "ศูนย์คนกับศูนย์คน" ซึ่งไม่มีอะไรให้เขียนจริง ๆ)
    if (!$row['students1'] && !$row['students2']) {
        return ['indicator' => $indicatorId, 'work1' => [], 'work2' => [], 'same_student' => null];
    }

    $p1 = $meta['work1_phase']; $p2 = $meta['work2_phase'];
    $e1 = $meta['work1_eval_phase']; $e2 = $meta['work2_eval_phase'];

    $pick = function ($sids, $essayPhase, $evalPhase, $limit, $tag, $usedNos, $order = 'asc', $minRaw = null) use ($ds, $indicatorId) {
        $cand = [];
        foreach ($sids as $sid) {
            $essay = $ds['essays'][$sid][$essayPhase] ?? null;
            if (!$essay || !$essay['has']) continue;
            $sc = ch45_scores_of($ds, $sid, $evalPhase);
            // ใช้เป็นตัวกรองเฉพาะตอนหยิบ "ตัวอย่างผลงานที่ทำได้ดี" มาเปรียบเทียบ (ดู good_example_min)
            // เพื่อไม่ให้หยิบผลงานที่จริง ๆ ยังทำได้ไม่ดีมาโชว์ว่าเป็นตัวอย่างที่ดี
            if ($minRaw !== null) {
                $r = $sc ? ($sc['raw'][$indicatorId] ?? null) : null;
                if ($r === null || $r < $minRaw) continue;
            }
            $no = $ds['students'][$sid]['no'];
            // การสะกดคำ (4.1) ต้องเห็นต้นฉบับที่นักเรียนพิมพ์จริงก่อนระบบจัดเว้นวรรคให้
            // มิฉะนั้นข้อความตัวอย่างที่นำไปเขียนบทที่ 4 จะไม่ใช่สภาพงานเดิมที่ใช้ประเมินข้อบกพร่อง
            $useRawEssay = ($indicatorId === '4.1');
            $sourceIntro = $useRawEssay ? ($essay['raw_intro'] ?? $essay['intro']) : $essay['intro'];
            $sourceBody = $useRawEssay ? ($essay['raw_body'] ?? $essay['body']) : $essay['body'];
            $sourceConclusion = $useRawEssay ? ($essay['raw_conclusion'] ?? $essay['conclusion']) : $essay['conclusion'];
            $sourceText = $useRawEssay ? ($essay['raw_text'] ?? $essay['text']) : $essay['text'];
            $cand[] = [
                'sid'   => $sid,
                'no'    => $no,
                // ชื่อจริง — ใช้เฉพาะตอนครู/ผู้เชี่ยวชาญเปิดโหมด "แสดงชื่อจริง" เพื่อไล่หาต้นฉบับ
                // เท่านั้น ห้ามหลุดเข้าไปในข้อความที่ส่งให้ AI หรือบทที่ 4 ฉบับจริงเด็ดขาด
                // (ch45_ai_evidence_block ด้านล่างหยิบเฉพาะฟิลด์ที่ต้องใช้ ไม่ได้ dump ทั้งอาร์เรย์
                // จึงไม่หลุดไปในคำสั่งที่ส่งให้ระบบโดยอัตโนมัติอยู่แล้ว)
                'name'  => $ds['students'][$sid]['name'],
                'raw'   => $sc ? $sc['raw'][$indicatorId] : null,
                'used_elsewhere' => in_array($no, $usedNos, true),
                'tag'   => $tag,
                'intro' => ch45_trim_text($sourceIntro, 700, $useRawEssay),
                'body'  => array_map(function ($p) use ($useRawEssay) { return ch45_trim_text($p, 700, $useRawEssay); }, $sourceBody),
                'conclusion' => ch45_trim_text($sourceConclusion, 700, $useRawEssay),
                'text'  => ch45_trim_text($sourceText, 2200, $useRawEssay),
                'words' => $useRawEssay ? count_thai_words($sourceText) : $essay['word_count'],
                'is_raw_original' => $useRawEssay,
            ];
        }
        usort($cand, function ($a, $b) use ($order) {
            // ให้นักเรียนที่ยังไม่ถูกยกเป็นตัวอย่างในหัวข้ออื่นมาก่อนคนที่ถูกยกไปแล้ว (ความหลากหลาย)
            // แล้วจึงเรียงตามคะแนนดิบ (ทิศทางตาม $order) เป็นเกณฑ์หลัก
            if ($a['used_elsewhere'] !== $b['used_elsewhere']) return $a['used_elsewhere'] ? 1 : -1;
            $x = ($a['raw'] === null) ? 99 : $a['raw'];
            $y = ($b['raw'] === null) ? 99 : $b['raw'];
            if ($x == $y) return 0;
            $cmp = ($x < $y) ? -1 : 1;
            return $order === 'desc' ? -$cmp : $cmp;
        });
        return array_slice($cand, 0, $limit);
    };

    // ครั้งที่ 1: ใช้เฉพาะผลงานที่ปรากฏข้อบกพร่องจริงเท่านั้น ถ้าไม่มีเลย (แต่ครั้งที่ 2 กลับมี = ข้อบกพร่อง
    // เพิ่งเกิดขึ้นใหม่ในครั้งที่ 2) ก็เว้นว่างไปเลย ไม่ปั้นตัวอย่างจากคนที่ไม่มีข้อบกพร่องขึ้นมาแทน
    $w1 = $row['students1'] ? $pick($row['students1'], $p1, $e1, $perSlot, 'มีข้อบกพร่องในครั้งที่ 1', $usedW1, 'asc') : [];

    // ครั้งที่ 2: ให้น้ำหนักนักเรียนที่แก้ข้อบกพร่องได้แล้วก่อน (เรื่องราวการเปลี่ยนแปลงที่ชัดที่สุด)
    // ถ้ายังไม่ครบ เติมด้วยคนที่ "ยังปรากฏข้อบกพร่องอยู่จริง" ในครั้งที่ 2 (ไม่ว่าจะมีมาแต่ครั้งที่ 1 หรือเพิ่งเกิดใหม่)
    // เพื่อให้เห็นว่าปัญหาที่ยังไม่หมดไปจริง ๆ หน้าตาเป็นอย่างไร แล้วจึงเติมด้วยผลงานทั่วไปเป็นตัวเลือกสุดท้าย
    // เฉพาะเมื่อครั้งที่ 1 เคยมีข้อบกพร่องจริง (มีอะไรให้เทียบ) — ถ้าครั้งที่ 1 ก็ไม่มีเลย การหยิบผลงานทั่วไปมา
    // เติมจะไม่มีความหมายอะไรให้เปรียบเทียบ
    $resolved = $row['resolved'];
    $w2 = [];
    if ($resolved) $w2 = $pick($resolved, $p2, $e2, $perSlot, 'ครั้งที่ 1 มีข้อบกพร่อง ครั้งที่ 2 แก้ได้แล้ว', $usedW2, 'asc');
    if (count($w2) < $perSlot && $row['students2']) {
        $stillHas = array_values(array_diff($row['students2'], array_column($w2, 'sid')));
        $more = $pick($stillHas, $p2, $e2, $perSlot - count($w2), 'ยังปรากฏข้อบกพร่องนี้อยู่ในครั้งที่ 2', $usedW2, 'asc');
        foreach ($more as $m) $w2[] = $m;
    }
    if (count($w2) < $perSlot && $row['students1']) {
        $rest = array_values(array_diff($ds['sids'], array_column($w2, 'sid')));
        // ตัวเลือกสุดท้าย: ผลงานที่ "ทำได้ดี" จริง ๆ เท่านั้น (คะแนนดิบตั้งแต่ระดับ good_example_min ขึ้นไป)
        // จึงเรียงจากคะแนนสูงลงมา (คนที่ยังไม่ถูกใช้ที่ไหนมาก่อนเสมอ) — ไม่หยิบผลงานที่ยังทำได้ไม่ดีมาโชว์เป็นตัวอย่างที่ดี
        $more = $pick($rest, $p2, $e2, $perSlot - count($w2), 'ผลงานครั้งที่ 2', $usedW2, 'desc',
            (int)($meta['good_example_min'] ?? 2));
        foreach ($more as $m) $w2[] = $m;
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
    $substep = trim((string)($in['poa_substep'] ?? ''));
    if (mb_strlen($substep) > 10) $substep = mb_substr($substep, 0, 10);
    $unit    = (int)($in['task_unit'] ?? 0);
    $problem = trim((string)($in['problem'] ?? ''));
    $solution= trim((string)($in['solution'] ?? ''));
    $evidence= trim((string)($in['evidence'] ?? ''));
    $id      = (int)($in['id'] ?? 0);

    if ($problem === '') return ['ok' => false, 'error' => 'กรุณาระบุปัญหาที่พบระหว่างการจัดการเรียนการสอน'];

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE ch45_teaching_logs SET poa_stage = ?, poa_substep = ?, task_unit = ?,
                               problem = ?, solution = ?, evidence = ? WHERE id = ?');
        $stmt->execute([$stage, ($substep !== '' ? $substep : null), $unit, $problem, $solution, $evidence, $id]);
        return ['ok' => true, 'id' => $id];
    }
    $stmt = $pdo->prepare('INSERT INTO ch45_teaching_logs (poa_stage, poa_substep, task_unit, problem, solution, evidence, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$stage, ($substep !== '' ? $substep : null), $unit, $problem, $solution, $evidence, $by]);
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}

/** ลบบันทึกหลังสอนหนึ่งรายการ */
function ch45_delete_teaching_log(PDO $pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM ch45_teaching_logs WHERE id = ?');
    $stmt->execute([(int)$id]);
    return $stmt->rowCount() > 0;
}

/* =========================================================================
 * ส่วนที่ 8.1  คลังอ้างอิงงานวิจัยที่เกี่ยวข้อง (ใช้เขียนอภิปรายผลบทที่ 5)
 *
 * ผู้วิจัยเป็นผู้กรอกเองหลังตรวจสอบมาแล้วว่ามีอยู่จริง (เช่นเดียวกับตัวบทเรียงความจริงที่ใช้
 * ยกเป็นตัวอย่างในบทที่ 4) ระบบใช้รายการนี้ "จับคู่" กับผลจริงเท่านั้น ห้ามอ้างอิงชื่อ/ปี
 * ที่ไม่อยู่ในคลังนี้โดยเด็ดขาด — ดู ch45_ai_findings() และ ch45_ai_parse('ch5_discussion', ...)
 * ใน chapter45_engine.php ที่ตรวจซ้ำฝั่งเซิร์ฟเวอร์ว่าป้ายอ้างอิงที่ระบบตอบกลับมาตรงกับคลังนี้จริงหรือไม่
 * ========================================================================= */

/** ประเภทของแหล่งอ้างอิงที่เลือกได้ */
function ch45_reference_source_types() {
    return [
        'thesis'  => 'วิทยานิพนธ์/สารนิพนธ์',
        'journal' => 'บทความวารสาร',
        'book'    => 'หนังสือ/ตำรา',
        'other'   => 'อื่น ๆ',
    ];
}

/** อ่านคลังอ้างอิงทั้งหมด เรียงตามวันที่เพิ่มล่าสุดก่อน */
function ch45_references(PDO $pdo) {
    try {
        $stmt = $pdo->query('SELECT * FROM ch45_references ORDER BY id DESC');
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/** บันทึก/แก้ไขรายการอ้างอิงหนึ่งรายการ */
function ch45_save_reference(PDO $pdo, array $in, $by = '') {
    $types = ch45_reference_source_types();
    $label = trim((string)($in['citation_label'] ?? ''));
    $finding = trim((string)($in['key_finding'] ?? ''));
    $full = trim((string)($in['full_citation'] ?? ''));
    $url = trim((string)($in['source_url'] ?? ''));
    $type = (string)($in['source_type'] ?? 'other');
    if (!isset($types[$type])) $type = 'other';
    // ประเด็นจากผลจริงที่รายการนี้มีไว้จับคู่ (ว่างได้ — เป็นตัวช่วยจัดระเบียบ ไม่ใช่เงื่อนไขบังคับ)
    $fkey = substr(trim((string)($in['finding_key'] ?? '')), 0, 60);
    $id = (int)($in['id'] ?? 0);

    if ($label === '') return ['ok' => false, 'error' => 'กรุณาระบุป้ายอ้างอิงที่ใช้ในเนื้อความ เช่น "Shi (2023)"'];
    if ($finding === '') return ['ok' => false, 'error' => 'กรุณาระบุสิ่งที่งานนี้ค้นพบโดยย่อ เพื่อให้ระบบจับคู่กับผลจริงได้'];

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE ch45_references SET citation_label = ?, key_finding = ?,
                               full_citation = ?, source_url = ?, source_type = ?, finding_key = ? WHERE id = ?');
        $stmt->execute([$label, $finding, $full, $url, $type, $fkey, $id]);
        return ['ok' => true, 'id' => $id];
    }
    $stmt = $pdo->prepare('INSERT INTO ch45_references (citation_label, key_finding, full_citation, source_url, source_type, finding_key, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$label, $finding, $full, $url, $type, $fkey, $by]);
    return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
}

/** ลบรายการอ้างอิงหนึ่งรายการ */
function ch45_delete_reference(PDO $pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM ch45_references WHERE id = ?');
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
    $add($items, 'interrater', 'ความเที่ยงระหว่างผู้ประเมิน (ICC) (ต้องรายงานในตาราง 12)',
        $ir ? 'ok' : 'missing',
        $ir ? ('คำนวณได้ ' . count($ir) . ' รอบ (จากกลุ่ม "' . CH45_ICC_GROUP . '")')
            : 'ยังไม่มีรอบใดในกลุ่ม "' . CH45_ICC_GROUP . '" ที่มีผู้ประเมินตั้งแต่ 2 คนขึ้นไปให้คะแนนผลงานชุดเดียวกัน',
        'ให้ผู้ประเมินคนที่ 2 (ผู้เชี่ยวชาญ) เข้าให้คะแนนผลงานชุดเดียวกันในหน้าประเมินผลงาน '
        . '(นักเรียนต้องอยู่ในกลุ่ม "' . CH45_ICC_GROUP . '")');

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

    // 6.1) ฉบับจัดเว้นวรรคแล้ว — ตัวบทตั้งต้นของการวิเคราะห์เชิงคุณภาพในบทที่ 4-5
    //      ภาษาไทยเขียนติดกัน ถ้าเว้นวรรคผิดที่ ตัวตัดคำอัตโนมัติจะแบ่งคำผิดและระบบจะอ่านใจความคลาดเคลื่อน
    $nmHave = 0; $nmTotal = 0;
    foreach (ai_norm_phases() as $nmPh) {
        foreach ($ds['sids'] as $sid) {
            if (empty($ds['essays'][$sid][$nmPh]['has'])) continue;
            $nmTotal++;
            if (!empty($ds['essays'][$sid][$nmPh]['normalized'])) $nmHave++;
        }
    }
    $add($items, 'spacing', 'ฉบับจัดเว้นวรรคแล้ว (ตัวบทตั้งต้นของการวิเคราะห์)',
        // ยังไม่จัดก็วิเคราะห์ได้จากต้นฉบับ จึงเป็นแค่ "ควรทำก่อน" ไม่ใช่ "ขาดข้อมูล"
        ($nmTotal > 0 && $nmHave >= $nmTotal) ? 'ok' : 'warn',
        ($nmTotal === 0)
            ? 'ยังไม่มีเรียงความในรอบที่ใช้วิเคราะห์'
            : ('จัดแล้ว ' . $nmHave . ' จาก ' . $nmTotal . ' ฉบับ'
               . ' (ก่อนเรียน · ร่างที่ 2 ของทั้งสองหน่วย · หลังเรียน)'
               . ($nmHave < $nmTotal ? ' — ฉบับที่ยังไม่จัดจะวิเคราะห์จากต้นฉบับตามเดิม' : '')),
        'กดปุ่ม "จัดเว้นวรรคก่อนวิเคราะห์ → แก้ทั้งหมด" ในหน้า "ระบบตรวจเรียงความอัตโนมัติ" '
        . '(ฉบับที่จัดแล้วมีถ้อยคำเหมือนต้นฉบับทุกตัว ต่างกันเฉพาะการเว้นวรรค และไม่แสดงแทนงานเขียนของนักเรียน)');

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

    // 11) คลังอ้างอิงงานวิจัยที่เกี่ยวข้อง (ใช้เขียนอภิปรายผลในบทที่ 5)
    // ไม่บังคับ (ไม่ใช่ missing) เพราะไม่มีคลังระบบยังเขียนอภิปรายผลด้วยเหตุผลเชิงกลไกได้
    // แต่จะไม่มีการอ้างอิงงานวิจัยใด ๆ ทั้งสิ้นจนกว่าจะกรอกคลังนี้
    $nRefs = count($ds['references'] ?? []);
    $add($items, 'references', 'คลังอ้างอิงงานวิจัยที่เกี่ยวข้อง (ใช้เขียนอภิปรายผลในบทที่ 5)',
        $nRefs >= 3 ? 'ok' : 'warn',
        $nRefs ? ('กรอกไว้แล้ว ' . $nRefs . ' รายการ') : 'ยังไม่ได้กรอกไว้เลย',
        'กรอกงานวิจัยที่เกี่ยวข้องที่ผู้วิจัยตรวจสอบมาแล้วว่ามีอยู่จริงในกล่อง "คลังอ้างอิงงานวิจัยที่เกี่ยวข้อง" '
        . 'ด้านล่าง — ถ้าไม่กรอกไว้ ระบบจะเขียนอภิปรายผลด้วยเหตุผลเชิงกลไกเท่านั้น ไม่มีการอ้างอิงงานวิจัยใด ๆ');

    $counts = ['ok' => 0, 'warn' => 0, 'missing' => 0];
    foreach ($items as $it) $counts[$it['status']]++;
    return ['items' => $items, 'counts' => $counts,
            'ready' => ($counts['missing'] === 0),
            'score' => count($items) ? round($counts['ok'] * 100 / count($items)) : 0];
}
