<?php
// essay_print.php
// เอกสาร "แบบเขียนเรียงความ" สำหรับพิมพ์/บันทึกเป็น PDF
// เป็นเอกสารฝั่งเซิร์ฟเวอร์ ดึงข้อมูลจากฐานข้อมูลโดยตรง ไม่โหลดทรัพยากรภายนอกใด ๆ
// รองรับทั้งแบบรวม (ตามตัวกรอง) และแบบแยกรายบุคคล (ส่ง student_id + essay_phase)

require_once 'auth_helper.php';
require_login();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['teacher', 'expert'])) {
    header('Location: index.php');
    exit;
}

$phaseLabels = [
    'pretest'  => 'ก่อนเรียน (Pretest)',
    'task1'    => 'ภารงาน หน่วยที่ 1',
    'task2'    => 'ภารงาน หน่วยที่ 2',
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
    $phaseCols = [
        'pretest'  => 'ก่อนเรียน',
        'task1'    => 'หน่วยที่ 1',
        'task2'    => 'หน่วยที่ 2',
        'posttest' => 'หลังเรียน',
    ];

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

    // นับยอดรวมที่ส่งในแต่ละรอบ
    $totals = ['pretest' => 0, 'task1' => 0, 'task2' => 0, 'posttest' => 0];
    foreach ($srows as $r) {
        foreach ($totals as $k => $_) { if ($hasPhase($r['phases'] ?? '', $k)) $totals[$k]++; }
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
        <div class="doc-sub">ระบบประเมินเรียงความอัจฉริยะ</div>
        <div class="doc-meta">
          <span><strong>กลุ่ม:</strong> <?php echo $hh($groupLabel); ?></span>
          <span><strong>ห้องเรียน:</strong> <?php echo $hh($roomLabel); ?></span>
          <span><strong>จำนวนนักเรียน:</strong> <?php echo count($srows); ?> คน</span>
          <span><strong>พิมพ์เมื่อ:</strong> <?php echo $hh($genAt); ?></span>
        </div>

        <?php if (empty($srows)): ?>
          <div class="empty"><div style="font-size:40px">📭</div>ไม่พบนักเรียนที่ตรงกับเงื่อนไข</div>
        <?php else: ?>
        <table class="report">
          <thead>
            <tr>
              <th>ที่</th>
              <th>รหัสนักเรียน</th>
              <th style="text-align:left;">ชื่อสกุล</th>
              <?php foreach ($phaseCols as $lbl): ?><th><?php echo $hh($lbl); ?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php $i = 0; foreach ($srows as $r): $i++;
              $room = trim((string)($r['classroom'] ?? '')); ?>
            <tr>
              <td class="idx"><?php echo $i; ?></td>
              <td class="sid"><?php echo $hh($r['student_id']); ?></td>
              <td><?php echo $hh($r['student_name']); ?><?php if ($room !== '' && ($fClassroom === 'all' || $fClassroom === '')): ?> <span style="color:#666;font-size:15px;">(ห้อง <?php echo $hh($room); ?>)</span><?php endif; ?></td>
              <?php foreach ($phaseCols as $key => $lbl): ?>
                <td class="mark"><?php echo $hasPhase($r['phases'] ?? '', $key) ? '✓' : ''; ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td class="foot-label" colspan="3">รวมส่งแล้ว (คน)</td>
              <?php foreach ($phaseCols as $key => $lbl): ?>
                <td><?php echo $totals[$key]; ?></td>
              <?php endforeach; ?>
            </tr>
          </tfoot>
        </table>
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

foreach ($rows as &$r) { $r['student_name'] = formatNamePrefix($r['student_name']); }
unset($r);

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// แปลงเนื้อหา (JSON) เป็นย่อหน้าเรียงความล้วน ไม่มีกล่องสี — ให้เหมือนเรียงความจริง
function essayParagraphs($contentStr, $h) {
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
        // คงการขึ้นบรรทัดภายในย่อหน้าไว้ด้วย
        $out .= '<p class="para">' . nl2br($h($p)) . '</p>';
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
  .content .lnum {
    position: absolute; left: 0; width: 2.1em; text-align: right;
    color: #9aa1ac; font-size: 14px; font-family: "Tahoma", sans-serif;
  }
  .no-content { color: #888; font-style: italic; text-indent: 0; }
  .empty { text-align: center; padding: 60px 20px; color: #94a3b8; font-family: "Tahoma", sans-serif; }

  @media print {
    .toolbar { display: none !important; }
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
      <?php foreach ($rows as $e):
        $room = trim((string)($e['classroom'] ?? ''));
        $grp  = trim((string)($e['student_group'] ?? ''));
        $phaseText = $phaseLabels[$e['essay_phase']] ?? $e['essay_phase'];
      ?>
      <div class="form">
        <h1 class="form-title">แบบเขียนเรียงความ</h1>
        <div class="info">
          <span class="lead">ชื่อ-สกุล</span><span class="fill name"><?php echo $h($e['student_name']); ?></span>
          <span class="lead">ห้อง</span><span class="fill room"><?php echo $h($room); ?></span>
          <span class="lead">รหัสนักเรียน</span><span class="fill sid"><?php echo $h($e['student_id']); ?></span>
        </div>
        <div class="topic">
          <span class="lead">หัวข้อ :</span><span class="fill"><?php echo $h($e['essay_title']); ?></span>
        </div>
        <div class="meta">รอบการประเมิน: <?php echo $h($phaseText); ?><?php if ($grp !== ''): ?> · กลุ่ม: <?php echo $h($grp); ?><?php endif; ?></div>
        <div class="content"><?php echo essayParagraphs($e['essay_content'] ?? '', $h); ?></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script>
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
    window.addEventListener('load', function () {
      addLineNumbers();
      setTimeout(function () { window.print(); }, 200);
    });
  </script>
</body>
</html>
