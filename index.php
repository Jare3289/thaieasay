<?php
$page_title = 'เมนูหลัก - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login(); // ต้องล็อกอินก่อนเข้าหน้านี้

require_once 'header.php';
?>

<!-- หน้าเมนูหลักผู้ใช้ (Menu View) -->
<div id="view-menu">
  <!-- บอร์ดต้อนรับผู้เรียน/คุณครูแบบสุภาพเป็นทางการ -->
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white position-relative overflow-hidden text-start" style="border-left: 6px solid var(--accent-teal) !important;">
    <div class="row align-items-center">
      <div class="col-md-8 text-start col-12">
        <h2 class="fw-extrabold text-dark mb-1">สวัสดี, <span class="text-primary"><?php echo htmlspecialchars($sessionUser['name']); ?></span></h2>
        <p class="text-muted mb-0 small">ยินดีต้อนรับเข้าสู่ระบบประเมินความก้าวหน้าการเขียนเชิงวิชาการ</p>
      </div>
      <div class="col-md-4 text-end d-none d-md-block">
        <span class="fs-1">🏫</span>
      </div>
    </div>
  </div>

  <?php if ($sessionUser['role'] === 'student'): ?>
  <!-- แผงสำหรับนักเรียน (Student Menu) -->
  <div id="menuStudent">
    <div class="row g-4 mb-4">
      <!-- เช็คลิสต์ติดตามความก้าวหน้าตนเอง (Checklist Progress) -->
      <div class="col-md-4 col-sm-12">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
          <div class="card-header bg-primary text-white fw-bold rounded-top-4 py-3 text-start">
            📋 Status งานประเมินสะสมของฉัน
          </div>
          <div class="card-body py-4 text-start">
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
              <span class="small fw-semibold text-secondary">1. การประเมินตนเอง</span>
              <span id="badgeSelfStatus" class="badge rounded-pill bg-secondary">-</span>
            </div>
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
              <span class="small fw-semibold text-secondary">2. การได้รับเพื่อนประเมิน</span>
              <span id="badgePeerStatus" class="badge rounded-pill bg-secondary">-</span>
            </div>
            <div class="d-flex align-items-center justify-content-between pb-1">
              <span class="small fw-semibold text-secondary">3. การได้รับครูประเมิน</span>
              <span id="badgeTeacherStatus" class="badge rounded-pill bg-secondary">-</span>
            </div>
          </div>
        </div>
      </div>

      <!-- เมนูปุ่มกดต่าง ๆ -->
      <div class="col-md-8 col-sm-12">
        <div class="row g-3 h-100">
          <div class="col-md-4 col-sm-12">
            <a href="evaluation.php?mode=self" class="btn menu-card w-100 text-decoration-none">
              <div>
                <div class="fs-2 mb-3">🙋‍♂️</div>
                <h5 class="fw-bold text-dark mb-2">ประเมินตนเอง</h5>
                <p class="text-muted small font-light" style="font-size: 0.78rem;">วิเคราะห์จุดแข็ง จุดที่ควรปรับปรุงเพื่อประเมินความเข้าใจงานเขียนของคุณ</p>
              </div>
              <span class="text-primary fw-bold text-start small mt-3">เริ่มต้นเลย &rarr;</span>
            </a>
          </div>

          <div class="col-md-4 col-sm-12">
            <a href="evaluation.php?mode=peer" class="btn menu-card w-100 text-decoration-none">
              <div>
                <div class="fs-2 mb-3">👥</div>
                <h5 class="fw-bold text-dark mb-2">เพื่อนช่วยประเมิน</h5>
                <p class="text-muted small font-light" style="font-size: 0.78rem;">สวมบทบาทเป็นกระจกสะท้อน ช่วยรีวิวให้คะแนนเรียงความของเพื่อนร่วมห้อง</p>
              </div>
              <span class="text-primary fw-bold text-start small mt-3">เลือกเพื่อน &rarr;</span>
            </a>
          </div>

          <div class="col-md-4 col-sm-12">
            <a href="reflection_tools.php" class="btn menu-card w-100 text-decoration-none">
              <div>
                <div class="fs-2 mb-3">🎨</div>
                <h5 class="fw-bold text-dark mb-2">สะท้อนคิด & ประเมินสะสม</h5>
                <p class="text-muted small font-light" style="font-size: 0.78rem;">บันทึกปัญหา ยืนยันตรวจสอบตนเอง และประเมินเพื่อนแบบสะท้อนกลับ</p>
              </div>
              <span class="text-primary fw-bold text-start small mt-3">เปิดเครื่องมือ &rarr;</span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- รายงานผลคะแนนและบทวิเคราะห์คุณภาพ -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-start">
          <div class="row align-items-center">
            <div class="col-md-9 col-sm-12">
              <h5 class="fw-bold text-dark mb-1">📊 บทวิเคราะห์สะสมและสถิติรอบทิศ (360° Report)</h5>
              <p class="text-muted small mb-0 font-light">แสดงคะแนนสรุปจากทุกมิติการประเมินเพื่อรับคำแนะนำที่ประมวลผลเชิงวิชาการ</p>
            </div>
            <div class="col-md-3 col-sm-12 text-end mt-3 mt-md-0">
              <a href="dashboard.php" id="btnOpenReport" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold w-100 shadow text-decoration-none">
                เปิดดูรายงานของฉัน
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php else: ?>
  <!-- แผงสำหรับคุณครู (Teacher Menu) -->
  <div id="menuTeacher" class="row g-4">
    <div class="col-md-4 col-sm-12">
      <a href="evaluation.php?mode=teacher" class="btn menu-card w-100 py-5 text-decoration-none">
        <div class="text-center w-100">
          <div class="fs-1 mb-3">👩‍🏫</div>
          <h4 class="fw-bold text-dark mb-2">ประเมินและให้คะแนนผลงาน</h4>
          <p class="text-muted small font-light">เลือกรายชื่อนักเรียนและให้คะแนนการเขียนแบบไม่มีอคติเกณฑ์คะแนนกวนใจ</p>
        </div>
        <span class="text-primary text-center fw-bold small mt-3 d-block">เปิดหน้าฟอร์มประเมิน &rarr;</span>
      </a>
    </div>
    <div class="col-md-4 col-sm-12">
      <a href="dashboard.php" class="btn menu-card w-100 py-5 text-decoration-none">
        <div class="text-center w-100">
          <div class="fs-1 mb-3">📊</div>
          <h4 class="fw-bold text-dark mb-2">แดชบอร์ดวิจัยชั้นเรียน</h4>
          <p class="text-muted small font-light">วิเคราะห์คะแนนนักเรียนรายบุคคล พร้อมประมวลข้อมูลรายงานเพื่อทำวิจัยในชั้นเรียน</p>
        </div>
        <span class="text-primary text-center fw-bold small mt-3 d-block">เปิดระบบแดชบอร์ด &rarr;</span>
      </a>
    </div>
    <div class="col-md-4 col-sm-12">
      <a href="reflection_tools.php" class="btn menu-card w-100 py-5 text-decoration-none">
        <div class="text-center w-100">
          <div class="fs-1 mb-3">🎨</div>
          <h4 class="fw-bold text-dark mb-2">รายงานการสะท้อนคิด</h4>
          <p class="text-muted small font-light">ตรวจสอบแบบบันทึกปัญหาการเขียน เช็คลิสต์ และสะท้อนคิดการเรียนรู้สะสมรายบุคคล</p>
        </div>
        <span class="text-primary text-center fw-bold small mt-3 d-block">เปิดเครื่องมือสะท้อนคิด &rarr;</span>
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
  // โหลดสถานะการประเมิน 3 มิติ เพื่อมาโชว์ใน Checklist นักเรียน
  async function checkStudentProgress() {
    if (!currentUser || currentUser.role !== 'student') return;
    
    const badgeSelf = document.getElementById('badgeSelfStatus');
    const badgePeer = document.getElementById('badgePeerStatus');
    const badgeTeacher = document.getElementById('badgeTeacherStatus');
    
    if (!badgeSelf || !badgePeer || !badgeTeacher) return;
    
    try {
      const response = await fetch(`api.php?action=get_student_scores&studentId=${currentUser.id}`);
      const res = await response.json();
      
      let hasSelf = false;
      let hasPeer = false;
      let hasTeacher = false;
      
      if (res.success && res.data) {
        res.data.forEach(item => {
          if (item.evaluatorType === 'ตนเองประเมิน') hasSelf = true;
          if (item.evaluatorType === 'เพื่อนประเมิน') hasPeer = true;
          if (item.evaluatorType === 'ครูประเมิน') hasTeacher = true;
        });
      }
      
      badgeSelf.className = hasSelf ? "badge rounded-pill bg-success text-white px-2.5" : "badge rounded-pill bg-secondary";
      badgeSelf.textContent = hasSelf ? "✓ ประเมินแล้ว" : "ยังไม่เริ่ม";
      
      badgePeer.className = hasPeer ? "badge rounded-pill bg-success text-white px-2.5" : "badge rounded-pill bg-secondary";
      badgePeer.textContent = hasPeer ? "✓ ประเมินแล้ว" : "รอดำเนินการ";
      
      badgeTeacher.className = hasTeacher ? "badge rounded-pill bg-success text-white px-2.5" : "badge rounded-pill bg-secondary";
      badgeTeacher.textContent = hasTeacher ? "✓ ประเมินแล้ว" : "รอดำเนินการ";
      
    } catch (err) {
      console.error("Error loading checklist progress:", err);
    }
  }

  // เรียกใช้ตรวจสอบความคืบหน้าเมื่อโหลดหน้าสำเร็จ
  document.addEventListener('DOMContentLoaded', () => {
    checkStudentProgress();
  });
</script>

<?php
require_once 'footer.php';
?>
