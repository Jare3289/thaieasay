<?php
/**
 * student_report_print.php
 * เอกสาร "รายงานผลการเรียนรู้รายบุคคล" สำหรับพิมพ์ / บันทึกเป็น PDF
 * เก็บเป็นรายงานประจำตัวนักเรียน 1 คน ต่อ 1 ฉบับ (ครูสั่งพิมพ์ทั้งห้องรวดเดียวก็ได้ — ขึ้นหน้าใหม่ให้อัตโนมัติ)
 *
 * เนื้อหาเป็นแฟ้มสะสมงานฉบับเต็ม — ทุกอย่างที่นักเรียนทำไว้ในระบบ
 *   1) ผลสัมฤทธิ์ทุกรอบ เทียบครู/ตนเอง/เพื่อน/ผู้เชี่ยวชาญ และค่าเฉลี่ยของชั้น
 *   2) บทวิเคราะห์รายบุคคล (คำนวณจากข้อมูลจริง ดู report_analysis.php)
 *   3) สถิติรายเกณฑ์ 11 ข้อ ก่อน→หลังเรียน จุดแข็ง จุดที่ควรพัฒนา
 *   4) คะแนนรายเกณฑ์จากผู้ประเมินทุกฝ่าย พร้อมความคิดเห็นเชิงบรรยาย
 *   5) ผลงานทุกรอบ + ใบตรวจสอบความครบถ้วน 12 ชิ้น
 *   6) เรียงความฉบับเต็มทุกรอบ
 *   7) ผลตรวจของ AI ทุกรอบ พร้อมพัฒนาการระหว่างรอบตรวจ
 *   8) บันทึกสะท้อนคิด (ปัญหาการเขียน / ตรวจสอบตนเอง / สะท้อนการเรียนรู้) ทุกหน่วย
 *   9) การประเมินร่วมกับเพื่อน ทั้งที่ได้รับและที่ไปประเมินให้เพื่อน
 *
 * พารามิเตอร์ (GET)
 *   student_id  ระบุ = พิมพ์คนเดียว, ไม่ระบุ = พิมพ์ทุกคนตามตัวกรอง (ครู/ผู้เชี่ยวชาญเท่านั้น)
 *   group       กลุ่มการวิจัย ('__none__' = ยังไม่ระบุกลุ่ม)
 *   classroom   ห้องเรียน
 *   brief=1     ฉบับย่อ (ผลสัมฤทธิ์ + บทวิเคราะห์ + สถิติรายเกณฑ์ + ผลงาน) เหมาะกับพิมพ์ทั้งห้อง
 */

require_once 'auth_helper.php';
require_login();
require_once 'report_data.php';
require_once 'report_analysis.php';
require_once 'report_sections.php';

$me   = $_SESSION['user'];
$role = $me['role'];

$fGroup     = isset($_GET['group'])      ? trim($_GET['group'])      : '';
$fClassroom = isset($_GET['classroom'])  ? trim($_GET['classroom'])  : '';
$fStudent   = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';
$brief      = !empty($_GET['brief']);

// นักเรียนดูได้เฉพาะรายงานของตนเองเท่านั้น
if ($role === 'student') {
    $fStudent   = $me['id'];
    $fGroup     = '';
    $fClassroom = '';
}

$data = report_dataset($pdo, [
    'group'     => $fGroup,
    'classroom' => $fClassroom,
]);

// เลือกว่าจะพิมพ์ของใครบ้าง
if ($fStudent !== '') {
    $targets = isset($data['students'][$fStudent]) ? [$fStudent] : [];
} else {
    $targets = array_keys($data['students']);
}

// สลับระหว่างฉบับเต็มกับฉบับย่อโดยคงตัวกรองเดิมไว้
$switchQs = http_build_query(array_filter([
    'student_id' => $fStudent,
    'group'      => $fGroup,
    'classroom'  => $fClassroom,
    'brief'      => $brief ? null : '1',
], function ($v) { return $v !== '' && $v !== null; }));

$titleWho = ($fStudent !== '' && isset($data['students'][$fStudent]))
    ? $data['students'][$fStudent]['student_name']
    : 'ทั้งชั้นเรียน (' . count($targets) . ' คน)';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานผลการเรียนรู้รายบุคคล · <?php echo rp_esc($titleWho); ?></title>
<?php rp_styles(); ?>
</head>
<body>
<?php
$extra = '';
if ($role !== 'student') {
    $qs = http_build_query(array_filter([
        'group'     => $fGroup,
        'classroom' => $fClassroom,
    ], function ($v) { return $v !== '' && $v !== null; }));
    $extra = '<a class="btn-alt" href="class_report_print.php' . ($qs ? '?' . rp_esc($qs) : '') . '">'
           . '📊 รายงานภาพรวมชั้นเรียน</a>';
}
$extra .= '<a class="btn-back" href="?' . rp_esc($switchQs) . '">'
       . ($brief ? '📄 ดูฉบับเต็ม' : '📃 ฉบับย่อ (สั้นลง)') . '</a>';
rp_toolbar($extra . '<span class="hint">พิมพ์ ' . count($targets) . ' ฉบับ · '
    . ($brief ? 'ฉบับย่อ ~3 หน้า/คน' : 'ฉบับเต็ม ~10 หน้า/คน') . ' · ขึ้นหน้าใหม่ให้อัตโนมัติทุกคน</span>');

if (!$targets) {
    echo '<div class="sheet"><div class="no-data">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก</div></div>';
    echo '</body></html>';
    exit;
}

$fullAll = report_full_data($pdo, $targets);

foreach ($targets as $sid):
    $sum = report_student_summary($data, $sid);
    if (!$sum) continue;
    $full = $fullAll[$sid] ?? [];
    $ins  = report_student_insights($sum, $full);
?>
<div class="sheet">
  <?php
  rs_doc_head($sum['student']);
  rs_achievement($sum, '1');
  rs_analysis($ins, '2');
  rs_criteria($sum, '3');
  if ($brief) {
      // ฉบับย่อ: เก็บเฉพาะส่วนที่ครูใช้บ่อยที่สุด เหมาะกับการพิมพ์แจกทั้งห้อง
      rs_works($sum, '4');
  } else {
      rs_eval_detail($full, '4');
      rs_works($sum, '5');
      rs_essays($full, '6');
      rs_ai($full, '7');
      rs_reflection($full, '8');
      rs_peer($full, '9');
  }
  rs_signature($sum);
  ?>
</div>
<?php endforeach; ?>
</body>
</html>
