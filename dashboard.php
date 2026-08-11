<?php
$page_title = 'แดชบอร์ดรายงานผล - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login(); // ต้องล็อกอินก่อนเข้าหน้านี้

require_once 'header.php';
?>

<div id="view-dashboard" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <?php if ($sessionUser['role'] === 'teacher'): ?>
  <!-- 1. แผงควบคุมภาพรวมคุณครู (Teacher Classroom Dashboard) -->
  <div id="teacherOverview" class="mb-4">
    <div class="row g-3 mb-4">
      <div class="col-md-4 col-sm-12">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center gap-3">
          <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 fs-3"><i class="bi bi-people"></i></div>
          <div>
            <p class="text-muted small mb-0 fw-semibold">จำนวนผู้เรียนทั้งหมด</p>
            <h4 class="fw-bold text-dark mb-0 font-outfit" id="statTotalStudents">-</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4 col-sm-12">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center gap-3">
          <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 fs-3"><i class="bi bi-graph-up-arrow"></i></div>
          <div>
            <p class="text-muted small mb-0 fw-semibold">คะแนนเฉลี่ยรวมของชั้นเรียน</p>
            <h4 class="fw-bold text-dark mb-0 font-outfit" id="statClassAvg">0.00 / 60</h4>
          </div>
        </div>
      </div>
      <div class="col-md-4 col-sm-12">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-row align-items-center gap-3">
          <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 fs-3"><i class="bi bi-patch-check"></i></div>
          <div>
            <p class="text-muted small mb-0 fw-semibold">การประเมินเสร็จสมบูรณ์ (360°)</p>
            <h4 class="fw-bold text-dark mb-0 font-outfit" id="statCompletion">0%</h4>
          </div>
        </div>
      </div>
    </div>

    <!-- รายงานกราฟสถิติเพื่อการวิจัยชั้นเรียน (Classroom Research Charts) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
      <div class="card-header bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-primary"></i> รายงานสถิติภาพรวมเพื่อการทำวิจัยวิชาการ (Research Statistical Analysis) <span class="badge bg-primary-subtle text-primary-emphasis fw-bold ms-1" id="dashGroupBadge">—</span></h6>
        <button class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3 text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#researchChartsCollapse" aria-expanded="true" aria-controls="researchChartsCollapse">
          แสดง/ซ่อน
        </button>
      </div>
      <div class="collapse show" id="researchChartsCollapse">
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-6 col-sm-12 text-center">
              <span class="small fw-bold text-secondary mb-2 d-block">1. สัดส่วนคุณภาพผลสัมฤทธิ์ทางการประเมินของห้องเรียน (คน)</span>
              <div style="position: relative; height: 260px;" class="w-100">
                <canvas id="classQualityDistributionChart"></canvas>
              </div>
            </div>
            <div class="col-md-6 col-sm-12 text-center">
              <span class="small fw-bold text-secondary mb-2 d-block">2. ค่าเฉลี่ยของชั้นเรียนคิดเป็นร้อยละแยกตามมิติหลักและเกณฑ์ย่อย (เต็ม 100%)</span>
              <div style="position: relative; height: 340px;" class="w-100">
                <canvas id="classDimensionAveragesChart"></canvas>
              </div>
            </div>
            <!-- กราฟแมงมุมคะแนนเฉลี่ยรายบุคคลแยกตามรายด้าน (1 นักเรียน = 1 รูปใย) — แยก 2 กราฟ: ภาระงาน และ ก่อน/หลังเรียน -->
            <div class="col-12 mt-4 border-top pt-4">
              <span class="small fw-bold text-secondary mb-3 d-block text-center">3. กราฟแมงมุมวิเคราะห์รูปแบบคะแนนเฉลี่ยของผู้เรียนแต่ละคนแยกตามรายด้าน (1 นักเรียน = 1 รูปใยแมงมุม)</span>
              <div class="row g-4">
                <div class="col-lg-6 col-12 text-center">
                  <span class="small fw-bold text-primary-emphasis mb-2 d-block"><i class="bi bi-clipboard-check"></i> 3.1 ภาระงานในหน่วยเรียน (Task 1 + Task 2)</span>
                  <div style="position: relative; height: 440px;" class="w-100">
                    <canvas id="classDimensionSpiderTask"></canvas>
                  </div>
                </div>
                <div class="col-lg-6 col-12 text-center">
                  <span class="small fw-bold text-success-emphasis mb-2 d-block"><i class="bi bi-arrow-left-right"></i> 3.2 ก่อนเรียน–หลังเรียน (Pretest / Posttest โดยครู)</span>
                  <div style="position: relative; height: 440px;" class="w-100">
                    <canvas id="classDimensionSpiderPrePost"></canvas>
                  </div>
                </div>
              </div>
            </div>
            <!-- บทวิเคราะห์และข้อเสนอแนะเชิงลึกเพื่อการทำวิจัยชั้นเรียน (Research Insights Panel) -->
            <div class="col-12 mt-4 border-top pt-4 text-start" id="researchInsightsPanel">
              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-lightbulb-fill text-warning"></i> บทวิเคราะห์และข้อเสนอแนะเชิงวิชาการเพื่อการพัฒนาการเรียนรู้ (Academic Insights & Recommendations)</h6>
              <div class="row g-3">
                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 rounded-3 p-3 h-100" style="background: #fafbff; border-left: 4px solid var(--primary-navy) !important;">
                    <span class="small fw-bold text-secondary text-uppercase tracking-wider mb-2 d-block">📊 การวิเคราะห์ข้อมูลเชิงปริมาณ (Quantitative Summary)</span>
                    <div id="quantitativeReport" class="small text-muted" style="line-height: 1.6;">
                      กำลังวิเคราะห์สถิติ...
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 rounded-3 p-3 h-100" style="background: #fffbeb; border-left: 4px solid #f59e0b !important;">
                    <span class="small fw-bold text-warning-emphasis text-uppercase tracking-wider mb-2 d-block">💡 ข้อเสนอแนะเพื่อทำวิจัยชั้นเรียน (Pedagogical & Research Action Points)</span>
                    <div id="pedagogicalReport" class="small text-warning-emphasis" style="line-height: 1.6;">
                      กำลังคำนวณข้อเสนอแนะ...
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- รายการสรุปผลและตารางจัดการ -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
      <div class="bg-primary text-white p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%) !important;">
        <h5 class="fw-bold mb-0"><i class="bi bi-table"></i> ตารางสรุปภาพรวมคะแนนการประเมินชั้นเรียน</h5>
        <button onclick="loadTeacherOverview()" class="btn btn-outline-light btn-sm fw-bold px-3">🔄 โหลดข้อมูลใหม่</button>
      </div>
      
      <!-- ตัวกรองและกล่องค้นหา -->
      <div class="card-body bg-light border-bottom p-3">
        <div class="row g-2">
          <div class="col-md-4 col-sm-12">
            <div class="input-group">
              <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-search"></i></span>
              <input type="text" id="teacherSearchInput" onkeyup="filterTeacherTable()" class="form-control bg-white border-start-0" placeholder="พิมพ์ชื่อนักเรียนหรือรหัส 5 หลักเพื่อค้นหา...">
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="input-group">
              <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-funnel"></i> ตัวกรองสถานะ</span>
              <select id="teacherStatusFilter" onchange="filterTeacherTable()" class="form-select bg-white border-start-0 font-semibold">
                <option value="all">ทั้งหมด (แสดงเด็กทั้งหมด)</option>
                <option value="complete">ประเมินเสร็จสมบูรณ์ครบ 3 มิติ</option>
                <option value="incomplete">ยังส่งประเมินไม่ครบถ้วน</option>
                <option value="no_eval">ยังไม่มีการประเมินเลยสักรายการ</option>
                <option value="missing_teacher">เฉพาะคนที่ "คุณครูยังไม่ได้ให้คะแนน"</option>
              </select>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="input-group">
              <span class="input-group-text bg-white text-secondary border-end-0"><i class="bi bi-grid-3x3-gap"></i> มุมมองรายงาน</span>
              <select id="dashboardViewMode" onchange="switchDashboardViewMode()" class="form-select bg-white border-start-0 font-semibold text-primary">
                <option value="task1" selected>ภาระงาน หน่วยที่ 1 (360° & Expert)</option>
                <option value="task2">ภาระงาน หน่วยที่ 2 (360° & Expert)</option>
                <option value="prepost">เปรียบเทียบผลสัมฤทธิ์ (ก่อนเรียน vs หลังเรียน)</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- ตารางรายชื่อ -->
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 text-start table-classroom">
          <thead id="overviewTableHead" class="table-light text-secondary small fw-bold text-uppercase">
            <tr>
              <th class="px-3 py-3" style="width: 10%">รหัสนักเรียน</th>
              <th class="px-3 py-3" style="width: 20%">ชื่อ-สกุลผู้เรียน</th>
              <th class="px-3 py-3 text-center" style="width: 10%">ตนเองประเมิน</th>
              <th class="px-3 py-3 text-center" style="width: 10%">เพื่อนประเมิน</th>
              <th class="px-3 py-3 text-center" style="width: 10%">ครูประเมิน</th>
              <th class="px-3 py-3 text-center text-warning-emphasis" style="width: 12%">ผู้เชี่ยวชาญ 1</th>
              <th class="px-3 py-3 text-center text-warning-emphasis" style="width: 12%">ผู้เชี่ยวชาญ 2</th>
              <th class="px-3 py-3 text-center" style="width: 8%">เฉลี่ยสะสม</th>
              <th class="px-3 py-3 text-end" style="width: 8%">การจัดการ</th>
            </tr>
          </thead>
          <tbody id="overviewTableBody" class="small">
            <tr><td colspan="9" class="text-center text-muted py-5 fw-bold">กำลังประมวลผลสรุปคะแนนนักเรียนรายบุคคล...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ลิงก์ไปหน้าระบบวิเคราะห์ทางสถิติเพื่อการวิจัย (Inter-rater & Paired t-test) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
      <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3" style="background: linear-gradient(135deg, #fafbff 0%, #fffbeb 100%);">
        <div>
          <h6 class="fw-bold text-dark mb-1"><i class="bi bi-gear-wide-connected text-warning"></i> ระบบวิเคราะห์ทางสถิติเพื่อการทำวิจัยและตรวจสอบคุณภาพเกณฑ์ประเมิน</h6>
          <p class="text-muted small mb-0">ค่าความสอดคล้องผู้ตรวจ 3 คน (ICC), Paired t-test, ศูนย์วิเคราะห์เชิงคุณภาพ และเรียงความนักเรียน — แยกเป็นหน้าเฉพาะเพื่อความชัดเจน (กลุ่มทดลอง: ห้อง 606)</p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
          <a href="essay_viewer.php" class="btn btn-outline-danger fw-bold px-4 rounded-pill text-nowrap">
            <i class="bi bi-pencil-square me-1"></i> ดูเรียงความนักเรียนทุกคน
          </a>
          <a href="research_analysis.php" class="btn btn-primary fw-bold px-4 rounded-pill text-nowrap">
            <i class="bi bi-box-arrow-up-right me-1"></i> เปิดหน้าวิเคราะห์สถิติงานวิจัย
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 2. ส่วนหัวรายงานผลคะแนนสะสมรอบทิศ (360° Report Header) -->
  <div id="individualReportHeader" class="bg-primary text-white p-4 rounded-4 shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 <?php echo $sessionUser['role'] === 'student' ? '' : 'd-none'; ?>" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%) !important;">
    <div>
      <h4 class="fw-bold mb-1">บทวิเคราะห์คะแนนและการประเมินสะสมรอบด้าน (360° Assessment)</h4>
      <p class="mb-0 text-white-50 small">นักเรียนเป้าหมาย: <span id="dashStudentName" class="fw-bold text-white fs-6">...</span> <span class="opacity-75">(รหัส: <span id="dashStudentId" class="font-mono">...</span>)</span></p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <label for="studentPhaseSelector" class="small text-white-50 text-nowrap mb-0">รอบประเมิน:</label>
      <select id="studentPhaseSelector" class="form-select form-select-sm bg-white text-dark font-semibold border-0" style="width:160px;" onchange="switchStudentPhase()">
        <option value="task1" selected>ภาระงาน หน่วยที่ 1</option>
        <option value="task2">ภาระงาน หน่วยที่ 2</option>
        <option value="posttest">หลังเรียน (T2)</option>
        <option value="pretest">ก่อนเรียน (T1)</option>
      </select>
      <?php if ($sessionUser['role'] === 'teacher'): ?>
      <button id="backToOverviewBtn" onclick="showTeacherOverviewOnly()" class="btn btn-light fw-bold px-3 btn-sm rounded-pill shadow-sm">
        &uarr; กลับไปภาพรวม
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- สถานะดาวน์โหลดข้อมูล -->
  <div id="searchLoading" class="card border-0 shadow-sm p-5 text-center bg-white d-none">
    <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
    <p class="text-muted fw-bold small mb-0">กำลังคำนวณและประมวลผลคะแนน 3 ส่วนอย่างละเอียด...</p>
  </div>

  <!-- ไม่พบข้อมูล -->
  <div id="searchNotFound" class="card border-0 shadow-sm p-5 text-center bg-white d-none">
    <div class="fs-1 text-warning mb-3">📂</div>
    <h5 class="fw-bold text-dark mb-1">ไม่พบรายงานคะแนน</h5>
    <p class="text-muted small mb-0">นักเรียนเป้าหมายคนนี้ยังไม่มีการประเมินใดๆ บันทึกเข้ามาในระบบ</p>
  </div>

  <!-- 3. ผลคะแนน 360° (searchResults) -->
  <div id="searchResults" class="d-none">
    <!-- แถวการ์ดคะแนน -->
    <div class="row g-3 mb-4">
      <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card text-white border-0 shadow h-100 d-flex flex-column justify-content-between p-4 position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);">
          <div>
            <span class="badge bg-white bg-opacity-20 text-white font-outfit fs-8 tracking-wider">CONSOLIDATED SCORE</span>
            <p class="text-white-50 small mt-3 mb-0">คะแนนเฉลี่ยรวม 3 ส่วน</p>
          </div>
          <div class="my-4">
            <h1 class="display-4 fw-bold mb-0" id="consolScore">0.0 <span class="fs-6 opacity-50">/ 60</span></h1>
          </div>
          <div class="fw-bold py-2 rounded text-center" id="consolQuality" style="background: rgba(255,255,255,0.15)">
            ระดับคุณภาพ: -
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card border-0 shadow-sm h-100 p-4 bg-white d-flex flex-column justify-content-between shadow-sm rounded-4 text-start" id="card-self-eval" style="border-top: 5px solid #2563eb">
          <div>
            <span class="text-muted small fw-bold">🙋‍♂️ ตนเองประเมิน</span>
            <h2 class="fw-bold text-dark mt-3 mb-0" id="cardSelfScore">-</h2>
            <p class="text-muted small mt-1 mb-0 text-truncate">ผู้ประเมิน: <span id="cardSelfName" class="fw-bold">-</span></p>
          </div>
          <div class="mt-3">
            <span class="badge badge-blue px-3 py-2" id="cardSelfLevel">ไม่มีข้อมูล</span>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card border-0 shadow-sm h-100 p-4 bg-white d-flex flex-column justify-content-between shadow-sm rounded-4 text-start" id="card-peer-eval" style="border-top: 5px solid #f97316">
          <div>
            <span class="text-muted small fw-bold">👥 เพื่อนประเมิน</span>
            <h2 class="fw-bold text-dark mt-3 mb-0" id="cardPeerScore">-</h2>
            <p class="text-muted small mt-1 mb-0 text-truncate">ผู้ประเมิน: <span id="cardPeerName" class="fw-bold">-</span></p>
          </div>
          <div class="mt-3">
            <span class="badge badge-coral px-3 py-2" id="cardPeerLevel">ไม่มีข้อมูล</span>
          </div>
        </div>
      </div>

      <div class="col-lg-3 col-md-6 col-sm-12">
        <div class="card border-0 shadow-sm h-100 p-4 bg-white d-flex flex-column justify-content-between shadow-sm rounded-4 text-start" id="card-teacher-eval" style="border-top: 5px solid #059669">
          <div>
            <span class="text-muted small fw-bold">👩‍🏫 ครูประเมิน</span>
            <h2 class="fw-bold text-dark mt-3 mb-0" id="cardTeacherScore">-</h2>
            <p class="text-muted small mt-1 mb-0 text-truncate">ผู้ประเมิน: <span id="cardTeacherName" class="fw-bold">-</span></p>
          </div>
          <div class="mt-3">
            <span class="badge badge-teal px-3 py-2" id="cardTeacherLevel">ไม่มีข้อมูล</span>
          </div>
        </div>
      </div>
    </div>

    <!-- กราฟรายด้านและความสอดคล้อง -->
    <div class="row g-4 mb-4">
      <div class="col-lg-8 col-md-12 col-sm-12">
        <div class="card border-0 shadow-sm p-4 bg-white h-100 rounded-4 text-start">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-fill text-primary"></i> กราฟเปรียบเทียบร้อยละของคะแนนเต็มจำแนกรายด้าน (4 มิติ)</h5>
          <div style="position: relative; height: 350px;" class="w-100">
            <canvas id="scoresChart"></canvas>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-12 col-sm-12">
        <div class="card border-0 shadow-sm p-4 bg-white h-100 rounded-4 text-start">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-primary"></i> การประเมินความสอดคล้อง (Consistency)</h5>
          <div id="consistencyIndicatorArea" class="h-100 d-flex align-items-center">
            <div class="text-muted text-center w-100">กำลังประมวลผลความสอดคล้องคะแนน...</div>
          </div>
        </div>
      </div>
    </div>

    <!-- กราฟเรดาร์ 360 Profile -->
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm p-4 bg-white rounded-4 text-start">
          <h5 class="fw-bold text-dark mb-3"><i class="bi bi-hexagon-fill text-primary"></i> กราฟเรดาร์ 6 เหลี่ยมแสดงระดับคุณภาพประเมินเทียบ 3 มิติ (360° Radar Profile)</h5>
          <div style="position: relative; height: 380px;" class="w-100">
            <canvas id="individualRadarChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- ตารางเปรียบเทียบละเอียด -->
    <div class="card border-0 shadow-sm p-4 bg-white mb-4 rounded-4 text-start">
      <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid-3x3-gap-fill text-primary"></i> ตารางเปรียบเทียบคะแนนรายข้อเกณฑ์ย่อยแบบละเอียด</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle mb-0 text-start" style="min-width: 800px;">
          <thead class="table-dark">
            <tr>
              <th class="px-3 py-2.5">หัวข้อเกณฑ์การประเมิน</th>
              <th class="px-3 py-2.5 text-center" style="width: 15%">ตัวคูณ (Weight)</th>
              <th class="px-3 py-2.5 text-center" style="width: 20%">🙋‍♂️ คะแนนตนเอง</th>
              <th class="px-3 py-2.5 text-center" style="width: 20%">👥 คะแนนเพื่อน</th>
              <th class="px-3 py-2.5 text-center" style="width: 20%">👩‍🏫 คะแนนครู</th>
            </tr>
          </thead>
          <tbody id="consistencyTableBody" class="small">
            <!-- ข้อมูลผ่าน JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- ความคิดเห็นคำแนะนำ AI -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-4 text-start mb-4">
      <h5 class="fw-bold text-dark mb-4"><i class="bi bi-lightbulb-fill text-warning"></i> ผลวิเคราะห์และข้อเสนอแนะเชิงลึกเพื่อการพัฒนา</h5>
      <div class="row g-4" id="feedbackContainer">
        <!-- ข้อมูลผ่าน JS -->
      </div>
    </div>

    <!-- บันทึกการสะท้อนการเรียนรู้ (Learning Reflection) -->
    <div class="card border-0 shadow-sm p-4 bg-white rounded-4 text-start mb-4" id="reflectionCard">
      <h5 class="fw-bold text-dark mb-1"><i class="bi bi-journal-text text-info"></i> บันทึกการสะท้อนการเรียนรู้ของผู้เรียน</h5>
      <p class="text-muted small mb-4">ข้อมูลที่ผู้เรียนบันทึกไว้ในแบบบันทึกการสะท้อนการเรียนรู้หลังจากปรับปรุงเรียงความฉบับสมบูรณ์</p>
      <div id="reflectionDataContainer">
        <div class="text-center text-muted py-3 small"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลดข้อมูลสะท้อนคิด...</div>
      </div>
    </div>
  </div>
</div>

<script>
  let studentDB = {};
  let scoresChartInstance = null;
  let individualRadarChartInstance = null;
  let classQualityChartInstance = null;
  let classDimensionChartInstance = null;
  // อินสแตนซ์กราฟแมงมุมรายบุคคล 2 กราฟ: ภาระงาน และ ก่อน/หลังเรียน
  let spiderChartInstances = { task: null, prepost: null };
  let classroomResearchData = null;
  let currentResearchPhase = 'task1';
  let currentDashboardViewMode = 'task1';

  // แผนที่กลุ่มการวิจัยของนักเรียน (รหัส -> กลุ่มทดลอง/กลุ่มตัวอย่าง) และตัวกรองกลุ่มปัจจุบัน
  let studentGroupDB = {};
  let currentGroupFilter = 'all';

  // ตรวจว่านักเรียนคนนี้อยู่ในกลุ่มที่กำลังเลือกแสดงหรือไม่ (ใช้กรองทั้งตาราง สถิติ และกราฟทั้ง 3)
  function passesGroupFilter(id) {
    if (currentGroupFilter === 'all') return true;
    return (studentGroupDB[id] || '') === currentGroupFilter;
  }

  // อัปเดตป้ายบอกกลุ่มที่กำลังแสดงบนหัวการ์ดรายงาน
  function updateGroupBadge() {
    const badge = document.getElementById('dashGroupBadge');
    if (badge) badge.textContent = (currentGroupFilter === 'all') ? 'ทุกกลุ่มรวมกัน' : currentGroupFilter;
  }

  // ตั้งค่ากลุ่มเริ่มต้นจากค่าที่จำไว้ร่วมกันทุกหน้า (ค่าเริ่มต้นทั้งระบบ = กลุ่มตัวอย่าง)
  function initGroupFilterFromStore() {
    currentGroupFilter = window.TEG ? TEG.get() : 'all';
    updateGroupBadge();
  }

  // ถูกเรียกจากปุ่มเลือกกลุ่มบน navbar (จุดควบคุมเดียว) เมื่อกลุ่มเปลี่ยน
  window.onTEGChange = function() {
    currentGroupFilter = window.TEG ? TEG.get() : 'all';
    updateGroupBadge();
    if (classroomResearchData) processDashboardData();
  };

  const criteriaMap = {
    '1.1': { name: '1.1 ความตรงประเด็น (คะแนนเต็ม 12)', mult: 3 },
    '1.2': { name: '1.2 แก่นเรื่องชัดเจน (คะแนนเต็ม 6)', mult: 1.5 },
    '1.3': { name: '1.3 การขยายความและเหตุผล (คะแนนเต็ม 9)', mult: 2.25 },
    '2.1': { name: '2.1 ความครบถ้วนขององค์ประกอบ (คะแนนเต็ม 8)', mult: 2 },
    '2.2': { name: '2.2 การลำดับประเด็นเป็นระบบ (คะแนนเต็ม 4)', mult: 1 },
    '3.1': { name: '3.1 การใช้ประโยคถูกต้อง (คะแนนเต็ม 4)', mult: 1 },
    '3.2': { name: '3.2 การเลือกใช้คำ (คะแนนเต็ม 6)', mult: 1.5 },
    '3.3': { name: '3.3 ระดับภาษาเหมาะสม (คะแนนเต็ม 5)', mult: 1.25 },
    '4.1': { name: '4.1 การสะกดคำถูกต้อง (คะแนนเต็ม 2)', mult: 0.5 },
    '4.2': { name: '4.2 การเว้นวรรค (คะแนนเต็ม 2)', mult: 0.5 },
    '4.3': { name: '4.3 ความเรียบร้อย (คะแนนเต็ม 2)', mult: 0.5 }
  };

  const rubricData = [
    {
      section: "1) ด้านเนื้อหาสาระ",
      items: [
        {
          id: "1.1", name: "1.1 ความตรงประเด็น (คะแนนเต็ม 12)", multiplier: 3,
          levels: [
            { score: 4, label: "ดีมาก", desc: "เนื้อหาสัมพันธ์กับหัวข้อทุกส่วน ไม่ปรากฏประเด็นที่อยู่นอกขอบเขตของหัวข้อ" },
            { score: 3, label: "ดี", desc: "เนื้อหาสัมพันธ์กับหัวข้อเกือบทุกส่วน ปรากฏประเด็นนอกขอบเขต 1 ประเด็น" },
            { score: 2, label: "ปานกลาง", desc: "เนื้อหาสัมพันธ์กับหัวข้อบางส่วนปรากฏประเด็นนอกขอบเขต 2 ประเด็น" },
            { score: 1, label: "พอใช้", desc: "เนื้อหาสัมพันธ์กับหัวข้อหลายส่วน ปรากฏประเด็นนอกขอบเขต 3 ประเด็นขึ้นไป" },
            { score: 0, label: "ปรับปรุง", desc: "เนื้อหาไม่สัมพันธ์กับหัวข้อที่กำหนด" }
          ]
        },
        {
          id: "1.2", name: "1.2 แก่นเรื่องชัดเจน (คะแนนเต็ม 6)", multiplier: 1.5,
          levels: [
            { score: 4, label: "ดีมาก", desc: "แก่นเรื่องโดดเด่น ชัดเจน โดยระบุประเด็นหลักไว้ในส่วนคำนำ ย้ำประเด็นเดิมในส่วนสรุป และทุกย่อหน้าสัมพันธ์กับประเด็นหลัก" },
            { score: 3, label: "ดี", desc: "แก่นเรื่องชัดเจนและสอดคล้องกับหัวข้อที่กำหนด ระบุประเด็นหลัก 2 ใน 3 ย่อหน้า ในส่วนคำนำ ส่วนเนื้อเรื่อง หรือส่วนสรุป" },
            { score: 2, label: "ปานกลาง", desc: "แก่นเรื่องไม่ชัดเจนหรือไม่ปรากฏชัดเจนส่วนเนื้อเรื่อง ระบุประเด็นหลักเพียง 1 ย่อหน้า" },
            { score: 1, label: "พอใช้", desc: "แก่นเรื่องไม่ชัดเจน ไม่ระบุประเด็นหลักทั้งในส่วนคำนำและส่วนสรุป แต่ยังสรุปประเด็นไม่ได้จากเนื้อเรื่อง" },
            { score: 0, label: "ปรับปรุง", desc: "แก่นเรื่องไม่ชัดเจน ไม่สามารถระบุประเด็นหลักได้" }
          ]
        },
        {
          id: "1.3", name: "1.3 การขยายความและเหตุผล (คะแนนเต็ม 9)", multiplier: 2.25,
          levels: [
            { score: 4, label: "ดีมาก", desc: "ขยายความครบทุกประเด็นหลัก โดยแต่ละประเด็นมีเหตุผลหรือตัวอย่างสนับสนุนที่สอดคล้อง ตั้งแต่ 2 รายการขึ้นไป" },
            { score: 3, label: "ดี", desc: "ขยายความครบทุกประเด็นหลัก โดยแต่ละประเด็นมีเหตุผลหรือตัวอย่างสนับสนุนที่สอดคล้อง จำนวน 1 รายการ" },
            { score: 2, label: "ปานกลาง", desc: "ขยายความครบทุกประเด็นหลัก แต่ปรากฏประเด็นที่ขาดเหตุผลหรือตัวอย่างสนับสนุน จำนวน 1 ถึง 2 ประเด็น" },
            { score: 1, label: "พอใช้", desc: "ขยายความเพียงบางประเด็น หรือปรากฏประเด็นที่ขาดเหตุผลหรือตัวอย่างสนับสนุน ตั้งแต่ 3 ประเด็นขึ้นไป" },
            { score: 0, label: "ปรับปรุง", desc: "ไม่มีการขยายความ  ไม่มีเหตุผลหรือตัวอย่างสนับสนุน" }
          ]
        }
      ]
    },
    {
      section: "2) ด้านองค์ประกอบและการลำดับ",
      items: [
        {
          id: "2.1", name: "2.1 ความครบถ้วนขององค์ประกอบ (คะแนนเต็ม 8)", multiplier: 2,
          levels: [
            { score: 4, label: "ดีมาก", desc: "องค์ประกอบครบทั้ง 3 ส่วน ได้แก่ คำนำ เนื้อเรื่อง และสรุป แยกย่อหน้าแต่ละส่วนชัดเจน" },
            { score: 3, label: "ดี", desc: "องค์ประกอบครบทั้ง 3 ส่วน แยกย่อหน้าแต่ละส่วนชัดเจน แต่สัดส่วนความยาวไม่เท่ากันทุกย่อหน้า" },
            { score: 2, label: "ปานกลาง", desc: "องค์ประกอบครบถ้วน แต่สัดส่วนไม่สมดุล หรือไม่แยกย่อหน้าให้เห็นขอบเขตอย่างชัดเจน" },
            { score: 1, label: "พอใช้", desc: "องค์ประกอบไม่ครบถ้วน ขาดส่วนสำคัญไป 1 ส่วน เช่น คำนำ หรือสรุป" },
            { score: 0, label: "ปรับปรุง", desc: "องค์ประกอบไม่ครบถ้วน ขาดตั้งแต่ 2 ส่วนขึ้นไป และไม่สามารถแยกแต่ละส่วนได้อย่างชัดเจน" }
          ]
        },
        {
          id: "2.2", name: "2.2 การลำดับประเด็นเป็นระบบ (คะแนนเต็ม 4)", multiplier: 1,
          levels: [
            { score: 4, label: "ดีมาก", desc: "ลำดับย่อหน้าเรียงตามลำดับเหตุผลได้ถูกต้อง มีทิศทางชัดเจน ไม่มีการสลับประเด็น" },
            { score: 3, label: "ดี", desc: "ลำดับย่อหน้าต่อเนื่อง มีทิศทางชัดเจนเป็นส่วนใหญ่ ปรากฏย่อหน้าที่วางผิด 1 ย่อหน้า" },
            { score: 2, label: "ปานกลาง", desc: "ลำดับย่อหน้าไม่สม่ำเสมอ ปรากฏย่อหน้าที่วางผิด 2 ย่อหน้า" },
            { score: 1, label: "พอใช้", desc: "ลำดับประเด็นสับสน กระทบต่อความเข้าใจ ปรากฏย่อหน้าที่วางผิด 3 ย่อหน้า" },
            { score: 0, label: "ปรับปรุง", desc: "ลำดับประเด็นไม่เป็นระบบ" }
          ]
        }
      ]
    },
    {
      section: "3) ด้านการใช้สำนวนภาษา",
      items: [
        {
          id: "3.1", name: "3.1 การใช้ประโยคถูกต้อง (คะแนนเต็ม 4)", multiplier: 1,
          levels: [
            { score: 4, label: "ดีมาก", desc: "ใช้ประโยคถูกต้องตามหลักภาษาทั้งหมด และใช้โครงสร้างประโยคหลากหลาย" },
            { score: 3, label: "ดี", desc: "ใช้ประโยคถูกต้องเป็นส่วนใหญ่ ปรากฏประโยคผิดหลักภาษาไม่เกิน 2 ประโยค แต่ใช้โครงสร้างประโยคหลากหลาย" },
            { score: 2, label: "ปานกลาง", desc: "ใช้ประโยคค่อนข้างถูกต้องแต่ปรากฏประโยคผิดหลักภาษา 3 ถึง 5 ประโยค และขาดความหลากหลาย" },
            { score: 1, label: "พอใช้", desc: "ใช้ประโยคผิดพลาดหลายแห่ง ตั้งแต่ 6 ถึง 8 ประโยค และขาดความหลากหลายของรูปแบบประโยค" },
            { score: 0, label: "ปรับปรุง", desc: "ใช้ประโยคผิดพลาด ตั้งแต่ 9 ประโยคขึ้นไป" }
          ]
        },
        {
          id: "3.2", name: "3.2 การเลือกใช้คำ (คะแนนเต็ม 6)", multiplier: 1.5,
          levels: [
            { score: 4, label: "ดีมาก", desc: "เลือกใช้คำและคำเชื่อมได้ถูกต้อง สื่อความหมายชัดเจน กระชับ และสละสลวย โดยใช้คำ คำเชื่อม และสำนวนถูกต้องทั้งหมด" },
            { score: 3, label: "ดี", desc: "เลือกใช้คำถูกต้องตามความหมายและสอดคล้องกับบริบท แต่ปรากฏการใช้คำเชื่อมคลาดเคลื่อนไม่เกิน 2 แห่ง" },
            { score: 2, label: "ปานกลาง", desc: "เลือกใช้คำถูกต้องแต่มีคำที่กำกวมบางแห่ง ปรากฏการใช้คำเชื่อมคลาดเคลื่อนรวม 3 ถึง 5 แห่ง" },
            { score: 1, label: "พอใช้", desc: "ใช้คำผิดความหมาย 6 ถึง 8 แห่ง และปรากฏการใช้สำนวนไม่เหมาะสม" },
            { score: 0, label: "ปรับปรุง", desc: "ใช้คำผิดความหมายและใช้สำนวนคลาดเคลื่อนเป็นส่วนใหญ่ ตั้งแต่ 9 แห่งขึ้นไป" }
          ]
        },
        {
          id: "3.3", name: "3.3 ระดับภาษาเหมาะสม (คะแนนเต็ม 5)", multiplier: 1.25,
          levels: [
            { score: 4, label: "ดีมาก", desc: "ใช้ภาษาระดับกึ่งทางการขึ้นไปได้ถูกต้องและสม่ำเสมอ โดยไม่ปรากฏภาษาพูดปะปน" },
            { score: 3, label: "ดี", desc: "ใช้ภาษาระดับกึ่งทางการขึ้นไปได้ถูกต้องสม่ำเสมอ ปรากฏคำภาษาพูดปะปนไม่เกิน 2 ตำแหน่ง" },
            { score: 2, label: "ปานกลาง", desc: "ใช้ภาษาระดับกึ่งทางการขึ้นไปเป็นส่วนใหญ่ ปรากฏคำภาษาพูดปะปน 3 ถึง 5 ตำแหน่ง" },
            { score: 1, label: "พอใช้", desc: "ใช้ภาษาระดับกึ่งทางการสลับไม่คงที่ ปรากฏคำภาษาพูดปะปน 6 ถึง 8 ตำแหน่ง" },
            { score: 0, label: "ปรับปรุง", desc: "ใช้ภาษาพูดหรือภาษาปากตลอดทั้งงานเขียน หรือปรากฏคำภาษาพูดตั้งแต่ 9 ตำแหน่งขึ้นไป" }
          ]
        }
      ]
    },
    {
      section: "4) ด้านอักขรวิธีและกลไกการเขียน",
      items: [
        {
          id: "4.1", name: "4.1 การสะกดคำถูกต้อง (คะแนนเต็ม 2)", multiplier: 0.5,
          levels: [
            { score: 4, label: "ดีมาก", desc: "สะกดคำได้ถูกต้องตามพจนานุกรมทุกคำ" },
            { score: 3, label: "ดี", desc: "สะกดคำผิด 1 ถึง 2 แห่ง" },
            { score: 2, label: "ปานกลาง", desc: "สะกดคำผิด 3 ถึง 5 แห่ง" },
            { score: 1, label: "พอใช้", desc: "สะกดคำผิด 6 ถึง 8 แห่ง" },
            { score: 0, label: "ปรับปรุง", desc: "สะกดคำผิดตั้งแต่ 9 แห่งขึ้นไป" }
          ]
        },
        {
          id: "4.2", name: "4.2 การเว้นวรรค (คะแนนเต็ม 2)", multiplier: 0.5,
          levels: [
            { score: 4, label: "ดีมาก", desc: "เว้นวรรคตอนถูกต้องตามหลักเกณฑ์ทั้งหมด" },
            { score: 3, label: "ดี", desc: "เว้นวรรคผิด 1 ถึง 2 จุด" },
            { score: 2, label: "ปานกลาง", desc: "เว้นวรรคผิด 3 ถึง 5 จุด" },
            { score: 1, label: "พอใช้", desc: "เว้นวรรคผิด 6 ถึง 8 จุด" },
            { score: 0, label: "ปรับปรุง", desc: "เว้นวรรคผิดตั้งแต่ 9 จุดขึ้นไป" }
          ]
        },
        {
          id: "4.3", name: "4.3 ความเรียบร้อย (คะแนนเต็ม 2)", multiplier: 0.5,
          levels: [
            { score: 4, label: "ดีมาก", desc: "ผลงานสะอาด เป็นระเบียบ ลายมืออ่านง่าย ไม่ปรากฏรอยขูดลบขีดฆ่า" },
            { score: 3, label: "ดี", desc: "ผลงานสะอาดเรียบร้อย ลายมืออ่านง่ายปรากฏรอยขูดลบขีดฆ่า 1 ถึง 2 จุด" },
            { score: 2, label: "ปานกลาง", desc: "ผลงานค่อนข้างเรียบร้อย ลายมืออ่านง่าย ปรากฏรอยขูดลบขีดฆ่า 3 ถึง 5 จุด" },
            { score: 1, label: "พอใช้", desc: "ผลงานไม่เรียบร้อย ปรากฏรอยขูดลบขีดฆ่า 6 ถึง 8 จุด" },
            { score: 0, label: "ปรับปรุง", desc: "ผลงานไม่เรียบร้อย ปรากฏรอยขูดลบขีดฆ่าตั้งแต่ 9 จุดขึ้นไป หรือลายมืออ่านยาก" }
          ]
        }
      ]
    }
  ];

  // โหลดรายชื่อจาก API
  async function loadStudents() {
    try {
      const response = await fetch(`api.php?action=get_students_list&_t=${new Date().getTime()}`);
      const res = await response.json();
      if (res.success) {
        studentDB = res.students;
      }
    } catch (err) {
      console.error(err);
    }
  }

  // --- ระบบสำหรับคุณครู ---
  async function loadTeacherOverview() {
    const tbody = document.getElementById('overviewTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-5 fw-bold"><div class="spinner-border spinner-border-sm mb-2 d-block mx-auto"></div>กำลังประมวลคำนวณและดึงข้อมูลสถิติสำหรับการวิจัย...</td></tr>';
    
    try {
      const response = await fetch(`api.php?action=get_classroom_research_data&_t=${new Date().getTime()}`);
      const text = await response.text();
      let res;
      try {
        res = JSON.parse(text);
      } catch (parseErr) {
        console.error("JSON parse error:", parseErr, "Response text:", text);
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-5 fw-bold">เกิดข้อผิดพลาดในการโหลดข้อมูลวิจัยชั้นเรียน<br><small class="fw-normal text-muted" style="font-family: monospace; display: block; max-height: 150px; overflow-y: auto; text-align: left; padding: 10px; background: #f8d7da; border-radius: 6px; border: 1px solid #f5c6cb; margin-top: 10px;">' + text.substring(0, 500).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</small></td></tr>';
        return;
      }
      
      if (res.success) {
        classroomResearchData = res;
        // สร้างแผนที่กลุ่มการวิจัยของนักเรียน (รหัส -> กลุ่ม) เพื่อใช้กรองแยกกลุ่มทดลอง/กลุ่มตัวอย่าง
        studentGroupDB = {};
        if (Array.isArray(classroomResearchData.students)) {
          classroomResearchData.students.forEach(s => {
            studentGroupDB[s.student_id] = s.student_group || '';
          });
        }
        // แปลง evaluator_type จากภาษาไทย → รหัสอังกฤษ ให้ตรงกับที่โค้ดแดชบอร์ดใช้เทียบ (self/peer/teacher/expert)
        if (Array.isArray(classroomResearchData.evaluations)) {
          const _typeMap = { 'ตนเองประเมิน': 'self', 'เพื่อนประเมิน': 'peer', 'ครูประเมิน': 'teacher', 'ผู้เชี่ยวชาญประเมิน': 'expert' };
          classroomResearchData.evaluations.forEach(ev => {
            if (_typeMap[ev.evaluator_type]) ev.evaluator_type = _typeMap[ev.evaluator_type];
          });
        }
        processDashboardData();
      } else {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-5 fw-bold">เกิดข้อผิดพลาดจาก API: ' + res.error + '</td></tr>';
      }
    } catch (err) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-5 fw-bold">เกิดข้อผิดพลาดในการเชื่อมต่อเครือข่าย: ' + err.message + '</td></tr>';
    }
  }

  function processDashboardData() {
    if (!classroomResearchData) return;

    const studentsList = classroomResearchData.students;
    const evaluations = classroomResearchData.evaluations;

    // จัดกลุ่มประเมินตามรหัสนักเรียนและรอบการประเมิน
    const studentEvals = {};
    studentsList.forEach(s => {
      studentEvals[s.student_id] = {
        pretest: [],
        task1:   [],
        task2:   [],
        posttest: []
      };
    });

    evaluations.forEach(ev => {
      const phase = ev.test_phase;
      if (studentEvals[ev.student_id] && studentEvals[ev.student_id][phase] !== undefined) {
        studentEvals[ev.student_id][phase].push(ev);
      }
    });

    const summaryData = {};

    if (currentDashboardViewMode === 'task1' || currentDashboardViewMode === 'task2') {
      // --- 1. โหมดรายงานภาระงานในหน่วยเรียน (Task 1 หรือ Task 2) ---
      const taskPhaseKey = currentDashboardViewMode; // 'task1' or 'task2'
      studentsList.forEach(s => {
        const id = s.student_id;
        const evs = studentEvals[id][taskPhaseKey] || [];

        const selfEval = evs.find(e => e.evaluator_type === 'self');
        const peerEval = evs.find(e => e.evaluator_type === 'peer');
        const teacherEval = evs.find(e => e.evaluator_type === 'teacher');
        const expert1Eval = evs.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 1' || e.evaluator_name === 'admin1'));
        const expert2Eval = evs.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 2' || e.evaluator_name === 'admin2'));

        let sum_1_1 = 0, sum_1_2 = 0, sum_1_3 = 0;
        let sum_2_1 = 0, sum_2_2 = 0;
        let sum_3_1 = 0, sum_3_2 = 0, sum_3_3 = 0;
        let sum_4_1 = 0, sum_4_2 = 0, sum_4_3 = 0;
        let sum_total = 0;

        evs.forEach(e => {
          sum_1_1 += Number(e.score_1_1);
          sum_1_2 += Number(e.score_1_2);
          sum_1_3 += Number(e.score_1_3);
          sum_2_1 += Number(e.score_2_1);
          sum_2_2 += Number(e.score_2_2);
          sum_3_1 += Number(e.score_3_1);
          sum_3_2 += Number(e.score_3_2);
          sum_3_3 += Number(e.score_3_3);
          sum_4_1 += Number(e.score_4_1);
          sum_4_2 += Number(e.score_4_2);
          sum_4_3 += Number(e.score_4_3);
          sum_total += Number(e.total_score);
        });

        const count = evs.length;
        const avgScore = count > 0 ? (sum_total / count) : 0;

        summaryData[id] = {
          self: !!selfEval,
          peer: !!peerEval,
          teacher: !!teacherEval,
          expert1: !!expert1Eval,
          expert2: !!expert2Eval,
          expert1_score: expert1Eval ? Number(expert1Eval.total_score) : null,
          expert2_score: expert2Eval ? Number(expert2Eval.total_score) : null,
          avgScore: avgScore,
          count: count,
          avg_c: count > 0 ? (sum_1_1 + sum_1_2 + sum_1_3) / count : 0,
          avg_s: count > 0 ? (sum_2_1 + sum_2_2) / count : 0,
          avg_l: count > 0 ? (sum_3_1 + sum_3_2 + sum_3_3) / count : 0,
          avg_m: count > 0 ? (sum_4_1 + sum_4_2 + sum_4_3) / count : 0,
          avg_1_1: count > 0 ? sum_1_1 / count : 0,
          avg_1_2: count > 0 ? sum_1_2 / count : 0,
          avg_1_3: count > 0 ? sum_1_3 / count : 0,
          avg_2_1: count > 0 ? sum_2_1 / count : 0,
          avg_2_2: count > 0 ? sum_2_2 / count : 0,
          avg_3_1: count > 0 ? sum_3_1 / count : 0,
          avg_3_2: count > 0 ? sum_3_2 / count : 0,
          avg_3_3: count > 0 ? sum_3_3 / count : 0,
          avg_4_1: count > 0 ? sum_4_1 / count : 0,
          avg_4_2: count > 0 ? sum_4_2 / count : 0,
          avg_4_3: count > 0 ? sum_4_3 / count : 0
        };
      });
    } else {
      // --- 2. โหมดเปรียบเทียบผลสัมฤทธิ์ก่อน-หลังเรียน (Pre vs Post) ---
      studentsList.forEach(s => {
        const id = s.student_id;
        const preEvs = studentEvals[id]['pretest'] || [];
        const postEvs = studentEvals[id]['posttest'] || [];

        // ใช้การประเมินของครูในการประเมินความสามารถวิชาการก่อน-หลังเรียน
        const preTeacher = preEvs.find(e => e.evaluator_type === 'teacher');
        const postTeacher = postEvs.find(e => e.evaluator_type === 'teacher');

        const preScore = preTeacher ? Number(preTeacher.total_score) : null;
        const postScore = postTeacher ? Number(postTeacher.total_score) : null;
        const gain = (preScore !== null && postScore !== null) ? (postScore - preScore) : null;

        const activeEval = postTeacher || preTeacher;

        summaryData[id] = {
          preScore: preScore,
          postScore: postScore,
          gain: gain,
          avgScore: postScore !== null ? postScore : (preScore !== null ? preScore : 0),
          count: (preTeacher ? 1 : 0) + (postTeacher ? 1 : 0),
          avg_c: activeEval ? Number(activeEval.score_1_1) + Number(activeEval.score_1_2) + Number(activeEval.score_1_3) : 0,
          avg_s: activeEval ? Number(activeEval.score_2_1) + Number(activeEval.score_2_2) : 0,
          avg_l: activeEval ? Number(activeEval.score_3_1) + Number(activeEval.score_3_2) + Number(activeEval.score_3_3) : 0,
          avg_m: activeEval ? Number(activeEval.score_4_1) + Number(activeEval.score_4_2) + Number(activeEval.score_4_3) : 0,
          avg_1_1: activeEval ? Number(activeEval.score_1_1) : 0,
          avg_1_2: activeEval ? Number(activeEval.score_1_2) : 0,
          avg_1_3: activeEval ? Number(activeEval.score_1_3) : 0,
          avg_2_1: activeEval ? Number(activeEval.score_2_1) : 0,
          avg_2_2: activeEval ? Number(activeEval.score_2_2) : 0,
          avg_3_1: activeEval ? Number(activeEval.score_3_1) : 0,
          avg_3_2: activeEval ? Number(activeEval.score_3_2) : 0,
          avg_3_3: activeEval ? Number(activeEval.score_3_3) : 0,
          avg_4_1: activeEval ? Number(activeEval.score_4_1) : 0,
          avg_4_2: activeEval ? Number(activeEval.score_4_2) : 0,
          avg_4_3: activeEval ? Number(activeEval.score_4_3) : 0
        };
      });
    }

    renderCustomTeacherOverview(summaryData);

    // กราฟแมงมุมรายบุคคล 2 กราฟ — ไม่ขึ้นกับโหมดตาราง แสดงทั้ง "ภาระงาน" และ "ก่อน/หลังเรียน" เสมอ
    const taskDimMap = buildDimMapFromEvals(studentEvals, 'task');
    const prepostDimMap = buildDimMapFromEvals(studentEvals, 'prepost');
    drawDimensionSpider(taskDimMap, 'classDimensionSpiderTask', 'task');
    drawDimensionSpider(prepostDimMap, 'classDimensionSpiderPrePost', 'prepost');
  }

  // สร้างแผนที่คะแนนเฉลี่ยรายด้านต่อคน สำหรับกราฟแมงมุม
  //  - mode 'task'    : รวมการประเมิน Task 1 + Task 2 จากผู้ประเมินทุกฝ่าย แล้วเฉลี่ย
  //  - mode 'prepost' : ใช้คะแนนครูประเมิน เลือกหลังเรียนก่อน ถ้าไม่มีจึงใช้ก่อนเรียน
  function buildDimMapFromEvals(studentEvals, mode) {
    const map = {};
    const subKeys = ['1_1','1_2','1_3','2_1','2_2','3_1','3_2','3_3','4_1','4_2','4_3'];
    Object.keys(studentEvals).forEach(id => {
      let evs = [];
      if (mode === 'task') {
        evs = (studentEvals[id]['task1'] || []).concat(studentEvals[id]['task2'] || []);
      } else {
        const post = (studentEvals[id]['posttest'] || []).find(e => e.evaluator_type === 'teacher');
        const pre = (studentEvals[id]['pretest'] || []).find(e => e.evaluator_type === 'teacher');
        const chosen = post || pre;
        evs = chosen ? [chosen] : [];
      }
      const rec = { count: evs.length };
      const sums = {};
      subKeys.forEach(k => sums[k] = 0);
      evs.forEach(e => { subKeys.forEach(k => { sums[k] += Number(e['score_' + k] || 0); }); });
      subKeys.forEach(k => { rec['avg_' + k] = evs.length > 0 ? (sums[k] / evs.length) : 0; });
      map[id] = rec;
    });
    return map;
  }

  function renderCustomTeacherOverview(data) {
    const tbody = document.getElementById('overviewTableBody');
    const thead = document.getElementById('overviewTableHead');
    if (!tbody || !thead) return;
    
    // แสดงผลส่วนหัวตารางแบบไดนามิก
    if (currentDashboardViewMode === 'task1' || currentDashboardViewMode === 'task2') {
      const unitLabel = currentDashboardViewMode === 'task1' ? 'หน่วยที่ 1' : 'หน่วยที่ 2';
      thead.innerHTML = `
        <tr>
          <th class="px-3 py-3" style="width: 10%">รหัสนักเรียน</th>
          <th class="px-3 py-3" style="width: 28%">ชื่อ-สกุลผู้เรียน</th>
          <th class="px-3 py-3 text-center" style="width: 14%">ตนเองประเมิน</th>
          <th class="px-3 py-3 text-center" style="width: 14%">เพื่อนประเมิน</th>
          <th class="px-3 py-3 text-center" style="width: 14%">ครูประเมิน</th>
          <th class="px-3 py-3 text-center" style="width: 8%">เฉลี่ย${unitLabel}</th>
          <th class="px-3 py-3 text-end" style="width: 12%">การจัดการ</th>
        </tr>
      `;
    } else {
      thead.innerHTML = `
        <tr>
          <th class="px-3 py-3" style="width: 15%">รหัสนักเรียน</th>
          <th class="px-3 py-3" style="width: 30%">ชื่อ-สกุลผู้เรียน</th>
          <th class="px-3 py-3 text-center text-danger-emphasis" style="width: 15%">ก่อนเรียน (Pretest)</th>
          <th class="px-3 py-3 text-center text-success-emphasis" style="width: 15%">หลังเรียน (Posttest)</th>
          <th class="px-3 py-3 text-center text-primary" style="width: 15%">คะแนนพัฒนาการ</th>
          <th class="px-3 py-3 text-end" style="width: 10%">การจัดการ</th>
        </tr>
      `;
    }

    tbody.innerHTML = '';

    // กรองเฉพาะนักเรียนในกลุ่มที่เลือก (ทั้งหมด/กลุ่มทดลอง/กลุ่มตัวอย่าง)
    const sortedKeys = Object.keys(studentDB).sort().filter(passesGroupFilter);
    let totalRegistered = sortedKeys.length;
    let totalScoredCount = 0;
    let totalSumScores = 0;
    let activeEvaluationSetCount = 0;

    let qualityCounts = { veryGood: 0, good: 0, fair: 0, pass: 0, poor: 0 };
    let dimensionSums = { content: 0, structure: 0, language: 0, mechanics: 0 };
    let evaluatedStdsCount = 0;
    let subCriteriaSums = {
      '1.1': 0, '1.2': 0, '1.3': 0,
      '2.1': 0, '2.2': 0,
      '3.1': 0, '3.2': 0, '3.3': 0,
      '4.1': 0, '4.2': 0, '4.3': 0
    };

    let html = '';
    sortedKeys.forEach(id => {
      const sData = data[id] || {};

      if (currentDashboardViewMode === 'task1' || currentDashboardViewMode === 'task2') {
        const checkIcon = '<i class="bi bi-check-circle-fill text-success fs-5" title="ส่งแล้ว"></i>';
        const crossIcon = '<span class="text-muted">-</span>';

        if (sData.avgScore > 0) {
          const avg = Number(sData.avgScore);
          totalSumScores += avg;
          totalScoredCount++;

          if (avg >= 49) qualityCounts.veryGood++;
          else if (avg >= 37) qualityCounts.good++;
          else if (avg >= 25) qualityCounts.fair++;
          else if (avg >= 13) qualityCounts.pass++;
          else qualityCounts.poor++;

          dimensionSums.content += Number(sData.avg_c || 0);
          dimensionSums.structure += Number(sData.avg_s || 0);
          dimensionSums.language += Number(sData.avg_l || 0);
          dimensionSums.mechanics += Number(sData.avg_m || 0);

          subCriteriaSums['1.1'] += Number(sData.avg_1_1 || 0);
          subCriteriaSums['1.2'] += Number(sData.avg_1_2 || 0);
          subCriteriaSums['1.3'] += Number(sData.avg_1_3 || 0);
          subCriteriaSums['2.1'] += Number(sData.avg_2_1 || 0);
          subCriteriaSums['2.2'] += Number(sData.avg_2_2 || 0);
          subCriteriaSums['3.1'] += Number(sData.avg_3_1 || 0);
          subCriteriaSums['3.2'] += Number(sData.avg_3_2 || 0);
          subCriteriaSums['3.3'] += Number(sData.avg_3_3 || 0);
          subCriteriaSums['4.1'] += Number(sData.avg_4_1 || 0);
          subCriteriaSums['4.2'] += Number(sData.avg_4_2 || 0);
          subCriteriaSums['4.3'] += Number(sData.avg_4_3 || 0);
          evaluatedStdsCount++;
        }

        if (sData.self && sData.peer && sData.teacher) {
          activeEvaluationSetCount++;
        }

        html += `
          <tr class="hover-row cursor-pointer" onclick="viewStudentDetail('${id}')">
            <td class="px-3 py-3 font-mono fw-bold text-secondary">${id}</td>
            <td class="px-3 py-3 fw-bold text-dark text-start">${studentDB[id]}</td>
            <td class="px-3 py-3 text-center">${sData.self ? checkIcon : crossIcon}</td>
            <td class="px-3 py-3 text-center">${sData.peer ? checkIcon : crossIcon}</td>
            <td class="px-3 py-3 text-center">${sData.teacher ? checkIcon : crossIcon}</td>
            <td class="px-3 py-3 text-center fw-extrabold ${sData.avgScore > 0 ? 'text-primary bg-light-blue' : 'text-muted'}">${sData.avgScore > 0 ? sData.avgScore.toFixed(2) : '-'}</td>
            <td class="px-3 py-3 text-end">
               <button class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3" onclick="event.stopPropagation(); viewStudentDetail('${id}')">วิเคราะห์</button>
            </td>
          </tr>
        `;
      } else {
        // prepost mode row rendering
        const preDisp = sData.preScore !== null ? sData.preScore.toFixed(2) : '-';
        const postDisp = sData.postScore !== null ? sData.postScore.toFixed(2) : '-';
        let gainDisp = '-';
        let gainClass = 'text-muted';
        if (sData.gain !== null) {
          if (sData.gain > 0) {
            gainDisp = `+${sData.gain.toFixed(2)}`;
            gainClass = 'text-success fw-bold';
          } else if (sData.gain < 0) {
            gainDisp = `${sData.gain.toFixed(2)}`;
            gainClass = 'text-danger fw-bold';
          } else {
            gainDisp = '0.00';
            gainClass = 'text-secondary';
          }
        }

        if (sData.postScore !== null) {
          const postVal = Number(sData.postScore);
          totalSumScores += postVal;
          totalScoredCount++;

          if (postVal >= 49) qualityCounts.veryGood++;
          else if (postVal >= 37) qualityCounts.good++;
          else if (postVal >= 25) qualityCounts.fair++;
          else if (postVal >= 13) qualityCounts.pass++;
          else qualityCounts.poor++;

          dimensionSums.content += Number(sData.avg_c || 0);
          dimensionSums.structure += Number(sData.avg_s || 0);
          dimensionSums.language += Number(sData.avg_l || 0);
          dimensionSums.mechanics += Number(sData.avg_m || 0);

          subCriteriaSums['1.1'] += Number(sData.avg_1_1 || 0);
          subCriteriaSums['1.2'] += Number(sData.avg_1_2 || 0);
          subCriteriaSums['1.3'] += Number(sData.avg_1_3 || 0);
          subCriteriaSums['2.1'] += Number(sData.avg_2_1 || 0);
          subCriteriaSums['2.2'] += Number(sData.avg_2_2 || 0);
          subCriteriaSums['3.1'] += Number(sData.avg_3_1 || 0);
          subCriteriaSums['3.2'] += Number(sData.avg_3_2 || 0);
          subCriteriaSums['3.3'] += Number(sData.avg_3_3 || 0);
          subCriteriaSums['4.1'] += Number(sData.avg_4_1 || 0);
          subCriteriaSums['4.2'] += Number(sData.avg_4_2 || 0);
          subCriteriaSums['4.3'] += Number(sData.avg_4_3 || 0);
          evaluatedStdsCount++;
        }

        html += `
          <tr class="hover-row cursor-pointer" onclick="viewStudentDetail('${id}')">
            <td class="px-3 py-3 font-mono fw-bold text-secondary">${id}</td>
            <td class="px-3 py-3 fw-bold text-dark text-start">${studentDB[id]}</td>
            <td class="px-3 py-3 text-center font-mono fw-semibold">${preDisp}</td>
            <td class="px-3 py-3 text-center font-mono fw-semibold">${postDisp}</td>
            <td class="px-3 py-3 text-center font-mono ${gainClass}">${gainDisp}</td>
            <td class="px-3 py-3 text-end">
               <button class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3" onclick="event.stopPropagation(); viewStudentDetail('${id}')">วิเคราะห์</button>
            </td>
          </tr>
        `;
      }
    });

    tbody.innerHTML = html;

    document.getElementById('statTotalStudents').textContent = totalRegistered + " คน";
    document.getElementById('statClassAvg').textContent = totalScoredCount > 0 ? (totalSumScores / totalScoredCount).toFixed(2) + " / 60" : "0 / 60";
    
    // In prepost mode, completion means having both pre and post evaluations from the teacher
    let completionPercentage = 0;
    if (currentDashboardViewMode === 'task1' || currentDashboardViewMode === 'task2') {
      completionPercentage = totalRegistered > 0 ? Math.round((activeEvaluationSetCount / totalRegistered) * 100) : 0;
    } else {
      // นับเฉพาะนักเรียนในกลุ่มที่เลือก เพื่อให้ตรงกับจำนวนผู้เรียนที่ใช้เป็นตัวหาร (totalRegistered)
      let bothTeacherCount = sortedKeys.filter(id => data[id] && data[id].preScore !== null && data[id].postScore !== null).length;
      completionPercentage = totalRegistered > 0 ? Math.round((bothTeacherCount / totalRegistered) * 100) : 0;
    }
    document.getElementById('statCompletion').textContent = completionPercentage + "%";

    drawClassQualityDistribution(qualityCounts);
    drawClassDimensionAverages(dimensionSums, evaluatedStdsCount, subCriteriaSums);
    generateResearchInsights(subCriteriaSums, evaluatedStdsCount, totalRegistered, totalSumScores, totalScoredCount, activeEvaluationSetCount);
  }

  function switchDashboardViewMode() {
    const selector = document.getElementById('dashboardViewMode');
    if (!selector) return;
    currentDashboardViewMode = selector.value;
    processDashboardData();
  }

  function switchStudentPhase() {
    const studentId = document.getElementById('dashStudentId').textContent;
    if (!studentId) return;
    
    // Fetch individual student scores for selected phase
    fetchScores(studentId, studentDB[studentId]);
    fetchReflectionData(studentId);
  }

  function generateResearchInsights(subCriteriaSums, evaluatedStdsCount, totalRegistered, totalSumScores, totalScoredCount, activeEvaluationSetCount) {
    const quantDiv = document.getElementById('quantitativeReport');
    const pedagDiv = document.getElementById('pedagogicalReport');
    if (!quantDiv || !pedagDiv) return;

    if (evaluatedStdsCount > 0) {
      const subCriteriaMeta = {
        '1.1': { name: '1.1 ความตรงประเด็น', max: 12 },
        '1.2': { name: '1.2 แก่นเรื่องชัดเจน', max: 6 },
        '1.3': { name: '1.3 การขยายความและเหตุผล', max: 9 },
        '2.1': { name: '2.1 ความครบถ้วนขององค์ประกอบ', max: 8 },
        '2.2': { name: '2.2 การลำดับประเด็นเป็นระบบ', max: 4 },
        '3.1': { name: '3.1 การใช้ประโยคถูกต้อง', max: 4 },
        '3.2': { name: '3.2 การเลือกใช้คำ', max: 6 },
        '3.3': { name: '3.3 ระดับภาษาเหมาะสม', max: 5 },
        '4.1': { name: '4.1 การสะกดคำถูกต้อง', max: 2 },
        '4.2': { name: '4.2 การเว้นวรรค', max: 2 },
        '4.3': { name: '4.3 ความเรียบร้อย', max: 2 }
      };

      let subCriteriaPcts = [];
      for (const [key, value] of Object.entries(subCriteriaMeta)) {
        const avgRaw = subCriteriaSums[key] / evaluatedStdsCount;
        const pct = (avgRaw / value.max) * 100;
        subCriteriaPcts.push({ key, name: value.name, pct, avgRaw, max: value.max });
      }

      // เรียงลำดับจากคะแนนสูงสุดไปต่ำสุด
      subCriteriaPcts.sort((a, b) => b.pct - a.pct);
      const highest = subCriteriaPcts[0];
      const lowest = subCriteriaPcts[subCriteriaPcts.length - 1];

      const classAvg = (totalSumScores / totalScoredCount).toFixed(2);
      
      quantDiv.innerHTML = `
        <ul class="mb-0 ps-3">
          <li class="mb-1"><strong>ผู้เรียนที่ประเมินแล้ว:</strong> ${evaluatedStdsCount} จาก ${totalRegistered} คน (${Math.round(evaluatedStdsCount/totalRegistered*100)}%)</li>
          <li class="mb-1"><strong>คะแนนเฉลี่ยรวมของชั้นเรียน:</strong> <span class="text-primary fw-bold">${classAvg} / 60 คะแนน</span></li>
          <li class="mb-1"><strong>ประเมินครบ 360° (ตนเอง+เพื่อน+ครู):</strong> ${activeEvaluationSetCount} คน (${Math.round(activeEvaluationSetCount/totalRegistered*100)}%)</li>
          <li class="mb-1"><strong>จุดเด่นสูงสุดของห้องเรียน:</strong> <span class="text-success fw-bold">${highest.name}</span> (เฉลี่ยร้อยละ ${highest.pct.toFixed(1)}% หรือ ${highest.avgRaw.toFixed(2)}/${highest.max} คะแนน)</li>
          <li class="mb-1"><strong>จุดบกพร่องที่ควรพัฒนาเร่งด่วน:</strong> <span class="text-danger fw-bold">${lowest.name}</span> (เฉลี่ยร้อยละ ${lowest.pct.toFixed(1)}% หรือ ${lowest.avgRaw.toFixed(2)}/${lowest.max} คะแนน)</li>
        </ul>
      `;

      let actionSteps = '';
      if (lowest.key.startsWith('1')) {
        actionSteps = `เน้นกระบวนการจัดทำแผนผังความคิด (Concept Map) และฝึกฝนการเขียนร่างแยกย่อยรายโครงสร้างเนื้อหา รวมถึงขยายตัวอย่างประโยคเชื่อมคำเชื่อมเพื่อลดการเขียนนอกประเด็น`;
      } else if (lowest.key.startsWith('2')) {
        actionSteps = `จัดทักษะการเรียนรู้แบบร่วมมือ ฝึกจัดกลุ่มหัวข้อหลักย่อย (Paragraph Outline Co-op) และการจัดทำคู่มือสัญลักษณ์การสะกด/ส่วนประกอบการเขียนเพื่อสร้างวินัยลำดับความคิด`;
      } else if (lowest.key.startsWith('3')) {
        actionSteps = `ออกแบบบัตรคำเรียนรู้ระดับคำศัพท์และสำนวน (Vocabulary Map & Word Wall) และจัดกิจกรรมแก้ไขประโยคกำกวม (Sentence Doctor) เพื่อยกระดับความเฉียบคมในการเลือกใช้ระดับภาษา`;
      } else {
        actionSteps = `ปรับปรุงโดยทำกิจกรรมเพื่อนช่วยตรวจสอบ (Peer Proofreading Checklist) รวบรวมคลังคำสะกดผิดยอดนิยม จัดทำคลินิกอักขรวิธี และส่งเสริมให้นักเรียนตรวจสอบการจัดวางวรรคตอนก่อนส่งงานจริง`;
      }

      pedagDiv.innerHTML = `
        <div class="mb-2"><strong>หัวข้อวิจัยในชั้นเรียนที่เหมาะสมสำหรับกลุ่มเรียนนี้:</strong></div>
        <div class="fw-bold text-dark mb-2">"การพัฒนาความสามารถในการเขียนเรียงความภาษาไทยของนักเรียน โดยจัดกิจกรรมแก้ปัญหาเชิงประจักษ์ในด้าน '${lowest.name.substring(4)}'"</div>
        <div class="mb-1"><strong>แนวทางการลงมือปฏิบัติเชิงวิจัยคลาสรูม:</strong></div>
        <div class="text-secondary small" style="line-height: 1.5;">${actionSteps}</div>
      `;
    } else {
      quantDiv.innerHTML = 'ไม่มีข้อมูลสถิติที่ประเมินแล้วสำหรับการวิเคราะห์เชิงปริมาณ';
      pedagDiv.innerHTML = 'โปรดบันทึกผลงานประเมินนักเรียนในระบบอย่างน้อย 1 รายการเพื่อรับข้อเสนอแนะในการวิจัยชั้นเรียน';
    }
  }

  function drawClassQualityDistribution(counts) {
    const canvas = document.getElementById('classQualityDistributionChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (classQualityChartInstance) classQualityChartInstance.destroy();
    
    classQualityChartInstance = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['ดีมาก (49-60 คะแนน)', 'ดี (37-48 คะแนน)', 'ปานกลาง (25-36 คะแนน)', 'พอใช้ (13-24 คะแนน)', 'ปรับปรุง (<13 คะแนน)'],
        datasets: [{
          data: [counts.veryGood, counts.good, counts.fair, counts.pass, counts.poor],
          backgroundColor: ['#059669', '#2563eb', '#ea580c', '#94a3b8', '#ef4444'],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, font: { family: 'Google Sans', size: 10 } }
          }
        }
      }
    });
  }

  function drawClassDimensionAverages(sums, count, subSums) {
    const canvas = document.getElementById('classDimensionAveragesChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (classDimensionChartInstance) classDimensionChartInstance.destroy();

    subSums = subSums || {};

    // โครงสร้างข้อมูล: แต่ละมิติหลัก (แท่งเข้ม) ตามด้วยเกณฑ์ย่อยของมิตินั้น (แท่งอ่อน)
    // เพื่อให้เห็นทั้งภาพรวมรายด้านและรายละเอียดแต่ละเกณฑ์ย่อยในกราฟเดียว
    const groups = [
      {
        main: { label: 'เนื้อหา (27)', value: sums.content, max: 27 },
        solid: '#2563eb', soft: 'rgba(37, 99, 235, 0.40)',
        subs: [
          { key: '1.1', label: '1.1 ตรงประเด็น (12)', max: 12 },
          { key: '1.2', label: '1.2 แก่นเรื่อง (6)', max: 6 },
          { key: '1.3', label: '1.3 ขยายความ (9)', max: 9 }
        ]
      },
      {
        main: { label: 'โครงสร้าง (12)', value: sums.structure, max: 12 },
        solid: '#8b5cf6', soft: 'rgba(139, 92, 246, 0.40)',
        subs: [
          { key: '2.1', label: '2.1 องค์ประกอบครบ (8)', max: 8 },
          { key: '2.2', label: '2.2 ลำดับประเด็น (4)', max: 4 }
        ]
      },
      {
        main: { label: 'ภาษา (15)', value: sums.language, max: 15 },
        solid: '#f59e0b', soft: 'rgba(245, 158, 11, 0.40)',
        subs: [
          { key: '3.1', label: '3.1 ประโยคถูกต้อง (4)', max: 4 },
          { key: '3.2', label: '3.2 เลือกใช้คำ (6)', max: 6 },
          { key: '3.3', label: '3.3 ระดับภาษา (5)', max: 5 }
        ]
      },
      {
        main: { label: 'อักขรวิธี (6)', value: sums.mechanics, max: 6 },
        solid: '#10b981', soft: 'rgba(16, 185, 129, 0.40)',
        subs: [
          { key: '4.1', label: '4.1 สะกดคำ (2)', max: 2 },
          { key: '4.2', label: '4.2 เว้นวรรค (2)', max: 2 },
          { key: '4.3', label: '4.3 เรียบร้อย (2)', max: 2 }
        ]
      }
    ];

    const labels = [];
    const dataPct = [];
    const bgColors = [];
    const borderColors = [];
    const meta = []; // เก็บ {avg, max, isMain} ไว้ใช้ใน tooltip

    groups.forEach(g => {
      const mainAvg = count > 0 ? (Number(g.main.value || 0) / count) : 0;
      labels.push('▎' + g.main.label);
      dataPct.push(parseFloat(((mainAvg / g.main.max) * 100).toFixed(2)));
      bgColors.push(g.solid);
      borderColors.push(g.solid);
      meta.push({ avg: mainAvg, max: g.main.max, isMain: true });

      g.subs.forEach(sub => {
        const subAvg = count > 0 ? (Number(subSums[sub.key] || 0) / count) : 0;
        labels.push(sub.label);
        dataPct.push(parseFloat(((subAvg / sub.max) * 100).toFixed(2)));
        bgColors.push(g.soft);
        borderColors.push(g.solid);
        meta.push({ avg: subAvg, max: sub.max, isMain: false });
      });
    });

    classDimensionChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'ร้อยละคะแนนเฉลี่ย',
          data: dataPct,
          backgroundColor: bgColors,
          borderColor: borderColors,
          borderWidth: 1.5
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const val = context.parsed.y;
                const m = meta[context.dataIndex] || {};
                const rawStr = (m.max ? ` (${(m.avg || 0).toFixed(2)}/${m.max} คะแนน)` : '');
                const tag = m.isMain ? 'มิติหลัก' : 'เกณฑ์ย่อย';
                return `[${tag}] คิดเป็นร้อยละ: ${val}%${rawStr}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: { stepSize: 20 },
            title: { display: true, text: 'ค่าร้อยละของคะแนนเต็ม (%)', font: { family: 'Google Sans', size: 10, weight: 'bold' } }
          },
          x: {
            ticks: {
              font: { family: 'Google Sans', size: 8 },
              maxRotation: 90,
              minRotation: 55,
              autoSkip: false
            }
          }
        }
      }
    });
  }

  // วาดกราฟแมงมุมรายบุคคล (ใช้ร่วมกันทั้งกราฟ "ภาระงาน" และ "ก่อน/หลังเรียน")
  //  dataMap  = { id: {count, avg_1_1..avg_4_3} }
  //  canvasId = id ของ <canvas>
  //  key      = 'task' | 'prepost' เพื่อเก็บอินสแตนซ์แยกกัน
  function drawDimensionSpider(dataMap, canvasId, key) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (spiderChartInstances[key]) spiderChartInstances[key].destroy();

    const datasets = [];
    // กรองเฉพาะนักเรียนในกลุ่มที่เลือกให้ตรงกับตารางและกราฟอื่น
    const sortedKeys = Object.keys(studentDB).sort().filter(passesGroupFilter);

    let lineCount = 0;
    sortedKeys.forEach(id => {
      const studentData = dataMap && dataMap[id] ? dataMap[id] : null;
      // วาดรูปใยแมงมุมของนักเรียนทันทีที่มีการประเมินอย่างน้อย 1 รายการในหมวดนี้
      if (studentData && studentData.count > 0) {
        const pct11 = (parseFloat(studentData.avg_1_1) / 12) * 100;
        const pct12 = (parseFloat(studentData.avg_1_2) / 6) * 100;
        const pct13 = (parseFloat(studentData.avg_1_3) / 9) * 100;
        const pct21 = (parseFloat(studentData.avg_2_1) / 8) * 100;
        const pct22 = (parseFloat(studentData.avg_2_2) / 4) * 100;
        const pct31 = (parseFloat(studentData.avg_3_1) / 4) * 100;
        const pct32 = (parseFloat(studentData.avg_3_2) / 6) * 100;
        const pct33 = (parseFloat(studentData.avg_3_3) / 5) * 100;
        const pct41 = (parseFloat(studentData.avg_4_1) / 2) * 100;
        const pct42 = (parseFloat(studentData.avg_4_2) / 2) * 100;
        const pct43 = (parseFloat(studentData.avg_4_3) / 2) * 100;
        
        const hue = (lineCount * 33) % 360;
        const colorDefault = `hsla(${hue}, 60%, 60%, 0.18)`; // เส้นใยจางลงโดยเริ่มต้นเพื่อให้อ่านง่ายเมื่อมีนักเรียนหลายคน
        const colorHover = `hsla(${hue}, 85%, 45%, 1.0)`; // เส้นใยเข้มขึ้นชัดเจนเมื่อเอาเมาส์ชี้ (Hover)
        const fillHover = `hsla(${hue}, 85%, 55%, 0.18)`; // เติมสีในรูปใยจาง ๆ เมื่อชี้เพื่อเน้นรูปทรงของนักเรียนคนนั้น

        datasets.push({
          label: `${id} - ${studentDB[id]}`,
          data: [
            parseFloat(pct11.toFixed(2)),
            parseFloat(pct12.toFixed(2)),
            parseFloat(pct13.toFixed(2)),
            parseFloat(pct21.toFixed(2)),
            parseFloat(pct22.toFixed(2)),
            parseFloat(pct31.toFixed(2)),
            parseFloat(pct32.toFixed(2)),
            parseFloat(pct33.toFixed(2)),
            parseFloat(pct41.toFixed(2)),
            parseFloat(pct42.toFixed(2)),
            parseFloat(pct43.toFixed(2))
          ],
          borderColor: colorDefault,
          borderWidth: 1.5,
          backgroundColor: 'transparent',
          fill: true,
          tension: 0.05,
          pointBackgroundColor: colorDefault,
          pointRadius: 1,
          pointHoverRadius: 5,
          pointHoverBackgroundColor: colorHover,
          hoverBorderColor: colorHover,
          hoverBackgroundColor: fillHover,
          hoverBorderWidth: 3
        });
        lineCount++;
      }
    });
    
    spiderChartInstances[key] = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: [
          '1.1 ตรงประเด็น (12)',
          '1.2 แก่นเรื่อง (6)',
          '1.3 ขยายความ (9)',
          '2.1 องค์ประกอบครบ (8)',
          '2.2 ลำดับประเด็น (4)',
          '3.1 ประโยคถูกต้อง (4)',
          '3.2 เลือกใช้คำ (6)',
          '3.3 ระดับภาษา (5)',
          '4.1 สะกดคำ (2)',
          '4.2 เว้นวรรค (2)',
          '4.3 เรียบร้อย (2)'
        ],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'dataset',
          intersect: false
        },
        plugins: {
          legend: { display: false }, // ซ่อนคำอธิบายใต้กราฟเนื่องจากนักเรียนมีจำนวนมาก เพื่อความสะอาดตา
          tooltip: {
            mode: 'nearest',
            intersect: false,
            callbacks: {
              label: function(context) {
                const studentInfo = context.dataset.label;
                const val = context.parsed.r;
                let rawStr = '';
                
                if (context.dataIndex === 0) rawStr = ` (${(val/100*12).toFixed(2)}/12 คะแนน)`;
                else if (context.dataIndex === 1) rawStr = ` (${(val/100*6).toFixed(2)}/6 คะแนน)`;
                else if (context.dataIndex === 2) rawStr = ` (${(val/100*9).toFixed(2)}/9 คะแนน)`;
                else if (context.dataIndex === 3) rawStr = ` (${(val/100*8).toFixed(2)}/8 คะแนน)`;
                else if (context.dataIndex === 4) rawStr = ` (${(val/100*4).toFixed(2)}/4 คะแนน)`;
                else if (context.dataIndex === 5) rawStr = ` (${(val/100*4).toFixed(2)}/4 คะแนน)`;
                else if (context.dataIndex === 6) rawStr = ` (${(val/100*6).toFixed(2)}/6 คะแนน)`;
                else if (context.dataIndex === 7) rawStr = ` (${(val/100*5).toFixed(2)}/5 คะแนน)`;
                else if (context.dataIndex === 8) rawStr = ` (${(val/100*2).toFixed(2)}/2 คะแนน)`;
                else if (context.dataIndex === 9) rawStr = ` (${(val/100*2).toFixed(2)}/2 คะแนน)`;
                else if (context.dataIndex === 10) rawStr = ` (${(val/100*2).toFixed(2)}/2 คะแนน)`;
                
                // ค้นหาจำนวนรายการประเมินที่นำมาเฉลี่ยในหมวดนี้
                const studentId = studentInfo.split(' - ')[0];
                const studentData = dataMap && dataMap[studentId] ? dataMap[studentId] : null;
                const countStr = studentData ? ` [ประเมิน ${studentData.count} รายการ]` : '';

                return `${studentInfo}: ${val}%${rawStr}${countStr}`;
              }
            }
          }
        },
        scales: {
          r: {
            beginAtZero: true,
            min: 0,
            max: 100,
            ticks: {
              stepSize: 20,
              font: { family: 'Google Sans', size: 9 },
              backdropColor: 'rgba(255, 255, 255, 0.75)',
              callback: function(value) { return value + '%'; }
            },
            pointLabels: { font: { family: 'Google Sans', size: 9.5, weight: 'bold' } },
            grid: { color: 'rgba(0, 0, 0, 0.06)' },
            angleLines: { color: 'rgba(0, 0, 0, 0.08)' }
          }
        }
      }
    });
  }

  function filterTeacherTable() {
    const input = document.getElementById('teacherSearchInput');
    const filter = document.getElementById('teacherStatusFilter');
    if (!input || !filter) return;
    
    const query = input.value.toLowerCase().trim();
    const filterVal = filter.value;
    const rows = document.querySelectorAll('#overviewTableBody tr');
    
    rows.forEach(row => {
      if (currentDashboardViewMode === 'task1' || currentDashboardViewMode === 'task2') {
        if(row.cells.length < 7) return;

        const studentId = row.cells[0].textContent.toLowerCase();
        const name = row.cells[1].textContent.toLowerCase();

        const hasSelf = row.cells[2].innerHTML.includes('bi-check-circle-fill');
        const hasPeer = row.cells[3].innerHTML.includes('bi-check-circle-fill');
        const hasTeacher = row.cells[4].innerHTML.includes('bi-check-circle-fill');
        
        const matchesSearch = studentId.includes(query) || name.includes(query);
        
        let matchesFilter = true;
        if (filterVal === 'complete') {
          matchesFilter = (hasSelf && hasPeer && hasTeacher);
        } else if (filterVal === 'incomplete') {
          matchesFilter = !(hasSelf && hasPeer && hasTeacher);
        } else if (filterVal === 'no_eval') {
          matchesFilter = (!hasSelf && !hasPeer && !hasTeacher);
        } else if (filterVal === 'missing_teacher') {
          matchesFilter = !hasTeacher;
        }
        
        if (matchesSearch && matchesFilter) {
          row.classList.remove('d-none');
        } else {
          row.classList.add('d-none');
        }
      } else {
        if(row.cells.length < 6) return;

        const studentId = row.cells[0].textContent.toLowerCase();
        const name = row.cells[1].textContent.toLowerCase();
        
        const hasPre = row.cells[2].textContent.trim() !== '-';
        const hasPost = row.cells[3].textContent.trim() !== '-';
        
        const matchesSearch = studentId.includes(query) || name.includes(query);
        
        let matchesFilter = true;
        if (filterVal === 'complete') {
          matchesFilter = (hasPre && hasPost);
        } else if (filterVal === 'incomplete') {
          matchesFilter = !(hasPre && hasPost);
        } else if (filterVal === 'no_eval') {
          matchesFilter = (!hasPre && !hasPost);
        } else if (filterVal === 'missing_teacher') {
          matchesFilter = !hasPost;
        }
        
        if (matchesSearch && matchesFilter) {
          row.classList.remove('d-none');
        } else {
          row.classList.add('d-none');
        }
      }
    });
  }

  function viewStudentDetail(id) {
    document.getElementById('teacherOverview').classList.add('d-none');
    document.getElementById('individualReportHeader').classList.remove('d-none');
    fetchScores(id, studentDB[id]);
  }

  function showTeacherOverviewOnly() {
    document.getElementById('searchResults').classList.add('d-none');
    document.getElementById('searchNotFound').classList.add('d-none');
    document.getElementById('individualReportHeader').classList.add('d-none');
    document.getElementById('teacherOverview').classList.remove('d-none');
  }

  // --- ระบบดึงรายงานและกราฟระดับวิเคราะห์ผู้เรียน (360° Assessment Analysis) ---
  async function fetchScores(studentId, defaultName) {
    document.getElementById('searchResults').classList.add('d-none');
    document.getElementById('searchNotFound').classList.add('d-none');
    document.getElementById('searchLoading').classList.remove('d-none');

    document.getElementById('dashStudentId').textContent = studentId;
    document.getElementById('dashStudentName').textContent = "กำลังวิเคราะห์...";

    const selector = document.getElementById('studentPhaseSelector');
    const phase = selector ? selector.value : currentResearchPhase;

    try {
      const response = await fetch(`api.php?action=get_student_scores&studentId=${studentId}&testPhase=${phase}&_t=${new Date().getTime()}`);
      const res = await response.json();
      renderSearchResults(res);
    } catch (err) {
      document.getElementById('searchLoading').classList.add('d-none');
      document.getElementById('searchNotFound').classList.remove('d-none');
      document.getElementById('dashStudentName').textContent = defaultName || "ไม่พบรายงาน";
    }
  }

  function renderSearchResults(res) {
    document.getElementById('searchLoading').classList.add('d-none');
    
    if(!res.success || !res.data || res.data.length === 0) {
      document.getElementById('dashStudentName').textContent = studentDB[document.getElementById('dashStudentId').textContent] || "ไม่พบผู้เรียน";
      document.getElementById('searchNotFound').classList.remove('d-none');
      return;
    }

    document.getElementById('dashStudentName').textContent = res.data[0].studentName;
    
    // เคลียร์ผล
    document.getElementById('cardSelfScore').textContent = "-";
    document.getElementById('cardSelfName').textContent = "-";
    document.getElementById('cardSelfLevel').className = "badge bg-secondary px-3 py-2";
    document.getElementById('cardSelfLevel').textContent = "ไม่มีข้อมูล";

    document.getElementById('cardPeerScore').textContent = "-";
    document.getElementById('cardPeerName').textContent = "-";
    document.getElementById('cardPeerLevel').className = "badge bg-secondary px-3 py-2";
    document.getElementById('cardPeerLevel').textContent = "ไม่มีข้อมูล";

    document.getElementById('cardTeacherScore').textContent = "-";
    document.getElementById('cardTeacherName').textContent = "-";
    document.getElementById('cardTeacherLevel').className = "badge bg-secondary px-3 py-2";
    document.getElementById('cardTeacherLevel').textContent = "ไม่มีข้อมูล";

    let accumulatedTotalScore = 0;
    let evaluatedRolesCount = res.data.length;

    let selfRecord = null;
    let peerRecord = null;
    let teacherRecord = null;

    res.data.forEach(item => {
      accumulatedTotalScore += parseFloat(item.totalScore);
      
      let targetScoreEl, targetNameEl, targetLevelEl;
      
      if (item.evaluatorType === 'ตนเองประเมิน') {
        selfRecord = item;
        targetScoreEl = document.getElementById('cardSelfScore');
        targetNameEl = document.getElementById('cardSelfName');
        targetLevelEl = document.getElementById('cardSelfLevel');
      } else if (item.evaluatorType === 'เพื่อนประเมิน') {
        peerRecord = item;
        targetScoreEl = document.getElementById('cardPeerScore');
        targetNameEl = document.getElementById('cardPeerName');
        targetLevelEl = document.getElementById('cardPeerLevel');
      } else if (item.evaluatorType === 'ครูประเมิน') {
        teacherRecord = item;
        targetScoreEl = document.getElementById('cardTeacherScore');
        targetNameEl = document.getElementById('cardTeacherName');
        targetLevelEl = document.getElementById('cardTeacherLevel');
      }

      if(targetScoreEl) {
        targetScoreEl.textContent = item.totalScore + " / 60";
        targetNameEl.textContent = item.evaluatorName;
        
        let badgeClass = 'bg-secondary';
        if(item.qualityLevel === 'ดีมาก') badgeClass = 'badge-teal';
        else if(item.qualityLevel === 'ดี') badgeClass = 'badge-blue';
        else if(item.qualityLevel === 'ปานกลาง') badgeClass = 'badge-coral';
        else if(item.qualityLevel === 'พอใช้') badgeClass = 'bg-secondary text-white';
        else if(item.qualityLevel === 'ต้องปรับปรุง') badgeClass = 'bg-danger text-white';

        targetLevelEl.className = `badge ${badgeClass} px-3 py-2`;
        targetLevelEl.textContent = `ระดับ: ${item.qualityLevel}`;
      }
    });

    let finalAvgScore = accumulatedTotalScore / evaluatedRolesCount;
    document.getElementById('consolScore').innerHTML = `${finalAvgScore.toFixed(2)} <span class="fs-6 fw-normal opacity-50">/ 60</span>`;
    
    let finalLevelText = '';
    let finalColorClass = '';
    if(finalAvgScore >= 49) { finalLevelText = 'ดีมาก'; finalColorClass = 'badge-teal text-success'; }
    else if(finalAvgScore >= 37) { finalLevelText = 'ดี'; finalColorClass = 'badge-blue text-primary'; }
    else if(finalAvgScore >= 25) { finalLevelText = 'ปานกลาง'; finalColorClass = 'badge-coral text-warning'; }
    else if(finalAvgScore >= 13) { finalLevelText = 'พอใช้'; finalColorClass = 'bg-secondary text-white'; }
    else { finalLevelText = 'ต้องปรับปรุง'; finalColorClass = 'bg-danger text-white'; }

    const consolDisplay = document.getElementById('consolQuality');
    consolDisplay.className = `fw-bold py-2 rounded text-center shadow-inner ${finalColorClass}`;
    consolDisplay.textContent = `คุณภาพรวม: ${finalLevelText}`;

    // เปิดหน้าผลรายบุคคลให้เห็นก่อน เพื่อไม่ให้กราฟที่ error ตัวใดตัวหนึ่งบังทั้งหน้า (ข้อมูลหาย)
    document.getElementById('searchResults').classList.remove('d-none');

    try { drawScoresChart(selfRecord, peerRecord, teacherRecord); } catch (e) { console.error('drawScoresChart', e); }
    try { drawRadarChart(selfRecord, peerRecord, teacherRecord); } catch (e) { console.error('drawRadarChart', e); }
    try { runConsistencyAnalysis(selfRecord, peerRecord, teacherRecord); } catch (e) { console.error('runConsistencyAnalysis', e); }
    try { renderConsistencyTable(selfRecord, peerRecord, teacherRecord); } catch (e) { console.error('renderConsistencyTable', e); }
    try { document.getElementById('feedbackContainer').innerHTML = generateQualitativeFeedback(res.data); } catch (e) { console.error('generateQualitativeFeedback', e); }

    // โหลดข้อมูลสะท้อนคิด (reflection data)
    try { fetchReflectionData(document.getElementById('dashStudentId').textContent); } catch (e) { console.error('fetchReflectionData', e); }
  }

  function drawScoresChart(selfData, peerData, teacherData) {
    const canvas = document.getElementById('scoresChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (scoresChartInstance) scoresChartInstance.destroy();
    
    const selfScores = selfData ? selfData.rawScores : { content: 0, structure: 0, language: 0, mechanics: 0 };
    const peerScores = peerData ? peerData.rawScores : { content: 0, structure: 0, language: 0, mechanics: 0 };
    const teacherScores = teacherData ? teacherData.rawScores : { content: 0, structure: 0, language: 0, mechanics: 0 };
    
    const selfC = selfScores.content ? (selfScores.content / 27 * 100).toFixed(2) : 0;
    const selfS = selfScores.structure ? (selfScores.structure / 12 * 100).toFixed(2) : 0;
    const selfL = selfScores.language ? (selfScores.language / 15 * 100).toFixed(2) : 0;
    const selfM = selfScores.mechanics ? (selfScores.mechanics / 6 * 100).toFixed(2) : 0;

    const peerC = peerScores.content ? (peerScores.content / 27 * 100).toFixed(2) : 0;
    const peerS = peerScores.structure ? (peerScores.structure / 12 * 100).toFixed(2) : 0;
    const peerL = peerScores.language ? (peerScores.language / 15 * 100).toFixed(2) : 0;
    const peerM = peerScores.mechanics ? (peerScores.mechanics / 6 * 100).toFixed(2) : 0;

    const teachC = teacherScores.content ? (teacherScores.content / 27 * 100).toFixed(2) : 0;
    const teachS = teacherScores.structure ? (teacherScores.structure / 12 * 100).toFixed(2) : 0;
    const teachL = teacherScores.language ? (teacherScores.language / 15 * 100).toFixed(2) : 0;
    const teachM = teacherScores.mechanics ? (teacherScores.mechanics / 6 * 100).toFixed(2) : 0;

    scoresChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['1. เนื้อหาสาระ (เต็ม 27)', '2. องค์ประกอบ (เต็ม 12)', '3. สำนวนภาษา (เต็ม 15)', '4. อักขรวิธี (เต็ม 6)'],
        datasets: [
          {
            label: '🙋‍♂️ ตนเองประเมิน',
            data: [selfC, selfS, selfL, selfM],
            backgroundColor: 'rgba(37, 99, 235, 0.8)',
            borderColor: '#2563eb',
            borderWidth: 1.5
          },
          {
            label: '👥 เพื่อนช่วยประเมิน',
            data: [peerC, peerS, peerL, peerM],
            backgroundColor: 'rgba(249, 115, 22, 0.8)',
            borderColor: '#f97316',
            borderWidth: 1.5
          },
          {
            label: '👩‍🏫 ครูผู้สอนประเมิน',
            data: [teachC, teachS, teachL, teachM],
            backgroundColor: 'rgba(5, 150, 105, 0.8)',
            borderColor: '#059669',
            borderWidth: 1.5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { font: { family: 'Google Sans', size: 11 } } },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.dataset.label || '';
                const val = context.parsed.y;
                let rawVal = 0;
                if (context.dataIndex === 0) rawVal = (val / 100 * 27).toFixed(2) + '/27 คะแนน';
                else if (context.dataIndex === 1) rawVal = (val / 100 * 12).toFixed(2) + '/12 คะแนน';
                else if (context.dataIndex === 2) rawVal = (val / 100 * 15).toFixed(2) + '/15 คะแนน';
                else if (context.dataIndex === 3) rawVal = (val / 100 * 6).toFixed(2) + '/6 คะแนน';
                return `${label}: ${val}% (${rawVal})`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: { stepSize: 20 },
            title: { display: true, text: 'ค่าร้อยละของคะแนนเต็ม (%)', font: { family: 'Google Sans', size: 11, weight: 'bold' } }
          },
          x: { ticks: { font: { family: 'Google Sans', size: 11 } } }
        }
      }
    });
  }

  function calculateSixAspects(record) {
    if (!record || !record.rawScores || !record.rawScores.details) {
      return [0, 0, 0, 0, 0, 0];
    }
    const details = record.rawScores.details;
    const content = parseFloat(details['1.1'] || 0) + parseFloat(details['1.2'] || 0) + parseFloat(details['1.3'] || 0);
    const contentPct = (content / 27) * 100;
    const structure = parseFloat(details['2.1'] || 0) + parseFloat(details['2.2'] || 0);
    const structurePct = (structure / 12) * 100;
    const sentence = parseFloat(details['3.1'] || 0);
    const sentencePct = (sentence / 4) * 100;
    const vocab = parseFloat(details['3.2'] || 0);
    const vocabPct = (vocab / 6) * 100;
    const register = parseFloat(details['3.3'] || 0);
    const registerPct = (register / 5) * 100;
    const mechanics = parseFloat(details['4.1'] || 0) + parseFloat(details['4.2'] || 0) + parseFloat(details['4.3'] || 0);
    const mechanicsPct = (mechanics / 6) * 100;
    
    return [
      parseFloat(contentPct.toFixed(2)),
      parseFloat(structurePct.toFixed(2)),
      parseFloat(sentencePct.toFixed(2)),
      parseFloat(vocabPct.toFixed(2)),
      parseFloat(registerPct.toFixed(2)),
      parseFloat(mechanicsPct.toFixed(2))
    ];
  }

  function drawRadarChart(selfData, peerData, teacherData) {
    const canvas = document.getElementById('individualRadarChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (individualRadarChartInstance) individualRadarChartInstance.destroy();
    
    const selfAspects = calculateSixAspects(selfData);
    const peerAspects = calculateSixAspects(peerData);
    const teacherAspects = calculateSixAspects(teacherData);
    
    individualRadarChartInstance = new Chart(ctx, {
      type: 'radar',
      data: {
        labels: [
          '1. เนื้อหาสาระ (เต็ม 27)',
          '2. โครงสร้างเรื่อง (เต็ม 12)',
          '3. การใช้ประโยค (เต็ม 4)',
          '4. การใช้คำศัพท์ (เต็ม 6)',
          '5. ระดับภาษาเขียน (เต็ม 5)',
          '6. อักขรวิธี/ความสะอาด (เต็ม 6)'
        ],
        datasets: [
          {
            label: '🙋‍♂️ ตนเองประเมิน',
            data: selfAspects,
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            borderColor: '#2563eb',
            borderWidth: 2.5,
            pointBackgroundColor: '#2563eb',
            pointRadius: 4
          },
          {
            label: '👥 เพื่อนช่วยประเมิน',
            data: peerAspects,
            backgroundColor: 'rgba(249, 115, 22, 0.08)',
            borderColor: '#f97316',
            borderWidth: 2.5,
            pointBackgroundColor: '#f97316',
            pointRadius: 4
          },
          {
            label: '👩‍🏫 ครูผู้สอนประเมิน',
            data: teacherAspects,
            backgroundColor: 'rgba(5, 150, 105, 0.12)',
            borderColor: '#059669',
            borderWidth: 2.5,
            pointBackgroundColor: '#059669',
            pointRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top', labels: { font: { family: 'Google Sans', size: 11 } } },
          tooltip: {
            callbacks: {
              label: function(context) {
                const datasetLabel = context.dataset.label || '';
                const val = context.parsed.r;
                let rawVal = '';
                if (context.dataIndex === 0) rawVal = (val / 100 * 27).toFixed(2) + '/27 คะแนน';
                else if (context.dataIndex === 1) rawVal = (val / 100 * 12).toFixed(2) + '/12 คะแนน';
                else if (context.dataIndex === 2) rawVal = (val / 100 * 4).toFixed(2) + '/4 คะแนน';
                else if (context.dataIndex === 3) rawVal = (val / 100 * 6).toFixed(2) + '/6 คะแนน';
                else if (context.dataIndex === 4) rawVal = (val / 100 * 5).toFixed(2) + '/5 คะแนน';
                else if (context.dataIndex === 5) rawVal = (val / 100 * 6).toFixed(2) + '/6 คะแนน';
                return `${datasetLabel}: ${val}% (${rawVal})`;
              }
            }
          }
        },
        scales: {
          r: {
            angleLines: { display: true },
            suggestedMin: 0,
            suggestedMax: 100,
            ticks: { stepSize: 20, font: { family: 'Google Sans', size: 9 } },
            pointLabels: { font: { family: 'Google Sans', size: 10, weight: 'bold' } }
          }
        }
      }
    });
  }

  function runConsistencyAnalysis(selfData, peerData, teacherData) {
    const area = document.getElementById('consistencyIndicatorArea');
    if (!area) return;
    const scores = [];
    if (selfData) scores.push({ label: 'ตนเอง', val: parseFloat(selfData.totalScore) });
    if (peerData) scores.push({ label: 'เพื่อน', val: parseFloat(peerData.totalScore) });
    if (teacherData) scores.push({ label: 'ครู', val: parseFloat(teacherData.totalScore) });
    
    if (scores.length < 2) {
      area.innerHTML = `
        <div class="alert alert-secondary w-100 mb-0">
          <i class="bi bi-info-circle-fill"></i> ข้อมูลไม่เพียงพอสำหรับวิเคราะห์ความสอดคล้อง (ต้องการการประเมินจากผู้ประเมินอย่างน้อย 2 ฝ่ายขึ้นไปเพื่อเปรียบเทียบ)
        </div>
      `;
      return;
    }
    
    const vals = scores.map(s => s.val);
    const max = Math.max(...vals);
    const min = Math.min(...vals);
    const diff = max - min;
    
    let title = '';
    let alertClass = '';
    let desc = '';
    
    if (diff <= 6) {
      title = 'สอดคล้องกันสูง (Highly Consistent)';
      alertClass = 'alert-success border-success text-success';
      desc = `ผลคะแนนรวมมีส่วนต่างกันน้อยมาก (ต่างกันเพียง ${diff.toFixed(2)} คะแนน) บ่งชี้ถึงความเที่ยงตรงของตัวชี้วัด ผู้ประเมินทุกส่วนมีความเข้าใจและใช้เกณฑ์รูบริกไปในทิศทางเดียวกันอย่างยอดเยี่ยม`;
    } else if (diff <= 12) {
      title = 'สอดคล้องกันปานกลาง (Moderately Consistent)';
      alertClass = 'alert-warning border-warning text-warning-emphasis';
      desc = `ผลคะแนนรวมมีส่วนต่างปานกลาง (ต่างกัน ${diff.toFixed(2)} คะแนน) พบมุมมองที่เห็นต่างกันเล็กน้อย ซึ่งพบได้ปกติทางการศึกษา ขอแนะนำให้ตรวจสอบคะแนนในมิติย่อยตารางด้านล่างเพื่อหาจุดที่มองต่างกัน`;
    } else {
      title = 'สอดคล้องกันน้อย (Low Consistency)';
      alertClass = 'alert-danger border-danger text-danger';
      desc = `ผลคะแนนรวมมีความแตกต่างกันสูงมาก (ต่างกันมากถึง ${diff.toFixed(2)} คะแนน!) บ่งชี้ว่ามาตรฐานในการมองหรือระดับการให้เกณฑ์คุณภาพของผู้ประเมินมีความขัดแย้งกัน แนะนำให้ร่วมตรวจสอบชิ้นงานเขียนเพื่อปรับความเข้าใจเกณฑ์ร่วมกัน`;
    }
    
    let noticeHtml = '';
    if (selfData && teacherData) {
      const selfVal = parseFloat(selfData.totalScore);
      const teacherVal = parseFloat(teacherData.totalScore);
      if (selfVal - teacherVal > 10) {
        noticeHtml = `
          <div class="mt-3 pt-2 border-top border-danger-subtle small text-danger-emphasis">
            <i class="bi bi-exclamation-triangle-fill"></i> <strong>ข้อสังเกต:</strong> นักเรียนประเมินตนเองไว้สูงกว่าครูประเมินอย่างเด่นชัด (ห่างกัน ${(selfVal - teacherVal).toFixed(2)} คะแนน) แนะนำให้ทบทวนความเข้าใจในระดับเกณฑ์เชิงลึกร่วมกับคุณครู
          </div>
        `;
      } else if (teacherVal - selfVal > 10) {
        noticeHtml = `
          <div class="mt-3 pt-2 border-top border-primary-subtle small text-primary-emphasis">
            <i class="bi bi-info-circle-fill"></i> <strong>ข้อสังเกต:</strong> คุณครูให้คะแนนประเมินสูงกว่าที่นักเรียนมองตนเอง (ห่างกัน ${(teacherVal - selfVal).toFixed(2)} คะแนน) สะท้อนว่าผลงานนักเรียนดี แต่นักเรียนมองจุดบกพร่องตนเองเข้มงวดเกณฑ์เกินไป
          </div>
        `;
      }
    }
    
    area.innerHTML = `
      <div class="alert ${alertClass} w-100 mb-0 d-flex flex-column justify-content-between h-100 p-3 text-start">
        <div>
          <h6 class="fw-bold mb-2"><i class="bi bi-shield-shaded"></i> ${title}</h6>
          <p class="small mb-0" style="line-height: 1.5;">${desc}</p>
        </div>
        ${noticeHtml}
      </div>
    `;
  }

  function getRubricLevelInfo(itemId, rawScore) {
    if (rawScore === null || rawScore === undefined) {
      return { label: 'ยังไม่มีข้อมูล', desc: 'ผู้ประเมินฝ่ายนี้ยังไม่ได้บันทึกระดับคะแนนความเห็นสำหรับข้อนี้' };
    }
    for (const section of rubricData) {
      for (const item of section.items) {
        if (item.id === itemId) {
          const level = item.levels.find(l => l.score === rawScore);
          if (level) return { label: level.label, desc: level.desc };
        }
      }
    }
    return { label: 'ไม่พบเกณฑ์', desc: '-' };
  }

  function renderConsistencyTable(selfData, peerData, teacherData) {
    const tbody = document.getElementById('consistencyTableBody');
    if (!tbody) return;
    let html = '';
    
    for (const [key, info] of Object.entries(criteriaMap)) {
      const selfVal = selfData ? selfData.rawScores.details[key] : null;
      const peerVal = peerData ? peerData.rawScores.details[key] : null;
      const teacherVal = teacherData ? teacherData.rawScores.details[key] : null;
      
      const selfRaw = (selfVal !== null) ? Math.round(selfVal / info.mult) : null;
      const peerRaw = (peerVal !== null) ? Math.round(peerVal / info.mult) : null;
      const teacherRaw = (teacherVal !== null) ? Math.round(teacherVal / info.mult) : null;

      const validRaws = [];
      if (selfRaw !== null) validRaws.push(selfRaw);
      if (peerRaw !== null) validRaws.push(peerRaw);
      if (teacherRaw !== null) validRaws.push(teacherRaw);

      let isHighVariance = false;
      let diffRaw = 0;
      if (validRaws.length >= 2) {
        diffRaw = Math.max(...validRaws) - Math.min(...validRaws);
        if (diffRaw >= 2) isHighVariance = true;
      }

      const formatScore = (val) => {
        if (val === null || val === undefined) return '<span class="text-muted">-</span>';
        const raw = Math.round(val / info.mult);
        return `<span class="fw-bold text-dark">${val}</span> <span class="text-muted" style="font-size: 10px;">(${raw}/4)</span>`;
      };

      const rowClass = isHighVariance ? 'table-danger border-danger-subtle' : 'hover-row';
      const warningBadge = isHighVariance ? `<span class="badge bg-danger text-white ms-2" style="font-size: 9px;"><i class="bi bi-exclamation-triangle"></i> ขัดแย้งสูง (${diffRaw} ระดับ)</span>` : '';
      
      html += `
        <tr class="${rowClass}">
          <td class="fw-semibold text-start text-dark">${info.name} ${warningBadge}</td>
          <td class="text-center font-mono">x${info.mult}</td>
          <td class="text-center">${formatScore(selfVal)}</td>
          <td class="text-center">${formatScore(peerVal)}</td>
          <td class="text-center">${formatScore(teacherVal)}</td>
        </tr>
      `;

      if (isHighVariance) {
        const selfInfo = getRubricLevelInfo(key, selfRaw);
        const peerInfo = getRubricLevelInfo(key, peerRaw);
        const teacherInfo = getRubricLevelInfo(key, teacherRaw);

        html += `
          <tr style="background-color: #fef2f2;">
            <td colspan="5" class="px-4 py-3 border-start border-danger border-4 text-start">
              <div class="fw-bold text-danger small mb-2">
                <i class="bi bi-file-earmark-diff"></i> ตรวจสอบคำอธิบายเกณฑ์ที่ผู้ประเมินแต่ละฝ่ายเลือก (เปรียบเทียบจุดขัดแย้ง):
              </div>
              <div class="row g-2">
                <div class="col-md-4">
                  <div class="p-2 bg-white rounded border border-danger border-opacity-10 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                      <span class="fw-bold text-primary" style="font-size: 11px;">🙋‍♂️ ตนเองเลือก:</span>
                      <span class="badge badge-blue" style="font-size: 9px;">${selfInfo.label}</span>
                    </div>
                    <p class="mb-0 text-muted" style="font-size: 10px; line-height: 1.4;">"${selfInfo.desc}"</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-2 bg-white rounded border border-danger border-opacity-10 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                      <span class="fw-bold text-warning-emphasis" style="font-size: 11px;">👥 เพื่อนประเมิน:</span>
                      <span class="badge badge-coral" style="font-size: 9px;">${peerInfo.label}</span>
                    </div>
                    <p class="mb-0 text-muted" style="font-size: 10px; line-height: 1.4;">"${peerInfo.desc}"</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-2 bg-white rounded border border-danger border-opacity-10 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom">
                      <span class="fw-bold text-success" style="font-size: 11px;">👩‍🏫 ครูประเมิน:</span>
                      <span class="badge badge-teal" style="font-size: 9px;">${teacherInfo.label}</span>
                    </div>
                    <p class="mb-0 text-muted" style="font-size: 10px; line-height: 1.4;">"${teacherInfo.desc}"</p>
                  </div>
                </div>
              </div>
            </td>
          </tr>
        `;
      }
    }
    
    const selfTotal = selfData ? selfData.totalScore : null;
    const peerTotal = peerData ? peerData.totalScore : null;
    const teacherTotal = teacherData ? teacherData.totalScore : null;
    
    html += `
      <tr class="table-dark fw-bold align-middle">
        <td class="text-start">คะแนนรวมสะสมสุทธิ (คะแนนเต็ม 60)</td>
        <td class="text-center font-mono">-</td>
        <td class="text-center fs-6 text-warning font-outfit">${selfTotal !== null ? selfTotal + ' / 60' : '-'}</td>
        <td class="text-center fs-6 text-warning font-outfit">${peerTotal !== null ? peerTotal + ' / 60' : '-'}</td>
        <td class="text-center fs-6 text-warning font-outfit">${teacherTotal !== null ? teacherTotal + ' / 60' : '-'}</td>
      </tr>
    `;
    
    tbody.innerHTML = html;
  }

  function generateQualitativeFeedback(reports) {
     if(!reports || reports.length === 0) return '';
     
     let primaryReport = reports.find(r => r.evaluatorType === 'ครูประเมิน');
     let finalScores = {};
     
     if (primaryReport && primaryReport.scores) {
       finalScores = primaryReport.scores;
     } else {
       let itemScores = {};
       let counts = {};
       reports.forEach(r => {
         if (r.scores) {
           Object.keys(r.scores).forEach(id => {
             if (!itemScores[id]) {
               itemScores[id] = 0;
               counts[id] = 0;
             }
             itemScores[id] += parseFloat(r.scores[id]);
             counts[id] += 1;
           });
         }
       });
       
       Object.keys(itemScores).forEach(id => {
         finalScores[id] = counts[id] > 0 ? (itemScores[id] / counts[id]) : 0;
       });
     }

     let strengths = [];
     let improvements = [];
     
     rubricData.forEach(section => {
       section.items.forEach(item => {
         let scoreValue = finalScores[item.id] !== undefined ? finalScores[item.id] : 0;
         let rawScore = Math.round(scoreValue / item.multiplier);
         
         let levelObj = item.levels.find(l => l.score === rawScore);
         if (levelObj) {
           if (rawScore >= 3) {
             strengths.push({ name: item.name, desc: levelObj.desc });
           } else {
             improvements.push({ name: item.name, desc: levelObj.desc });
           }
         }
       });
     });

     let strengthParagraph = "";
     if (strengths.length > 0) {
       strengthParagraph = strengths.map(s => `
         <div class="mb-3 text-start">
           <div class="fw-bold text-success" style="font-size: 0.9rem;">⭐ ${s.name}</div>
           <div class="text-success-emphasis" style="font-size: 0.85rem; padding-left: 1.2rem; margin-top: 2px;">${s.desc}</div>
         </div>
       `).join("");
     } else {
       strengthParagraph = `
         <div class="text-muted small text-start">เป็นกำลังใจให้นะครับ! แม้ว่าผลงานชิ้นนี้ยังมีจุดที่ต้องปรับปรุงอยู่หลายส่วน แต่หากตั้งใจพัฒนาและฝึกฝนในข้อที่ควรปรับปรุงจะสามารถยกระดับความสามารถได้อย่างแน่นอนครับ</div>
       `;
     }
     
     let improveParagraph = "";
     if (improvements.length > 0) {
       improveParagraph = improvements.map(s => `
         <div class="mb-3 text-start">
           <div class="fw-bold text-warning-emphasis" style="font-size: 0.9rem;">📍 ${s.name}</div>
           <div class="text-warning-emphasis" style="font-size: 0.85rem; padding-left: 1.2rem; margin-top: 2px;">${s.desc}</div>
         </div>
       `).join("");
     } else {
       improveParagraph = `
         <div class="text-muted small text-start">ยอดเยี่ยมมาก! ผลงานเขียนชิ้นนี้ทำได้ดีในทุกองค์ประกอบ ข้อแนะนำคือควรเริ่มฝึกฝนหัวข้อการเขียนเรียงความที่มีความท้าทาย ซับซ้อน หรือมีระดับรูปแบบประโยควิชาการที่สูงขึ้น เพื่อสร้างมาตรฐานงานเขียนและยกระดับความเชี่ยวชาญต่อไปครับ</div>
       `;
     }
     
     return `
       <div class="col-md-6 text-start col-sm-12">
         <div class="p-4 rounded-3 h-100 border border-success border-opacity-25" style="background-color: #f0fdfa">
           <h6 class="fw-bold text-success mb-3"><i class="bi bi-star-fill"></i> จุดเด่นที่ทำได้ยอดเยี่ยม</h6>
           <div class="leading-relaxed mb-0 text-success-emphasis" style="line-height:1.6">${strengthParagraph}</div>
         </div>
       </div>
       <div class="col-md-6 text-start col-sm-12">
         <div class="p-4 rounded-3 h-100 border border-warning border-opacity-25" style="background-color: #fffbeb">
           <h6 class="fw-bold text-warning-emphasis mb-3"><i class="bi bi-arrow-up-circle-fill"></i> สิ่งที่ควรพัฒนาต่อยอด</h6>
           <div class="leading-relaxed mb-0 text-warning-emphasis" style="line-height:1.6">${improveParagraph}</div>
         </div>
       </div>
     `;
  }

  async function fetchReflectionData(studentId) {
    const container = document.getElementById('reflectionDataContainer');
    if (!container) return;
    container.innerHTML = '<div class="text-center text-muted py-3 small"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลดข้อมูลสะท้อนคิด...</div>';
    try {
      const response = await fetch(`api.php?action=get_reflection_data&studentId=${encodeURIComponent(studentId)}&_t=${new Date().getTime()}`);
      const res = await response.json();
      if (res.success && res.found && res.data) {
        const d = res.data;
        const reflectionFields = [
          { label: '1. ด้านเนื้อหาสาระและองค์ประกอบ', key: 'content_structure', color: '#0ea5e9', bg: '#f0f9ff' },
          { label: '2. ด้านการใช้สำนวนภาษาและอักขรวิธี', key: 'language_mechanics', color: '#8b5cf6', bg: '#faf5ff' },
          { label: '3. การนำข้อเสนอแนะไปปรับปรุงงาน', key: 'feedback_applied', color: '#10b981', bg: '#f0fdf4' },
          { label: '4. การประยุกต์ใช้และเป้าหมายในอนาคต', key: 'future_goals', color: '#f59e0b', bg: '#fffbeb' }
        ];
        container.innerHTML = `
          <div class="row g-3">
            ${reflectionFields.map(f => `
              <div class="col-md-6 col-sm-12">
                <div class="rounded-3 p-3 h-100" style="background:${f.bg}; border-left: 4px solid ${f.color};">
                  <div class="fw-bold mb-1" style="color:${f.color}; font-size:0.85rem;">${f.label}</div>
                  <div class="text-dark" style="font-size:0.875rem; line-height:1.6; white-space:pre-wrap;">${d[f.key] ? d[f.key].replace(/</g, '&lt;').replace(/>/g, '&gt;') : '<em class="text-muted">ยังไม่ได้กรอกข้อมูล</em>'}</div>
                </div>
              </div>
            `).join('')}
          </div>
        `;
      } else {
        container.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-journal-x fs-3 d-block mb-2"></i>ผู้เรียนคนนี้ยังไม่ได้บันทึกข้อมูลการสะท้อนการเรียนรู้</div>';
      }
    } catch (err) {
      container.innerHTML = '<div class="text-danger small text-center py-3">ไม่สามารถโหลดข้อมูลสะท้อนคิดได้</div>';
    }
  }

  // --- เริ่มรันอัตโนมัติ ---
  (async function init() {
    await loadStudents();

    // ตรวจสอบบทบาทผู้ใช้
    const userRole = "<?php echo $sessionUser['role']; ?>";
    if (userRole === 'teacher') {
      initGroupFilterFromStore(); // ดึงกลุ่มที่จำไว้มาตั้งเป็นค่าเริ่มต้น
      loadTeacherOverview();
    } else {
      // สำหรับนักเรียน ให้ดึงรายงานของตนเองทันที
      fetchScores(currentUser.id, currentUser.name);
    }
  })();
</script>

<?php
require_once 'footer.php';
?>
