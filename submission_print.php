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

// ---- การประเมินตามเกณฑ์ (ประเมินตนเอง / ประเมินเพื่อน) แยกตามหน่วยการเรียน ----
// ประเมินตนเอง = มีแถวที่นักเรียนประเมินงานของตัวเองในรอบนั้น
// ประเมินเพื่อน = "นักเรียนคนนี้ไปประเมินให้เพื่อน" จึงดูจากชื่อผู้ประเมิน (evaluator_name)
// ไม่ใช่ student_id ของแถว (ซึ่งเป็นเจ้าของผลงาน) — กติกาเดียวกับ api.php (get_submission_report)
$evalSet = [
    'self' => [1 => [], 2 => []],
    'peer' => [1 => [], 2 => []],
];
$nameToIds = [];
try {
    $allStuStmt = $pdo->query('SELECT student_id, student_name FROM students');
    while ($r = $allStuStmt->fetch()) {
        foreach ([trim((string)$r['student_name']), trim(formatNamePrefix($r['student_name']))] as $variant) {
            if ($variant === '') continue;
            $nameToIds[$variant][$r['student_id']] = true;
        }
    }
} catch (Exception $e) { /* ปล่อยว่าง */ }
$phaseUnitMap = ['task1' => 1, 'task2' => 2];
try {
    $evStmt = $pdo->query("SELECT student_id, evaluator_type, evaluator_name, test_phase
                           FROM evaluations
                           WHERE evaluator_type IN ('ตนเองประเมิน', 'เพื่อนประเมิน')");
    while ($r = $evStmt->fetch()) {
        // ก่อนเรียน/หลังเรียนไม่มีภาระงานประเมินตนเอง-ประเมินเพื่อน
        if (!isset($phaseUnitMap[$r['test_phase']])) continue;
        $u = $phaseUnitMap[$r['test_phase']];
        if ($r['evaluator_type'] === 'ตนเองประเมิน') {
            $evalSet['self'][$u][$r['student_id']] = true;
            continue;
        }
        $evName = trim((string)$r['evaluator_name']);
        if (!isset($nameToIds[$evName])) continue;
        foreach (array_keys($nameToIds[$evName]) as $reviewerId) {
            if ($reviewerId === $r['student_id']) continue;
            $evalSet['peer'][$u][$reviewerId] = true;
        }
    }
} catch (Exception $e) { /* ตารางอาจยังไม่มี */ }

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
        'self1'        => isset($evalSet['self'][1][$sid]),
        'peer1'        => isset($evalSet['peer'][1][$sid]),
        'problems1'    => isset($flagSet['problems'][1][$sid]),
        'checklist1'   => isset($flagSet['checklist'][1][$sid]),
        'reflection1'  => isset($flagSet['reflection'][1][$sid]),
        'd2_1'         => isset($essaySet['task2_d1'][$sid]),
        'd2_2'         => isset($essaySet['task2_d2'][$sid]),
        'self2'        => isset($evalSet['self'][2][$sid]),
        'peer2'        => isset($evalSet['peer'][2][$sid]),
        'problems2'    => isset($flagSet['problems'][2][$sid]),
        'checklist2'   => isset($flagSet['checklist'][2][$sid]),
        'reflection2'  => isset($flagSet['reflection'][2][$sid]),
        'posttest'     => isset($essaySet['posttest'][$sid]),
    ];
}

// คอลัมน์สถานะ 16 ช่อง (ตามลำดับการแสดงผล) พร้อมคลาสสีตามหน่วยการเรียน
$statusCols = [
    'pretest',
    'd1_1','d1_2','self1','peer1','problems1','checklist1','reflection1',
    'd2_1','d2_2','self2','peer2','problems2','checklist2','reflection2',
    'posttest',
];
$colUnitClass = [
    'pretest' => '', 'posttest' => '',
    'd1_1' => 'u1', 'd1_2' => 'u1', 'self1' => 'u1', 'peer1' => 'u1',
    'problems1' => 'u1', 'checklist1' => 'u1', 'reflection1' => 'u1',
    'd2_1' => 'u2', 'd2_2' => 'u2', 'self2' => 'u2', 'peer2' => 'u2',
    'problems2' => 'u2', 'checklist2' => 'u2', 'reflection2' => 'u2',
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

// ---- ข้อมูลสำหรับ "กระดานข่าววิ่ง" (ticker) ใครเหลืองานอะไรบ้าง ----
// ชื่อภาระงานแบบสั้น พอให้อ่านทันตอนวิ่งผ่าน (น.1 / น.2 = หน่วยการเรียนที่ 1 / 2)
$colLabel = [
    'pretest'     => 'ก่อนเรียน',
    'd1_1'        => 'D1.1',
    'd1_2'        => 'D1.2',
    'self1'       => 'ประเมินตนเอง น.1',
    'peer1'       => 'ประเมินเพื่อน น.1',
    'problems1'   => 'ปัญหาการเขียน น.1',
    'checklist1'  => 'ตรวจสอบตนเอง น.1',
    'reflection1' => 'สะท้อนการเรียนรู้ น.1',
    'd2_1'        => 'D2.1',
    'd2_2'        => 'D2.2',
    'self2'       => 'ประเมินตนเอง น.2',
    'peer2'       => 'ประเมินเพื่อน น.2',
    'problems2'   => 'ปัญหาการเขียน น.2',
    'checklist2'  => 'ตรวจสอบตนเอง น.2',
    'reflection2' => 'สะท้อนการเรียนรู้ น.2',
    'posttest'    => 'หลังเรียน',
];
$colCount = count($statusCols);

$ticker = [];
foreach ($report as $r) {
    $missing = [];
    foreach ($statusCols as $k) { if (!$r[$k]) $missing[] = $colLabel[$k]; }
    $ticker[] = [
        'name'    => $r['student_name'],
        'room'    => $r['classroom'],
        'missing' => $missing,
        'left'    => count($missing),
    ];
}
// เรียงแบบกระดานหุ้น: ใครค้างมากที่สุดวิ่งมาก่อน (ค้างเท่ากันเรียงตามห้อง/ชื่อ)
usort($ticker, function ($a, $b) {
    if ($a['left'] !== $b['left']) return $b['left'] - $a['left'];
    $c = strcmp((string)$a['room'], (string)$b['room']);
    return $c !== 0 ? $c : strcmp((string)$a['name'], (string)$b['name']);
});
$pendingStudents = 0;
foreach ($ticker as $t) { if ($t['left'] > 0) $pendingStudents++; }
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
  table.report td.mark { font-weight: 700; color: #0a6b2e; font-size: 20px; padding: 6px 2px; }
  /* คอลัมน์เพิ่มเป็น 16 ช่อง — บีบหัวตารางแถวย่อยให้พอดีความกว้าง A4 แนวนอน */
  table.report thead tr + tr th { font-size: 15px; padding: 5px 2px; line-height: 1.1; }
  table.report td.u1 { background: #f4f8ff; }
  table.report td.u2 { background: #f6f5ff; }
  table.report tfoot td { background: #f4f6f9; font-weight: 700; }
  table.report tfoot td.foot-label { text-align: right; }
  .legend { font-size: 16px; color: #555; margin-top: 10px; }
  .empty { text-align: center; padding: 80px 20px; color: #94a3b8; font-family: "Tahoma", sans-serif; }

  /* ── กระดานข่าววิ่ง "งานค้าง" แบบกระดานหุ้น (แสดงบนจอเท่านั้น) ── */
  .ticker {
    display: flex; align-items: stretch; overflow: hidden;
    background: #0b1727; color: #e6edf5;
    border-top: 1px solid rgba(255,255,255,.10); border-bottom: 3px solid #0d3b66;
    font-family: "Tahoma", sans-serif;
  }
  .ticker-label {
    flex: 0 0 auto; display: flex; align-items: center; gap: 10px;
    background: #0d3b66; padding: 8px 14px; font-size: 13px; font-weight: 700;
  }
  .ticker-label .tk-rate {
    background: rgba(255,255,255,.16); border-radius: 999px; padding: 2px 10px;
    font-size: 12px; font-variant-numeric: tabular-nums; white-space: nowrap;
  }
  .ticker-view { position: relative; flex: 1 1 auto; overflow: hidden; }
  /* ไล่เงาขอบซ้าย-ขวา ให้ข้อความค่อย ๆ โผล่/หายเหมือนกระดานข่าว */
  .ticker-view::before, .ticker-view::after {
    content: ""; position: absolute; top: 0; bottom: 0; width: 34px; pointer-events: none; z-index: 2;
  }
  .ticker-view::before { left: 0;  background: linear-gradient(90deg, #0b1727, rgba(11,23,39,0)); }
  .ticker-view::after  { right: 0; background: linear-gradient(270deg, #0b1727, rgba(11,23,39,0)); }
  .ticker-track {
    display: flex; width: max-content; will-change: transform;
    animation: tk-scroll var(--tk-duration, 45s) linear infinite;
  }
  .ticker:hover .ticker-track, .ticker.paused .ticker-track { animation-play-state: paused; }
  @keyframes tk-scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(calc(-1 * var(--tk-shift, 100%))); }
  }
  .tk-group { display: flex; align-items: center; }
  .tk-item {
    display: inline-flex; align-items: baseline; gap: 9px; white-space: nowrap;
    padding: 9px 18px; font-size: 14px; border-right: 1px solid rgba(255,255,255,.08);
  }
  .tk-name  { font-weight: 700; }
  .tk-room  { color: #8fa6c0; font-size: 12px; }
  .tk-delta { font-weight: 700; font-variant-numeric: tabular-nums; }
  .tk-jobs  { font-size: 13px; color: #cbd8e6; }
  .tk-late .tk-delta { color: #ff6b6b; }
  .tk-warn .tk-delta { color: #ffc65c; }
  .tk-done .tk-delta, .tk-done .tk-jobs { color: #3ddc97; }
  .ticker-toggle {
    flex: 0 0 auto; background: transparent; color: #cbd8e6; border: 0; border-left: 1px solid rgba(255,255,255,.12);
    padding: 0 14px; font-size: 13px; font-family: inherit; cursor: pointer; white-space: nowrap;
  }
  .ticker-toggle:hover { background: rgba(255,255,255,.08); color: #fff; }

  @media (prefers-reduced-motion: reduce) {
    .ticker-track { animation: none; }
    .ticker-view { overflow-x: auto; }
  }

  @media print {
    html, body { background: #fff; }
    .toolbar, .ticker { display: none !important; }
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

  <?php if (!empty($ticker)): ?>
  <div class="ticker" id="submissionTicker">
    <div class="ticker-label">
      <span>📈 กระดานงานค้าง</span>
      <span class="tk-rate">ส่งแล้ว <?php echo $rate; ?>% · ค้าง <?php echo $pendingStudents; ?> คน</span>
    </div>
    <div class="ticker-view">
      <div class="ticker-track" id="tickerTrack">
        <div class="tk-group">
          <?php foreach ($ticker as $t): ?>
            <?php
              $left = $t['left'];
              $cls  = $left === 0 ? 'tk-done' : ($left <= 3 ? 'tk-warn' : 'tk-late');
              // โชว์ไม่เกิน 6 ชิ้นแรก ที่เหลือสรุปเป็นตัวเลข ไม่งั้นข้อความยาวจนอ่านไม่ทันตอนวิ่ง
              $shown = array_slice($t['missing'], 0, 6);
              $more  = $left - count($shown);
              $jobs  = $left === 0
                  ? 'ส่งครบทุกชิ้น'
                  : implode(' · ', $shown) . ($more > 0 ? ' +อีก ' . $more : '');
              $delta = $left === 0 ? '▲ ' . $colCount . '/' . $colCount : '▼ ค้าง ' . $left;
            ?>
            <span class="tk-item <?php echo $cls; ?>">
              <span class="tk-name"><?php echo $hh($t['name']); ?></span>
              <span class="tk-room"><?php echo $hh($t['room']); ?></span>
              <span class="tk-delta"><?php echo $hh($delta); ?></span>
              <span class="tk-jobs"><?php echo $hh($jobs); ?></span>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <button type="button" class="ticker-toggle" id="tickerToggle" aria-pressed="false">⏸ หยุด</button>
  </div>
  <?php endif; ?>

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
            <th colspan="7" class="u1">หน่วยการเรียนที่ 1</th>
            <th colspan="7" class="u2">หน่วยการเรียนที่ 2</th>
            <th rowspan="2">หลังเรียน</th>
          </tr>
          <tr>
            <th class="u1">D1.1</th>
            <th class="u1">D1.2</th>
            <th class="u1">ประเมิน<br>ตนเอง</th>
            <th class="u1">ประเมิน<br>เพื่อน</th>
            <th class="u1">ปัญหา<br>การเขียน</th>
            <th class="u1">ตรวจสอบ<br>ตนเอง</th>
            <th class="u1">สะท้อน<br>การเรียนรู้</th>
            <th class="u2">D2.1</th>
            <th class="u2">D2.2</th>
            <th class="u2">ประเมิน<br>ตนเอง</th>
            <th class="u2">ประเมิน<br>เพื่อน</th>
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
            <?php foreach ($statusCols as $k): ?>
              <td class="mark <?php echo $colUnitClass[$k]; ?>"><?php echo $mark($r[$k]); ?></td>
            <?php endforeach; ?>
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
      <div class="legend">เครื่องหมาย ✓ = ส่งแล้ว · ช่องว่าง = ยังไม่ส่ง · D1/D2 = ร่างที่ 1 / ร่างที่ 2 ของภาระงานแต่ละหน่วย · ประเมินเพื่อน = นักเรียนคนนี้ประเมินงานให้เพื่อนแล้ว · การประเมินและสะท้อนคิดแยกบันทึกตามหน่วยการเรียน</div>
      <?php endif; ?>
  </div><!-- /.paper -->

<script>
// กระดานข่าววิ่ง: ทำสำเนาชุดข้อมูลให้ยาวพอเต็มจอ แล้วเลื่อนวนแบบไม่มีรอยต่อ
(function () {
  var ticker = document.getElementById('submissionTicker');
  var track  = document.getElementById('tickerTrack');
  if (!ticker || !track) return;

  var group = track.querySelector('.tk-group');
  var PX_PER_SEC = 70; // ความเร็วข่าววิ่ง (พิกเซลต่อวินาที)

  function layout() {
    var clones = track.querySelectorAll('.tk-group.tk-clone');
    for (var i = 0; i < clones.length; i++) clones[i].remove();

    var width = group.scrollWidth;
    if (!width) return;
    var viewWidth = ticker.querySelector('.ticker-view').clientWidth;
    // ต้องมีเนื้อหาอย่างน้อย 1 ชุด + เต็มความกว้างจอ เพื่อให้วนแล้วไม่เห็นช่องว่าง
    var copies = Math.ceil(viewWidth / width) + 1;
    for (var j = 0; j < copies; j++) {
      var clone = group.cloneNode(true);
      clone.classList.add('tk-clone');
      clone.setAttribute('aria-hidden', 'true');
      track.appendChild(clone);
    }
    track.style.setProperty('--tk-shift', width + 'px');
    track.style.setProperty('--tk-duration', Math.max(12, width / PX_PER_SEC) + 's');
  }

  layout();

  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(layout, 200);
  });

  var toggle = document.getElementById('tickerToggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var paused = ticker.classList.toggle('paused');
      toggle.textContent = paused ? '▶ เล่นต่อ' : '⏸ หยุด';
      toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
    });
  }
})();
</script>
</body>
</html>
