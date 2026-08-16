<?php
// reflection_print.php
// เอกสาร "รายงานเครื่องมือส่งเสริมการเรียนรู้ (สะท้อนคิด)" สำหรับดูตัวอย่างก่อนพิมพ์/บันทึกเป็น PDF
// รวมข้อมูล 3 ส่วนของนักเรียนแต่ละคน: อุปสรรคและแผนแก้ปัญหา / การตรวจสอบตนเอง / การสะท้อนคิด
// แยกตามหน่วยการเรียน (task_unit) ตามที่ครูเลือกจากหน้าแดชบอร์ด

require_once 'auth_helper.php';
require_login('teacher');

$hh = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// ---- ตัวกรอง: หน่วยการเรียน (1/2) + กลุ่มการวิจัย ----
$unit  = (isset($_GET['unit']) && intval($_GET['unit']) === 2) ? 2 : 1;
$group = isset($_GET['group']) ? trim($_GET['group']) : '';

// ---- ดึงข้อมูลนักเรียน + เครื่องมือสะท้อนคิดของหน่วยที่เลือก (LEFT JOIN แยกตาม task_unit) ----
$params = [$unit, $unit, $unit];
$sql = '
    SELECT
        s.student_id, s.student_name, s.classroom, s.student_group,
        wp.prob_1_1, wp.sol_1_1, wp.prob_1_2, wp.sol_1_2, wp.prob_1_3, wp.sol_1_3,
        wp.prob_2_1, wp.sol_2_1, wp.prob_2_2, wp.sol_2_2,
        wp.prob_3_1, wp.sol_3_1, wp.prob_3_2, wp.sol_3_2, wp.prob_3_3, wp.sol_3_3,
        wp.prob_4_1, wp.sol_4_1, wp.prob_4_2, wp.sol_4_2, wp.prob_4_3, wp.sol_4_3,
        chk.check_1_1, chk.check_1_2, chk.check_1_3,
        chk.check_2_1, chk.check_2_2,
        chk.check_3_1, chk.check_3_2, chk.check_3_3,
        chk.check_4_1, chk.check_4_2, chk.check_4_3,
        chk.notes AS checklist_notes,
        ref.content_structure, ref.language_mechanics, ref.feedback_applied, ref.future_goals
    FROM students s
    LEFT JOIN writing_problems wp     ON s.student_id = wp.student_id  AND wp.task_unit  = ?
    LEFT JOIN self_checklists chk     ON s.student_id = chk.student_id AND chk.task_unit = ?
    LEFT JOIN learning_reflections ref ON s.student_id = ref.student_id AND ref.task_unit = ?';
if ($group !== '') {
    $sql .= ' WHERE s.student_group = ?';
    $params[] = $group;
}
$sql .= ' ORDER BY s.classroom ASC, s.student_id ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}

// เกณฑ์ 11 ข้อ (ตรงกับหน้าเครื่องมือสะท้อนคิด)
$criteria = [
    '1_1' => '1.1 ความตรงประเด็น',
    '1_2' => '1.2 แก่นเรื่องชัดเจน',
    '1_3' => '1.3 การขยายความและเหตุผล',
    '2_1' => '2.1 องค์ประกอบครบ',
    '2_2' => '2.2 ลำดับประเด็นเป็นระบบ',
    '3_1' => '3.1 ประโยคถูกต้อง',
    '3_2' => '3.2 เลือกใช้คำ',
    '3_3' => '3.3 ระดับภาษาเหมาะสม',
    '4_1' => '4.1 การสะกดคำถูกต้อง',
    '4_2' => '4.2 การเว้นวรรค',
    '4_3' => '4.3 ความเรียบร้อย',
];
$reflectFields = [
    'content_structure'  => 'ด้านเนื้อหาสาระและองค์ประกอบ',
    'language_mechanics' => 'ด้านการใช้สำนวนภาษาและอักขรวิธี',
    'feedback_applied'   => 'การนำข้อเสนอแนะไปปรับปรุงงาน',
    'future_goals'       => 'การประยุกต์ใช้และเป้าหมายในอนาคต',
];

$nz = function ($v) { return $v !== null && trim((string)$v) !== ''; };

// คัดเฉพาะนักเรียนที่มีข้อมูลอย่างน้อยหนึ่งส่วนในหน่วยนี้
$withData = [];
foreach ($rows as $r) {
    $hasOb = false; $hasChk = false; $hasRef = false;
    foreach ($criteria as $k => $_) {
        if ($nz($r["prob_$k"]) || $nz($r["sol_$k"])) $hasOb = true;
        if ($nz($r["check_$k"])) $hasChk = true;
    }
    foreach ($reflectFields as $f => $_) { if ($nz($r[$f])) $hasRef = true; }
    if ($hasOb || $hasChk || $hasRef) {
        $r['_hasOb'] = $hasOb; $r['_hasChk'] = $hasChk; $r['_hasRef'] = $hasRef;
        $withData[] = $r;
    }
}

$groupLabel = ($group === '') ? 'ทุกกลุ่มรวมกัน' : $group;
$today = date('j/n/Y');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานเครื่องมือส่งเสริมการเรียนรู้ · หน่วยที่ <?php echo $unit; ?></title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: "TH Sarabun New", "Sarabun", "Segoe UI", Tahoma, sans-serif;
    color: #1e293b; margin: 0; padding: 0; background: #f1f5f9;
  }
  .toolbar {
    position: sticky; top: 0; z-index: 10;
    background: #0f172a; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center; justify-content: center;
  }
  .toolbar button {
    border: 0; border-radius: 999px; padding: 8px 20px; font-size: 15px;
    font-weight: 700; cursor: pointer;
  }
  .btn-print { background: #ef4444; color: #fff; }
  .btn-back  { background: #e2e8f0; color: #0f172a; }
  .sheet {
    background: #fff; max-width: 800px; margin: 16px auto; padding: 28px 32px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.08);
  }
  .doc-head { text-align: center; border-bottom: 3px double #334155; padding-bottom: 12px; margin-bottom: 6px; }
  .doc-head h1 { font-size: 24px; margin: 0 0 4px; }
  .doc-head .sub { font-size: 15px; color: #475569; }
  .meta { display: flex; justify-content: space-between; font-size: 14px; color: #475569; margin: 10px 2px 4px; }
  .student { border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 18px; overflow: hidden; page-break-inside: avoid; }
  .student > h2 {
    font-size: 17px; margin: 0; padding: 8px 14px; background: #1e3a8a; color: #fff;
    display: flex; justify-content: space-between; align-items: center;
  }
  .student > h2 .badges { font-size: 12px; font-weight: 400; }
  .student > h2 .badges span { background: rgba(255,255,255,.18); border-radius: 999px; padding: 2px 8px; margin-left: 4px; }
  .sec { padding: 8px 14px 12px; border-top: 1px dashed #e2e8f0; }
  .sec h3 { font-size: 15px; margin: 4px 0 6px; }
  .sec.ob h3   { color: #b91c1c; }
  .sec.chk h3  { color: #047857; }
  .sec.ref h3  { color: #0369a1; }
  table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
  th, td { border: 1px solid #cbd5e1; padding: 4px 7px; vertical-align: top; text-align: left; }
  th { background: #f1f5f9; font-weight: 700; }
  .chk-list { list-style: none; margin: 0; padding: 0; font-size: 13.5px; }
  .chk-list li { display: flex; justify-content: space-between; padding: 3px 2px; border-bottom: 1px dotted #e2e8f0; }
  .pill { border-radius: 999px; padding: 1px 9px; font-size: 12.5px; font-weight: 700; }
  .p-full { background: #dcfce7; color: #166534; }
  .p-part { background: #fef9c3; color: #854d0e; }
  .p-poor { background: #fee2e2; color: #991b1b; }
  .ref-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13.5px; }
  .ref-grid .cell { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 8px; }
  .ref-grid .cell strong { display: block; color: #475569; font-size: 12.5px; margin-bottom: 2px; }
  .empty { color: #94a3b8; font-size: 13.5px; margin: 2px 0; }
  .no-data { text-align: center; color: #64748b; padding: 40px; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { box-shadow: none; margin: 0; max-width: none; padding: 0 6mm; }
    @page { size: A4 portrait; margin: 12mm; }
  }
</style>
</head>
<body>
  <div class="toolbar">
    <button class="btn-print" onclick="window.print()">🖨️ พิมพ์ / บันทึกเป็น PDF</button>
    <button class="btn-back" onclick="window.close()">ปิดหน้าต่าง</button>
  </div>

  <div class="sheet">
    <div class="doc-head">
      <h1>รายงานเครื่องมือส่งเสริมการเรียนรู้ (การสะท้อนคิด)</h1>
      <div class="sub">ภาระงาน หน่วยที่ <?php echo $unit; ?> — อุปสรรคและแผนแก้ปัญหา · การตรวจสอบตนเอง · การสะท้อนคิดการเรียนรู้</div>
    </div>
    <div class="meta">
      <span>กลุ่ม: <?php echo $hh($groupLabel); ?></span>
      <span>จำนวนนักเรียนที่มีข้อมูล: <?php echo count($withData); ?> คน</span>
      <span>พิมพ์เมื่อ: <?php echo $hh($today); ?></span>
    </div>

    <?php if (empty($withData)): ?>
      <div class="no-data">— ยังไม่มีข้อมูลเครื่องมือสะท้อนคิดของหน่วยที่ <?php echo $unit; ?> ในกลุ่มนี้ —</div>
    <?php else: ?>
      <?php foreach ($withData as $r): ?>
        <div class="student">
          <h2>
            <span><?php echo $hh($r['student_id']); ?> — <?php echo $hh(formatNamePrefix($r['student_name'])); ?><?php echo $r['classroom'] ? ' (ห้อง ' . $hh($r['classroom']) . ')' : ''; ?></span>
            <span class="badges">
              <span><?php echo $r['_hasOb'] ? '✓' : '–'; ?> อุปสรรค</span>
              <span><?php echo $r['_hasChk'] ? '✓' : '–'; ?> ตรวจสอบตนเอง</span>
              <span><?php echo $r['_hasRef'] ? '✓' : '–'; ?> สะท้อนคิด</span>
            </span>
          </h2>

          <!-- 1. อุปสรรคและแผนการแก้ปัญหา -->
          <div class="sec ob">
            <h3>1. อุปสรรคและแผนการแก้ปัญหา</h3>
            <?php
              $obRows = '';
              foreach ($criteria as $k => $label) {
                  $p = $nz($r["prob_$k"]) ? $hh(trim($r["prob_$k"])) : '';
                  $s = $nz($r["sol_$k"])  ? $hh(trim($r["sol_$k"]))  : '';
                  if ($p !== '' || $s !== '') {
                      $obRows .= '<tr><td style="width:26%">' . $hh($label) . '</td><td style="width:37%">' . ($p ?: '-') . '</td><td style="width:37%">' . ($s ?: '-') . '</td></tr>';
                  }
              }
            ?>
            <?php if ($obRows !== ''): ?>
              <table><thead><tr><th>เกณฑ์</th><th>อุปสรรค</th><th>แผนแก้ปัญหา</th></tr></thead><tbody><?php echo $obRows; ?></tbody></table>
            <?php else: ?>
              <p class="empty">— ยังไม่มีการบันทึกอุปสรรคการเขียน —</p>
            <?php endif; ?>
          </div>

          <!-- 2. การตรวจสอบตนเอง -->
          <div class="sec chk">
            <h3>2. การตรวจสอบตนเอง</h3>
            <?php
              $chkItems = '';
              foreach ($criteria as $k => $label) {
                  $v = $r["check_$k"];
                  if ($nz($v)) {
                      $v = trim($v);
                      $cls = ($v === 'ครบถ้วน') ? 'p-full' : (($v === 'บางส่วน') ? 'p-part' : 'p-poor');
                      $chkItems .= '<li><span>' . $hh($label) . '</span><span class="pill ' . $cls . '">' . $hh($v) . '</span></li>';
                  }
              }
            ?>
            <?php if ($chkItems !== ''): ?>
              <ul class="chk-list"><?php echo $chkItems; ?></ul>
              <?php if ($nz($r['checklist_notes'])): ?>
                <p style="font-size:13px;margin:6px 0 0;"><strong>บันทึกเพิ่มเติม:</strong> <?php echo $hh(trim($r['checklist_notes'])); ?></p>
              <?php endif; ?>
            <?php else: ?>
              <p class="empty">— ยังไม่มีการตรวจสอบตนเอง —</p>
            <?php endif; ?>
          </div>

          <!-- 3. การสะท้อนคิดการเรียนรู้ -->
          <div class="sec ref">
            <h3>3. การสะท้อนคิดการเรียนรู้</h3>
            <?php
              $refCells = '';
              foreach ($reflectFields as $f => $label) {
                  if ($nz($r[$f])) {
                      $refCells .= '<div class="cell"><strong>' . $hh($label) . '</strong>' . $hh(trim($r[$f])) . '</div>';
                  }
              }
            ?>
            <?php if ($refCells !== ''): ?>
              <div class="ref-grid"><?php echo $refCells; ?></div>
            <?php else: ?>
              <p class="empty">— ยังไม่มีบทสะท้อนคิด —</p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script>
    // เปิดหน้าต่างพิมพ์อัตโนมัติเมื่อโหลดเสร็จ (ผู้ใช้เลือกบันทึกเป็น PDF ได้)
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
  </script>
</body>
</html>
