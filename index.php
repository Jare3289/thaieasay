<?php
$page_title = 'เมนูหลัก - ระบบประเมินเรียงความ';
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
    
    <!-- 1. ตารางความคืบหน้า/สิ่งที่ยังไม่ได้ทำ — แยกเป็นตารางตามรอบ (ก่อนเรียน/หน่วย 1/หน่วย 2/หลังเรียน) กดลิงก์ไปทำงานที่ค้างได้ตรงจุด -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden text-start">
      <div class="card-header bg-primary bg-opacity-10 text-primary fw-bold py-3 px-4 border-0 d-flex align-items-center justify-content-between">
        <span class="fs-6"><i class="bi bi-list-check me-2"></i>ความคืบหน้า / สิ่งที่ยังไม่ได้ทำ</span>
        <span class="d-flex align-items-center gap-2">
          <button type="button" id="todoRefreshBtn" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fs-8" title="ตรวจสอบความคืบหน้าใหม่อีกครั้ง">
            <i class="bi bi-arrow-clockwise"></i> รีเฟรช
          </button>
          <span id="todoCountBadge" class="badge bg-primary rounded-pill px-3 py-1 font-mono fs-8 d-none">-</span>
        </span>
      </div>
      <div class="card-body p-4">
        <div id="todoListLoading" class="text-center text-muted py-3">
          <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>กำลังตรวจสอบความคืบหน้า...
        </div>
        <div id="todoListDone" class="d-none text-center text-success py-3 fw-bold">
          <i class="bi bi-check-circle-fill fs-2 d-block mb-2"></i>ทำครบทุกอย่างแล้วในตอนนี้ เยี่ยมมาก! 🎉
        </div>
        <div id="todoListContainer"></div>
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

      <!-- ผู้ช่วย AI ตรวจเรียงความ -->
      <div class="col-md-6 col-12">
        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white menu-action-card" style="border-top: 4px solid #6d28d9 !important; transition: transform 0.2s, box-shadow 0.2s;">
          <div class="card-body p-4 d-flex align-items-start gap-3">
            <div class="flex-shrink-0 p-3 rounded-3 fs-2 line-height-1" style="background:rgba(109,40,217,.1); color:#6d28d9;">
              🤖
            </div>
            <div class="flex-grow-1">
              <h5 class="fw-bold text-dark mb-1">ให้ AI ช่วยตรวจเรียงความ</h5>
              <p class="text-muted small mb-3" style="line-height:1.5; font-size:0.82rem;">
                ให้ AI อ่านเรียงความที่บันทึกไว้ แล้วบอกจุดแข็ง จุดที่ควรปรับปรุง และวิธีแก้ตามเกณฑ์ของคุณครู เพื่อนำไปพัฒนางานเขียนในร่างถัดไป
              </p>
              <a href="ai_feedback.php" class="btn btn-sm rounded-pill px-4 fw-bold shadow-sm text-white" style="background:linear-gradient(135deg,#6d28d9,#0d7377);">
                เปิดผู้ช่วย AI &rarr;
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
            <div class="col-md-8 col-sm-12">
              <h5 class="fw-bold text-dark mb-1"><i class="bi bi-pie-chart text-primary me-2"></i>รายงานผลการประเมินสะสมรอบทิศ (360° Student Report)</h5>
              <p class="text-muted small mb-0 font-light" style="font-size: 0.85rem;">คลิกเพื่อดูสรุปคะแนนประเมินร่วมกันระหว่างนักเรียน เพื่อนร่วมชั้น และคุณครูผู้สอน พร้อมวิเคราะห์พัฒนาการรายบุคคล</p>
            </div>
            <div class="col-md-4 col-sm-12 text-end mt-3 mt-md-0 d-grid gap-2">
              <a href="dashboard.php" id="btnOpenReport" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold w-100 shadow text-decoration-none">
                เปิดรายงานของฉัน
              </a>
              <!-- เอกสารประจำตัว: ข้อมูลทั้งหมดของตนเองพร้อมบทวิเคราะห์ (ในหน้านั้นมีปุ่มพิมพ์เป็น PDF ด้วย) -->
              <a href="student_report.php"
                 class="btn btn-outline-primary rounded-pill px-4 fw-bold w-100 text-decoration-none">
                <i class="bi bi-person-vcard me-1"></i>รายงานผลการเรียนรู้ของฉัน (ฉบับเต็ม)
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
    <div class="col-md-5 col-sm-12">
      <a href="ai_feedback.php" class="btn menu-card w-100 py-5 text-decoration-none">
        <div class="text-center w-100">
          <div class="fs-1 mb-3">🤖</div>
          <h4 class="fw-bold text-dark mb-2">ผลตรวจจาก AI</h4>
          <p class="text-muted small font-light">ดูข้อเสนอแนะและคะแนนโดยประมาณที่ AI ให้ไว้กับเรียงความของนักเรียน (เป็นข้อมูลประกอบ ไม่ใช่คะแนนจริง)</p>
        </div>
        <span class="text-primary text-center fw-bold small mt-3 d-block">เปิดหน้าผลตรวจ AI &rarr;</span>
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
          <div class="stat-value" id="dTotal">0</div>
          <div class="stat-foot" id="dGroup">ทุกกลุ่มการวิจัย</div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">✍️</span> เรียงความส่งแล้ว</div>
            <a href="essay_viewer.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dEssays">0</div>
          <div class="stat-foot">รวมทุกรอบ (6 ชิ้น/คน)</div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">✅</span> ส่งครบทุกชิ้น</div>
            <a href="submission_report.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dComplete">0</div>
          <div class="stat-foot" id="dCompleteFoot">จากทั้งหมด 0 คน</div>
        </div>
      </div>
      <div class="col-md-3 col-6">
        <div class="stat-card">
          <div class="d-flex justify-content-between align-items-start">
            <div class="stat-label"><span class="stat-icon badge-blue">📊</span> อัตราการส่งงาน</div>
            <a href="submission_report.php" class="text-decoration-none text-muted"><i class="bi bi-arrow-up-right"></i></a>
          </div>
          <div class="stat-value" id="dRate">0%</div>
          <div class="stat-foot">เฉลี่ยทั้งชั้น</div>
        </div>
      </div>
    </div>

    <!-- 2) เมนูด่วน + ทางลัดงานวิจัย -->
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
                ['ai_feedback.php',             '🤖', 'ผู้ช่วย AI ตรวจเรียงความ', 'ให้ข้อเสนอแนะอัตโนมัติ + ตั้งค่า AI'],
                ['research_analysis.php',       '🔬', 'วิเคราะห์สถิติวิจัย', 'ICC, Paired t-test, เชิงคุณภาพ'],
                ['chapter45.php',              '📘', 'วิเคราะห์บทที่ 4-5', 'สถิติ + ให้ AI เรียบเรียงผลการวิจัย'],
                ['class_report_print.php',      '📋', 'รายงานภาพรวมชั้นเรียน', 'สรุปผลสัมฤทธิ์ทั้งห้อง พร้อมพิมพ์เป็น PDF'],
                ['student_report.php',          '🧑‍🎓', 'รายงานรายบุคคล', 'ข้อมูลทั้งหมดของนักเรียนรายคน พร้อมบทวิเคราะห์'],
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
      function fmtGroupName() {
        const g = (window.TEG ? TEG.get() : 'all');
        return (g === 'all') ? 'ทุกกลุ่มการวิจัย' : g;
      }
      // ตัวช่วยเซ็ตข้อความแบบปลอดภัย (ไม่ให้พังทั้งชุดหากหา element ไม่เจอ)
      function setText(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
      function setWidth(id, val) { const el = document.getElementById(id); if (el) el.style.width = val; }

      async function loadTeacherDashboard() {
        const param = window.TEG ? TEG.param() : '';
        let report = [];
        try {
          const res = await (await fetch('api.php?action=get_submission_report' + param)).json();
          if (res.success) report = res.report || [];
        } catch (e) { console.error(e); }

        const total = report.length;
        const sum = k => report.reduce((a, s) => a + (s[k] ? 1 : 0), 0);
        const essays = ['pretest','d1_1','d1_2','d2_1','d2_2','posttest'].reduce((a,k)=>a+sum(k),0);

        // ส่งครบ = ครบทุกช่องใน 12 รายการ (เรียงความ 6 + สะท้อนคิดแยกหน่วย 6)
        let complete = 0, cellsDone = 0;
        const cellsTotal = total * 12;
        report.forEach(s => {
          let d = ['pretest','d1_1','d1_2','d2_1','d2_2','posttest'].reduce((a,k)=>a+(s[k]?1:0),0);
          d += ['problems1','checklist1','reflection1','problems2','checklist2','reflection2'].reduce((a,k)=>a+(s[k]?1:0),0);
          cellsDone += d;
          if (d === 12) complete++;
        });
        const rate = cellsTotal ? Math.round(cellsDone / cellsTotal * 100) : 0;

        // เติมค่าในการ์ดสถิติ (ค่าเริ่มต้นเป็น 0 อยู่แล้ว จึงไม่มีสถานะค้าง "-")
        setText('dTotal', total);
        setText('dGroup', fmtGroupName());
        setText('dEssays', essays);
        setText('dComplete', complete);
        setText('dCompleteFoot', 'จากทั้งหมด ' + total + ' คน');
        setText('dRate', rate + '%');
        setText('dRate2', rate + '%');
        setWidth('dRateBar', rate + '%');
      }

      window.onTEGChange = loadTeacherDashboard;
      document.addEventListener('DOMContentLoaded', loadTeacherDashboard);
    })();
  </script>
  <?php endif; ?>
</div>

<script>
  // คอลัมน์ตาราง = รอบ/หน่วย เรียงจากซ้ายไปขวาตามลำดับที่เรียน
  const todoColumns = ['pretest', 'task1', 'task2', 'posttest'];
  const todoColumnLabels = {
    pretest:  'ก่อนเรียน',
    task1:    'หน่วยที่ 1',
    task2:    'หน่วยที่ 2',
    posttest: 'หลังเรียน'
  };
  function escToDoText(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  // เซลล์ 1 ช่อง: done=null → งานนี้ไม่มีในรอบนี้ (ขีดกลาง ไม่นับความคืบหน้า) / true → เสร็จแล้ว / false → ยังไม่เสร็จ (มีปุ่มลิงก์ไปทำ)
  function buildTodoCell(done, link, actionText) {
    if (done === null) return `<td class="text-center text-muted">—</td>`;
    if (done) return `<td class="text-center"><span class="badge bg-success"><i class="bi bi-check-lg"></i></span></td>`;
    return `<td class="text-center"><a href="${link}" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1" style="font-size:0.72rem; white-space:nowrap;">${escToDoText(actionText || 'ไปทำ')}</a></td>`;
  }

  // การ์ดสถิติความคืบหน้า 1 ใบ (ภาพรวม หรือ รายรอบ)
  function buildStatTile(label, done, total, big) {
    const pct = total ? Math.round((done / total) * 100) : 0;
    const barClass = pct === 100 ? 'bg-success' : (pct > 0 ? 'bg-warning' : 'bg-secondary');
    return `
      <div class="${big ? 'col-12' : 'col-6 col-md-3'}">
        <div class="p-3 rounded-3 border bg-light bg-opacity-50 h-100">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small fw-bold text-muted">${escToDoText(label)}</span>
            <span class="small fw-bold text-dark">${done}/${total} <span class="text-muted">(${pct}%)</span></span>
          </div>
          <div class="progress" style="height:${big ? 10 : 6}px;">
            <div class="progress-bar ${barClass}" style="width:${pct}%"></div>
          </div>
        </div>
      </div>`;
  }

  // โหลดสถานะทุกด้าน (บันทึกเรียงความ D1/D2, ประเมินตนเอง/เพื่อน, เครื่องมือสะท้อนคิด 3 หัวข้อ) ทุกรอบในครั้งเดียว
  // แล้วแสดงเป็นสถิติความคืบหน้า + ตาราง: คอลัมน์แรก = ชื่อภาระงาน, หัวตารางบน = รอบ (ก่อนเรียน/หน่วย 1/หน่วย 2/หลังเรียน) พร้อมลิงก์ไปทำงานที่ค้างได้ทันที
  async function loadTodoList() {
    if (!currentUser || currentUser.role !== 'student') return;

    const loadingEl   = document.getElementById('todoListLoading');
    const doneEl      = document.getElementById('todoListDone');
    const containerEl = document.getElementById('todoListContainer');
    const countBadge  = document.getElementById('todoCountBadge');
    if (!containerEl) return;

    try {
      const response = await fetch(`api.php?action=get_my_todo_status&_t=${Date.now()}`);
      const res = await response.json();
      if (loadingEl) loadingEl.classList.add('d-none');
      if (!res.success || !res.phases) return;

      const p = res.phases;
      const reflUnit = (phase) => (phase === 'task1' ? 1 : 2);
      const essayCell = (phase, draft) => {
        const e = p[phase].essay[draft];
        return { done: e.done, link: `essay_writer.php?phase=${encodeURIComponent(e.phaseKey)}`, actionText: 'ไปเขียน' };
      };

      // นิยามแต่ละแถวของตาราง — applicable: รายชื่อรอบที่มีงานนี้จริง (รอบอื่นขึ้นขีดกลาง)
      const taskUnits = ['task1', 'task2'];
      const rowDefs = [
        {
          icon: '✍️', label: 'บันทึกเรียงความ', applicable: ['pretest', 'posttest'],
          cell: (phase) => essayCell(phase, 'plain')
        },
        {
          icon: '📄', label: 'บันทึกเรียงความ D1 (ร่างแรก)', applicable: taskUnits,
          cell: (phase) => essayCell(phase, 'd1')
        },
        {
          icon: '📝', label: 'บันทึกเรียงความ D2 (ร่างปรับปรุง)', applicable: taskUnits,
          cell: (phase) => essayCell(phase, 'd2')
        },
        {
          icon: '🙋‍♂️', label: 'ประเมินตนเอง', applicable: taskUnits,
          cell: (phase) => ({ done: p[phase].selfDone,
            link: `evaluation.php?mode=self&phase=${phase}`, actionText: 'ไปประเมิน' })
        },
        {
          icon: '👥', label: 'ประเมินเพื่อน', applicable: taskUnits,
          cell: (phase) => {
            const ph = p[phase];
            if (ph.peerDone) return { done: true };
            if (ph.partnerId) return { done: false, link: `evaluation.php?mode=peer&phase=${phase}`, actionText: 'ไปประเมิน' };
            return { done: false, link: `peer_matching.php?round=${phase}`, actionText: 'ไปจับคู่' };
          }
        },
        {
          icon: '📝', label: 'บันทึกอุปสรรคปัญหาการเขียน', applicable: taskUnits,
          cell: (phase) => ({ done: p[phase].problemsDone,
            link: `reflection_tools.php?unit=${reflUnit(phase)}`, actionText: 'ไปบันทึก' })
        },
        {
          icon: '✅', label: 'แบบตรวจสอบตนเอง', applicable: taskUnits,
          cell: (phase) => ({ done: p[phase].checklistDone,
            link: `reflection_tools.php?unit=${reflUnit(phase)}`, actionText: 'ไปบันทึก' })
        },
        {
          icon: '💭', label: 'แบบสะท้อนการเรียนรู้', applicable: taskUnits,
          cell: (phase) => ({ done: p[phase].reflectionDone,
            link: `reflection_tools.php?unit=${reflUnit(phase)}`, actionText: 'ไปบันทึก' })
        }
      ];

      // สร้างแถวทีละแถว พร้อมสะสมความคืบหน้าต่อคอลัมน์ (รอบ) และภาพรวมทั้งหมดไปพร้อมกัน
      let overallDone = 0, overallTotal = 0;
      const colProgress = {};
      todoColumns.forEach(phase => { colProgress[phase] = { done: 0, total: 0 }; });

      const bodyRowsHTML = rowDefs.map(r => {
        const cellsHTML = todoColumns.map(phase => {
          if (!r.applicable.includes(phase)) return buildTodoCell(null);
          const c = r.cell(phase);
          if (c.done !== null && c.done !== undefined) {
            colProgress[phase].total++;
            overallTotal++;
            if (c.done) { colProgress[phase].done++; overallDone++; }
          }
          return buildTodoCell(c.done, c.link, c.actionText);
        }).join('');
        return `<tr><td class="small text-nowrap">${r.icon} ${escToDoText(r.label)}</td>${cellsHTML}</tr>`;
      }).join('');

      const headCellsHTML = todoColumns.map(phase => {
        const cp = colProgress[phase];
        return `<th class="text-center small">${escToDoText(todoColumnLabels[phase])}<br><span class="text-muted fw-normal" style="font-size:0.72rem;">${cp.done}/${cp.total}</span></th>`;
      }).join('');

      // สถิติความคืบหน้า: ภาพรวม 1 แถบใหญ่ + รายรอบ 4 การ์ดเล็ก
      const statsHTML = `
        <div class="row g-2 mb-2">${buildStatTile('ความคืบหน้ารวมทั้งหมด', overallDone, overallTotal, true)}</div>
        <div class="row g-2 mb-3">
          ${todoColumns.map(phase => buildStatTile(todoColumnLabels[phase], colProgress[phase].done, colProgress[phase].total)).join('')}
        </div>`;

      containerEl.innerHTML = `
        ${statsHTML}
        <div class="table-responsive rounded-3 border">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr><th class="small">ภาระงาน</th>${headCellsHTML}</tr>
            </thead>
            <tbody>${bodyRowsHTML}</tbody>
          </table>
        </div>`;

      if (countBadge) {
        countBadge.textContent = `${overallDone}/${overallTotal}`;
        countBadge.classList.remove('d-none');
      }
      if (doneEl) doneEl.classList.toggle('d-none', !(overallTotal > 0 && overallDone === overallTotal));
    } catch (err) {
      console.error("Error loading todo list:", err);
      if (loadingEl) loadingEl.classList.add('d-none');
    }
  }

  // เรียกใช้ตรวจสอบความคืบหน้าเมื่อโหลดหน้าสำเร็จ
  document.addEventListener('DOMContentLoaded', () => {
    loadTodoList();
    const refreshBtn = document.getElementById('todoRefreshBtn');
    if (refreshBtn) refreshBtn.addEventListener('click', () => {
      const loadingEl = document.getElementById('todoListLoading');
      if (loadingEl) loadingEl.classList.remove('d-none');
      loadTodoList();
    });
  });

  // กลับมาที่หน้านี้อีกครั้ง (กดย้อนกลับจากหน้าประเมิน/หน้าเขียนเรียงความ หรือสลับแท็บกลับมา)
  // ต้องดึงสถานะใหม่เสมอ ไม่ใช้ของเก่าที่ค้างอยู่ในแคชของเบราว์เซอร์
  // มิฉะนั้นงานที่เพิ่งบันทึกไปจะยังไม่ขึ้นเครื่องหมายถูกจนกว่าจะรีโหลดเอง
  window.addEventListener('pageshow', (e) => { if (e.persisted) loadTodoList(); });
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') loadTodoList();
  });
</script>

<?php
require_once 'footer.php';
?>
