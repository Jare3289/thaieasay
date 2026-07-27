<?php
// essay_print.php
// หน้าเอกสารสำหรับพิมพ์/บันทึกเป็น PDF ของเรียงความนักเรียน
// เป็นเอกสารมาตรฐานฝั่งเซิร์ฟเวอร์ ดึงข้อมูลจากฐานข้อมูลโดยตรง
// ไม่โหลดทรัพยากรภายนอกใด ๆ (ไม่มี Bootstrap/Google Fonts) เพื่อให้กล่องพิมพ์เด้งได้เสมอ
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

// อ่านค่าตัวกรองจาก query string
$fGroup     = isset($_GET['group'])       ? trim($_GET['group'])       : 'all';
$fClassroom = isset($_GET['classroom'])   ? trim($_GET['classroom'])   : 'all';
$fPhase     = isset($_GET['phase'])       ? trim($_GET['phase'])       : 'all';
$fQuery     = isset($_GET['q'])           ? trim($_GET['q'])           : '';
$oneStudent = isset($_GET['student_id'])  ? trim($_GET['student_id'])  : '';
$onePhase   = isset($_GET['essay_phase']) ? trim($_GET['essay_phase']) : '';
$isSingle   = ($oneStudent !== '');

// สร้างคำสั่ง SQL ตามตัวกรอง
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

// ดึงข้อความล้วนจากเนื้อหา (ซึ่งเก็บเป็น JSON) เพื่อใช้ค้นหา
function essayPlainText($contentStr) {
    if (!$contentStr) return '';
    $obj = json_decode($contentStr, true);
    if (is_array($obj) && isset($obj['introduction'])) {
        $body = isset($obj['body']) && is_array($obj['body']) ? implode(' ', $obj['body']) : '';
        return trim(($obj['introduction'] ?? '') . ' ' . $body . ' ' . ($obj['conclusion'] ?? ''));
    }
    return $contentStr;
}

// กรองด้วยคำค้น (ทำใน PHP เพราะเนื้อหาเป็น JSON)
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

// ตัดคำนำหน้าชื่อออก
foreach ($rows as &$r) { $r['student_name'] = formatNamePrefix($r['student_name']); }
unset($r);

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// สร้าง HTML เนื้อหาเรียงความพร้อมป้ายกำกับส่วนต่าง ๆ
function renderEssaySections($contentStr, $h) {
    $obj = json_decode((string)$contentStr, true);
    $out = '';
    if (is_array($obj) && isset($obj['introduction'])) {
        if (!empty($obj['introduction'])) {
            $out .= '<div class="sec intro"><span class="lbl">✍️ ส่วนคำนำ (Introduction)</span><div class="txt">' . $h($obj['introduction']) . '</div></div>';
        }
        if (isset($obj['body']) && is_array($obj['body'])) {
            $i = 1;
            foreach ($obj['body'] as $p) {
                if (trim((string)$p) !== '') {
                    $out .= '<div class="sec body"><span class="lbl">📖 ส่วนเนื้อเรื่อง ย่อหน้าที่ ' . $i . ' (Body)</span><div class="txt">' . $h($p) . '</div></div>';
                }
                $i++;
            }
        }
        if (!empty($obj['conclusion'])) {
            $out .= '<div class="sec concl"><span class="lbl">🏁 ส่วนสรุป (Conclusion)</span><div class="txt">' . $h($obj['conclusion']) . '</div></div>';
        }
        if ($out !== '') return $out;
    }
    $plain = trim((string)$contentStr);
    if ($plain !== '') {
        return '<div class="sec body"><span class="lbl">📝 เนื้อหาเรียงความ</span><div class="txt">' . $h($plain) . '</div></div>';
    }
    return '<div class="no-content">ไม่มีเนื้อหาเรียงความ</div>';
}

// ป้ายบริบทบนหน้าปก
$groupLabel = $fGroup === 'all' ? 'ทุกกลุ่ม' : ($fGroup === '__none__' ? 'ยังไม่ระบุกลุ่ม' : $fGroup);
$roomLabel  = $fClassroom === 'all' ? 'ทุกห้องเรียน' : ('ห้อง ' . $fClassroom);
$phaseLbl   = $fPhase === 'all' ? 'ทุกรอบการประเมิน' : ($phaseLabels[$fPhase] ?? $fPhase);
$genAt      = date('d/m/Y H:i');
$docTitle   = $isSingle ? 'เรียงความนักเรียน' : 'รวมเรียงความนักเรียน';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $h($docTitle); ?></title>
<style>
  /* ฟอนต์ไทยจากระบบเท่านั้น — ไม่โหลดจากอินเทอร์เน็ต จึงพิมพ์ได้แม้ออฟไลน์ */
  @page { size: A4; margin: 18mm 15mm; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body {
    font-family: "TH Sarabun New", "Sarabun", "Leelawadee UI", "Leelawadee", "Thonburi", "Tahoma", sans-serif;
    color: #1f2937; line-height: 1.75; font-size: 15px;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .toolbar {
    position: sticky; top: 0; background: #0d3b66; color: #fff; padding: 10px 16px;
    display: flex; gap: 10px; align-items: center; justify-content: space-between;
  }
  .toolbar button, .toolbar a {
    background: #fff; color: #0d3b66; border: 0; border-radius: 999px;
    padding: 6px 18px; font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none;
    font-family: inherit;
  }
  .toolbar .hint { font-size: 13px; opacity: .9; font-weight: 400; }
  .page { max-width: 800px; margin: 0 auto; padding: 16px; }
  .cover { text-align: center; padding: 48px 20px 24px; border-bottom: 4px double #0d3b66; margin-bottom: 24px; page-break-after: always; }
  .cover .emblem { font-size: 48px; }
  .cover h1 { font-size: 26px; margin: 10px 0 6px; color: #0d3b66; }
  .cover .subtitle { color: #475569; font-size: 15px; margin-bottom: 16px; }
  .cover .chips span { display: inline-block; margin: 4px; padding: 5px 14px; border-radius: 999px; background: #eef2ff; color: #0d3b66; font-weight: 700; font-size: 13px; }
  .cover .gen { margin-top: 18px; color: #64748b; font-size: 13px; }
  .essay { margin-bottom: 18px; }
  .essay + .essay { page-break-before: always; }
  .essay-head { background: #0d3b66; color: #fff; padding: 13px 18px; border-radius: 10px; margin-bottom: 14px; }
  .essay-head .name { font-size: 18px; font-weight: 700; }
  .essay-head .name .sid { font-weight: 400; opacity: .85; font-size: 14px; }
  .essay-head .title { font-size: 14px; opacity: .95; margin-top: 2px; font-style: italic; }
  .essay-head .tags { margin-top: 9px; }
  .tag { display: inline-block; padding: 2px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; margin: 3px 6px 0 0; background: rgba(255,255,255,.18); }
  .sec { margin-bottom: 13px; padding: 11px 15px; border-radius: 10px; border-left: 6px solid #cbd5e1; background: #f8fafc; page-break-inside: avoid; }
  .sec .lbl { display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; }
  .sec .txt { white-space: pre-wrap; text-align: justify; }
  .sec.intro { border-left-color: #2563eb; background: #eff6ff; } .sec.intro .lbl { color: #2563eb; }
  .sec.body  { border-left-color: #059669; background: #ecfdf5; } .sec.body .lbl { color: #059669; }
  .sec.concl { border-left-color: #dc2626; background: #fef2f2; } .sec.concl .lbl { color: #dc2626; }
  .no-content { color: #94a3b8; font-style: italic; }
  .stamp { text-align: right; color: #94a3b8; font-size: 11px; margin-top: 6px; }
  .empty { text-align: center; padding: 60px 20px; color: #94a3b8; }
  @media print {
    .toolbar { display: none !important; }
    .page { max-width: none; padding: 0; }
  }
</style>
</head>
<body>
  <div class="toolbar">
    <span class="hint">เอกสารพร้อมพิมพ์ — กล่องพิมพ์จะเปิดอัตโนมัติ หากไม่เปิด กดปุ่ม "พิมพ์ / บันทึก PDF"</span>
    <div>
      <button onclick="window.print()">🖨️ พิมพ์ / บันทึก PDF</button>
      <a href="essay_viewer.php">ปิด</a>
    </div>
  </div>

  <div class="page">
    <?php if (empty($rows)): ?>
      <div class="empty"><div style="font-size:40px">📭</div>ไม่พบเรียงความที่ตรงกับเงื่อนไข</div>
    <?php else: ?>
      <?php if (!$isSingle): ?>
      <div class="cover">
        <div class="emblem">📝</div>
        <h1>รวมเรียงความนักเรียน</h1>
        <div class="subtitle">ระบบประเมินความสามารถในการเขียนเรียงความ</div>
        <div class="chips">
          <span>กลุ่ม: <?php echo $h($groupLabel); ?></span>
          <span><?php echo $h($roomLabel); ?></span>
          <span><?php echo $h($phaseLbl); ?></span>
          <span>รวม <?php echo count($rows); ?> ชิ้น</span>
        </div>
        <div class="gen">จัดทำเอกสารเมื่อ <?php echo $h($genAt); ?></div>
      </div>
      <?php endif; ?>

      <?php foreach ($rows as $i => $e):
        $room = trim((string)($e['classroom'] ?? ''));
        $grp  = trim((string)($e['student_group'] ?? ''));
        $phaseText = $phaseLabels[$e['essay_phase']] ?? $e['essay_phase'];
        $wordCount = (int)($e['word_count'] ?? 0);
        $dt = !empty($e['updated_at']) ? $e['updated_at'] : ($e['created_at'] ?? '');
      ?>
      <article class="essay">
        <div class="essay-head">
          <div class="name"><?php echo (!$isSingle ? ($i + 1) . '. ' : ''); ?><?php echo $h($e['student_name']); ?>
            <span class="sid">(<?php echo $h($e['student_id']); ?>)</span></div>
          <?php if (!empty($e['essay_title'])): ?><div class="title">เรื่อง: <?php echo $h($e['essay_title']); ?></div><?php endif; ?>
          <div class="tags">
            <span class="tag"><?php echo $h($phaseText); ?></span>
            <?php if ($grp !== ''): ?><span class="tag"><?php echo $h($grp); ?></span><?php endif; ?>
            <?php if ($room !== ''): ?><span class="tag">ห้อง <?php echo $h($room); ?></span><?php endif; ?>
            <span class="tag"><?php echo number_format($wordCount); ?> คำ</span>
          </div>
        </div>
        <?php echo renderEssaySections($e['essay_content'] ?? '', $h); ?>
        <?php if ($dt): ?><div class="stamp">บันทึกล่าสุด: <?php echo $h($dt); ?></div><?php endif; ?>
      </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <script>
    // เปิดกล่องพิมพ์อัตโนมัติ — เอกสารนี้ไม่มีทรัพยากรภายนอก จึงพร้อมพิมพ์ได้ทันที
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 200); });
  </script>
</body>
</html>
