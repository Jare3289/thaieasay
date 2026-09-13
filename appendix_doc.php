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

function appendix_raters(array $ds, $sid, $phase) {
    $rows = [];
    foreach (['teacher', 'expert'] as $role) {
        foreach ($ds['evals'][$sid][$phase][$role] ?? [] as $evaluation) $rows[] = $evaluation;
    }
    return $rows;
}

function appendix_score_table(array $ds, array $phases, $caption) {
    $domains = ch45_domains();
    $html = '<h2>' . appendix_h($caption) . '</h2>';
    $html .= '<table><thead><tr><th>ลำดับ</th><th>รหัสนักเรียน</th><th>ชื่อ-สกุล</th><th>รอบ/ชิ้นงาน</th><th>ผู้ตรวจประเมิน</th>';
    foreach ($domains as $domain) $html .= '<th>' . appendix_h($domain['name']) . '<br>(' . $domain['max'] . ')</th>';
    $html .= '<th>รวม (60)</th></tr></thead><tbody>';
    $hasRows = false;
    foreach ($ds['sids'] as $sid) {
        foreach ($phases as $phase => $label) {
            $raters = appendix_raters($ds, $sid, $phase);
            if (!$raters) {
                $raters = [['rater' => '— ยังไม่มีผลประเมิน —', 'weighted' => [], 'total' => null]];
            }
            $domainSums = array_fill_keys(array_keys($domains), 0.0);
            $domainCounts = array_fill_keys(array_keys($domains), 0);
            $totalSum = 0.0; $totalCount = 0;
            foreach ($raters as $evaluation) {
                $hasRows = true;
                $html .= '<tr><td>' . appendix_h($ds['students'][$sid]['no']) . '</td><td>' . appendix_h($sid)
                    . '</td><td>' . appendix_h($ds['students'][$sid]['name']) . '</td><td>' . appendix_h($label)
                    . '</td><td>' . appendix_h($evaluation['rater']) . '</td>';
                $totals = ch45_domain_total($evaluation['weighted']);
                foreach ($domains as $key => $domain) {
                    $value = $totals[$key] ?? null;
                    $html .= '<td class="num">' . appendix_number($value) . '</td>';
                    if ($value !== null) { $domainSums[$key] += $value; $domainCounts[$key]++; }
                }
                $html .= '<td class="num">' . appendix_number($evaluation['total']) . '</td></tr>';
                if ($evaluation['total'] !== null) { $totalSum += (float)$evaluation['total']; $totalCount++; }
            }
            $html .= '<tr class="average"><td colspan="5">ค่าเฉลี่ยของนักเรียนคนนี้ · ' . appendix_h($label)
                . ' (' . $totalCount . ' ผู้ตรวจ)</td>';
            foreach ($domains as $key => $domain) {
                $html .= '<td class="num">' . appendix_number($domainCounts[$key] ? $domainSums[$key] / $domainCounts[$key] : null) . '</td>';
            }
            $html .= '<td class="num">' . appendix_number($totalCount ? $totalSum / $totalCount : null) . '</td></tr>';
        }
    }
    if (!$hasRows) $html .= '<tr><td colspan="10">ไม่พบนักเรียนในขอบเขตที่เลือก</td></tr>';
    return $html . '</tbody></table>';
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
    $body .= appendix_defect_records($ds);
    $css = '@page{size:A4 landscape;margin:1.2cm}body{font-family:"TH Sarabun New",Tahoma,sans-serif;font-size:14pt;color:#111}h1{text-align:center;font-size:24pt;margin:90pt 0 24pt}h2{font-size:18pt;margin:22pt 0 8pt}h3{font-size:15pt;margin:18pt 0 5pt}.center{text-align:center}.note{color:#444}.pagebreak{page-break-before:always}table{border-collapse:collapse;width:100%;margin:8pt 0 18pt;font-size:10.5pt}th,td{border:1px solid #555;padding:4pt;vertical-align:top}th{background:#e2e8f0;text-align:center}.num{text-align:right}.average{font-weight:bold;background:#f1f5f9}.empty{text-align:center;color:#777}';
    $html = '<!doctype html><html lang="th"><head><meta charset="utf-8"><title>ภาคผนวกคะแนนดิบ</title><style>' . $css . '</style></head><body>' . $body . '</body></html>';
    echo json_encode(['success' => true, 'html' => $html, 'title' => 'ภาคผนวก_คะแนนดิบ_' . date('Y-m-d'), 'students' => count($ds['sids'])], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'สร้างเอกสารภาคผนวกไม่สำเร็จ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
