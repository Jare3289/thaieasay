<?php
/**
 * report_data.php — ชั้นรวบรวมข้อมูลสำหรับ "รายงานผลการเรียนรู้"
 * ---------------------------------------------------------------------------
 * ใช้ร่วมกัน 2 เอกสาร:
 *   - student_report_print.php : รายงานรายบุคคล (เก็บเป็นแฟ้มสะสมงานของนักเรียนแต่ละคน)
 *   - class_report_print.php   : รายงานภาพรวมทั้งชั้นเรียน (สรุปให้คุณครู)
 *
 * ไฟล์นี้ "อ่านอย่างเดียว" ไม่แก้ไขฐานข้อมูลใด ๆ และดึงข้อมูลแบบรวมทีเดียว
 * (query ละไม่กี่ครั้งแล้วจัดกลุ่มในหน่วยความจำ) เพื่อให้พิมพ์รายงานทั้งห้องได้เร็ว
 *
 * แหล่งข้อมูล:
 *   students / evaluations           → ผลสัมฤทธิ์ (คะแนนครู เพื่อน ตนเอง ผู้เชี่ยวชาญ)
 *   student_essays                   → ผลงาน (เรียงความแต่ละรอบ)
 *   essay_ai_feedback                → ผลตรวจของ AI และพัฒนาการระหว่างรอบตรวจ
 *   writing_problems / self_checklists / learning_reflections → เครื่องมือสะท้อนคิด
 *   peer_reviews                     → ข้อคิดเห็นเชิงคุณภาพจากเพื่อน
 */

require_once 'ai_config.php';

/** เกณฑ์การประเมิน 11 ข้อ พร้อมชื่อคอลัมน์ในตาราง evaluations (score_1_1 …) */
function report_criteria() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    foreach (ai_rubric() as $it) {
        $cache[] = [
            'id'   => $it['id'],
            'col'  => 'score_' . str_replace('.', '_', $it['id']),
            'name' => $it['name'],
            'max'  => (float)$it['max'],
        ];
    }
    return $cache;
}

/** รอบการประเมินในตาราง evaluations (ครูให้คะแนน 4 รอบนี้) */
function report_eval_phases() {
    return [
        'pretest'  => 'ก่อนเรียน (Pre-test)',
        'task1'    => 'ภาระงานหน่วยที่ 1',
        'task2'    => 'ภาระงานหน่วยที่ 2',
        'posttest' => 'หลังเรียน (Post-test)',
    ];
}

/** รอบงานเขียนเรียงความทั้งหมด (รวมร่างที่ 1 ของแต่ละหน่วย) */
function report_essay_phases() {
    $out = [];
    foreach (ai_all_phases() as $p) $out[$p] = ai_phase_label($p);
    return $out;
}

/** ชื่อผู้ประเมินแต่ละประเภทตามที่บันทึกไว้ในฐานข้อมูล → คีย์สั้นที่ใช้ในรายงาน */
function report_evaluator_map() {
    return [
        'ครูประเมิน'           => 'teacher',
        'ตนเองประเมิน'         => 'self',
        'เพื่อนประเมิน'        => 'peer',
        'ผู้เชี่ยวชาญประเมิน'  => 'expert',
    ];
}

/** ชิ้นงานที่นับว่า "ครบ" ในรายงาน (12 ชิ้น — ชุดเดียวกับหน้ารายงานการส่งงาน) */
function report_work_items() {
    return [
        ['key' => 'essay:pretest',   'label' => 'เรียงความก่อนเรียน',            'group' => 'ก่อนเรียน'],
        ['key' => 'essay:task1_d1',  'label' => 'ภาระงานหน่วยที่ 1 · ร่างที่ 1', 'group' => 'หน่วยที่ 1'],
        ['key' => 'essay:task1_d2',  'label' => 'ภาระงานหน่วยที่ 1 · ร่างที่ 2', 'group' => 'หน่วยที่ 1'],
        ['key' => 'tool:problems:1', 'label' => 'บันทึกปัญหาการเขียน หน่วยที่ 1','group' => 'หน่วยที่ 1'],
        ['key' => 'tool:checklist:1','label' => 'ตรวจสอบตนเอง หน่วยที่ 1',       'group' => 'หน่วยที่ 1'],
        ['key' => 'tool:reflection:1','label' => 'สะท้อนการเรียนรู้ หน่วยที่ 1', 'group' => 'หน่วยที่ 1'],
        ['key' => 'essay:task2_d1',  'label' => 'ภาระงานหน่วยที่ 2 · ร่างที่ 1', 'group' => 'หน่วยที่ 2'],
        ['key' => 'essay:task2_d2',  'label' => 'ภาระงานหน่วยที่ 2 · ร่างที่ 2', 'group' => 'หน่วยที่ 2'],
        ['key' => 'tool:problems:2', 'label' => 'บันทึกปัญหาการเขียน หน่วยที่ 2','group' => 'หน่วยที่ 2'],
        ['key' => 'tool:checklist:2','label' => 'ตรวจสอบตนเอง หน่วยที่ 2',       'group' => 'หน่วยที่ 2'],
        ['key' => 'tool:reflection:2','label' => 'สะท้อนการเรียนรู้ หน่วยที่ 2', 'group' => 'หน่วยที่ 2'],
        ['key' => 'essay:posttest',  'label' => 'เรียงความหลังเรียน',            'group' => 'หลังเรียน'],
    ];
}

/* ------------------------------------------------------------------ สถิติพื้นฐาน */

/** ค่าเฉลี่ย (คืน null เมื่อไม่มีข้อมูล เพื่อให้รายงานแสดงขีดกลางแทนเลข 0) */
function report_mean(array $vals) {
    $vals = array_values(array_filter($vals, function ($v) { return $v !== null && $v !== ''; }));
    if (!$vals) return null;
    return array_sum($vals) / count($vals);
}

/** ส่วนเบี่ยงเบนมาตรฐานของกลุ่มตัวอย่าง (n-1) — ใช้รายงานเชิงวิจัยตามแบบเดียวกับหน้าวิเคราะห์สถิติ */
function report_sd(array $vals) {
    $vals = array_values(array_filter($vals, function ($v) { return $v !== null && $v !== ''; }));
    $n = count($vals);
    if ($n < 2) return null;
    $m = array_sum($vals) / $n;
    $sum = 0.0;
    foreach ($vals as $v) $sum += ($v - $m) * ($v - $m);
    return sqrt($sum / ($n - 1));
}

/** ปัดทศนิยมให้อ่านง่าย คืน null ไว้เหมือนเดิมเพื่อให้ผู้เรียกแสดง "—" ได้ */
function report_round($v, $digits = 2) {
    return ($v === null) ? null : round((float)$v, $digits);
}

/** ระดับคุณภาพจากคะแนนเต็ม 60 (เกณฑ์เดียวกับหน้าประเมินของครู) */
function report_level($total60) {
    return ($total60 === null) ? '' : ai_quality_level($total60);
}

/* ------------------------------------------------------------------ ดึงข้อมูล */

/**
 * รวบรวมข้อมูลทั้งหมดที่รายงานต้องใช้
 * $opt = ['group' => กลุ่มการวิจัย, 'classroom' => ห้อง, 'student_id' => ดูคนเดียว]
 *
 * หมายเหตุ: ตัวกรอง group/classroom ใช้กำหนด "ประชากรของชั้นเรียน" ที่นำมาคิดค่าเฉลี่ย
 * ส่วน student_id เป็นเพียงการเลือกว่าจะพิมพ์รายงานของใครบ้าง ค่าเฉลี่ยยังคิดจากทั้งกลุ่มเสมอ
 */
function report_dataset(PDO $pdo, array $opt = []) {
    $group     = isset($opt['group'])      ? trim((string)$opt['group'])      : '';
    $classroom = isset($opt['classroom'])  ? trim((string)$opt['classroom'])  : '';
    $onlyId    = isset($opt['student_id']) ? trim((string)$opt['student_id']) : '';

    // ---- 1) รายชื่อนักเรียนในขอบเขตของรายงาน ----
    $conds = [];
    $params = [];
    if ($group === '__none__') {
        $conds[] = "(student_group IS NULL OR student_group = '')";
    } elseif ($group !== '' && $group !== 'all') {
        $conds[] = 'student_group = ?';
        $params[] = $group;
    }
    if ($classroom !== '' && $classroom !== 'all') {
        $conds[] = 'classroom = ?';
        $params[] = $classroom;
    }
    $sql = 'SELECT student_id, student_name, classroom, student_group FROM students';
    if ($conds) $sql .= ' WHERE ' . implode(' AND ', $conds);
    $sql .= ' ORDER BY classroom ASC, student_id ASC';

    $students = [];
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        while ($r = $stmt->fetch()) {
            $students[$r['student_id']] = [
                'student_id'    => $r['student_id'],
                'student_name'  => formatNamePrefix((string)$r['student_name']),
                'classroom'     => (string)($r['classroom'] ?? ''),
                'student_group' => (string)($r['student_group'] ?? ''),
            ];
        }
    } catch (Exception $e) { $students = []; }

    // ---- 2) ผลการประเมินทุกประเภท ทุกรอบ ----
    $crit    = report_criteria();
    $evalMap = report_evaluator_map();
    $evals   = [];   // [sid][phase][teacher|self|peer|expert] = ข้อมูลคะแนน
    try {
        $cols = [];
        foreach ($crit as $c) $cols[] = 'e.' . $c['col'];
        $base = 'SELECT e.student_id, e.test_phase, e.evaluator_type, e.evaluator_name,
                        e.total_score, e.quality_level, e.timestamp, ';
        try {
            // คอลัมน์ความคิดเห็นของเพื่อนถูกเพิ่มภายหลัง — ฐานข้อมูลที่ยังไม่ได้อัปเดตให้ข้ามส่วนนี้ไป
            $q = $pdo->query($base . ' e.peer_strength, e.peer_improvement, e.peer_encouragement, '
                             . implode(', ', $cols) . ' FROM evaluations e');
        } catch (Exception $e) {
            $q = $pdo->query($base . implode(', ', $cols) . ' FROM evaluations e');
        }
        while ($r = $q->fetch()) {
            $sid = $r['student_id'];
            if (!isset($students[$sid])) continue;
            $key = $evalMap[$r['evaluator_type']] ?? '';
            if ($key === '') continue;
            $scores = [];
            foreach ($crit as $c) $scores[$c['id']] = (float)$r[$c['col']];
            $row = [
                'total'          => (float)$r['total_score'],
                'level'          => (string)$r['quality_level'],
                'scores'         => $scores,
                'evaluator_name' => formatNamePrefix((string)$r['evaluator_name']),
                'timestamp'      => (string)$r['timestamp'],
                'strength'       => (string)($r['peer_strength'] ?? ''),
                'improvement'    => (string)($r['peer_improvement'] ?? ''),
                'encouragement'  => (string)($r['peer_encouragement'] ?? ''),
            ];
            // เพื่อนประเมินมีได้หลายคน → เก็บเป็นรายการแล้วเฉลี่ยทีหลัง
            if ($key === 'peer') {
                $evals[$sid][$r['test_phase']]['peer_list'][] = $row;
            } else {
                $evals[$sid][$r['test_phase']][$key] = $row;
            }
        }
    } catch (Exception $e) { /* ไม่มีข้อมูลประเมินก็ยังพิมพ์รายงานส่วนอื่นได้ */ }

    // เฉลี่ยคะแนนของเพื่อนแต่ละรอบให้เหลือชุดเดียว (แต่ยังเก็บความคิดเห็นทุกคนไว้)
    foreach ($evals as $sid => $byPhase) {
        foreach ($byPhase as $ph => $set) {
            if (empty($set['peer_list'])) continue;
            $totals = [];
            $scoreSum = [];
            foreach ($set['peer_list'] as $pr) {
                $totals[] = $pr['total'];
                foreach ($pr['scores'] as $cid => $v) {
                    $scoreSum[$cid][] = $v;
                }
            }
            $avgScores = [];
            foreach ($scoreSum as $cid => $vals) $avgScores[$cid] = report_mean($vals);
            $evals[$sid][$ph]['peer'] = [
                'total'          => report_mean($totals),
                'level'          => report_level(report_mean($totals)),
                'scores'         => $avgScores,
                'evaluator_name' => 'เฉลี่ยจากเพื่อน ' . count($set['peer_list']) . ' คน',
                'timestamp'      => '',
                'strength'       => '',
                'improvement'    => '',
                'encouragement'  => '',
            ];
        }
    }

    // ---- 3) เรียงความแต่ละรอบ ----
    $essays = [];
    try {
        $q = $pdo->query("SELECT student_id, essay_phase, word_count, created_at, updated_at,
                                 (COALESCE(intro_content,'') <> '' OR COALESCE(body_content,'') <> ''
                                  OR COALESCE(conclusion_content,'') <> '' OR COALESCE(word_count,0) > 0) AS has_content
                            FROM student_essays");
        while ($r = $q->fetch()) {
            if (!isset($students[$r['student_id']])) continue;
            $essays[$r['student_id']][$r['essay_phase']] = [
                'word_count' => (int)$r['word_count'],
                'created_at' => (string)$r['created_at'],
                'updated_at' => (string)$r['updated_at'],
                'submitted'  => !empty($r['has_content']),
            ];
        }
    } catch (Exception $e) { /* ยังไม่มีตารางเรียงความ */ }

    // ---- 4) ผลตรวจของ AI (รวมข้อมูลเทียบกับการตรวจครั้งก่อน) ----
    $ai = [];
    try {
        $q = $pdo->query('SELECT f.*, s.student_name, s.classroom
                            FROM essay_ai_feedback f
                            LEFT JOIN students s ON s.student_id = f.student_id');
        while ($r = $q->fetch()) {
            if (!isset($students[$r['student_id']])) continue;
            $ai[$r['student_id']][$r['essay_phase']] = ai_feedback_row_to_array($r);
        }
    } catch (Exception $e) { /* ยังไม่ได้ใช้ระบบ AI */ }

    // ---- 5) เครื่องมือสะท้อนคิด (แยกตามหน่วยการเรียน) ----
    $tools = [];
    foreach ([
        'problems'   => 'writing_problems',
        'checklist'  => 'self_checklists',
        'reflection' => 'learning_reflections',
    ] as $key => $tbl) {
        try {
            $q = $pdo->query("SELECT student_id, task_unit FROM `$tbl`");
            while ($r = $q->fetch()) {
                if (!isset($students[$r['student_id']])) continue;
                $u = ((int)$r['task_unit'] === 2) ? 2 : 1;
                $tools[$r['student_id']][$u][$key] = true;
            }
        } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง */ }
    }

    // ---- 6) ข้อคิดเห็นเชิงคุณภาพจากเพื่อน ----
    // อ่านจากตาราง evaluations (evaluator_type = 'เพื่อนประเมิน') ซึ่งเป็นที่ที่หน้า evaluation.php
    // บันทึกจริง — ตาราง peer_reviews ไม่มีหน้าไหนเขียนลงไปแล้ว ถ้าอ่านจากตารางนั้น รายงานจะไม่มี
    // ข้อคิดเห็นจากเพื่อนเลย ทั้งที่นักเรียนเขียนและบันทึกไปแล้ว
    // (evaluations เก็บ "ชื่อ" ผู้ประเมิน ไม่ใช่รหัส จึงต้องเทียบกลับเป็นรหัสด้วยชื่อในทะเบียน)
    $peer = [];
    $peerIdByName = report_student_id_by_name($pdo);
    try {
        $q = $pdo->query("SELECT student_id, evaluator_name, test_phase,
                                 peer_strength, peer_improvement, peer_encouragement, timestamp
                            FROM evaluations
                           WHERE evaluator_type = 'เพื่อนประเมิน'");
        while ($r = $q->fetch()) {
            $reviewerName = trim((string)$r['evaluator_name']);
            $reviewerId   = $peerIdByName[$reviewerName] ?? '';
            if (isset($students[$r['student_id']])) {
                $peer[$r['student_id']]['received'][] = [
                    'reviewer'      => formatNamePrefix($reviewerName),
                    'phase'         => (string)$r['test_phase'],
                    'strength'      => (string)($r['peer_strength'] ?? ''),
                    'improvement'   => (string)($r['peer_improvement'] ?? ''),
                    'encouragement' => (string)($r['peer_encouragement'] ?? ''),
                    'created_at'    => (string)$r['timestamp'],
                ];
            }
            if ($reviewerId !== '' && isset($students[$reviewerId])) {
                $peer[$reviewerId]['given'] = (int)($peer[$reviewerId]['given'] ?? 0) + 1;
            }
        }
    } catch (Exception $e) { /* ยังไม่มีการประเมินเพื่อน */ }

    $data = [
        'students'  => $students,
        'evals'     => $evals,
        'essays'    => $essays,
        'ai'        => $ai,
        'tools'     => $tools,
        'peer'      => $peer,
        'filter'    => ['group' => $group, 'classroom' => $classroom, 'student_id' => $onlyId],
    ];
    $data['class'] = report_class_stats($data);
    return $data;
}

/* ------------------------------------------------------- สถานะการส่งงานรายชิ้น */

/** ชิ้นงานชิ้นนี้ของนักเรียนคนนี้ส่งแล้วหรือยัง (key ตาม report_work_items) */
function report_item_done(array $data, $sid, $key) {
    $parts = explode(':', $key);
    if ($parts[0] === 'essay') {
        return !empty($data['essays'][$sid][$parts[1]]['submitted']);
    }
    if ($parts[0] === 'tool') {
        return !empty($data['tools'][$sid][(int)$parts[2]][$parts[1]]);
    }
    return false;
}

/** จำนวนชิ้นงานที่ส่งแล้วของนักเรียนคนหนึ่ง */
function report_done_count(array $data, $sid) {
    $n = 0;
    foreach (report_work_items() as $it) {
        if (report_item_done($data, $sid, $it['key'])) $n++;
    }
    return $n;
}

/* ------------------------------------------------------------ สถิติทั้งชั้นเรียน */

/**
 * สถิติระดับชั้นเรียน ใช้เป็นเส้นเปรียบเทียบในรายงานรายบุคคล และเป็นเนื้อหาหลักของรายงานภาพรวม
 * คิดจาก "คะแนนของคุณครู" เป็นหลัก เพราะเป็นคะแนนที่ใช้ตัดสินผลสัมฤทธิ์จริง
 */
function report_class_stats(array $data) {
    $crit   = report_criteria();
    $phases = report_eval_phases();
    $sids   = array_keys($data['students']);

    $out = [
        'count'      => count($sids),
        'phase'      => [],
        'criteria'   => [],
        'growth'     => ['n' => 0, 'mean' => null, 'sd' => null, 'improved' => 0, 'declined' => 0, 'same' => 0],
        'submission' => ['items' => count(report_work_items()), 'per_item' => [], 'complete' => 0, 'mean_done' => null],
        'ai'         => [],
        'top_growth' => [],
        'watchlist'  => [],
    ];

    // ---- คะแนนรายรอบของคุณครู ----
    foreach ($phases as $ph => $label) {
        $totals = [];
        $levels = [];
        foreach ($sids as $sid) {
            $t = $data['evals'][$sid][$ph]['teacher']['total'] ?? null;
            if ($t === null) continue;
            $totals[] = (float)$t;
            $lv = $data['evals'][$sid][$ph]['teacher']['level'] ?? '';
            if ($lv === '') $lv = report_level((float)$t);
            $levels[$lv] = (int)($levels[$lv] ?? 0) + 1;
        }
        $out['phase'][$ph] = [
            'label'  => $label,
            'n'      => count($totals),
            'mean'   => report_mean($totals),
            'sd'     => report_sd($totals),
            'min'    => $totals ? min($totals) : null,
            'max'    => $totals ? max($totals) : null,
            'levels' => $levels,
        ];

        // ค่าเฉลี่ยรายเกณฑ์ของรอบนี้ (ใช้ชี้ว่าทั้งห้องอ่อนเกณฑ์ข้อไหน)
        foreach ($crit as $c) {
            $vals = [];
            foreach ($sids as $sid) {
                $sc = $data['evals'][$sid][$ph]['teacher']['scores'][$c['id']] ?? null;
                if ($sc !== null) $vals[] = (float)$sc;
            }
            $out['criteria'][$ph][$c['id']] = report_mean($vals);
        }
    }

    // ---- พัฒนาการ ก่อนเรียน → หลังเรียน ----
    $diffs = [];
    foreach ($sids as $sid) {
        $pre  = $data['evals'][$sid]['pretest']['teacher']['total']  ?? null;
        $post = $data['evals'][$sid]['posttest']['teacher']['total'] ?? null;
        if ($pre === null || $post === null) continue;
        $d = (float)$post - (float)$pre;
        $diffs[] = $d;
        if ($d > 0) $out['growth']['improved']++;
        elseif ($d < 0) $out['growth']['declined']++;
        else $out['growth']['same']++;
        $out['top_growth'][] = [
            'student_id'   => $sid,
            'student_name' => $data['students'][$sid]['student_name'],
            'pre'          => (float)$pre,
            'post'         => (float)$post,
            'diff'         => $d,
        ];
    }
    $out['growth']['n']    = count($diffs);
    $out['growth']['mean'] = report_mean($diffs);
    $out['growth']['sd']   = report_sd($diffs);
    usort($out['top_growth'], function ($a, $b) {
        if ($a['diff'] == $b['diff']) return strcmp($a['student_id'], $b['student_id']);
        return ($a['diff'] < $b['diff']) ? 1 : -1;
    });

    // ---- การส่งงาน ----
    $doneCounts = [];
    foreach (report_work_items() as $it) {
        $n = 0;
        foreach ($sids as $sid) { if (report_item_done($data, $sid, $it['key'])) $n++; }
        $out['submission']['per_item'][$it['key']] = ['label' => $it['label'], 'n' => $n];
    }
    foreach ($sids as $sid) {
        $c = report_done_count($data, $sid);
        $doneCounts[] = $c;
        if ($c >= $out['submission']['items']) $out['submission']['complete']++;
    }
    $out['submission']['mean_done'] = report_mean($doneCounts);

    // ---- ผลตรวจของ AI รายรอบงาน ----
    foreach (report_essay_phases() as $ph => $label) {
        $totals = [];
        $rounds = 0;
        $deltas = [];
        foreach ($sids as $sid) {
            $fb = $data['ai'][$sid][$ph] ?? null;
            if (!$fb) continue;
            $totals[] = (float)$fb['total_score'];
            if ((int)($fb['review_round'] ?? 1) > 1) {
                $rounds++;
                if (!empty($fb['progress']['has_prev'])) $deltas[] = (float)$fb['progress']['total_delta'];
            }
        }
        $out['ai'][$ph] = [
            'label'       => $label,
            'n'           => count($totals),
            'mean'        => report_mean($totals),
            'max_score'   => ai_rubric_max(),
            'rechecked'   => $rounds,
            'mean_delta'  => report_mean($deltas),
        ];
    }

    // ---- นักเรียนที่ควรติดตาม: ส่งงานไม่ครบ หรือคะแนนหลังเรียนต่ำกว่าค่าเฉลี่ยมาก ----
    $postMean = $out['phase']['posttest']['mean'] ?? null;
    foreach ($sids as $sid) {
        $done   = report_done_count($data, $sid);
        $post   = $data['evals'][$sid]['posttest']['teacher']['total'] ?? null;
        $reason = [];
        if ($done < $out['submission']['items']) {
            $reason[] = 'ส่งงาน ' . $done . '/' . $out['submission']['items'] . ' ชิ้น';
        }
        if ($post !== null && $postMean !== null && (float)$post < $postMean - 5) {
            $reason[] = 'คะแนนหลังเรียนต่ำกว่าค่าเฉลี่ยชั้น ' . number_format($postMean - (float)$post, 1) . ' คะแนน';
        }
        if ($post === null) $reason[] = 'ยังไม่มีคะแนนหลังเรียนจากคุณครู';
        if ($reason) {
            $out['watchlist'][] = [
                'student_id'   => $sid,
                'student_name' => $data['students'][$sid]['student_name'],
                'done'         => $done,
                'post'         => ($post === null) ? null : (float)$post,
                'reasons'      => $reason,
            ];
        }
    }

    return $out;
}

/* ------------------------------------------------------------ สรุปรายบุคคล */

/**
 * สรุปข้อมูลของนักเรียน 1 คนให้พร้อมพิมพ์เป็นรายงาน
 * แบ่งเป็น 3 ส่วนตามที่รายงานต้องการ: ผลสัมฤทธิ์ / ผลงาน / สถิติ
 */
function report_student_summary(array $data, $sid) {
    $crit    = report_criteria();
    $phases  = report_eval_phases();
    $class   = $data['class'];
    $student = $data['students'][$sid] ?? null;
    if (!$student) return null;

    // ---- ผลสัมฤทธิ์: คะแนนแต่ละรอบ เทียบผู้ประเมินและเทียบค่าเฉลี่ยของชั้น ----
    $achievement = [];
    foreach ($phases as $ph => $label) {
        $teacher = $data['evals'][$sid][$ph]['teacher'] ?? null;
        $tTotal  = $teacher ? (float)$teacher['total'] : null;
        $cMean   = $class['phase'][$ph]['mean'] ?? null;
        $achievement[$ph] = [
            'label'      => $label,
            'teacher'    => $tTotal,
            'level'      => $teacher ? (($teacher['level'] !== '') ? $teacher['level'] : report_level($tTotal)) : '',
            'self'       => isset($data['evals'][$sid][$ph]['self'])   ? (float)$data['evals'][$sid][$ph]['self']['total']   : null,
            'peer'       => isset($data['evals'][$sid][$ph]['peer'])   ? (float)$data['evals'][$sid][$ph]['peer']['total']   : null,
            'expert'     => isset($data['evals'][$sid][$ph]['expert']) ? (float)$data['evals'][$sid][$ph]['expert']['total'] : null,
            'class_mean' => $cMean,
            'vs_class'   => ($tTotal === null || $cMean === null) ? null : $tTotal - $cMean,
            'scored_at'  => $teacher ? $teacher['timestamp'] : '',
        ];
    }

    // ---- พัฒนาการก่อนเรียน → หลังเรียน ----
    $pre  = $achievement['pretest']['teacher']  ?? null;
    $post = $achievement['posttest']['teacher'] ?? null;
    $growth = [
        'pre'        => $pre,
        'post'       => $post,
        'diff'       => ($pre === null || $post === null) ? null : $post - $pre,
        'pct'        => ($pre === null || $post === null || $pre <= 0) ? null : (($post - $pre) / $pre) * 100,
        'class_mean' => $class['growth']['mean'],
        'level_from' => ($pre  === null) ? '' : report_level($pre),
        'level_to'   => ($post === null) ? '' : report_level($post),
    ];

    // ---- สถิติรายเกณฑ์: ก่อนเรียน → หลังเรียน และเทียบค่าเฉลี่ยของชั้น ----
    $byCriterion = [];
    foreach ($crit as $c) {
        $cid  = $c['id'];
        $pRaw = $data['evals'][$sid]['pretest']['teacher']['scores'][$cid]  ?? null;
        $qRaw = $data['evals'][$sid]['posttest']['teacher']['scores'][$cid] ?? null;
        $byCriterion[$cid] = [
            'id'         => $cid,
            'name'       => $c['name'],
            'max'        => $c['max'],
            'pre'        => ($pRaw === null) ? null : (float)$pRaw,
            'post'       => ($qRaw === null) ? null : (float)$qRaw,
            'diff'       => ($pRaw === null || $qRaw === null) ? null : (float)$qRaw - (float)$pRaw,
            'class_post' => $class['criteria']['posttest'][$cid] ?? null,
            'pct'        => ($qRaw === null || $c['max'] <= 0) ? null : ((float)$qRaw / $c['max']) * 100,
        ];
    }

    // จุดแข็ง / จุดที่ควรพัฒนา — คิดจากสัดส่วนคะแนนหลังเรียนต่อคะแนนเต็มของเกณฑ์นั้น
    $ranked = array_values(array_filter($byCriterion, function ($c) { return $c['pct'] !== null; }));
    usort($ranked, function ($a, $b) {
        if ($a['pct'] == $b['pct']) return strcmp($a['id'], $b['id']);
        return ($a['pct'] < $b['pct']) ? 1 : -1;
    });
    $strong = array_slice($ranked, 0, 3);
    $weak   = array_slice(array_reverse($ranked), 0, 3);

    // ---- ผลงาน: เรียงความทุกรอบ + ผลตรวจของ AI ----
    $works = [];
    foreach (report_essay_phases() as $ph => $label) {
        $es = $data['essays'][$sid][$ph] ?? null;
        $fb = $data['ai'][$sid][$ph] ?? null;
        $works[$ph] = [
            'label'       => $label,
            'submitted'   => !empty($es['submitted']),
            'word_count'  => $es ? (int)$es['word_count'] : 0,
            'updated_at'  => $es ? (string)$es['updated_at'] : '',
            'ai_total'    => $fb ? (float)$fb['total_score'] : null,
            'ai_max'      => $fb ? (float)$fb['max_score'] : null,
            'ai_level'    => $fb ? (string)$fb['quality_level'] : '',
            'ai_round'    => $fb ? (int)($fb['review_round'] ?? 1) : 0,
            'ai_delta'    => ($fb && !empty($fb['progress']['has_prev'])) ? (float)$fb['progress']['total_delta'] : null,
            'ai_pending'  => $fb ? !empty($fb['needs_recheck']) : false,
        ];
    }

    // ---- รายการชิ้นงานทั้ง 12 ชิ้น (ใช้เป็นใบตรวจสอบความครบถ้วน) ----
    $checklist = [];
    foreach (report_work_items() as $it) {
        $checklist[] = [
            'label' => $it['label'],
            'group' => $it['group'],
            'done'  => report_item_done($data, $sid, $it['key']),
        ];
    }
    $done = report_done_count($data, $sid);

    // ---- ลำดับในชั้นเรียน (คะแนนหลังเรียน และพัฒนาการ) ----
    // เรียงจากมากไปน้อย คนที่คะแนนเท่ากันได้ลำดับเดียวกัน
    $rankOf = function ($values, $mine) {
        if ($mine === null) return null;
        $vals = array_values(array_filter($values, function ($v) { return $v !== null; }));
        if (!$vals) return null;
        $better = 0;
        foreach ($vals as $v) { if ($v > $mine) $better++; }
        return ['rank' => $better + 1, 'of' => count($vals)];
    };
    $allPost = $allGrowth = [];
    foreach (array_keys($data['students']) as $other) {
        $p = $data['evals'][$other]['pretest']['teacher']['total']  ?? null;
        $q = $data['evals'][$other]['posttest']['teacher']['total'] ?? null;
        $allPost[]   = ($q === null) ? null : (float)$q;
        $allGrowth[] = ($p === null || $q === null) ? null : (float)$q - (float)$p;
    }

    // ---- ผลตรวจของ AI ฉบับล่าสุดที่มี (ใช้เป็นข้อเสนอแนะปิดท้ายรายงาน) ----
    $latestAi = null;
    foreach (array_reverse(array_keys(report_essay_phases())) as $ph) {
        if (!empty($data['ai'][$sid][$ph])) { $latestAi = $data['ai'][$sid][$ph]; break; }
    }

    return [
        'student'     => $student,
        'achievement' => $achievement,
        'growth'      => $growth,
        'criteria'    => $byCriterion,
        'strong'      => $strong,
        'weak'        => $weak,
        'works'       => $works,
        'checklist'   => $checklist,
        'done'        => $done,
        'done_total'  => count(report_work_items()),
        'peer'        => $data['peer'][$sid] ?? ['received' => [], 'given' => 0],
        'latest_ai'   => $latestAi,
        'rank_post'   => $rankOf($allPost, $post),
        'rank_growth' => $rankOf($allGrowth, $growth['diff']),
        'class'       => $class,
    ];
}

/* ==========================================================================
 * ข้อมูล "ทั้งหมด" ของนักเรียนรายบุคคล — ใช้ทำรายงานฉบับเต็มและหน้าเว็บรายบุคคล
 * ทุกอย่างที่นักเรียนทำไว้ในระบบ: เรียงความฉบับเต็มทุกรอบ คะแนนรายเกณฑ์จากผู้ประเมินทุกฝ่าย
 * ผลตรวจของ AI ทุกรอบ เครื่องมือสะท้อนคิดทุกหน่วย และข้อคิดเห็นจากเพื่อน
 * ========================================================================== */

/** ป้ายชื่อเกณฑ์ 11 ข้อ แบบคีย์ขีดล่าง (ตรงกับชื่อคอลัมน์ในตารางเครื่องมือสะท้อนคิด) */
function report_criteria_underscore() {
    $out = [];
    foreach (report_criteria() as $c) {
        $out[str_replace('.', '_', $c['id'])] = $c['id'] . ' ' . $c['name'];
    }
    return $out;
}

/** ป้ายชื่อเกณฑ์ของ "แบบประเมินเพื่อน" (มีข้อย่อยเพิ่มอีก 2 ข้อ รวม 13 ข้อ) */
function report_peer_criteria() {
    return [
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
}

/** ทำดัชนี "ชื่อนักเรียน → รหัส" (ทั้งชื่อเต็มและชื่อที่ตัดคำนำหน้าออก)
 *  ใช้หาว่าใครเป็นผู้ประเมิน เพราะตาราง evaluations เก็บชื่อผู้ประเมิน ไม่ได้เก็บรหัส
 *  ดึงจากทะเบียนเองและแคชไว้ต่อ 1 request เพื่อให้เรียกใช้ได้จากทุกฟังก์ชันในไฟล์นี้ */
function report_student_id_by_name(PDO $pdo) {
    static $idByName = null;
    if ($idByName !== null) return $idByName;
    $idByName = [];
    try {
        $rows = $pdo->query('SELECT student_id, student_name FROM students')->fetchAll();
        foreach ($rows as $r) {
            $sid  = (string)$r['student_id'];
            $full = (string)($r['student_name'] ?? '');
            if (trim($full) !== '')                   $idByName[trim($full)] = $sid;
            $short = trim(formatNamePrefix($full));
            if ($short !== '')                        $idByName[$short] = $sid;
        }
    } catch (PDOException $e) { /* ทะเบียนยังไม่ถูกสร้าง — คืนดัชนีว่าง */ }
    return $idByName;
}

/** แปลงคะแนนรายเกณฑ์ที่ถ่วงน้ำหนักแล้วใน evaluations กลับเป็นระดับ 0-4 ตามเกณฑ์เดิม
 *  (ใช้ multiplier ชุดเดียวกับทั้งระบบจาก ai_rubric()) */
function report_peer_level_from_weighted($critKey, $weighted) {
    static $mult = null;
    if ($mult === null) {
        $mult = [];
        if (function_exists('ai_rubric')) {
            foreach (ai_rubric() as $ri) { $mult[str_replace('.', '_', $ri['id'])] = (float)$ri['multiplier']; }
        }
    }
    if ($weighted === null || $weighted === '') return '';
    $m = $mult[$critKey] ?? 0;
    return (string)($m > 0 ? round((float)$weighted / $m, 2) : (float)$weighted);
}

/** หัวข้อของบันทึกสะท้อนการเรียนรู้ */
function report_reflect_fields() {
    return [
        'content_structure'  => 'ด้านเนื้อหาสาระและองค์ประกอบ',
        'language_mechanics' => 'ด้านการใช้สำนวนภาษาและอักขรวิธี',
        'feedback_applied'   => 'การนำข้อเสนอแนะไปปรับปรุงงาน',
        'future_goals'       => 'การประยุกต์ใช้และเป้าหมายในอนาคต',
    ];
}

/** เกณฑ์ความยาวเรียงความที่ครูกำหนด */
function report_word_target() {
    return ['min' => 250, 'max' => 300];
}

/**
 * ดึงข้อมูลดิบทั้งหมดของนักเรียนหลายคนพร้อมกัน (query ละครั้งต่อประเภทข้อมูล)
 * คืนค่า [student_id => dossier] — ใช้กับรายงานทั้งห้องได้โดยไม่ยิง query ซ้ำต่อคน
 */
function report_full_data(PDO $pdo, array $sids) {
    $out = [];
    if (!$sids) return $out;
    $in     = implode(',', array_fill(0, count($sids), '?'));
    $blank  = [
        'essays' => [], 'evals' => [], 'ai' => [], 'problems' => [], 'checklists' => [],
        'reflections' => [], 'peer_received' => [], 'peer_given' => [],
    ];
    foreach ($sids as $sid) $out[$sid] = $blank;

    // หัวข้อที่ครูกำหนดแต่ละรอบ (ตัวช่วยอยู่ใน db_config.php ซึ่งหน้าเว็บโหลดผ่าน auth_helper.php อยู่แล้ว)
    $topics = function_exists('essay_topics_map') ? essay_topics_map($pdo) : [];

    // ---- เรียงความฉบับเต็มทุกรอบ ----
    try {
        $st = $pdo->prepare("SELECT student_id, essay_phase, intro_content, body_content, conclusion_content,
                                    word_count, created_at, updated_at
                               FROM student_essays WHERE student_id IN ($in)");
        $st->execute($sids);
        while ($r = $st->fetch()) {
            $body = json_decode((string)$r['body_content'], true);
            if (!is_array($body)) {
                $body = (trim((string)$r['body_content']) !== '') ? [(string)$r['body_content']] : [];
            }
            $body = array_values(array_filter(array_map('strval', $body), function ($p) { return trim($p) !== ''; }));
            $intro = (string)($r['intro_content'] ?? '');
            $concl = (string)($r['conclusion_content'] ?? '');
            $out[$r['student_id']]['essays'][$r['essay_phase']] = [
                'intro'      => $intro,
                'body'       => $body,
                'conclusion' => $concl,
                'word_count' => (int)$r['word_count'],
                'created_at' => (string)$r['created_at'],
                'updated_at' => (string)$r['updated_at'],
                'topic'      => (string)($topics[function_exists('essay_topic_phase')
                                    ? essay_topic_phase($r['essay_phase']) : $r['essay_phase']] ?? ''),
                'has_text'   => (trim($intro) !== '' || $body || trim($concl) !== ''),
            ];
        }
    } catch (Exception $e) { /* ยังไม่มีตารางเรียงความ */ }

    // ---- คะแนนรายเกณฑ์จากผู้ประเมินทุกฝ่าย ทุกรอบ ----
    $crit    = report_criteria();
    $evalMap = report_evaluator_map();
    try {
        $cols = [];
        foreach ($crit as $c) $cols[] = 'e.' . $c['col'];
        $base = "SELECT e.student_id, e.test_phase, e.evaluator_type, e.evaluator_name,
                        e.total_score, e.quality_level, e.timestamp, ";
        $tail = " FROM evaluations e WHERE e.student_id IN ($in)";
        try {
            $st = $pdo->prepare($base . 'e.peer_strength, e.peer_improvement, e.peer_encouragement, '
                                . implode(', ', $cols) . $tail);
            $st->execute($sids);
        } catch (Exception $e) {
            $st = $pdo->prepare($base . implode(', ', $cols) . $tail);
            $st->execute($sids);
        }
        while ($r = $st->fetch()) {
            $key = $evalMap[$r['evaluator_type']] ?? '';
            if ($key === '') continue;
            $scores = [];
            foreach ($crit as $c) $scores[$c['id']] = (float)$r[$c['col']];
            $row = [
                'type'           => $key,
                'type_label'     => (string)$r['evaluator_type'],
                'evaluator_name' => formatNamePrefix((string)$r['evaluator_name']),
                'total'          => (float)$r['total_score'],
                'level'          => (string)$r['quality_level'],
                'scores'         => $scores,
                'timestamp'      => (string)$r['timestamp'],
                'strength'       => (string)($r['peer_strength'] ?? ''),
                'improvement'    => (string)($r['peer_improvement'] ?? ''),
                'encouragement'  => (string)($r['peer_encouragement'] ?? ''),
            ];
            $out[$r['student_id']]['evals'][$r['test_phase']][] = $row;
        }
    } catch (Exception $e) { /* ยังไม่มีผลประเมิน */ }

    // ---- ผลตรวจของ AI ทุกรอบงาน ----
    try {
        $st = $pdo->prepare("SELECT f.*, s.student_name, s.classroom
                               FROM essay_ai_feedback f
                               LEFT JOIN students s ON s.student_id = f.student_id
                              WHERE f.student_id IN ($in)");
        $st->execute($sids);
        while ($r = $st->fetch()) {
            $out[$r['student_id']]['ai'][$r['essay_phase']] = ai_feedback_row_to_array($r);
        }
    } catch (Exception $e) { /* ยังไม่ได้ใช้ระบบ AI */ }

    // ---- บันทึกปัญหาการเขียนและแนวทางแก้ (แยกตามหน่วย) ----
    $critU = report_criteria_underscore();
    try {
        $st = $pdo->prepare("SELECT * FROM writing_problems WHERE student_id IN ($in)");
        $st->execute($sids);
        while ($r = $st->fetch()) {
            $u = ((int)$r['task_unit'] === 2) ? 2 : 1;
            $items = [];
            foreach ($critU as $k => $label) {
                $prob = trim((string)($r['prob_' . $k] ?? ''));
                $sol  = trim((string)($r['sol_' . $k] ?? ''));
                if ($prob === '' && $sol === '') continue;
                $items[$k] = ['label' => $label, 'problem' => $prob, 'solution' => $sol];
            }
            $out[$r['student_id']]['problems'][$u] = [
                'items'      => $items,
                'created_at' => (string)($r['created_at'] ?? ''),
            ];
        }
    } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง */ }

    // ---- รายการตรวจสอบตนเอง (แยกตามหน่วย) ----
    try {
        $st = $pdo->prepare("SELECT * FROM self_checklists WHERE student_id IN ($in)");
        $st->execute($sids);
        while ($r = $st->fetch()) {
            $u = ((int)$r['task_unit'] === 2) ? 2 : 1;
            $items = [];
            foreach ($critU as $k => $label) {
                $items[$k] = ['label' => $label, 'value' => trim((string)($r['check_' . $k] ?? ''))];
            }
            $out[$r['student_id']]['checklists'][$u] = [
                'items'      => $items,
                'notes'      => trim((string)($r['notes'] ?? '')),
                'created_at' => (string)($r['created_at'] ?? ''),
            ];
        }
    } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง */ }

    // ---- บันทึกสะท้อนการเรียนรู้ (แยกตามหน่วย) ----
    try {
        $st = $pdo->prepare("SELECT * FROM learning_reflections WHERE student_id IN ($in)");
        $st->execute($sids);
        while ($r = $st->fetch()) {
            $u = ((int)$r['task_unit'] === 2) ? 2 : 1;
            $fields = [];
            foreach (report_reflect_fields() as $f => $label) {
                $fields[$f] = ['label' => $label, 'text' => trim((string)($r[$f] ?? ''))];
            }
            $out[$r['student_id']]['reflections'][$u] = [
                'fields'     => $fields,
                'created_at' => (string)($r['created_at'] ?? ''),
            ];
        }
    } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง */ }

    // ---- แบบประเมินเพื่อนเชิงคุณภาพ (ทั้งที่ได้รับและที่ไปประเมินให้เพื่อน) ----
    // อ่านจาก evaluations (evaluator_type = 'เพื่อนประเมิน') ซึ่งเป็นแหล่งข้อมูลจริง
    // ไม่ใช่ตาราง peer_reviews ที่ไม่มีหน้าไหนเขียนลงไปแล้ว (รายงานจะไม่มีข้อมูลประเมินเพื่อนเลย)
    // คะแนนใน evaluations เป็นค่าหลังถ่วงน้ำหนัก จึงหารกลับเป็นระดับ 0-4 ตามเกณฑ์เดิม
    // ส่วนเกณฑ์ 1.4 / 3.4 มีเฉพาะแบบประเมินเพื่อนรูปแบบเดิม จึงเว้นว่างไว้
    $peerCrit = report_peer_criteria();
    $peerIdByName2 = report_student_id_by_name($pdo);
    try {
        $st = $pdo->prepare("SELECT e.*, own.student_name AS owner_name
                               FROM evaluations e
                               LEFT JOIN students own ON own.student_id = e.student_id
                              WHERE e.evaluator_type = 'เพื่อนประเมิน'");
        $st->execute();
        while ($r = $st->fetch()) {
            $reviewerName = trim((string)$r['evaluator_name']);
            $reviewerId   = $peerIdByName2[$reviewerName] ?? '';
            // เอาเฉพาะที่เกี่ยวข้องกับนักเรียนในรายงานชุดนี้ (เจ้าของผลงาน หรือผู้ประเมิน)
            if (!isset($out[$r['student_id']]) && !($reviewerId !== '' && isset($out[$reviewerId]))) continue;

            $scores = [];
            foreach ($peerCrit as $k => $label) {
                $col = 'score_' . $k;
                $scores[$k] = [
                    'label' => $label,
                    'value' => array_key_exists($col, $r) ? report_peer_level_from_weighted($k, $r[$col]) : '',
                ];
            }
            $item = [
                'owner_id'      => (string)$r['student_id'],
                'owner_name'    => formatNamePrefix((string)($r['owner_name'] ?? '')),
                'reviewer_id'   => $reviewerId,
                'reviewer_name' => formatNamePrefix($reviewerName),
                'phase'         => (string)($r['test_phase'] ?? ''),
                'scores'        => $scores,
                'strength'      => trim((string)($r['peer_strength'] ?? '')),
                'improvement'   => trim((string)($r['peer_improvement'] ?? '')),
                'encouragement' => trim((string)($r['peer_encouragement'] ?? '')),
                'created_at'    => (string)($r['timestamp'] ?? ''),
            ];
            if (isset($out[$r['student_id']]))                    $out[$r['student_id']]['peer_received'][] = $item;
            if ($reviewerId !== '' && isset($out[$reviewerId]))   $out[$reviewerId]['peer_given'][]         = $item;
        }
    } catch (Exception $e) { /* ยังไม่มีการประเมินเพื่อน */ }

    return $out;
}
