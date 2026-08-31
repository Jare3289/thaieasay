<?php
/**
 * student_report.php — หน้าเว็บ "รายงานผลการเรียนรู้รายบุคคล ฉบับเต็ม"
 * ---------------------------------------------------------------------------
 * รวมทุกอย่างที่นักเรียนคนหนึ่งทำไว้ในระบบไว้ในหน้าเดียว พร้อมบทวิเคราะห์รายบุคคล
 * ใช้ส่วนเนื้อหาชุดเดียวกับเอกสารสำหรับพิมพ์ (report_sections.php)
 * ที่เห็นบนหน้าเว็บจึงตรงกับที่พิมพ์ออกกระดาษเสมอ
 *
 * ครู/ผู้เชี่ยวชาญ: เลือกดูได้ทุกคน · นักเรียน: เห็นของตนเองเท่านั้น
 */

$page_title = 'รายงานผลการเรียนรู้รายบุคคล - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login();
require_once 'report_data.php';
require_once 'report_analysis.php';
require_once 'report_sections.php';

$me      = $_SESSION['user'];
$role    = $me['role'];
$isStaff = in_array($role, ['teacher', 'expert'], true);

$fGroup     = isset($_GET['group'])      ? trim($_GET['group'])      : '';
$fClassroom = isset($_GET['classroom'])  ? trim($_GET['classroom'])  : '';
$fStudent   = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';

// นักเรียนดูได้เฉพาะของตนเอง และไม่ต้องมีตัวกรองใด ๆ
if (!$isStaff) {
    $fStudent   = $me['id'];
    $fGroup     = '';
    $fClassroom = '';
}

$data = report_dataset($pdo, ['group' => $fGroup, 'classroom' => $fClassroom]);

// รายชื่อในช่องเลือกด้านบน: เอาเฉพาะ "กลุ่มตัวอย่าง" ไม่นำนักเรียนกลุ่มทดลองมาปนในรายการ
// (ค่าเฉลี่ยของชั้นเรียนยังคิดจากขอบเขตเดิมตามตัวกรอง group/classroom เหมือนเดิม)
$rpSampleGroup = 'กลุ่มตัวอย่าง';
$pickStudents  = [];
foreach ($data['students'] as $sid => $stu) {
    if (trim((string)($stu['student_group'] ?? '')) === $rpSampleGroup) $pickStudents[$sid] = $stu;
}
// ยังไม่มีใครถูกจัดเป็นกลุ่มตัวอย่างเลย → แสดงรายชื่อตามขอบเขตเดิม กันไม่ให้ช่องเลือกว่างเปล่า
if (!$pickStudents) $pickStudents = $data['students'];

// ยังไม่ได้เลือกนักเรียน (ครูเปิดหน้าครั้งแรก) → เลือกคนแรกในรายการให้
if ($fStudent === '' && $pickStudents) {
    $fStudent = (string)array_key_first($pickStudents);
}
$sum = ($fStudent !== '' && isset($data['students'][$fStudent]))
    ? report_student_summary($data, $fStudent) : null;

$full = $ins = [];
if ($sum) {
    $fullAll = report_full_data($pdo, [$fStudent]);
    $full    = $fullAll[$fStudent] ?? [];
    $ins     = report_student_insights($sum, $full);
}

// ลิงก์ไปหน้าเอกสารสำหรับพิมพ์ของคนเดียวกัน
$printQs = http_build_query(array_filter([
    'student_id' => $fStudent,
    'group'      => $fGroup,
    'classroom'  => $fClassroom,
], function ($v) { return $v !== '' && $v !== null; }));

require_once 'header.php';
?>

<div class="text-start">

  <!-- แถบเลือกนักเรียน + ปุ่มพิมพ์ -->
  <div class="content-card mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h5 class="fw-bold mb-1" style="color:var(--primary-navy)">
          <i class="bi bi-person-vcard me-2"></i>รายงานผลการเรียนรู้รายบุคคล (ฉบับเต็ม)
        </h5>
        <p class="text-muted small mb-0">
          รวมผลสัมฤทธิ์ ผลงาน สถิติ บันทึกสะท้อนคิด และข้อเสนอแนะทั้งหมดของนักเรียนคนเดียว พร้อมบทวิเคราะห์รายบุคคล
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <?php if ($isStaff): ?>
        <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
          <?php if ($fGroup !== ''): ?><input type="hidden" name="group" value="<?php echo rp_esc($fGroup); ?>"><?php endif; ?>
          <?php if ($fClassroom !== ''): ?><input type="hidden" name="classroom" value="<?php echo rp_esc($fClassroom); ?>"><?php endif; ?>
          <select class="form-select form-select-sm rounded-pill px-3" name="student_id"
                  style="min-width:280px;" onchange="this.form.submit()"
                  data-search-select data-search-placeholder="พิมพ์ค้นหาด้วยรหัส หรือ ชื่อนักเรียน...">
            <?php foreach ($pickStudents as $sid => $stu): ?>
            <option value="<?php echo rp_esc($sid); ?>"<?php echo ($sid === $fStudent) ? ' selected' : ''; ?>>
              <?php echo rp_esc($sid . ' · ' . $stu['student_name']
                    . ($stu['classroom'] !== '' ? ' (ห้อง ' . $stu['classroom'] . ')' : '')); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <noscript><button class="btn btn-sm btn-primary rounded-pill px-3">แสดง</button></noscript>
        </form>
        <?php endif; ?>
        <a class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm" target="_blank" rel="noopener"
           href="student_report_print.php<?php echo $printQs ? '?' . rp_esc($printQs) : ''; ?>">
          <i class="bi bi-printer me-1"></i>พิมพ์ / บันทึกเป็น PDF
        </a>
        <?php if ($isStaff): ?>
        <a class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold" target="_blank" rel="noopener"
           href="class_report_print.php<?php echo ($fGroup !== '' || $fClassroom !== '')
                 ? '?' . rp_esc(http_build_query(array_filter(['group' => $fGroup, 'classroom' => $fClassroom],
                     function ($v) { return $v !== '' && $v !== null; }))) : ''; ?>">
          <i class="bi bi-clipboard-data me-1"></i>รายงานภาพรวมชั้นเรียน
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php if (!$sum): ?>
  <div class="content-card text-center text-muted py-5">
    <i class="bi bi-inbox fs-1 d-block mb-2"></i>ยังไม่มีข้อมูลนักเรียนให้แสดงรายงาน
  </div>
<?php else: ?>

  <!-- สารบัญลัด -->
  <div class="content-card mb-3 py-2">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span class="text-muted small fw-bold me-1">ไปยังส่วน:</span>
      <?php
      $toc = [
          'sec-achievement' => 'ผลสัมฤทธิ์',
          'sec-analysis'    => 'บทวิเคราะห์',
          'sec-criteria'    => 'สถิติรายเกณฑ์',
          'sec-eval'        => 'คะแนนจากผู้ประเมินทุกฝ่าย',
          'sec-works'       => 'ผลงาน',
          'sec-essays'      => 'เรียงความฉบับเต็ม',
          'sec-ai'          => 'ผลตรวจ AI',
          'sec-reflect'     => 'บันทึกสะท้อนคิด',
          'sec-peer'        => 'ประเมินกับเพื่อน',
      ];
      foreach ($toc as $id => $label): ?>
      <a class="btn btn-sm btn-outline-primary rounded-pill px-3" href="#<?php echo rp_esc($id); ?>">
        <?php echo rp_esc($label); ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- เนื้อหารายงาน — สไตล์ถูกจำกัดไว้ในกล่อง .rp-doc เท่านั้น ไม่กระทบหน้าอื่นของระบบ -->
  <div class="content-card">
    <div class="rp-doc">
      <?php
      rs_doc_head($sum['student']);
      rs_achievement($sum, '1');
      rs_analysis($ins, '2');
      rs_criteria($sum, '3');
      rs_eval_detail($full, '4');
      rs_works($sum, '5');
      rs_essays($full, '6');
      rs_ai($full, '7');
      rs_reflection($full, '8');
      rs_peer($full, '9');
      rs_signature($sum);
      ?>
    </div>
  </div>

<?php endif; ?>
</div>

<?php rp_styles('.rp-doc'); ?>
<style>
  /* กล่องรายงานในหน้าเว็บ: พื้นหลังขาวเหมือนกระดาษ และเลื่อนหัวข้อไม่ให้ไปซ่อนใต้แถบบน */
  .rp-doc { background: #fff; }
  .rp-doc .sec-title { scroll-margin-top: 90px; }
  .rp-doc table { display: table; }
  /* ตารางกว้าง ๆ บนจอแคบให้เลื่อนแนวนอนได้ แทนที่จะบีบตัวอักษรจนอ่านไม่ออก */
  @media (max-width: 767.98px) {
    .rp-doc { overflow-x: auto; }
  }
</style>

<?php require_once 'footer.php'; ?>
