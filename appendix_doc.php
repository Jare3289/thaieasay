<?php
/** สร้างเอกสารภาคผนวกข้อมูลดิบสำหรับส่งต่อไปยัง Google Docs (ครูเท่านั้น) */
require_once 'auth_helper.php';
require_login('teacher');
require_once 'db_config.php';
require_once 'chapter45_data.php';

header('Content-Type: application/json; charset=utf-8');

function appendix_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function appendix_number($value) {
    if ($value === null || $value === '') return '—';
    return number_format((float)$value, 2, '.', '');
}

function appendix_stat_number($value) {
    if ($value === null || $value === '') return '—';
    $text = number_format((float)$value, 3, '.', '');
    return preg_replace('/^(-?)0\./', '$1.', $text);
}

function appendix_raters(array $ds, $sid, $phase) {
    $rows = [];
    foreach (['teacher', 'expert'] as $role) {
        foreach ($ds['evals'][$sid][$phase][$role] ?? [] as $evaluation) $rows[] = $evaluation;
    }
    return $rows;
}

function appendix_quality($score) {
    if ($score === null) return '—';
    return function_exists('ai_quality_level') ? ai_quality_level((float)$score) : '—';
}

/** ตารางสรุปรายคนแบบก่อน–หลังตามรูปแบบภาคผนวก (คะแนนเป็นค่าเฉลี่ยผู้ตรวจที่ตั้งไว้ในงานวิจัย) */
function appendix_score_table(array $ds, array $phases, $caption) {
    $domains = ch45_domains();
    $phaseKeys = array_keys($phases);
    $first = $phaseKeys[0]; $second = $phaseKeys[1];
    $diffLabel = ($first === 'pretest' && $second === 'posttest') ? 'Post − Pre' : 'แผน 2 − แผน 1';
    $html = '<h2>' . appendix_h($caption) . '</h2>';
    $html .= '<table class="score-summary"><thead><tr><th rowspan="2">รหัสนักเรียน</th>'
        . '<th colspan="5">' . appendix_h($phases[$first]) . '</th><th colspan="5">' . appendix_h($phases[$second]) . '</th>'
        . '<th rowspan="2">ผลต่าง<br>(' . appendix_h($diffLabel) . ')</th><th rowspan="2">ระดับคุณภาพ<br>' . appendix_h($phases[$second]) . '</th></tr><tr>';
    foreach ([1, 2] as $_) {
        foreach ($domains as $domain) $html .= '<th>ด้าน ' . $domain['no'] . '<br>(' . $domain['max'] . ')</th>';
        $html .= '<th>รวม<br>(60)</th>';
    }
    $html .= '</tr></thead><tbody>';
    $series = [];
    foreach ([$first, $second] as $phase) foreach (array_merge(array_keys($domains), ['total']) as $key) $series[$phase][$key] = [];
    foreach ($ds['sids'] as $sid) {
        $scores = [];
        foreach ([$first, $second] as $phase) {
            $score = ch45_scores_of($ds, $sid, $phase);
            $scores[$phase] = $score;
            $dom = $score ? ch45_domain_total($score['weighted']) : [];
            foreach ($domains as $key => $domain) {
                $value = $dom[$key] ?? null;
                if ($value !== null) $series[$phase][$key][] = $value;
            }
            if (($score['total'] ?? null) !== null) $series[$phase]['total'][] = $score['total'];
        }
        $html .= '<tr><td>' . str_pad((string)$ds['students'][$sid]['no'], 2, '0', STR_PAD_LEFT) . '</td>';
        foreach ([$first, $second] as $phase) {
            $dom = $scores[$phase] ? ch45_domain_total($scores[$phase]['weighted']) : [];
            foreach ($domains as $key => $domain) $html .= '<td class="num">' . appendix_number($dom[$key] ?? null) . '</td>';
            $html .= '<td class="num">' . appendix_number($scores[$phase]['total'] ?? null) . '</td>';
        }
        $a = $scores[$first]['total'] ?? null; $b = $scores[$second]['total'] ?? null;
        $html .= '<td class="num">' . appendix_number($a !== null && $b !== null ? $b - $a : null) . '</td>'
            . '<td>' . appendix_h(appendix_quality($b)) . '</td></tr>';
    }
    if (!$ds['sids']) $html .= '<tr><td colspan="13">ไม่พบนักเรียนในขอบเขตที่เลือก</td></tr>';
    foreach (['mean' => 'เฉลี่ย (X̄)', 'sd' => 'S.D.', 'pct' => 'ร้อยละ (%)'] as $stat => $label) {
        $html .= '<tr class="average"><td>' . $label . '</td>';
        foreach ([$first, $second] as $phase) {
            foreach ($domains as $key => $domain) {
                $d = ch45_describe($series[$phase][$key]);
                $value = $stat === 'mean' ? $d['mean'] : ($stat === 'sd' ? $d['sd'] : ($d['mean'] === null ? null : $d['mean'] * 100 / $domain['max']));
                $html .= '<td class="num">' . appendix_number($value) . '</td>';
            }
            $d = ch45_describe($series[$phase]['total']);
            $value = $stat === 'mean' ? $d['mean'] : ($stat === 'sd' ? $d['sd'] : ($d['mean'] === null ? null : $d['mean'] * 100 / 60));
            $html .= '<td class="num">' . appendix_number($value) . '</td>';
        }
        $html .= '<td colspan="2">—</td></tr>';
    }
    return $html . '</tbody></table>';
}

function appendix_p($p) {
    if ($p === null) return '—';
    if ($p < 0.001) return '&lt; .001';
    return '= ' . ltrim(number_format($p, 3, '.', ''), '0');
}

/** สร้างข้อมูล ICC แยกคะแนนรวมและทั้ง 4 ด้าน พร้อม matrix คะแนนรายผู้ตรวจ */
function appendix_icc_phase(array $ds, $phase, $label) {
    $domains = ch45_domains();
    $raters = [];
    foreach ($ds['sids'] as $sid) foreach (['teacher', 'expert'] as $role) {
        foreach ($ds['evals'][$sid][$phase][$role] ?? [] as $r) {
            $key = $role . ':' . $r['rater'];
            $raters[$key][$sid] = ['total' => $r['total'], 'domains' => ch45_domain_total($r['weighted'])];
        }
    }
    if (count($raters) < 2) return null;
    $keys = array_keys($raters);
    usort($keys, function ($a, $b) { return (strpos($a, 'teacher:') === 0 ? 0 : 1) <=> (strpos($b, 'teacher:') === 0 ? 0 : 1) ?: strcmp($a, $b); });
    $common = $ds['sids'];
    foreach ($keys as $key) $common = array_values(array_filter($common, function ($sid) use ($raters, $key, $domains) {
        if (!isset($raters[$key][$sid]) || $raters[$key][$sid]['total'] === null) return false;
        foreach ($domains as $domainKey => $_) if (($raters[$key][$sid]['domains'][$domainKey] ?? null) === null) return false;
        return true;
    }));
    if (count($common) < 3) return null;
    $sets = ['total' => ['label' => 'คะแนนรวม', 'max' => 60]];
    foreach ($domains as $key => $domain) $sets[$key] = ['label' => 'ด้านที่ ' . $domain['no'] . ' ' . $domain['name'], 'max' => $domain['max']];
    foreach ($sets as $setKey => &$set) {
        $matrix = [];
        foreach ($common as $sid) {
            $row = [];
            foreach ($keys as $key) $row[] = $setKey === 'total' ? $raters[$key][$sid]['total'] : $raters[$key][$sid]['domains'][$setKey];
            $matrix[] = $row;
        }
        $set['matrix'] = $matrix; $set['icc'] = ch45_icc($matrix); $set['pearson'] = [];
        for ($i = 0; $i < count($keys); $i++) for ($j = $i + 1; $j < count($keys); $j++) {
            $a = array_column($matrix, $i); $b = array_column($matrix, $j);
            $set['pearson'][] = ch45_pearson($a, $b)['r'];
        }
    }
    unset($set);
    return ['phase' => $phase, 'label' => $label, 'keys' => $keys, 'common' => $common, 'raters' => $raters, 'sets' => $sets];
}

function appendix_icc_section(array $ds) {
    $phases = ['pretest' => 'ก่อนเรียน', 'task1' => 'ภาระงานหน่วยที่ 1', 'task2' => 'ภาระงานหน่วยที่ 2', 'posttest' => 'หลังเรียน'];
    $all = [];
    foreach ($phases as $phase => $label) { $one = appendix_icc_phase($ds, $phase, $label); if ($one) $all[] = $one; }
    $html = '<div class="pagebreak"></div><h2>ความเที่ยงระหว่างผู้ประเมิน (ICC — Inter-rater Reliability)</h2>'
        . '<p class="note">ใช้ ICC แบบสองทางผสม ความสอดคล้องสัมบูรณ์ (two-way mixed effects, absolute agreement) เป็นค่าหลักในการสรุปผล ตามเกณฑ์แปลผลของ Koo &amp; Li (2016) — Pearson r แสดงประกอบเป็นค่าความสัมพันธ์รายคู่เท่านั้น</p>'
        . '<table><thead><tr><th>รอบ</th><th>ผู้ประเมิน</th><th>n</th><th>ICC(3,1)</th><th>ICC(3,k)</th><th>p</th><th>แปลผล (ยึดตาม ICC)</th><th>Pearson r รายคู่ (ประกอบ)</th></tr></thead><tbody>';
    foreach ($all as $phase) foreach ($phase['sets'] as $set) {
        $pairs = []; foreach ($set['pearson'] as $i => $r) $pairs[] = 'คู่ ' . ($i + 1) . ': r = ' . appendix_stat_number($r);
        $html .= '<tr><td>' . appendix_h($phase['label'] . ' — ' . $set['label']) . '</td><td>' . count($phase['keys']) . '</td><td>' . count($phase['common']) . '</td>'
            . '<td>' . appendix_stat_number($set['icc']['icc1']) . '</td><td>' . appendix_stat_number($set['icc']['iccK']) . '</td><td>p ' . appendix_p($set['icc']['p']) . '</td>'
            . '<td>' . appendix_h(ch45_icc_label($set['icc']['iccK'])) . '</td><td>' . appendix_h(implode(', ', $pairs)) . '</td></tr>';
    }
    if (!$all) $html .= '<tr><td colspan="8" class="empty">ยังคำนวณ ICC ไม่ได้ — ต้องมีนักเรียนอย่างน้อย 3 คนที่ผู้ตรวจอย่างน้อย 2 ท่านให้คะแนนครบ</td></tr>';
    $html .= '</tbody></table>';
    foreach ($all as $phase) {
        $html .= '<div class="pagebreak"></div><h2>คะแนนรายคนที่ใช้คำนวณ ICC — ' . appendix_h($phase['label']) . '</h2>'
            . '<p class="note">เลขในหัวตารางหมายถึงผู้ตรวจตามลำดับ: ';
        $raterNames = []; foreach ($phase['keys'] as $i => $key) $raterNames[] = ($i + 1) . ' = ' . substr($key, strpos($key, ':') + 1);
        $html .= appendix_h(implode(' · ', $raterNames)) . '</p><p class="note">ข้อมูลชุดเดียวกันจากผู้ตรวจทุกคนถูกคำนวณแยก 5 ชุด ได้แก่ คะแนนรวมและคะแนนด้านที่ 1–4 โดยรายงาน M และ SD ก่อนใช้ two-way mixed effects, absolute agreement คำนวณ ICC(3,k) ของคะแนนเฉลี่ยจากผู้ตรวจทั้ง k คน</p>';
        $k = count($phase['keys']);
        $html .= '<table><thead><tr><th rowspan="2">นักเรียนคนที่</th>';
        foreach (array_slice($phase['sets'], 1) as $set) $html .= '<th colspan="' . $k . '">' . appendix_h($set['label']) . ' (' . $set['max'] . ')</th>';
        $html .= '<th rowspan="2">คะแนนรวมเฉลี่ย<br>(60)</th></tr><tr>';
        for ($d = 0; $d < 4; $d++) for ($i = 1; $i <= $k; $i++) $html .= '<th>' . $i . '</th>';
        $html .= '</tr></thead><tbody>';
        foreach ($phase['common'] as $sid) {
            $html .= '<tr><td>' . $ds['students'][$sid]['no'] . '</td>';
            foreach (array_slice($phase['sets'], 1) as $domainKey => $set) foreach ($phase['keys'] as $key) $html .= '<td class="num">' . appendix_number($phase['raters'][$key][$sid]['domains'][$domainKey]) . '</td>';
            $totals = []; foreach ($phase['keys'] as $key) $totals[] = $phase['raters'][$key][$sid]['total'];
            $html .= '<td class="num">' . appendix_number(ch45_mean($totals)) . '</td></tr>';
        }
        foreach (['mean' => 'ค่าเฉลี่ย (M)', 'sd' => 'ส่วนเบี่ยงเบนมาตรฐาน (SD)'] as $stat => $label) {
            $html .= '<tr class="average"><td>' . $label . '</td>';
            foreach (array_slice($phase['sets'], 1) as $set) foreach (range(0, $k - 1) as $i) { $d = ch45_describe(array_column($set['matrix'], $i)); $html .= '<td class="num">' . appendix_number($d[$stat]) . '</td>'; }
            $totMeans = []; foreach ($phase['sets']['total']['matrix'] as $row) $totMeans[] = ch45_mean($row);
            $d = ch45_describe($totMeans); $html .= '<td class="num">' . appendix_number($d[$stat]) . '</td></tr>';
        }
        $html .= '<tr class="average"><td>สถิติความเที่ยง</td>';
        foreach (array_slice($phase['sets'], 1) as $set) $html .= '<td colspan="' . $k . '">ICC(3,k) = ' . appendix_stat_number($set['icc']['iccK']) . '<br>p ' . appendix_p($set['icc']['p']) . ' · ' . appendix_h(ch45_icc_label($set['icc']['iccK'])) . '</td>';
        $overall = $phase['sets']['total']; $html .= '<td>ICC รวม = ' . appendix_stat_number($overall['icc']['iccK']) . '<br>p ' . appendix_p($overall['icc']['p']) . '</td></tr></tbody></table>';
    }
    return $html;
}

function appendix_defect_records(array $ds) {
    $indicators = ch45_indicators();
    $html = '<div class="pagebreak"></div><h2>แบบบันทึกร่องรอยข้อบกพร่องเชิงคุณภาพรายบุคคล</h2>'
        . '<p class="note">รวบรวมรายการปัญหาและแนวทางแก้ไขที่บันทึกไว้ระหว่างการเรียน แยกนักเรียนและแผนการเรียนรู้</p>';
    foreach ($ds['sids'] as $sid) {
        $html .= '<h3>นักเรียนลำดับที่ ' . appendix_h($ds['students'][$sid]['no']) . ' · '
            . appendix_h($sid) . ' · ' . appendix_h($ds['students'][$sid]['name']) . '</h3>';
        $html .= '<table><thead><tr><th>แผน</th><th>ตัวบ่งชี้</th><th>ร่องรอยข้อบกพร่องที่บันทึก</th><th>แนวทางแก้ไข/ข้อเสนอแนะ</th></tr></thead><tbody>';
        $count = 0;
        foreach ([1 => 'แผนการเรียนรู้ที่ 1', 2 => 'แผนการเรียนรู้ที่ 2'] as $unit => $unitLabel) {
            $record = $ds['reflect']['problems'][$sid][$unit] ?? [];
            foreach ($indicators as $id => $indicator) {
                $key = str_replace('.', '_', $id);
                $problem = trim((string)($record['prob_' . $key] ?? ''));
                $solution = trim((string)($record['sol_' . $key] ?? ''));
                if ($problem === '' && $solution === '') continue;
                $count++;
                $html .= '<tr><td>' . appendix_h($unitLabel) . '</td><td>' . appendix_h($id . ' ' . $indicator['name'])
                    . '</td><td>' . nl2br(appendix_h($problem ?: '—')) . '</td><td>' . nl2br(appendix_h($solution ?: '—')) . '</td></tr>';
            }
        }
        if (!$count) $html .= '<tr><td colspan="4" class="empty">— ยังไม่มีบันทึกร่องรอยข้อบกพร่อง —</td></tr>';
        $html .= '</tbody></table>';
    }
    return $html;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$group = trim((string)($input['group'] ?? ''));
if ($group === 'all') $group = '';
$classroom = trim((string)($input['classroom'] ?? ''));

try {
    $ds = ch45_dataset($pdo, ['group' => $group, 'classroom' => $classroom]);
    // ICC ใช้กลุ่มที่มีผู้ตรวจซ้ำโดยเฉพาะเสมอ ไม่เปลี่ยนตามตัวกรองเอกสารหลัก
    $iccDs = ch45_dataset($pdo, ['group' => CH45_ICC_GROUP]);
    $scope = $group !== '' ? $group : 'ทุกกลุ่ม';
    if ($classroom !== '') $scope .= ' ห้อง ' . $classroom;
    $body = '<h1>ภาคผนวก<br>คะแนนดิบและหลักฐานการประเมินผลงาน</h1>'
        . '<p class="center">ขอบเขตข้อมูล: ' . appendix_h($scope) . ' · นักเรียน ' . count($ds['sids']) . ' คน</p>'
        . '<p class="note">คะแนนรายด้าน ได้แก่ เนื้อหาสาระ 27 คะแนน องค์ประกอบและการลำดับเรื่อง 12 คะแนน การใช้สำนวนภาษา 15 คะแนน และอักขรวิธีและกลไกการเขียน 6 คะแนน รวม 60 คะแนน ค่าเฉลี่ยคำนวณจากผู้ตรวจที่มีข้อมูลจริง โดยแสดงจำนวนผู้ตรวจกำกับทุกแถว</p>';
    $body .= appendix_score_table($ds, ['pretest' => 'ก่อนเรียน (Pre-test)', 'posttest' => 'หลังเรียน (Post-test)'],
        'ตารางคะแนนดิบการทดสอบก่อนเรียนและหลังเรียน (Pre-test & Post-test)');
    $body .= '<div class="pagebreak"></div>' . appendix_score_table($ds,
        ['task1' => 'แผนการเรียนรู้ที่ 1 (เรียงความเชิงบรรยาย)', 'task2' => 'แผนการเรียนรู้ที่ 2 (เรียงความเชิงวิจารณ์)'],
        'ตารางคะแนนดิบและการประเมินผลงานระหว่างเรียน');
    $body .= appendix_icc_section($iccDs);
    $body .= appendix_defect_records($ds);
    $css = '@page{size:A4 landscape;margin:1.2cm}body{font-family:"TH Sarabun New",Tahoma,sans-serif;font-size:14pt;color:#111}h1{text-align:center;font-size:24pt;margin:90pt 0 24pt}h2{font-size:18pt;margin:22pt 0 8pt}h3{font-size:15pt;margin:18pt 0 5pt}.center{text-align:center}.note{color:#444}.pagebreak{page-break-before:always}table{border-collapse:collapse;width:100%;margin:8pt 0 18pt;font-size:10.5pt}th,td{border:1px solid #555;padding:4pt;vertical-align:top}th{background:#e2e8f0;text-align:center}.num{text-align:right}.average{font-weight:bold;background:#f1f5f9}.empty{text-align:center;color:#777}';
    $html = '<!doctype html><html lang="th"><head><meta charset="utf-8"><title>ภาคผนวกคะแนนดิบ</title><style>' . $css . '</style></head><body>' . $body . '</body></html>';
    echo json_encode(['success' => true, 'html' => $html, 'title' => 'ภาคผนวก_คะแนนดิบ_' . date('Y-m-d'), 'students' => count($ds['sids'])], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'สร้างเอกสารภาคผนวกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
