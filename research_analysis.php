<?php
$page_title = 'ระบบวิเคราะห์สถิติงานวิจัย (ICC & Paired t-test) - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<style>
  html { scroll-behavior: smooth; }
  #section-icc, #section-ttest, #section-qual, #section-essay { scroll-margin-top: 84px; }
</style>

<div class="text-start">
  <div class="mb-3">
    <a href="dashboard.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับไปแดชบอร์ด
    </a>
  </div>

  <!-- 🔬 สถิติตรวจสอบคุณภาพเกณฑ์และการวิจัย (Research Statistics & Inter-Rater Reliability Dashboard) -->
  <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden" id="researchStatsCard">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
      <h6 class="fw-bold mb-0 text-white"><i class="bi bi-gear-wide-connected text-warning"></i> ระบบวิเคราะห์ทางสถิติเพื่อการทำวิจัยและตรวจสอบคุณภาพเกณฑ์ประเมิน (Inter-rater &amp; Paired t-test)</h6>
      <span class="badge bg-warning text-dark font-mono font-semibold">One-Group Pretest-Posttest Design</span>
    </div>
    <div class="alert alert-primary rounded-0 mb-0 py-2 px-4 small border-0 d-flex align-items-center gap-2">
      <i class="bi bi-info-circle-fill"></i>
      <span><strong>กลุ่มทดลอง (Experimental Group):</strong> ระบบกำหนดนักเรียน<strong>ห้อง 606</strong> เป็นกลุ่มทดลองของงานวิจัยชุดนี้ ทุกส่วนของหน้านี้ — ค่า ICC (คะแนนรวม + 4 ด้าน), Paired t-test, ศูนย์วิเคราะห์เชิงคุณภาพ และเรียงความนักเรียน — กรองให้แสดงเฉพาะข้อมูลของนักเรียนห้อง 606 เท่านั้น (ตั้งค่าห้องเรียนของนักเรียนได้ที่หน้า <a href="manage_students.php" class="alert-link">จัดการข้อมูลนักเรียน</a>)</span>
    </div>
    <div class="card-body p-4 text-start">

      <!-- แถบสรุปตัวเลขสำคัญ (KPI Overview) -->
      <div class="row g-3 mb-4" id="researchKpiRow">
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light h-100">
            <div class="text-muted small fw-semibold mb-1 lh-sm"><i class="bi bi-people-fill me-1 text-primary"></i>ICC ภาพรวม</div>
            <div class="fs-4 fw-bold text-primary mb-1" id="kpiIccOverall">-</div>
            <span class="badge bg-secondary" id="kpiIccBadge">รอข้อมูล</span>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light h-100">
            <div class="text-muted small fw-semibold mb-1 lh-sm"><i class="bi bi-graph-up-arrow me-1 text-success"></i>Paired t-test</div>
            <div class="fs-4 fw-bold text-success mb-1" id="kpiTtestValue">-</div>
            <span class="badge bg-secondary" id="kpiTtestBadge">รอข้อมูล</span>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light h-100">
            <div class="text-muted small fw-semibold mb-1 lh-sm"><i class="bi bi-chat-square-text-fill me-1 text-warning-emphasis"></i>ข้อมูลเชิงคุณภาพ</div>
            <div class="fs-4 fw-bold text-warning-emphasis mb-1" id="kpiQualCount">-</div>
            <span class="text-muted small">รายการที่บันทึกไว้</span>
          </div>
        </div>
        <div class="col-md-3 col-6">
          <div class="card border-0 rounded-3 p-3 text-center bg-light h-100">
            <div class="text-muted small fw-semibold mb-1 lh-sm"><i class="bi bi-pencil-square me-1 text-danger"></i>เรียงความ</div>
            <div class="fs-4 fw-bold text-danger mb-1" id="kpiEssayCount">-</div>
            <span class="text-muted small">ฉบับที่ส่งแล้ว</span>
          </div>
        </div>
      </div>

      <!-- ปุ่มลัดไปยังแต่ละส่วน -->
      <div class="d-flex flex-wrap gap-2 mb-4" id="sectionQuickNav">
        <a href="#section-icc" class="btn btn-outline-primary btn-sm fw-bold rounded-pill px-3">
          <i class="bi bi-people-fill"></i> ICC
        </a>
        <a href="#section-ttest" class="btn btn-outline-success btn-sm fw-bold rounded-pill px-3">
          <i class="bi bi-graph-up-arrow"></i> Paired t-test
        </a>
        <a href="#section-qual" class="btn btn-outline-warning btn-sm fw-bold rounded-pill px-3">
          <i class="bi bi-chat-square-text-fill"></i> Content Analysis Hub
        </a>
        <a href="#section-essay" class="btn btn-outline-danger btn-sm fw-bold rounded-pill px-3">
          <i class="bi bi-pencil-square"></i> Essay Viewer
        </a>
      </div>

        <!-- ส่วนที่ 1: ICC -->
        <section id="section-icc" class="mb-5">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-primary border-2">
            <span class="badge bg-primary fs-6">1</span>
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill text-primary"></i> ค่าความสอดคล้องผู้ตรวจ 3 คน (ICC)</h5>
          </div>
          <div class="d-flex flex-wrap justify-content-end mb-3">
            <div class="input-group" style="width: auto;">
              <span class="input-group-text bg-white small border-end-0 text-nowrap"><i class="bi bi-calendar-check"></i> ภารงานที่ใช้คำนวณ ICC</span>
              <select id="iccTaskPhaseSelector" class="form-select form-select-sm bg-white border-start-0 small" onchange="switchICCTaskPhase()">
                <option value="task1" selected>หน่วยที่ 1</option>
                <option value="task2">หน่วยที่ 2</option>
              </select>
            </div>
          </div>
          <div class="row g-4">
            <div class="col-md-5 col-sm-12">
              <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-primary h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle-fill text-primary"></i> การตีความความสอดคล้องผู้ตรวจ (Koo &amp; Li, 2016)</h6>
                <p class="small text-muted mb-3" style="line-height: 1.6;">
                  คำนวณค่าสัมประสิทธิ์สหสัมพันธ์ภายในชั้น (Intraclass Correlation Coefficient — ICC) ของคะแนนที่ให้โดยผู้ตรวจ 3 คน คือ <strong>ครูผู้สอน + ผู้เชี่ยวชาญ 2 ท่าน</strong> สำหรับงานของนักเรียน<strong>ห้อง 606 (กลุ่มทดลอง)</strong> เพื่อตรวจสอบความสอดคล้องระหว่างผู้ตรวจ (Inter-rater Reliability) — ใช้โมเดล ICC(2,1) two-way random, absolute agreement
                </p>
                <div class="d-flex flex-column gap-2 small">
                  <div class="d-flex justify-content-between p-2 rounded bg-success bg-opacity-10 text-success fw-semibold">
                    <span>ICC &ge; 0.90</span><span>ดีเยี่ยม (Excellent)</span>
                  </div>
                  <div class="d-flex justify-content-between p-2 rounded bg-info bg-opacity-10 text-info fw-semibold">
                    <span>0.75 &le; ICC &lt; 0.90</span><span>ดี (Good)</span>
                  </div>
                  <div class="d-flex justify-content-between p-2 rounded bg-warning bg-opacity-10 text-warning-emphasis fw-semibold">
                    <span>0.50 &le; ICC &lt; 0.75</span><span>ปานกลาง (Moderate)</span>
                  </div>
                  <div class="d-flex justify-content-between p-2 rounded bg-danger bg-opacity-10 text-danger fw-semibold">
                    <span>ICC &lt; 0.50</span><span>ต่ำ (Poor)</span>
                  </div>
                </div>
                <div class="border-top pt-3 mt-3">
                  <div class="text-secondary small fw-bold">ICC ภาพรวม (คะแนนรวม)</div>
                  <h2 class="fw-bold text-primary font-outfit mt-1" id="overallPearsonResult">-</h2>
                  <span id="overallPearsonInterpretation" class="badge bg-secondary">ไม่มีข้อมูล</span>
                </div>
              </div>
            </div>
            <div class="col-md-7 col-sm-12">
              <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-warning h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-steps text-warning"></i> ค่า ICC รายด้าน (คะแนนรวม + 4 ด้าน)</h6>
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                  <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="table-light text-secondary">
                      <tr>
                        <th class="text-nowrap">มิติคะแนน</th>
                        <th class="text-center text-nowrap">จำนวน (N)</th>
                        <th class="text-center text-nowrap">ค่า ICC</th>
                        <th class="text-end text-nowrap">ผลประเมิน</th>
                      </tr>
                    </thead>
                    <tbody id="reliabilityTableBody">
                      <tr><td colspan="4" class="text-center py-4 text-muted">กรุณารอประมวลผลข้อมูล...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- ตารางคะแนนผู้ตรวจ 3 คน รายบุคคล (ห้อง 606) + ICC ท้ายตาราง -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-info">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-people-fill text-info"></i> คะแนนผู้ตรวจ 3 คน รายบุคคล (เฉพาะห้อง 606)</h6>
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                  <table class="table table-sm table-hover align-middle mb-0 small">
                    <thead class="table-light text-secondary">
                      <tr>
                        <th class="text-nowrap">รหัส</th>
                        <th class="text-nowrap">ชื่อ-สกุล</th>
                        <th class="text-center text-nowrap">ครูผู้สอน</th>
                        <th class="text-center text-nowrap">ผู้เชี่ยวชาญ 1</th>
                        <th class="text-center text-nowrap">ผู้เชี่ยวชาญ 2</th>
                      </tr>
                    </thead>
                    <tbody id="iccStudentTableBody">
                      <tr><td colspan="5" class="text-center py-4 text-muted">รอประมวลผลข้อมูล...</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- ICC interpretation paragraph -->
          <div class="row mt-3">
            <div class="col-12">
              <div id="pearsonReportParagraph" class="card border-0 rounded-3 p-3 text-secondary small bg-light border-start border-3 border-secondary" style="line-height: 1.6;">
                <em>กำลังรอผลสัมประสิทธิ์ความสอดคล้อง...</em>
              </div>
            </div>
          </div>
        </section>

        <!-- ส่วนที่ 2: Paired t-test -->
        <section id="section-ttest" class="mb-5">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-success border-2">
            <span class="badge bg-success fs-6">2</span>
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up-arrow text-success"></i> ประสิทธิภาพการพัฒนาการเขียน (Paired t-test)</h5>
          </div>
          <div class="row g-4">
            <div class="col-md-4 col-sm-12">
              <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-success h-100">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders text-success"></i> ตัวแปรวิจัยเชิงสถิติ</h6>
                <div class="mb-3">
                  <label class="form-label small fw-bold text-secondary">แหล่งที่มาของคะแนนดิบที่จับคู่:</label>
                  <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 px-3 py-2.5 fs-7 w-100 text-start fw-bold rounded-3">
                    <i class="bi bi-person-workspace me-1"></i> คุณครูผู้สอน (ประเมิน T1 &amp; T2 ครบถ้วน)
                  </div>
                  <select id="ttestReviewerSelector" class="d-none">
                    <option value="teacher" selected>คะแนนจากคุณครูผู้สอน</option>
                  </select>
                </div>
                <p class="small text-muted" style="line-height: 1.6;">
                  เปรียบเทียบคะแนนเขียนเรียงความของนักเรียน<strong>ห้อง 606 (กลุ่มทดลอง)</strong> คนเดียวกันระหว่างก่อนเรียน (Pretest - T1) กับหลังเรียน (Posttest - T2) ด้วยสถิติ **Paired t-test (Dependent t-test)** เพื่อทดสอบประสิทธิภาพของแนวทางการจัดการเรียนการสอนแบบ POA
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
                        <th class="text-nowrap">ตัวแปรการทดสอบ</th>
                        <th class="text-nowrap">N</th>
                        <th class="text-nowrap">Mean ก่อนเรียน (T1)</th>
                        <th class="text-nowrap">Mean หลังเรียน (T2)</th>
                        <th class="text-nowrap">ผลต่างเฉลี่ย (D)</th>
                        <th class="text-nowrap">SD ของผลต่าง (SD_D)</th>
                        <th class="text-nowrap">ค่าสถิติ t</th>
                        <th class="text-nowrap">องศาอิสระ (df)</th>
                        <th class="text-nowrap">ค่าความนัยสำคัญ (p-value)</th>
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
        </section>

        <!-- ส่วนที่ 3: Qualitative Content Analysis Hub -->
        <section id="section-qual" class="mb-5">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-warning border-2">
            <span class="badge bg-warning text-dark fs-6">3</span>
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-chat-square-text-fill text-warning-emphasis"></i> ศูนย์วิเคราะห์เชิงคุณภาพ (Content Analysis Hub)</h5>
          </div>

          <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3 border flex-wrap" id="qualViewTab" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active fw-bold text-dark px-4 py-2.5 rounded-3 d-flex align-items-center gap-2" id="qual-overview-tab" data-bs-toggle="pill" data-bs-target="#qual-overview-pane" type="button" role="tab" aria-selected="true">
                <i class="bi bi-bar-chart-steps"></i> ภาพรวม
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link fw-bold text-dark px-4 py-2.5 rounded-3 d-flex align-items-center gap-2" id="qual-individual-tab" data-bs-toggle="pill" data-bs-target="#qual-individual-pane" type="button" role="tab" aria-selected="false">
                <i class="bi bi-person-lines-fill"></i> รายบุคคล <span class="badge bg-secondary ms-1" id="qualIndividualCountBadge">0</span>
              </button>
            </li>
          </ul>

          <div class="tab-content">

            <!-- ภาพรวม: อุปสรรค/จุดแข็งรายด้าน เพื่อวางแผนขั้น Enabling (POA) -->
            <div class="tab-pane fade show active" id="qual-overview-pane" role="tabpanel" aria-labelledby="qual-overview-tab">
              <div id="qualitativeReportParagraph" class="card border-0 rounded-3 p-3 text-secondary small bg-light border-start border-3 border-success mb-4" style="line-height: 1.6;">
                <em>กำลังประมวลผลข้อเขียนวิเคราะห์ข้อมูลเชิงคุณภาพ...</em>
              </div>

              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-danger h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-exclamation-triangle-fill text-danger"></i> ความถี่ของอุปสรรคการเขียน รายด้าน (11 ด้าน)</h6>
                    <div style="position: relative; height: 420px;" class="w-100">
                      <canvas id="qualProblemFreqChart"></canvas>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-primary h-100">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-people-fill text-primary"></i> คะแนนประเมินจากเพื่อนเฉลี่ย รายด้าน (เต็ม 4)</h6>
                    <div style="position: relative; height: 420px;" class="w-100">
                      <canvas id="qualPeerScoreChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- รายบุคคล: การ์ดข้อมูลดิบรายคน -->
            <div class="tab-pane fade" id="qual-individual-pane" role="tabpanel" aria-labelledby="qual-individual-tab">
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

              <!-- กล่องแสดงข้อความสำหรับการทำวิเคราะห์เนื้อหา (Content Analysis Cards) -->
              <div class="row g-3" id="qualitativeHubContainer" style="max-height: 480px; overflow-y: auto;">
                <!-- Cards created by JS dynamically -->
                <div class="col-12 text-center py-5 text-muted">กำลังโหลดข้อความเชิงคุณภาพเพื่อใช้วิเคราะห์เนื้อหา...</div>
              </div>
            </div>

          </div>
        </section>

        <!-- ส่วนที่ 4: Essay Viewer -->
        <section id="section-essay">
          <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-danger border-2">
            <span class="badge bg-danger fs-6">4</span>
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square text-danger"></i> เรียงความนักเรียน (Essay Viewer)</h5>
          </div>
          <!-- Controls row -->
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
              <p class="text-muted small mb-0">ตรวจสอบเนื้อหาเรียงความที่นักเรียนพิมพ์ส่งเพื่อประกอบการวิเคราะห์เชิงคุณภาพ (เฉพาะกลุ่มทดลอง ห้อง 606)</p>
              <a href="essay_viewer.php" class="small fw-bold text-danger text-decoration-none"><i class="bi bi-box-arrow-up-right me-1"></i>ดูเรียงความของนักเรียนทุกคน ทุกห้อง แยกกลุ่มทดลอง/กลุ่มตัวอย่าง</a>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
              <select id="essayPhaseFilter" onchange="filterEssayViewer()" class="form-select form-select-sm border-2 rounded-pill" style="width:auto;">
                <option value="all">ทุกรอบ</option>
                <option value="pretest">ก่อนเรียน</option>
                <option value="task1">ภารงาน หน่วยที่ 1</option>
                <option value="task2">ภารงาน หน่วยที่ 2</option>
                <option value="posttest">หลังเรียน</option>
              </select>
              <input type="text" id="essaySearchInput" onkeyup="filterEssayViewer()" class="form-control form-control-sm border-2 rounded-pill" placeholder="ค้นหาชื่อหรือเนื้อหา..." style="width:220px;">
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
              <i class="bi bi-hourglass-split fs-3 d-block mb-2"></i>กำลังโหลดเรียงความ...
            </div>
          </div>
        </section>

    </div>
  </div>
</div>

<script>
  // ค่าคงที่ของงานวิจัย: ห้อง 606 คือกลุ่มทดลองสำหรับทุกองค์ประกอบของ ICC และ Paired t-test
  const EXPERIMENTAL_CLASSROOM = '606';

  let studentDB = {};
  let classroomResearchData = null;
  let icctaskPhase = 'task1';

  // คืนค่าเซตของรหัสนักเรียนห้อง 606 (กลุ่มทดลอง) เพื่อกรองข้อมูลทุกส่วนของหน้านี้ให้ตรงกัน
  function getExperimentalStudentIds() {
    if (!classroomResearchData || !Array.isArray(classroomResearchData.students)) return new Set();
    return new Set(
      classroomResearchData.students
        .filter(s => s.classroom === EXPERIMENTAL_CLASSROOM)
        .map(s => s.student_id)
    );
  }

  // อัปเดตแถบสรุปตัวเลขสำคัญ (KPI) ด้านบนของหน้า
  function setKpiValue(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  }
  function setKpiBadge(id, text, cssClass) {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = text;
      el.className = 'badge ' + cssClass;
    }
  }

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

  // โหลดข้อมูลดิบทั้งหมดของชั้นเรียน แล้วประมวลผลทุกส่วนของหน้านี้
  async function loadResearchData() {
    try {
      const response = await fetch(`api.php?action=get_classroom_research_data&_t=${new Date().getTime()}`);
      const res = await response.json();
      if (!res.success) {
        console.error("API error:", res.error);
        return;
      }

      classroomResearchData = res;
      // แปลง evaluator_type จากภาษาไทย → รหัสอังกฤษ ให้ตรงกับที่โค้ดหน้านี้ใช้เทียบ (self/peer/teacher/expert)
      if (Array.isArray(classroomResearchData.evaluations)) {
        const _typeMap = { 'ตนเองประเมิน': 'self', 'เพื่อนประเมิน': 'peer', 'ครูประเมิน': 'teacher', 'ผู้เชี่ยวชาญประเมิน': 'expert' };
        classroomResearchData.evaluations.forEach(ev => {
          if (_typeMap[ev.evaluator_type]) ev.evaluator_type = _typeMap[ev.evaluator_type];
        });
      }

      buildAndRenderICC();
      calculateResearchTTest();
      renderQualitativeHub();
    } catch (err) {
      console.error(err);
    }
  }

  function switchICCTaskPhase() {
    const sel = document.getElementById('iccTaskPhaseSelector');
    if (!sel) return;
    icctaskPhase = sel.value;
    buildAndRenderICC();
  }

  // สร้างชุดข้อมูลคะแนนผู้ตรวจ 3 คน (ครู + ผู้เชี่ยวชาญ 2 คน) เฉพาะนักเรียนห้อง 606 (กลุ่มทดลอง) เพื่อคำนวณ ICC
  function buildAndRenderICC() {
    if (!classroomResearchData) return;

    const studentsList = classroomResearchData.students;
    const evaluations = classroomResearchData.evaluations;

    const studentEvals = {};
    studentsList.forEach(s => {
      studentEvals[s.student_id] = { task1: [], task2: [] };
    });
    evaluations.forEach(ev => {
      if (studentEvals[ev.student_id] && studentEvals[ev.student_id][ev.test_phase] !== undefined) {
        studentEvals[ev.student_id][ev.test_phase].push(ev);
      }
    });

    const raterTriples = [];
    studentsList.forEach(s => {
      if (s.classroom !== EXPERIMENTAL_CLASSROOM) return;
      const id = s.student_id;
      const taskEvs = studentEvals[id][icctaskPhase] || [];
      const teacherEval = taskEvs.find(e => e.evaluator_type === 'teacher');
      const expert1Eval = taskEvs.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 1' || e.evaluator_name === 'admin1'));
      const expert2Eval = taskEvs.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 2' || e.evaluator_name === 'admin2'));

      if (teacherEval && expert1Eval && expert2Eval) {
        raterTriples.push({ sid: id, name: (studentDB[id] || s.student_name || id), evals: [teacherEval, expert1Eval, expert2Eval] });
      }
    });

    calculateICCReliability(raterTriples);
  }

  function getICCInterpretation(icc) {
    if (icc === null || isNaN(icc)) return { text: 'ข้อมูลน้อยเกินไป', css: 'bg-secondary' };
    if (icc >= 0.90) return { text: 'ดีเยี่ยม (Excellent)', css: 'bg-success' };
    if (icc >= 0.75) return { text: 'ดี (Good)', css: 'bg-info text-dark' };
    if (icc >= 0.50) return { text: 'ปานกลาง (Moderate)', css: 'bg-warning text-dark' };
    return { text: 'ต่ำ (Poor)', css: 'bg-danger' };
  }

  // คำนวณ ICC(2,1): two-way random-effects, absolute agreement, single rater
  function computeICC(matrix) {
    const n = matrix.length;
    if (n < 2) return null;
    const k = matrix[0].length;
    if (k < 2) return null;
    let grand = 0;
    for (const row of matrix) for (const v of row) grand += v;
    grand /= (n * k);
    const rowMeans = matrix.map(r => r.reduce((a, b) => a + b, 0) / k);
    const colMeans = [];
    for (let j = 0; j < k; j++) { let s = 0; for (let i = 0; i < n; i++) s += matrix[i][j]; colMeans.push(s / n); }
    let SSR = 0; for (const rm of rowMeans) SSR += (rm - grand) ** 2; SSR *= k;
    let SSC = 0; for (const cm of colMeans) SSC += (cm - grand) ** 2; SSC *= n;
    let SST = 0; for (const row of matrix) for (const v of row) SST += (v - grand) ** 2;
    const SSE = SST - SSR - SSC;
    const MSR = SSR / (n - 1);
    const MSC = SSC / (k - 1);
    const MSE = SSE / ((n - 1) * (k - 1));
    const denom = MSR + (k - 1) * MSE + (k / n) * (MSC - MSE);
    if (denom === 0) return null;
    return (MSR - MSE) / denom;
  }

  function calculateICCReliability(triples) {
    const overallEl = document.getElementById('overallPearsonResult');
    const interpEl = document.getElementById('overallPearsonInterpretation');
    const tableBody = document.getElementById('reliabilityTableBody');
    if (!overallEl || !tableBody) return;

    if (triples.length < 2) {
      overallEl.textContent = "N/A";
      interpEl.textContent = "ต้องมีนักเรียนห้อง 606 ที่ถูกตรวจครบทั้ง 3 คน อย่างน้อย 2 คน";
      interpEl.className = "badge bg-secondary";
      tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">ต้องการข้อมูลนักเรียน<strong>ห้อง 606</strong>ที่ครูผู้สอน + ผู้เชี่ยวชาญ 2 คน ตรวจครบ อย่างน้อย 2 คน</td></tr>`;
      const p = document.getElementById('pearsonReportParagraph');
      if (p) p.innerHTML = `<h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-primary"></i> บทวิเคราะห์ค่าความสอดคล้องผู้ตรวจ (ICC)</h6><p class="mb-0 text-muted">ยังมีข้อมูลไม่พอสำหรับคำนวณ ICC — ต้องมีนักเรียนห้อง 606 ที่ผู้ตรวจครบ 3 คนอย่างน้อย 2 คน</p>`;
      setKpiValue('kpiIccOverall', 'N/A');
      setKpiBadge('kpiIccBadge', 'ข้อมูลไม่พอ', 'bg-secondary');
      return;
    }

    // มิติคะแนน: รวม + 4 ด้าน
    const dims = [
      { name: 'คะแนนรวม (Total 60)', get: e => Number(e.total_score) },
      { name: 'ด้านเนื้อหา (Content)', get: e => Number(e.score_1_1) + Number(e.score_1_2) + Number(e.score_1_3) },
      { name: 'ด้านโครงสร้าง (Structure)', get: e => Number(e.score_2_1) + Number(e.score_2_2) },
      { name: 'ด้านการใช้ภาษา (Language)', get: e => Number(e.score_3_1) + Number(e.score_3_2) + Number(e.score_3_3) },
      { name: 'ด้านอักขรวิธี (Mechanics)', get: e => Number(e.score_4_1) + Number(e.score_4_2) + Number(e.score_4_3) }
    ];

    // ภาพรวม = คะแนนรวม
    const totalMatrix = triples.map(tr => tr.evals.map(dims[0].get));
    const overallICC = computeICC(totalMatrix);
    overallEl.textContent = overallICC !== null ? overallICC.toFixed(4) : "N/A";
    const overallInterp = getICCInterpretation(overallICC);
    interpEl.textContent = overallInterp.text;
    interpEl.className = "badge " + overallInterp.css;
    setKpiValue('kpiIccOverall', overallICC !== null ? overallICC.toFixed(3) : 'N/A');
    setKpiBadge('kpiIccBadge', overallInterp.text.split(' (')[0], overallInterp.css);

    let html = '';
    const iccVals = [];
    dims.forEach(d => {
      const m = triples.map(tr => tr.evals.map(d.get));
      const icc = computeICC(m);
      if (icc !== null) iccVals.push(icc);
      const interp = getICCInterpretation(icc);
      html += `
        <tr>
          <td class="fw-semibold">${d.name}</td>
          <td class="text-center font-mono">${triples.length}</td>
          <td class="text-center font-mono fw-bold text-primary">${icc !== null ? icc.toFixed(4) : "N/A"}</td>
          <td class="text-end"><span class="badge ${interp.css} small">${interp.text.split(' (')[0]}</span></td>
        </tr>
      `;
    });
    tableBody.innerHTML = html;

    // ตารางคะแนนผู้ตรวจ 3 คน รายบุคคล + ICC ท้ายตาราง
    const stBody = document.getElementById('iccStudentTableBody');
    if (stBody) {
      let sh = triples.map(tr => `
        <tr>
          <td class="font-mono">${tr.sid}</td>
          <td>${tr.name}</td>
          <td class="text-center fw-semibold">${Number(tr.evals[0].total_score).toFixed(1)}</td>
          <td class="text-center fw-semibold">${Number(tr.evals[1].total_score).toFixed(1)}</td>
          <td class="text-center fw-semibold">${Number(tr.evals[2].total_score).toFixed(1)}</td>
        </tr>`).join('');
      sh += `
        <tr class="table-primary fw-bold">
          <td colspan="2" class="text-end">ค่า ICC ภาพรวม (คะแนนรวม) →</td>
          <td colspan="3" class="text-center">${overallICC !== null ? overallICC.toFixed(4) : 'N/A'} <span class="badge ${overallInterp.css} ms-1">${overallInterp.text.split(' (')[0]}</span></td>
        </tr>`;
      stBody.innerHTML = sh;
    }

    const paragraphEl = document.getElementById('pearsonReportParagraph');
    if (paragraphEl) {
      const minICC = iccVals.length > 0 ? Math.min(...iccVals).toFixed(4) : "N/A";
      const maxICC = iccVals.length > 0 ? Math.max(...iccVals).toFixed(4) : "N/A";
      const overallInterpText = overallInterp.text.split(' (')[0];
      paragraphEl.innerHTML = `
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-primary"></i> บทวิเคราะห์ค่าความสอดคล้องระหว่างผู้ตรวจ (Inter-rater Reliability — ICC)</h6>
        <p class="mb-0 text-slate-700" style="line-height: 1.6;">
          การตรวจสอบความสอดคล้องของการให้คะแนนโดยผู้ตรวจ 3 คน (ครูผู้สอน และผู้เชี่ยวชาญ 2 ท่าน) สำหรับงานเขียนของนักเรียน<strong>ห้อง 606 (กลุ่มทดลอง)</strong> (N = ${triples.length} คน)
          ด้วยค่าสัมประสิทธิ์สหสัมพันธ์ภายในชั้น <strong>ICC(2,1) — two-way random-effects, absolute agreement, single rater</strong>
          พบว่า <strong>ค่า ICC ของคะแนนรวมเท่ากับ ${overallICC !== null ? overallICC.toFixed(4) : "N/A"}</strong> ซึ่งเมื่อแปลผลตามเกณฑ์ของ Koo &amp; Li (2016) จัดอยู่ใน<strong>ระดับ${overallInterpText}</strong>
          และเมื่อพิจารณาแยกราย 4 ด้าน พบว่าค่า ICC อยู่ระหว่าง <strong>${minICC}</strong> ถึง <strong>${maxICC}</strong> สะท้อนถึงความสอดคล้องของเกณฑ์ประเมินระหว่างผู้ตรวจในระดับที่เชื่อถือได้เชิงสถิติวิจัย
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

  // ประมวลผล Paired t-test เฉพาะนักเรียนห้อง 606 (กลุ่มทดลอง)
  function calculateResearchTTest() {
    const rowEl = document.getElementById('ttestStatsRow');
    const interpEl = document.getElementById('ttestInterpretationText');
    const reviewerSourceEl = document.getElementById('ttestReviewerSelector');
    if (!rowEl || !interpEl || !reviewerSourceEl) return;
    const reviewerSource = reviewerSourceEl.value;

    if (!classroomResearchData) return;

    const students = classroomResearchData.students.filter(s => s.classroom === EXPERIMENTAL_CLASSROOM);
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
      rowEl.innerHTML = `<td colspan="9" class="text-center py-4 text-muted">ต้องการข้อมูลผลคะแนนที่ตรงคู่กันระหว่าง Pretest และ Posttest ของนักเรียนห้อง 606 อย่างน้อย 2 คน</td>`;
      interpEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill text-warning"></i> ไม่สามารถคำนวณสถิติ Paired t-test ได้ เนื่องจากจำนวนตัวอย่างที่จับคู่มีไม่เพียงพอ (N = ${N})`;
      setKpiValue('kpiTtestValue', 'N/A');
      setKpiBadge('kpiTtestBadge', 'ข้อมูลไม่พอ', 'bg-secondary');
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

    setKpiValue('kpiTtestValue', (meanDiff >= 0 ? '+' : '') + meanDiff.toFixed(2));
    setKpiBadge('kpiTtestBadge', isSignificant ? 'มีนัยสำคัญ (p<.05)' : 'ไม่มีนัยสำคัญ', isSignificant ? 'bg-success' : 'bg-secondary');

    interpEl.innerHTML = `
      <div class="mb-2"><strong>วิเคราะห์ผลต่างเฉลี่ยสัมฤทธิ์:</strong></div>
      <p class="mb-2 text-dark">
        จากนักเรียนห้อง 606 (กลุ่มทดลอง) ที่นำมาวิเคราะห์ N = ${N} คน พบว่า คะแนนเฉลี่ยก่อนเรียน (Pretest) อยู่ที่ <strong>${meanPre.toFixed(2)} คะแนน</strong> และคะแนนเฉลี่ยหลังเรียน (Posttest) อยู่ที่ <strong>${meanPost.toFixed(2)} คะแนน</strong> เพิ่มขึ้นโดยเฉลี่ย <strong>+${meanDiff.toFixed(2)} คะแนน</strong>
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
          การศึกษาเปรียบเทียบผลสัมฤทธิ์ความสามารถในการเขียนเรียงความภาษาไทยของนักเรียนกลุ่มทดลอง ห้อง 606 (N = ${N} คน) ก่อนเรียน (Pretest - T1) และหลังเรียน (Posttest - T2) จากคะแนนดิบประเมินของคุณครูผู้สอน
          พบว่า <strong>คะแนนเฉลี่ยก่อนเรียนเท่ากับ ${meanPre.toFixed(2)} คะแนน (SD = ${sdPre.toFixed(2)})</strong> และ <strong>คะแนนเฉลี่ยหลังเรียนเท่ากับ ${meanPost.toFixed(2)} คะแนน (SD = ${sdPost.toFixed(2)})</strong>
          เมื่อวิเคราะห์ทางสถิติเปรียบเทียบด้วย Paired t-test พบว่า คะแนนเฉลี่ยความสามารถงานเขียนหลังเรียนของนักเรียนสูงขึ้นเพิ่มขึ้นเฉลี่ย <strong>+${meanDiff.toFixed(2)} คะแนน</strong> และแตกต่างกันอย่างมีนัยสำคัญทางสถิติ โดยผลการทดสอบแสดงว่า คะแนนความสามารถงานเขียนของนักเรียน<strong>${sigText}</strong> แสดงให้เห็นว่านวัตกรรมการสอนเขียนเรียงความมีประสิทธิผลในการพัฒนาทักษะวิชาการผู้เรียนอย่างแท้จริง
        </p>
      `;
    }
  }

  // แปลงระดับคุณภาพที่เพื่อนให้ (ดีมาก/ดี/ปานกลาง/พอใช้/ปรับปรุง) เป็นตัวเลข 0-4 เพื่อหาค่าเฉลี่ยรายด้าน
  const PEER_LEVEL_SCORE = { 'ดีมาก': 4, 'ดี': 3, 'ปานกลาง': 2, 'พอใช้': 1, 'ปรับปรุง': 0 };
  const QUAL_SUB_CRITERIA = ['1.1', '1.2', '1.3', '2.1', '2.2', '3.1', '3.2', '3.3', '4.1', '4.2', '4.3'];

  let qualProblemChartInstance = null;
  let qualPeerScoreChartInstance = null;

  function renderQualitativeHub() {
    renderQualitativeOverview();
    renderQualitativeCards();
  }

  // ภาพรวม: ความถี่ของอุปสรรครายด้าน + คะแนนประเมินจากเพื่อนเฉลี่ยรายด้าน เพื่อดูว่าประเด็นความสามารถใดสูง/ต่ำ
  // สำหรับใช้วางแผนการจัดการเรียนรู้ขั้น Enabling (POA)
  function renderQualitativeOverview() {
    if (!classroomResearchData) return;

    const experimentalIds = getExperimentalStudentIds();
    const problems = classroomResearchData.problems.filter(p => experimentalIds.has(p.student_id));
    const peerReviews = classroomResearchData.peer_reviews.filter(pr => experimentalIds.has(pr.student_id));
    const reflections = classroomResearchData.reflections.filter(rf => experimentalIds.has(rf.student_id));

    setKpiValue('kpiQualCount', (problems.length + peerReviews.length + reflections.length).toLocaleString('th-TH'));

    // 1) ความถี่ของอุปสรรคการเขียนรายด้าน (จาก writing_problems)
    const criteriaCounts = {};
    QUAL_SUB_CRITERIA.forEach(sc => { criteriaCounts[sc] = 0; });
    problems.forEach(p => {
      QUAL_SUB_CRITERIA.forEach(sc => {
        const key = sc.replace('.', '_');
        if (p['prob_' + key]) criteriaCounts[sc]++;
      });
    });

    // 2) คะแนนประเมินจากเพื่อนเฉลี่ยรายด้าน (จาก peer_reviews)
    const peerSums = {}, peerCounts = {};
    QUAL_SUB_CRITERIA.forEach(sc => { peerSums[sc] = 0; peerCounts[sc] = 0; });
    peerReviews.forEach(pr => {
      QUAL_SUB_CRITERIA.forEach(sc => {
        const key = 'score_' + sc.replace('.', '_');
        const level = pr[key];
        if (level && PEER_LEVEL_SCORE[level] !== undefined) {
          peerSums[sc] += PEER_LEVEL_SCORE[level];
          peerCounts[sc]++;
        }
      });
    });
    const peerAverages = {};
    QUAL_SUB_CRITERIA.forEach(sc => { peerAverages[sc] = peerCounts[sc] > 0 ? peerSums[sc] / peerCounts[sc] : null; });

    renderQualProblemChart(criteriaCounts);
    renderQualPeerScoreChart(peerAverages);

    // ประมวลผลบทวิเคราะห์เชิงคุณภาพ
    const qualParagraphEl = document.getElementById('qualitativeReportParagraph');
    if (qualParagraphEl) {
      const sortedCrit = Object.entries(criteriaCounts).sort((a,b) => b[1] - a[1]);
      const top1 = sortedCrit[0];
      const top2 = sortedCrit[1];
      const top1Name = top1 && top1[1] > 0 ? criteriaMap[top1[0]].name.split(' (')[0] : "ไม่มีข้อมูลหลัก";
      const top2Name = top2 && top2[1] > 0 ? criteriaMap[top2[0]].name.split(' (')[0] : "ไม่มีข้อมูลรอง";
      const top1Count = top1 ? top1[1] : 0;
      const top2Count = top2 ? top2[1] : 0;

      const peerRanked = Object.entries(peerAverages).filter(([, v]) => v !== null).sort((a, b) => a[1] - b[1]);
      const weakest = peerRanked[0];
      const strongest = peerRanked[peerRanked.length - 1];
      const weakestName = weakest ? criteriaMap[weakest[0]].name.split(' (')[0] : null;
      const strongestName = strongest ? criteriaMap[strongest[0]].name.split(' (')[0] : null;

      const totalPeer = peerReviews.length;
      const totalReflect = reflections.length;

      qualParagraphEl.innerHTML = `
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-text text-success"></i> บทวิเคราะห์และอภิปรายเชิงคุณภาพ (Qualitative Content Analysis Summary)</h6>
        <p class="mb-0 text-slate-700" style="line-height: 1.6;">
          จากการวิเคราะห์เนื้อหาเชิงคุณภาพ (Content Analysis) จากบันทึกข้อมูลอุปสรรคและแนวทางแก้ไข (POA) คะแนนประเมินจากเพื่อน และการสะท้อนคิด พบประเด็นเชิงคุณภาพที่เด่นชัดดังนี้:
        </p>
        <ul class="mt-2 mb-0 ps-3 text-slate-700 small" style="line-height: 1.6;">
          ${weakestName ? `<li class="mb-1"><strong>ประเด็นความสามารถที่ควรพัฒนาเร่งด่วน (จัดการเรียนรู้ขั้น Enabling):</strong> ด้าน <strong class="text-danger">${weakestName}</strong> ได้คะแนนประเมินจากเพื่อนเฉลี่ยต่ำสุดที่ <strong>${weakest[1].toFixed(2)}/4</strong> ${strongestName ? `ในขณะที่ด้าน <strong class="text-success">${strongestName}</strong> เป็นจุดแข็งของชั้นเรียนด้วยคะแนนเฉลี่ยสูงสุดที่ <strong>${strongest[1].toFixed(2)}/4</strong>` : ''}</li>` : ''}
          <li class="mb-1"><strong>อุปสรรคการเขียนที่พบบ่อยที่สุด:</strong> อันดับแรกคือด้าน <strong>${top1Name}</strong> (พบปัญหาจากผู้เรียนจำนวน ${top1Count} คน) และอันดับรองลงมาคือด้าน <strong>${top2Name}</strong> (พบปัญหาจากผู้เรียนจำนวน ${top2Count} คน)</li>
          <li class="mb-1"><strong>การสะท้อนคิดและการมีส่วนร่วมของกลุ่มเพื่อน:</strong> มีการบันทึกการให้ข้อคิดเห็นเชิงคุณภาพและข้อเสนอแนะร่วมกันระหว่างเพื่อน (Peer Reviews) สะสมจำนวน <strong>${totalPeer} ครั้ง</strong> และแบบสะท้อนคิดการเรียนรู้เรียงความของตนเอง (Self Reflections) จำนวน <strong>${totalReflect} ครั้ง</strong></li>
        </ul>
      `;
    }
  }

  function renderQualProblemChart(criteriaCounts) {
    const canvas = document.getElementById('qualProblemFreqChart');
    if (!canvas) return;
    const rows = QUAL_SUB_CRITERIA.map(sc => ({ sc, count: criteriaCounts[sc] || 0, name: criteriaMap[sc].name.split(' (')[0] }))
      .sort((a, b) => b.count - a.count);

    if (qualProblemChartInstance) qualProblemChartInstance.destroy();
    qualProblemChartInstance = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: rows.map(x => `${x.sc} ${x.name}`),
        datasets: [{
          label: 'จำนวนนักเรียนที่พบอุปสรรค (คน)',
          data: rows.map(x => x.count),
          backgroundColor: 'rgba(239, 68, 68, 0.75)',
          borderColor: '#ef4444',
          borderWidth: 1.5,
          borderRadius: 4
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  function renderQualPeerScoreChart(peerAverages) {
    const canvas = document.getElementById('qualPeerScoreChart');
    if (!canvas) return;
    const rows = QUAL_SUB_CRITERIA.map(sc => ({ sc, avg: peerAverages[sc], name: criteriaMap[sc].name.split(' (')[0] }))
      .sort((a, b) => (a.avg === null ? 99 : a.avg) - (b.avg === null ? 99 : b.avg));

    const colorFor = (avg) => {
      if (avg === null) return '#adb5bd';
      if (avg >= 3.5) return '#059669';
      if (avg >= 2.5) return '#2563eb';
      if (avg >= 1.5) return '#f59e0b';
      return '#ef4444';
    };

    if (qualPeerScoreChartInstance) qualPeerScoreChartInstance.destroy();
    qualPeerScoreChartInstance = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: rows.map(x => `${x.sc} ${x.name}`),
        datasets: [{
          label: 'คะแนนเฉลี่ยจากเพื่อน (เต็ม 4)',
          data: rows.map(x => x.avg !== null ? x.avg : 0),
          backgroundColor: rows.map(x => colorFor(x.avg)),
          borderWidth: 0,
          borderRadius: 4
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, max: 4 } }
      }
    });
  }

  // รายบุคคล: การ์ดข้อมูลดิบของอุปสรรค/คำแนะนำเพื่อน/การสะท้อนคิด พร้อมตัวกรองและค้นหา
  function renderQualitativeCards() {
    const container = document.getElementById('qualitativeHubContainer');
    if (!container) return;

    if (!classroomResearchData) {
      container.innerHTML = '<div class="col-12 text-center text-muted">ไม่มีข้อมูลประมวลผล</div>';
      return;
    }

    const filterCriteria = document.getElementById('qualitativeCriteriaFilter').value;
    const query = document.getElementById('qualitativeSearchInput').value.toLowerCase().trim();

    const experimentalIds = getExperimentalStudentIds();
    const problems = classroomResearchData.problems.filter(p => experimentalIds.has(p.student_id));
    const peerReviews = classroomResearchData.peer_reviews.filter(pr => experimentalIds.has(pr.student_id));
    const reflections = classroomResearchData.reflections.filter(rf => experimentalIds.has(rf.student_id));

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

    setKpiValue('qualIndividualCountBadge', cardCount.toLocaleString('th-TH'));
  }

  function filterQualitativeHub() {
    renderQualitativeCards();
  }

  function exportQualitativeToCSV() {
    if (!classroomResearchData) return;

    let csvContent = "﻿"; // UTF-8 BOM
    csvContent += "Student ID,Student Name,Data Type,Sub-criteria,Content / Problem / Strength,Solution / Improvement / Feedback,Encouragement / Goals\n";

    const experimentalIds = getExperimentalStudentIds();
    const problems = classroomResearchData.problems.filter(p => experimentalIds.has(p.student_id));
    const peerReviews = classroomResearchData.peer_reviews.filter(pr => experimentalIds.has(pr.student_id));
    const reflections = classroomResearchData.reflections.filter(rf => experimentalIds.has(rf.student_id));

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
      if (researchDataPromise) await researchDataPromise;
      const experimentalIds = getExperimentalStudentIds();
      const res = await fetch('api.php?action=get_all_essays');
      const data = await res.json();
      if (data.success) {
        // กรองเฉพาะเรียงความของนักเรียนห้อง 606 (กลุ่มทดลอง) ไม่ให้ปนกับห้องอื่น
        allEssaysCache = data.essays.filter(e => experimentalIds.has(e.student_id));
        setKpiValue('kpiEssayCount', allEssaysCache.length.toLocaleString('th-TH'));
        renderEssayViewer(allEssaysCache);
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

  // แปลงอักขระพิเศษของ HTML เพื่อกันสคริปต์ฝังในข้อมูลที่นักเรียนกรอก (ชื่อเรื่อง/ชื่อ ฯลฯ)
  function escapeHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
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

    container.innerHTML = filtered.map((e, idx) => {
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
      const phaseLabel   = escapeHtml(essayPhaseLabels[e.essay_phase] || e.essay_phase);
      const wordCount    = parseInt(e.word_count || 0).toLocaleString('th-TH');
      const studentName  = escapeHtml(e.student_name);
      const studentId    = escapeHtml(e.student_id);
      const essayTitle   = escapeHtml(e.essay_title);
      // ใช้ลำดับที่ (index) ของรายการที่แสดง เป็น id — ปลอดภัยและไม่ชนกัน แม้รหัส/รอบจะมีอักขระพิเศษ
      const essayId      = `essay_${idx}`;
      const formattedHTML = formatEssayHTML(e.essay_content);

      return `
        <div class="card border-0 rounded-3 mb-3 shadow-sm" style="border-left: 4px solid #0d7377 !important;">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge ${badgeClass} px-2 py-1 small">${phaseLabel}</span>
                <span class="fw-bold text-dark">${studentName} <span class="text-muted fw-normal small">(${studentId})</span></span>
                ${e.essay_title ? `<span class="text-secondary small fst-italic">— ${essayTitle}</span>` : ''}
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

    // ป้องกัน CSV formula injection: ค่าที่ขึ้นต้นด้วย = + - @ (หรือ tab/CR) อาจถูกโปรแกรมตารางตีความเป็นสูตร
    // จึงเติมเครื่องหมาย ' นำหน้าเพื่อบังคับให้เป็นข้อความล้วนก่อนครอบด้วยเครื่องหมายคำพูด
    const esc = s => {
      let v = (s == null ? '' : String(s));
      if (/^[=+\-@\t\r]/.test(v)) v = "'" + v;
      return '"' + v.replace(/"/g,'""').replace(/\n/g,' ') + '"';
    };
    let csv = '﻿' + 'รหัสนักเรียน,ชื่อ-สกุล,รอบการประเมิน,ชื่อเรื่อง,จำนวนคำ,ส่วนคำนำ (Introduction),ส่วนเนื้อเรื่อง (Body),ส่วนสรุป (Conclusion),วันที่บันทึก\n';
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

  // --- เริ่มรันอัตโนมัติ ---
  let researchDataPromise = null;
  (async function init() {
    // ต้องรอ studentDB ให้พร้อมก่อนเสมอ เพราะ renderQualitativeHub() ใช้ studentDB แปลงรหัสเป็นชื่อ
    await loadStudents();
    researchDataPromise = loadResearchData();
    await researchDataPromise;
    loadEssayViewer(); // โหลดล่วงหน้าเบื้องหลัง เพื่อให้ตัวเลข KPI และแท็บเรียงความพร้อมใช้งานทันที
  })();
</script>

<?php
require_once 'footer.php';
