<?php
$page_title = 'เมนูหลัก - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login(); // ต้องล็อกอินก่อนเข้าหน้านี้

require_once 'header.php';
?>

<!-- หน้าเมนูหลักผู้ใช้ (Menu View) -->
<div id="view-menu">
  <!-- บอร์ดต้อนรับผู้เรียน/คุณครูแบบสุภาพเป็นทางการ -->
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white position-relative overflow-hidden text-start" style="border-left: 6px solid var(--accent-blue) !important;">
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
                พิมพ์เรียงความที่คุณเขียนลงบนกระดาษเข้ามาในระบบ เพื่อจัดเก็บข้อมูลดิบและนำไปใช้วิเคราะห์ประเมินผลในรอบก่อนเรียนและภาระงานในหน่วยการเรียน
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
                จับคู่กับเพื่อนร่วมห้องด้วยตนเอง (ส่งคำขอ–กดรับ แล้วจับคู่ไป-กลับอัตโนมัติ) จากนั้นให้คะแนนวิจารณ์ผลงานพร้อมส่งข้อเสนอแนะเชิงสร้างสรรค์และให้กำลังใจเพื่อน
              </p>
              <div class="d-flex flex-wrap gap-2">
                <a href="peer_matching.php" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">
                  🤝 จับคู่เพื่อน &rarr;
                </a>
                <a href="evaluation.php?mode=peer" class="btn btn-sm btn-info text-white rounded-pill px-4 fw-bold shadow-sm">
                  เริ่มประเมินเพื่อน &rarr;
                </a>
              </div>
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
    <div class="col-md-5 col-sm-12">
      <a href="essay_viewer.php" class="btn menu-card w-100 py-5 text-decoration-none">
        <div class="text-center w-100">
          <div class="fs-1 mb-3">📝</div>
          <h4 class="fw-bold text-dark mb-2">เรียงความนักเรียน (Essay Viewer)</h4>
          <p class="text-muted small font-light">อ่านเนื้อหาเรียงความของนักเรียนทุกคน แยกกลุ่มทดลอง/กลุ่มตัวอย่าง ทุกห้องเรียน และทุกรอบการประเมิน</p>
        </div>
        <span class="text-primary text-center fw-bold small mt-3 d-block">เปิดหน้าดูเรียงความ &rarr;</span>
      </a>
    </div>
  </div>
  <?php else: ?>
  <!-- ============================ แดชบอร์ดคุณครู (Teacher Dashboard) ============================ -->
  <div id="teacherDashboard">

    <!-- 1) การ์ดสถิติภาพรวม -->
    <div class="row g-3 mb-3">
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">👥</span> นักเรียนทั้งหมด</div>
            <a href="manage_students.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dTotal">-</div>
          <div class="stat-foot" id="dGroup">ทุกกลุ่มการวิจัย</div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">✍️</span> เรียงความส่งแล้ว</div>
            <a href="essay_viewer.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dEssays">-</div>
          <div class="stat-foot">รวมทุกรอบ (6 ชิ้น/คน)</div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">✅</span> ส่งครบทุกชิ้น</div>
            <a href="submission_report.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dComplete">-</div>
          <div class="stat-foot" id="dCompleteFoot">จากทั้งหมด - คน</div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">📊</span> อัตราการส่งงาน</div>
            <a href="submission_report.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dRate">-</div>
          <div class="stat-foot">เฉลี่ยทั้งชั้น</div>
        </div>
      </div>
    </div>

    <!-- 2) กราฟการส่งงานรายรอบ + วงแหวนความคืบหน้า -->
    <div class="row g-3 mb-3">
      <div class="col-lg-8 col-12">
        <div class="content-card h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h5 class="fw-bold mb-0" style="color:var(--primary-navy)">ภาพรวมการส่งงานแต่ละรอบ</h5>
              <span class="text-muted small">จำนวนนักเรียนที่ส่งงานในแต่ละรอบ</span>
            </div>
            <a href="submission_report.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">ดูรายงาน</a>
          </div>
          <div style="height:260px;position:relative"><canvas id="chartRounds"></canvas></div>
        </div>
      </div>
      <div class="col-lg-4 col-12">
        <div class="content-card h-100 d-flex flex-column">
          <h5 class="fw-bold mb-1" style="color:var(--primary-navy)">ความคืบหน้าการส่งงาน</h5>
          <span class="text-muted small mb-2">สัดส่วนนักเรียนที่ส่งงานครบทุกชิ้น</span>
          <div class="flex-grow-1 d-flex align-items-center justify-content-center">
            <div style="position:relative;width:210px;height:210px">
              <canvas id="chartProgress"></canvas>
              <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                <div class="fw-extrabold" style="font-size:2.2rem;color:var(--primary-navy);line-height:1" id="progressPct">-</div>
                <div class="text-muted small">ส่งครบทุกชิ้น</div>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-center gap-3 small mt-2">
            <span><i class="bi bi-circle-fill" style="color:var(--accent-blue)"></i> ส่งครบ</span>
            <span><i class="bi bi-circle-fill" style="color:#e2e8f0"></i> ยังไม่ครบ</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 3) เมนูด่วน + ทางลัดงานวิจัย -->
    <div class="row g-3">
      <div class="col-lg-8 col-12">
        <div class="content-card h-100">
          <h5 class="fw-bold mb-3" style="color:var(--primary-navy)">เมนูด่วน</h5>
          <div class="row g-3">
            <?php
              $quick = [
                ['evaluation.php?mode=teacher', '👩‍🏫', 'ประเมินให้คะแนน', 'ให้คะแนนเรียงความแบบไม่มีอคติ'],
                ['submission_report.php',       '🧾', 'รายงานการส่งงาน', 'ติดตามสถานะการส่งงานรายคน'],
                ['manage_students.php',         '👨‍👩‍👧‍👦', 'จัดการนักเรียน & จับคู่', 'รายชื่อ กลุ่ม และคู่ประเมิน'],
                ['reflection_tools.php',        '💡', 'รายงานสะท้อนคิด', 'ปัญหาการเขียน/เช็คลิสต์/สะท้อนคิด'],
                ['essay_viewer.php',            '📝', 'เรียงความนักเรียน', 'อ่านงานเขียนทุกคนทุกรอบ'],
                ['research_analysis.php',       '🔬', 'วิเคราะห์สถิติวิจัย', 'ICC, Paired t-test, เชิงคุณภาพ'],
              ];
              foreach ($quick as $q):
            ?>
            <div class="col-md-6 col-12">
              <a href="<?php echo $q[0]; ?>" class="quick-action text-decoration-none">
                <span class="quick-icon"><?php echo $q[1]; ?></span>
                <span class="quick-text">
                  <span class="quick-title"><?php echo $q[2]; ?></span>
                  <span class="quick-desc"><?php echo $q[3]; ?></span>
                </span>
                <i class="bi bi-chevron-right quick-arrow"></i>
              </a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="col-lg-4 col-12">
        <div class="content-card h-100" style="background:linear-gradient(160deg,var(--primary-navy) 0%,var(--accent-blue-dark) 100%);color:#fff;border:none">
          <h5 class="fw-bold mb-1 text-white">แดชบอร์ดวิจัยชั้นเรียน</h5>
          <p class="small mb-4" style="color:rgba(255,255,255,.7)">ดูพัฒนาการรายบุคคล เปรียบเทียบก่อน-หลังเรียน และประมวลผลข้อมูลเชิงลึกเพื่อทำวิจัยในชั้นเรียน</p>
          <div class="d-flex align-items-baseline gap-2 mb-1">
            <span class="fw-extrabold" style="font-size:2rem" id="dRate2">-</span>
            <span class="small" style="color:rgba(255,255,255,.7)">อัตราการส่งงานเฉลี่ย</span>
          </div>
          <div class="progress mb-4" style="height:8px;background:rgba(255,255,255,.18)">
            <div class="progress-bar" id="dRateBar" style="width:0%;background:#fff"></div>
          </div>
          <a href="dashboard.php" class="btn btn-light w-100 rounded-pill fw-bold">
            <i class="bi bi-bar-chart-line-fill me-1"></i> เปิดแดชบอร์ดวิจัย
          </a>
        </div>
      </div>
    </div>
  </div>

  <style>
    .quick-action {
      display:flex; align-items:center; gap:14px;
      padding:14px 16px; border:1px solid var(--border-gray); border-radius:14px;
      background:#fff; transition:all .18s ease; height:100%;
    }
    .quick-action:hover { border-color:var(--accent-blue); background:var(--light-blue); transform:translateY(-2px); box-shadow:0 8px 18px -8px rgba(37,99,235,.35); }
    .quick-icon { font-size:1.5rem; width:46px; height:46px; flex-shrink:0; display:inline-flex; align-items:center; justify-content:center; background:var(--light-blue); border-radius:12px; }
    .quick-text { display:flex; flex-direction:column; flex:1; min-width:0; }
    .quick-title { font-weight:700; color:var(--primary-navy); font-size:.94rem; }
    .quick-desc { color:#94a3b8; font-size:.76rem; }
    .quick-arrow { color:#cbd5e1; }
    .quick-action:hover .quick-arrow { color:var(--accent-blue); }
  </style>

  <script>
    (function () {
      let chartRounds = null, chartProgress = null;
      const BLUE = '#2563eb', NAVY = '#1e3a8a', LIGHT = '#e2e8f0';

      function fmtGroupName() {
        const g = (window.TEG ? TEG.get() : 'all');
        return (g === 'all') ? 'ทุกกลุ่มการวิจัย' : g;
      }

      async function loadTeacherDashboard() {
        const param = window.TEG ? TEG.param() : '';
        let report = [];
        try {
          const res = await (await fetch('api.php?action=get_submission_report' + param)).json();
          if (res.success) report = res.report || [];
        } catch (e) { console.error(e); }

        const total = report.length;
        const sum = k => report.reduce((a, s) => a + (s[k] ? 1 : 0), 0);
        const rounds = { pretest: sum('pretest'), d1_1: sum('d1_1'), d1_2: sum('d1_2'), d2_1: sum('d2_1'), d2_2: sum('d2_2'), posttest: sum('posttest') };
        const essays = rounds.pretest + rounds.d1_1 + rounds.d1_2 + rounds.d2_1 + rounds.d2_2 + rounds.posttest;

        const COLS = ['pretest','d1_1','d1_2','problems','checklist','reflection','d2_1','d2_2','posttest'];
        // ส่งครบ = ครบทุกช่องใน 12 คอลัมน์ของรายงาน (เรียงความ 6 + สะท้อนคิด×2หน่วย 6)
        let complete = 0, cellsDone = 0;
        const cellsTotal = total * 12;
        report.forEach(s => {
          let d = 0;
          d += ['pretest','d1_1','d1_2','d2_1','d2_2','posttest'].reduce((a,k)=>a+(s[k]?1:0),0);
          d += 2 * ((s.problems?1:0) + (s.checklist?1:0) + (s.reflection?1:0));
          cellsDone += d;
          if (d === 12) complete++;
        });
        const rate = cellsTotal ? Math.round(cellsDone / cellsTotal * 100) : 0;
        const completePct = total ? Math.round(complete / total * 100) : 0;

        // เติมค่าในการ์ดสถิติ
        document.getElementById('dTotal').textContent = total;
        document.getElementById('dGroup').textContent = fmtGroupName();
        document.getElementById('dEssays').textContent = essays;
        document.getElementById('dComplete').textContent = complete;
        document.getElementById('dCompleteFoot').textContent = 'จากทั้งหมด ' + total + ' คน';
        document.getElementById('dRate').textContent = rate + '%';
        document.getElementById('dRate2').textContent = rate + '%';
        document.getElementById('dRateBar').style.width = rate + '%';
        document.getElementById('progressPct').textContent = completePct + '%';

        renderRoundsChart(rounds);
        renderProgressChart(complete, total - complete);
      }

      function renderRoundsChart(r) {
        const ctx = document.getElementById('chartRounds');
        if (!ctx) return;
        const data = [r.pretest, r.d1_1, r.d1_2, r.d2_1, r.d2_2, r.posttest];
        if (chartRounds) { chartRounds.data.datasets[0].data = data; chartRounds.update(); return; }
        chartRounds = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['ก่อนเรียน', 'D1.1', 'D1.2', 'D2.1', 'D2.2', 'หลังเรียน'],
            datasets: [{ data, backgroundColor: [NAVY, BLUE, BLUE, BLUE, BLUE, NAVY], borderRadius: 10, borderSkipped: false, maxBarThickness: 46 }]
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ส่งแล้ว ' + c.parsed.y + ' คน' } } },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
              x: { grid: { display: false } }
            }
          }
        });
      }

      function renderProgressChart(done, remain) {
        const ctx = document.getElementById('chartProgress');
        if (!ctx) return;
        if (chartProgress) { chartProgress.data.datasets[0].data = [done, remain]; chartProgress.update(); return; }
        chartProgress = new Chart(ctx, {
          type: 'doughnut',
          data: { datasets: [{ data: [done, remain], backgroundColor: [BLUE, LIGHT], borderWidth: 0 }] },
          options: { cutout: '74%', plugins: { legend: { display: false }, tooltip: { enabled: false } } }
        });
      }

      window.onTEGChange = loadTeacherDashboard;
      document.addEventListener('DOMContentLoaded', loadTeacherDashboard);
    })();
  </script>
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
