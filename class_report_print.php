<?php
/**
 * class_report_print.php
 * เอกสาร "รายงานภาพรวมชั้นเรียน" สำหรับพิมพ์ / บันทึกเป็น PDF
 * สรุปผลของทั้งห้องไว้ในฉบับเดียว: ผลสัมฤทธิ์รายรอบ พัฒนาการ การกระจายระดับคุณภาพ
 * ค่าเฉลี่ยรายเกณฑ์ การส่งงาน ภาพรวมการตรวจอัตโนมัติและรายชื่อที่ควรติดตาม
 *
 * พารามิเตอร์ (GET): group (กลุ่มการวิจัย, '__none__' = ยังไม่ระบุกลุ่ม), classroom (ห้องเรียน)
 */

require_once 'auth_helper.php';
require_login();
if (!in_array($_SESSION['user']['role'], ['teacher', 'expert'], true)) {
    header('Location: index.php');
    exit;
}
require_once 'report_data.php';
require_once 'report_print_ui.php';

$fGroup     = isset($_GET['group'])     ? trim($_GET['group'])     : '';
$fClassroom = isset($_GET['classroom']) ? trim($_GET['classroom']) : '';

$data  = report_dataset($pdo, ['group' => $fGroup, 'classroom' => $fClassroom]);
$class = $data['class'];
$crit  = report_criteria();

$scopeParts = [];
$scopeParts[] = ($fClassroom !== '' && $fClassroom !== 'all') ? 'ห้อง ' . $fClassroom : 'ทุกห้องเรียน';
if ($fGroup === '__none__')                          $scopeParts[] = 'เฉพาะผู้ที่ยังไม่ระบุกลุ่ม';
elseif ($fGroup !== '' && $fGroup !== 'all')         $scopeParts[] = 'กลุ่ม ' . $fGroup;
else                                                 $scopeParts[] = 'ทุกกลุ่มการวิจัย';
$scope = implode(' · ', $scopeParts);

// ระดับคุณภาพที่ใช้แสดงการกระจาย (เรียงจากสูงไปต่ำ)
$levelOrder = ['ดีมาก', 'ดี', 'ปานกลาง', 'พอใช้', 'ต้องปรับปรุง'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานภาพรวมชั้นเรียน · <?php echo rp_esc($scope); ?></title>
<?php rp_styles(); ?>
</head>
<body>
<?php
$qs = http_build_query(array_filter([
    'group'     => $fGroup,
    'classroom' => $fClassroom,
], function ($v) { return $v !== '' && $v !== null; }));
rp_toolbar('<a class="btn-alt" href="student_report_print.php' . ($qs ? '?' . rp_esc($qs) : '') . '">'
    . '🧑‍🎓 พิมพ์รายงานรายบุคคลทั้งห้อง</a>');

if (!$class['count']) {
    echo '<div class="sheet"><div class="no-data">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก</div></div></body></html>';
    exit;
}
?>
<div class="sheet">

  <div class="doc-head">
    <h1>รายงานภาพรวมชั้นเรียน</h1>
    <div class="sub">รายวิชาภาษาไทย · การพัฒนาความสามารถในการเขียนเรียงความ</div>
  </div>

  <div class="idbox">
    <div><b>ขอบเขตรายงาน:</b> <?php echo rp_esc($scope); ?></div>
    <div><b>จำนวนนักเรียน:</b> <?php echo (int)$class['count']; ?> คน</div>
    <div class="grow"><b>ออกรายงาน:</b> <?php echo rp_esc(rp_thai_date()); ?></div>
  </div>

  <!-- ============================ ภาพรวม ============================ -->
  <h2 class="sec-title">ส่วนที่ 1 · ภาพรวมผลสัมฤทธิ์ <span>คิดจากคะแนนของครูผู้สอน เต็ม 60 คะแนน</span></h2>

  <div class="cards">
    <div class="card">
      <div class="lbl">เฉลี่ยก่อนเรียน</div>
      <div class="val"><?php echo rp_num($class['phase']['pretest']['mean'] ?? null, 2); ?></div>
      <div class="foot">จาก <?php echo (int)($class['phase']['pretest']['n'] ?? 0); ?> คน</div>
    </div>
    <div class="card">
      <div class="lbl">เฉลี่ยหลังเรียน</div>
      <div class="val"><?php echo rp_num($class['phase']['posttest']['mean'] ?? null, 2); ?></div>
      <div class="foot">จาก <?php echo (int)($class['phase']['posttest']['n'] ?? 0); ?> คน</div>
    </div>
    <div class="card">
      <div class="lbl">พัฒนาการเฉลี่ย</div>
      <div class="val"><?php echo rp_diff($class['growth']['mean'], 2); ?></div>
      <div class="foot">S.D. <?php echo rp_num($class['growth']['sd'], 2); ?> · <?php echo (int)$class['growth']['n']; ?> คน</div>
    </div>
    <div class="card">
      <div class="lbl">คะแนนสูงขึ้น</div>
      <div class="val"><?php echo (int)$class['growth']['improved']; ?> คน</div>
      <div class="foot">ลดลง <?php echo (int)$class['growth']['declined']; ?> · เท่าเดิม <?php echo (int)$class['growth']['same']; ?></div>
    </div>
    <div class="card">
      <div class="lbl">ส่งงานครบทุกชิ้น</div>
      <div class="val"><?php echo (int)$class['submission']['complete']; ?> คน</div>
      <div class="foot">เฉลี่ย <?php echo rp_num($class['submission']['mean_done'], 1); ?>/<?php echo (int)$class['submission']['items']; ?> ชิ้น</div>
    </div>
  </div>

  <h3 class="sub-title">สถิติคะแนนรายรอบการประเมิน</h3>
  <table>
    <thead>
      <tr>
        <th style="width:26%">รอบการประเมิน</th>
        <th class="num" style="width:9%">n</th>
        <th class="num" style="width:12%">ค่าเฉลี่ย</th>
        <th class="num" style="width:11%">S.D.</th>
        <th class="num" style="width:11%">ต่ำสุด</th>
        <th class="num" style="width:11%">สูงสุด</th>
        <th style="width:20%">สัดส่วนของคะแนนเต็ม</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($class['phase'] as $ph => $st): ?>
      <tr<?php echo ($ph === 'posttest') ? ' class="hi"' : ''; ?>>
        <td><?php echo rp_esc($st['label']); ?></td>
        <td class="num"><?php echo (int)$st['n']; ?></td>
        <td class="num"><b><?php echo rp_num($st['mean'], 2); ?></b></td>
        <td class="num"><?php echo rp_num($st['sd'], 2); ?></td>
        <td class="num"><?php echo rp_num($st['min'], 1); ?></td>
        <td class="num"><?php echo rp_num($st['max'], 1); ?></td>
        <td><?php echo rp_bar($st['mean'], 60, $ph === 'pretest' ? 'pre' : 'post'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3 class="sub-title">การกระจายระดับคุณภาพ</h3>
  <table>
    <thead>
      <tr>
        <th style="width:26%">รอบการประเมิน</th>
        <?php foreach ($levelOrder as $lv): ?>
        <th class="num"><?php echo rp_esc($lv); ?></th>
        <?php endforeach; ?>
        <th class="num" style="width:10%">รวม</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($class['phase'] as $ph => $st): ?>
      <tr>
        <td><?php echo rp_esc($st['label']); ?></td>
        <?php foreach ($levelOrder as $lv): ?>
        <td class="num"><?php echo (int)($st['levels'][$lv] ?? 0); ?></td>
        <?php endforeach; ?>
        <td class="num"><b><?php echo (int)$st['n']; ?></b></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="note">
    เกณฑ์ระดับคุณภาพ: ดีมาก 49-60 · ดี 37-48 · ปานกลาง 25-36 · พอใช้ 13-24 · ต้องปรับปรุง 0-12 คะแนน
  </div>

  <!-- ============================ รายเกณฑ์ ============================ -->
  <h2 class="sec-title">ส่วนที่ 2 · ค่าเฉลี่ยรายเกณฑ์ของทั้งชั้น <span>ใช้ชี้ว่าควรเน้นสอนซ่อมเสริมเรื่องใด</span></h2>

  <table>
    <thead>
      <tr>
        <th style="width:34%">เกณฑ์การประเมิน</th>
        <th class="num" style="width:9%">เต็ม</th>
        <th class="num" style="width:12%">ก่อนเรียน</th>
        <th class="num" style="width:12%">หลังเรียน</th>
        <th class="num" style="width:12%">พัฒนาการ</th>
        <th style="width:21%">สัดส่วนที่ทำได้ (หลังเรียน)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($crit as $c):
        $pre  = $class['criteria']['pretest'][$c['id']]  ?? null;
        $post = $class['criteria']['posttest'][$c['id']] ?? null;
        $dif  = ($pre === null || $post === null) ? null : $post - $pre; ?>
      <tr>
        <td><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?></td>
        <td class="num"><?php echo rp_num($c['max'], 0); ?></td>
        <td class="num"><?php echo rp_num($pre, 2); ?></td>
        <td class="num"><b><?php echo rp_num($post, 2); ?></b></td>
        <td class="num"><?php echo rp_diff($dif, 2); ?></td>
        <td><?php echo rp_bar($post, $c['max'], 'post'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ============================ รายบุคคลย่อ ============================ -->
  <h2 class="sec-title">ส่วนที่ 3 · สรุปรายบุคคล <span>เรียงตามห้องและรหัสนักเรียน</span></h2>

  <table>
    <thead>
      <tr>
        <th style="width:11%">รหัส</th>
        <th style="width:27%">ชื่อ-สกุล</th>
        <th class="num" style="width:11%">ก่อนเรียน</th>
        <th class="num" style="width:11%">หลังเรียน</th>
        <th class="num" style="width:11%">พัฒนาการ</th>
        <th style="width:14%">ระดับ (หลังเรียน)</th>
        <th class="num" style="width:15%">ส่งงาน</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data['students'] as $sid => $stu):
        $pre  = $data['evals'][$sid]['pretest']['teacher']['total']  ?? null;
        $post = $data['evals'][$sid]['posttest']['teacher']['total'] ?? null;
        $dif  = ($pre === null || $post === null) ? null : (float)$post - (float)$pre;
        $done = report_done_count($data, $sid); ?>
      <tr>
        <td><?php echo rp_esc($sid); ?></td>
        <td><?php echo rp_esc($stu['student_name']); ?></td>
        <td class="num"><?php echo rp_num($pre, 1); ?></td>
        <td class="num"><b><?php echo rp_num($post, 1); ?></b></td>
        <td class="num"><?php echo rp_diff($dif, 1); ?></td>
        <td><?php echo rp_level_badge($post === null ? '' : report_level((float)$post)); ?></td>
        <td class="num"><?php echo (int)$done; ?>/<?php echo (int)$class['submission']['items']; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2">ค่าเฉลี่ยทั้งชั้น</td>
        <td class="num"><?php echo rp_num($class['phase']['pretest']['mean']  ?? null, 2); ?></td>
        <td class="num"><?php echo rp_num($class['phase']['posttest']['mean'] ?? null, 2); ?></td>
        <td class="num"><?php echo rp_diff($class['growth']['mean'], 2); ?></td>
        <td>—</td>
        <td class="num"><?php echo rp_num($class['submission']['mean_done'], 1); ?>/<?php echo (int)$class['submission']['items']; ?></td>
      </tr>
    </tfoot>
  </table>

  <?php $top = array_slice(array_filter($class['top_growth'], function ($r) { return $r['diff'] > 0; }), 0, 10); ?>
  <?php if ($top): ?>
  <h3 class="sub-title">นักเรียนที่มีพัฒนาการสูงสุด</h3>
  <table>
    <thead>
      <tr><th style="width:8%">ลำดับ</th><th style="width:12%">รหัส</th><th>ชื่อ-สกุล</th>
          <th class="num" style="width:13%">ก่อนเรียน</th><th class="num" style="width:13%">หลังเรียน</th>
          <th class="num" style="width:14%">พัฒนาการ</th></tr>
    </thead>
    <tbody>
      <?php $i = 1; foreach ($top as $r): ?>
      <tr>
        <td class="num"><?php echo $i++; ?></td>
        <td><?php echo rp_esc($r['student_id']); ?></td>
        <td><?php echo rp_esc($r['student_name']); ?></td>
        <td class="num"><?php echo rp_num($r['pre'], 1); ?></td>
        <td class="num"><?php echo rp_num($r['post'], 1); ?></td>
        <td class="num"><?php echo rp_diff($r['diff'], 1); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- ============================ การส่งงาน + ระบบ ============================ -->
  <h2 class="sec-title">ส่วนที่ 4 · การส่งงานและการตรวจด้วยระบบตรวจอัตโนมัติ</h2>

  <h3 class="sub-title">การส่งงานรายชิ้น (จากนักเรียน <?php echo (int)$class['count']; ?> คน)</h3>
  <table>
    <thead><tr><th style="width:46%">ชิ้นงาน</th><th class="num" style="width:14%">ส่งแล้ว</th>
               <th class="num" style="width:14%">ยังไม่ส่ง</th><th style="width:26%">สัดส่วนการส่ง</th></tr></thead>
    <tbody>
      <?php foreach ($class['submission']['per_item'] as $key => $it): ?>
      <tr>
        <td><?php echo rp_esc($it['label']); ?></td>
        <td class="num"><b><?php echo (int)$it['n']; ?></b></td>
        <td class="num"><?php echo (int)($class['count'] - $it['n']); ?></td>
        <td><?php echo rp_bar($it['n'], $class['count'], 'post'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h3 class="sub-title">ภาพรวมผลตรวจของระบบรายรอบงาน</h3>
  <table>
    <thead>
      <tr>
        <th style="width:30%">รอบงาน</th>
        <th class="num" style="width:12%">ตรวจแล้ว</th>
        <th class="num" style="width:16%">คะแนนเฉลี่ย</th>
        <th class="num" style="width:14%">ตรวจซ้ำ</th>
        <th class="num" style="width:28%">ส่วนต่างเฉลี่ย<br>(เทียบฉบับตั้งต้นตามคู่)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($class['ai'] as $ph => $st): ?>
      <tr>
        <td><?php echo rp_esc($st['label']); ?></td>
        <td class="num"><?php echo (int)$st['n']; ?></td>
        <td class="num"><?php echo ($st['n'] === 0)
            ? '<span class="muted">ยังไม่มีการตรวจ</span>'
            : rp_num($st['mean'], 2) . ' <span class="muted">/ ' . rp_num($st['max_score'], 0) . '</span>'; ?></td>
        <td class="num"><?php echo (int)$st['rechecked']; ?> ฉบับ</td>
        <td class="num"><?php echo ($st['mean_delta'] === null)
            ? '<span class="muted">ไม่มีคู่เทียบ</span>'
            : rp_diff($st['mean_delta'], 2)
              . ' <span class="muted">(ดีขึ้น ' . (int)$st['improved'] . '/' . (int)$st['paired'] . ')</span>'; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="note">
    ช่อง "ตรวจซ้ำ" คือจำนวนฉบับที่นักเรียนแก้ไขแล้วให้ระบบตรวจอีกครั้ง
    ส่วน "ส่วนต่างเฉลี่ย" คือคะแนนที่ต่างจากฉบับตั้งต้นตามคู่ที่ครูกำหนด
    (D1.2 เทียบ D1.1 · D2.2 เทียบ D2.1 · หลังเรียน เทียบ ก่อนเรียน) พร้อมจำนวนฉบับที่ได้คะแนนสูงกว่าฉบับตั้งต้นจริง
    — รอบที่เป็นฉบับตั้งต้นเอง (ก่อนเรียน · ร่างที่ 1) จึงไม่มีคู่เทียบ
  </div>

  <?php if (!empty($class['watchlist'])): ?>
  <h2 class="sec-title">ส่วนที่ 5 · นักเรียนที่ควรติดตามเป็นพิเศษ</h2>
  <table>
    <thead><tr><th style="width:12%">รหัส</th><th style="width:26%">ชื่อ-สกุล</th>
               <th class="num" style="width:12%">ส่งงาน</th><th class="num" style="width:14%">หลังเรียน</th>
               <th>เหตุผลที่ควรติดตาม</th></tr></thead>
    <tbody>
      <?php foreach ($class['watchlist'] as $w): ?>
      <tr>
        <td><?php echo rp_esc($w['student_id']); ?></td>
        <td><?php echo rp_esc($w['student_name']); ?></td>
        <td class="num"><?php echo (int)$w['done']; ?>/<?php echo (int)$class['submission']['items']; ?></td>
        <td class="num"><?php echo rp_num($w['post'], 1); ?></td>
        <td><?php echo rp_esc(implode(' · ', $w['reasons'])); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="signrow">
    <div class="sign"><div class="line"></div>ลงชื่อครูผู้สอน</div>
    <div class="sign"><div class="line"></div>ลงชื่อหัวหน้ากลุ่มสาระการเรียนรู้</div>
  </div>

  <div class="foot-note">
    รายงานฉบับนี้สร้างจากข้อมูลในระบบประเมินการเขียนเรียงความ เมื่อ <?php echo rp_esc(rp_thai_date()); ?>
    · ค่าเฉลี่ยและ S.D. คิดเฉพาะนักเรียนที่มีคะแนนในรอบนั้น ๆ (n ตามที่ระบุในตาราง)
  </div>
</div>
</body>
</html>
