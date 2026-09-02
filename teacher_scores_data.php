<?php
/**
 * teacher_scores_data.php — ชั้นข้อมูลของ "ตารางคะแนนที่ครูเป็นผู้ประเมิน"
 * ---------------------------------------------------------------------------
 * ในการตัดสินผลการเรียน ระบบใช้ "คะแนนที่ครูเป็นผู้ประเมินเท่านั้น"
 * คะแนนประเมินตนเอง คะแนนจากเพื่อน และคะแนนของผู้เชี่ยวชาญเป็นข้อมูลประกอบ
 * ไม่ถูกนำมารวมในตารางชุดนี้แม้แต่ค่าเดียว
 *
 * รอบที่ครูให้คะแนน มี 4 รอบ และจับคู่กันตายตัวตามที่ครูกำหนดไว้ทั้งระบบ
 *
 *      ก่อนเรียน (pretest)                ↔  หลังเรียน (posttest)
 *      หน่วยที่ 1 ร่างที่ 2 (task1)        ↔  หน่วยที่ 2 ร่างที่ 2 (task2)
 *
 * คู่แรกวัด "พัฒนาการจากการสอนทั้งหน่วย" คู่ที่สองวัด "การเปลี่ยนแปลงระหว่างภาระงานสองหน่วย"
 * ทั้งสองคู่คิดสถิติแยกกันคนละชุด และแต่ละคู่ใช้เฉพาะนักเรียนที่มีคะแนน "ครบทั้งสองรอบของคู่นั้น"
 * ค่า n ของสองคู่จึงไม่จำเป็นต้องเท่ากัน และระบบแสดง n ที่ใช้จริงกำกับไว้ทุกตาราง
 *
 * ภาระงานแต่ละหน่วยมีสองร่าง แต่ครูให้คะแนนเฉพาะร่างที่ 2 (ตรงกับ evaluation.php)
 * คะแนนจึงถูกบันทึกในรอบ task1 / task2 โดยอ่านจากเรียงความ task1_d2 / task2_d2
 *
 * ตัวเลขทุกตัวคำนวณด้วยฟังก์ชันสถิติชุดเดียวกับบทที่ 4-5 (chapter45_stats.php)
 * ซึ่งตรวจสอบผลกับ SciPy ไว้แล้ว จึงรายงานในวิทยานิพนธ์ได้โดยไม่ต้องคำนวณซ้ำ
 *
 * ไฟล์นี้ "อ่านอย่างเดียว" ไม่แก้ไขข้อมูลใด ๆ
 */

require_once 'chapter45_data.php';

/* =========================================================================
 * ส่วนที่ 1  นิยามรอบและคู่เทียบ
 * ========================================================================= */

/**
 * รอบที่ครูให้คะแนนทั้ง 4 รอบ
 *   eval  = รอบในตาราง evaluations (test_phase)
 *   essay = รอบเรียงความที่ครูอ่านตอนให้คะแนน (ภาระงานให้คะแนนเฉพาะร่างที่ 2)
 */
function ts_rounds() {
    return [
        'pretest'  => ['key' => 'pretest',  'essay' => 'pretest',
                       'label' => 'ก่อนเรียน', 'short' => 'ก่อนเรียน',
                       'full'  => 'ก่อนเรียน (Pre-test)'],
        'task1'    => ['key' => 'task1',    'essay' => 'task1_d2',
                       'label' => 'หน่วยที่ 1 · ร่างที่ 2', 'short' => 'หน่วย 1 (ร่าง 2)',
                       'full'  => 'ภาระงานหน่วยที่ 1 ร่างที่ 2'],
        'task2'    => ['key' => 'task2',    'essay' => 'task2_d2',
                       'label' => 'หน่วยที่ 2 · ร่างที่ 2', 'short' => 'หน่วย 2 (ร่าง 2)',
                       'full'  => 'ภาระงานหน่วยที่ 2 ร่างที่ 2'],
        'posttest' => ['key' => 'posttest', 'essay' => 'posttest',
                       'label' => 'หลังเรียน', 'short' => 'หลังเรียน',
                       'full'  => 'หลังเรียน (Post-test)'],
    ];
}

/**
 * คู่ที่ต้องนำมาเทียบกัน — คิดสถิติแยกกันคนละชุด ไม่ปนกัน
 * ลำดับ a → b คือ "รอบแรก → รอบหลัง" ผลต่างจึงเป็น b − a
 */
function ts_pairs() {
    return [
        'prepost' => [
            'key'   => 'prepost',
            'a'     => 'pretest',
            'b'     => 'posttest',
            'label' => 'ก่อนเรียน → หลังเรียน',
            'note'  => 'วัดพัฒนาการจากการจัดการเรียนการสอนทั้งหมด (คนละหัวข้อ เป็นงานเขียนคนละชิ้น)',
        ],
        'units' => [
            'key'   => 'units',
            'a'     => 'task1',
            'b'     => 'task2',
            'label' => 'หน่วยที่ 1 (ร่างที่ 2) → หน่วยที่ 2 (ร่างที่ 2)',
            'note'  => 'เทียบผลงานปลายหน่วยสองหน่วย งานเขียนคนละประเภท จึงอ่านผลเป็นการปรับกลวิธี ไม่ใช่พัฒนาการล้วน ๆ',
        ],
    ];
}

/** คะแนนเต็มของทั้งฉบับตามเกณฑ์ของครู (11 ตัวบ่งชี้) */
function ts_full_max() {
    $sum = 0.0;
    foreach (ch45_indicators() as $ind) $sum += (float)$ind['max'];
    return $sum;   // 60
}

/** ตัวบ่งชี้ที่ระบบตรวจได้ (ใช้ทำสเกลเทียบระบบกับครูให้เป็นสเกลเดียวกัน) */
function ts_ai_indicator_ids() {
    $out = [];
    foreach (ch45_indicators() as $id => $ind) {
        if (!empty($ind['ai_scored'])) $out[] = $id;
    }
    return $out;
}

/** คะแนนเต็มเฉพาะข้อที่ระบบตรวจได้ */
function ts_ai_max() {
    $sum = 0.0;
    foreach (ch45_indicators() as $ind) {
        if (!empty($ind['ai_scored'])) $sum += (float)$ind['max'];
    }
    return $sum;   // 58
}

/* =========================================================================
 * ส่วนที่ 2  ดึงคะแนนรายคนรายรอบ
 * ========================================================================= */

/**
 * คะแนนของครูในรอบหนึ่งของนักเรียนหนึ่งคน
 * คืน null เมื่อ "ครูยังไม่ได้ตรวจรอบนี้" — ไม่คืนเลข 0 เพราะยังไม่ตรวจกับได้ 0 คะแนนไม่ใช่เรื่องเดียวกัน
 */
function ts_teacher_score(array $ds, $sid, $round) {
    $sc = ch45_scores_of($ds, $sid, $round, 'teacher');
    if (!$sc) return null;

    // คะแนนเฉพาะข้อที่ระบบตรวจได้ ไว้เทียบกับคะแนนอัตโนมัติบนสเกลเดียวกัน
    $aiScale = 0.0; $aiScaleOk = true;
    foreach (ts_ai_indicator_ids() as $id) {
        if (!isset($sc['weighted'][$id]) || $sc['weighted'][$id] === null) { $aiScaleOk = false; break; }
        $aiScale += (float)$sc['weighted'][$id];
    }

    $raters = [];
    foreach ($ds['evals'][$sid][$round]['teacher'] ?? [] as $one) {
        if (trim((string)$one['rater']) !== '') $raters[] = trim((string)$one['rater']);
    }

    return [
        'total'    => $sc['total'],
        'complete' => $sc['complete'],
        'level'    => ($sc['total'] === null) ? '' : ai_quality_level($sc['total']),
        'weighted' => $sc['weighted'],
        'raw'      => $sc['raw'],
        'domains'  => ch45_domain_total($sc['weighted']),
        'ai_scale' => $aiScaleOk ? round($aiScale, 2) : null,
        'raters'   => array_values(array_unique($raters)),
    ];
}

/**
 * คะแนนที่ระบบให้กับเรียงความของรอบนั้น (คะแนนหลังครูปรับรายข้อแล้ว ถ้ามีการปรับ)
 * คืน null เมื่อระบบยังไม่ได้ตรวจฉบับนี้
 */
function ts_ai_score(array $ds, $sid, $round) {
    $rounds = ts_rounds();
    $phase  = $rounds[$round]['essay'] ?? '';
    if ($phase === '' || !isset($ds['ai'][$sid][$phase])) return null;

    $row = $ds['ai'][$sid][$phase];
    $max = (float)($row['max'] ?? 0);
    if ($max <= 0) $max = ts_ai_max();

    return [
        'total'    => round((float)$row['total'], 2),
        'max'      => $max,
        'level'    => (string)($row['level'] ?? ''),
        // คะแนนดั้งเดิมของระบบก่อนครูปรับรายข้อ และจำนวนข้อที่ถูกปรับ
        'ai_total' => isset($row['ai_total']) ? round((float)$row['ai_total'], 2) : null,
        'override' => (int)($row['override_count'] ?? 0),
    ];
}

/* =========================================================================
 * ส่วนที่ 3  สถิติของคู่เทียบ
 * ========================================================================= */

/**
 * สถิติหนึ่งแถวของตารางเปรียบเทียบ: M, SD ของสองรอบ + ผลต่าง + t, df, p + ขนาดอิทธิพล
 * $a, $b เป็นคะแนนที่ "เรียงตรงคู่กันรายคน" แล้ว
 */
function ts_compare_row($key, $label, $max, array $a, array $b) {
    $da = ch45_describe($a);
    $db = ch45_describe($b);
    $tt = ch45_paired_t($a, $b);
    return [
        'key'      => $key,
        'label'    => $label,
        'max'      => $max,
        'n'        => $tt['n'],
        'a_mean'   => $da['mean'], 'a_sd' => $da['sd'],
        'b_mean'   => $db['mean'], 'b_sd' => $db['sd'],
        'a_pct'    => ($da['mean'] === null || $max <= 0) ? null : $da['mean'] * 100 / $max,
        'b_pct'    => ($db['mean'] === null || $max <= 0) ? null : $db['mean'] * 100 / $max,
        'diff'     => $tt['mean_diff'],
        'sd_diff'  => $tt['sd_diff'],
        't'        => $tt['t'],
        'df'       => $tt['df'],
        'p'        => $tt['p'],
        'sig'      => ($tt['p'] !== null && $tt['p'] < 0.05),
        'dz'       => $tt['dz'],
        'effect'   => ch45_effect_label($tt['dz']),
        'ci_low'   => $tt['ci_low'],
        'ci_high'  => $tt['ci_high'],
    ];
}

/**
 * สถิติของคู่หนึ่ง (ภาพรวม + 4 ด้าน) จากคะแนนครูอย่างเดียว
 * ใช้เฉพาะนักเรียนที่มีคะแนนครบทั้งสองรอบของคู่นั้น
 */
function ts_teacher_pair(array $ds, array $pair, array $scores) {
    $a = $pair['a']; $b = $pair['b'];

    $ids = []; $va = []; $vb = [];
    foreach ($ds['sids'] as $sid) {
        $sa = $scores[$sid][$a] ?? null;
        $sb = $scores[$sid][$b] ?? null;
        if (!$sa || !$sb || $sa['total'] === null || $sb['total'] === null) continue;
        $ids[] = $sid;
        $va[]  = $sa['total'];
        $vb[]  = $sb['total'];
    }

    $rows = [ts_compare_row('overall', 'ภาพรวม', ts_full_max(), $va, $vb)];
    foreach (ch45_domains() as $k => $d) {
        $xa = []; $xb = [];
        foreach ($ids as $sid) {
            $pa = $scores[$sid][$a]['domains'][$k] ?? null;
            $pb = $scores[$sid][$b]['domains'][$k] ?? null;
            if ($pa === null || $pb === null) continue;
            $xa[] = $pa; $xb[] = $pb;
        }
        $rows[] = ts_compare_row($k, 'ด้านที่ ' . $d['no'] . ' ' . $d['name'], (float)$d['max'], $xa, $xb);
    }

    // ตรวจการแจกแจงปกติของคะแนนผลต่างภาพรวม — ถ้าไม่ปกติต้องรายงานอย่างระมัดระวัง
    $diff = [];
    for ($i = 0; $i < count($va); $i++) $diff[] = $vb[$i] - $va[$i];

    return [
        'key'       => $pair['key'],
        'label'     => $pair['label'],
        'note'      => $pair['note'],
        'a'         => $a,
        'b'         => $b,
        'n'         => count($ids),
        'sids'      => $ids,
        'rows'      => $rows,
        'by_key'    => array_column($rows, null, 'key'),
        'normality' => ch45_shapiro_wilk($diff),
    ];
}

/** สถิติของคู่หนึ่งจากคะแนนอัตโนมัติ (ภาพรวมอย่างเดียว — ระบบตรวจได้ 10 ข้อ เต็ม 58) */
function ts_ai_pair(array $ds, array $pair, array $aiScores) {
    $a = $pair['a']; $b = $pair['b'];
    $ids = []; $va = []; $vb = [];
    foreach ($ds['sids'] as $sid) {
        $sa = $aiScores[$sid][$a] ?? null;
        $sb = $aiScores[$sid][$b] ?? null;
        if (!$sa || !$sb) continue;
        $ids[] = $sid;
        $va[]  = $sa['total'];
        $vb[]  = $sb['total'];
    }
    $row = ts_compare_row('overall', 'ภาพรวม (เฉพาะข้อที่ระบบตรวจได้)', ts_ai_max(), $va, $vb);
    return [
        'key'   => $pair['key'],
        'label' => $pair['label'],
        'note'  => $pair['note'],
        'a'     => $a,
        'b'     => $b,
        'n'     => count($ids),
        'sids'  => $ids,
        'row'   => $row,
    ];
}

/**
 * เทียบคะแนนอัตโนมัติกับคะแนนครูในรอบเดียวกัน บนสเกลเดียวกัน (เฉพาะ 10 ข้อที่ระบบตรวจได้ เต็ม 58)
 * ใช้เฉพาะนักเรียนที่ "มีทั้งคะแนนครูและคะแนนอัตโนมัติ" ของรอบนั้น
 */
function ts_ai_vs_teacher(array $ds, $round, array $scores, array $aiScores) {
    $t = []; $a = [];
    foreach ($ds['sids'] as $sid) {
        $st = $scores[$sid][$round] ?? null;
        $sa = $aiScores[$sid][$round] ?? null;
        if (!$st || !$sa || $st['ai_scale'] === null) continue;
        $t[] = $st['ai_scale'];
        $a[] = $sa['total'];
    }
    $row = ts_compare_row($round, ts_rounds()[$round]['label'], ts_ai_max(), $t, $a);
    $row['r'] = ch45_pearson($t, $a);
    return $row;
}

/* =========================================================================
 * ส่วนที่ 4  ประกอบรายงานทั้งชุด
 * ========================================================================= */

/**
 * รายงาน "ตารางคะแนนที่ครูเป็นผู้ประเมิน" ทั้งชุด
 * $opt = ['group' => กลุ่มการวิจัย, 'classroom' => ห้องเรียน]
 *
 * คืนค่า:
 *   meta        ขอบเขตข้อมูล คะแนนเต็ม และเวลาที่ออกรายงาน
 *   rounds      นิยาม 4 รอบ
 *   students[]  คะแนนครู + คะแนนอัตโนมัติ + สถานะการส่งงาน รายคนรายรอบ
 *   columns     n, M, SD, ต่ำสุด-สูงสุด ของคะแนนครูแต่ละรอบ (ทุกคนที่ตรวจแล้ว)
 *   ai_columns  เช่นเดียวกันของคะแนนอัตโนมัติ
 *   pairs       สถิติจับคู่ของคะแนนครู 2 คู่ (ภาพรวม + 4 ด้าน)
 *   ai_pairs    สถิติจับคู่ของคะแนนอัตโนมัติ 2 คู่ (ภาพรวม)
 *   agreement   เทียบระบบกับครูรายรอบบนสเกล 58
 *   pending     รายการที่ครูยังไม่ได้ตรวจ และรายการที่ระบบยังไม่ได้ตรวจ
 */
function ts_report(PDO $pdo, array $opt = []) {
    return ts_build_report(ch45_dataset($pdo, $opt), $opt);
}

/**
 * ประกอบรายงานจากชุดข้อมูลที่ดึงมาแล้ว
 * แยกจาก ts_report() เพื่อให้ทดสอบการคำนวณได้โดยไม่ต้องต่อฐานข้อมูลจริง
 */
function ts_build_report(array $ds, array $opt = []) {
    $rounds = ts_rounds();

    $scores   = [];   // [sid][round] = คะแนนครู
    $aiScores = [];   // [sid][round] = คะแนนอัตโนมัติ
    $students = [];

    foreach ($ds['sids'] as $sid) {
        $stu = $ds['students'][$sid];
        $row = [
            'id'        => $sid,
            'no'        => $stu['no'],
            // ตัดคำนำหน้าชื่อออกให้ตารางอ่านง่าย (formatNamePrefix อยู่ใน auth_helper.php ซึ่งหน้าเว็บโหลดไว้แล้ว)
            'name'      => function_exists('formatNamePrefix') ? formatNamePrefix($stu['name']) : $stu['name'],
            'classroom' => $stu['classroom'],
            'group'     => $stu['group'],
            'teacher'   => [],
            'ai'        => [],
            'essay'     => [],
            'teacher_done' => 0,
            'ai_done'      => 0,
        ];
        foreach ($rounds as $rk => $r) {
            $t = ts_teacher_score($ds, $sid, $rk);
            $a = ts_ai_score($ds, $sid, $rk);
            $scores[$sid][$rk]   = $t;
            $aiScores[$sid][$rk] = $a;
            $row['teacher'][$rk] = $t;
            $row['ai'][$rk]      = $a;
            $row['essay'][$rk]   = !empty($ds['essays'][$sid][$r['essay']]['has']);
            if ($t && $t['total'] !== null) $row['teacher_done']++;
            if ($a) $row['ai_done']++;
        }
        $students[] = $row;
    }

    // ---- สถิติรายคอลัมน์ (ทุกคนที่มีคะแนนในรอบนั้น ไม่บังคับว่าต้องครบคู่) ----
    $columns = []; $aiColumns = [];
    foreach ($rounds as $rk => $r) {
        $tv = []; $av = [];
        foreach ($ds['sids'] as $sid) {
            if (!empty($scores[$sid][$rk]) && $scores[$sid][$rk]['total'] !== null) $tv[] = $scores[$sid][$rk]['total'];
            if (!empty($aiScores[$sid][$rk])) $av[] = $aiScores[$sid][$rk]['total'];
        }
        $dt = ch45_describe($tv);
        $da = ch45_describe($av);
        $total = count($ds['sids']);
        $columns[$rk]   = $dt + ['missing' => $total - $dt['n'], 'max_score' => ts_full_max()];
        $aiColumns[$rk] = $da + ['missing' => $total - $da['n'], 'max_score' => ts_ai_max()];
    }

    // ---- สถิติจับคู่ — สองคู่คิดแยกกัน แต่ละคู่มี n ของตัวเอง ----
    $pairs = []; $aiPairs = [];
    foreach (ts_pairs() as $pk => $pair) {
        $pairs[$pk]   = ts_teacher_pair($ds, $pair, $scores);
        $aiPairs[$pk] = ts_ai_pair($ds, $pair, $aiScores);
    }

    // ---- เทียบระบบกับครูรายรอบ ----
    $agreement = [];
    foreach ($rounds as $rk => $r) {
        $agreement[$rk] = ts_ai_vs_teacher($ds, $rk, $scores, $aiScores);
    }

    // ---- รายการที่ยังไม่ได้ตรวจ ----
    $pending = ts_pending($ds, $students);

    return [
        'meta' => [
            'group'        => (string)($opt['group'] ?? ''),
            'classroom'    => (string)($opt['classroom'] ?? ''),
            'n_students'   => count($ds['sids']),
            'full_max'     => ts_full_max(),
            'ai_max'       => ts_ai_max(),
            'manual_max'   => ts_full_max() - ts_ai_max(),
            'generated_at' => date('Y-m-d H:i:s'),
            'evaluator'    => 'ครูประเมิน',
        ],
        'rounds'     => $rounds,
        'pair_defs'  => ts_pairs(),
        'domains'    => ch45_domains(),
        'students'   => $students,
        'columns'    => $columns,
        'ai_columns' => $aiColumns,
        'pairs'      => $pairs,
        'ai_pairs'   => $aiPairs,
        'agreement'  => $agreement,
        'pending'    => $pending,
    ];
}

/**
 * รายการงานที่ยังไม่ได้ตรวจ — แยกให้ชัดว่า "ตรวจได้เลย" กับ "ยังส่งงานไม่ถึงมือ"
 *
 *   teacher.ready   นักเรียนส่งเรียงความแล้ว แต่ครูยังไม่ได้ให้คะแนน → กดลิงก์ไปตรวจได้ทันที
 *   teacher.no_work นักเรียนยังไม่ส่งเรียงความรอบนั้น → ยังตรวจไม่ได้ ต้องตามงานก่อน
 *   ai.ready        มีเรียงความแล้วแต่ระบบยังไม่ได้ตรวจ
 */
function ts_pending(array $ds, array $students) {
    $rounds = ts_rounds();
    $out = [
        'teacher' => ['ready' => [], 'no_work' => []],
        'ai'      => ['ready' => []],
        'count'   => ['teacher_ready' => 0, 'teacher_no_work' => 0, 'ai_ready' => 0],
        'by_round'=> [],
    ];
    foreach ($rounds as $rk => $r) {
        $out['by_round'][$rk] = ['teacher_ready' => 0, 'teacher_no_work' => 0, 'ai_ready' => 0, 'teacher_done' => 0];
    }

    foreach ($students as $s) {
        foreach ($rounds as $rk => $r) {
            $hasEssay   = !empty($s['essay'][$rk]);
            $hasTeacher = (!empty($s['teacher'][$rk]) && $s['teacher'][$rk]['total'] !== null);
            $hasAi      = !empty($s['ai'][$rk]);

            if ($hasTeacher) {
                $out['by_round'][$rk]['teacher_done']++;
            } else {
                $item = [
                    'id'    => $s['id'],
                    'name'  => $s['name'],
                    'classroom' => $s['classroom'],
                    'round' => $rk,
                    'round_label' => $r['label'],
                    'essay_phase' => $r['essay'],
                    'has_essay'   => $hasEssay,
                ];
                if ($hasEssay) {
                    $out['teacher']['ready'][] = $item;
                    $out['by_round'][$rk]['teacher_ready']++;
                    $out['count']['teacher_ready']++;
                } else {
                    $out['teacher']['no_work'][] = $item;
                    $out['by_round'][$rk]['teacher_no_work']++;
                    $out['count']['teacher_no_work']++;
                }
            }

            if (!$hasAi && $hasEssay) {
                $out['ai']['ready'][] = [
                    'id'    => $s['id'],
                    'name'  => $s['name'],
                    'classroom' => $s['classroom'],
                    'round' => $rk,
                    'round_label' => $r['label'],
                    'essay_phase' => $r['essay'],
                ];
                $out['by_round'][$rk]['ai_ready']++;
                $out['count']['ai_ready']++;
            }
        }
    }
    return $out;
}

/* =========================================================================
 * ส่วนที่ 5  ตัวช่วยจัดรูปแบบตัวเลขสำหรับหน้าเว็บและไฟล์ส่งออก
 * ========================================================================= */

/** แสดงตัวเลขทศนิยม 2 ตำแหน่ง — ค่าที่ยังไม่มีข้อมูลแสดงเป็นขีด ไม่ใช่ 0.00 */
function ts_num($v, $digits = 2) {
    return ($v === null || $v === '') ? '—' : number_format((float)$v, $digits);
}

/** ค่า p ตามธรรมเนียมการรายงานผลวิจัย */
function ts_p($p) {
    if ($p === null) return '—';
    return ($p < 0.001) ? '< .001' : number_format((float)$p, 3);
}

/**
 * ค่า p สำหรับแสดงบนหน้าเว็บ — ต้องหนีอักขระ '<' ของ "< .001" มิฉะนั้นเบราว์เซอร์จะกลืนไปเป็นแท็ก
 * (ฝั่งไฟล์ CSV ให้ใช้ ts_p() ตามเดิม เพราะไม่ใช่ HTML)
 */
function ts_p_html($p) {
    return htmlspecialchars(ts_p($p), ENT_QUOTES, 'UTF-8');
}

/** ห้องเรียนทั้งหมดที่มีในระบบ ไว้ทำตัวกรอง */
function ts_classrooms(PDO $pdo) {
    $out = [];
    try {
        $stmt = $pdo->query("SELECT DISTINCT classroom FROM students
                             WHERE classroom IS NOT NULL AND classroom <> ''
                             ORDER BY classroom ASC");
        foreach ($stmt->fetchAll() as $r) $out[] = (string)$r['classroom'];
    } catch (Exception $e) { /* ฐานข้อมูลเก่าอาจยังไม่มีคอลัมน์ห้องเรียน */ }
    return $out;
}
