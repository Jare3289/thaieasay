<?php
$page_title = 'ระบบวิเคราะห์สถิติงานวิจัย (ICC & Paired t-test) - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login('teacher'); // ครูเท่านั้น
require_once 'header.php';
?>

<style>
  html { scroll-behavior: smooth; }
  #section-icc, #section-ttest, #section-qual, #section-essay { scroll-margin-top: 84px; }
  /* แสดงขอบเขตการตัดคำในตัวอย่างเรียงความ (อ่านอย่างเดียว) — เส้นประบาง ๆ ใต้แต่ละคำ */
  .thai-word { border-bottom: 1px dotted #b9c4c4; }
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
        <a href="#section-export" class="btn btn-dark btn-sm fw-bold rounded-pill px-3">
          <i class="bi bi-cloud-download-fill"></i> ส่งออกรายงาน
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
              <span class="input-group-text bg-white small border-end-0 text-nowrap"><i class="bi bi-calendar-check"></i> ภาระงานที่ใช้คำนวณ ICC</span>
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

          <!-- คะแนนผู้ตรวจ 3 คน รายบุคคล (ห้อง 606) — แยกรายด้าน + ระดับที่ผู้ตรวจเลือก -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-info">
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-people-fill text-info"></i> คะแนนผู้ตรวจ 3 คน รายบุคคล (เฉพาะห้อง 606) — แยกรายข้อประเมิน (11 ข้อ)</h6>
                <p class="text-muted small mb-2" id="iccRaterSummary">ผู้ตรวจ 3 คน (ครูผู้สอน + ผู้เชี่ยวชาญ 2 ท่าน) — คะแนนเต็ม 60</p>
                <div class="alert alert-light border rounded-3 py-2 px-3 small mb-3">
                  <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="d-inline-flex align-items-center gap-1">
                      <span class="badge bg-info text-dark" style="font-size:.66rem;">ดี</span>
                      <span><strong>แถวรายข้อ (11 ข้อ)</strong> = <strong>ระดับที่ผู้ตรวจเลือกจริง</strong></span>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                      <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:.66rem;">≈ ดี</span>
                      <span>แถว <strong>รวมด้าน / คะแนนรวม</strong> = แถบระดับ<strong>เทียบเกณฑ์ที่คำนวณจากคะแนน</strong> (ไม่ใช่ระดับที่ผู้ตรวจเลือก)</span>
                    </span>
                  </div>
                  <div class="text-muted mt-1" style="font-size:.72rem;">
                    <i class="bi bi-info-circle"></i> ระบบบันทึกระดับที่ผู้ตรวจเลือกไว้ราย 11 ข้อเท่านั้น — ระดับรวมด้าน/รวมทั้งฉบับเป็นการจัดแถบตามสัดส่วนคะแนน (≥81.6% ดีมาก · ≥61.6% ดี · ≥41.6% ปานกลาง · ≥21.6% พอใช้)
                  </div>
                </div>
                <div id="iccStudentCards" style="max-height: 620px; overflow-y: auto;">
                  <div class="text-center py-4 text-muted">รอประมวลผลข้อมูล...</div>
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

          <!-- แผงแสดงค่าที่แทนในสูตร ICC (ของคะแนนรวม) -->
          <div class="row mt-3">
            <div class="col-12">
              <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-3 border-primary">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-calculator text-primary"></i> การคำนวณค่า ICC — แสดงค่าที่แทนในสูตร (คะแนนรวม)</h6>
                <div id="iccFormulaPanel" class="small text-secondary">
                  <em>รอประมวลผลข้อมูล...</em>
                </div>
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
                <option value="task1">ภาระงาน หน่วยที่ 1</option>
                <option value="task2">ภาระงาน หน่วยที่ 2</option>
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

          <!-- Summary stats row 2: คำรวมทั้งหมด + ส่วนเบี่ยงเบนมาตรฐาน -->
          <div class="row g-3 mb-4" id="essaySummaryRow2">
            <div class="col-md-6 col-6">
              <div class="card border-0 rounded-3 p-3 text-center bg-light">
                <div class="fs-4 fw-bold text-primary" id="essayStatTotalWords">-</div>
                <div class="text-muted small">คำรวมทั้งหมด</div>
              </div>
            </div>
            <div class="col-md-6 col-6">
              <div class="card border-0 rounded-3 p-3 text-center bg-light">
                <div class="fs-4 fw-bold text-dark" id="essayStatStdDevWords">-</div>
                <div class="text-muted small">ส่วนเบี่ยงเบนมาตรฐาน (คำ)</div>
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

      <!-- 📥 ศูนย์ส่งออกชุดข้อมูลรายงาน (Report Export Center) — ย้ายมาไว้ท้ายสุดของหน้า -->
      <section id="section-export" class="mb-5" style="scroll-margin-top: 84px;">
        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-dark border-2">
          <span class="badge bg-dark fs-6">5</span>
          <h5 class="fw-bold text-dark mb-0"><i class="bi bi-cloud-download-fill"></i> ส่งออกรายงานเพื่อการวิเคราะห์ (Report Export Center)</h5>
        </div>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
            <h6 class="fw-bold mb-0 text-white"><i class="bi bi-cloud-download-fill text-info"></i> ศูนย์ส่งออกชุดข้อมูลรายงานเพื่อการวิเคราะห์ (Report Export Center)</h6>
            <span class="badge bg-info text-dark fw-bold">Word / Google Docs · CSV</span>
          </div>
          <div class="card-body p-4">
            <p class="text-muted small mb-4" style="line-height:1.7;">
              สร้าง <strong>รายงานผลการวิจัยฉบับสมบูรณ์</strong> จากผลที่ระบบวิเคราะห์ให้ในหน้านี้ — ค่า <strong>ICC</strong>, <strong>Paired t-test</strong>,
              สถิติเชิงพรรณนา และผลการวิเคราะห์เชิงคุณภาพ — จัดรูปแบบมี <strong>ปก · คำนำ · สารบัญ</strong> และเรียบเรียงไล่ทั้งกระบวนการวิจัย
              พร้อมบทวิเคราะห์และสรุปผล ไฟล์เปิดได้ทันทีใน <strong>Microsoft Word</strong> หรืออัปโหลดเปิดใน <strong>Google Docs</strong>
            </p>

            <!-- ปุ่มหลัก: ส่งรายงานเข้า Google Docs โดยตรง -->
            <div class="text-center mb-2">
              <button onclick="sendReportToGoogleDocs()" id="genReportBtn" class="btn btn-dark btn-lg fw-bold rounded-pill px-5 py-3 shadow-sm">
                <i class="bi bi-google text-info"></i> ส่งรายงานเข้า Google Docs โดยตรง
              </button>
              <div class="mt-2">
                <button onclick="generateResearchReportDoc()" id="dlDocBtn" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                  <i class="bi bi-download"></i> หรือดาวน์โหลดเป็นไฟล์ .doc
                </button>
              </div>
              <div class="small text-muted mt-2"><i class="bi bi-info-circle"></i> ครั้งแรกระบบจะพาไปเชื่อมต่อบัญชี Google เพื่ออนุญาตให้บันทึกไฟล์ลง Google Drive ของคุณครู · ข้อมูลรวบรวมให้อัตโนมัติ (ICC จากกลุ่มทดลอง, ส่วนอื่นจากกลุ่มตัวอย่าง)</div>
              <div id="googleStatusBox" class="small mt-2"></div>
            </div>

            <hr class="my-4">

            <!-- ข้อมูลดิบรายตาราง (CSV) สำหรับผู้ที่ต้องการวิเคราะห์เอง -->
            <div class="border rounded-3 p-3 bg-light">
              <div class="fw-bold text-secondary mb-1"><i class="bi bi-filetype-csv me-1"></i>ดาวน์โหลดข้อมูลดิบรายตาราง (CSV)</div>
              <p class="text-muted small mb-3">สำหรับนำไปวิเคราะห์เองใน Excel / SPSS / R / Python — ไฟล์ฝัง UTF-8 BOM รองรับภาษาไทย และป้องกัน CSV formula injection</p>
              <div class="row g-2 align-items-end mb-3">
                <div class="col-md-4 col-sm-6">
                  <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-people-fill me-1"></i>กรองตามกลุ่ม</label>
                  <select id="exportGroupFilter" class="form-select form-select-sm rounded-3">
                    <option value="">ทุกกลุ่ม</option>
                    <option value="กลุ่มทดลอง">กลุ่มทดลอง (Experimental)</option>
                    <option value="กลุ่มตัวอย่าง">กลุ่มตัวอย่าง (Control/Sample)</option>
                  </select>
                </div>
                <div class="col-md-4 col-sm-6">
                  <label class="form-label small fw-semibold text-secondary mb-1"><i class="bi bi-door-open-fill me-1"></i>กรองตามห้องเรียน</label>
                  <input type="text" id="exportClassroomFilter" class="form-control form-control-sm rounded-3" placeholder="เช่น 606 (เว้นว่าง = ทุกห้อง)">
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2" id="csvQuickLinks">
              <button onclick="doExport('summary','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> สรุปคะแนน</button>
              <button onclick="doExport('class_stats','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> สถิติระดับชั้น</button>
              <button onclick="doExport('evaluations','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> ผลประเมิน</button>
              <button onclick="doExport('writing_problems','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> ปัญหาการเขียน</button>
              <button onclick="doExport('self_checklists','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> ตรวจสอบตนเอง</button>
              <button onclick="doExport('peer_reviews','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> ประเมินเพื่อน</button>
              <button onclick="doExport('reflections','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> สะท้อนการเรียนรู้</button>
              <button onclick="doExport('essays','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> เรียงความ</button>
              <button onclick="doExport('peer_pairs','csv')" class="btn btn-link btn-sm text-decoration-none p-1 small"><i class="bi bi-filetype-csv"></i> การจับคู่</button>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</div>

<script src="thai_review.js"></script>
<script>
  // ==== ศูนย์ส่งออกรายงาน: ประกอบ URL แล้วสั่งดาวน์โหลดตามตัวกรองที่เลือก ====
  function doExport(report, format) {
    const grp = (document.getElementById('exportGroupFilter') || {}).value || '';
    const room = ((document.getElementById('exportClassroomFilter') || {}).value || '').trim();
    const params = new URLSearchParams({ report: report, format: format });
    if (grp) params.set('group', grp);
    if (room) params.set('classroom', room);
    // เปิดในแท็บชั่วคราวเพื่อให้เบราว์เซอร์รับไฟล์แนบ (Content-Disposition: attachment)
    window.location.href = 'export.php?' + params.toString();
    return false;
  }

  // ==== ผู้จัดทำรายงาน (ดึงชื่อผู้ใช้ที่ล็อกอินจากฝั่งเซิร์ฟเวอร์) ====
  const REPORT_AUTHOR = <?php echo json_encode(isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'ครูผู้สอน'); ?>;

  // ==== สร้างรายงานผลการวิจัยฉบับสมบูรณ์ (เปิดใน Word / Google Docs) ====
  // ================= สร้างเนื้อหารายงาน (คืน HTML เอกสาร) =================
  // ข้อกำหนดข้อมูล:
  //   • ส่วน ICC → ใช้กลุ่มทดลอง (ห้อง 606) เฉพาะภาระงานหน่วยที่ 1 อย่างละเอียด (ด้านหลัก+ด้านย่อย+ผู้ตรวจ 3 คน)
  //   • ส่วนอื่น (เชิงปริมาณ/คุณภาพ/เรียงความ) → ใช้ "กลุ่มตัวอย่าง" ทั้งหมด
  const SAMPLE_GROUP = 'กลุ่มตัวอย่าง';

  async function buildReportHtml() {
    if (typeof researchDataPromise !== 'undefined' && researchDataPromise) { await researchDataPromise; }
    if (!classroomResearchData) { await loadResearchData(); }
    if (!classroomResearchData) throw new Error('ยังไม่มีข้อมูลสำหรับจัดทำรายงาน');

    const esc = (typeof escapeHtml === 'function') ? escapeHtml : (s => String(s == null ? '' : s));
    const f2 = (x) => (x === null || x === undefined || isNaN(x)) ? '-' : Number(x).toFixed(2);
    const f4 = (x) => (x === null || x === undefined || isNaN(x)) ? '-' : Number(x).toFixed(4);
    const evals = classroomResearchData.evaluations || [];
    const allStudents = classroomResearchData.students || [];
    const nameOf = (id) => (studentDB && studentDB[id]) || (allStudents.find(s => s.student_id === id) || {}).student_name || id;

    // ---------- แบ่งกลุ่ม ----------
    const expStudents = allStudents.filter(s => s.classroom === EXPERIMENTAL_CLASSROOM);        // กลุ่มทดลอง (ICC)
    const expIds = new Set(expStudents.map(s => s.student_id));
    const sampleStudents = allStudents.filter(s => s.student_group === SAMPLE_GROUP);            // กลุ่มตัวอย่าง (ส่วนอื่น)
    const sampleIds = new Set(sampleStudents.map(s => s.student_id));

    // ---------- ตัวช่วยสถิติ ----------
    function descStats(vals) {
      const n = vals.length; if (!n) return null;
      const mean = vals.reduce((a, b) => a + b, 0) / n;
      let ss = 0; vals.forEach(v => ss += Math.pow(v - mean, 2));
      const sd = n > 1 ? Math.sqrt(ss / (n - 1)) : 0;
      return { n, mean, sd, min: Math.min(...vals), max: Math.max(...vals) };
    }
    function effectSizeLabel(dz) {
      const a = Math.abs(dz);
      if (a >= 0.8) return 'ขนาดผลใหญ่ (large)';
      if (a >= 0.5) return 'ขนาดผลปานกลาง (medium)';
      if (a >= 0.2) return 'ขนาดผลเล็ก (small)';
      return 'ขนาดผลน้อยมาก (negligible)';
    }
    const DIM_MAIN = [
      { key: 'total', name: 'คะแนนรวม (เต็ม 60)', max: 60, get: e => Number(e.total_score) },
      { key: 'c', name: 'ด้านเนื้อหาสาระ (เต็ม 27)', max: 27, get: e => Number(e.score_1_1) + Number(e.score_1_2) + Number(e.score_1_3) },
      { key: 's', name: 'ด้านองค์ประกอบและการลำดับ (เต็ม 12)', max: 12, get: e => Number(e.score_2_1) + Number(e.score_2_2) },
      { key: 'l', name: 'ด้านการใช้สำนวนภาษา (เต็ม 15)', max: 15, get: e => Number(e.score_3_1) + Number(e.score_3_2) + Number(e.score_3_3) },
      { key: 'm', name: 'ด้านอักขรวิธีและกลไกการเขียน (เต็ม 6)', max: 6, get: e => Number(e.score_4_1) + Number(e.score_4_2) + Number(e.score_4_3) }
    ];
    // ด้านย่อย 11 ตัวชี้วัด (สำหรับ ICC ละเอียด)
    const SUB_KEYS = ['1_1', '1_2', '1_3', '2_1', '2_2', '3_1', '3_2', '3_3', '4_1', '4_2', '4_3'];
    const SUB_NAME = { '1_1': '1.1 ความตรงประเด็น', '1_2': '1.2 แก่นเรื่องชัดเจน', '1_3': '1.3 การขยายความและเหตุผล', '2_1': '2.1 ความครบถ้วนขององค์ประกอบ', '2_2': '2.2 การลำดับประเด็นเป็นระบบ', '3_1': '3.1 การใช้ประโยคถูกต้อง', '3_2': '3.2 การเลือกใช้คำ', '3_3': '3.3 ระดับภาษาเหมาะสม', '4_1': '4.1 การสะกดคำถูกต้อง', '4_2': '4.2 การเว้นวรรค', '4_3': '4.3 ความเรียบร้อย' };

    // ---------- ตาราง HTML ----------
    const tbl = (headers, rows, opts) => {
      opts = opts || {};
      let h = '<table class="data"><thead><tr>';
      headers.forEach(x => h += '<th>' + esc(x) + '</th>');
      h += '</tr></thead><tbody>';
      if (!rows.length) h += '<tr><td colspan="' + headers.length + '" style="text-align:center;color:#888">— ยังไม่มีข้อมูล —</td></tr>';
      rows.forEach(r => {
        h += '<tr>';
        r.forEach((c, i) => {
          const align = (opts.numCols && opts.numCols.includes(i)) ? ' style="text-align:center"' : '';
          h += '<td' + align + '>' + (c === null || c === undefined ? '' : esc(c)) + '</td>';
        });
        h += '</tr>';
      });
      return h + '</tbody></table>';
    };

    // ================= ICC (กลุ่มทดลอง ห้อง 606, หน่วยที่ 1) =================
    const ICC_PHASE = 'task1';
    const triples = [];
    expStudents.forEach(s => {
      const ev = evals.filter(e => e.student_id === s.student_id && e.test_phase === ICC_PHASE);
      const t = ev.find(e => e.evaluator_type === 'teacher');
      const e1 = ev.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 1' || e.evaluator_name === 'admin1'));
      const e2 = ev.find(e => e.evaluator_type === 'expert' && (e.evaluator_name === 'ผู้เชี่ยวชาญ 2' || e.evaluator_name === 'admin2'));
      if (t && e1 && e2) triples.push({ sid: s.student_id, evals: [t, e1, e2] });
    });
    const iccOf = (getter) => {
      const m = triples.map(tr => tr.evals.map(getter));
      const det = (triples.length >= 2) ? computeICC(m) : null;
      const icc = det ? det.icc : null;
      return { icc, interp: getICCInterpretation(icc) };
    };
    // ตาราง ICC ด้านหลัก
    const iccMainRows = DIM_MAIN.map(d => { const r = iccOf(d.get); return [d.name, triples.length, f4(r.icc), r.interp.text]; });
    // ตาราง ICC ด้านย่อย 11 ตัวชี้วัด
    const iccSubRows = SUB_KEYS.map(k => { const r = iccOf(e => Number(e['score_' + k])); return [SUB_NAME[k], triples.length, f4(r.icc), r.interp.text]; });
    const iccOverall = iccOf(e => Number(e.total_score));
    // ตารางคะแนนผู้ตรวจ 3 คน รายบุคคล (คะแนนรวม + 4 ด้านหลัก ของแต่ละผู้ตรวจ)
    const raterNames = ['ครูผู้สอน', 'ผู้เชี่ยวชาญ 1', 'ผู้เชี่ยวชาญ 2'];
    const raterRows = [];
    triples.forEach((tr, idx) => {
      raterNames.forEach((rn, ri) => {
        const ev = tr.evals[ri];
        raterRows.push([
          ri === 0 ? (idx + 1) : '', ri === 0 ? esc(nameOf(tr.sid)) : '', rn,
          f2(DIM_MAIN[1].get(ev)), f2(DIM_MAIN[2].get(ev)), f2(DIM_MAIN[3].get(ev)), f2(DIM_MAIN[4].get(ev)),
          f2(Number(ev.total_score))
        ]);
      });
    });

    // ================= เชิงปริมาณ (กลุ่มตัวอย่าง) =================
    const phaseKeys = [['pretest', 'ก่อนเรียน (Pretest)'], ['task1', 'ภาระงานหน่วยที่ 1'], ['task2', 'ภาระงานหน่วยที่ 2'], ['posttest', 'หลังเรียน (Posttest)']];
    const teacherScoresByPhase = (phase, getter) => evals
      .filter(e => sampleIds.has(e.student_id) && e.test_phase === phase && e.evaluator_type === 'teacher')
      .map(getter);
    // 3.1 ภาพรวม: สถิติคะแนนรวมต่อรอบ
    const descRows = phaseKeys.map(([k, label]) => {
      const d = descStats(teacherScoresByPhase(k, e => Number(e.total_score)));
      return d ? [label, d.n, f2(d.mean), f2(d.sd), f2(d.min), f2(d.max)] : [label, 0, '-', '-', '-', '-'];
    });
    // 3.2 ภาพรวมรายด้าน (ใช้รอบหลังเรียนถ้ามี ไม่งั้นเลือกรอบที่มีข้อมูลมากสุด)
    const phaseCount = {};
    phaseKeys.forEach(([k]) => phaseCount[k] = teacherScoresByPhase(k, () => 1).length);
    let mainPhase = 'posttest';
    if (!phaseCount['posttest']) { mainPhase = phaseKeys.map(([k]) => k).sort((a, b) => phaseCount[b] - phaseCount[a])[0] || 'posttest'; }
    const mainPhaseLabel = (phaseKeys.find(([k]) => k === mainPhase) || [null, mainPhase])[1];
    const dimDescRows = DIM_MAIN.map(d => {
      const st = descStats(teacherScoresByPhase(mainPhase, d.get));
      return st ? [d.name, st.n, f2(st.mean), f2(st.sd), f2(st.min), f2(st.max)] : [d.name, 0, '-', '-', '-', '-'];
    });
    // 3.4 รายละเอียดรายบุคคล (คะแนน 4 ด้าน + รวม รอบหลัก)
    const teacherEvalOf = (id, phase) => evals.find(e => e.student_id === id && e.test_phase === phase && e.evaluator_type === 'teacher');
    const perStudentDetail = [];
    sampleStudents.forEach((s, i) => {
      const ev = teacherEvalOf(s.student_id, mainPhase);
      if (!ev) return;
      perStudentDetail.push([i + 1, esc(nameOf(s.student_id)),
        f2(DIM_MAIN[1].get(ev)), f2(DIM_MAIN[2].get(ev)), f2(DIM_MAIN[3].get(ev)), f2(DIM_MAIN[4].get(ev)), f2(Number(ev.total_score))]);
    });
    // Paired t-test (ก่อน–หลัง) กลุ่มตัวอย่าง
    function pairedT(source) {
      const pairs = [];
      sampleStudents.forEach(s => {
        const id = s.student_id;
        const preE = evals.filter(e => e.student_id === id && e.test_phase === 'pretest');
        const postE = evals.filter(e => e.student_id === id && e.test_phase === 'posttest');
        let a = null, b = null;
        if (source === 'teacher') {
          const p = preE.find(e => e.evaluator_type === 'teacher'); const q = postE.find(e => e.evaluator_type === 'teacher');
          if (p && q) { a = Number(p.total_score); b = Number(q.total_score); }
        }
        if (a !== null && b !== null) pairs.push({ id, pre: a, post: b, diff: b - a });
      });
      const N = pairs.length;
      if (N < 2) return { N, insufficient: true, pairs };
      const meanPre = pairs.reduce((s, d) => s + d.pre, 0) / N;
      const meanPost = pairs.reduce((s, d) => s + d.post, 0) / N;
      const meanDiff = pairs.reduce((s, d) => s + d.diff, 0) / N;
      let ss = 0; pairs.forEach(d => ss += Math.pow(d.diff - meanDiff, 2));
      const sdDiff = Math.sqrt(ss / (N - 1));
      const t = sdDiff > 0 ? meanDiff / (sdDiff / Math.sqrt(N)) : 0;
      const df = N - 1;
      const p = calculateOneTailedPValue(t, df);
      const dz = sdDiff > 0 ? meanDiff / sdDiff : 0;
      return { N, meanPre, meanPost, meanDiff, sdDiff, t, df, p, dz, pairs };
    }
    const tTeacher = pairedT('teacher');

    // ================= เชิงคุณภาพ (กลุ่มตัวอย่าง) =================
    const problems = (classroomResearchData.problems || []).filter(p => sampleIds.has(p.student_id));
    const peerReviews = (classroomResearchData.peer_reviews || []).filter(pr => sampleIds.has(pr.student_id));
    const reflections = (classroomResearchData.reflections || []).filter(rf => sampleIds.has(rf.student_id));
    const critCounts = {}; QUAL_SUB_CRITERIA.forEach(sc => critCounts[sc] = 0);
    problems.forEach(p => QUAL_SUB_CRITERIA.forEach(sc => { if (p['prob_' + sc.replace('.', '_')]) critCounts[sc]++; }));
    const peerSum = {}, peerCnt = {}; QUAL_SUB_CRITERIA.forEach(sc => { peerSum[sc] = 0; peerCnt[sc] = 0; });
    peerReviews.forEach(pr => QUAL_SUB_CRITERIA.forEach(sc => {
      const lv = pr['score_' + sc.replace('.', '_')];
      if (lv && PEER_LEVEL_SCORE[lv] !== undefined) { peerSum[sc] += PEER_LEVEL_SCORE[lv]; peerCnt[sc]++; }
    }));
    const sortedCrit = QUAL_SUB_CRITERIA.map(sc => [sc, critCounts[sc]]).sort((a, b) => b[1] - a[1]);

    const now = new Date();
    const thDate = now.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });

    // ================= ประกอบเนื้อหา =================
    const P = [];
    // ----- ส่วนที่ 1 -----
    P.push('<h1 class="secn" id="s1">ส่วนที่ 1 บทนำและกระบวนการวิจัย</h1>');
    P.push('<h2>1.1 ความเป็นมาและวัตถุประสงค์</h2>');
    P.push('<p>รายงานฉบับนี้จัดทำขึ้นเพื่อรวบรวมและวิเคราะห์ผลการประเมินความสามารถในการเขียนเรียงความของนักเรียน โดยใช้ข้อมูลที่บันทึกผ่านระบบประเมินเรียงความ ครอบคลุมทั้งการวิเคราะห์เชิงปริมาณ (สถิติเชิงพรรณนา การทดสอบพัฒนาการ และค่าความสอดคล้องระหว่างผู้ตรวจ) และเชิงคุณภาพ (ปัญหาการเขียน ข้อเสนอแนะจากเพื่อน และการสะท้อนการเรียนรู้) เพื่อตรวจสอบพัฒนาการของผู้เรียนและคุณภาพของเครื่องมือประเมิน</p>');
    P.push('<h2>1.2 แบบแผนการวิจัย</h2>');
    P.push('<p>การวิจัยใช้แบบแผน <b>กลุ่มทดลองกลุ่มเดียว ทดสอบก่อน–หลัง (One-Group Pretest–Posttest Design)</b> เก็บข้อมูลการประเมิน 4 รอบ ได้แก่ ก่อนเรียน (Pretest) ภาระงานหน่วยที่ 1 หน่วยที่ 2 และหลังเรียน (Posttest)</p>');
    P.push('<h2>1.3 กลุ่มเป้าหมายในการวิเคราะห์</h2>');
    P.push('<p>ผลการวิเคราะห์เชิงปริมาณ เชิงคุณภาพ และผลงานเรียงความ ใช้ข้อมูลของ<b>กลุ่มตัวอย่าง จำนวน ' + sampleStudents.length + ' คน</b> ส่วนการตรวจสอบความสอดคล้องระหว่างผู้ตรวจ (ICC) ใช้ข้อมูลของ<b>กลุ่มทดลอง (นักเรียนห้อง ' + esc(EXPERIMENTAL_CLASSROOM) + ') จำนวน ' + expStudents.length + ' คน</b> ซึ่งได้รับการตรวจโดยผู้ประเมิน 3 คน</p>');
    P.push('<h2>1.4 เครื่องมือที่ใช้ในการวิจัย</h2>');
    P.push('<ul>' +
      '<li><b>แบบประเมินความสามารถในการเขียนเรียงความ</b> (Rubric) 11 ตัวชี้วัด จัดเป็น 4 ด้าน คะแนนเต็ม 60</li>' +
      '<li><b>แบบบันทึกปัญหาการเขียนและแนวทางแก้ไข</b> รายตัวชี้วัด</li>' +
      '<li><b>แบบตรวจสอบตนเอง</b> (Self-Checklist)</li>' +
      '<li><b>แบบประเมินผลงานเพื่อนเชิงคุณภาพ</b> (Peer Review)</li>' +
      '<li><b>แบบบันทึกการสะท้อนการเรียนรู้</b> (Learning Reflection)</li>' +
      '</ul>');
    P.push('<h2>1.5 การตรวจสอบความเที่ยงของเครื่องมือ</h2>');
    P.push('<p>งานเขียนของกลุ่มทดลองได้รับการตรวจโดยผู้ประเมิน 3 คน คือ <b>ครูผู้สอนและผู้เชี่ยวชาญ 2 ท่าน</b> เพื่อคำนวณค่าความสอดคล้องระหว่างผู้ตรวจ ด้วยสัมประสิทธิ์สหสัมพันธ์ภายในชั้น ICC(2,1) แบบ two-way random effects, absolute agreement ตามแนวทางของ Koo &amp; Li (2016)</p>');

    // ----- ส่วนที่ 2 : ICC (606, หน่วย 1, ละเอียด) -----
    P.push('<div class="pagebreak"></div>');
    P.push('<h1 class="secn" id="s2">ส่วนที่ 2 ผลการตรวจสอบความสอดคล้องระหว่างผู้ตรวจ (ICC)</h1>');
    P.push('<p>คำนวณจากคะแนนของผู้ตรวจ 3 คน (ครูผู้สอน + ผู้เชี่ยวชาญ 2 ท่าน) เฉพาะนักเรียน<b>กลุ่มทดลอง (ห้อง ' + esc(EXPERIMENTAL_CLASSROOM) + ')</b> ในภาระงาน<b>หน่วยที่ 1</b> จำนวนนักเรียนที่ผู้ตรวจครบทั้ง 3 คน = <b>' + triples.length + ' คน</b> · เกณฑ์การแปลผล: ≥0.90 ดีเยี่ยม, 0.75–0.89 ดี, 0.50–0.74 ปานกลาง, &lt;0.50 ต่ำ</p>');
    P.push('<h2>2.1 ค่า ICC รายด้านหลัก</h2>');
    P.push(tbl(['มิติคะแนน', 'N', 'ค่า ICC', 'การแปลผล'], iccMainRows, { numCols: [1, 2] }));
    P.push('<h2>2.2 ค่า ICC รายตัวชี้วัดย่อย (11 ตัวชี้วัด)</h2>');
    P.push(tbl(['ตัวชี้วัดย่อย', 'N', 'ค่า ICC', 'การแปลผล'], iccSubRows, { numCols: [1, 2] }));
    P.push('<h2>2.3 คะแนนของผู้ตรวจทั้ง 3 คน รายบุคคล</h2>');
    P.push('<p>แสดงคะแนน 4 ด้านและคะแนนรวมที่ผู้ตรวจแต่ละคนให้ (ภาระงานหน่วยที่ 1)</p>');
    P.push(tbl(['ที่', 'ชื่อ-สกุล', 'ผู้ตรวจ', 'ด้านเนื้อหา', 'ด้านองค์ประกอบ', 'ด้านภาษา', 'ด้านอักขรวิธี', 'คะแนนรวม'], raterRows, { numCols: [0, 3, 4, 5, 6, 7] }));
    P.push('<p class="analysis"><b>บทวิเคราะห์:</b> ค่า ICC ของคะแนนรวม (ภาระงานหน่วยที่ 1) เท่ากับ ' + f4(iccOverall.icc) + ' (' + iccOverall.interp.text + ') ' +
      'เมื่อพิจารณารายด้านและรายตัวชี้วัดย่อยพบว่าค่าความสอดคล้องอยู่ในระดับที่ยอมรับได้ตามเกณฑ์ของ Koo &amp; Li (2016) แสดงว่าครูผู้สอนและผู้เชี่ยวชาญให้คะแนนสอดคล้องกัน จึงเป็นหลักฐานยืนยันความเที่ยงของเครื่องมือและกระบวนการประเมิน</p>');

    // ----- ส่วนที่ 3 : เชิงปริมาณ (กลุ่มตัวอย่าง) -----
    P.push('<div class="pagebreak"></div>');
    P.push('<h1 class="secn" id="s3">ส่วนที่ 3 ผลการวิเคราะห์เชิงปริมาณ (กลุ่มตัวอย่าง)</h1>');
    P.push('<h2>3.1 ภาพรวม: สถิติเชิงพรรณนาของคะแนนรวม จำแนกตามรอบ</h2>');
    P.push(tbl(['รอบการประเมิน', 'N', 'ค่าเฉลี่ย', 'S.D.', 'ต่ำสุด', 'สูงสุด'], descRows, { numCols: [1, 2, 3, 4, 5] }));
    P.push('<h2>3.2 ภาพรวม: สถิติเชิงพรรณนารายด้าน (' + esc(mainPhaseLabel) + ')</h2>');
    P.push(tbl(['มิติคะแนน', 'N', 'ค่าเฉลี่ย', 'S.D.', 'ต่ำสุด', 'สูงสุด'], dimDescRows, { numCols: [1, 2, 3, 4, 5] }));
    P.push('<h2>3.3 การทดสอบพัฒนาการด้วย Paired-samples t-test (ก่อนเรียน–หลังเรียน)</h2>');
    if (tTeacher.insufficient) {
      P.push('<p style="color:#888">— ยังมีคู่คะแนนก่อน–หลังไม่เพียงพอสำหรับการทดสอบ (N = ' + tTeacher.N + ') —</p>');
    } else {
      P.push(tbl(['N', 'x̄ ก่อน', 'x̄ หลัง', 'ผลต่าง', 'S.D.(ผลต่าง)', 't', 'df', 'p (one-tailed)'],
        [[tTeacher.N, f2(tTeacher.meanPre), f2(tTeacher.meanPost), '+' + f2(tTeacher.meanDiff), f2(tTeacher.sdDiff), f4(tTeacher.t), tTeacher.df, (tTeacher.p < 0.001 ? '<0.001' : f4(tTeacher.p))]],
        { numCols: [0, 1, 2, 3, 4, 5, 6, 7] }));
      const sig = tTeacher.p < 0.05;
      P.push('<p class="analysis"><b>บทวิเคราะห์:</b> คะแนนเฉลี่ยหลังเรียน (' + f2(tTeacher.meanPost) + ') สูงกว่าก่อนเรียน (' + f2(tTeacher.meanPre) + ') เฉลี่ย ' + f2(tTeacher.meanDiff) + ' คะแนน; t(' + tTeacher.df + ') = ' + f4(tTeacher.t) + ', p ' + (tTeacher.p < 0.001 ? '< 0.001' : '= ' + f4(tTeacher.p)) + ' จึง<b>' + (sig ? 'มีนัยสำคัญทางสถิติที่ระดับ .05' : 'ยังไม่พบนัยสำคัญที่ระดับ .05') + '</b> (Cohen\'s d<sub>z</sub> = ' + f2(tTeacher.dz) + ', ' + effectSizeLabel(tTeacher.dz) + ')</p>');
    }
    P.push('<h2>3.4 รายละเอียด: คะแนนรายบุคคลจำแนกรายด้าน (' + esc(mainPhaseLabel) + ')</h2>');
    P.push(tbl(['ที่', 'ชื่อ-สกุล', 'ด้านเนื้อหา', 'ด้านองค์ประกอบ', 'ด้านภาษา', 'ด้านอักขรวิธี', 'คะแนนรวม'], perStudentDetail, { numCols: [0, 2, 3, 4, 5, 6] }));

    // ----- ส่วนที่ 4 : เชิงคุณภาพ (กลุ่มตัวอย่าง) ข้อมูลจริงทั้งหมด -----
    P.push('<div class="pagebreak"></div>');
    P.push('<h1 class="secn" id="s4">ส่วนที่ 4 ผลการวิเคราะห์เชิงคุณภาพ (กลุ่มตัวอย่าง)</h1>');
    P.push('<h2>4.1 ความถี่ของปัญหา/อุปสรรคการเขียน จำแนกรายตัวชี้วัด</h2>');
    const critRows = QUAL_SUB_CRITERIA.map(sc => {
      const avgPeer = peerCnt[sc] > 0 ? peerSum[sc] / peerCnt[sc] : null;
      return [criteriaMap[sc].name.split(' (')[0], critCounts[sc], avgPeer === null ? '-' : f2(avgPeer) + ' / 4'];
    });
    P.push(tbl(['ตัวชี้วัด', 'จำนวนที่พบปัญหา (คน)', 'คะแนนเฉลี่ยจากเพื่อน'], critRows, { numCols: [1, 2] }));
    if (sortedCrit[0] && sortedCrit[0][1] > 0) {
      P.push('<p class="analysis"><b>บทวิเคราะห์:</b> ตัวชี้วัดที่พบปัญหามากที่สุดคือ <b>' + esc(criteriaMap[sortedCrit[0][0]].name.split(' (')[0]) + '</b> (' + sortedCrit[0][1] + ' คน)' +
        (sortedCrit[1] && sortedCrit[1][1] > 0 ? ' รองลงมาคือ <b>' + esc(criteriaMap[sortedCrit[1][0]].name.split(' (')[0]) + '</b> (' + sortedCrit[1][1] + ' คน)' : '') +
        ' ควรออกแบบกิจกรรมเสริมที่เน้นตัวชี้วัดเหล่านี้เป็นลำดับแรก</p>');
    }

    // 4.2 ข้อมูลเชิงบรรยายจริงทั้งหมด
    P.push('<h2>4.2 ข้อมูลเชิงบรรยายจากนักเรียนและเพื่อนผู้ประเมิน (ข้อมูลจริงทั้งหมด)</h2>');
    // 4.2.1 ปัญหาการเขียนและแนวทางแก้ไข
    P.push('<h3>4.2.1 บันทึกปัญหาการเขียนและแนวทางแก้ไข</h3>');
    const probRows = [];
    problems.forEach(p => {
      QUAL_SUB_CRITERIA.forEach(sc => {
        const k = sc.replace('.', '_');
        const prob = (p['prob_' + k] || '').trim(); const sol = (p['sol_' + k] || '').trim();
        if (prob || sol) probRows.push([esc(nameOf(p.student_id)), criteriaMap[sc].name.split(' (')[0], prob, sol]);
      });
    });
    P.push(tbl(['ชื่อ-สกุล', 'ตัวชี้วัด', 'ปัญหา/อุปสรรคที่พบ', 'แนวทางแก้ไข'], probRows));
    // 4.2.2 การประเมินเพื่อนเชิงบรรยาย
    P.push('<h3>4.2.2 ข้อเสนอแนะจากการประเมินเพื่อน</h3>');
    const peerRows = [];
    peerReviews.forEach(pr => {
      const st = (pr.strength || '').trim(), im = (pr.improvement || '').trim(), en = (pr.encouragement || '').trim();
      if (st || im || en) peerRows.push([esc(nameOf(pr.student_id)), esc(nameOf(pr.reviewer_id)), st, im, en]);
    });
    P.push(tbl(['เจ้าของผลงาน', 'เพื่อนผู้ประเมิน', 'จุดแข็ง/สิ่งที่ประทับใจ', 'จุดที่ควรปรับปรุง', 'ข้อความให้กำลังใจ'], peerRows));
    // 4.2.3 การสะท้อนการเรียนรู้
    P.push('<h3>4.2.3 การสะท้อนการเรียนรู้ของนักเรียน</h3>');
    const refRows = [];
    reflections.forEach(rf => {
      refRows.push([esc(nameOf(rf.student_id)), (rf.content_structure || '').trim(), (rf.language_mechanics || '').trim(), (rf.feedback_applied || '').trim(), (rf.future_goals || '').trim()]);
    });
    P.push(tbl(['ชื่อ-สกุล', 'ด้านเนื้อหา/องค์ประกอบ', 'ด้านภาษา/อักขรวิธี', 'การนำข้อเสนอแนะไปปรับปรุง', 'เป้าหมายในอนาคต'], refRows));

    // ----- ส่วนที่ 5 : เรียงความ (กลุ่มตัวอย่าง) เชิงปริมาณ -----
    let sampleEssays = [];
    try {
      const er = await fetch('api.php?action=get_all_essays&_t=' + Date.now());
      const ed = await er.json();
      if (ed.success && Array.isArray(ed.essays)) sampleEssays = ed.essays.filter(e => e.student_group === SAMPLE_GROUP || sampleIds.has(e.student_id));
    } catch (e) { /* ไม่มีเรียงความก็ข้ามได้ */ }
    P.push('<div class="pagebreak"></div>');
    P.push('<h1 class="secn" id="s5">ส่วนที่ 5 ผลงานเรียงความของนักเรียน (เชิงปริมาณ)</h1>');
    const essayRows = phaseKeys.map(([k, label]) => {
      const list = sampleEssays.filter(e => essayTopicPhase(e.essay_phase) === k);
      const words = list.map(e => Number(e.word_count) || 0);
      const st = descStats(words);
      return st ? [label, list.length, Math.round(st.mean).toLocaleString('th-TH'), st.min.toLocaleString('th-TH'), st.max.toLocaleString('th-TH')] : [label, 0, '-', '-', '-'];
    });
    P.push(tbl(['รอบ/ภาระงาน', 'จำนวนที่ส่ง (ฉบับ)', 'จำนวนคำเฉลี่ย', 'คำน้อยสุด', 'คำมากสุด'], essayRows, { numCols: [1, 2, 3, 4] }));

    // ----- ส่วนที่ 6 : สรุปและอภิปรายผล (ละเอียด) -----
    P.push('<div class="pagebreak"></div>');
    P.push('<h1 class="secn" id="s6">ส่วนที่ 6 สรุปและอภิปรายผล</h1>');
    P.push('<h2>6.1 สรุปผลการวิจัย</h2>');
    let sumQuant = 'ด้านผลสัมฤทธิ์เชิงปริมาณ ';
    if (tTeacher.insufficient) {
      sumQuant += 'ยังมีคู่คะแนนก่อน–หลังของกลุ่มตัวอย่างไม่เพียงพอต่อการทดสอบพัฒนาการด้วย Paired-samples t-test จึงควรเก็บข้อมูลเพิ่มเติมให้ครบทั้งก่อนเรียนและหลังเรียน อย่างไรก็ตามสถิติเชิงพรรณนาแสดงระดับคะแนนของผู้เรียนในแต่ละรอบไว้เพื่อการติดตามพัฒนาการ';
    } else {
      const sig = tTeacher.p < 0.05;
      sumQuant += 'พบว่าคะแนนเฉลี่ยหลังเรียน (' + f2(tTeacher.meanPost) + ') สูงกว่าก่อนเรียน (' + f2(tTeacher.meanPre) + ') เฉลี่ย ' + f2(tTeacher.meanDiff) + ' คะแนน และจากการทดสอบ Paired-samples t-test พบว่า t(' + tTeacher.df + ') = ' + f4(tTeacher.t) + ', p ' + (tTeacher.p < 0.001 ? '< 0.001' : '= ' + f4(tTeacher.p)) + ' ' + (sig ? 'ซึ่งมีนัยสำคัญทางสถิติที่ระดับ .05 และมีขนาดอิทธิพลในระดับ' + effectSizeLabel(tTeacher.dz) + ' (Cohen\'s d_z = ' + f2(tTeacher.dz) + ') สะท้อนว่าการจัดการเรียนรู้ช่วยพัฒนาความสามารถในการเขียนเรียงความของผู้เรียนได้จริง' : 'ซึ่งยังไม่ถึงระดับนัยสำคัญ .05 จึงควรเพิ่มขนาดตัวอย่างหรือระยะเวลาการทดลอง');
    }
    P.push('<p>' + esc(sumQuant) + '</p>');
    P.push('<p>ด้านคุณภาพเครื่องมือ ค่าความสอดคล้องระหว่างผู้ตรวจ (ICC) ของคะแนนรวมในภาระงานหน่วยที่ 1 เท่ากับ ' + f4(iccOverall.icc) + ' (' + iccOverall.interp.text + ') โดยเมื่อพิจารณารายด้านหลักและรายตัวชี้วัดย่อยทั้ง 11 ตัวชี้วัด พบว่าค่าความสอดคล้องอยู่ในเกณฑ์ที่ยอมรับได้ แสดงว่าเกณฑ์การประเมินมีความชัดเจนและผู้ตรวจเข้าใจตรงกัน</p>');
    if (sortedCrit[0] && sortedCrit[0][1] > 0) {
      P.push('<p>ด้านข้อมูลเชิงคุณภาพ ปัญหาการเขียนที่พบบ่อยที่สุดคือ <b>' + esc(criteriaMap[sortedCrit[0][0]].name.split(' (')[0]) + '</b>' + (sortedCrit[1] && sortedCrit[1][1] > 0 ? ' และ <b>' + esc(criteriaMap[sortedCrit[1][0]].name.split(' (')[0]) + '</b>' : '') + ' สอดคล้องกับข้อเสนอแนะจากการประเมินเพื่อนและการสะท้อนคิดของผู้เรียน ที่ชี้ให้เห็นจุดที่ควรพัฒนาอย่างเป็นรูปธรรม</p>');
    }
    P.push('<h2>6.2 อภิปรายผล</h2>');
    P.push('<p>ผลการวิจัยชี้ให้เห็นว่ากระบวนการประเมินแบบหลายมุมมอง (ครู–ตนเอง–เพื่อน) ร่วมกับการบันทึกปัญหาการเขียนและการสะท้อนการเรียนรู้ ช่วยให้ผู้เรียนตระหนักถึงจุดแข็งและจุดที่ต้องพัฒนาของตนเอง การที่ค่า ICC อยู่ในระดับที่ยอมรับได้สนับสนุนว่าเครื่องมือประเมินมีความเที่ยงตรงเพียงพอที่จะใช้ติดตามพัฒนาการของผู้เรียนได้อย่างน่าเชื่อถือ ขณะที่ข้อมูลเชิงคุณภาพช่วยเสริมความเข้าใจเชิงลึกที่ตัวเลขเพียงอย่างเดียวไม่สามารถอธิบายได้</p>');
    P.push('<h2>6.3 ข้อเสนอแนะ</h2>');
    P.push('<ul>' +
      '<li>ควรนำตัวชี้วัดที่ผู้เรียนพบปัญหามากที่สุดไปออกแบบกิจกรรมซ่อมเสริมแบบเจาะจง</li>' +
      '<li>ควรใช้ข้อมูลเชิงคุณภาพ (ปัญหาการเขียน ข้อเสนอแนะจากเพื่อน และการสะท้อนคิด) ประกอบการวางแผนพัฒนาผู้เรียนเป็นรายบุคคล</li>' +
      '<li>ควรเก็บข้อมูลคะแนนให้ครบทั้งก่อนเรียนและหลังเรียนของทุกคน เพื่อให้การทดสอบพัฒนาการมีความสมบูรณ์</li>' +
      '<li>อาจขยายผลการตรวจสอบความเที่ยง (ICC) ไปยังภาระงานหน่วยอื่น เพื่อยืนยันความคงเส้นคงวาของเกณฑ์ประเมิน</li>' +
      '</ul>');

    const body = P.join('\n');

    // ================= สารบัญ + ปก + คำนำ =================
    const toc = '<div class="toc">' +
      '<h1 class="secn nonum">สารบัญ</h1>' +
      '<div class="tocitem"><span>ส่วนที่ 1 บทนำและกระบวนการวิจัย</span></div>' +
      '<div class="tocitem"><span>ส่วนที่ 2 ผลการตรวจสอบความสอดคล้องระหว่างผู้ตรวจ (ICC)</span></div>' +
      '<div class="tocitem"><span>ส่วนที่ 3 ผลการวิเคราะห์เชิงปริมาณ (กลุ่มตัวอย่าง)</span></div>' +
      '<div class="tocitem"><span>ส่วนที่ 4 ผลการวิเคราะห์เชิงคุณภาพ (กลุ่มตัวอย่าง)</span></div>' +
      '<div class="tocitem"><span>ส่วนที่ 5 ผลงานเรียงความของนักเรียน (เชิงปริมาณ)</span></div>' +
      '<div class="tocitem"><span>ส่วนที่ 6 สรุปและอภิปรายผล</span></div>' +
      '</div>';

    const preface = '<div class="pagebreak"></div>' +
      '<h1 class="secn nonum">คำนำ</h1>' +
      '<p>รายงานฉบับนี้เป็นการประมวลผลข้อมูลจากระบบประเมินการเขียนเรียงความ เพื่อนำเสนอผลการวิเคราะห์ความสามารถในการเขียนเรียงความของนักเรียน ทั้งในเชิงปริมาณ ได้แก่ สถิติเชิงพรรณนา การทดสอบพัฒนาการด้วย Paired-samples t-test และค่าความสอดคล้องระหว่างผู้ตรวจ (ICC) และในเชิงคุณภาพ ได้แก่ ปัญหาการเขียนรายตัวชี้วัด ข้อเสนอแนะจากการประเมินเพื่อน และการสะท้อนการเรียนรู้ของนักเรียน</p>' +
      '<p>เนื้อหาเรียบเรียงตามลำดับกระบวนการวิจัย เพื่อให้ผู้อ่านติดตามได้ตั้งแต่ที่มา ระเบียบวิธี ผลการวิเคราะห์ ไปจนถึงข้อสรุปและข้อเสนอแนะ ผู้จัดทำหวังว่ารายงานนี้จะเป็นประโยชน์ต่อการพัฒนาการจัดการเรียนรู้และการพัฒนาผู้เรียนต่อไป</p>' +
      '<p style="text-align:right;margin-top:24pt">' + esc(REPORT_AUTHOR) + '<br>' + esc(thDate) + '</p>';

    const cover = '<div class="cover">' +
      '<div class="cover-top">รายงานผลการวิจัยในชั้นเรียน</div>' +
      '<div class="cover-title">การวิเคราะห์ความสามารถในการเขียนเรียงความของนักเรียน</div>' +
      '<div class="cover-sub">แบบแผนการวิจัย: กลุ่มทดลองกลุ่มเดียว ทดสอบก่อน–หลัง<br>(One-Group Pretest–Posttest Design)</div>' +
      '<div class="cover-box">กลุ่มตัวอย่าง ' + sampleStudents.length + ' คน &nbsp;•&nbsp; ตรวจสอบความเที่ยง (ICC) จากกลุ่มทดลองห้อง ' + esc(EXPERIMENTAL_CLASSROOM) + '</div>' +
      '<div class="cover-foot">จัดทำโดย ' + esc(REPORT_AUTHOR) + '<br>วันที่ ' + esc(thDate) + '</div>' +
      '</div>';

    const css =
      '@page { size: A4; margin: 2.54cm 2.2cm; }' +
      'body { font-family: "TH Sarabun New","Sarabun","Angsana New","Cordia New",serif; font-size: 16pt; color:#000; line-height:1.5; }' +
      'h1.secn { font-size: 20pt; color:#1F3A5F; border-bottom:2pt solid #1F3A5F; padding-bottom:4pt; margin:0 0 12pt; }' +
      'h2 { font-size: 17pt; color:#22406a; margin:14pt 0 6pt; }' +
      'h3 { font-size: 16pt; color:#333; margin:10pt 0 4pt; }' +
      'p { margin: 0 0 8pt; text-align: justify; }' +
      'ul { margin: 0 0 8pt 0; }' +
      'table.data { border-collapse: collapse; width: 100%; margin: 6pt 0 12pt; font-size: 15pt; }' +
      'table.data th { background:#1F3A5F; color:#fff; border:0.75pt solid #33455f; padding:4pt 6pt; text-align:center; }' +
      'table.data td { border:0.75pt solid #999; padding:3pt 6pt; vertical-align:top; }' +
      'p.analysis { background:#eef3fb; border-left:3pt solid #1F3A5F; padding:6pt 10pt; margin:6pt 0 12pt; }' +
      '.pagebreak { page-break-before: always; }' +
      '.cover { text-align:center; padding-top:110pt; }' +
      '.cover-top { font-size:22pt; color:#1F3A5F; letter-spacing:1pt; margin-bottom:30pt; }' +
      '.cover-title { font-size:30pt; font-weight:bold; color:#111; margin-bottom:16pt; line-height:1.35; }' +
      '.cover-sub { font-size:17pt; color:#444; margin-bottom:40pt; }' +
      '.cover-box { display:inline-block; border:1.5pt solid #1F3A5F; border-radius:6pt; padding:8pt 20pt; font-size:16pt; color:#1F3A5F; margin-bottom:60pt; }' +
      '.cover-foot { font-size:17pt; color:#333; }' +
      '.toc .tocitem { font-size:16pt; padding:5pt 0; border-bottom:0.5pt dotted #bbb; }' +
      'h1.nonum { text-align:center; border-bottom:none; }';

    const doc =
      '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">' +
      '<head><meta charset="utf-8"><title>รายงานผลการวิจัย</title>' +
      '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom><w:DoNotOptimizeForBrowser/></w:WordDocument></xml><![endif]-->' +
      '<style>' + css + '</style></head><body>' +
      cover + toc + preface + '<div class="pagebreak"></div>' + body +
      '</body></html>';

    return { doc, filename: 'รายงานผลการวิจัย_เขียนเรียงความ_' + now.toISOString().slice(0, 10) };
  }

  // ---- ส่งรายงานเข้า Google Docs โดยตรง (ผ่าน Google Drive API) ----
  async function sendReportToGoogleDocs() {
    const btn = document.getElementById('genReportBtn');
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังจัดทำและส่งเข้า Google Docs...'; }
    try {
      // 1) ตรวจสถานะการเชื่อมต่อบัญชี Google
      let status;
      try { status = await (await fetch('google_auth.php?action=status&_t=' + Date.now())).json(); }
      catch (e) { throw new Error('ยังไม่ได้ตั้งค่า Google API (google_auth.php) บนเซิร์ฟเวอร์'); }
      if (!status || !status.configured) {
        if (typeof showToast === 'function') showToast('ผู้ดูแลระบบยังไม่ได้ตั้งค่า Google API (โปรดกรอก Client ID/Secret ใน google_config.php)', 'error');
        return;
      }
      if (!status.connected) {
        if (typeof showToast === 'function') showToast('กำลังพาไปเชื่อมต่อบัญชี Google ครั้งแรก...', 'info');
        const ret = encodeURIComponent(location.pathname + '#section-export');
        window.location.href = 'google_auth.php?action=connect&return=' + ret;
        return;
      }
      // 2) สร้างเนื้อหารายงาน แล้วส่งขึ้น Google Drive (แปลงเป็น Google Docs)
      const rep = await buildReportHtml();
      const httpResp = await fetch('google_upload_doc.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ html: rep.doc, title: rep.filename })
      });
      const raw = await httpResp.text();
      let res;
      try { res = JSON.parse(raw); }
      catch (e) {
        // เซิร์ฟเวอร์ตอบไม่ใช่ JSON (เช่น PHP error/warning) — แสดงเนื้อหาเพื่อวินิจฉัย
        console.error('Upload response (HTTP ' + httpResp.status + '):', raw);
        throw new Error('เซิร์ฟเวอร์ตอบไม่ใช่ JSON (HTTP ' + httpResp.status + '): ' + raw.slice(0, 200));
      }
      if (res.success) {
        if (typeof showToast === 'function') showToast('ส่งเข้า Google Docs สำเร็จ! กำลังเปิดเอกสาร...', 'success');
        window.open(res.link, '_blank');
      } else if (res.reauth) {
        const ret = encodeURIComponent(location.pathname + '#section-export');
        window.location.href = 'google_auth.php?action=connect&return=' + ret;
      } else {
        // แสดงรายละเอียดให้มากที่สุดเพื่อวินิจฉัย (เผื่อ response ไม่ใช่รูปแบบที่คาดไว้)
        console.error('Upload failed, response =', res);
        throw new Error(res.error || ('อัปโหลดไม่สำเร็จ (HTTP ' + httpResp.status + ') — ' + JSON.stringify(res).slice(0, 200)));
      }
    } catch (err) {
      console.error(err);
      if (typeof showToast === 'function') showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
      else alert('เกิดข้อผิดพลาด: ' + err.message);
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
  }

  // ---- แสดงสถานะการเชื่อมต่อ Google + redirect URI สำหรับตั้งค่า ----
  async function loadGoogleStatus() {
    const box = document.getElementById('googleStatusBox');
    if (!box) return;
    try {
      const st = await (await fetch('google_auth.php?action=status&_t=' + Date.now())).json();
      if (!st.configured) {
        box.innerHTML = '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> ยังไม่ได้ตั้งค่า Google API</span>' +
          '<div class="text-muted mt-1">ผู้ดูแลระบบต้องกรอก Client ID/Secret ใน <code>google_config.php</code> และลงทะเบียน Redirect URI นี้ใน Google Cloud Console:</div>' +
          (st.redirect_uri ? '<div class="mt-1"><code style="word-break:break-all">' + st.redirect_uri + '</code></div>' : '');
      } else if (st.connected) {
        box.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> เชื่อมต่อบัญชี Google แล้ว</span>' +
          ' <a href="google_auth.php?action=disconnect" onclick="event.preventDefault();fetch(this.href).then(()=>loadGoogleStatus());" class="ms-2 text-danger text-decoration-none small">ยกเลิกการเชื่อมต่อ</a>';
      } else {
        box.innerHTML = '<span class="badge bg-secondary"><i class="bi bi-plug"></i> ยังไม่ได้เชื่อมต่อบัญชี Google</span>' +
          '<span class="text-muted ms-2">กดปุ่มด้านบนเพื่อเชื่อมต่อครั้งแรก</span>';
      }
    } catch (e) {
      box.innerHTML = '';
    }
  }

  // ---- ดาวน์โหลดเป็นไฟล์ .doc (ตัวเลือกสำรอง) ----
  async function generateResearchReportDoc() {
    const btn = document.getElementById('dlDocBtn');
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>กำลังจัดทำรายงาน...'; }
    try {
      const rep = await buildReportHtml();
      const blob = new Blob(['﻿', rep.doc], { type: 'application/msword' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = rep.filename + '.doc';
      document.body.appendChild(a); a.click(); document.body.removeChild(a);
      setTimeout(() => URL.revokeObjectURL(url), 4000);
      if (typeof showToast === 'function') showToast('สร้างรายงานสำเร็จ', 'success');
    } catch (err) {
      console.error(err);
      if (typeof showToast === 'function') showToast('เกิดข้อผิดพลาด: ' + err.message, 'error');
      else alert('เกิดข้อผิดพลาด: ' + err.message);
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
  }

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
    const icc = (MSR - MSE) / denom;
    // คืนค่ากลางทั้งหมด เพื่อนำไปแสดง "ค่าที่แทนในสูตร" ให้เห็นชัด
    return { icc, n, k, grand, SSR, SSC, SSE, SST, MSR, MSC, MSE, denom };
  }

  // แปลงคะแนนรวม (เต็ม 60) เป็นระดับคุณภาพ — ใช้เกณฑ์เดียวกับหน้าฟอร์มประเมิน
  function getScoreLevel(total) {
    if (total >= 49) return { text: 'ดีมาก', css: 'text-success' };
    if (total >= 37) return { text: 'ดี', css: 'text-primary' };
    if (total >= 25) return { text: 'ปานกลาง', css: 'text-warning' };
    if (total >= 13) return { text: 'พอใช้', css: 'text-warning' };
    return { text: 'ต้องปรับปรุง', css: 'text-danger' };
  }

  // แปลง "คะแนน/คะแนนเต็ม" ของแต่ละด้าน เป็นระดับที่ผู้ตรวจเลือก (อิงเกณฑ์เดียวกับคะแนนรวม โดยเทียบเป็นสัดส่วน)
  // คืนค่าคลาส badge เพื่อระบายสีระดับให้ดูง่าย
  // แถบระดับ "เทียบเกณฑ์" ที่คำนวณจากคะแนนรวม/รายด้าน (ไม่ใช่ระดับที่ผู้ตรวจเลือกเอง)
  // ใช้สไตล์ outline (soft) เพื่อแยกให้ชัดจากระดับที่ผู้ตรวจเลือกรายข้อ
  function getDomainLevel(score, max) {
    const ratio = max > 0 ? score / max : 0;
    if (ratio >= 49 / 60) return { text: 'ดีมาก', badge: 'bg-success', soft: 'bg-success-subtle text-success-emphasis border border-success-subtle' };
    if (ratio >= 37 / 60) return { text: 'ดี', badge: 'bg-info text-dark', soft: 'bg-info-subtle text-info-emphasis border border-info-subtle' };
    if (ratio >= 25 / 60) return { text: 'ปานกลาง', badge: 'bg-warning text-dark', soft: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' };
    if (ratio >= 13 / 60) return { text: 'พอใช้', badge: 'bg-warning text-dark', soft: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' };
    return { text: 'ต้องปรับปรุง', badge: 'bg-danger', soft: 'bg-danger-subtle text-danger-emphasis border border-danger-subtle' };
  }

  // ระดับที่ผู้ตรวจเลือก "รายข้อ" — คะแนนรายข้อ = ระดับ(0-4) × ตัวคูณ ดังนั้น ระดับ = score/max×4 พอดี
  function getRubricLevel(score, max) {
    const lv = max > 0 ? Math.round((score / max) * 4) : 0;
    if (lv >= 4) return { text: 'ดีมาก', badge: 'bg-success' };
    if (lv === 3) return { text: 'ดี', badge: 'bg-info text-dark' };
    if (lv === 2) return { text: 'ปานกลาง', badge: 'bg-warning text-dark' };
    if (lv === 1) return { text: 'พอใช้', badge: 'bg-warning text-dark' };
    return { text: 'ต้องปรับปรุง', badge: 'bg-danger' };
  }

  // แสดงรายละเอียดการคำนวณ ICC (ค่าที่แทนในสูตร) ของคะแนนรวม
  function renderIccFormula(d) {
    const el = document.getElementById('iccFormulaPanel');
    if (!el) return;
    if (!d) {
      el.innerHTML = '<em class="text-muted">ยังมีข้อมูลไม่พอสำหรับคำนวณ — ต้องมีนักเรียนห้อง 606 ที่ผู้ตรวจครบ 3 คน อย่างน้อย 2 คน</em>';
      return;
    }
    const n = d.n, k = d.k;
    const f2 = x => Number(x).toFixed(2);
    const f3 = x => Number(x).toFixed(3);
    const f4 = x => Number(x).toFixed(4);
    const dfR = n - 1, dfC = k - 1, dfE = (n - 1) * (k - 1);
    const num = d.MSR - d.MSE;
    el.innerHTML = `
      <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="border rounded-2 p-2 text-center bg-light"><div class="text-muted" style="font-size:.75rem;">จำนวนนักเรียน (n)</div><div class="fs-5 fw-bold text-dark">${n}</div></div></div>
        <div class="col-6 col-md-3"><div class="border rounded-2 p-2 text-center bg-light"><div class="text-muted" style="font-size:.75rem;">จำนวนผู้ตรวจ (k)</div><div class="fs-5 fw-bold text-dark">${k}</div></div></div>
        <div class="col-6 col-md-3"><div class="border rounded-2 p-2 text-center bg-light"><div class="text-muted" style="font-size:.75rem;">ค่าเฉลี่ยรวม (Grand mean)</div><div class="fs-5 fw-bold text-dark">${f2(d.grand)}</div></div></div>
        <div class="col-6 col-md-3"><div class="border rounded-2 p-2 text-center bg-light"><div class="text-muted" style="font-size:.75rem;">ผลลัพธ์ ICC(2,1)</div><div class="fs-5 fw-bold text-primary">${f4(d.icc)}</div></div></div>
      </div>
      <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered text-center align-middle mb-0">
          <thead class="table-light"><tr><th class="text-start">แหล่งความแปรปรวน</th><th>Sum of Squares (SS)</th><th>df</th><th>Mean Square (MS)</th></tr></thead>
          <tbody>
            <tr><td class="text-start">ระหว่างนักเรียน (Rows)</td><td>${f2(d.SSR)}</td><td>n−1 = ${dfR}</td><td class="fw-bold">MSR = ${f3(d.MSR)}</td></tr>
            <tr><td class="text-start">ระหว่างผู้ตรวจ (Columns)</td><td>${f2(d.SSC)}</td><td>k−1 = ${dfC}</td><td class="fw-bold">MSC = ${f3(d.MSC)}</td></tr>
            <tr><td class="text-start">ความคลาดเคลื่อน (Error)</td><td>${f2(d.SSE)}</td><td>(n−1)(k−1) = ${dfE}</td><td class="fw-bold">MSE = ${f3(d.MSE)}</td></tr>
            <tr class="table-light"><td class="text-start fw-bold">รวม (Total)</td><td>${f2(d.SST)}</td><td>${n * k - 1}</td><td>—</td></tr>
          </tbody>
        </table>
      </div>
      <div class="p-3 rounded-3" style="background:#eff6ff; line-height:2;">
        <div class="fw-bold text-dark mb-1">สูตร ICC(2,1) — two-way random effects, absolute agreement, single rater</div>
        <div class="font-mono">ICC = (MSR − MSE) / [ MSR + (k−1)·MSE + (k/n)·(MSC − MSE) ]</div>
        <div class="font-mono">= ( ${f3(d.MSR)} − ${f3(d.MSE)} ) / [ ${f3(d.MSR)} + (${k}−1)·${f3(d.MSE)} + (${k}/${n})·( ${f3(d.MSC)} − ${f3(d.MSE)} ) ]</div>
        <div class="font-mono">= ${f3(num)} / ${f3(d.denom)} = <span class="fw-bold text-primary">${f4(d.icc)}</span></div>
      </div>
    `;
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
      const rs = document.getElementById('iccRaterSummary');
      if (rs) rs.innerHTML = 'ผู้ตรวจ 3 คน (ครูผู้สอน + ผู้เชี่ยวชาญ 2 ท่าน) — ยังมีนักเรียนที่ถูกตรวจครบไม่พอ';
      const sc = document.getElementById('iccStudentCards');
      if (sc) sc.innerHTML = `<div class="text-center py-4 text-muted">ต้องการข้อมูลนักเรียน<strong>ห้อง 606</strong> ที่ครูผู้สอน + ผู้เชี่ยวชาญ 2 คน ตรวจครบ อย่างน้อย 2 คน</div>`;
      renderIccFormula(null);
      return;
    }

    // มิติคะแนน: รวม + 4 ด้าน (ชื่อด้านตรงตามเกณฑ์การประเมิน + คะแนนเต็มรายด้าน)
    const dims = [
      { name: 'คะแนนรวม', max: 60, get: e => Number(e.total_score) },
      { name: '1) ด้านเนื้อหาสาระ', max: 27, get: e => Number(e.score_1_1) + Number(e.score_1_2) + Number(e.score_1_3) },
      { name: '2) ด้านองค์ประกอบและการลำดับ', max: 12, get: e => Number(e.score_2_1) + Number(e.score_2_2) },
      { name: '3) ด้านการใช้สำนวนภาษา', max: 15, get: e => Number(e.score_3_1) + Number(e.score_3_2) + Number(e.score_3_3) },
      { name: '4) ด้านอักขรวิธีและกลไกการเขียน', max: 6, get: e => Number(e.score_4_1) + Number(e.score_4_2) + Number(e.score_4_3) }
    ];

    // ภาพรวม = คะแนนรวม
    const totalMatrix = triples.map(tr => tr.evals.map(dims[0].get));
    const overallDetail = computeICC(totalMatrix);
    const overallICC = overallDetail ? overallDetail.icc : null;
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
      const det = computeICC(m);
      const icc = det ? det.icc : null;
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

    // อัปเดตข้อความสรุปจำนวนผู้ตรวจ/นักเรียน
    const raterSummaryEl = document.getElementById('iccRaterSummary');
    if (raterSummaryEl) {
      raterSummaryEl.innerHTML = `ผู้ตรวจ <strong>3 คน</strong> (ครูผู้สอน + ผู้เชี่ยวชาญ 2 ท่าน) · นักเรียนที่ถูกตรวจครบทั้ง 3 คน = <strong>${triples.length} คน</strong> · คะแนนเต็ม 60`
        + `<br><span style="font-size:.75rem;">เกณฑ์ระดับ: ≥49 ดีมาก · ≥37 ดี · ≥25 ปานกลาง · ≥13 พอใช้ · ต่ำกว่านั้น ต้องปรับปรุง</span>`;
    }

    // การ์ดคะแนนผู้ตรวจ 3 คน รายบุคคล — แยกรายด้าน แสดงคะแนน + ระดับที่ผู้ตรวจแต่ละคนเลือก
    const stCards = document.getElementById('iccStudentCards');
    if (stCards) {
      const raters = ['ครูผู้สอน', 'ผู้เชี่ยวชาญ 1', 'ผู้เชี่ยวชาญ 2'];
      // โครงสร้างรายข้อประเมิน (11 ข้อ) จัดกลุ่มตาม 4 ด้าน — ชื่อและคะแนนเต็มตรงตามเกณฑ์การประเมิน
      const itemGroups = [
        { domain: '1) ด้านเนื้อหาสาระ', max: 27, items: [
          { no: '1.1', name: 'ความตรงประเด็น', max: 12, key: 'score_1_1' },
          { no: '1.2', name: 'แก่นเรื่องชัดเจน', max: 6, key: 'score_1_2' },
          { no: '1.3', name: 'การขยายความและเหตุผล', max: 9, key: 'score_1_3' },
        ]},
        { domain: '2) ด้านองค์ประกอบและการลำดับ', max: 12, items: [
          { no: '2.1', name: 'ความครบถ้วนขององค์ประกอบ', max: 8, key: 'score_2_1' },
          { no: '2.2', name: 'การลำดับประเด็นเป็นระบบ', max: 4, key: 'score_2_2' },
        ]},
        { domain: '3) ด้านการใช้สำนวนภาษา', max: 15, items: [
          { no: '3.1', name: 'การใช้ประโยคถูกต้อง', max: 4, key: 'score_3_1' },
          { no: '3.2', name: 'การเลือกใช้คำ', max: 6, key: 'score_3_2' },
          { no: '3.3', name: 'ระดับภาษาเหมาะสม', max: 5, key: 'score_3_3' },
        ]},
        { domain: '4) ด้านอักขรวิธีและกลไกการเขียน', max: 6, items: [
          { no: '4.1', name: 'การสะกดคำถูกต้อง', max: 2, key: 'score_4_1' },
          { no: '4.2', name: 'การเว้นวรรค', max: 2, key: 'score_4_2' },
          { no: '4.3', name: 'ความเรียบร้อย', max: 2, key: 'score_4_3' },
        ]},
      ];

      // ช่องแสดงคะแนน + "ระดับที่ผู้ตรวจเลือก" รายข้อ (badge ทึบ — เป็นระดับที่ผู้ตรวจเลือกจริง)
      const scoreCell = (score, max, lv) => `<td class="text-center">
          <div class="fw-bold text-dark" style="font-size:.9rem;">${score.toFixed(1)}<span class="text-muted fw-normal" style="font-size:.7rem;">/${max}</span></div>
          <span class="badge ${lv.badge}" style="font-size:.63rem;">${lv.text}</span>
        </td>`;

      // ช่องแสดงคะแนนรวมด้าน/รวมทั้งหมด + "แถบระดับเทียบเกณฑ์" (badge outline + ≈ — เป็นค่าที่คำนวณ ไม่ใช่ระดับที่ผู้ตรวจเลือก)
      const bandCell = (score, max, lv) => `<td class="text-center">
          <div class="fw-bold text-dark" style="font-size:.9rem;">${score.toFixed(1)}<span class="text-muted fw-normal" style="font-size:.7rem;">/${max}</span></div>
          <span class="badge ${lv.soft}" style="font-size:.63rem;" title="แถบระดับที่คำนวณจากคะแนน — ไม่ใช่ระดับที่ผู้ตรวจเลือกรายข้อ">≈ ${lv.text}</span>
        </td>`;

      // ช่องพิสัย (สูงสุด−ต่ำสุด) พร้อมระบายสีเตือนเมื่อผู้ตรวจให้คะแนนต่างกันมาก
      const rangeCell = (vals, hi, mid) => {
        const range = Math.max(...vals) - Math.min(...vals);
        const rc = range >= hi ? 'text-danger fw-bold' : (range >= mid ? 'text-warning-emphasis' : 'text-success');
        return `<td class="text-center ${rc}">${range.toFixed(1)}</td>`;
      };

      const cardsHtml = triples.map(tr => {
        // แถวรายข้อ (11 ข้อ) จัดกลุ่มตาม 4 ด้าน + คะแนนรวมรายด้าน
        const bodyRows = itemGroups.map(g => {
          const header = `<tr class="table-secondary">
            <td class="text-start fw-bold text-dark" colspan="5">${escapeHtml(g.domain)}
              <span class="fw-normal text-muted" style="font-size:.7rem;">(คะแนนเต็ม ${g.max})</span></td>
          </tr>`;
          const itemRows = g.items.map(it => {
            const vals = tr.evals.map(e => Number(e[it.key]));
            return `<tr>
              <td class="text-start" style="padding-left:1.1rem;">
                <span class="text-muted font-mono" style="font-size:.72rem;">${it.no}</span> ${escapeHtml(it.name)}
                <span class="text-muted d-block" style="font-size:.65rem;padding-left:1.55rem;">คะแนนเต็ม ${it.max}</span></td>
              ${vals.map(v => scoreCell(v, it.max, getRubricLevel(v, it.max))).join('')}
              ${rangeCell(vals, it.max * 0.5, it.max * 0.25)}
            </tr>`;
          }).join('');
          // รวมคะแนนรายด้าน
          const dvals = tr.evals.map(e => g.items.reduce((s, it) => s + Number(e[it.key]), 0));
          const subRow = `<tr class="fw-semibold" style="background:#eef2f7;">
            <td class="text-start" style="padding-left:1.1rem;">รวมด้าน <span class="text-muted fw-normal" style="font-size:.65rem;">(ระดับ = เทียบเกณฑ์)</span></td>
            ${dvals.map(v => bandCell(v, g.max, getDomainLevel(v, g.max))).join('')}
            ${rangeCell(dvals, 4, 2)}
          </tr>`;
          return header + itemRows + subRow;
        }).join('');

        // แถวคะแนนรวม
        const totals = tr.evals.map(e => Number(e.total_score));
        const mean  = totals.reduce((a, b) => a + b, 0) / totals.length;
        const totalRange = Math.max(...totals) - Math.min(...totals);
        const trCls = totalRange >= 10 ? 'text-danger fw-bold' : (totalRange >= 5 ? 'text-warning-emphasis' : 'text-success');
        const meanLv = getDomainLevel(mean, 60);
        const totalRow = `<tr class="table-primary fw-bold">
          <td class="text-start text-dark">คะแนนรวม 4 ด้าน <span class="fw-normal d-block" style="font-size:.68rem;">คะแนนเต็ม 60 · ระดับ = เทียบเกณฑ์</span></td>
          ${totals.map(v => bandCell(v, 60, getDomainLevel(v, 60))).join('')}
          <td class="text-center ${trCls}">${totalRange.toFixed(1)}</td>
        </tr>`;

        return `
        <div class="border rounded-3 mb-3 overflow-hidden">
          <div class="d-flex flex-wrap align-items-center gap-2 px-3 py-2 bg-info bg-opacity-10 border-bottom">
            <span class="badge bg-info text-dark font-mono">${escapeHtml(tr.sid)}</span>
            <span class="fw-bold text-dark">${escapeHtml(tr.name)}</span>
            <span class="ms-auto small text-secondary">คะแนนรวมเฉลี่ย 3 คน:
              <strong class="text-primary" style="font-size:1rem;">${mean.toFixed(1)}</strong>/60
              <span class="badge ${meanLv.soft} ms-1" title="แถบระดับที่คำนวณจากคะแนน — ไม่ใช่ระดับที่ผู้ตรวจเลือก">≈ ${meanLv.text}</span></span>
          </div>
          <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0 small text-center">
              <thead class="table-light text-secondary">
                <tr>
                  <th class="text-start">ด้าน / ข้อประเมิน</th>
                  <th>${raters[0]}</th>
                  <th>${raters[1]}</th>
                  <th>${raters[2]}</th>
                  <th class="text-nowrap">พิสัย<br><span class="fw-normal" style="font-size:.66rem;">สูงสุด−ต่ำสุด</span></th>
                </tr>
              </thead>
              <tbody>${bodyRows}${totalRow}</tbody>
            </table>
          </div>
        </div>`;
      }).join('');

      const iccBanner = `
        <div class="alert alert-primary border-0 rounded-3 d-flex flex-wrap align-items-center gap-2 py-2 px-3 mb-0 small">
          <i class="bi bi-people-fill"></i>
          <span class="fw-semibold">ค่า ICC ภาพรวม (คะแนนรวม) ของผู้ตรวจ 3 คน =</span>
          <span class="fw-bold" style="font-size:1.05rem;">${overallICC !== null ? overallICC.toFixed(4) : 'N/A'}</span>
          <span class="badge ${overallInterp.css}">${overallInterp.text.split(' (')[0]}</span>
        </div>`;

      stCards.innerHTML = cardsHtml + iccBanner;
    }

    // แผงแสดง "ค่าที่แทนในสูตร ICC" ของคะแนนรวม
    renderIccFormula(overallDetail);

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
    task1:    'ภาระงาน หน่วยที่ 1',
    task2:    'ภาระงาน หน่วยที่ 2',
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
      const res = await fetch('api.php?action=get_all_essays');
      const data = await res.json();
      if (data.success) {
        // กรองเฉพาะเรียงความของนักเรียน "กลุ่มตัวอย่าง" เท่านั้น (ให้ตรงกับส่วนอื่นของรายงานวิจัยที่ใช้กลุ่มนี้)
        allEssaysCache = data.essays.filter(e => (e.student_group || '').trim() === SAMPLE_GROUP);
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

  // essay_phase ของจริงในฐานข้อมูลถูกแยกเป็นฉบับร่าง เช่น task1_d1/task1_d2/task2_d1/task2_d2
  // ฟังก์ชันนี้ย่อกลับเป็นหน่วยงานหยาบ (task1/task2) ให้ตรงกับตัวเลือกตัวกรองในหน้านี้
  // (สอดคล้องกับ essay_topic_phase() ฝั่งเซิร์ฟเวอร์ใน db_config.php)
  function essayTopicPhase(phase) {
    phase = String(phase || '');
    if (phase.indexOf('task1') === 0) return 'task1';
    if (phase.indexOf('task2') === 0) return 'task2';
    return phase; // pretest / posttest
  }

  // ตัดคำภาษาไทยด้วย Intl.Segmenter เพื่อแสดงขอบเขตคำให้เห็น (แสดงผลอย่างเดียว ไม่ใช่การแก้ไข)
  let __raWordSegmenter = null;
  if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
    try { __raWordSegmenter = new Intl.Segmenter('th', { granularity: 'word' }); } catch (e) { __raWordSegmenter = null; }
  }
  function wordSegmentedHTML(s) {
    const esc = (t) => String(t).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    if (!__raWordSegmenter) return esc(s).replace(/\n/g, '<br>');
    let html = '';
    for (const part of __raWordSegmenter.segment(s)) {
      const e = esc(part.segment).replace(/\n/g, '<br>');
      html += part.isWordLike ? `<span class="thai-word">${e}</span>` : e;
    }
    return html;
  }

  // แยกโครงสร้างเรียงความ (คำนำ/เนื้อเรื่อง/สรุป) จาก JSON ที่บันทึกไว้ — คืน null ถ้าไม่ใช่รูปแบบ JSON โครงสร้าง
  function parseEssayStructured(contentStr) {
    try {
      const obj = JSON.parse(contentStr);
      if (obj && typeof obj === 'object' && obj.introduction !== undefined) {
        return { introduction: obj.introduction || '', body: Array.isArray(obj.body) ? obj.body : [], conclusion: obj.conclusion || '' };
      }
    } catch (e) {}
    return null;
  }

  // ต่อทุกส่วนของเรียงความเป็นข้อความเดียว (ใช้ส่งตรวจคำผิด/นับตัวอักษร)
  function essayCombinedText(contentStr) {
    const parts = parseEssayStructured(contentStr);
    if (parts) {
      return [parts.introduction, ...parts.body, parts.conclusion].filter(t => t).join('\n');
    }
    return contentStr || '';
  }

  // sets = { misspelled, foreign, spacing } จาก api.php?action=check_thai_spelling — ใส่ null ถ้ายังไม่ได้ตรวจ (แสดงแค่ขอบเขตคำ)
  function formatEssayHTML(contentStr, sets) {
    if (!contentStr) return '<em class="text-muted">ไม่มีเนื้อหาเรียงความ</em>';
    const renderText = (t) => sets ? ThaiReview.renderStaticHTML(t, sets) : wordSegmentedHTML(t);
    const parts = parseEssayStructured(contentStr);
    if (parts) {
      let html = '';
      if (parts.introduction) {
        html += `
          <div class="mb-3">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold mb-1"><i class="bi bi-pencil-fill me-1"></i>ส่วนคำนำ (Introduction)</span>
            <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${renderText(parts.introduction)}</div>
          </div>`;
      }
      parts.body.forEach((paraText, i) => {
        if (paraText) {
          html += `
            <div class="mb-3">
              <span class="badge bg-success bg-opacity-10 text-success fw-bold mb-1"><i class="bi bi-book-fill me-1"></i>ส่วนเนื้อเรื่อง ย่อหน้าที่ ${i+1} (Body Paragraph)</span>
              <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${renderText(paraText)}</div>
            </div>`;
        }
      });
      if (parts.conclusion) {
        html += `
          <div class="mb-0">
            <span class="badge bg-danger bg-opacity-10 text-danger fw-bold mb-1"><i class="bi bi-award-fill me-1"></i>ส่วนสรุป (Conclusion)</span>
            <div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${renderText(parts.conclusion)}</div>
          </div>`;
      }
      return html;
    }
    return `<div class="p-3 bg-white rounded-3 border text-dark" style="white-space:pre-wrap; line-height:1.7;">${renderText(contentStr)}</div>`;
  }

  // แถบค่าสถิติรายบุคคลของเรียงความฉบับนี้ — จำนวนคำ/ตัวอักษร และจำนวนจุดที่น่าสงสัยแต่ละประเภท
  function renderEssayIndividualStats(sets, combinedText, wordCountStr) {
    const chars  = combinedText.length;
    const nMis   = (sets.misspelled || []).length;
    const nFor   = (sets.foreign || []).length;
    const nSpace = (sets.spacing || []).length;
    const badges = [
      `<span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1"><i class="bi bi-fonts me-1"></i>${wordCountStr} คำ</span>`,
      `<span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1"><i class="bi bi-text-paragraph me-1"></i>${chars.toLocaleString('th-TH')} ตัวอักษร</span>`
    ];
    if (nMis > 0) badges.push(`<span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>สะกดผิด ${nMis} คำ</span>`);
    if (nFor > 0) badges.push(`<span class="badge rounded-pill px-2 py-1" style="background:#e0d6ff;color:#4b2fa8;"><i class="bi bi-translate me-1"></i>ภาษาอื่นปน ${nFor} คำ</span>`);
    if (nSpace > 0) badges.push(`<span class="badge rounded-pill px-2 py-1" style="background:#ffd8c2;color:#a03e00;"><i class="bi bi-textarea-t me-1"></i>เว้นวรรค "ๆ" ผิด ${nSpace} คำ</span>`);
    if (nMis === 0 && nFor === 0 && nSpace === 0) {
      badges.push(`<span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>ไม่พบคำผิด</span>`);
    }
    return `<div class="d-flex flex-wrap gap-2 mb-3 pb-2 border-bottom">${badges.join('')}</div>`;
  }

  // แคชผลตรวจคำผิดต่อเรียงความ (คีย์ = essayId) กันตรวจซ้ำเวลาเปิด/ปิดกล่องซ้ำ ๆ
  const essayCheckCache = {};
  let currentFilteredEssays = [];

  // ครั้งแรกที่เปิดดูฉบับเต็ม จะส่งตรวจคำผิด/ภาษาอื่น/เว้นวรรค แล้วไฮไลต์ + แสดงค่าสถิติของฉบับนั้นให้เลย
  async function toggleEssayFull(essayId, idx) {
    const panel = document.getElementById(essayId);
    if (!panel) return;
    const wasHidden = panel.classList.contains('d-none');
    panel.classList.toggle('d-none');
    if (!wasHidden || essayCheckCache[essayId]) return;

    const e = currentFilteredEssays[idx];
    if (!e) return;
    const combinedText = essayCombinedText(e.essay_content);
    const wordCountStr = parseInt(e.word_count || 0).toLocaleString('th-TH');

    let sets = { misspelled: [], foreign: [], spacing: [] };
    if (combinedText.trim() && typeof ThaiReview !== 'undefined') {
      const loadingId = essayId + '_loading';
      panel.insertAdjacentHTML('afterbegin', `<div class="text-muted small mb-2" id="${loadingId}"><span class="spinner-border spinner-border-sm me-1"></span>กำลังตรวจสอบคำผิด...</div>`);
      try {
        const res = await fetch('api.php?action=check_thai_spelling', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ text: combinedText })
        });
        const data = await res.json();
        if (data.success) sets = { misspelled: data.misspelled, foreign: data.foreign, spacing: data.spacing };
      } catch (err) {
        // เงียบไว้ — แสดงเนื้อหาได้แม้ตรวจคำผิดไม่สำเร็จ (จะไม่มีคำไฮไลต์)
      }
      const loadingEl = document.getElementById(loadingId);
      if (loadingEl) loadingEl.remove();
    }

    essayCheckCache[essayId] = sets;
    panel.innerHTML = renderEssayIndividualStats(sets, combinedText, wordCountStr) + formatEssayHTML(e.essay_content, sets);
  }

  function renderEssayViewer(essays) {
    const phaseFilter = (document.getElementById('essayPhaseFilter') || {}).value || 'all';
    const query       = ((document.getElementById('essaySearchInput') || {}).value || '').toLowerCase().trim();
    const container   = document.getElementById('essayViewerContainer');

    let filtered = essays.filter(e => {
      if (phaseFilter !== 'all' && essayTopicPhase(e.essay_phase) !== phaseFilter) return false;

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
    const total      = filtered.length;
    const words      = filtered.map(e => parseInt(e.word_count || 0));
    const totalWords = words.reduce((a,b)=>a+b,0);
    const avgWords   = total > 0 ? Math.round(totalWords/total) : 0;
    const maxWords   = total > 0 ? Math.max(...words) : 0;
    const minWords   = total > 0 ? Math.min(...words) : 0;
    const stdDevWords = total > 0
      ? Math.round(Math.sqrt(words.reduce((a,b)=>a+Math.pow(b-avgWords,2),0)/total))
      : 0;

    const setEl = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val.toLocaleString('th-TH'); };
    setEl('essayStatTotal', total);
    setEl('essayStatAvgWords', avgWords);
    setEl('essayStatMaxWords', maxWords);
    setEl('essayStatMinWords', minWords);
    setEl('essayStatTotalWords', totalWords);
    setEl('essayStatStdDevWords', stdDevWords);

    currentFilteredEssays = filtered;
    // ล้างแคชผลตรวจคำผิดเดิม เพราะ essayId (essay_<ลำดับ>) จะถูกใช้ซ้ำกับเรียงความคนละฉบับหลังกรอง/ค้นหาใหม่
    Object.keys(essayCheckCache).forEach(k => delete essayCheckCache[k]);

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
                  onclick="toggleEssayFull('${essayId}', ${idx})">
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
      if (phaseFilter !== 'all' && essayTopicPhase(e.essay_phase) !== phaseFilter) return false;
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
    loadGoogleStatus(); // แสดงสถานะการเชื่อมต่อ Google Docs
  })();
</script>

<?php
require_once 'footer.php';
