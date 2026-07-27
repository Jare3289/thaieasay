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
$isSingle   = ($oneStudent !== '');

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

  .form-title { text-align: center; font-size: 26px; font-weight: 700; margin: 0 0 10px; }
  .info { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; font-size: 20px; margin-bottom: 6px; }
  .info .lead { white-space: nowrap; }
  .topic { font-size: 20px; margin: 4px 0 2px; display: flex; align-items: baseline; gap: 6px; }
  .topic .lead { white-space: nowrap; font-weight: 700; }
  .meta { font-size: 15px; color: #444; margin: 2px 0 10px; }
  /* ช่องเติมข้อความแบบเส้นประ */
  .fill { flex: 1; min-width: 60px; border-bottom: 1px dotted #000; padding: 0 6px 2px; }
  .fill.name { flex: 3; }
  .fill.room { flex: 1; text-align: center; }
  .fill.no   { flex: 1; text-align: center; }

  /* พื้นที่เนื้อความบนเส้นบรรทัด (เหมือนกระดาษมีเส้น) */
  .content {
    margin-top: 10px;
    font-size: 22px;
    line-height: 40px;
    background-image: repeating-linear-gradient(to bottom, transparent 0, transparent 39px, #b9c0cc 39px, #b9c0cc 40px);
    background-position: 0 2px;
  }
  .content .para { margin: 0; text-indent: 2.5em; text-align: justify; }
  .no-content { color: #888; font-style: italic; text-indent: 0; }
  .stamp { text-align: right; color: #888; font-size: 13px; margin-top: 8px; font-family: "Tahoma", sans-serif; }
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
          <span class="lead">ชื่อ</span><span class="fill name"><?php echo $h($e['student_name']); ?></span>
          <span class="lead">ชั้น</span><span class="fill room"><?php echo $h($room); ?></span>
          <span class="lead">เลขที่</span><span class="fill no"></span>
        </div>
        <div class="topic">
          <span class="lead">หัวข้อ :</span><span class="fill"><?php echo $h($e['essay_title']); ?></span>
        </div>
        <div class="meta">รอบการประเมิน: <?php echo $h($phaseText); ?><?php if ($grp !== ''): ?> · กลุ่ม: <?php echo $h($grp); ?><?php endif; ?><?php if ($room !== ''): ?> · ห้อง <?php echo $h($room); ?><?php endif; ?></div>
        <div class="content"><?php echo essayParagraphs($e['essay_content'] ?? '', $h); ?></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script>
    // เอกสารนี้ไม่มีทรัพยากรภายนอก จึงพร้อมพิมพ์ได้ทันที
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 200); });
  </script>
</body>
</html>
