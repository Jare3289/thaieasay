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

  <!-- แผงสำหรับนักเรียน (Student Menu) -->
  <div id="menuStudent">
    
    <!-- 1. เช็คลิสต์ติดตามความก้าวหน้าตนเอง (Checklist Progress Dashboard) - จัดวางแนวนอนเพิ่มความกว้างและสัดส่วนที่ชัดเจน -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden text-start">
      <div class="card-header bg-primary bg-opacity-10 text-primary fw-bold py-3 px-4 border-0 d-flex align-items-center justify-content-between">
        <span class="fs-6"><i class="bi bi-calendar-check-fill me-2"></i>ความคืบหน้าการส่งงานประเมินรอบด้านของฉัน (My Evaluation Status)</span>
        <span class="badge bg-primary rounded-pill px-3 py-1 font-mono fs-8">360° Tracker</span>
      </div>
      <div class="card-body p-4">
        <div class="row g-3">
          <div class="col-md-4 col-12">
            <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between bg-light bg-opacity-50">
              <div class="d-flex align-items-center gap-3">
                <div class="fs-3">🙋‍♂️</div>
                <div>
                  <div class="fw-bold text-dark small" style="font-size:0.88rem;">1. ประเมินตนเอง</div>
                  <div class="text-muted small" style="font-size:0.75rem;">Self-Evaluation</div>
                </div>
              </div>
              <span id="badgeSelfStatus" class="badge rounded-pill bg-secondary">-</span>
            </div>
          </div>
          <div class="col-md-4 col-12">
            <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between bg-light bg-opacity-50">
              <div class="d-flex align-items-center gap-3">
                <div class="fs-3">👥</div>
                <div>
                  <div class="fw-bold text-dark small" style="font-size:0.88rem;">2. เพื่อนประเมิน</div>
                  <div class="text-muted small" style="font-size:0.75rem;">Peer Assessment</div>
                </div>
              </div>
              <span id="badgePeerStatus" class="badge rounded-pill bg-secondary">-</span>
            </div>
          </div>
          <div class="col-md-4 col-12">
            <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between bg-light bg-opacity-50">
              <div class="d-flex align-items-center gap-3">
                <div class="fs-3">👩‍🏫</div>
                <div>
                  <div class="fw-bold text-dark small" style="font-size:0.88rem;">3. ครูประเมินผล</div>
                  <div class="text-muted small" style="font-size:0.75rem;">Teacher Grading</div>
                </div>
              </div>
              <span id="badgeTeacherStatus" class="badge rounded-pill bg-secondary">-</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. เมนูปุ่มกดต่าง ๆ ปรับเป็น 2x2 Grid แบบขนาดใหญ่ (col-md-6 col-12) เพื่อให้อ่านง่ายไม่บีบตัวหนังสือบนมือถือ -->
    <div class="row g-4 mb-4 text-start">
      
      <!-- บันทึกเรียงความ (แนะนำให้ทำเป็นอย่างแรก) -->
      <div class="col-md-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white menu-action-card" style="border-top: 4px solid #0d7377 !important; transition: transform 0.2s, box-shadow 0.2s;">
          <div class="card-body p-4 d-flex align-items-start gap-3">
            <div class="flex-shrink-0 bg-success bg-opacity-10 text-success p-3 rounded-3 fs-2 line-height-1">
              ✍️
            </div>
            <div class="flex-grow-1">
              <h5 class="fw-bold text-dark mb-1">บันทึกเรียงความของฉัน</h5>
              <p class="text-muted small mb-3" style="line-height:1.5; font-size:0.82rem;">
                พิมพ์เรียงความที่คุณเขียนลงบนกระดาษเข้ามาในระบบ เพื่อจัดเก็บข้อมูลดิบและนำไปใช้วิเคราะห์ประเมินผลในรอบก่อนเรียนและภารงานในหน่วยการเรียน
              </p>
              <a href="essay_writer.php" class="btn btn-sm btn-success rounded-pill px-4 fw-bold shadow-sm">
                พิมพ์บันทึกเรียงความ &rarr;
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- ประเมินตนเอง -->
      <div class="col-md-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white menu-action-card" style="border-top: 4px solid var(--bs-primary) !important; transition: transform 0.2s, box-shadow 0.2s;">
          <div class="card-body p-4 d-flex align-items-start gap-3">
            <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary p-3 rounded-3 fs-2 line-height-1">
              🙋‍♂️
            </div>
            <div class="flex-grow-1">
              <h5 class="fw-bold text-dark mb-1">ประเมินผลงานตนเอง</h5>
              <p class="text-muted small mb-3" style="line-height:1.5; font-size:0.82rem;">
                ทบทวนและให้คะแนนผลงานการเขียนของตนเองด้วยเกณฑ์รูบริกคุณภาพ เพื่อสะท้อนความตระหนักรู้ จุดเด่น และจุดที่ควรพัฒนาของนักเรียนเอง
              </p>
              <a href="evaluation.php?mode=self" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">
                เริ่มประเมินตนเอง &rarr;
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- เพื่อนช่วยประเมิน -->
      <div class="col-md-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white menu-action-card" style="border-top: 4px solid var(--bs-info) !important; transition: transform 0.2s, box-shadow 0.2s;">
          <div class="card-body p-4 d-flex align-items-start gap-3">
            <div class="flex-shrink-0 bg-info bg-opacity-10 text-info p-3 rounded-3 fs-2 line-height-1">
              👥
            </div>
            <div class="flex-grow-1">
              <h5 class="fw-bold text-dark mb-1">ประเมินผลงานของเพื่อน</h5>
              <p class="text-muted small mb-3" style="line-height:1.5; font-size:0.82rem;">
                สวมบทบาทเป็นผู้ประเมินเพื่อช่วยเหลือเพื่อนร่วมชั้นเรียน ให้คะแนนวิจารณ์ผลงานพร้อมส่งข้อเสนอแนะเชิงสร้างสรรค์และให้กำลังใจเพื่อน
              </p>
              <a href="evaluation.php?mode=peer" class="btn btn-sm btn-info text-white rounded-pill px-4 fw-bold shadow-sm">
                เลือกเพื่อนประเมิน &rarr;
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- สะท้อนคิด & ประเมินสะสม -->
      <div class="col-md-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white menu-action-card" style="border-top: 4px solid var(--bs-warning) !important; transition: transform 0.2s, box-shadow 0.2s;">
          <div class="card-body p-4 d-flex align-items-start gap-3">
            <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning p-3 rounded-3 fs-2 line-height-1">
              🎨
            </div>
            <div class="flex-grow-1">
              <h5 class="fw-bold text-dark mb-1">บันทึกสะท้อนคิดการเรียนรู้</h5>
              <p class="text-muted small mb-3" style="line-height:1.5; font-size:0.82rem;">
                บันทึกอุปสรรคและปัญหาการเขียนเฉพาะบุคคล (POA) ยืนยันเช็คลิสต์ประเมินตนเอง และสะท้อนมุมมองการนำฟีดแบ็กไปปรับใช้พัฒนางานเขียน
              </p>
              <a href="reflection_tools.php" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold shadow-sm">
                เปิดเครื่องมือสะท้อนคิด &rarr;
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- 3. รายงานผลคะแนนและบทวิเคราะห์คุณภาพ -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white text-start">
          <div class="row align-items-center">
            <div class="col-md-9 col-sm-12">
              <h5 class="fw-bold text-dark mb-1"><i class="bi bi-pie-chart text-primary me-2"></i>รายงานผลการประเมินสะสมรอบทิศ (360° Student Report)</h5>
              <p class="text-muted small mb-0 font-light" style="font-size: 0.85rem;">คลิกเพื่อดูสรุปคะแนนประเมินร่วมกันระหว่างนักเรียน เพื่อนร่วมชั้น และคุณครูผู้สอน พร้อมวิเคราะห์พัฒนาการรายบุคคล</p>
            </div>
            <div class="col-md-3 col-sm-12 text-end mt-3 mt-md-0">
              <a href="dashboard.php" id="btnOpenReport" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold w-100 shadow text-decoration-none">
                เปิดรายงานของฉัน
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <style>
  .menu-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
  }
  .line-height-1 {
    line-height: 1;
  }
  </style>
  <?php elseif ($sessionUser['role'] === 'expert'): ?>
  <!-- แผงสำหรับผู้เชี่ยวชาญ (Expert Menu) -->
  <div id="menuExpert" class="row g-4 justify-content-center">
    <div class="col-md-5 col-sm-12">
      <a href="evaluation.php?mode=expert" class="btn menu-card w-100 py-5 text-decoration-none">
        <div class="text-center w-100">
          <div class="fs-1 mb-3">🎓</div>
          <h4 class="fw-bold text-dark mb-2">ประเมินและให้คะแนนผลงาน (ผู้เชี่ยวชาญ)</h4>
          <p class="text-muted small font-light">ทำการประเมินและให้คะแนนเรียงความของนักเรียนตามเกณฑ์ Inter-rater Reliability (ผู้ประเมินร่วม)</p>
        </div>
        <span class="text-primary text-center fw-bold small mt-3 d-block">เปิดหน้าฟอร์มประเมิน &rarr;</span>
      </a>
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
