<?php
/**
 * chapter45_print.php — ร่าง "บทที่ 4 และบทที่ 5" ฉบับประกอบเสร็จ สำหรับพิมพ์หรือคัดลอกไปวาง
 * ---------------------------------------------------------------------------
 * ประกอบ 2 ส่วนเข้าด้วยกัน
 *   1) ตัวเลขและตารางที่ระบบคำนวณจากข้อมูลจริง (ตาราง 12 และ 14 พร้อมสถิติทุกตัว)
 *   2) ความเรียงที่ระบบเรียบเรียงไว้ในหน้า "วิเคราะห์บทที่ 4 และบทที่ 5"
 *
 * หัวข้อใดยังไม่ได้วิเคราะห์จะขึ้นเป็นช่องว่างสีเหลืองพร้อมบอกว่าต้องกดวิเคราะห์หัวข้อใด
 * เอกสารนี้เป็น "ร่าง" ผู้วิจัยต้องอ่านทวนและปรับสำนวนก่อนนำไปใช้จริงเสมอ
 *
 * พารามิเตอร์ (GET): group (กลุ่มการวิจัย), classroom (ห้องเรียน)
 */

require_once 'auth_helper.php';
require_login();
if (!in_array($_SESSION['user']['role'], ['teacher', 'expert'], true)) {
    header('Location: index.php');
    exit;
}
require_once 'chapter45_engine.php';
require_once 'report_print_ui.php';

$fGroup     = isset($_GET['group'])     ? trim($_GET['group'])     : '';
$fClassroom = isset($_GET['classroom']) ? trim($_GET['classroom']) : '';

$ctx     = ch45_build_context($pdo, ['group' => $fGroup, 'classroom' => $fClassroom]);
$ds      = $ctx['ds'];
$meta    = $ds['meta'];
$quant   = $ctx['quant'];
$defects = $ctx['defects'];
$mech    = $ctx['mech'];
$R       = $ctx['results'];
$doms    = ch45_domains();
$inds    = ch45_indicators();

/** ดึงข้อความจากผลวิเคราะห์ของระบบ — ไม่มีให้ขึ้นช่องว่างที่บอกว่าต้องทำอะไรต่อ */
function c45p($R, $job, $field, $label = '') {
    $v = $R[$job]['payload'][$field] ?? '';
    if (trim((string)$v) === '') {
        $jobs = ch45_ai_jobs();
        $name = $jobs[$job]['label'] ?? $job;
        return '<span class="todo">[ยังไม่มีเนื้อหา' . ($label ? ' — ' . rp_esc($label) : '')
             . ' · กดวิเคราะห์หัวข้อ &quot;' . rp_esc($name) . '&quot; ในหน้าวิเคราะห์บทที่ 4-5]</span>';
    }
    return rp_esc($v);
}

/** ย่อหน้าเนื้อความของวิทยานิพนธ์ (ย่อหน้าแรกเว้นวรรค 2.5 em ตามรูปแบบเอกสาร) */
function c45para($html) {
    return '<p class="para">' . $html . '</p>';
}

/** ข้อความตัวอย่างที่ยกจากผลงานจริง พร้อมบรรทัดอ้างอิงและป้ายผลการตรวจสอบ */
function c45quote($ex, $roundLabel) {
    if (!$ex || trim((string)($ex['text'] ?? '')) === '') {
        return '<div class="quote todo-box">[ยังไม่มีข้อความตัวอย่างจากผลงานจริง]</div>';
    }
    $bad = ($ex['verified'] === false);
    return '<div class="quote' . ($bad ? ' quote-bad' : '') . '">'
        . rp_esc($ex['text'])
        . '<div class="quote-cite">(นักเรียนคนที่ ' . rp_esc($ex['student_no']) . ' ' . rp_esc($roundLabel) . ')</div>'
        . ($bad ? '<div class="quote-warn">⚠ ระบบตรวจไม่พบข้อความนี้ในผลงานจริง — ต้องตรวจสอบก่อนนำไปใช้</div>' : '')
        . '</div>';
}

$scopeParts = [];
$scopeParts[] = ($fClassroom !== '' && $fClassroom !== 'all') ? 'ห้อง ' . $fClassroom : 'ทุกห้องเรียน';
if ($fGroup === '__none__')                  $scopeParts[] = 'เฉพาะผู้ที่ยังไม่ระบุกลุ่ม';
elseif ($fGroup !== '' && $fGroup !== 'all') $scopeParts[] = 'กลุ่ม ' . $fGroup;
else                                         $scopeParts[] = 'ทุกกลุ่มการวิจัย';
$scope = implode(' · ', $scopeParts);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ร่างบทที่ 4 และบทที่ 5 · <?php echo rp_esc($scope); ?></title>
<?php rp_styles(); ?>
<style>
  .para { text-indent: 2.5em; margin: 0 0 10px; line-height: 1.75; text-align: justify; }
  .para-noindent { text-indent: 0; }
  h3.sub { font-size: 16pt; font-weight: 700; margin: 16px 0 6px; }
  h4.sub2 { font-size: 16pt; font-weight: 700; margin: 12px 0 4px; }
  .tbl-cap { font-weight: 700; margin: 14px 0 0; }
  .tbl-title { font-style: italic; margin: 0 0 6px; }
  .tbl-note { font-size: 14pt; margin: 4px 0 12px; }
  .quote { margin: 8px 0 8px 2.5em; padding: 8px 12px; border-left: 3px solid #94a3b8;
           background: #f8fafc; line-height: 1.75; }
  .quote-cite { text-align: right; font-size: 14pt; color: #475569; margin-top: 4px; }
  .quote-warn { color: #b91c1c; font-size: 14pt; margin-top: 4px; font-weight: 700; }
  .quote-bad { border-left-color: #dc2626; background: #fef2f2; }
  .todo { background: #fef3c7; color: #92400e; padding: 1px 6px; border-radius: 4px; font-size: 14pt; }
  .todo-box { border-left-color: #f59e0b; background: #fffbeb; color: #92400e; }
  .draft-note { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px;
                padding: 10px 14px; margin-bottom: 14px; font-size: 14pt; }
  table.thesis { width: 100%; border-collapse: collapse; font-size: 14pt; margin-bottom: 6px; }
  table.thesis th, table.thesis td { padding: 5px 7px; vertical-align: top; }
  table.thesis thead th { border-top: 1.5px solid #1e293b; border-bottom: 1px solid #1e293b; text-align: center; }
  table.thesis tbody tr:last-child td { border-bottom: 1.5px solid #1e293b; }
  table.thesis td.l, table.thesis th.l { text-align: left; }
  table.thesis td.c { text-align: center; }
  .grp td { font-weight: 700; padding-top: 8px; }
</style>
</head>
<body>
<?php rp_toolbar('<a class="btn-alt" href="chapter45.php">⟵ กลับหน้าวิเคราะห์</a>'); ?>

<div class="sheet">
  <div class="draft-note no-print">
    <strong>เอกสารนี้เป็น &quot;ร่าง&quot;</strong> — ตัวเลขและตารางคำนวณจากข้อมูลจริงในระบบ
    ส่วนความเรียงเรียบเรียงโดยระบบผู้วิจัยต้องอ่านทวน ตรวจสอบข้อความตัวอย่างกับต้นฉบับลายมือ
    และปรับสำนวนให้เป็นเสียงของตนเองก่อนนำไปใช้ในวิทยานิพนธ์เสมอ ·
    ขอบเขตข้อมูล: <?php echo rp_esc($scope); ?> · จัดทำเมื่อ <?php echo rp_thai_date(); ?>
  </div>

  <div class="doc-head">
    <h1>บทที่ 4</h1>
    <div class="sub">ผลการวิจัย</div>
  </div>

  <?php
  echo c45para('ผลการวิจัย เรื่องผลการจัดการเรียนการสอนเขียนตามแนวคิด POA ที่มีต่อความสามารถในการเขียนเรียงความ'
    . 'ของนักเรียนมัธยมศึกษาตอนปลาย ผู้วิจัยนำเสนอผลการวิจัยเป็น 2 ตอน ตามวัตถุประสงค์ในการวิจัย ดังนี้');
  echo c45para('ตอนที่ 1 ผลการเปรียบเทียบความสามารถในการเขียนเรียงความของนักเรียนมัธยมศึกษาตอนปลาย'
    . 'ก่อนและหลังได้รับการจัดการเรียนการสอนเขียนตามแนวคิด POA');
  echo c45para('ตอนที่ 2 ผลการวิเคราะห์การเปลี่ยนแปลงความสามารถในการเขียนเรียงความของนักเรียน'
    . 'มัธยมศึกษาตอนปลายระหว่างที่ได้รับการจัดการเรียนการสอนเขียนตามแนวคิด POA');
  ?>

  <h2 class="sec-title">ตอนที่ 1 <span>ผลการเปรียบเทียบความสามารถในการเขียนเรียงความก่อนและหลังเรียน</span></h2>
  <?php echo c45para(c45p($R, 'quant_narrative', 'para_method', 'ย่อหน้าตรวจสอบข้อตกลงเบื้องต้น')); ?>

  <p class="tbl-cap">ตาราง 12</p>
  <p class="tbl-title">ผลการเปรียบเทียบความสามารถในการเขียนเรียงความของนักเรียนมัธยมศึกษาตอนปลาย
     ก่อนและหลังได้รับการจัดการเรียนการสอนเขียนตามแนวคิด POA</p>
  <table class="thesis">
    <thead>
      <tr>
        <th class="l" rowspan="2">ความสามารถในการเขียนเรียงความ</th>
        <th rowspan="2">คะแนนเต็ม</th>
        <th colspan="2">ก่อนเรียน</th>
        <th colspan="2">หลังเรียน</th>
        <th rowspan="2">t</th><th rowspan="2">p</th><th rowspan="2">d</th>
      </tr>
      <tr><th>M</th><th>SD</th><th>M</th><th>SD</th></tr>
    </thead>
    <tbody>
      <?php foreach ($quant['rows'] as $r): ?>
      <tr>
        <td class="l"><?php echo rp_esc($r['label']); ?></td>
        <td class="c"><?php echo rp_num($r['max'], 0); ?></td>
        <td class="c"><?php echo rp_num($r['pre_mean']); ?></td>
        <td class="c"><?php echo rp_num($r['pre_sd']); ?></td>
        <td class="c"><?php echo rp_num($r['post_mean']); ?></td>
        <td class="c"><?php echo rp_num($r['post_sd']); ?></td>
        <td class="c"><?php echo ch45_fmt($r['t'], 3) . ($r['sig'] ? '*' : ''); ?></td>
        <td class="c"><?php echo ch45_fmt_p($r['p']); ?></td>
        <td class="c"><?php echo ch45_fmt($r['dz']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="tbl-note">หมายเหตุ. n = <?php echo (int)$quant['n']; ?>, df = <?php echo (int)$quant['df']; ?>,
     *p &lt; .05 · d = ขนาดอิทธิพลแบบ Cohen's d<sub>z</sub> สำหรับข้อมูลจับคู่</p>

  <?php
  echo c45para(c45p($R, 'quant_narrative', 'para_overall', 'ย่อหน้าอ่านผลภาพรวม'));
  echo c45para(c45p($R, 'quant_narrative', 'para_domains', 'ย่อหน้าผลรายด้าน'));
  echo c45para(c45p($R, 'quant_narrative', 'para_ranking', 'ย่อหน้าเปรียบเทียบขนาดการเปลี่ยนแปลง'));
  ?>

  <h2 class="sec-title">ตอนที่ 2 <span>ผลการวิเคราะห์การเปลี่ยนแปลงระหว่างที่ได้รับการจัดการเรียนการสอน</span></h2>
  <?php
  echo c45para(c45p($R, 'overview', 'intro', 'ย่อหน้านำของตอนที่ 2'));
  echo c45para(c45p($R, 'overview', 'genre_note', 'ย่อหน้าชี้แจงเรื่องประเภทของงานเขียน'));
  ?>

  <p class="tbl-cap">ตาราง 13</p>
  <p class="tbl-title">สรุปภาพรวมของการเปลี่ยนแปลงของความสามารถในการเขียนเรียงความ</p>
  <table class="thesis">
    <thead><tr><th class="l">องค์ประกอบ</th>
      <th class="l"><?php echo rp_esc($meta['work1_label']); ?></th>
      <th class="l"><?php echo rp_esc($meta['work2_label']); ?></th></tr></thead>
    <tbody>
      <?php foreach ($doms as $dk => $d): ?>
      <tr>
        <td class="l"><?php echo rp_esc($d['name']); ?></td>
        <td class="l"><?php
          $c = $R['overview']['payload']['cells'][$dk] ?? [];
          echo trim((string)($c['work1'] ?? '')) !== '' ? rp_esc($c['work1'])
             : '<span class="todo">[ยังไม่ได้วิเคราะห์ตาราง 13]</span>'; ?></td>
        <td class="l"><?php
          echo trim((string)($c['work2'] ?? '')) !== '' ? rp_esc($c['work2'])
             : '<span class="todo">[ยังไม่ได้วิเคราะห์ตาราง 13]</span>'; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php echo c45para('นอกจากนี้ ผู้วิจัยได้นับจำนวนนักเรียนที่ปรากฏข้อบกพร่องในแต่ละตัวบ่งชี้จากผลงานทั้ง 2 ครั้ง '
    . 'เพื่อแสดงการเปลี่ยนแปลงในเชิงปริมาณควบคู่ไปกับการวิเคราะห์เนื้อหา ดังตาราง 14'); ?>

  <p class="tbl-cap">ตาราง 14</p>
  <p class="tbl-title">จำนวนและร้อยละของนักเรียนที่ปรากฏข้อบกพร่องในผลงานเรียงความ จำนวน 2 ครั้ง</p>
  <table class="thesis">
    <thead>
      <tr><th class="l" rowspan="2">ข้อบกพร่องที่พบในผลงานเรียงความ</th>
        <th colspan="2"><?php echo rp_esc($meta['work1_label']); ?></th>
        <th colspan="2"><?php echo rp_esc($meta['work2_label']); ?></th></tr>
      <tr><th>n</th><th>%</th><th>n</th><th>%</th></tr>
    </thead>
    <tbody>
      <?php foreach ($doms as $dk => $d): ?>
        <tr class="grp"><td class="l" colspan="5">ด้าน<?php echo rp_esc($d['name']); ?></td></tr>
        <?php foreach ($d['indicators'] as $id): $r = $defects['rows'][$id]; ?>
        <tr>
          <td class="l"><?php echo (int)$r['no'] . '. ' . rp_esc($r['defect']); ?></td>
          <td class="c"><?php echo (int)$r['n1']; ?></td>
          <td class="c"><?php echo rp_num($r['pct1']); ?></td>
          <td class="c"><?php echo (int)$r['n2']; ?></td>
          <td class="c"><?php echo rp_num($r['pct2']); ?></td>
        </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="tbl-note">หมายเหตุ. n = <?php echo (int)$defects['n']; ?> ·
     <?php echo rp_esc($defects['rule']); ?></p>

  <?php echo c45para(c45p($R, 'defect_narrative', 'para', 'ย่อหน้าใต้ตาราง 14')); ?>

  <?php echo c45para('เมื่อผู้วิจัยวิเคราะห์การเปลี่ยนแปลงความสามารถในการเขียนเรียงความแบบจำแนกตามองค์ประกอบ 4 ด้าน '
    . 'จากผลงานเรียงความของนักเรียน จำนวน 2 ครั้ง ผู้วิจัยวิเคราะห์เนื้อหาและนำเสนอข้อมูลตามองค์ประกอบต่าง ๆ ดังนี้'); ?>

  <?php foreach ($doms as $dk => $d):
      $djob = 'domain_' . $dk;
      $dpay = $R[$djob]['payload'] ?? []; ?>
    <h3 class="sub"><?php echo rp_esc($d['section'] . ' ด้าน' . $d['name']); ?></h3>
    <?php echo c45para('เมื่อผู้วิจัยตรวจงานเรียงความของนักเรียนมัธยมศึกษาตอนปลายองค์ประกอบด้าน'
        . rp_esc($d['name']) . ' จำนวน 2 ครั้ง ผู้วิจัยได้ข้อค้นพบว่า '
        . c45p($R, $djob, 'finding', 'ข้อค้นพบของด้านนี้')
        . ' ปรากฏผลสรุปการเปลี่ยนแปลงด้าน' . rp_esc($d['name']) . ' ดังนี้'); ?>

    <p class="tbl-cap">ตาราง <?php echo (int)$d['table']; ?></p>
    <p class="tbl-title">ผลการเปลี่ยนแปลงความสามารถในการเขียนเรียงความด้าน<?php echo rp_esc($d['name']); ?></p>
    <table class="thesis">
      <thead><tr><th class="l">การเปลี่ยนแปลง</th>
        <th class="l"><?php echo rp_esc($meta['work1_label']); ?></th>
        <th class="l"><?php echo rp_esc($meta['work2_label']); ?></th></tr></thead>
      <tbody>
        <?php foreach ($d['indicators'] as $i => $id):
            $tr = null;
            foreach (($dpay['table_rows'] ?? []) as $row) { if (($row['indicator'] ?? '') === $id) $tr = $row; } ?>
        <tr>
          <td class="l"><?php echo ($i + 1) . '. ' . rp_esc($inds[$id]['name']); ?></td>
          <td class="l"><?php echo trim((string)($tr['cell1'] ?? '')) !== '' ? rp_esc($tr['cell1'])
              : '<span class="todo">[ยังไม่ได้วิเคราะห์]</span>'; ?></td>
          <td class="l"><?php echo trim((string)($tr['cell2'] ?? '')) !== '' ? rp_esc($tr['cell2'])
              : '<span class="todo">[ยังไม่ได้วิเคราะห์]</span>'; ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php foreach ($d['indicators'] as $id):
        $ind  = $inds[$id];
        $ijob = 'ind_' . str_replace('.', '_', $id);
        $ip   = $R[$ijob]['payload'] ?? [];
        $ex   = $ind['ex']; ?>
      <h4 class="sub2"><?php echo rp_esc($ind['sub'] . ' ' . $ind['name']); ?></h4>
      <?php echo c45para(c45p($R, $ijob, 'finding', 'ย่อหน้าเปิดหัวข้อ')
          . ' ดังตัวอย่าง (' . $ex[0] . ')–(' . $ex[1] . ')'); ?>
      <?php
      echo c45quote($ip['excerpt1'] ?? null, $meta['work1_label']);
      echo c45quote($ip['excerpt2'] ?? null, $meta['work2_label']);
      echo c45para('<strong>ตัวอย่าง (' . $ex[0] . ')</strong> ' . c45p($R, $ijob, 'analysis1', 'บทวิเคราะห์ตัวอย่างแรก'));
      echo c45para('<strong>ตัวอย่าง (' . $ex[1] . ')</strong> ' . c45p($R, $ijob, 'analysis2', 'บทวิเคราะห์ตัวอย่างที่สอง'));
      echo c45para(c45p($R, $ijob, 'synthesis', 'ข้อสรุปจากคู่ตัวอย่าง'));
      if (trim((string)($ip['caution'] ?? '')) !== '') echo c45para(rp_esc($ip['caution']));
      ?>
    <?php endforeach; ?>
  <?php endforeach; ?>
</div>

<div class="sheet">
  <div class="doc-head">
    <h1>บทที่ 5</h1>
    <div class="sub">สรุปผลการวิจัย อภิปรายผล และข้อเสนอแนะ</div>
  </div>

  <?php echo c45para(c45p($R, 'ch5_summary', 'opening', 'ย่อหน้าเปิดบทที่ 5')); ?>

  <h2 class="sec-title">สรุปผลการวิจัย</h2>
  <?php
  echo c45para('<strong>ตอนที่ 1</strong> ' . c45p($R, 'ch5_summary', 'part1', 'สรุปผลตอนที่ 1'));
  echo c45para('<strong>ตอนที่ 2</strong> ' . c45p($R, 'ch5_summary', 'part2_intro', 'ประโยคนำสรุปผลตอนที่ 2'));
  foreach ($doms as $dk => $d) {
      echo c45para('<strong>ด้าน' . rp_esc($d['name']) . '</strong> '
          . c45p($R, 'ch5_summary', 'part2_' . $dk, 'สรุปด้าน' . $d['name']));
  }
  ?>

  <h2 class="sec-title">อภิปรายผล <span>· ตรวจข้อความในร่างกับผลจริง</span></h2>
  <?php echo c45para('<span class="para-noindent">ส่วนอภิปรายผลเป็นส่วนที่ผู้วิจัยเขียนไว้แล้วบนฐานของการให้เหตุผล'
    . 'และงานวิจัยที่เกี่ยวข้อง ระบบจึงไม่เขียนแทน แต่ตรวจให้ว่าข้อความในร่างตรงกับผลจริงหรือไม่ ดังนี้</span>'); ?>
  <?php $chk = $R['ch5_discussion']['payload']['checks'] ?? []; ?>
  <?php if (!$chk): ?>
    <p class="para"><span class="todo">[ยังไม่ได้ตรวจ — กดวิเคราะห์หัวข้อ &quot;ประเด็นที่ต้องปรับในส่วนอภิปรายผล&quot;]</span></p>
  <?php else: ?>
  <table class="thesis">
    <thead><tr><th>ข้อ</th><th class="l">ข้อความในร่างอภิปรายผล</th><th>ผลจริง</th>
      <th class="l">หลักฐาน</th><th class="l">ข้อความที่ควรใช้แทน</th></tr></thead>
    <tbody>
      <?php foreach ($chk as $c): ?>
      <tr>
        <td class="c"><?php echo rp_esc($c['point']); ?></td>
        <td class="l"><?php echo rp_esc($c['claim']); ?></td>
        <td class="c"><?php echo rp_esc($c['verdict']); ?></td>
        <td class="l"><?php echo rp_esc($c['evidence']); ?></td>
        <td class="l"><?php echo $c['suggest'] !== '' ? rp_esc($c['suggest']) : '—'; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php foreach (($R['ch5_discussion']['payload']['new_points'] ?? []) as $np): ?>
    <h3 class="sub"><?php echo rp_esc($np['heading']); ?></h3>
    <?php echo c45para(rp_esc($np['text'])); ?>
  <?php endforeach; ?>

  <?php if (trim((string)($R['ch5_discussion']['payload']['limitation_note'] ?? '')) !== ''): ?>
    <h2 class="sec-title">ข้อจำกัดของการวิจัย <span>· ข้อความที่ควรเพิ่มจากผลจริง</span></h2>
    <?php echo c45para(rp_esc($R['ch5_discussion']['payload']['limitation_note'])); ?>
  <?php endif; ?>

  <h2 class="sec-title">ข้อเสนอแนะ</h2>
  <h3 class="sub">ข้อเสนอแนะสำหรับการนำผลการวิจัยไปใช้</h3>
  <?php
  $recs = $R['ch5_recommend']['payload']['items'] ?? [];
  if (!$recs) {
      echo c45para('<span class="todo">[ยังไม่มีข้อเสนอแนะ — ต้องบันทึก &quot;บันทึกหลังสอน&quot; '
        . 'ในหน้าวิเคราะห์บทที่ 4-5 ก่อน เพราะข้อเสนอแนะส่วนนี้ต้องเขียนจากปัญหาที่พบจริง]</span>');
  } else {
      foreach ($recs as $it) echo c45para(rp_esc($it['text']));
  }
  if (trim((string)($R['ch5_recommend']['payload']['institution'] ?? '')) !== '') {
      echo c45para(rp_esc($R['ch5_recommend']['payload']['institution']));
  }
  ?>
  <h3 class="sub">ข้อเสนอแนะสำหรับการทำวิจัยครั้งต่อไป</h3>
  <?php
  $fut = $R['ch5_recommend']['payload']['future'] ?? [];
  if (!$fut) {
      echo c45para('<span class="todo">[ยังไม่ได้วิเคราะห์หัวข้อข้อเสนอแนะ]</span>');
  } else {
      foreach ($fut as $f) echo c45para(rp_esc($f['text']));
  }
  ?>
</div>

<div class="sheet no-print">
  <div class="doc-head"><h1>ภาคผนวกของร่าง</h1><div class="sub">ข้อมูลประกอบที่ไม่ต้องพิมพ์ลงวิทยานิพนธ์</div></div>
  <h2 class="sec-title">ความเที่ยงระหว่างผู้ประเมิน (ICC)</h2>
  <?php if (!$quant['interrater']): ?>
    <p class="para">ยังคำนวณไม่ได้ — ต้องมีผู้ประเมินตั้งแต่ 2 คนขึ้นไปให้คะแนนผลงานชุดเดียวกัน</p>
  <?php else: ?>
  <p class="para">ใช้ ICC แบบสองทางผสม ความสอดคล้องสัมบูรณ์ (two-way mixed effects, absolute agreement) เป็นค่าหลักในการสรุปผล
    ตามเกณฑ์แปลผลของ Koo &amp; Li (2016) — Pearson r แสดงประกอบเป็นค่าความสัมพันธ์รายคู่เท่านั้น</p>
  <table class="thesis">
    <thead><tr><th class="l">รอบ</th><th>ผู้ประเมิน</th><th>n</th><th>ICC(3,1)</th>
      <th>ICC(3,k)</th><th>p</th><th>แปลผล (ยึดตาม ICC)</th><th class="l">Pearson r (ประกอบ)</th></tr></thead>
    <tbody>
      <?php foreach ($quant['interrater'] as $ir): ?>
      <tr>
        <td class="l"><?php echo rp_esc($ir['label']); ?></td>
        <td class="c"><?php echo (int)$ir['k']; ?></td>
        <td class="c"><?php echo (int)$ir['n']; ?></td>
        <td class="c"><?php echo ch45_fmt_r($ir['icc']['icc1']); ?></td>
        <td class="c"><strong><?php echo ch45_fmt_r($ir['icc']['iccK']); ?></strong></td>
        <td class="c"><?php echo ch45_fmt_p($ir['icc']['p']); ?></td>
        <td class="c"><?php echo rp_esc($ir['icc_label']); ?></td>
        <td class="l"><?php
          echo rp_esc(implode(', ', array_map(function ($p) { return 'r = ' . ch45_fmt_r($p['r']); }, $ir['pearson']))); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <h2 class="sec-title">ผลทดสอบการแจกแจงปกติของคะแนนผลต่าง (Shapiro-Wilk)</h2>
  <table class="thesis">
    <thead><tr><th class="l">ระดับ</th><th>n</th><th>W</th><th>p</th><th class="l">แปลผล</th></tr></thead>
    <tbody>
      <?php foreach ($quant['normality'] as $k => $nrm):
        $label = ($k === 'overall') ? 'ภาพรวม' : ('ด้าน' . ($doms[$k]['name'] ?? $k)); ?>
      <tr>
        <td class="l"><?php echo rp_esc($label); ?></td>
        <td class="c"><?php echo (int)$nrm['n']; ?></td>
        <td class="c"><?php echo ch45_fmt_r($nrm['W']); ?></td>
        <td class="c"><?php echo ch45_fmt_p($nrm['p']); ?></td>
        <td class="l"><?php echo $nrm['W'] === null ? rp_esc($nrm['error'] ?? '—')
            : ($nrm['normal'] ? 'ไม่แตกต่างจากการแจกแจงปกติ' : 'แตกต่างจากการแจกแจงปกติ'); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <h2 class="sec-title">ข้อมูลกลไกการเขียนที่นับจากตัวบทจริง</h2>
  <table class="thesis">
    <thead><tr><th class="l">รอบผลงาน</th><th>จำนวนฉบับ</th><th>สะกดผิดเฉลี่ย/ชิ้น</th>
      <th>ผลงานที่สะกดผิด ≥ 3 แห่ง</th><th>ความยาวเฉลี่ย (คำ)</th></tr></thead>
    <tbody>
      <?php foreach (['work1', 'work2'] as $slot): $w = $mech[$slot]; ?>
      <tr>
        <td class="l"><?php echo rp_esc($w['label']); ?></td>
        <td class="c"><?php echo (int)$w['pieces']; ?></td>
        <td class="c"><?php echo rp_num($w['spell_mean']); ?></td>
        <td class="c"><?php echo (int)$w['spell_ge3']; ?></td>
        <td class="c"><?php echo rp_num($w['word_mean'], 0); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p class="tbl-note"><?php echo rp_esc($mech['note']); ?></p>
</div>
</body>
</html>
