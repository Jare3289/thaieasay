<?php
// essay_print.php
// เอกสาร "แบบเขียนเรียงความ" สำหรับพิมพ์/บันทึกเป็น PDF
// เป็นเอกสารฝั่งเซิร์ฟเวอร์ ดึงข้อมูลจากฐานข้อมูลโดยตรง ไม่โหลดทรัพยากรภายนอกใด ๆ
// รองรับทั้งแบบรวม (ตามตัวกรอง) และแบบแยกรายบุคคล (ส่ง student_id + essay_phase)

require_once 'auth_helper.php';
require_once 'thai_text_utils.php';
require_login();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'expert'])) {
    header('Location: index.php');
    exit;
}
$isTeacher = ($_SESSION['user']['role'] === 'teacher'); // ครูแก้ไข/ลบเรียงความในหน้านี้ได้

$phaseLabels = [
    'pretest'  => 'ก่อนเรียน (Pretest)',
    'task1'    => 'ภาระงาน หน่วยที่ 1',
    'task2'    => 'ภาระงาน หน่วยที่ 2',
    'posttest' => 'หลังเรียน (Posttest)',
];

$fGroup     = isset($_GET['group'])       ? trim($_GET['group'])       : 'all';
$fClassroom = isset($_GET['classroom'])   ? trim($_GET['classroom'])   : 'all';
$fPhase     = isset($_GET['phase'])       ? trim($_GET['phase'])       : 'all';
$fQuery     = isset($_GET['q'])           ? trim($_GET['q'])           : '';
$oneStudent = isset($_GET['student_id'])  ? trim($_GET['student_id'])  : '';
$onePhase   = isset($_GET['essay_phase']) ? trim($_GET['essay_phase']) : '';
$fMode      = isset($_GET['mode'])        ? trim($_GET['mode'])        : 'forms';
$isSingle   = ($oneStudent !== '');

// ===== โหมดรายงานสรุปทั้งห้อง (ตารางสถานะการส่งเรียงความรายบุคคล) =====
// เอกสาร PDF อย่างเป็นทางการ: แต่ละแถว = นักเรียนหนึ่งคน, แต่ละช่องรอบ = ส่งแล้ว (✓) / ยังไม่ส่ง (ว่าง)
if (!$isSingle && $fMode === 'summary') {
    // คอลัมน์รายงาน: ภาระงานแต่ละหน่วยแตกเป็นร่าง D1/D2 (ให้คะแนนเฉพาะ D2)
    $sumCols = ['pretest', 'task1_d1', 'task1_d2', 'task2_d1', 'task2_d2', 'posttest'];

    $sconds  = [];
    $sparams = [];
    if ($fGroup === '__none__') {
        $sconds[] = "(s.student_group IS NULL OR s.student_group = '')";
    } elseif ($fGroup !== 'all' && $fGroup !== '') {
        $sconds[] = 's.student_group = ?';
        $sparams[] = $fGroup;
    }
    if ($fClassroom !== 'all' && $fClassroom !== '') {
        $sconds[] = 's.classroom = ?';
        $sparams[] = $fClassroom;
    }
    $ssql = 'SELECT s.student_id, s.student_name, s.classroom, s.student_group,
                    GROUP_CONCAT(DISTINCT se.essay_phase) AS phases
             FROM students s
             LEFT JOIN student_essays se ON se.student_id = s.student_id';
    if ($sconds) { $ssql .= ' WHERE ' . implode(' AND ', $sconds); }
    $ssql .= ' GROUP BY s.student_id, s.student_name, s.classroom, s.student_group
               ORDER BY s.classroom ASC, s.student_id ASC';

    $srows = [];
    try {
        $sstmt = $pdo->prepare($ssql);
        $sstmt->execute($sparams);
        $srows = $sstmt->fetchAll();
    } catch (Exception $e) {
        $srows = [];
    }

    // กรองด้วยคำค้น (ชื่อ/รหัส) ให้สอดคล้องกับหน้า Essay Viewer
    if ($fQuery !== '') {
        $needle = mb_strtolower($fQuery, 'UTF-8');
        $srows = array_values(array_filter($srows, function ($r) use ($needle) {
            $hay = mb_strtolower(($r['student_name'] ?? '') . ' ' . ($r['student_id'] ?? ''), 'UTF-8');
            return mb_strpos($hay, $needle) !== false;
        }));
    }

    foreach ($srows as &$r) { $r['student_name'] = formatNamePrefix($r['student_name']); }
    unset($r);

    $hh = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $hasPhase = function ($phasesStr, $key) {
        $set = array_filter(array_map('trim', explode(',', (string)$phasesStr)));
        return in_array($key, $set, true);
    };

    // นับยอดรวมที่ส่งในแต่ละคอลัมน์ (รวมร่าง D1/D2 แยกกัน)
    $totals = array_fill_keys($sumCols, 0);
    foreach ($srows as $r) {
        foreach ($sumCols as $k) { if ($hasPhase($r['phases'] ?? '', $k)) $totals[$k]++; }
    }

    $groupLabel = ($fGroup === '__none__') ? 'ยังไม่ระบุกลุ่ม'
                : (($fGroup === 'all' || $fGroup === '') ? 'ทุกกลุ่ม' : $fGroup);
    $roomLabel  = ($fClassroom === 'all' || $fClassroom === '') ? 'ทุกห้องเรียน' : ('ห้อง ' . $fClassroom);
    $genAt      = date('d/m/Y H:i');
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุปการส่งเรียงความ</title>
    <style>
      @page { size: A4; margin: 14mm 14mm 16mm 14mm; }
      * { box-sizing: border-box; }
      html, body { margin: 0; padding: 0; }
      body {
        font-family: "TH Sarabun PSK", "THSarabunPSK", "TH SarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
        color: #000; font-size: 18px;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
      }
      .toolbar {
        background: #0d3b66; color: #fff; padding: 10px 16px;
        display: flex; gap: 10px; align-items: center; justify-content: space-between;
        font-family: "Tahoma", sans-serif; font-size: 14px;
      }
      .toolbar button, .toolbar a {
        background: #fff; color: #0d3b66; border: 0; border-radius: 999px;
        padding: 6px 18px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; font-family: inherit;
      }
      .sheet { max-width: 900px; margin: 0 auto; padding: 20px 24px; }
      .doc-title { text-align: center; font-size: 26px; font-weight: 700; margin: 0 0 4px; }
      .doc-sub   { text-align: center; font-size: 18px; color: #333; margin: 0 0 12px; }
      .doc-meta  { display: flex; flex-wrap: wrap; gap: 4px 24px; font-size: 17px; margin-bottom: 12px; }
      table.report { width: 100%; border-collapse: collapse; }
      table.report th, table.report td { border: 1px solid #333; padding: 5px 8px; font-size: 17px; }
      table.report thead th { background: #e8eef5; text-align: center; font-weight: 700; }
      table.report td.idx  { text-align: center; width: 42px; color: #444; }
      table.report td.sid  { text-align: center; white-space: nowrap; }
      table.report td.mark { text-align: center; font-weight: 700; color: #0a7d33; }
      table.report tfoot td { background: #f4f6f9; font-weight: 700; text-align: center; }
      table.report tfoot td.foot-label { text-align: right; }
      table.report th.d2col { background: #fde7c9; }
      table.report td.d2col { background: #fff7e6; }
      .empty { text-align: center; padding: 60px 20px; color: #94a3b8; font-family: "Tahoma", sans-serif; }
      @media print {
        .toolbar { display: none !important; }
        .sheet { max-width: none; padding: 0; }
        table.report thead { display: table-header-group; }
        table.report tr { page-break-inside: avoid; }
      }
    </style>
    </head>
    <body>
      <div class="toolbar">
        <span>เอกสารพร้อมพิมพ์ — กล่องพิมพ์จะเปิดอัตโนมัติ หากไม่เปิด กดปุ่ม "พิมพ์ / บันทึก PDF" (แนะนำให้เครื่องมีฟอนต์ TH Sarabun PSK)</span>
        <div>
          <button onclick="window.print()">🖨️ พิมพ์ / บันทึก PDF</button>
          <a href="essay_viewer.php">ปิด</a>
        </div>
      </div>

      <div class="sheet">
        <h1 class="doc-title">รายงานสรุปการส่งเรียงความ</h1>
        <div class="doc-sub">ระบบประเมินเรียงความ</div>
        <div class="doc-meta">
          <span><strong>กลุ่ม:</strong> <?php echo $hh($groupLabel); ?></span>
          <span><strong>ห้องเรียน:</strong> <?php echo $hh($roomLabel); ?></span>
          <span><strong>จำนวนนักเรียน:</strong> <?php echo count($srows); ?> คน</span>
          <span><strong>พิมพ์เมื่อ:</strong> <?php echo $hh($genAt); ?></span>
        </div>

        <?php if (empty($srows)): ?>
          <div class="empty"><div style="font-size:40px">📭</div>ไม่พบนักเรียนที่ตรงกับเงื่อนไข</div>
        <?php else: ?>
        <?php
          // คอลัมน์ที่เป็นร่างให้คะแนน (D2) — ไฮไลต์ในตาราง
          $isD2 = function ($key) { return $key === 'task1_d2' || $key === 'task2_d2'; };
        ?>
        <table class="report">
          <thead>
            <tr>
              <th rowspan="2">ที่</th>
              <th rowspan="2">รหัสนักเรียน</th>
              <th rowspan="2" style="text-align:left;">ชื่อสกุล</th>
              <th rowspan="2">ก่อนเรียน</th>
              <th colspan="2">หน่วยที่ 1</th>
              <th colspan="2">หน่วยที่ 2</th>
              <th rowspan="2">หลังเรียน</th>
            </tr>
            <tr>
              <th>D1</th>
              <th class="d2col">D2 ★</th>
              <th>D1</th>
              <th class="d2col">D2 ★</th>
            </tr>
          </thead>
          <tbody>
            <?php $i = 0; foreach ($srows as $r): $i++;
              $room = trim((string)($r['classroom'] ?? '')); ?>
            <tr>
              <td class="idx"><?php echo $i; ?></td>
              <td class="sid"><?php echo $hh($r['student_id']); ?></td>
              <td><?php echo $hh($r['student_name']); ?><?php if ($room !== '' && ($fClassroom === 'all' || $fClassroom === '')): ?> <span style="color:#666;font-size:15px;">(ห้อง <?php echo $hh($room); ?>)</span><?php endif; ?></td>
              <?php foreach ($sumCols as $key): ?>
                <td class="mark<?php echo $isD2($key) ? ' d2col' : ''; ?>"><?php echo $hasPhase($r['phases'] ?? '', $key) ? '✓' : ''; ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td class="foot-label" colspan="3">รวมส่งแล้ว (คน)</td>
              <?php foreach ($sumCols as $key): ?>
                <td class="<?php echo $isD2($key) ? 'd2col' : ''; ?>"><?php echo $totals[$key]; ?></td>
              <?php endforeach; ?>
            </tr>
          </tfoot>
        </table>
        <div style="font-size:15px;color:#555;margin-top:8px;">★ D2 = ร่างที่ 2 (ร่างที่ใช้ให้คะแนน) · D1 = ร่างที่ 1 · เครื่องหมาย ✓ = ส่งแล้ว</div>
        <?php endif; ?>
      </div>

      <script>
        window.addEventListener('load', function () {
          setTimeout(function () { window.print(); }, 200);
        });
      </script>
    </body>
    </html>
    <?php
    exit;
}

$sql = 'SELECT se.*, s.student_name, s.classroom, s.student_group
        FROM student_essays se LEFT JOIN students s ON se.student_id = s.student_id';
$conds = [];
$params = [];
if ($isSingle) {
    $conds[] = 'se.student_id = ?';
    $params[] = $oneStudent;
    if ($onePhase !== '') { $conds[] = 'se.essay_phase = ?'; $params[] = $onePhase; }
} else {
    if ($fGroup === '__none__') {
        $conds[] = "(s.student_group IS NULL OR s.student_group = '')";
    } elseif ($fGroup !== 'all' && $fGroup !== '') {
        $conds[] = 's.student_group = ?';
        $params[] = $fGroup;
    }
    if ($fClassroom !== 'all' && $fClassroom !== '') { $conds[] = 's.classroom = ?'; $params[] = $fClassroom; }
    if ($fPhase !== 'all' && $fPhase !== '')         { $conds[] = 'se.essay_phase = ?'; $params[] = $fPhase; }
}
if ($conds) { $sql .= ' WHERE ' . implode(' AND ', $conds); }
$sql .= ' ORDER BY s.classroom ASC, se.essay_phase ASC, s.student_id ASC';

$rows = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}

function essayPlainText($contentStr) {
    if (!$contentStr) return '';
    $obj = json_decode($contentStr, true);
    if (is_array($obj) && isset($obj['introduction'])) {
        $body = isset($obj['body']) && is_array($obj['body']) ? implode(' ', $obj['body']) : '';
        return trim(($obj['introduction'] ?? '') . ' ' . $body . ' ' . ($obj['conclusion'] ?? ''));
    }
    return $contentStr;
}

// ประกอบเนื้อหาจากคอลัมน์แยกส่วน + หัวข้อที่ครูกำหนด (essay_title = หัวข้อของครู) ก่อนนำไปค้นหา/แสดงผล
$topicsMap = essay_topics_map($pdo);
foreach ($rows as &$r) {
    $r['student_name']  = formatNamePrefix($r['student_name']);
    $r['essay_content'] = essay_compose_content($r['intro_content'] ?? null, $r['body_content'] ?? null, $r['conclusion_content'] ?? null);
    $r['essay_title']   = $topicsMap[essay_topic_phase($r['essay_phase'])] ?? '';
}
unset($r);

if (!$isSingle && $fQuery !== '') {
    $needle = mb_strtolower($fQuery, 'UTF-8');
    $rows = array_values(array_filter($rows, function ($e) use ($needle) {
        $hay = mb_strtolower(
            ($e['student_name'] ?? '') . ' ' . ($e['student_id'] ?? '') . ' ' .
            ($e['essay_title'] ?? '') . ' ' . essayPlainText($e['essay_content'] ?? ''),
            'UTF-8'
        );
        return mb_strpos($hay, $needle) !== false;
    }));
}

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// แปลงเนื้อหา (JSON) เป็นย่อหน้าเรียงความล้วน ไม่มีกล่องสี — ให้เหมือนเรียงความจริง
function essayParagraphs($contentStr) {
    $paras = [];
    $obj = json_decode((string)$contentStr, true);
    if (is_array($obj) && isset($obj['introduction'])) {
        if (trim((string)($obj['introduction'] ?? '')) !== '') $paras[] = $obj['introduction'];
        if (isset($obj['body']) && is_array($obj['body'])) {
            foreach ($obj['body'] as $p) { if (trim((string)$p) !== '') $paras[] = $p; }
        }
        if (trim((string)($obj['conclusion'] ?? '')) !== '') $paras[] = $obj['conclusion'];
    } else {
        // ข้อความล้วน — แยกย่อหน้าด้วยการเว้นบรรทัด
        foreach (preg_split('/\n{2,}/u', (string)$contentStr) as $p) {
            if (trim($p) !== '') $paras[] = $p;
        }
    }
    if (empty($paras)) return '<div class="no-content">— ยังไม่มีเนื้อหาเรียงความ —</div>';
    $out = '';
    foreach ($paras as $p) {
        // แสดงขอบเขตการตัดคำให้เห็นด้วย (เส้นประใต้คำ) — ดู thai_text_utils.php
        $out .= '<p class="para">' . render_thai_segmented_html((string)$p) . '</p>';
    }
    return $out;
}

$genAt = date('d/m/Y H:i');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>แบบเขียนเรียงความ</title>
<style>
  /* ฟอนต์: PDF ใช้ TH Sarabun PSK (ฟอนต์ราชการที่ติดตั้งในเครื่องส่วนใหญ่) ถ้าไม่มีจึงถอยไปฟอนต์ไทยอื่นของระบบ */
  @page { size: A4; margin: 16mm 16mm 18mm 16mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body {
    font-family: "TH Sarabun PSK", "THSarabunPSK", "TH SarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
    color: #000; font-size: 18px;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .toolbar {
    background: #0d3b66; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center; justify-content: space-between;
    font-family: "Tahoma", sans-serif; font-size: 14px;
  }
  .toolbar button, .toolbar a {
    background: #fff; color: #0d3b66; border: 0; border-radius: 999px;
    padding: 6px 18px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none; font-family: inherit;
  }
  .sheet { max-width: 800px; margin: 0 auto; padding: 20px 24px; }
  .form { page-break-after: always; }
  .form:last-child { page-break-after: auto; }

  .form-title { text-align: center; font-size: 28px; font-weight: 700; margin: 0 0 12px; }
  .info { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; font-size: 19px; margin-bottom: 6px; }
  .info .lead { white-space: nowrap; }
  .topic { font-size: 19px; margin: 4px 0 2px; display: flex; align-items: baseline; gap: 6px; }
  .topic .lead { white-space: nowrap; font-weight: 700; }
  .meta { font-size: 15px; color: #444; margin: 2px 0 8px; }
  /* ช่องเติมข้อความแบบเส้นประ */
  .fill { flex: 1; min-width: 60px; border-bottom: 1px dotted #000; padding: 0 6px 2px; }
  .fill.name { flex: 3; }
  .fill.room { flex: 1; text-align: center; }
  .fill.sid  { flex: 1.4; text-align: center; }

  /* พื้นที่เนื้อความ — ไม่มีเส้นบรรทัด มีเลขบรรทัดทุก 5 บรรทัดที่ขอบซ้าย */
  .content {
    position: relative;
    margin-top: 8px;
    padding-left: 2.8em;   /* เว้นที่สำหรับเลขบรรทัด */
    font-size: 20px;
    line-height: 30px;     /* ต้องตรงกับ LH ในสคริปต์ด้านล่าง */
  }
  .content .para { margin: 0; text-indent: 2.5em; text-align: justify; }
  /* แสดงขอบเขตการตัดคำ (อ่านอย่างเดียว) — เส้นประบาง ๆ ใต้แต่ละคำ */
  .content .thai-word { border-bottom: 1px dotted #999; }
  .content .lnum {
    position: absolute; left: 0; width: 2.1em; text-align: right;
    color: #9aa1ac; font-size: 14px; font-family: "Tahoma", sans-serif;
  }
  .no-content { color: #888; font-style: italic; text-indent: 0; }
  .empty { text-align: center; padding: 60px 20px; color: #94a3b8; font-family: "Tahoma", sans-serif; }

  /* แถบเครื่องมือครู (แก้ไข/ลบ) — แสดงบนจอเท่านั้น ไม่พิมพ์ลง PDF */
  .editbar {
    display: flex; gap: 8px; justify-content: flex-end; margin: 4px 0 6px;
    font-family: "Tahoma", sans-serif;
  }
  /* ปุ่มตรวจสอบการสะกดคำ/แยกคำทั้งหน้า — แสดงบนจอเท่านั้น ไม่พิมพ์ลง PDF */
  .reviewbar {
    display: flex; justify-content: flex-end; margin: 4px 0 6px;
    font-family: "Tahoma", sans-serif;
  }
  .reviewbar button {
    border: 1px solid #0d3b66; background: #fff; color: #0d3b66; border-radius: 999px;
    padding: 4px 14px; font-size: 14px; font-weight: 700; cursor: pointer;
  }
  .reviewbar button:hover { filter: brightness(0.96); }
  .editbar button {
    border: 1px solid #cbd5e1; background: #fff; border-radius: 999px;
    padding: 4px 14px; font-size: 14px; font-weight: 700; cursor: pointer;
  }
  .editbar button.b-edit { color: #0d3b66; border-color: #0d3b66; }
  .editbar button.b-del  { color: #c0392b; border-color: #c0392b; }
  .editbar button:hover  { filter: brightness(0.96); }
  .editpanel {
    border: 1px dashed #94a3b8; border-radius: 10px; padding: 14px 16px; margin: 4px 0 10px;
    background: #f8fafc; font-family: "Tahoma", sans-serif;
  }
  .editpanel label { display: block; font-weight: 700; font-size: 14px; color: #0d3b66; margin: 8px 0 3px; }
  .editpanel textarea {
    width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px;
    font-family: inherit; font-size: 15px; resize: vertical;
  }
  .editpanel .body-para { display: flex; gap: 6px; align-items: flex-start; margin-bottom: 6px; }
  .editpanel .body-para textarea { flex: 1; }
  .editpanel .mini {
    border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 4px 10px;
    font-size: 13px; font-weight: 700; cursor: pointer; flex-shrink: 0;
  }
  .editpanel .mini.del { color: #c0392b; border-color: #e5a29a; }
  .editpanel .actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
  .editpanel .actions button {
    border: 0; border-radius: 999px; padding: 6px 18px; font-size: 14px; font-weight: 700; cursor: pointer;
  }
  .editpanel .actions .save   { background: #0d3b66; color: #fff; }
  .editpanel .actions .cancel { background: #e2e8f0; color: #334155; }

  @media print {
    .toolbar { display: none !important; }
    .editbar, .editpanel, .reviewbar { display: none !important; }
    .sheet { max-width: none; padding: 0; }
  }
</style>
</head>
<body>
  <div class="toolbar">
    <span>เอกสารพร้อมพิมพ์ — กล่องพิมพ์จะเปิดอัตโนมัติ หากไม่เปิด กดปุ่ม "พิมพ์ / บันทึก PDF" (แนะนำให้เครื่องมีฟอนต์ TH Sarabun PSK)</span>
    <div>
      <button onclick="window.print()">🖨️ พิมพ์ / บันทึก PDF</button>
      <a href="essay_viewer.php">ปิด</a>
    </div>
  </div>

  <div class="sheet">
    <?php if (empty($rows)): ?>
      <div class="empty"><div style="font-size:40px">📭</div>ไม่พบเรียงความที่ตรงกับเงื่อนไข</div>
    <?php else: ?>
      <?php
        // ป้ายรอบการประเมินแบบเต็ม (รวมร่าง D1/D2)
        $phaseTextFull = [
          'pretest'  => 'ก่อนเรียน (Pretest)',
          'task1_d1' => 'ภาระงาน หน่วยที่ 1 · ร่างที่ 1 (D1)',
          'task1_d2' => 'ภาระงาน หน่วยที่ 1 · ร่างที่ 2 (D2)',
          'posttest' => 'หลังเรียน (Posttest)',
        ];
        $editData = []; // ข้อมูลสำหรับให้ครูแก้ไข (ต่อเรียงความหนึ่งชิ้น)
        $fi = 0;
        foreach ($rows as $e):
          $room = trim((string)($e['classroom'] ?? ''));
          $grp  = trim((string)($e['student_group'] ?? ''));
          $phaseText = $phaseTextFull[$e['essay_phase']] ?? ($phaseLabels[$e['essay_phase']] ?? $e['essay_phase']);
          // แยกส่วนเนื้อหาเพื่อใช้เติมในฟอร์มแก้ไข
          $eObj  = json_decode((string)($e['essay_content'] ?? ''), true);
          $eIntro = is_array($eObj) ? (string)($eObj['introduction'] ?? '') : '';
          $eBody  = (is_array($eObj) && isset($eObj['body']) && is_array($eObj['body'])) ? array_values($eObj['body']) : [];
          $eConc  = is_array($eObj) ? (string)($eObj['conclusion'] ?? '') : '';
          $eAllText = trim($eIntro . "\n" . implode("\n", $eBody) . "\n" . $eConc);
          $eWordCount = count_thai_words($eAllText);
          $eSentenceCount = count_thai_sentences($eAllText);
          // เก็บเนื้อหาไว้ให้ JS ใช้ได้ทั้งครูและผู้เชี่ยวชาญ (สำหรับปุ่ม "ตรวจสอบทั้งหน้า")
          // ส่วนแผงแก้ไข/ลบด้านล่างยังจำกัดเฉพาะครูเหมือนเดิม
          $editData[$fi] = [
            'sid'   => (string)$e['student_id'],
            'phase' => (string)$e['essay_phase'],
            'phaseText' => $phaseText,
            'intro' => $eIntro,
            'body'  => $eBody,
            'conc'  => $eConc,
          ];
      ?>
      <div class="form">
        <?php if ($isTeacher): ?>
        <div class="editbar">
          <button type="button" class="b-edit" onclick="toggleEdit(<?php echo $fi; ?>)"><span id="editLbl_<?php echo $fi; ?>">✏️ แก้ไข</span></button>
          <button type="button" class="b-del" onclick="deleteEssay(<?php echo $fi; ?>)">🗑️ ลบ</button>
        </div>
        <?php endif; ?>
        <div class="reviewbar">
          <button type="button" onclick="openPrintSpellingReview(<?php echo $fi; ?>)">🔍 ตรวจสอบทั้งหน้า / แยกคำ</button>
        </div>
        <h1 class="form-title">แบบเขียนเรียงความ</h1>
        <div class="info">
          <span class="lead">ชื่อ-สกุล</span><span class="fill name"><?php echo $h($e['student_name']); ?></span>
          <span class="lead">ห้อง</span><span class="fill room"><?php echo $h($room); ?></span>
          <span class="lead">รหัสนักเรียน</span><span class="fill sid"><?php echo $h($e['student_id']); ?></span>
        </div>
        <div class="topic">
          <span class="lead">หัวข้อ :</span><span class="fill"><?php echo $h($e['essay_title']); ?></span>
        </div>
        <div class="meta">รอบการประเมิน: <?php echo $h($phaseText); ?><?php if ($grp !== ''): ?> · กลุ่ม: <?php echo $h($grp); ?><?php endif; ?> · <?php echo $eWordCount; ?> คำ · <?php echo $eSentenceCount; ?> ประโยค (โดยประมาณ)</div>
        <div class="content" id="content_<?php echo $fi; ?>"><?php echo essayParagraphs($e['essay_content'] ?? ''); ?></div>
        <?php if ($isTeacher): ?>
        <div class="editpanel" id="panel_<?php echo $fi; ?>" style="display:none;">
          <label>ส่วนคำนำ (Introduction)</label>
          <textarea id="intro_<?php echo $fi; ?>" rows="3"></textarea>
          <label>ส่วนเนื้อเรื่อง (Body) — แยกเป็นย่อหน้า</label>
          <div id="bodyList_<?php echo $fi; ?>"></div>
          <button type="button" class="mini" onclick="addPara(<?php echo $fi; ?>, '')">+ เพิ่มย่อหน้า</button>
          <label>ส่วนสรุป (Conclusion)</label>
          <textarea id="conc_<?php echo $fi; ?>" rows="3"></textarea>
          <div class="actions">
            <button type="button" class="cancel" onclick="toggleEdit(<?php echo $fi; ?>)">ยกเลิก</button>
            <button type="button" class="save" onclick="saveEssay(<?php echo $fi; ?>)">💾 บันทึก</button>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php $fi++; endforeach; ?>
    <?php endif; ?>
  </div>

  <script src="thai_review.js"></script>
  <script>
    // ข้อมูลเนื้อหาเรียงความต่อชิ้น (ใช้โดยปุ่ม "ตรวจสอบทั้งหน้า" — ทุกคนที่เห็นหน้านี้ใช้ได้
    // ส่วนการบันทึกแก้ไขจริงยังจำกัดเฉพาะครูอยู่ที่ api.php?action=admin_save_essay)
    var ESSAY_EDIT = <?php echo json_encode($editData ?? [], JSON_UNESCAPED_UNICODE); ?>;

    // สร้างเลขบรรทัดทุก 5 บรรทัด (5, 10, 15, ...) ที่ขอบซ้ายของเนื้อความ
    // คำนวณจำนวนบรรทัดจริงหลังจัดหน้าเสร็จ แล้ววางตัวเลขตามระยะบรรทัด (LH)
    function addLineNumbers() {
      var LH = 30; // ต้องตรงกับ line-height ของ .content ใน CSS
      var boxes = document.querySelectorAll('.content');
      for (var b = 0; b < boxes.length; b++) {
        var box = boxes[b];
        var lines = Math.round(box.clientHeight / LH);
        for (var i = 5; i <= lines; i += 5) {
          var s = document.createElement('span');
          s.className = 'lnum';
          s.textContent = i;
          s.style.top = ((i - 1) * LH) + 'px';
          box.appendChild(s);
        }
      }
    }
    // เอกสารนี้ไม่มีทรัพยากรภายนอก จึงพร้อมพิมพ์ได้ทันที
    // ครูที่เปิดเรียงความชิ้นเดียวเพื่อจัดการ (แก้ไข/ลบ) จะไม่เปิดกล่องพิมพ์อัตโนมัติ
    var AUTO_PRINT = <?php echo (!($isTeacher && $isSingle)) ? 'true' : 'false'; ?>;
    window.addEventListener('load', function () {
      addLineNumbers();
      if (AUTO_PRINT) setTimeout(function () { window.print(); }, 200);
    });

    // สร้างรายการย่อหน้าทั้งฉบับของเรียงความชิ้นที่ i (สำหรับหน้าต่างตรวจสอบการสะกดคำ)
    function buildPrintReviewParagraphs(i) {
      var d = ESSAY_EDIT[i] || {};
      var paras = [];
      if ((d.intro || '').trim()) paras.push({ label: 'intro', text: d.intro });
      (d.body || []).forEach(function (p, idx) {
        if ((p || '').trim()) paras.push({ label: 'body:' + idx, text: p });
      });
      if ((d.conc || '').trim()) paras.push({ label: 'concl', text: d.conc });
      return paras;
    }

    // เปิดหน้าต่างตรวจสอบการสะกดคำ/แยกคำทั้งหน้า สำหรับเรียงความชิ้นที่ i
    function openPrintSpellingReview(i) {
      var paragraphs = buildPrintReviewParagraphs(i);
      if (paragraphs.length === 0) {
        alert('ไม่มีเนื้อหาเรียงความให้ตรวจสอบ');
        return;
      }
      var combined = paragraphs.map(function (p) { return p.text; }).join('\n');

      fetch('api.php?action=check_thai_spelling', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text: combined })
      }).then(function (r) { return r.json(); }).catch(function () { return null; }).then(function (data) {
        var misspelled = (data && data.success) ? data.misspelled : [];
        ThaiReview.open({
          paragraphs: paragraphs,
          misspelled: misspelled,
          onSave: function (editedParagraphs) {
            var d = ESSAY_EDIT[i];
            if (!d) return Promise.reject(new Error('ไม่พบข้อมูลเรียงความ'));
            var byLabel = {};
            editedParagraphs.forEach(function (p) { byLabel[p.label] = p.text; });
            var intro = (byLabel['intro'] !== undefined) ? byLabel['intro'].trim() : '';
            var conc  = (byLabel['concl'] !== undefined) ? byLabel['concl'].trim() : '';
            var body  = (d.body || []).map(function (_, idx) {
              return (byLabel['body:' + idx] !== undefined) ? byLabel['body:' + idx].trim() : '';
            }).filter(function (t) { return t !== ''; });

            return fetch('api.php?action=admin_save_essay', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ student_id: d.sid, essay_phase: d.phase, introduction: intro, body: body, conclusion: conc })
            }).then(function (r) { return r.json(); }).then(function (res) {
              if (!res.success) throw new Error(res.error || 'บันทึกไม่สำเร็จ');
              // บันทึกสำเร็จ — โหลดหน้าใหม่เพื่อแสดงเนื้อหาที่แก้ไขแล้ว (เหมือนแผงแก้ไขเดิม)
              location.reload();
            });
          }
        });
      });
    }
  </script>

  <?php if ($isTeacher): ?>
  <script>
    // ===== ครูแก้ไข/ลบเรียงความในหน้าเอกสาร =====

    // เปิด/ปิดแผงแก้ไข (เติมเนื้อหาเดิมครั้งแรกที่เปิด)
    function toggleEdit(i) {
      var panel = document.getElementById('panel_' + i);
      var lbl   = document.getElementById('editLbl_' + i);
      if (!panel) return;
      var opening = (panel.style.display === 'none' || panel.style.display === '');
      if (opening) {
        var d = ESSAY_EDIT[i] || {};
        document.getElementById('intro_' + i).value = d.intro || '';
        document.getElementById('conc_' + i).value  = d.conc  || '';
        var list = document.getElementById('bodyList_' + i);
        list.innerHTML = '';
        var body = Array.isArray(d.body) ? d.body : [];
        if (body.length) body.forEach(function (p) { addPara(i, p); });
        else addPara(i, '');
        panel.style.display = 'block';
        if (lbl) lbl.textContent = '✕ ปิดการแก้ไข';
      } else {
        panel.style.display = 'none';
        if (lbl) lbl.textContent = '✏️ แก้ไข';
      }
    }

    // เพิ่มช่องย่อหน้าเนื้อเรื่องหนึ่งช่อง
    function addPara(i, text) {
      var list = document.getElementById('bodyList_' + i);
      if (!list) return;
      var row = document.createElement('div');
      row.className = 'body-para';
      var ta = document.createElement('textarea');
      ta.rows = 2; ta.value = text || ''; ta.placeholder = 'ย่อหน้าเนื้อเรื่อง...';
      var del = document.createElement('button');
      del.type = 'button'; del.className = 'mini del'; del.textContent = 'ลบ';
      del.onclick = function () { row.remove(); };
      row.appendChild(ta); row.appendChild(del);
      list.appendChild(row);
    }

    // บันทึกการแก้ไข → เขียนทับเรียงความเดิม แล้วรีเฟรชหน้า
    function saveEssay(i) {
      var d = ESSAY_EDIT[i]; if (!d) return;
      var intro = (document.getElementById('intro_' + i).value || '').trim();
      var conc  = (document.getElementById('conc_' + i).value  || '').trim();
      var body  = [].slice.call(document.querySelectorAll('#bodyList_' + i + ' textarea'))
                    .map(function (t) { return t.value.trim(); }).filter(function (t) { return t !== ''; });
      if (!intro && !conc && body.length === 0) { alert('กรุณากรอกเนื้อหาอย่างน้อยหนึ่งส่วน'); return; }
      fetch('api.php?action=admin_save_essay', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: d.sid, essay_phase: d.phase, introduction: intro, body: body, conclusion: conc })
      }).then(function (r) { return r.json(); }).then(function (res) {
        if (res.success) { alert('บันทึกเรียงความเรียบร้อยแล้ว'); location.reload(); }
        else { alert('บันทึกไม่สำเร็จ: ' + (res.error || '')); }
      }).catch(function () { alert('บันทึกไม่สำเร็จ'); });
    }

    // ลบเรียงความชิ้นนี้ → กลับไปหน้ารายการ
    function deleteEssay(i) {
      var d = ESSAY_EDIT[i]; if (!d) return;
      if (!confirm('ต้องการลบเรียงความรอบ "' + (d.phaseText || d.phase) + '" ของนักเรียนรหัส ' + d.sid + ' ใช่หรือไม่?\nการลบไม่สามารถย้อนกลับได้')) return;
      fetch('api.php?action=admin_delete_essay', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ student_id: d.sid, essay_phase: d.phase })
      }).then(function (r) { return r.json(); }).then(function (res) {
        if (res.success) { alert('ลบเรียงความเรียบร้อยแล้ว'); window.location.href = 'essay_viewer.php'; }
        else { alert('ลบไม่สำเร็จ: ' + (res.error || '')); }
      }).catch(function () { alert('ลบไม่สำเร็จ'); });
    }
  </script>
  <?php endif; ?>
</body>
</html>
