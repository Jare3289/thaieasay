<?php
// submission_print.php
// เอกสาร "รายงานการส่งงานรายบุคคล" สำหรับดูตัวอย่างก่อนพิมพ์/บันทึกเป็น PDF
// ทำงานฝั่งเซิร์ฟเวอร์ ดึงข้อมูลจากฐานข้อมูลตรง ๆ แล้วย่อให้พอดี "กระดาษแผ่นเดียว หน้าเดียว" (A4 แนวตั้ง)

require_once 'auth_helper.php';
require_login('teacher');

$hh = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// เอาเฉพาะ "ชื่อ" (ตัดคำนำหน้าและนามสกุลออก) — หัวตารางแคบ ๆ จะได้อ่านง่าย
$firstNameOnly = function ($name) {
    $n = formatNamePrefix($name);
    $parts = preg_split('/\s+/u', trim($n));
    return ($parts && $parts[0] !== '') ? $parts[0] : $n;
};

// ---- ตัวกรองกลุ่มการวิจัย (ให้ตรงกับปุ่มเลือกกลุ่มบนหน้ารายงาน) ----
$groupParam = isset($_GET['group']) ? trim($_GET['group']) : '';

// ---- ดึงรายชื่อนักเรียนตามกลุ่ม ----
$stuSql = 'SELECT student_id, student_name, classroom, student_group FROM students';
$stuParams = [];
if ($groupParam !== '') {
    $stuSql .= ' WHERE student_group = ?';
    $stuParams[] = $groupParam;
}
$stuSql .= ' ORDER BY classroom ASC, student_id ASC';

try {
    $stuStmt = $pdo->prepare($stuSql);
    $stuStmt->execute($stuParams);
    $stuRows = $stuStmt->fetchAll();
} catch (Exception $e) {
    $stuRows = [];
}

// ---- เรียงความที่ส่งแล้ว (มีเนื้อหา หรือ word_count > 0) ----
$essaySet = [
    'pretest' => [], 'task1_d1' => [], 'task1_d2' => [],
    'task2_d1' => [], 'task2_d2' => [], 'posttest' => [],
];
try {
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
} catch (Exception $e) { /* ปล่อยว่าง */ }

// ---- เครื่องมือสะท้อนคิด (แยกตามหน่วยการเรียน หน่วยที่ 1 / หน่วยที่ 2) ----
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
    } catch (Exception $e) { /* ตารางอาจยังไม่มี */ }
}

// ---- ประกอบข้อมูลรายบุคคล ----
$report = [];
foreach ($stuRows as $s) {
    $sid = $s['student_id'];
    $report[] = [
        'student_id'   => $sid,
        'student_name' => $firstNameOnly($s['student_name']),
        'classroom'    => $s['classroom'],
        'pretest'      => isset($essaySet['pretest'][$sid]),
        'd1_1'         => isset($essaySet['task1_d1'][$sid]),
        'd1_2'         => isset($essaySet['task1_d2'][$sid]),
        'problems1'    => isset($flagSet['problems'][1][$sid]),
        'checklist1'   => isset($flagSet['checklist'][1][$sid]),
        'reflection1'  => isset($flagSet['reflection'][1][$sid]),
        'd2_1'         => isset($essaySet['task2_d1'][$sid]),
        'd2_2'         => isset($essaySet['task2_d2'][$sid]),
        'problems2'    => isset($flagSet['problems'][2][$sid]),
        'checklist2'   => isset($flagSet['checklist'][2][$sid]),
        'reflection2'  => isset($flagSet['reflection'][2][$sid]),
        'posttest'     => isset($essaySet['posttest'][$sid]),
    ];
}

// คอลัมน์สถานะ 12 ช่อง (ตามลำดับการแสดงผล) พร้อมคลาสสีตามหน่วยการเรียน
$statusCols = [
    'pretest',
    'd1_1','d1_2','problems1','checklist1','reflection1',
    'd2_1','d2_2','problems2','checklist2','reflection2',
    'posttest',
];
$colUnitClass = [
    'pretest' => '', 'posttest' => '',
    'd1_1' => 'u1', 'd1_2' => 'u1', 'problems1' => 'u1', 'checklist1' => 'u1', 'reflection1' => 'u1',
    'd2_1' => 'u2', 'd2_2' => 'u2', 'problems2' => 'u2', 'checklist2' => 'u2', 'reflection2' => 'u2',
];

// สรุปยอด
$totalStudents = count($report);
$totals = array_fill_keys($statusCols, 0);
$completeCount = 0;
foreach ($report as $r) {
    $done = 0;
    foreach ($statusCols as $k) { if ($r[$k]) { $totals[$k]++; $done++; } }
    if ($done === count($statusCols)) $completeCount++;
}
$cellsTotal = $totalStudents * count($statusCols);
$cellsDone  = array_sum($totals);
$rate = $cellsTotal ? round($cellsDone / $cellsTotal * 100) : 0;

$groupLabel = ($groupParam === '') ? 'ทุกกลุ่มการวิจัย' : $groupParam;
$genAt = date('d/m/Y H:i');

// ตัวช่วยแสดงสถานะ ✓ / ว่าง
$mark = function ($on) { return $on ? '✓' : ''; };
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานการส่งงานรายบุคคล</title>
<style>
  @page { size: A4 landscape; margin: 10mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; background: #eceff3; }
  body {
    font-family: "TH Sarabun PSK", "THSarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
    color: #000;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }

  /* แถบเครื่องมือ (ไม่พิมพ์) */
  .toolbar {
    background: #0d3b66; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center; justify-content: space-between;
    font-family: "Tahoma", sans-serif; font-size: 14px; flex-wrap: wrap;
  }
  .toolbar .tb-actions { display: flex; gap: 8px; }
  .toolbar button, .toolbar a {
    background: #fff; color: #0d3b66; border: 0; border-radius: 999px;
    padding: 7px 20px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; font-family: inherit;
  }
  .toolbar a.tb-close { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,.5); }

  /* กระดาษ A4 แนวนอน — เต็มขนาด อ่านชัด ไหลได้หลายหน้า */
  .paper {
    width: 277mm;         /* พื้นที่พิมพ์ A4 แนวนอน (297 - ขอบ 10mm×2) */
    max-width: 100%;
    margin: 18px auto;
    background: #fff;
    box-shadow: 0 3px 16px rgba(0,0,0,0.18);
    padding: 14px 18px;
  }

  .doc-title { text-align: center; font-size: 26px; font-weight: 700; margin: 0 0 2px; }
  .doc-sub   { text-align: center; font-size: 18px; color: #333; margin: 0 0 10px; }
  .doc-meta  { display: flex; flex-wrap: wrap; gap: 3px 26px; font-size: 18px; margin-bottom: 12px; }

  table.report { width: 100%; border-collapse: collapse; }
  table.report th, table.report td { border: 1px solid #444; padding: 7px 8px; font-size: 18px; text-align: center; line-height: 1.15; }
  table.report thead th { background: #e8eef5; font-weight: 700; }
  table.report thead th.u1 { background: #dbe8fb; }
  table.report thead th.u2 { background: #e2e0fb; }
  /* ทำซ้ำหัวตารางทุกหน้าเวลาพิมพ์ + ไม่ตัดกลางแถว */
  table.report thead { display: table-header-group; }
  table.report tfoot { display: table-footer-group; }
  table.report tr { page-break-inside: avoid; break-inside: avoid; }

  /* ช่อง ที่/รหัส/ชื่อ — กว้างเท่าเนื้อหา ไม่ปล่อยให้บานเกิน */
  table.report th.fit, table.report td.idx, table.report td.sid, table.report td.name { width: 1%; white-space: nowrap; }
  table.report td.idx { color: #444; }
  table.report td.sid { font-variant-numeric: tabular-nums; }
  table.report td.name { text-align: left; }
  /* ช่องสถานะที่เหลือ ขยายแบ่งพื้นที่เท่า ๆ กัน อ่านง่าย */
  table.report td.mark { font-weight: 700; color: #0a6b2e; font-size: 20px; }
  table.report td.u1 { background: #f4f8ff; }
  table.report td.u2 { background: #f6f5ff; }
  table.report tfoot td { background: #f4f6f9; font-weight: 700; }
  table.report tfoot td.foot-label { text-align: right; }
  .legend { font-size: 16px; color: #555; margin-top: 10px; }
  .empty { text-align: center; padding: 80px 20px; color: #94a3b8; font-family: "Tahoma", sans-serif; }

  @media print {
    html, body { background: #fff; }
    .toolbar { display: none !important; }
    .paper { width: auto; max-width: none; margin: 0; padding: 0; box-shadow: none; }
  }
</style>
</head>
<body>
  <div class="toolbar">
    <span>👀 ดูตัวอย่างก่อนพิมพ์ — ตารางเต็มขนาด อ่านชัด (A4 แนวนอน) หัวตารางจะซ้ำทุกหน้า · กดปุ่ม "พิมพ์ / บันทึก PDF" เมื่อพร้อม</span>
    <div class="tb-actions">
      <button onclick="window.print()">🖨️ พิมพ์ / บันทึก PDF</button>
      <a class="tb-close" href="submission_report.php">ปิด</a>
    </div>
  </div>

  <div class="paper">
      <h1 class="doc-title">รายงานการส่งงานรายบุคคล</h1>
      <div class="doc-sub">ระบบประเมินเรียงความ</div>
      <div class="doc-meta">
        <span><strong>กลุ่ม:</strong> <?php echo $hh($groupLabel); ?></span>
        <span><strong>จำนวนนักเรียน:</strong> <?php echo $totalStudents; ?> คน</span>
        <span><strong>ส่งครบทุกชิ้น:</strong> <?php echo $completeCount; ?> คน</span>
        <span><strong>อัตราการส่งงานเฉลี่ย:</strong> <?php echo $rate; ?>%</span>
        <span><strong>พิมพ์เมื่อ:</strong> <?php echo $hh($genAt); ?></span>
      </div>

      <?php if (empty($report)): ?>
        <div class="empty"><div style="font-size:40px">📭</div>ไม่พบนักเรียนในกลุ่มที่เลือก</div>
      <?php else: ?>
      <table class="report">
        <thead>
          <tr>
            <th class="fit" rowspan="2">ที่</th>
            <th class="fit" rowspan="2">รหัส</th>
            <th class="fit" rowspan="2" style="text-align:left;">ชื่อ</th>
            <th rowspan="2">ก่อนเรียน</th>
            <th colspan="5" class="u1">หน่วยการเรียนที่ 1</th>
            <th colspan="5" class="u2">หน่วยการเรียนที่ 2</th>
            <th rowspan="2">หลังเรียน</th>
          </tr>
          <tr>
            <th class="u1">D1.1</th>
            <th class="u1">D1.2</th>
            <th class="u1">ปัญหา<br>การเขียน</th>
            <th class="u1">ตรวจสอบ<br>ตนเอง</th>
            <th class="u1">สะท้อน<br>การเรียนรู้</th>
            <th class="u2">D2.1</th>
            <th class="u2">D2.2</th>
            <th class="u2">ปัญหา<br>การเขียน</th>
            <th class="u2">ตรวจสอบ<br>ตนเอง</th>
            <th class="u2">สะท้อน<br>การเรียนรู้</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0; foreach ($report as $r): $i++; ?>
          <tr>
            <td class="idx"><?php echo $i; ?></td>
            <td class="sid"><?php echo $hh($r['student_id']); ?></td>
            <td class="name"><?php echo $hh($r['student_name']); ?></td>
            <td class="mark"><?php echo $mark($r['pretest']); ?></td>
            <td class="mark u1"><?php echo $mark($r['d1_1']); ?></td>
            <td class="mark u1"><?php echo $mark($r['d1_2']); ?></td>
            <td class="mark u1"><?php echo $mark($r['problems1']); ?></td>
            <td class="mark u1"><?php echo $mark($r['checklist1']); ?></td>
            <td class="mark u1"><?php echo $mark($r['reflection1']); ?></td>
            <td class="mark u2"><?php echo $mark($r['d2_1']); ?></td>
            <td class="mark u2"><?php echo $mark($r['d2_2']); ?></td>
            <td class="mark u2"><?php echo $mark($r['problems2']); ?></td>
            <td class="mark u2"><?php echo $mark($r['checklist2']); ?></td>
            <td class="mark u2"><?php echo $mark($r['reflection2']); ?></td>
            <td class="mark"><?php echo $mark($r['posttest']); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td class="foot-label" colspan="3">รวมส่งแล้ว (คน)</td>
            <?php foreach ($statusCols as $k): ?>
              <td class="<?php echo $colUnitClass[$k]; ?>"><?php echo $totals[$k]; ?></td>
            <?php endforeach; ?>
          </tr>
        </tfoot>
      </table>
      <div class="legend">เครื่องหมาย ✓ = ส่งแล้ว · ช่องว่าง = ยังไม่ส่ง · D1/D2 = ร่างที่ 1 / ร่างที่ 2 ของภาระงานแต่ละหน่วย · สะท้อนคิดแยกบันทึกตามหน่วยการเรียน</div>
      <?php endif; ?>
  </div><!-- /.paper -->
</body>
</html>
