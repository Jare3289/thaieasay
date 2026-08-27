<?php
/**
 * student_report_print.php
 * เอกสาร "รายงานผลการเรียนรู้รายบุคคล" สำหรับพิมพ์ / บันทึกเป็น PDF
 * เก็บเป็นรายงานประจำตัวนักเรียน 1 คน ต่อ 1 ฉบับ (ครูสั่งพิมพ์ทั้งห้องรวดเดียวก็ได้ — ขึ้นหน้าใหม่ให้อัตโนมัติ)
 *
 * เนื้อหา 3 ส่วนหลัก
 *   1) ผลสัมฤทธิ์ — คะแนนของครูทุกรอบ เทียบกับตนเอง/เพื่อน/ผู้เชี่ยวชาญ และเทียบค่าเฉลี่ยของชั้น
 *   2) ผลงาน     — เรียงความทุกรอบ ผลตรวจของ AI และใบตรวจสอบความครบถ้วน 12 ชิ้น
 *   3) สถิติ     — พัฒนาการก่อน→หลังเรียน รายเกณฑ์ 11 ข้อ จุดแข็ง จุดที่ควรพัฒนา
 *
 * พารามิเตอร์ (GET)
 *   student_id  ระบุ = พิมพ์คนเดียว, ไม่ระบุ = พิมพ์ทุกคนตามตัวกรอง (ครู/ผู้เชี่ยวชาญเท่านั้น)
 *   group       กลุ่มการวิจัย ('__none__' = ยังไม่ระบุกลุ่ม)
 *   classroom   ห้องเรียน
 */

require_once 'auth_helper.php';
require_login();
require_once 'report_data.php';
require_once 'report_print_ui.php';

$me   = $_SESSION['user'];
$role = $me['role'];

$fGroup     = isset($_GET['group'])      ? trim($_GET['group'])      : '';
$fClassroom = isset($_GET['classroom'])  ? trim($_GET['classroom'])  : '';
$fStudent   = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';

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
rp_toolbar($extra . '<span class="hint">พิมพ์ ' . count($targets) . ' ฉบับ · ขึ้นหน้าใหม่ให้อัตโนมัติทุกคน</span>');

if (!$targets) {
    echo '<div class="sheet"><div class="no-data">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก</div></div>';
    echo '</body></html>';
    exit;
}

$critList = report_criteria();
$evalPhases = report_eval_phases();

foreach ($targets as $sid):
    $sum = report_student_summary($data, $sid);
    if (!$sum) continue;
    $stu   = $sum['student'];
    $g     = $sum['growth'];
    $class = $sum['class'];
?>
<div class="sheet">

  <div class="doc-head">
    <h1>รายงานผลการเรียนรู้รายบุคคล</h1>
    <div class="sub">รายวิชาภาษาไทย · การพัฒนาความสามารถในการเขียนเรียงความ</div>
  </div>

  <div class="idbox">
    <div><b>ชื่อ-สกุล:</b> <?php echo rp_esc($stu['student_name'] ?: '—'); ?></div>
    <div><b>รหัสนักเรียน:</b> <?php echo rp_esc($stu['student_id']); ?></div>
    <?php if ($stu['classroom'] !== ''): ?><div><b>ห้อง:</b> <?php echo rp_esc($stu['classroom']); ?></div><?php endif; ?>
    <?php if ($stu['student_group'] !== ''): ?><div><b>กลุ่ม:</b> <?php echo rp_esc($stu['student_group']); ?></div><?php endif; ?>
    <div class="grow"><b>ออกรายงาน:</b> <?php echo rp_esc(rp_thai_date()); ?></div>
  </div>

  <!-- ============================ 1) ผลสัมฤทธิ์ ============================ -->
  <h2 class="sec-title">ส่วนที่ 1 · ผลสัมฤทธิ์ <span>คะแนนเต็ม 60 คะแนน ตามเกณฑ์การประเมินของครูผู้สอน</span></h2>

  <div class="cards">
    <div class="card">
      <div class="lbl">คะแนนก่อนเรียน</div>
      <div class="val"><?php echo rp_num($g['pre'], 1); ?></div>
      <div class="foot"><?php echo $g['level_from'] !== '' ? rp_esc($g['level_from']) : 'ยังไม่มีคะแนน'; ?></div>
    </div>
    <div class="card">
      <div class="lbl">คะแนนหลังเรียน</div>
      <div class="val"><?php echo rp_num($g['post'], 1); ?></div>
      <div class="foot"><?php echo $g['level_to'] !== '' ? rp_esc($g['level_to']) : 'ยังไม่มีคะแนน'; ?></div>
    </div>
    <div class="card">
      <div class="lbl">พัฒนาการ</div>
      <div class="val"><?php echo rp_diff($g['diff'], 1); ?></div>
      <div class="foot"><?php
        echo ($g['pct'] === null) ? 'ต้องมีคะแนนทั้งสองรอบ' : 'คิดเป็น ' . rp_num($g['pct'], 1) . '%';
      ?></div>
    </div>
    <div class="card">
      <div class="lbl">เทียบค่าเฉลี่ยของชั้น</div>
      <div class="val"><?php echo rp_diff($sum['achievement']['posttest']['vs_class'], 1); ?></div>
      <div class="foot">ชั้นเรียนเฉลี่ย <?php echo rp_num($class['phase']['posttest']['mean'] ?? null, 1); ?> คะแนน</div>
    </div>
    <div class="card">
      <div class="lbl">ความครบถ้วนของงาน</div>
      <div class="val"><?php echo (int)$sum['done']; ?>/<?php echo (int)$sum['done_total']; ?></div>
      <div class="foot">ชิ้นงานที่ส่งแล้ว</div>
    </div>
  </div>

  <h3 class="sub-title">คะแนนรายรอบการประเมิน</h3>
  <table>
    <thead>
      <tr>
        <th style="width:24%">รอบการประเมิน</th>
        <th class="num" style="width:11%">ครูประเมิน</th>
        <th class="num" style="width:11%">ตนเอง</th>
        <th class="num" style="width:11%">เพื่อน</th>
        <th class="num" style="width:11%">ผู้เชี่ยวชาญ</th>
        <th class="num" style="width:12%">เฉลี่ยทั้งชั้น</th>
        <th class="num" style="width:10%">สูง/ต่ำกว่า</th>
        <th style="width:10%">ระดับคุณภาพ</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sum['achievement'] as $ph => $a): ?>
      <tr<?php echo ($ph === 'posttest') ? ' class="hi"' : ''; ?>>
        <td><?php echo rp_esc($a['label']); ?>
          <?php echo rp_bar($a['teacher'], 60, $ph === 'pretest' ? 'pre' : 'post'); ?></td>
        <td class="num"><b><?php echo rp_num($a['teacher'], 1); ?></b></td>
        <td class="num"><?php echo rp_num($a['self'], 1); ?></td>
        <td class="num"><?php echo rp_num($a['peer'], 1); ?></td>
        <td class="num"><?php echo rp_num($a['expert'], 1); ?></td>
        <td class="num"><?php echo rp_num($a['class_mean'], 1); ?></td>
        <td class="num"><?php echo rp_diff($a['vs_class'], 1); ?></td>
        <td><?php echo rp_level_badge($a['level']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="note">
    คะแนนของครูผู้สอนเป็นคะแนนที่ใช้ตัดสินผลสัมฤทธิ์ ส่วนคะแนนของตนเองและเพื่อนเป็นข้อมูลประกอบ
    เพื่อให้เห็นว่านักเรียนประเมินงานของตนเองใกล้เคียงกับครูเพียงใด
  </div>

  <!-- ============================ 2) สถิติรายเกณฑ์ ============================ -->
  <h2 class="sec-title">ส่วนที่ 2 · สถิติรายเกณฑ์ <span>เทียบก่อนเรียนกับหลังเรียน และเทียบกับค่าเฉลี่ยของชั้น</span></h2>

  <table>
    <thead>
      <tr>
        <th style="width:34%">เกณฑ์การประเมิน</th>
        <th class="num" style="width:9%">เต็ม</th>
        <th class="num" style="width:11%">ก่อนเรียน</th>
        <th class="num" style="width:11%">หลังเรียน</th>
        <th class="num" style="width:11%">พัฒนาการ</th>
        <th class="num" style="width:12%">เฉลี่ยทั้งชั้น<br>(หลังเรียน)</th>
        <th style="width:12%">สัดส่วนที่ทำได้</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($critList as $c):
        $row = $sum['criteria'][$c['id']]; ?>
      <tr>
        <td><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?></td>
        <td class="num"><?php echo rp_num($c['max'], 0); ?></td>
        <td class="num"><?php echo rp_num($row['pre'], 2); ?></td>
        <td class="num"><b><?php echo rp_num($row['post'], 2); ?></b></td>
        <td class="num"><?php echo rp_diff($row['diff'], 2); ?></td>
        <td class="num"><?php echo rp_num($row['class_post'], 2); ?></td>
        <td><?php echo rp_bar($row['post'], $c['max'], 'post'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td>รวม</td>
        <td class="num">60</td>
        <td class="num"><?php echo rp_num($g['pre'], 2); ?></td>
        <td class="num"><?php echo rp_num($g['post'], 2); ?></td>
        <td class="num"><?php echo rp_diff($g['diff'], 2); ?></td>
        <td class="num"><?php echo rp_num($class['phase']['posttest']['mean'] ?? null, 2); ?></td>
        <td><?php echo rp_bar($g['post'], 60, 'post'); ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="twocol" style="margin-top:10px;">
    <div class="box good">
      <h4>จุดแข็งของนักเรียน</h4>
      <?php if ($sum['strong']): ?>
      <ul>
        <?php foreach ($sum['strong'] as $c): ?>
        <li><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?> —
            ทำได้ <?php echo rp_num($c['post'], 2); ?>/<?php echo rp_num($c['max'], 0); ?>
            (<?php echo rp_num($c['pct'], 0); ?>%)</li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><div class="muted">ยังไม่มีคะแนนหลังเรียนสำหรับจัดอันดับ</div><?php endif; ?>
    </div>
    <div class="box watch">
      <h4>จุดที่ควรพัฒนาต่อ</h4>
      <?php if ($sum['weak']): ?>
      <ul>
        <?php foreach ($sum['weak'] as $c): ?>
        <li><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?> —
            ทำได้ <?php echo rp_num($c['post'], 2); ?>/<?php echo rp_num($c['max'], 0); ?>
            (<?php echo rp_num($c['pct'], 0); ?>%)</li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><div class="muted">ยังไม่มีคะแนนหลังเรียนสำหรับจัดอันดับ</div><?php endif; ?>
    </div>
  </div>

  <!-- ============================ 3) ผลงาน ============================ -->
  <h2 class="sec-title">ส่วนที่ 3 · ผลงานของนักเรียน <span>เรียงความทุกรอบ และผลตรวจโดยผู้ช่วย AI</span></h2>

  <table>
    <thead>
      <tr>
        <th style="width:28%">รอบงาน</th>
        <th class="num" style="width:11%">สถานะ</th>
        <th class="num" style="width:11%">จำนวนคำ</th>
        <th class="num" style="width:16%">บันทึกล่าสุด</th>
        <th class="num" style="width:12%">คะแนน AI</th>
        <th class="num" style="width:10%">ครั้งที่ตรวจ</th>
        <th class="num" style="width:12%">เทียบครั้งก่อน</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($sum['works'] as $ph => $w): ?>
      <tr>
        <td><?php echo rp_esc($w['label']); ?></td>
        <td class="num"><?php echo $w['submitted']
            ? '<span class="pill lv-3">ส่งแล้ว</span>'
            : '<span class="pill lv-0">ยังไม่ส่ง</span>'; ?></td>
        <td class="num"><?php echo $w['submitted'] ? number_format($w['word_count']) : '—'; ?></td>
        <td class="num"><?php echo rp_esc($w['submitted'] ? rp_when($w['updated_at']) : '—'); ?></td>
        <td class="num"><?php echo ($w['ai_total'] === null)
            ? '<span class="muted">ยังไม่ตรวจ</span>'
            : rp_num($w['ai_total'], 1) . ' <span class="muted">/ ' . rp_num($w['ai_max'], 0) . '</span>'; ?></td>
        <td class="num"><?php echo $w['ai_round'] > 0 ? (int)$w['ai_round'] : '—'; ?></td>
        <td class="num"><?php echo ($w['ai_delta'] === null) ? '<span class="muted">—</span>' : rp_diff($w['ai_delta'], 2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div class="note">
    คะแนนของ AI เป็นเพียงข้อมูลประกอบการพัฒนางานเขียน (เต็ม <?php echo rp_num(ai_rubric_max(), 0); ?> คะแนนเฉพาะข้อที่ AI ตรวจได้)
    ไม่ถูกนำไปรวมกับคะแนนจริงของครู · ช่อง "เทียบครั้งก่อน" คือคะแนนที่เปลี่ยนไปหลังนักเรียนแก้ไขงานแล้วให้ AI ตรวจซ้ำ
  </div>

  <h3 class="sub-title">ใบตรวจสอบความครบถ้วนของชิ้นงาน (<?php echo (int)$sum['done']; ?>/<?php echo (int)$sum['done_total']; ?> ชิ้น)</h3>
  <div class="checklist">
    <?php foreach ($sum['checklist'] as $it): ?>
    <div class="item">
      <span><?php echo rp_esc($it['label']); ?></span>
      <span class="<?php echo $it['done'] ? 'yes' : 'no'; ?>"><?php echo $it['done'] ? '✓ ส่งแล้ว' : '✗ ยังไม่ส่ง'; ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ============================ 4) ข้อเสนอแนะ ============================ -->
  <?php
  $fb = $sum['latest_ai'];
  $peerRecv = $sum['peer']['received'] ?? [];
  if ($fb || $peerRecv):
  ?>
  <h2 class="sec-title">ส่วนที่ 4 · ข้อเสนอแนะเพื่อพัฒนางานเขียน</h2>

  <?php if ($fb): ?>
  <h3 class="sub-title">จากผู้ช่วย AI · <?php echo rp_esc($fb['phase_label']); ?>
    (ตรวจครั้งที่ <?php echo (int)($fb['review_round'] ?? 1); ?>)</h3>

  <?php if (!empty($fb['progress']['has_prev'])):
    $pg = $fb['progress']; ?>
  <div class="box info" style="margin-bottom:8px;">
    <h4>ความเปลี่ยนแปลงจากการตรวจครั้งก่อน</h4>
    <div>คะแนน <?php echo rp_num($pg['prev_total'], 2); ?> → <?php echo rp_num($pg['total'], 2); ?>
      <?php echo rp_diff($pg['total_delta'], 2); ?>
      · ดีขึ้น <?php echo (int)$pg['up']; ?> ข้อ · ลดลง <?php echo (int)$pg['down']; ?> ข้อ
      · เท่าเดิม <?php echo (int)$pg['same']; ?> ข้อ</div>
    <?php if (!empty($pg['comment'])): ?>
    <div style="margin-top:4px;"><?php echo rp_esc($pg['comment']); ?></div>
    <?php endif; ?>
    <?php if (!empty($pg['fixed'])): ?>
    <div style="margin-top:4px;"><b>แก้ได้แล้ว:</b>
      <?php
      $fixedNames = [];
      foreach ($pg['fixed'] as $f) {
          $fixedNames[] = trim(($f['criterion'] !== '' ? 'ข้อ ' . $f['criterion'] . ' ' : '') . $f['name']);
      }
      echo rp_esc(implode(', ', array_filter($fixedNames)));
      ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <div class="twocol">
    <div class="box good">
      <h4>จุดแข็งที่ AI พบ</h4>
      <?php if (!empty($fb['strengths'])): ?>
      <ul><?php foreach ($fb['strengths'] as $s): ?><li><?php echo rp_esc($s); ?></li><?php endforeach; ?></ul>
      <?php else: ?><div class="muted">— ไม่มีข้อมูล —</div><?php endif; ?>
    </div>
    <div class="box watch">
      <h4>จุดที่ควรปรับปรุง</h4>
      <?php if (!empty($fb['improvements'])): ?>
      <ul>
        <?php foreach (array_slice($fb['improvements'], 0, 5) as $im): ?>
        <li><?php echo rp_esc(($im['criterion'] !== '' ? 'ข้อ ' . $im['criterion'] . ': ' : '') . $im['issue']); ?>
          <?php if (!empty($im['suggestion'])): ?>
          <div class="muted">แนวทางแก้: <?php echo rp_esc($im['suggestion']); ?></div>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><div class="muted">— ไม่มีข้อมูล —</div><?php endif; ?>
    </div>
  </div>

  <?php if (!empty($fb['next_steps'])): ?>
  <div class="box info" style="margin-top:8px;">
    <h4>สิ่งที่ควรทำต่อในงานเขียนชิ้นถัดไป</h4>
    <ul><?php foreach ($fb['next_steps'] as $s): ?><li><?php echo rp_esc($s); ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($peerRecv): ?>
  <h3 class="sub-title">จากเพื่อนร่วมชั้น (<?php echo count($peerRecv); ?> คน)</h3>
  <table>
    <thead><tr><th style="width:18%">ผู้ให้ข้อคิดเห็น</th><th style="width:41%">จุดแข็งที่เพื่อนเห็น</th><th style="width:41%">จุดที่เพื่อนแนะนำให้ปรับปรุง</th></tr></thead>
    <tbody>
      <?php foreach (array_slice($peerRecv, 0, 4) as $pr): ?>
      <tr>
        <td><?php echo rp_esc($pr['reviewer'] ?: '—'); ?></td>
        <td><?php echo rp_esc($pr['strength'] ?: '—'); ?></td>
        <td><?php echo rp_esc($pr['improvement'] ?: '—'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
  <?php endif; ?>

  <div class="signrow">
    <div class="sign"><div class="line"></div>ลงชื่อครูผู้สอน</div>
    <div class="sign"><div class="line"></div>ลงชื่อนักเรียน</div>
    <div class="sign"><div class="line"></div>ลงชื่อผู้ปกครองรับทราบ</div>
  </div>

  <div class="foot-note">
    รายงานฉบับนี้สร้างจากข้อมูลในระบบประเมินการเขียนเรียงความ เมื่อ <?php echo rp_esc(rp_thai_date()); ?>
    · ค่าเฉลี่ยของชั้นคิดจากนักเรียน <?php echo (int)$class['count']; ?> คนที่อยู่ในขอบเขตของรายงานนี้
  </div>
</div>
<?php endforeach; ?>
</body>
</html>
