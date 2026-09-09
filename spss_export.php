<?php
/**
 * spss_export.php — ส่งออกข้อมูลดิบและไฟล์คำสั่งสำหรับ IBM SPSS Statistics
 * ---------------------------------------------------------------------------
 * ระบบคำนวณสถิติของบทที่ 4-5 ให้ครบอยู่แล้ว (chapter45_stats.php) แต่การส่งวิทยานิพนธ์
 * มักต้องแนบ "ผลจาก SPSS" เป็นหลักฐานว่าตัวเลขในตารางมาจากไหน ไฟล์นี้จึงเตรียมของให้ครบชุด
 * เพื่อให้เปิด SPSS แล้วกดรันได้ทันที โดยไม่ต้องพิมพ์คำสั่งเองเลย
 *
 *   spss_data.csv      ข้อมูลดิบรายบุคคล 1 แถว = นักเรียน 1 คน (รูปแบบ wide ที่ SPSS ใช้ได้ตรง ๆ)
 *   spss_raters.csv    คะแนนแยกรายผู้ประเมิน ใช้คำนวณความเที่ยงระหว่างผู้ประเมิน (ICC / Pearson r)
 *   spss_syntax.sps    ไฟล์คำสั่ง SPSS ที่ประกาศตัวแปร ป้ายชื่อ และสั่งวิเคราะห์ครบทุกตารางของบทที่ 4
 *   spss_codebook.csv  พจนานุกรมตัวแปร (ชื่อตัวแปร → ความหมาย → ค่าที่เป็นไปได้) สำหรับแนบภาคผนวก
 *   README_SPSS.txt    ขั้นตอนการนำไปใช้ทีละขั้น
 *
 * พารามิเตอร์ (GET):
 *   file      = 'zip' (ค่าเริ่มต้น — ได้ครบทั้ง 5 ไฟล์) | 'data' | 'raters' | 'syntax' | 'codebook' | 'readme'
 *   group     = กรองเฉพาะกลุ่มการวิจัย (ไม่ระบุ = ทุกกลุ่ม)
 *   classroom = กรองเฉพาะห้องเรียน (ไม่ระบุ = ทุกห้อง)
 *   dir       = โฟลเดอร์ที่จะแตกไฟล์ไว้บนเครื่องที่ลง SPSS (ใช้เขียน path ในไฟล์คำสั่ง)
 *
 * ไฟล์นี้ "อ่านอย่างเดียว" ไม่แก้ไขข้อมูลวิจัยใด ๆ · เข้าถึงได้เฉพาะคุณครู
 */

require_once 'auth_helper.php';
require_once 'chapter45_data.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ต้องเข้าสู่ระบบในฐานะคุณครูเพื่อส่งออกชุดข้อมูลนี้';
    exit;
}

$which     = isset($_GET['file']) ? trim($_GET['file']) : 'zip';
$group     = isset($_GET['group']) ? trim($_GET['group']) : '';
$classroom = isset($_GET['classroom']) ? trim($_GET['classroom']) : '';
$targetDir = isset($_GET['dir']) ? trim($_GET['dir']) : '';

/* =========================================================================
 * ส่วนที่ 1  ค่าคงที่และตัวช่วยเล็ก ๆ
 * ========================================================================= */

/** โฟลเดอร์ปลายทางเริ่มต้นบนเครื่องที่ลง SPSS (ผู้ใช้แก้ได้ในหน้าตั้งค่า) */
const SPSS_DEFAULT_DIR = 'C:\\thaieasay_spss';

const SPSS_FILE_DATA     = 'spss_data.csv';
const SPSS_FILE_RATERS   = 'spss_raters.csv';
const SPSS_FILE_SYNTAX   = 'spss_syntax.sps';
const SPSS_FILE_CODEBOOK = 'spss_codebook.csv';
const SPSS_FILE_README   = 'README_SPSS.txt';

/**
 * รอบการประเมินในไฟล์คะแนนรายผู้ประเมิน (รหัสตัวเลขที่ใช้ในตัวแปร phase)
 * ป้ายชื่อใช้ชุดเดียวกับ ch45_interrater() เพื่อให้เทียบผลกับหน้าจอได้ตรงตัว
 */
function spss_rater_phases() {
    return [
        'pretest'  => ['code' => 1, 'label' => 'ก่อนเรียน'],
        'task1'    => ['code' => 2, 'label' => 'ภาระงานหน่วยที่ 1'],
        'task2'    => ['code' => 3, 'label' => 'ภาระงานหน่วยที่ 2'],
        'posttest' => ['code' => 4, 'label' => 'หลังเรียน'],
    ];
}

/** ทำความสะอาดข้อความก่อนลง CSV — ตัดขึ้นบรรทัดใหม่ออก เพราะ SPSS อ่านทีละบรรทัด */
function spss_clean_text($v, $maxChars = 60) {
    $s = preg_replace('/\s+/u', ' ', (string)$v);
    $s = trim($s);
    if (function_exists('mb_substr') && mb_strlen($s, 'UTF-8') > $maxChars) {
        $s = mb_substr($s, 0, $maxChars, 'UTF-8');
    }
    return $s;
}

/** เซลล์หนึ่งช่องในไฟล์ CSV ที่ SPSS จะอ่าน (ข้อความครอบด้วย " เสมอ ตัวเลขไม่ครอบ) */
function spss_cell($v, $decimals = 3) {
    if ($v === null || $v === '') return '';
    if (is_int($v)) return (string)$v;
    if (is_float($v)) return sprintf('%.' . (int)$decimals . 'f', $v);
    return '"' . str_replace('"', '""', spss_clean_text($v, 60)) . '"';
}

/** ชื่อผู้ประเมินแบบอ่านง่าย จากคีย์ภายใน "role:ชื่อ" */
function spss_rater_label($key) {
    $roleTh = ['teacher' => 'ครูผู้สอน', 'expert' => 'ผู้เชี่ยวชาญ',
               'self' => 'ประเมินตนเอง', 'peer' => 'เพื่อนประเมิน', 'other' => 'ผู้ประเมิน'];
    $pos  = strpos((string)$key, ':');
    $role = $pos === false ? 'other' : substr($key, 0, $pos);
    $name = $pos === false ? (string)$key : substr($key, $pos + 1);
    $out  = $roleTh[$role] ?? 'ผู้ประเมิน';
    return trim($name) !== '' ? $out . ' (' . spss_clean_text($name, 40) . ')' : $out;
}

/** ตัวเลขในบทสรุปที่แนบไว้ในไฟล์คำสั่ง — ค่าว่างแสดงเป็นขีด */
function spss_n($v, $dec = 2) {
    return ($v === null) ? '-' : number_format((float)$v, $dec, '.', '');
}

/* =========================================================================
 * ส่วนที่ 2  โครงสร้างตัวแปรของไฟล์ข้อมูลดิบ (ลำดับนี้คือลำดับคอลัมน์ใน CSV)
 * ========================================================================= */

/**
 * รอบงาน 4 ช่วงที่นำเข้า SPSS
 *   pre / post  ใช้ทดสอบก่อน–หลังเรียน (ตาราง 12)
 *   w1 / w2     ผลงานครั้งที่ 1 และครั้งที่ 2 ใช้เปรียบเทียบข้อบกพร่อง (ตาราง 14)
 */
function spss_slots(array $meta) {
    return [
        'pre'  => ['eval' => 'pretest',  'essay' => 'pretest',  'label' => 'ก่อนเรียน'],
        'post' => ['eval' => 'posttest', 'essay' => 'posttest', 'label' => 'หลังเรียน'],
        'w1'   => ['eval' => $meta['work1_eval_phase'], 'essay' => $meta['work1_phase'],
                   'label' => 'ผลงาน' . $meta['work1_label']],
        'w2'   => ['eval' => $meta['work2_eval_phase'], 'essay' => $meta['work2_phase'],
                   'label' => 'ผลงาน' . $meta['work2_label']],
    ];
}

/** ชื่อตัวแปรของตัวบ่งชี้ เช่น '1.1' + 'pre' → 'pre_i11' */
function spss_ind_var($slot, $indicatorId) {
    return $slot . '_i' . str_replace('.', '', $indicatorId);
}

/**
 * รายการตัวแปรทั้งหมดของไฟล์ข้อมูลดิบ เรียงตามลำดับคอลัมน์
 * แต่ละรายการ: name / fmt (รูปแบบของ SPSS) / label / measure / values (ป้ายค่า)
 *
 * ลำดับสำคัญ — ไฟล์คำสั่งอ้างช่วงตัวแปรแบบ "pre_i11 TO post_i43" ซึ่งอาศัยลำดับนี้
 */
function spss_variables(array $meta) {
    $levels = ch45_score_levels();
    $vars = [
        // ความกว้างของตัวแปรข้อความนับเป็น "ไบต์" อักษรไทยตัวละ 3 ไบต์ และ spss_clean_text()
        // ตัดข้อความไว้ที่ 60 ตัวอักษร จึงประกาศ A180 เพื่อไม่ให้ SPSS ตัดข้อความทิ้งเงียบ ๆ
        ['name' => 'stu_code',  'fmt' => 'A60',  'measure' => 'NOMINAL', 'label' => 'รหัสประจำตัวนักเรียน'],
        ['name' => 'stu_no',    'fmt' => 'F4.0', 'measure' => 'NOMINAL', 'label' => 'เลขนิรนามที่ใช้อ้างในบทที่ 4 (นักเรียนคนที่ N)'],
        ['name' => 'stu_name',  'fmt' => 'A180', 'measure' => 'NOMINAL', 'label' => 'ชื่อ-สกุล (ตัดออกก่อนแนบเป็นภาคผนวก)'],
        ['name' => 'classroom', 'fmt' => 'A180', 'measure' => 'NOMINAL', 'label' => 'ห้องเรียน'],
        ['name' => 'grp',       'fmt' => 'A180', 'measure' => 'NOMINAL', 'label' => 'กลุ่มการวิจัย'],
    ];

    // คะแนนดิบรายตัวบ่งชี้ 0-4 ของทั้ง 4 รอบ (แกนหลักของการวิเคราะห์ทั้งหมด)
    foreach (spss_slots($meta) as $slot => $s) {
        foreach (ch45_indicators() as $id => $ind) {
            $vars[] = [
                'name'    => spss_ind_var($slot, $id),
                'fmt'     => 'F8.4',
                'measure' => 'ORDINAL',
                'label'   => $s['label'] . ' · ' . $id . ' ' . $ind['name'] . ' (คะแนนดิบ 0-4)',
                'values'  => $levels,
            ];
        }
    }
    // จำนวนผู้ประเมินที่คะแนนของรอบนั้นเฉลี่ยมาจาก (ใช้ตรวจสอบว่าฐานการคำนวณตรงกับที่รายงาน)
    foreach (spss_slots($meta) as $slot => $s) {
        $vars[] = ['name' => $slot . '_k', 'fmt' => 'F2.0', 'measure' => 'SCALE',
                   'label' => $s['label'] . ' · จำนวนผู้ประเมินที่นำคะแนนมาเฉลี่ย'];
    }
    // ความยาวงานเขียน (ใช้ในหัวข้อ 2.4 และบรรยายลักษณะของผลงาน)
    foreach (spss_slots($meta) as $slot => $s) {
        $vars[] = ['name' => $slot . '_words', 'fmt' => 'F6.0', 'measure' => 'SCALE',
                   'label' => $s['label'] . ' · จำนวนคำในเรียงความ'];
    }
    // จำนวนคำที่ระบบตรวจว่าสะกดผิด (นับชนิดคำที่ไม่ซ้ำกัน) — ใช้ในหัวข้อ 2.4.1
    foreach (['w1' => 'ผลงานครั้งที่ 1', 'w2' => 'ผลงานครั้งที่ 2'] as $slot => $lb) {
        $vars[] = ['name' => $slot . '_miss', 'fmt' => 'F4.0', 'measure' => 'SCALE',
                   'label' => $lb . ' · จำนวนคำสะกดผิดที่ตรวจพบ (ชนิดคำที่ไม่ซ้ำกัน · เป็นค่าประมาณ)'];
    }
    return $vars;
}

/* =========================================================================
 * ส่วนที่ 3  สร้างข้อมูลดิบรายบุคคล
 * ========================================================================= */

/** 1 แถว = นักเรียน 1 คน — คืนอาร์เรย์ [ชื่อตัวแปร => ค่า] เพื่อให้ลำดับคอลัมน์ผูกกับ spss_variables() เสมอ */
function spss_wide_rows(array $ds, array $mech) {
    $meta  = $ds['meta'];
    $slots = spss_slots($meta);
    $inds  = ch45_indicators();

    $rows = [];
    foreach ($ds['sids'] as $sid) {
        $stu = $ds['students'][$sid];
        $r = [
            'stu_code'  => (string)$sid,
            'stu_no'    => (int)$stu['no'],
            'stu_name'  => (string)$stu['name'],
            'classroom' => (string)$stu['classroom'],
            'grp'       => (string)$stu['group'],
        ];
        foreach ($slots as $slot => $s) {
            $sc = ch45_scores_of($ds, $sid, $s['eval']);
            foreach ($inds as $id => $ind) {
                $v = ($sc && $sc['raw'][$id] !== null) ? (float)$sc['raw'][$id] : null;
                $r[spss_ind_var($slot, $id)] = $v;
            }
            $r[$slot . '_k'] = $sc ? (int)$sc['raters'] : null;
        }
        foreach ($slots as $slot => $s) {
            $e = $ds['essays'][$sid][$s['essay']] ?? null;
            $r[$slot . '_words'] = ($e && $e['has']) ? (int)$e['word_count'] : null;
        }
        foreach (['w1' => 'work1', 'w2' => 'work2'] as $slot => $mk) {
            $per = $mech[$mk]['per_student_misspelled'] ?? null;
            $r[$slot . '_miss'] = (is_array($per) && isset($per[$sid])) ? count($per[$sid]) : null;
        }
        $rows[] = $r;
    }
    return $rows;
}

/** ประกอบไฟล์ CSV จากรายการตัวแปรและแถวข้อมูล (UTF-8 มี BOM — บรรทัดหัวคอลัมน์ SPSS ข้ามอยู่แล้ว) */
function spss_csv(array $vars, array $rows) {
    $out = "\xEF\xBB\xBF";
    $out .= implode(',', array_column($vars, 'name')) . "\r\n";
    foreach ($rows as $r) {
        $line = [];
        foreach ($vars as $v) {
            $val = $r[$v['name']] ?? null;
            $line[] = spss_cell($val, (strpos($v['fmt'], '.') !== false) ? (int)substr($v['fmt'], strpos($v['fmt'], '.') + 1) : 0);
        }
        $out .= implode(',', $line) . "\r\n";
    }
    return $out;
}

/* =========================================================================
 * ส่วนที่ 4  คะแนนแยกรายผู้ประเมิน (สำหรับ ICC และค่าสหสัมพันธ์ระหว่างผู้ประเมิน)
 * ========================================================================= */

/**
 * จัดคะแนนรวมของผู้ประเมินแต่ละคนให้อยู่ในรูปที่ SPSS คำนวณ ICC ได้
 * (1 แถว = นักเรียน 1 คนในรอบหนึ่ง · คอลัมน์ rater1..raterK = ผู้ประเมินแต่ละคน)
 *
 * ใช้ตรรกะเดียวกับ ch45_interrater() คือ นับเฉพาะนักเรียนที่ผู้ประเมิน "ทุกคน" ให้คะแนนครบ
 * เพื่อให้ตัวเลขที่ SPSS คำนวณได้ตรงกับที่ระบบรายงานไว้
 */
function spss_rater_dataset(array $iccDs) {
    $roles  = ch45_rater_roles($iccDs['meta']);
    $phases = spss_rater_phases();

    $out = ['max_k' => 0, 'phases' => [], 'rows' => []];
    foreach ($phases as $phase => $ph) {
        $raters = [];
        foreach ($iccDs['sids'] as $sid) {
            foreach ($roles as $role) {
                foreach ($iccDs['evals'][$sid][$phase][$role] ?? [] as $one) {
                    if ($one['total'] !== null) $raters[$role . ':' . $one['rater']][$sid] = (float)$one['total'];
                }
            }
        }
        if (count($raters) < 2) continue;

        $keys = array_keys($raters);
        sort($keys);
        $common = null;
        foreach ($keys as $k) {
            $ids = array_keys($raters[$k]);
            $common = ($common === null) ? $ids : array_values(array_intersect($common, $ids));
        }
        if (!$common || count($common) < 3) continue;
        sort($common);

        $out['phases'][$phase] = [
            'code'   => $ph['code'],
            'label'  => $ph['label'],
            'raters' => $keys,
            'k'      => count($keys),
            'n'      => count($common),
        ];
        $out['max_k'] = max($out['max_k'], count($keys));

        foreach ($common as $sid) {
            $row = ['stu_code' => (string)$sid, 'phase' => $ph['code']];
            foreach ($keys as $i => $k) $row['rater' . ($i + 1)] = $raters[$k][$sid];
            $out['rows'][] = $row;
        }
    }
    return $out;
}

/** รายการตัวแปรของไฟล์คะแนนรายผู้ประเมิน */
function spss_rater_variables(array $rd) {
    $phaseLabels = [];
    foreach ($rd['phases'] as $p) $phaseLabels[$p['code']] = $p['label'];
    $vars = [
        ['name' => 'stu_code', 'fmt' => 'A60',  'measure' => 'NOMINAL', 'label' => 'รหัสประจำตัวนักเรียน'],
        ['name' => 'phase',    'fmt' => 'F2.0', 'measure' => 'NOMINAL', 'label' => 'รอบการประเมิน',
         'values' => $phaseLabels],
    ];
    for ($i = 1; $i <= max(1, (int)$rd['max_k']); $i++) {
        $vars[] = ['name' => 'rater' . $i, 'fmt' => 'F7.2', 'measure' => 'SCALE',
                   'label' => 'คะแนนรวมของผู้ประเมินคนที่ ' . $i . ' (เต็ม 60 คะแนน)'];
    }
    return $vars;
}

/* =========================================================================
 * ส่วนที่ 5  พจนานุกรมตัวแปร (แนบเป็นภาคผนวกของวิทยานิพนธ์ได้)
 * ========================================================================= */

function spss_codebook(array $vars, array $raterVars, array $meta) {
    $out = "\xEF\xBB\xBF";
    $out .= "ไฟล์,ชื่อตัวแปร,ความหมาย,ชนิด,มาตรวัด,ค่าที่เป็นไปได้\r\n";
    $push = function ($file, $list) use (&$out) {
        foreach ($list as $v) {
            $vals = '';
            if (!empty($v['values'])) {
                $parts = [];
                foreach ($v['values'] as $k => $lb) $parts[] = $k . ' = ' . $lb;
                $vals = implode(' · ', $parts);
            }
            $type = (strpos($v['fmt'], 'A') === 0) ? 'ข้อความ' : 'ตัวเลข';
            $out .= '"' . $file . '","' . $v['name'] . '","' . str_replace('"', '""', $v['label']) . '","'
                 . $type . '","' . $v['measure'] . '","' . str_replace('"', '""', $vals) . "\"\r\n";
        }
    };
    $push(SPSS_FILE_DATA, $vars);
    $push(SPSS_FILE_RATERS, $raterVars);

    // ตัวแปรที่ไฟล์คำสั่งสร้างขึ้นเองระหว่างรัน (ไม่มีในไฟล์ CSV แต่มีในไฟล์ .sav ที่ได้)
    $out .= "\r\n";
    $out .= "ไฟล์,ชื่อตัวแปร,ความหมาย,ชนิด,มาตรวัด,ค่าที่เป็นไปได้\r\n";
    $derived = [
        ['pre_d1..pre_d4 / post_d1..post_d4', 'คะแนนรวมรายองค์ประกอบหลังถ่วงน้ำหนัก (เต็ม 27/12/15/6)'],
        ['pre_tot / post_tot', 'คะแนนรวมทั้งฉบับ (เต็ม 60)'],
        ['diff_tot / diff_d1..diff_d4', 'ผลต่างหลังเรียน − ก่อนเรียน (ใช้ทดสอบการแจกแจงปกติ)'],
        ['w1_def11..w2_def43', 'สถานะ "ปรากฏข้อบกพร่อง" รายตัวบ่งชี้ (1 = ปรากฏ, 0 = ไม่ปรากฏ)'],
        ['base12', 'มีคะแนนครบทั้งก่อนและหลังเรียน (ฐานของตาราง 12)'],
        ['base14', 'มีคะแนนครบทั้งผลงานครั้งที่ 1 และครั้งที่ 2 (ฐานของตาราง 14)'],
    ];
    foreach ($derived as $d) {
        $out .= '"(สร้างโดยไฟล์คำสั่ง)","' . $d[0] . '","' . str_replace('"', '""', $d[1])
             . '","ตัวเลข","SCALE",""' . "\r\n";
    }

    $out .= "\r\n\"นิยามเชิงปฏิบัติการที่ใช้\",\"" . str_replace('"', '""', (string)$meta['defect_rule']) . "\"\r\n";
    return $out;
}

/* =========================================================================
 * ส่วนที่ 6  ไฟล์คำสั่ง SPSS
 * ========================================================================= */

/** ครอบข้อความให้เป็นบรรทัดคอมเมนต์ของ SPSS (ขึ้นต้นด้วย * ปิดท้ายด้วยจุด ซึ่งเป็นตัวปิดคำสั่ง) */
function spss_note($text) {
    return '* ' . preg_replace('/[\r\n]+/u', ' ', (string)$text) . ".\n";
}

/**
 * ตัดรายชื่อตัวแปรยาว ๆ ให้ขึ้นบรรทัดใหม่เป็นช่วง ๆ
 * เพราะคำสั่งของ SPSS มีเพดานความยาวต่อบรรทัด การใส่ชื่อตัวแปร 44 ตัวในบรรทัดเดียวเสี่ยงถูกตัด
 */
function spss_wrap_names(array $names, $perLine = 8, $indent = '  ') {
    $lines = [];
    foreach (array_chunk($names, $perLine) as $chunk) $lines[] = $indent . implode(' ', $chunk);
    return implode("\n", $lines);
}

/** ประกาศตัวแปรในบล็อก GET DATA */
function spss_var_decl(array $vars) {
    $lines = [];
    foreach ($vars as $v) $lines[] = '    ' . str_pad($v['name'], 12) . ' ' . $v['fmt'];
    return implode("\n", $lines);
}

/** VARIABLE LABELS / VALUE LABELS / VARIABLE LEVEL ของชุดตัวแปรหนึ่งไฟล์ */
function spss_labels_block(array $vars) {
    $s = "VARIABLE LABELS\n";
    $rows = [];
    foreach ($vars as $v) {
        $rows[] = '  ' . $v['name'] . " '" . str_replace("'", "''", $v['label']) . "'";
    }
    $s .= implode("\n", $rows) . ".\n\n";

    // ป้ายค่าของตัวแปรที่มีชุดค่าเดียวกัน รวมประกาศทีเดียวเพื่อให้ไฟล์สั้นและอ่านง่าย
    $byValues = [];
    foreach ($vars as $v) {
        if (empty($v['values'])) continue;
        $sig = md5(json_encode($v['values'], JSON_UNESCAPED_UNICODE));
        $byValues[$sig]['values'] = $v['values'];
        $byValues[$sig]['names'][] = $v['name'];
    }
    foreach ($byValues as $g) {
        $s .= "VALUE LABELS\n" . spss_wrap_names($g['names']) . "\n";
        $parts = [];
        foreach ($g['values'] as $k => $lb) {
            $parts[] = "  " . $k . " '" . str_replace("'", "''", $lb) . "'";
        }
        $s .= implode("\n", $parts) . ".\n\n";
    }

    $byLevel = [];
    foreach ($vars as $v) $byLevel[$v['measure']][] = $v['name'];
    foreach ($byLevel as $lvl => $names) {
        $s .= "VARIABLE LEVEL\n" . spss_wrap_names($names) . ' (' . $lvl . ").\n";
    }
    return $s . "\n";
}

/** บล็อก GET DATA ของไฟล์ CSV หนึ่งไฟล์ */
function spss_get_data($path, array $vars, $datasetName) {
    return "GET DATA\n"
        . "  /TYPE=TXT\n"
        . "  /FILE='" . $path . "'\n"
        . "  /ENCODING='UTF8'\n"
        . "  /ARRANGEMENT=DELIMITED\n"
        . "  /DELIMITERS=\",\"\n"
        . "  /QUALIFIER='\"'\n"
        . "  /FIRSTCASE=2\n"
        . "  /DELCASE=LINE\n"
        . "  /VARIABLES=\n" . spss_var_decl($vars) . ".\n"
        . "CACHE.\n"
        . "EXECUTE.\n"
        . "DATASET NAME " . $datasetName . " WINDOW=FRONT.\n\n";
}

/**
 * สร้างไฟล์คำสั่งทั้งฉบับ
 *
 * เขียนคำสั่งจากค่าจริงที่ตั้งไว้ในระบบทุกจุด (ตัวคูณน้ำหนัก · เกณฑ์นับข้อบกพร่อง ·
 * รายชื่อผู้ประเมินของแต่ละรอบ) ไฟล์ที่ได้จึงคำนวณซ้ำได้ตรงกับตัวเลขบนหน้าจอ
 * และแนบตัวเลขที่ระบบคำนวณไว้เป็นคอมเมนต์ให้เทียบผลได้ทันทีหลังรันเสร็จ
 */
function spss_syntax(array $ds, array $quant, array $defects, array $rd, $dir) {
    $meta   = $ds['meta'];
    $inds   = ch45_indicators();
    $domains = ch45_domains();
    $slots  = spss_slots($meta);
    $vars   = spss_variables($meta);
    $rvars  = spss_rater_variables($rd);
    $sep    = (substr($dir, -1) === '\\' || substr($dir, -1) === '/') ? '' : '\\';
    $pData  = $dir . $sep . SPSS_FILE_DATA;
    $pRater = $dir . $sep . SPSS_FILE_RATERS;
    $cut    = is_array($meta['defect_cut'] ?? null) ? $meta['defect_cut'] : [];

    $s = "\xEF\xBB\xBF";
    $s .= "* =====================================================================\n";
    $s .= "* ไฟล์คำสั่ง IBM SPSS Statistics — วิเคราะห์ผลการวิจัยบทที่ 4\n";
    $s .= "* สร้างอัตโนมัติจากระบบประเมินการเขียนเรียงความ เมื่อ " . date('Y-m-d H:i') . "\n";
    $s .= "* =====================================================================\n";
    $s .= "* วิธีใช้ (ทำ 3 ขั้น)\n";
    $s .= "*   1) แตกไฟล์ทั้งหมดจากไฟล์ zip ไว้ในโฟลเดอร์เดียวกัน\n";
    $s .= "*   2) ถ้าโฟลเดอร์ไม่ใช่ " . $dir . " ให้ค้นหาข้อความนี้ในไฟล์แล้วแทนที่ด้วยโฟลเดอร์จริง\n";
    $s .= "*      (แทนที่ทุกแห่งที่พบ — อยู่ในบรรทัด /FILE= และ SAVE OUTFILE=)\n";
    $s .= "*   3) เปิดไฟล์นี้ใน SPSS แล้วสั่ง Run > All (หรือกด Ctrl+A แล้ว Ctrl+R)\n";
    $s .= "* =====================================================================\n\n";

    $s .= spss_note('ขอบเขตข้อมูล: กลุ่ม ' . ($ds['filter']['group'] !== '' ? $ds['filter']['group'] : 'ทุกกลุ่ม')
        . ' · ห้อง ' . ($ds['filter']['classroom'] !== '' ? $ds['filter']['classroom'] : 'ทุกห้อง')
        . ' · นักเรียนในชุดข้อมูล ' . count($ds['sids']) . ' คน');
    $s .= spss_note('คะแนนที่ใช้วิเคราะห์: ' . $meta['score_source_label']);
    $s .= spss_note($meta['defect_rule']);
    $s .= "\n";

    $s .= "SET DECIMAL=DOT.\n\n";

    /* ---------- 1) นำเข้าข้อมูล ---------- */
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 1) นำเข้าข้อมูลดิบรายบุคคล  <<< แก้ path ตรงนี้ถ้าเก็บไฟล์ไว้ที่อื่น.\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= spss_get_data($pData, $vars, 'main');
    $s .= spss_labels_block($vars);

    /* ---------- 2) คะแนนถ่วงน้ำหนักและคะแนนรวม ---------- */
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 2) คำนวณคะแนนรายองค์ประกอบและคะแนนรวม\n";
    $s .= "*    ฐานข้อมูลเก็บคะแนนดิบ 0-4 ต่อตัวบ่งชี้ ตัวคูณน้ำหนักของแต่ละตัวบ่งชี้ตามเกณฑ์.\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    foreach (['pre' => 'ก่อนเรียน', 'post' => 'หลังเรียน'] as $slot => $lb) {
        foreach ($domains as $dk => $d) {
            $terms = [];
            foreach ($d['indicators'] as $id) {
                // เขียนตัวคูณด้วยทศนิยม 2 ตำแหน่งเสมอ เพื่อไม่ให้บรรทัดจบด้วยจำนวนเต็มติดจุดปิดคำสั่ง
                // ซึ่งเป็นรูปแบบที่คู่มือ SPSS เตือนว่าอ่านได้กำกวม
                $terms[] = spss_ind_var($slot, $id) . ' * ' . number_format($inds[$id]['multiplier'], 2, '.', '');
            }
            $s .= 'COMPUTE ' . $slot . '_' . $dk . ' = ' . implode(' + ', $terms) . ".\n";
        }
        $s .= 'COMPUTE ' . $slot . '_tot = ' . $slot . '_d1 + ' . $slot . '_d2 + ' . $slot . '_d3 + ' . $slot . "_d4.\n";
    }
    $s .= "COMPUTE diff_tot = post_tot - pre_tot.\n";
    foreach ($domains as $dk => $d) $s .= 'COMPUTE diff_' . $dk . ' = post_' . $dk . ' - pre_' . $dk . ".\n";
    $s .= "EXECUTE.\n\n";

    $s .= "VARIABLE LABELS\n";
    $lab = [];
    foreach (['pre' => 'ก่อนเรียน', 'post' => 'หลังเรียน'] as $slot => $lb) {
        foreach ($domains as $dk => $d) {
            $lab[] = '  ' . $slot . '_' . $dk . " '" . $lb . ' · ด้านที่ ' . $d['no'] . ' ' . $d['name'] . ' (เต็ม ' . $d['max'] . ")'";
        }
        $lab[] = '  ' . $slot . '_tot ' . "'" . $lb . " · คะแนนรวม (เต็ม 60)'";
    }
    $lab[] = "  diff_tot 'ผลต่างคะแนนรวม (หลังเรียน − ก่อนเรียน)'";
    foreach ($domains as $dk => $d) {
        $lab[] = '  diff_' . $dk . " 'ผลต่างด้านที่ " . $d['no'] . ' ' . $d['name'] . " (หลังเรียน − ก่อนเรียน)'";
    }
    $s .= implode("\n", $lab) . ".\n\n";

    /* ---------- 3) ฐานการวิเคราะห์ ---------- */
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 3) ฐานการวิเคราะห์ — นับเฉพาะนักเรียนที่มีคะแนนครบ เพื่อให้เทียบกันได้จริง.\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "COMPUTE base12 = (NMISS(pre_i11 TO post_i43) = 0).\n";
    $s .= "COMPUTE base14 = (NMISS(w1_i11 TO w2_i43) = 0).\n";
    $s .= "VARIABLE LABELS base12 'มีคะแนนครบทั้งก่อนและหลังเรียน' base14 'มีคะแนนครบทั้งผลงานครั้งที่ 1 และ 2'.\n";
    $s .= "VALUE LABELS base12 base14 0 'ไม่ครบ' 1 'ครบ'.\n";
    $s .= "EXECUTE.\n";
    $s .= "FREQUENCIES VARIABLES=base12 base14 /ORDER=ANALYSIS.\n\n";

    /* ---------- 4) ตาราง 12 ---------- */
    $totVars = 'pre_tot pre_d1 pre_d2 pre_d3 pre_d4';
    $postVars = 'post_tot post_d1 post_d2 post_d3 post_d4';
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 4) ตาราง 12 — เปรียบเทียบคะแนนก่อนเรียนและหลังเรียน\n";
    $s .= "*    4.1 ค่าเฉลี่ยและส่วนเบี่ยงเบนมาตรฐาน\n";
    $s .= "*    4.2 ตรวจการแจกแจงปกติของคะแนนผลต่าง (Shapiro-Wilk) ก่อนใช้ t-test\n";
    $s .= "*    4.3 Paired-samples t-test พร้อมขนาดอิทธิพล (Cohen's d)\n";
    $s .= "*    4.4 Wilcoxon signed-rank สำรองไว้ กรณีคะแนนผลต่างไม่เป็นการแจกแจงปกติ.\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "USE ALL.\n";
    $s .= "FILTER OFF.\n";
    $s .= "FILTER BY base12.\n\n";
    $s .= "DESCRIPTIVES VARIABLES=" . $totVars . ' ' . $postVars . "\n";
    $s .= "  /STATISTICS=MEAN STDDEV MIN MAX.\n\n";
    $s .= "EXAMINE VARIABLES=diff_tot diff_d1 diff_d2 diff_d3 diff_d4\n";
    $s .= "  /PLOT NPPLOT\n";
    $s .= "  /STATISTICS DESCRIPTIVES\n";
    $s .= "  /MISSING LISTWISE.\n\n";
    $s .= "T-TEST PAIRS=" . $totVars . " WITH " . $postVars . " (PAIRED)\n";
    $s .= "  /ES DISPLAY(TRUE)\n";
    $s .= "  /CRITERIA=CI(.9500)\n";
    $s .= "  /MISSING=ANALYSIS.\n\n";
    $s .= "NPAR TESTS\n";
    $s .= "  /WILCOXON=" . $totVars . " WITH " . $postVars . " (PAIRED)\n";
    $s .= "  /MISSING ANALYSIS.\n\n";
    $s .= "* ความสอดคล้องภายในของเกณฑ์ 11 ตัวบ่งชี้ (ข้อมูลประกอบ ไม่ได้อยู่ในตาราง 12).\n";
    $s .= "RELIABILITY /VARIABLES=post_i11 TO post_i43 /SCALE('เกณฑ์ 11 ตัวบ่งชี้ หลังเรียน') ALL\n";
    $s .= "  /MODEL=ALPHA /SUMMARY=TOTAL.\n\n";
    $s .= "FILTER OFF.\n\n";

    // ตัวเลขที่ระบบคำนวณไว้ ใช้เทียบกับผลที่ SPSS จะพิมพ์ออกมา
    $s .= "* --- ตัวเลขที่ระบบคำนวณไว้แล้ว (ใช้เทียบกับผลที่ SPSS พิมพ์ออกมา) ---.\n";
    $s .= spss_note('n = ' . $quant['n'] . ' คน · df = ' . $quant['df']);
    foreach ($quant['rows'] as $r) {
        $s .= spss_note($r['label']
            . ': ก่อนเรียน M=' . spss_n($r['pre_mean']) . ' SD=' . spss_n($r['pre_sd'])
            . ' | หลังเรียน M=' . spss_n($r['post_mean']) . ' SD=' . spss_n($r['post_sd'])
            . ' | t=' . spss_n($r['t'], 3) . ' p=' . spss_n($r['p'], 4) . ' dz=' . spss_n($r['dz'], 3));
    }
    foreach ($quant['normality'] as $k => $nm) {
        if (empty($nm) || !isset($nm['W'])) continue;
        $s .= spss_note('Shapiro-Wilk ของผลต่าง (' . $k . '): W=' . spss_n($nm['W'], 4)
            . ' p=' . spss_n($nm['p'] ?? null, 4));
    }
    $s .= "\n";

    /* ---------- 5) ตาราง 14 ---------- */
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 5) ตาราง 14 — จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่อง 11 ตัวบ่งชี้\n";
    $s .= "*    สร้างตัวแปร 0/1 ตามเกณฑ์ที่ตั้งไว้ในระบบ แล้วทดสอบการเปลี่ยนแปลงด้วย McNemar.\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    foreach (['w1', 'w2'] as $slot) {
        foreach ($inds as $id => $ind) {
            $c = (int)($cut[$id] ?? 2);
            $s .= 'COMPUTE ' . $slot . '_def' . str_replace('.', '', $id) . ' = ('
                . spss_ind_var($slot, $id) . ' <= ' . $c . ").\n";
        }
    }
    $s .= "EXECUTE.\n\n";
    $s .= "VARIABLE LABELS\n";
    $lab = [];
    foreach (['w1' => 'ครั้งที่ 1', 'w2' => 'ครั้งที่ 2'] as $slot => $lb) {
        foreach ($inds as $id => $ind) {
            $lab[] = '  ' . $slot . '_def' . str_replace('.', '', $id)
                . " '" . $lb . ' · ' . $id . ' ' . str_replace("'", "''", $ind['defect']) . "'";
        }
    }
    $s .= implode("\n", $lab) . ".\n";
    $defNames = [];
    foreach (['w1', 'w2'] as $slot) {
        foreach ($inds as $id => $ind) $defNames[] = $slot . '_def' . str_replace('.', '', $id);
    }
    $s .= "VALUE LABELS\n" . spss_wrap_names($defNames) . "\n  0 'ไม่ปรากฏข้อบกพร่อง'\n  1 'ปรากฏข้อบกพร่อง'.\n";
    $s .= "VARIABLE LEVEL\n" . spss_wrap_names($defNames) . " (NOMINAL).\n\n";

    $w1def = []; $w2def = [];
    foreach ($inds as $id => $ind) {
        $w1def[] = 'w1_def' . str_replace('.', '', $id);
        $w2def[] = 'w2_def' . str_replace('.', '', $id);
    }
    $s .= "USE ALL.\n";
    $s .= "FILTER OFF.\n";
    $s .= "FILTER BY base14.\n\n";
    $s .= "FREQUENCIES VARIABLES=\n" . spss_wrap_names($w1def, 6) . "\n  /ORDER=ANALYSIS.\n\n";
    $s .= "FREQUENCIES VARIABLES=\n" . spss_wrap_names($w2def, 6) . "\n  /ORDER=ANALYSIS.\n\n";
    $s .= "NPAR TESTS\n";
    $s .= "  /MCNEMAR=\n" . spss_wrap_names($w1def, 6, '    ') . "\n  WITH\n"
        . spss_wrap_names($w2def, 6, '    ') . " (PAIRED)\n";
    $s .= "  /MISSING ANALYSIS.\n\n";
    $s .= "* คะแนนดิบเฉลี่ยรายตัวบ่งชี้ของทั้งสองครั้ง (ใช้บรรยายทิศทางการเปลี่ยนแปลงใต้ตาราง 14).\n";
    $s .= "DESCRIPTIVES VARIABLES=w1_i11 TO w2_i43 /STATISTICS=MEAN STDDEV MIN MAX.\n\n";
    $s .= "T-TEST PAIRS=w1_i11 TO w1_i43 WITH w2_i11 TO w2_i43 (PAIRED)\n";
    $s .= "  /ES DISPLAY(TRUE)\n";
    $s .= "  /CRITERIA=CI(.9500)\n";
    $s .= "  /MISSING=ANALYSIS.\n\n";
    $s .= "* จำนวนคำและคำสะกดผิดของผลงานทั้งสองครั้ง (ใช้ในหัวข้อ 2·4·1).\n";
    $s .= "DESCRIPTIVES VARIABLES=w1_words w2_words w1_miss w2_miss\n";
    $s .= "  /STATISTICS=MEAN STDDEV MIN MAX.\n\n";
    $s .= "FILTER OFF.\n\n";

    $s .= "* --- ตัวเลขที่ระบบคำนวณไว้แล้ว (ใช้เทียบกับผลที่ SPSS พิมพ์ออกมา) ---.\n";
    $s .= spss_note('ฐานการนับตาราง 14 = ' . $defects['n'] . ' คน');
    foreach ($defects['rows'] as $r) {
        $s .= spss_note($r['id'] . ' ' . $r['name'] . ': ครั้งที่ 1 = ' . $r['n1'] . ' คน ('
            . spss_n($r['pct1'], 1) . '%) · ครั้งที่ 2 = ' . $r['n2'] . ' คน (' . spss_n($r['pct2'], 1) . '%)');
    }
    $s .= "\n";

    /* ---------- 6) ความเที่ยงระหว่างผู้ประเมิน ---------- */
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 6) ความเที่ยงระหว่างผู้ประเมิน — Pearson r รายคู่ และ ICC(3,1)/ICC(3,k)\n";
    $s .= "*    two-way mixed effects, absolute agreement (ผู้ประเมินเป็นชุดคนตายตัว ไม่ได้สุ่มมา).\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    if (empty($rd['phases'])) {
        $s .= spss_note('ยังไม่มีรอบใดที่มีผู้ประเมินตั้งแต่ 2 คนขึ้นไปให้คะแนนผลงานชุดเดียวกัน จึงยังคำนวณส่วนนี้ไม่ได้');
        $s .= spss_note('เมื่อมีข้อมูลตรวจซ้ำแล้ว ให้ส่งออกชุดข้อมูลใหม่อีกครั้ง');
        $s .= "\n";
    } else {
        $s .= spss_get_data($pRater, $rvars, 'raters');
        $s .= spss_labels_block($rvars);
        foreach ($rd['phases'] as $phase => $p) {
            $names = [];
            for ($i = 1; $i <= $p['k']; $i++) $names[] = 'rater' . $i;
            $s .= spss_note('รอบ ' . $p['label'] . ' — ผู้ประเมิน ' . $p['k'] . ' คน · นักเรียน ' . $p['n'] . ' คน');
            foreach ($p['raters'] as $i => $key) {
                $s .= spss_note('  rater' . ($i + 1) . ' = ' . spss_rater_label($key));
            }
            $s .= "TEMPORARY.\n";
            $s .= "SELECT IF phase = " . $p['code'] . ".\n";
            $s .= "CORRELATIONS /VARIABLES=" . implode(' ', $names) . "\n";
            $s .= "  /PRINT=TWOTAIL NOSIG /MISSING=PAIRWISE.\n\n";
            $s .= "TEMPORARY.\n";
            $s .= "SELECT IF phase = " . $p['code'] . ".\n";
            $s .= "RELIABILITY /VARIABLES=" . implode(' ', $names) . "\n";
            $s .= "  /SCALE('" . str_replace("'", "''", $p['label']) . "') ALL\n";
            $s .= "  /MODEL=ALPHA\n";
            $s .= "  /ICC=MODEL(MIXED) TYPE(ABSOLUTE) CIN=95 TESTVAL=0.\n\n";
        }
        $s .= "* --- ค่าที่ระบบคำนวณไว้แล้ว ---.\n";
        foreach ($quant['interrater'] as $ir) {
            $s .= spss_note($ir['label'] . ': ICC(3,1) = ' . spss_n($ir['icc']['icc1'] ?? null, 3)
                . ' · ICC(3,k) = ' . spss_n($ir['icc']['iccK'] ?? null, 3)
                . ' · n = ' . $ir['n'] . ' · ผู้ประเมิน ' . $ir['k'] . ' คน');
            foreach ($ir['pearson'] as $pr) {
                $s .= spss_note('  r(' . spss_rater_label($pr['rater_a']) . ' , ' . spss_rater_label($pr['rater_b'])
                    . ') = ' . spss_n($pr['r'], 3) . ' p = ' . spss_n($pr['p'], 4));
            }
        }
        $s .= "\n";
    }

    /* ---------- 7) บันทึกไฟล์ ---------- */
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "* 7) บันทึกเป็นไฟล์ข้อมูลของ SPSS ไว้ใช้ต่อ.\n";
    $s .= "* ---------------------------------------------------------------------.\n";
    $s .= "DATASET ACTIVATE main.\n";
    $s .= "SAVE OUTFILE='" . $dir . $sep . "thaieasay_main.sav'\n  /COMPRESSED.\n";
    if (!empty($rd['phases'])) {
        $s .= "DATASET ACTIVATE raters.\n";
        $s .= "SAVE OUTFILE='" . $dir . $sep . "thaieasay_raters.sav'\n  /COMPRESSED.\n";
        $s .= "DATASET ACTIVATE main.\n";
    }
    $s .= "\n* จบไฟล์คำสั่ง.\n";
    return $s;
}

/* =========================================================================
 * ส่วนที่ 7  คู่มือย่อที่แนบไปกับชุดข้อมูล
 * ========================================================================= */

function spss_readme(array $ds, array $rd, $dir) {
    $meta = $ds['meta'];
    $n = count($ds['sids']);
    $t = "\xEF\xBB\xBF";
    $t .= "ชุดข้อมูลสำหรับ IBM SPSS Statistics\r\n";
    $t .= "ส่งออกจากระบบประเมินการเขียนเรียงความ เมื่อ " . date('d/m/Y H:i') . "\r\n";
    $t .= str_repeat('=', 70) . "\r\n\r\n";

    $t .= "ไฟล์ในชุดนี้\r\n";
    $t .= "  " . SPSS_FILE_DATA . "      ข้อมูลดิบ 1 แถว = นักเรียน 1 คน (" . $n . " คน)\r\n";
    $t .= "  " . SPSS_FILE_RATERS . "    คะแนนแยกรายผู้ประเมิน สำหรับคำนวณ ICC และค่าสหสัมพันธ์\r\n";
    $t .= "  " . SPSS_FILE_SYNTAX . "    ไฟล์คำสั่ง SPSS — เปิดแล้วสั่ง Run All ได้ทันที\r\n";
    $t .= "  " . SPSS_FILE_CODEBOOK . "  พจนานุกรมตัวแปร (แนบเป็นภาคผนวกของวิทยานิพนธ์ได้)\r\n";
    $t .= "  " . SPSS_FILE_README . "    ไฟล์นี้\r\n\r\n";

    $t .= "ขั้นตอนการใช้งาน\r\n";
    $t .= "  1. แตกไฟล์ทั้งหมดไว้ในโฟลเดอร์เดียวกัน แนะนำให้ใช้ " . $dir . "\r\n";
    $t .= "     (ถ้าใช้โฟลเดอร์อื่น ต้องแก้ path ในไฟล์คำสั่งด้วย ดูข้อ 3)\r\n";
    $t .= "  2. เปิด SPSS แล้วเลือก File > Open > Syntax เลือกไฟล์ " . SPSS_FILE_SYNTAX . "\r\n";
    $t .= "  3. ถ้าเก็บไฟล์ไว้โฟลเดอร์อื่น ให้แทนที่ข้อความ " . $dir . " ในไฟล์คำสั่งด้วยโฟลเดอร์จริง\r\n";
    $t .= "     (แทนที่ทุกแห่งที่พบ — อยู่ในบรรทัด /FILE= และ SAVE OUTFILE=)\r\n";
    $t .= "  4. กด Ctrl+A เลือกทั้งหมด แล้วกด Ctrl+R เพื่อสั่งรัน\r\n";
    $t .= "  5. ผลลัพธ์ทั้งหมดจะขึ้นในหน้าต่าง Output — บันทึกเป็น .spv หรือส่งออกเป็น Word/PDF\r\n\r\n";

    $t .= "ผลที่ได้ตรงกับส่วนใดของวิทยานิพนธ์\r\n";
    $t .= "  Paired-Samples T Test        ตาราง 12 (ค่า t, df, p, Cohen's d)\r\n";
    $t .= "  Descriptives                 ตาราง 12 (M และ SD ก่อน/หลังเรียน)\r\n";
    $t .= "  Tests of Normality           ข้อตกลงเบื้องต้นก่อนใช้ t-test (Shapiro-Wilk)\r\n";
    $t .= "  Wilcoxon Signed Ranks Test   ใช้แทน t-test เมื่อคะแนนผลต่างไม่เป็นการแจกแจงปกติ\r\n";
    $t .= "  Frequencies (w1_def / w2_def) ตาราง 14 (จำนวนและร้อยละผู้ปรากฏข้อบกพร่อง)\r\n";
    $t .= "  McNemar Test                 ทดสอบว่าสัดส่วนผู้มีข้อบกพร่องเปลี่ยนอย่างมีนัยสำคัญหรือไม่\r\n";
    $t .= "  Intraclass Correlation       ความเที่ยงระหว่างผู้ประเมิน ICC(3,1) และ ICC(3,k)\r\n";
    $t .= "  Correlations                 ค่าสหสัมพันธ์ระหว่างผู้ประเมินรายคู่ (Pearson r)\r\n\r\n";

    $t .= "นิยามเชิงปฏิบัติการที่ใช้ (เขียนอธิบายในบทที่ 3 ได้ตรงตามนี้)\r\n";
    $t .= "  คะแนนที่ใช้วิเคราะห์: " . $meta['score_source_label'] . "\r\n";
    $t .= "  " . $meta['defect_rule'] . "\r\n";
    $t .= "  ฐานการนับ: นับเฉพาะนักเรียนที่มีคะแนนครบทั้งสองครั้ง (ตัวแปร base12 และ base14)\r\n\r\n";

    $t .= "ข้อควรทราบ\r\n";
    $t .= "  - ตัวเลขที่ระบบคำนวณไว้แล้วแนบเป็นคอมเมนต์อยู่ในไฟล์คำสั่ง ใช้เทียบกับผลของ SPSS ได้ทันที\r\n";
    $t .= "    ถ้าตัวเลขไม่ตรงกัน ให้ตรวจว่าเปิดไฟล์ข้อมูลถูกไฟล์และตัวกรองกลุ่มตรงกันหรือไม่\r\n";
    $t .= "  - คอลัมน์ stu_name เป็นชื่อจริงของนักเรียน ให้ลบทิ้งก่อนแนบชุดข้อมูลเป็นภาคผนวก\r\n";
    $t .= "    (บทที่ 4 อ้างถึงนักเรียนด้วยเลขนิรนามในคอลัมน์ stu_no เท่านั้น)\r\n";
    $t .= "  - จำนวนคำสะกดผิดนับด้วยพจนานุกรมอัตโนมัติ คำวิสามานยนามอาจถูกนับเกินจริง\r\n";
    $t .= "    ควรสุ่มตรวจยืนยันก่อนรายงานเป็นตัวเลขในวิทยานิพนธ์\r\n";
    if (empty($rd['phases'])) {
        $t .= "  - ยังไม่มีรอบใดที่มีผู้ประเมินตั้งแต่ 2 คนขึ้นไปให้คะแนนผลงานชุดเดียวกัน\r\n";
        $t .= "    ส่วนความเที่ยงระหว่างผู้ประเมินจึงยังว่างอยู่ ให้ส่งออกใหม่เมื่อมีข้อมูลตรวจซ้ำแล้ว\r\n";
    }
    $t .= "  - ไฟล์ CSV เข้ารหัส UTF-8 ไฟล์คำสั่งประกาศ /ENCODING='UTF8' ไว้ให้แล้ว\r\n";
    $t .= "    ถ้าเปิดใน Excel แล้วภาษาไทยเพี้ยน ห้ามกดบันทึกทับ เพราะจะทำให้ SPSS อ่านไม่ออก\r\n";
    return $t;
}

/* =========================================================================
 * ส่วนที่ 8  ตัวเขียนไฟล์ zip (แบบไม่บีบอัด — ไม่ต้องพึ่งส่วนขยายใด ๆ ของ PHP)
 * ========================================================================= */

function spss_zip(array $files) {
    $now   = getdate();
    $dTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | (int)($now['seconds'] / 2);
    $dDate = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

    $local = ''; $central = ''; $offset = 0; $count = 0;
    foreach ($files as $name => $content) {
        $crc = crc32($content);
        $len = strlen($content);
        $head = "PK\x03\x04" . pack('v', 20) . pack('v', 0) . pack('v', 0)
              . pack('v', $dTime) . pack('v', $dDate)
              . pack('V', $crc) . pack('V', $len) . pack('V', $len)
              . pack('v', strlen($name)) . pack('v', 0) . $name;
        $local .= $head . $content;
        $central .= "PK\x01\x02" . pack('v', 20) . pack('v', 20) . pack('v', 0) . pack('v', 0)
                  . pack('v', $dTime) . pack('v', $dDate)
                  . pack('V', $crc) . pack('V', $len) . pack('V', $len)
                  . pack('v', strlen($name)) . pack('v', 0) . pack('v', 0)
                  . pack('v', 0) . pack('v', 0) . pack('V', 32)
                  . pack('V', $offset) . $name;
        $offset += strlen($head) + $len;
        $count++;
    }
    return $local . $central . "PK\x05\x06" . pack('v', 0) . pack('v', 0)
        . pack('v', $count) . pack('v', $count)
        . pack('V', strlen($central)) . pack('V', $offset) . pack('v', 0);
}

/** ส่งไฟล์เดียวออกไปเป็นไฟล์ดาวน์โหลด */
function spss_send($filename, $mime, $content) {
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo $content;
}

/* =========================================================================
 * ส่วนที่ 9  เดินเรื่องหลัก
 * ========================================================================= */

try {
    if ($targetDir === '') $targetDir = SPSS_DEFAULT_DIR;
    // path ของ SPSS อยู่ในเครื่องหมายคำพูดเดี่ยว จึงต้องกันอัญประกาศเดี่ยวและอักขระควบคุมออก
    $targetDir = preg_replace('/[\'"\r\n]+/u', '', $targetDir);

    $ds = ch45_dataset($pdo, ['group' => $group, 'classroom' => $classroom]);
    if (!$ds['sids']) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'ไม่พบนักเรียนในขอบเขตที่เลือก จึงยังส่งออกชุดข้อมูลไม่ได้ — ลองเปลี่ยนกลุ่มหรือห้องเรียนที่กรอง';
        exit;
    }

    // ความเที่ยงระหว่างผู้ประเมินคำนวณจากกลุ่มที่มีการตรวจซ้ำเสมอ (เช่นเดียวกับที่หน้าบทที่ 4-5 ทำ)
    $iccDs = ($ds['filter']['group'] === CH45_ICC_GROUP && $ds['filter']['classroom'] === '')
        ? $ds
        : ch45_dataset($pdo, ['group' => CH45_ICC_GROUP, 'classroom' => '']);

    $mech = [];
    try { $mech = ch45_mechanics($pdo, $ds); } catch (Exception $e) { $mech = []; }

    $meta      = $ds['meta'];
    $vars      = spss_variables($meta);
    $rd        = spss_rater_dataset($iccDs);
    $raterVars = spss_rater_variables($rd);

    switch ($which) {
        case 'data':
            spss_send(SPSS_FILE_DATA, 'text/csv; charset=utf-8', spss_csv($vars, spss_wide_rows($ds, $mech)));
            break;

        case 'raters':
            spss_send(SPSS_FILE_RATERS, 'text/csv; charset=utf-8', spss_csv($raterVars, $rd['rows']));
            break;

        case 'codebook':
            spss_send(SPSS_FILE_CODEBOOK, 'text/csv; charset=utf-8', spss_codebook($vars, $raterVars, $meta));
            break;

        case 'readme':
            spss_send(SPSS_FILE_README, 'text/plain; charset=utf-8', spss_readme($ds, $rd, $targetDir));
            break;

        case 'syntax':
            spss_send(SPSS_FILE_SYNTAX, 'text/plain; charset=utf-8',
                spss_syntax($ds, ch45_quant($ds, $iccDs), ch45_defects($ds), $rd, $targetDir));
            break;

        default:
            $quant   = ch45_quant($ds, $iccDs);
            $defects = ch45_defects($ds);
            $files = [
                SPSS_FILE_DATA     => spss_csv($vars, spss_wide_rows($ds, $mech)),
                SPSS_FILE_RATERS   => spss_csv($raterVars, $rd['rows']),
                SPSS_FILE_SYNTAX   => spss_syntax($ds, $quant, $defects, $rd, $targetDir),
                SPSS_FILE_CODEBOOK => spss_codebook($vars, $raterVars, $meta),
                SPSS_FILE_README   => spss_readme($ds, $rd, $targetDir),
            ];
            spss_send('thaieasay_spss_' . date('Ymd') . '.zip', 'application/zip', spss_zip($files));
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'เกิดข้อผิดพลาดในการเตรียมชุดข้อมูล: ' . $e->getMessage();
}
