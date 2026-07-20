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
      <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-primary"></i> รายงานสถิติภาพรวมเพื่อการทำวิจัยวิชาการ (Research Statistical Analysis)</h6>
        <button class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#researchChartsCollapse" aria-expanded="true" aria-controls="researchChartsCollapse">
          แสดง/ซ่อน รายงานวิจัย
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
              <span class="small fw-bold text-secondary mb-2 d-block">2. ค่าเฉลี่ยของชั้นเรียนคิดเป็นร้อยละแยกตามมิติหลัก (เต็ม 100%)</span>
              <div style="position: relative; height: 260px;" class="w-100">
                <canvas id="classDimensionAveragesChart"></canvas>
              </div>
            </div>
            <!-- กราฟเส้นคะแนนเฉลี่ยรายบุคคลแยกตามรายด้าน (1 นักเรียน = 1 เส้นพาด) -->
            <div class="col-12 text-center mt-4 border-top pt-4">
              <span class="small fw-bold text-secondary mb-2 d-block">3. กราฟเส้นวิเคราะห์รูปแบบคะแนนเฉลี่ยของผู้เรียนแต่ละคนแยกตามรายด้าน (1 นักเรียน = 1 เส้นพาด)</span>
              <div style="position: relative; height: 340px;" class="w-100">
                <canvas id="classDimensionLinesChart"></canvas>
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
                <option value="task" selected>ภารงานในหน่วยเรียน (360° & Expert)</option>
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

    <!-- 🔬 สถิติตรวจสอบคุณภาพเกณฑ์และการวิจัย (Research Statistics & Inter-Rater Reliability Dashboard) -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden" id="researchStatsCard">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h6 class="fw-bold mb-0 text-white"><i class="bi bi-gear-wide-connected text-warning"></i> ระบบวิเคราะห์ทางสถิติเพื่อการทำวิจัยและตรวจสอบคุณภาพเกณฑ์ประเมิน (Inter-rater & Paired t-test)</h6>
        <span class="badge bg-warning text-dark font-mono font-semibold">One-Group Pretest-Posttest Design</span>
      </div>
      <div class="card-body p-4 text-start">
        
        <!-- แท็บเลื่อนเลือกประเภทสถิติ -->
        <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3 border" id="researchTab" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark px-4 py-2.5 rounded-3 d-flex align-items-center gap-2" id="reliability-tab" data-bs-toggle="pill" data-bs-target="#tab-reliability" type="button" role="tab" aria-selected="true">
              🤝 ค่าความสอดคล้องผู้ประเมินร่วม (Pearson r)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark px-4 py-2.5 rounded-3 d-flex align-items-center gap-2" id="ttest-tab" data-bs-toggle="pill" data-bs-target="#tab-ttest" type="button" role="tab" aria-selected="false">
              📈 ประสิทธิภาพการพัฒนาการเขียน (Paired t-test)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark px-4 py-2.5 rounded-3 d-flex align-items-center gap-2" id="qualitative-tab" data-bs-toggle="pill" data-bs-target="#tab-qualitative" type="button" role="tab" aria-selected="false">
              🔬 ศูนย์วิเคราะห์เชิงคุณภาพ (Content Analysis Hub)
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark px-4 py-2.5 rounded-3 d-flex align-items-center gap-2" id="essays-tab" data-bs-toggle="pill" data-bs-target="#tab-essays" type="button" role="tab" aria-selected="false" onclick="loadEssayViewer()">
              ✍️ เรียงความนักเรียน (Essay Viewer)
            </button>
          </li>
        </ul>

        <div class="tab-content" id="researchTabContent">
          
          <!-- 1. Pearson Correlation Reliability Tab -->
          <div class="tab-pane fade show active" id="tab-reliability" role="tabpanel" aria-labelledby="reliability-tab">
            <div class="row g-4">
              <div class="col-md-5 col-sm-12">
                <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-primary h-100">
                  <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle-fill text-primary"></i> การตีความความน่าเชื่อถือเกณฑ์ประเมิน (Hopkins & Stanley, 1981)</h6>
                  <p class="small text-muted mb-3" style="line-height: 1.6;">
                    เป็นการคำนวณค่าสัมประสิทธิ์สหสัมพันธ์แบบเพียร์สัน (Pearson correlation coefficient - r) ของคะแนนที่ให้โดยผู้เชี่ยวชาญคนที่ 1 (admin1) และคนที่ 2 (admin2) เพื่อพิสูจน์ความสม่ำเสมอของเกณฑ์ประเมิน (Inter-rater Reliability)
                  </p>
                  <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between p-2 rounded bg-success bg-opacity-10 text-success fw-semibold">
                      <span>r &ge; 0.90</span><span>ระดับสูงมาก (Very High)</span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-info bg-opacity-10 text-info fw-semibold">
                      <span>0.70 &le; r &lt; 0.90</span><span>ระดับสูง (High)</span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-warning bg-opacity-10 text-warning-emphasis fw-semibold">
                      <span>0.50 &le; r &lt; 0.70</span><span>ระดับปานกลาง (Moderate)</span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-danger bg-opacity-10 text-danger fw-semibold">
                      <span>0.30 &le; r &lt; 0.50</span><span>ระดับต่ำ (Low)</span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-secondary bg-opacity-10 text-muted fw-semibold">
                      <span>r &lt; 0.30</span><span>น้อยมาก (Little if any)</span>
                    </div>
                  </div>
                  <div class="border-top pt-3 mt-3">
                    <div class="text-secondary small fw-bold">สัมประสิทธิ์ภาพรวม (Overall Pearson r)</div>
                    <h2 class="fw-bold text-primary font-outfit mt-1" id="overallPearsonResult">-</h2>
                    <span id="overallPearsonInterpretation" class="badge bg-secondary">ไม่มีข้อมูล</span>
                  </div>
                </div>
              </div>
              <div class="col-md-7 col-sm-12">
                <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-warning h-100">
                  <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-steps text-warning"></i> ความเที่ยงรายด้านย่อย 11 เกณฑ์ (Sub-criteria Reliability)</h6>
                  <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                      <thead class="table-light text-secondary">
                        <tr>
                          <th>เกณฑ์ประเมิน</th>
                          <th class="text-center">จำนวนคู่</th>
                          <th class="text-center">ค่าสหสัมพันธ์ (r)</th>
                          <th class="text-end">ผลประเมิน</th>
                        </tr>
                      </thead>
                      <tbody id="reliabilityTableBody">
                        <tr><td colspan="4" class="text-center py-4 text-muted">กรุณารอประมวลผลข้อมูล...</td></tr>
                      </tbody>
                    </table>
                  </div>
            </div>
            
            <!-- Pearson interpretation paragraph -->
            <div class="row mt-3">
              <div class="col-12">
                <div id="pearsonReportParagraph" class="card border-0 rounded-3 p-3 text-secondary small bg-light border-start border-3 border-secondary" style="line-height: 1.6;">
                  <em>กำลังรอผลสัมประสิทธิ์ความสอดคล้อง...</em>
                </div>
              </div>
            </div>
          </div>
          
          <!-- 2. Paired t-test Tab -->
          <div class="tab-pane fade" id="tab-ttest" role="tabpanel" aria-labelledby="ttest-tab">
            <div class="row g-4">
              <div class="col-md-4 col-sm-12">
                <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-success h-100">
                  <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders text-success"></i> ตัวแปรวิจัยเชิงสถิติ</h6>
                  <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">แหล่งที่มาของคะแนนดิบที่จับคู่:</label>
                    <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2.5 fs-7 w-100 text-start fw-bold rounded-3">
                      <i class="bi bi-person-workspace me-1"></i> คุณครูผู้สอน (ประเมิน T1 & T2 ครบถ้วน)
                    </div>
                    <select id="ttestReviewerSelector" class="d-none">
                      <option value="teacher" selected>คะแนนจากคุณครูผู้สอน</option>
                    </select>
                  </div>
                  <p class="small text-muted" style="line-height: 1.6;">
                    เปรียบเทียบคะแนนเขียนเรียงความของนักเรียนคนเดียวกันระหว่างก่อนเรียน (Pretest - T1) กับหลังเรียน (Posttest - T2) ด้วยสถิติ **Paired t-test (Dependent t-test)** เพื่อทดสอบประสิทธิภาพของแนวทางการจัดการเรียนการสอนแบบ POA
                  </p>
                  <div class="alert alert-info border-0 rounded-3 p-3 small mb-0">
                    <strong>สมมติฐานหลัก (H0):</strong> คะแนนสอบเฉลี่ยก่อนเรียนและหลังเรียนเท่ากัน<br>
                    <strong>สมมติฐานทางเลือก (H1):</strong> คะแนนหลังเรียนสูงกว่าก่อนเรียนอย่างมีนัยสำคัญ
                  </div>
                </div>
              </div>
              <div class="col-md-8 col-sm-12">
                <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-danger h-100">
                  <h6 class="fw-bold text-dark mb-3"><i class="bi bi-journal-check text-danger"></i> ตารางสถิติเปรียบเทียบผลสัมฤทธิ์ทางการเขียน (Paired t-test Table)</h6>
                  <div class="table-responsive mb-3">
                    <table class="table table-bordered align-middle text-center small mb-0">
                      <thead class="table-light text-secondary">
                        <tr>
                          <th>ตัวแปรการทดสอบ</th>
                          <th>N</th>
                          <th>Mean ก่อนเรียน (T1)</th>
                          <th>Mean หลังเรียน (T2)</th>
                          <th>ผลต่างเฉลี่ย (D)</th>
                          <th>SD ของผลต่าง (SD_D)</th>
                          <th>ค่าสถิติ t</th>
                          <th>องศาอิสระ (df)</th>
                          <th>ค่าความนัยสำคัญ (p-value)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr id="ttestStatsRow">
                          <td colspan="9" class="py-4 text-muted">ไม่มีข้อมูลประมวลผล...</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="card border-0 rounded bg-light p-3 small text-secondary" id="ttestInterpretationText">
                    รอคำนวณสรุปผลทางสถิติ...
                  </div>
                  <div id="ttestReportParagraph" class="mt-3 p-3 rounded-3 border bg-light small text-secondary" style="line-height: 1.6;">
                    <em>กำลังประมวลผลข้อเขียนรายงานสถิติเชิงปริมาณ...</em>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Qualitative Content Analysis Hub Tab -->
          <div class="tab-pane fade" id="tab-qualitative" role="tabpanel" aria-labelledby="qualitative-tab">
            <div class="card border-0 rounded-3 p-3 bg-light shadow-sm mb-4">
              <div class="row g-2 align-items-center">
                <div class="col-md-4 col-sm-12">
                  <div class="input-group">
                    <span class="input-group-text bg-white small border-end-0"><i class="bi bi-grid-fill"></i> เกณฑ์ด้าน</span>
                    <select id="qualitativeCriteriaFilter" class="form-select bg-white border-start-0 small" onchange="filterQualitativeHub()">
                      <option value="all" selected>แสดงทุกเกณฑ์ประเมิน</option>
                      <option value="1.1">1.1 ความตรงประเด็น</option>
                      <option value="1.2">1.2 แก่นเรื่องชัดเจน</option>
                      <option value="1.3">1.3 การขยายความและเหตุผล</option>
                      <option value="2.1">2.1 ความครบถ้วนขององค์ประกอบ</option>
                      <option value="2.2">2.2 การลำดับประเด็นเป็นระบบ</option>
                      <option value="3.1">3.1 การใช้ประโยคถูกต้อง</option>
                      <option value="3.2">3.2 การเลือกใช้คำ</option>
                      <option value="3.3">3.3 ระดับภาษาเหมาะสม</option>
                      <option value="4.1">4.1 การสะกดคำถูกต้อง</option>
                      <option value="4.2">4.2 การเว้นวรรค</option>
                      <option value="4.3">4.3 ความเรียบร้อย</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-5 col-sm-12">
                  <div class="input-group">
                    <span class="input-group-text bg-white small border-end-0"><i class="bi bi-search"></i> ค้นหา</span>
                    <input type="text" id="qualitativeSearchInput" onkeyup="filterQualitativeHub()" class="form-control bg-white border-start-0 small text-start" placeholder="ค้นหาคำศัพท์ อุปสรรค ความคิดเห็น...">
                  </div>
                </div>
                <div class="col-md-3 col-sm-12 text-md-end text-center mt-2 mt-md-0">
                  <button class="btn btn-primary btn-sm fw-bold px-4 py-2.5 rounded-pill" onclick="exportQualitativeToCSV()"><i class="bi bi-download"></i> ส่งออกข้อมูลดิบ (CSV)</button>
                </div>
              </div>
            </div>

            <!-- Qualitative Content Analysis paragraph -->
            <div id="qualitativeReportParagraph" class="card border-0 rounded-3 p-3 text-secondary small bg-light border-start border-3 border-success mb-3" style="line-height: 1.6;">
              <em>กำลังประมวลผลข้อเขียนวิเคราะห์ข้อมูลเชิงคุณภาพ...</em>
            </div>

            <!-- กล่องแสดงข้อความสำหรับการทำวิเคราะห์เนื้อหา (Content Analysis Cards) -->
            <div class="row g-3" id="qualitativeHubContainer" style="max-height: 480px; overflow-y: auto;">
              <!-- Cards created by JS dynamically -->
              <div class="col-12 text-center py-5 text-muted">กำลังโหลดข้อความเชิงคุณภาพเพื่อใช้วิเคราะห์เนื้อหา...</div>
            </div>
          </div>

          <!-- 4. Essay Viewer Tab -->
          <div class="tab-pane fade" id="tab-essays" role="tabpanel" aria-labelledby="essays-tab">
            <!-- Controls row -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
              <div>
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>เรียงความของนักเรียน (Essay Viewer)</h6>
                <p class="text-muted small mb-0">ตรวจสอบเนื้อหาเรียงความที่นักเรียนพิมพ์ส่งเพื่อประกอบการวิเคราะห์เชิงคุณภาพ</p>
              </div>
              <div class="d-flex gap-2 flex-wrap align-items-center">
                <select id="essayPhaseFilter" onchange="filterEssayViewer()" class="form-select form-select-sm border-2 rounded-pill" style="width:auto;">
                  <option value="all">ทุกรอบ</option>
                  <option value="pretest">ก่อนเรียน</option>
                  <option value="task1">ภารงาน หน่วยที่ 1</option>
                  <option value="task2">ภารงาน หน่วยที่ 2</option>
                  <option value="posttest">หลังเรียน</option>
                </select>
                <input type="text" id="essaySearchInput" onkeyup="filterEssayViewer()" class="form-control form-control-sm border-2 rounded-pill" placeholder="🔍 ค้นหาชื่อหรือเนื้อหา..." style="width:220px;">
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="exportEssaysCSV()">
                  <i class="bi bi-download me-1"></i>ส่งออก CSV
                </button>
              </div>
            </div>

            <!-- Summary stats row -->
            <div class="row g-3 mb-4" id="essaySummaryRow">
              <div class="col-md-3 col-6">
                <div class="card border-0 rounded-3 p-3 text-center bg-light">
                  <div class="fs-4 fw-bold text-primary" id="essayStatTotal">-</div>
                  <div class="text-muted small">ส่งเรียงความแล้ว</div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="card border-0 rounded-3 p-3 text-center bg-light">
                  <div class="fs-4 fw-bold text-success" id="essayStatAvgWords">-</div>
                  <div class="text-muted small">เฉลี่ย คำ/ชิ้น</div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="card border-0 rounded-3 p-3 text-center bg-light">
                  <div class="fs-4 fw-bold text-warning" id="essayStatMaxWords">-</div>
                  <div class="text-muted small">มากสุด (คำ)</div>
                </div>
              </div>
              <div class="col-md-3 col-6">
                <div class="card border-0 rounded-3 p-3 text-center bg-light">
                  <div class="fs-4 fw-bold text-danger" id="essayStatMinWords">-</div>
                  <div class="text-muted small">น้อยสุด (คำ)</div>
                </div>
              </div>
            </div>

            <!-- Essay cards -->
            <div id="essayViewerContainer" style="max-height:560px; overflow-y:auto;">
              <div class="text-center text-muted py-5">
                <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>กดแท็บ "เรียงความนักเรียน" เพื่อโหลดข้อมูล
              </div>
            </div>
          </div>

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
      <select id="studentPhaseSelector" class="form-select form-select-sm bg-white text-dark font-semibold border-0" style="width:140px;" onchange="switchStudentPhase()">
        <option value="posttest" selected>หลังเรียน (T2)</option>
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
  let classDimensionLinesChartInstance = null;
  let classroomResearchData = null;
  let currentResearchPhase = 'posttest';
  let currentDashboardViewMode = 'task1';

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
    const expertPairs = [];

    // ดึงผู้ประเมินร่วม (Expert 1 & 2) เฉพาะภารงานในหน่วยที่เลือก เพื่อคำนวณ Pearson r
    const expertTaskPhase = (currentDashboardViewMode === 'task2') ? 'task2' : 'task1';
    studentsList.forEach(s => {
      const id = s.student_id;
      const taskEvs = studentEvals[id][expertTaskPhase] || [];
      const expert1Eval = taskEvs.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 1' || e.evaluator_name === 'admin1'));
      const expert2Eval = taskEvs.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 2' || e.evaluator_name === 'admin2'));

      if (expert1Eval && expert2Eval) {
        expertPairs.push({
          e1: expert1Eval,
          e2: expert2Eval
        });
      }
    });

    if (currentDashboardViewMode === 'task1' || currentDashboardViewMode === 'task2') {
      // --- 1. โหมดรายงานภารงานในหน่วยเรียน (Task 1 หรือ Task 2) ---
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
    calculatePearsonReliability(expertPairs);
    calculateResearchTTest();
    renderQualitativeHub();
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
          <th class="px-3 py-3" style="width: 20%">ชื่อ-สกุลผู้เรียน</th>
          <th class="px-3 py-3 text-center" style="width: 10%">ตนเองประเมิน</th>
          <th class="px-3 py-3 text-center" style="width: 10%">เพื่อนประเมิน</th>
          <th class="px-3 py-3 text-center" style="width: 10%">ครูประเมิน</th>
          <th class="px-3 py-3 text-center text-warning-emphasis" style="width: 12%">ผู้เชี่ยวชาญ 1</th>
          <th class="px-3 py-3 text-center text-warning-emphasis" style="width: 12%">ผู้เชี่ยวชาญ 2</th>
          <th class="px-3 py-3 text-center" style="width: 8%">เฉลี่ย${unitLabel}</th>
          <th class="px-3 py-3 text-end" style="width: 8%">การจัดการ</th>
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

    const sortedKeys = Object.keys(studentDB).sort();
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
        const checkIcon = '<span class="badge badge-teal"><i class="bi bi-check-circle-fill"></i> ส่งแล้ว</span>';
        const crossIcon = '<span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill">-</span>';

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
            <td class="px-3 py-3 text-center">${sData.expert1 ? checkIcon : crossIcon}</td>
            <td class="px-3 py-3 text-center">${sData.expert2 ? checkIcon : crossIcon}</td>
            <td class="px-3 py-3 text-center fw-extrabold ${sData.avgScore > 0 ? 'text-primary bg-light-blue' : 'text-muted'}">${sData.avgScore > 0 ? sData.avgScore.toFixed(2) : '-'}</td>
            <td class="px-3 py-3 text-end">
               <button class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">วิเคราะห์</button>
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
               <button class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">วิเคราะห์</button>
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
    if (currentDashboardViewMode === 'task') {
      completionPercentage = Math.round((activeEvaluationSetCount / totalRegistered) * 100);
    } else {
      let bothTeacherCount = Object.values(data).filter(x => x.preScore !== null && x.postScore !== null).length;
      completionPercentage = Math.round((bothTeacherCount / totalRegistered) * 100);
    }
    document.getElementById('statCompletion').textContent = completionPercentage + "%";

    drawClassQualityDistribution(qualityCounts);
    drawClassDimensionAverages(dimensionSums, evaluatedStdsCount);
    drawClassroomDimensionLines({ success: true, data: data });
    generateResearchInsights(subCriteriaSums, evaluatedStdsCount, totalRegistered, totalSumScores, totalScoredCount, activeEvaluationSetCount);
  }

  function pearsonCorrelation(pairs, keyFn) {
    const N = pairs.length;
    if (N < 2) return null;

    let sumX = 0, sumY = 0;
    let sumXSq = 0, sumYSq = 0;
    let sumXY = 0;

    for (let i = 0; i < N; i++) {
      const valX = keyFn(pairs[i].e1);
      const valY = keyFn(pairs[i].e2);
      sumX += valX;
      sumY += valY;
      sumXSq += valX * valX;
      sumYSq += valY * valY;
      sumXY += valX * valY;
    }

    const num = N * sumXY - sumX * sumY;
    const den = Math.sqrt((N * sumXSq - sumX * sumX) * (N * sumYSq - sumY * sumY));
    if (den === 0) return 0;
    return num / den;
  }

  function getPearsonInterpretation(r) {
    if (r === null) return { text: 'ข้อมูลน้อยเกินไป', css: 'bg-secondary' };
    const absR = Math.abs(r);
    if (absR >= 0.90) return { text: 'ระดับสูงมาก (Very High Reliability)', css: 'bg-success' };
    if (absR >= 0.70) return { text: 'ระดับสูง (High Reliability)', css: 'bg-info text-dark' };
    if (absR >= 0.50) return { text: 'ระดับปานกลาง (Moderate Reliability)', css: 'bg-warning text-dark' };
    if (absR >= 0.30) return { text: 'ระดับต่ำ (Low Reliability)', css: 'bg-danger' };
    return { text: 'น้อยมากหรือไม่มีความเที่ยง (Little if any)', css: 'bg-danger bg-opacity-75' };
  }

  function calculatePearsonReliability(pairs) {
    const overallEl = document.getElementById('overallPearsonResult');
    const interpEl = document.getElementById('overallPearsonInterpretation');
    const tableBody = document.getElementById('reliabilityTableBody');
    if (!overallEl || !tableBody) return;

    if (pairs.length < 2) {
      overallEl.textContent = "N/A";
      interpEl.textContent = "ต้องมีผู้เรียนถูกประเมินโดยทั้ง 2 ผู้เชี่ยวชาญอย่างน้อย 2 คน";
      interpEl.className = "badge bg-secondary";
      tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">ต้องการข้อมูลประเมินครบทั้งสองผู้เชี่ยวชาญอย่างน้อย 2 คนในการประมวลผล</td></tr>`;
      
      const pearsonParagraphEl = document.getElementById('pearsonReportParagraph');
      if (pearsonParagraphEl) {
        pearsonParagraphEl.innerHTML = `
          <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-primary"></i> บทวิเคราะห์ความสอดคล้องผู้ประเมินเชิงปริมาณ</h6>
          <p class="mb-0 text-muted">ต้องการข้อมูลประเมินจับคู่ครบทั้งสองผู้เชี่ยวชาญอย่างน้อย 2 คนในการประมวลผลบทรายงาน</p>
        `;
      }
      return;
    }

    const overallR = pearsonCorrelation(pairs, e => Number(e.total_score));
    overallEl.textContent = overallR !== null ? overallR.toFixed(4) : "N/A";
    const overallInterp = getPearsonInterpretation(overallR);
    interpEl.textContent = overallInterp.text;
    interpEl.className = "badge " + overallInterp.css;

    const subCriteria = [
      { id: '1.1', name: '1.1 ความตรงประเด็น' },
      { id: '1.2', name: '1.2 แก่นเรื่องชัดเจน' },
      { id: '1.3', name: '1.3 การขยายความและเหตุผล' },
      { id: '2.1', name: '2.1 ความครบถ้วนขององค์ประกอบ' },
      { id: '2.2', name: '2.2 การลำดับประเด็นเป็นระบบ' },
      { id: '3.1', name: '3.1 การใช้ประโยคถูกต้อง' },
      { id: '3.2', name: '3.2 การเลือกใช้คำ' },
      { id: '3.3', name: '3.3 ระดับภาษาเหมาะสม' },
      { id: '4.1', name: '4.1 การสะกดคำถูกต้อง' },
      { id: '4.2', name: '4.2 การเว้นวรรค' },
      { id: '4.3', name: '4.3 ความเรียบร้อย' }
    ];

    let html = '';
    let subRs = [];
    subCriteria.forEach(sc => {
      const scoreKey = 'score_' + sc.id.replace('.', '_');
      const rVal = pearsonCorrelation(pairs, e => Number(e[scoreKey]));
      if (rVal !== null) subRs.push(rVal);
      const interp = getPearsonInterpretation(rVal);
      const displayR = rVal !== null ? rVal.toFixed(4) : "N/A";

      html += `
        <tr>
          <td class="fw-semibold">${sc.name}</td>
          <td class="text-center font-mono">${pairs.length}</td>
          <td class="text-center font-mono fw-bold text-primary">${displayR}</td>
          <td class="text-end"><span class="badge ${interp.css} small">${interp.text.split(' (')[0]}</span></td>
        </tr>
      `;
    });
    tableBody.innerHTML = html;

    const pearsonParagraphEl = document.getElementById('pearsonReportParagraph');
    if (pearsonParagraphEl) {
      const minSubR = subRs.length > 0 ? Math.min(...subRs).toFixed(4) : "N/A";
      const maxSubR = subRs.length > 0 ? Math.max(...subRs).toFixed(4) : "N/A";
      const overallInterpText = overallInterp.text.split(' (')[0];

      pearsonParagraphEl.innerHTML = `
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-primary"></i> บทวิเคราะห์ความสอดคล้องผู้ประเมินเชิงปริมาณ (Inter-rater Reliability Narrative)</h6>
        <p class="mb-0 text-slate-700" style="line-height: 1.6;">
          จากการศึกษาความสอดคล้องของการให้คะแนนความสามารถการเขียนเรียงความเชิงปริมาณ ระหว่างผู้เชี่ยวชาญคนที่ 1 (Expert 1) และผู้เชี่ยวชาญคนที่ 2 (Expert 2) ประเมินในรอบภารงานในหน่วยเรียนร่วมกันของนักเรียนกลุ่มเป้าหมาย (N = ${pairs.length} คน) 
          พบว่า <strong>ค่าสัมประสิทธิ์สหสัมพันธ์ความสอดคล้องของคะแนนรวม (Overall Pearson r) มีค่าเท่ากับ ${overallR !== null ? overallR.toFixed(4) : "N/A"}</strong> ซึ่งเมื่อแปลความตามเกณฑ์ของ Hopkins & Stanley (1981) พบว่ามีความสอดคล้องความเที่ยงของเกณฑ์ประเมินร่วมอยู่ใน<strong>ระดับ${overallInterpText}</strong> 
          และเมื่อพิจารณาแยกรายเกณฑ์ย่อย 11 เกณฑ์ พบว่าค่าสหสัมพันธ์ของเกณฑ์ย่อยมีค่าอยู่ระหว่าง <strong>r = ${minSubR}</strong> ถึง <strong>r = ${maxSubR}</strong> ซึ่งสะท้อนถึงระดับความเที่ยงตรงสม่ำเสมอของรูบริกประเมินและทิศทางเกณฑ์ประเมินที่มีความเชื่อถือได้ในระดับสูงเชิงสถิติวิจัย
        </p>
      `;
    }
  }

  function normalCDF(z) {
    const t = 1 / (1 + 0.2316419 * Math.abs(z));
    const d = 0.39894228 * Math.exp(-z * z / 2);
    const prob = d * t * (0.31938153 + t * (-0.356563782 + t * (1.781477937 + t * (-1.821255978 + 1.330274429 * t))));
    if (z >= 0) return 1 - prob;
    return prob;
  }

  function calculateOneTailedPValue(t, df) {
    if (df <= 0) return 1;
    const x = t;
    const n = df;
    if (n === 1) {
      return 0.5 - Math.atan(x) / Math.PI;
    }
    const d1 = 1 / (4 * n);
    const d2 = 5 / (96 * n * n);
    const z = x * Math.sqrt(1 - d1 + d2) / Math.sqrt(1 + x * x / (2 * n));
    return 1 - normalCDF(z);
  }

  function recalculateTTest() {
    calculateResearchTTest();
  }

  function calculateResearchTTest() {
    const rowEl = document.getElementById('ttestStatsRow');
    const interpEl = document.getElementById('ttestInterpretationText');
    const reviewerSource = document.getElementById('ttestReviewerSelector').value;
    if (!rowEl || !interpEl) return;

    if (!classroomResearchData) return;

    const students = classroomResearchData.students;
    const evaluations = classroomResearchData.evaluations;

    const pretestEvals = evaluations.filter(e => e.test_phase === 'pretest');
    const posttestEvals = evaluations.filter(e => e.test_phase === 'posttest');

    const pairedData = [];

    students.forEach(s => {
      const id = s.student_id;

      let preScore = null;
      let postScore = null;

      const preStudentEvals = pretestEvals.filter(e => e.student_id === id);
      const postStudentEvals = posttestEvals.filter(e => e.student_id === id);

      if (reviewerSource === 'expert_avg') {
        const preExperts = preStudentEvals.filter(e => e.evaluator_type === 'expert');
        const postExperts = postStudentEvals.filter(e => e.evaluator_type === 'expert');
        if (preExperts.length > 0 && postExperts.length > 0) {
          preScore = preExperts.reduce((sum, e) => sum + Number(e.total_score), 0) / preExperts.length;
          postScore = postExperts.reduce((sum, e) => sum + Number(e.total_score), 0) / postExperts.length;
        }
      } else if (reviewerSource === 'expert1') {
        const preE1 = preStudentEvals.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 1' || e.evaluator_name === 'admin1'));
        const postE1 = postStudentEvals.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 1' || e.evaluator_name === 'admin1'));
        if (preE1 && postE1) {
          preScore = Number(preE1.total_score);
          postScore = Number(postE1.total_score);
        }
      } else if (reviewerSource === 'expert2') {
        const preE2 = preStudentEvals.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 2' || e.evaluator_name === 'admin2'));
        const postE2 = postStudentEvals.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 2' || e.evaluator_name === 'admin2'));
        if (preE2 && postE2) {
          preScore = Number(preE2.total_score);
          postScore = Number(postE2.total_score);
        }
      } else if (reviewerSource === 'teacher') {
        const preT = preStudentEvals.find(e => e.evaluator_type === 'teacher');
        const postT = postStudentEvals.find(e => e.evaluator_type === 'teacher');
        if (preT && postT) {
          preScore = Number(preT.total_score);
          postScore = Number(postT.total_score);
        }
      }

      if (preScore !== null && postScore !== null) {
        pairedData.push({
          id: id,
          pre: preScore,
          post: postScore,
          diff: postScore - preScore
        });
      }
    });

    const N = pairedData.length;
    if (N < 2) {
      rowEl.innerHTML = `<td colspan="9" class="text-center py-4 text-muted">ต้องการข้อมูลผลคะแนนที่ตรงคู่กันระหว่าง Pretest และ Posttest อย่างน้อย 2 คน</td>`;
      interpEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning"></i> ไม่สามารถคำนวณสถิติ Paired t-test ได้ เนื่องจากจำนวนตัวอย่างที่จับคู่มีไม่เพียงพอ (N = ${N})`;
      return;
    }

    let sumPre = 0, sumPost = 0, sumDiff = 0;
    pairedData.forEach(d => {
      sumPre += d.pre;
      sumPost += d.post;
      sumDiff += d.diff;
    });

    const meanPre = sumPre / N;
    const meanPost = sumPost / N;
    const meanDiff = sumDiff / N;

    let sumSqDiff = 0;
    pairedData.forEach(d => {
      sumSqDiff += Math.pow(d.diff - meanDiff, 2);
    });
    const sdDiff = Math.sqrt(sumSqDiff / (N - 1));
    const seDiff = sdDiff / Math.sqrt(N);
    const tStat = seDiff > 0 ? meanDiff / seDiff : 0;
    const df = N - 1;
    const pValue = calculateOneTailedPValue(tStat, df);

    rowEl.innerHTML = `
      <td class="fw-bold">ก่อนเรียน (T1) vs หลังเรียน (T2)</td>
      <td class="font-mono">${N}</td>
      <td class="font-mono fw-bold">${meanPre.toFixed(2)}</td>
      <td class="font-mono fw-bold">${meanPost.toFixed(2)}</td>
      <td class="font-mono text-primary fw-bold">+${meanDiff.toFixed(2)}</td>
      <td class="font-mono">${sdDiff.toFixed(2)}</td>
      <td class="font-mono text-danger fw-bold">${tStat.toFixed(4)}</td>
      <td class="font-mono">${df}</td>
      <td class="font-mono text-success fw-bold">${pValue < 0.001 ? '&lt; 0.001' : pValue.toFixed(4)}</td>
    `;

    const isSignificant = pValue < 0.05;
    let signStr = isSignificant 
      ? `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> มีนัยสำคัญทางสถิติที่ระดับ .05 (p = ${pValue < 0.001 ? '< .001' : pValue.toFixed(4)})</span>`
      : `<span class="text-danger fw-bold"><i class="bi bi-x-circle-fill"></i> ไม่มีนัยสำคัญทางสถิติที่ระดับ .05 (p = ${pValue.toFixed(4)})</span>`;

    interpEl.innerHTML = `
      <div class="mb-2"><strong>วิเคราะห์ผลต่างเฉลี่ยสัมฤทธิ์:</strong></div>
      <p class="mb-2 text-dark">
        จากนักเรียนกลุ่มตัวอย่างที่นำมาวิเคราะห์ N = ${N} คน พบว่า คะแนนเฉลี่ยก่อนเรียน (Pretest) อยู่ที่ <strong>${meanPre.toFixed(2)} คะแนน</strong> และคะแนนเฉลี่ยหลังเรียน (Posttest) อยู่ที่ <strong>${meanPost.toFixed(2)} คะแนน</strong> เพิ่มขึ้นโดยเฉลี่ย <strong>+${meanDiff.toFixed(2)} คะแนน</strong>
      </p>
      <div class="p-2.5 rounded bg-white border">
        <strong>สรุปผลสมมติฐานทางวิจัย:</strong> ${signStr} <br>
        <span class="text-muted small mt-1 d-block">${isSignificant ? 'สมมติฐานหลัก H0 ถูกปฏิเสธ: แสดงว่าการจัดการเรียนการสอนเขียนเรียงความภาษาไทยส่งผลให้ความสามารถในการเขียนของนักเรียนเพิ่มสูงขึ้นจริงอย่างเด่นชัด' : 'ยอมรับสมมติฐานหลัก H0: คะแนนเฉลี่ยหลังเรียนไม่แตกต่างจากก่อนเรียนอย่างเพียงพอในทางสถิติ'}</span>
      </div>
    `;

    // ค้นหาค่าความเบี่ยงเบนมาตรฐาน (SD) ของ Pretest และ Posttest
    let sumSqPre = 0, sumSqPost = 0;
    pairedData.forEach(d => {
      sumSqPre += Math.pow(d.pre - meanPre, 2);
      sumSqPost += Math.pow(d.post - meanPost, 2);
    });
    const sdPre = N > 1 ? Math.sqrt(sumSqPre / (N - 1)) : 0;
    const sdPost = N > 1 ? Math.sqrt(sumSqPost / (N - 1)) : 0;

    const ttestParagraphEl = document.getElementById('ttestReportParagraph');
    if (ttestParagraphEl) {
      const sigText = pValue < 0.05 
        ? `สูงกว่าก่อนเรียนอย่างมีนัยสำคัญทางสถิติที่ระดับ .05 (t(${df}) = ${tStat.toFixed(4)}, p = ${pValue < 0.001 ? '&lt; 0.001' : pValue.toFixed(4)}) ซึ่งเป็นไปตามสมมติฐานการวิจัย` 
        : `ไม่สูงกว่าก่อนเรียนอย่างมีนัยสำคัญทางสถิติที่ระดับ .05 (t(${df}) = ${tStat.toFixed(4)}, p = ${pValue.toFixed(4)})`;

      ttestParagraphEl.innerHTML = `
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-success"></i> บทเขียนสรุปผลสัมฤทธิ์วิจัยเชิงปริมาณ (Paired t-test Narrative)</h6>
        <p class="mb-0 text-slate-700" style="line-height: 1.6;">
          การศึกษาเปรียบเทียบผลสัมฤทธิ์ความสามารถในการเขียนเรียงความภาษาไทยของนักเรียนกลุ่มเป้าหมาย (N = ${N} คน) ก่อนเรียน (Pretest - T1) และหลังเรียน (Posttest - T2) จากคะแนนดิบประเมินของคุณครูผู้สอน 
          พบว่า <strong>คะแนนเฉลี่ยก่อนเรียนเท่ากับ ${meanPre.toFixed(2)} คะแนน (SD = ${sdPre.toFixed(2)})</strong> และ <strong>คะแนนเฉลี่ยหลังเรียนเท่ากับ ${meanPost.toFixed(2)} คะแนน (SD = ${sdPost.toFixed(2)})</strong> 
          เมื่อวิเคราะห์ทางสถิติเปรียบเทียบด้วย Paired t-test พบว่า คะแนนเฉลี่ยความสามารถงานเขียนหลังเรียนของนักเรียนสูงขึ้นเพิ่มขึ้นเฉลี่ย <strong>+${meanDiff.toFixed(2)} คะแนน</strong> และแตกต่างกันอย่างมีนัยสำคัญทางสถิติ โดยผลการทดสอบแสดงว่า คะแนนความสามารถงานเขียนของนักเรียน<strong>${sigText}</strong> แสดงให้เห็นว่านวัตกรรมการสอนเขียนเรียงความมีประสิทธิผลในการพัฒนาทักษะวิชาการผู้เรียนอย่างแท้จริง
        </p>
      `;
    }
  }

  function renderQualitativeHub() {
    const container = document.getElementById('qualitativeHubContainer');
    if (!container) return;

    if (!classroomResearchData) {
      container.innerHTML = '<div class="col-12 text-center text-muted">ไม่มีข้อมูลประมวลผล</div>';
      return;
    }

    const filterCriteria = document.getElementById('qualitativeCriteriaFilter').value;
    const query = document.getElementById('qualitativeSearchInput').value.toLowerCase().trim();

    const problems = classroomResearchData.problems;
    const peerReviews = classroomResearchData.peer_reviews;
    const reflections = classroomResearchData.reflections;

    // ประมวลผลบทวิเคราะห์เชิงคุณภาพ
    const qualParagraphEl = document.getElementById('qualitativeReportParagraph');
    if (qualParagraphEl) {
      const criteriaCounts = {};
      const subCriteriaList = ['1.1', '1.2', '1.3', '2.1', '2.2', '3.1', '3.2', '3.3', '4.1', '4.2', '4.3'];
      subCriteriaList.forEach(sc => { criteriaCounts[sc] = 0; });

      problems.forEach(p => {
        subCriteriaList.forEach(sc => {
          const key = sc.replace('.', '_');
          if (p['prob_' + key]) {
            criteriaCounts[sc]++;
          }
        });
      });

      const sortedCrit = Object.entries(criteriaCounts).sort((a,b) => b[1] - a[1]);
      const top1 = sortedCrit[0];
      const top2 = sortedCrit[1];

      const top1Name = top1 && top1[1] > 0 ? criteriaMap[top1[0]].name.split(' (')[0] : "ไม่มีข้อมูลหลัก";
      const top2Name = top2 && top2[1] > 0 ? criteriaMap[top2[0]].name.split(' (')[0] : "ไม่มีข้อมูลรอง";
      const top1Count = top1 ? top1[1] : 0;
      const top2Count = top2 ? top2[1] : 0;

      const totalPeer = peerReviews.length;
      const totalReflect = reflections.length;

      qualParagraphEl.innerHTML = `
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-success"></i> บทวิเคราะห์และอภิปรายเชิงคุณภาพ (Qualitative Content Analysis Summary)</h6>
        <p class="mb-0 text-slate-700" style="line-height: 1.6;">
          จากการวิเคราะห์เนื้อหาเชิงคุณภาพ (Content Analysis) จากบันทึกข้อมูลอุปสรรคและแนวทางแก้ไข (POA) การสะท้อนคิด และข้อเสนอแนะระหว่างการจัดการเรียนการสอนคลาสรูม พบประเด็นเชิงคุณภาพที่เด่นชัดดังนี้:
        </p>
        <ul class="mt-2 mb-0 ps-3 text-slate-700 small" style="line-height: 1.6;">
          <li class="mb-1"><strong>อุปสรรคการเขียนที่พบบ่อยที่สุด:</strong> อันดับแรกคือด้าน <strong>${top1Name}</strong> (พบปัญหาจากผู้เรียนจำนวน ${top1Count} คน) และอันดับรองลงมาคือด้าน <strong>${top2Name}</strong> (พบปัญหาจากผู้เรียนจำนวน ${top2Count} คน) ซึ่งสะท้อนถึงประเด็นอุปสรรคในการเขียนที่นักเรียนกลุ่มเป้าหมายส่วนใหญ่เผชิญและต้องให้ความสำคัญเพิ่มเติม</li>
          <li class="mb-1"><strong>การสะท้อนคิดและการมีส่วนร่วมของกลุ่มเพื่อน:</strong> มีการบันทึกการให้ข้อคิดเห็นเชิงคุณภาพและข้อเสนอแนะร่วมกันระหว่างเพื่อน (Peer Reviews) สะสมจำนวน <strong>${totalPeer} ครั้ง</strong> และแบบสะท้อนคิดการเรียนรู้เรียงความของตนเอง (Self Reflections) จำนวน <strong>${totalReflect} ครั้ง</strong></li>
          <li class="mb-1"><strong>แนวทางการลงข้อเสนอแนะและเป้าหมาย:</strong> จากคำแนะนำในการเขียนเรียงความ นักเรียนส่วนใหญ่วางกรอบแนวทางแก้ไขปัญหาโดยนำคำวิจารณ์ของเพื่อนและคำแนะแนวของครูผู้สอนมาปรับปรุงเรียงความ และตั้งเป้าหมายเชิงพัฒนาเพื่อเลือกใช้คำเชื่อมที่สละสลวยขึ้นในอนาคต</li>
        </ul>
      `;
    }

    let html = '';
    let cardCount = 0;

    problems.forEach(p => {
      const studentName = studentDB[p.student_id] || p.student_id;
      const subCriteria = ['1.1', '1.2', '1.3', '2.1', '2.2', '3.1', '3.2', '3.3', '4.1', '4.2', '4.3'];
      subCriteria.forEach(sc => {
        if (filterCriteria !== 'all' && filterCriteria !== sc) return;

        const key = sc.replace('.', '_');
        const probText = p['prob_' + key] || '';
        const solText = p['sol_' + key] || '';

        if (!probText && !solText) return;

        const matchSearch = !query || 
                            studentName.toLowerCase().includes(query) || 
                            p.student_id.toLowerCase().includes(query) || 
                            probText.toLowerCase().includes(query) || 
                            solText.toLowerCase().includes(query);

        if (!matchSearch) return;

        cardCount++;
        html += `
          <div class="col-md-6 col-sm-12 qualitative-card" data-criteria="${sc}">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-3 border-danger h-100 text-start">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-danger bg-opacity-10 text-danger fw-bold small">อุปสรรค & แนวคิดแก้ไข (POA)</span>
                <span class="badge bg-secondary font-mono small">${sc}</span>
              </div>
              <h6 class="fw-bold text-dark mb-1">${studentName} <span class="text-muted small">(${p.student_id})</span></h6>
              <div class="small text-secondary mb-2"><strong>เกณฑ์:</strong> ${criteriaMap[sc].name}</div>
              <div class="small mb-2" style="line-height: 1.5;">
                <span class="text-danger-emphasis fw-bold">อุปสรรคการเขียน:</span> ${probText}
              </div>
              <div class="small text-success-emphasis border-top pt-2" style="line-height: 1.5;">
                <strong>แนวทางแก้ไขที่ตั้งใจ:</strong> ${solText}
              </div>
            </div>
          </div>
        `;
      });
    });

    peerReviews.forEach(pr => {
      const studentName = studentDB[pr.student_id] || pr.student_id;
      const reviewerName = studentDB[pr.reviewer_id] || pr.reviewer_id;

      if (filterCriteria !== 'all') return;

      const strength = pr.strength || '';
      const improvement = pr.improvement || '';
      const encouragement = pr.encouragement || '';

      if (!strength && !improvement && !encouragement) return;

      const matchSearch = !query || 
                          studentName.toLowerCase().includes(query) || 
                          pr.student_id.toLowerCase().includes(query) || 
                          reviewerName.toLowerCase().includes(query) || 
                          strength.toLowerCase().includes(query) || 
                          improvement.toLowerCase().includes(query) || 
                          encouragement.toLowerCase().includes(query);

      if (!matchSearch) return;

      cardCount++;
      html += `
        <div class="col-md-6 col-sm-12 qualitative-card" data-criteria="peer_review">
          <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-3 border-primary h-100 text-start">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-primary bg-opacity-10 text-primary fw-bold small">คำแนะนำเชิงคุณภาพจากเพื่อน</span>
              <span class="text-muted small font-semibold">ผู้รีวิว: ${reviewerName}</span>
            </div>
            <h6 class="fw-bold text-dark mb-1">ถึง: ${studentName} <span class="text-muted small">(${pr.student_id})</span></h6>
            <div class="small mb-2" style="line-height: 1.5;">
              <span class="text-primary fw-bold">จุดเด่นที่ชื่นชอบ:</span> ${strength || '-'}
            </div>
            <div class="small mb-2 border-top pt-2" style="line-height: 1.5;">
              <span class="text-warning-emphasis fw-bold">จุดควรพัฒนาเพิ่มเติม:</span> ${improvement || '-'}
            </div>
            <div class="small text-success border-top pt-2" style="line-height: 1.5;">
              <strong>ข้อความให้กำลังใจ:</strong> ${encouragement || '-'}
            </div>
          </div>
        </div>
      `;
    });

    reflections.forEach(rf => {
      const studentName = studentDB[rf.student_id] || rf.student_id;

      if (filterCriteria !== 'all') return;

      const cs = rf.content_structure || '';
      const lm = rf.language_mechanics || '';
      const fa = rf.feedback_applied || '';
      const fg = rf.future_goals || '';

      if (!cs && !lm && !fa && !fg) return;

      const matchSearch = !query || 
                          studentName.toLowerCase().includes(query) || 
                          rf.student_id.toLowerCase().includes(query) || 
                          cs.toLowerCase().includes(query) || 
                          lm.toLowerCase().includes(query) || 
                          fa.toLowerCase().includes(query) || 
                          fg.toLowerCase().includes(query);

      if (!matchSearch) return;

      cardCount++;
      html += `
        <div class="col-md-6 col-sm-12 qualitative-card" data-criteria="reflection">
          <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-3 border-success h-100 text-start">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-success bg-opacity-10 text-success fw-bold small">แบบสะท้อนคิดหลังเรียนรู้นักเรียน</span>
              <span class="text-muted small font-mono">${rf.student_id}</span>
            </div>
            <h6 class="fw-bold text-dark mb-3">${studentName}</h6>
            <div class="small mb-2" style="line-height: 1.5;">
              <strong>1. โครงสร้างและเนื้อหาที่พัฒนา:</strong> ${cs || '-'}
            </div>
            <div class="small mb-2 border-top pt-2" style="line-height: 1.5;">
              <strong>2. การเลือกใช้ภาษาและอักขรวิธี:</strong> ${lm || '-'}
            </div>
            <div class="small mb-2 border-top pt-2" style="line-height: 1.5;">
              <strong>3. การนำข้อเสนอแนะเพื่อน/ครูไปปรับปรุง:</strong> ${fa || '-'}
            </div>
            <div class="small text-success border-top pt-2" style="line-height: 1.5;">
              <strong>4. เป้าหมายการเขียนเรียงความถัดไป:</strong> ${fg || '-'}
            </div>
          </div>
        </div>
      `;
    });

    if (cardCount === 0) {
      container.innerHTML = '<div class="col-12 text-center text-muted py-5">ไม่พบข้อมูลที่ตรงกับตัวกรองหรือคำค้นหานี้</div>';
    } else {
      container.innerHTML = html;
    }
  }

  function filterQualitativeHub() {
    renderQualitativeHub();
  }

  function exportQualitativeToCSV() {
    if (!classroomResearchData) return;

    let csvContent = "\uFEFF"; // UTF-8 BOM
    csvContent += "Student ID,Student Name,Data Type,Sub-criteria,Content / Problem / Strength,Solution / Improvement / Feedback,Encouragement / Goals\n";

    const problems = classroomResearchData.problems;
    const peerReviews = classroomResearchData.peer_reviews;
    const reflections = classroomResearchData.reflections;

    const escapeCSV = (str) => {
      if (!str) return '';
      return '"' + str.replace(/"/g, '""').replace(/\n/g, ' ') + '"';
    };

    problems.forEach(p => {
      const name = studentDB[p.student_id] || p.student_id;
      const subCriteria = ['1.1', '1.2', '1.3', '2.1', '2.2', '3.1', '3.2', '3.3', '4.1', '4.2', '4.3'];
      subCriteria.forEach(sc => {
        const key = sc.replace('.', '_');
        const prob = p['prob_' + key] || '';
        const sol = p['sol_' + key] || '';
        if (prob || sol) {
          csvContent += `${escapeCSV(p.student_id)},${escapeCSV(name)},Writing Problem,${sc},${escapeCSV(prob)},${escapeCSV(sol)},\n`;
        }
      });
    });

    peerReviews.forEach(pr => {
      const name = studentDB[pr.student_id] || pr.student_id;
      csvContent += `${escapeCSV(pr.student_id)},${escapeCSV(name)},Peer Review,,${escapeCSV(pr.strength)},${escapeCSV(pr.improvement)},${escapeCSV(pr.encouragement)}\n`;
    });

    reflections.forEach(rf => {
      const name = studentDB[rf.student_id] || rf.student_id;
      csvContent += `${escapeCSV(rf.student_id)},${escapeCSV(name)},Self Reflection,,${escapeCSV(rf.content_structure + " | " + rf.language_mechanics)},${escapeCSV(rf.feedback_applied)},${escapeCSV(rf.future_goals)}\n`;
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    link.setAttribute("href", url);
    link.setAttribute("download", "qualitative_content_analysis_data.csv");
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  function switchDashboardViewMode() {
    const selector = document.getElementById('dashboardViewMode');
    if (!selector) return;
    currentDashboardViewMode = selector.value;
    processDashboardData();
  }

  // ========== Essay Viewer Functions ==========
  let allEssaysCache = null;

  const essayPhaseLabels = {
    pretest:  'ก่อนเรียน (Pretest)',
    task1:    'ภารงาน หน่วยที่ 1',
    task2:    'ภารงาน หน่วยที่ 2',
    posttest: 'หลังเรียน (Posttest)'
  };
  const essayPhaseBadgeClass = {
    pretest: 'bg-primary',
    task1: 'bg-success',
    task2: 'bg-warning text-dark',
    posttest: 'bg-danger'
  };

  async function loadEssayViewer() {
    if (allEssaysCache) { renderEssayViewer(allEssaysCache); return; }
    const container = document.getElementById('essayViewerContainer');
    container.innerHTML = '<div class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>กำลังโหลดเรียงความ...</div>';
    try {
      const res = await fetch('api.php?action=get_all_essays');
      const data = await res.json();
      if (data.success) {
        allEssaysCache = data.essays;
        renderEssayViewer(data.essays);
      } else {
        container.innerHTML = `<div class="text-center py-5 text-danger fw-bold">เกิดข้อผิดพลาด: ${data.error}</div>`;
      }
    } catch(err) {
      container.innerHTML = '<div class="text-center py-5 text-danger fw-bold">ไม่สามารถโหลดข้อมูลได้</div>';
    }
  }

  function filterEssayViewer() {
    if (!allEssaysCache) return;
    renderEssayViewer(allEssaysCache);
  }

  function formatEssayHTML(contentStr) {
    if (!contentStr) return '<em class="text-muted">ไม่มีเนื้อหาเรียงความ</em>';
    try {
      const obj = JSON.parse(contentStr);
      if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
        let html = '';
        if (obj.introduction) {
          html += `
            <div class="mb-3">
              <span class="badge bg-primary bg-opacity-10 text-primary fw-bold mb-1"><i class="bi bi-pencil-fill me-1"></i>ส่วนคำนำ (Introduction)</span>
              <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${obj.introduction.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
            </div>`;
        }
        if (obj.body && Array.isArray(obj.body)) {
          obj.body.forEach((paraText, i) => {
            if (paraText) {
              html += `
                <div class="mb-3">
                  <span class="badge bg-success bg-opacity-10 text-success fw-bold mb-1"><i class="bi bi-book-fill me-1"></i>ส่วนเนื้อเรื่อง ย่อหน้าที่ ${i+1} (Body Paragraph)</span>
                  <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${paraText.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
                </div>`;
            }
          });
        }
        if (obj.conclusion) {
          html += `
            <div class="mb-0">
              <span class="badge bg-danger bg-opacity-10 text-danger fw-bold mb-1"><i class="bi bi-award-fill me-1"></i>ส่วนสรุป (Conclusion)</span>
              <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${obj.conclusion.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
            </div>`;
        }
        return html;
      }
    } catch(e) {}
    return `<div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${contentStr.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>`;
  }

  function renderEssayViewer(essays) {
    const phaseFilter = (document.getElementById('essayPhaseFilter') || {}).value || 'all';
    const query       = ((document.getElementById('essaySearchInput') || {}).value || '').toLowerCase().trim();
    const container   = document.getElementById('essayViewerContainer');

    let filtered = essays.filter(e => {
      if (phaseFilter !== 'all' && e.essay_phase !== phaseFilter) return false;
      
      let searchableText = e.essay_content || '';
      try {
        const obj = JSON.parse(e.essay_content);
        if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
          searchableText = (obj.introduction || '') + ' ' + (obj.body ? obj.body.join(' ') : '') + ' ' + (obj.conclusion || '');
        }
      } catch(err) {}

      if (query) {
        const combined = ((e.student_name || '') + ' ' + (e.student_id || '') + ' ' + (e.essay_title || '') + ' ' + searchableText).toLowerCase();
        if (!combined.includes(query)) return false;
      }
      return true;
    });

    // update summary stats
    const total    = filtered.length;
    const words    = filtered.map(e => parseInt(e.word_count || 0));
    const avgWords = total > 0 ? Math.round(words.reduce((a,b)=>a+b,0)/total) : 0;
    const maxWords = total > 0 ? Math.max(...words) : 0;
    const minWords = total > 0 ? Math.min(...words) : 0;

    const setEl = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val.toLocaleString('th-TH'); };
    setEl('essayStatTotal', total);
    setEl('essayStatAvgWords', avgWords);
    setEl('essayStatMaxWords', maxWords);
    setEl('essayStatMinWords', minWords);

    if (filtered.length === 0) {
      container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2"></i>ไม่พบเรียงความที่ตรงกับเงื่อนไข</div>';
      return;
    }

    container.innerHTML = filtered.map(e => {
      let previewText = '';
      try {
        const obj = JSON.parse(e.essay_content);
        if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
          const firstBody = (obj.body && obj.body[0]) ? obj.body[0] : '';
          previewText = `[คำนำ] ${obj.introduction || ''}\n[เนื้อเรื่อง] ${firstBody}...`;
        } else {
          previewText = e.essay_content || '';
        }
      } catch(err) {
        previewText = e.essay_content || '';
      }

      const previewTrunc = previewText.substring(0, 300).trim();
      const hasMore      = previewText.length > 300;
      const dt           = new Date(e.updated_at || e.created_at);
      const dateStr      = dt.toLocaleString('th-TH', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
      const badgeClass   = essayPhaseBadgeClass[e.essay_phase] || 'bg-secondary';
      const phaseLabel   = essayPhaseLabels[e.essay_phase] || e.essay_phase;
      const wordCount    = parseInt(e.word_count || 0).toLocaleString('th-TH');
      const essayId      = `essay_${e.student_id}_${e.essay_phase}`;
      const formattedHTML = formatEssayHTML(e.essay_content);

      return `
        <div class="card border-0 rounded-3 mb-3 shadow-sm" style="border-left: 4px solid #0d7377 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge ${badgeClass} px-2 py-1 small">${phaseLabel}</span>
                <span class="fw-bold text-dark">${e.student_name} <span class="text-muted fw-normal small">(${e.student_id})</span></span>
                ${e.essay_title ? `<span class="text-secondary small fst-italic">— ${e.essay_title}</span>` : ''}
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">
                  <i class="bi bi-fonts me-1"></i>${wordCount} คำ
                </span>
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 small" style="font-size:0.75rem;"
                  onclick="document.getElementById('${essayId}').classList.toggle('d-none')">
                  <i class="bi bi-eye me-1"></i>เปิดดูเต็มยศ
                </button>
              </div>
            </div>

            <!-- Preview -->
            <div class="text-secondary small mb-2 text-start" style="white-space:pre-wrap; line-height:1.6; background:#f8f9fa; padding:10px; border-radius:6px; max-height:100px; overflow:hidden;">
              ${previewTrunc.replace(/</g,'&lt;').replace(/>/g,'&gt;')}${hasMore ? '...' : ''}
            </div>

            <!-- Full content (hidden by default) -->
            <div id="${essayId}" class="d-none text-dark small mt-3 p-3 bg-light rounded-3" style="font-family:'Sarabun',sans-serif; background-color: #fffdf9 !important; border: 1px solid #f0e6c8 !important;">
              ${formattedHTML}
            </div>

            <div class="text-muted text-start mt-2" style="font-size:0.72rem;">
              <i class="bi bi-clock me-1"></i>บันทึกล่าสุด: ${dateStr}
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  function exportEssaysCSV() {
    if (!allEssaysCache) { showToast('กรุณาโหลดข้อมูลก่อน', 'error'); return; }
    const phaseFilter = (document.getElementById('essayPhaseFilter') || {}).value || 'all';
    const query       = ((document.getElementById('essaySearchInput') || {}).value || '').toLowerCase();

    let filtered = allEssaysCache.filter(e => {
      if (phaseFilter !== 'all' && e.essay_phase !== phaseFilter) return false;
      if (query) {
        const combined = ((e.student_name||'')+(e.essay_title||'')+(e.essay_content||'')).toLowerCase();
        if (!combined.includes(query)) return false;
      }
      return true;
    });

    const esc = s => '"' + (s||'').replace(/"/g,'""').replace(/\n/g,' ') + '"';
    let csv = '\uFEFF' + 'รหัสนักเรียน,ชื่อ-สกุล,รอบการประเมิน,ชื่อเรื่อง,จำนวนคำ,ส่วนคำนำ (Introduction),ส่วนเนื้อเรื่อง (Body),ส่วนสรุป (Conclusion),วันที่บันทึก\n';
    filtered.forEach(e => {
      const dt = new Date(e.updated_at||e.created_at).toLocaleString('th-TH');
      let intro = '';
      let bodyText = '';
      let conc = '';
      try {
        const obj = JSON.parse(e.essay_content);
        if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
          intro = obj.introduction || '';
          bodyText = obj.body ? obj.body.join('\n\n') : '';
          conc = obj.conclusion || '';
        } else {
          intro = e.essay_content || '';
        }
      } catch(err) {
        intro = e.essay_content || '';
      }
      csv += [esc(e.student_id), esc(e.student_name), esc(essayPhaseLabels[e.essay_phase]||e.essay_phase),
              esc(e.essay_title), e.word_count, esc(intro), esc(bodyText), esc(conc), esc(dt)].join(',') + '\n';
    });

    const blob = new Blob([csv], { type:'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'student_essays.csv';
    link.click();
  }
  // ============================================

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

  function drawClassDimensionAverages(sums, count) {
    const canvas = document.getElementById('classDimensionAveragesChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (classDimensionChartInstance) classDimensionChartInstance.destroy();
    
    const avgContent = count > 0 ? (sums.content / count) : 0;
    const avgStructure = count > 0 ? (sums.structure / count) : 0;
    const avgLanguage = count > 0 ? (sums.language / count) : 0;
    const avgMechanics = count > 0 ? (sums.mechanics / count) : 0;
    
    const pctContent = ((avgContent / 27) * 100).toFixed(2);
    const pctStructure = ((avgStructure / 12) * 100).toFixed(2);
    const pctLanguage = ((avgLanguage / 15) * 100).toFixed(2);
    const pctMechanics = ((avgMechanics / 6) * 100).toFixed(2);
    
    classDimensionChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['1. เนื้อหา (เต็ม 27)', '2. โครงสร้าง (เต็ม 12)', '3. ภาษา (เต็ม 15)', '4. อักขรวิธี (เต็ม 6)'],
        datasets: [{
          label: 'ร้อยละคะแนนเฉลี่ย',
          data: [pctContent, pctStructure, pctLanguage, pctMechanics],
          backgroundColor: ['rgba(37, 99, 235, 0.85)', 'rgba(139, 92, 246, 0.85)', 'rgba(245, 158, 11, 0.85)', 'rgba(16, 185, 129, 0.85)'],
          borderColor: ['#2563eb', '#8b5cf6', '#f59e0b', '#10b981'],
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
                let rawStr = '';
                if (context.dataIndex === 0) rawStr = ` (${(val/100*27).toFixed(2)}/27 คะแนน)`;
                else if (context.dataIndex === 1) rawStr = ` (${(val/100*12).toFixed(2)}/12 คะแนน)`;
                else if (context.dataIndex === 2) rawStr = ` (${(val/100*15).toFixed(2)}/15 คะแนน)`;
                else if (context.dataIndex === 3) rawStr = ` (${(val/100*6).toFixed(2)}/6 คะแนน)`;
                return `คิดเป็นร้อยละ: ${val}%${rawStr}`;
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
          x: { ticks: { font: { family: 'Google Sans', size: 10 } } }
        }
      }
    });
  }

  function drawClassroomDimensionLines(res) {
    const canvas = document.getElementById('classDimensionLinesChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    if (classDimensionLinesChartInstance) classDimensionLinesChartInstance.destroy();
    
    const datasets = [];
    const sortedKeys = Object.keys(studentDB).sort();
    
    let lineCount = 0;
    sortedKeys.forEach(id => {
      const studentData = res.data && res.data[id] ? res.data[id] : null;
      // กราฟนี้จะวาดเส้นแสดงคะแนนเฉลี่ยรายบุคคล ทันทีที่มีการประเมินอย่างน้อย 1 รายการ โดยไม่ต้องรอครบ 3 มิติ (ตนเอง/เพื่อน/ครู)
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
        const colorDefault = `hsla(${hue}, 60%, 60%, 0.15)`; // เส้นจางลงโดยเริ่มต้นเพื่อให้อ่านง่าย
        const colorHover = `hsla(${hue}, 85%, 45%, 1.0)`; // เส้นเข้มขึ้นชัดเจนเมื่อเอาเมาส์ชี้ (Hover)
        
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
          fill: false,
          tension: 0.15,
          pointBackgroundColor: colorDefault,
          pointRadius: 1,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: colorHover,
          hoverBorderColor: colorHover,
          hoverBorderWidth: 4
        });
        lineCount++;
      }
    });
    
    classDimensionLinesChartInstance = new Chart(ctx, {
      type: 'line',
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
                const val = context.parsed.y;
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
                
                // ค้นหาจำนวนรายการประเมินที่ส่งแล้ว
                const studentId = studentInfo.split(' - ')[0];
                const studentData = res.data && res.data[studentId] ? res.data[studentId] : null;
                const countStr = studentData ? ` [ประเมินแล้ว ${studentData.count}/3 รายการ]` : '';
                
                return `${studentInfo}: ${val}%${rawStr}${countStr}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: { stepSize: 20 },
            title: { display: true, text: 'ร้อยละจากคะแนนเต็ม (%)', font: { family: 'Google Sans', size: 10, weight: 'bold' } }
          },
          x: { ticks: { font: { family: 'Google Sans', size: 10 } } }
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
      if (currentDashboardViewMode === 'task') {
        if(row.cells.length < 9) return;

        const studentId = row.cells[0].textContent.toLowerCase();
        const name = row.cells[1].textContent.toLowerCase();
        
        const hasSelf = row.cells[2].innerHTML.includes('ส่งแล้ว');
        const hasPeer = row.cells[3].innerHTML.includes('ส่งแล้ว');
        const hasTeacher = row.cells[4].innerHTML.includes('ส่งแล้ว');
        
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

    drawScoresChart(selfRecord, peerRecord, teacherRecord);
    drawRadarChart(selfRecord, peerRecord, teacherRecord);
    runConsistencyAnalysis(selfRecord, peerRecord, teacherRecord);
    renderConsistencyTable(selfRecord, peerRecord, teacherRecord);
    
    document.getElementById('feedbackContainer').innerHTML = generateQualitativeFeedback(res.data);
    
    // โหลดข้อมูลสะท้อนคิด (reflection data)
    const studentId = document.getElementById('dashStudentId').textContent;
    fetchReflectionData(studentId);
    
    document.getElementById('searchResults').classList.remove('d-none');
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
