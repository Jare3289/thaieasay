<?php
$page_title = 'แบบประเมิน - ระบบประเมินเรียงความอัจฉริยะ';
require_once 'auth_helper.php';

// ดึงโหมดที่ระบุมา
$mode_param = isset($_GET['mode']) ? $_GET['mode'] : '';
$currentMode = '';

if ($mode_param === 'self') {
    require_login('student');
    $currentMode = 'ตนเองประเมิน';
} else if ($mode_param === 'peer') {
    require_login('student');
    $currentMode = 'เพื่อนประเมิน';
} else if ($mode_param === 'teacher') {
    require_login('teacher');
    $currentMode = 'ครูประเมิน';
} else if ($mode_param === 'expert') {
    require_login('expert');
    $currentMode = 'ผู้เชี่ยวชาญประเมิน';
} else {
    header('Location: index.php');
    exit;
}

require_once 'header.php';
?>

<div id="view-evaluation" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
    <div class="p-4 position-relative text-white" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <h4 class="fw-bold mb-1">กรอกแบบประเมินความสามารถงานเขียน</h4>
        <span id="roleBadge" class="badge bg-white text-dark px-3 py-2 fs-6 fw-bold"><?php echo $currentMode; ?></span>
      </div>
      <p class="text-white-50 mb-0 small font-light">โปรดรีวิวย่อหน้าผลงานแล้วทำเครื่องหมายเลือกเกณฑ์คุณภาพที่ตรงตามจริง (ซ่อนตัวเลขคะแนน/ตัวคูณลดอคติ)</p>
    </div>

    <form id="evalForm" class="p-4">
      <!-- ข้อมูลนักเรียนเป้าหมายที่ได้รับการประเมิน -->
      <div class="card border-0 rounded-3 p-4 mb-4" style="background-color: var(--light-blue);">
        <div class="row align-items-end">
          <div class="col-md-5 col-sm-12">
            <label for="targetStudentSelect" class="form-label fw-bold text-secondary small text-uppercase tracking-wider">เลือกนักเรียนที่เป็นเป้าหมายผู้ถูกประเมิน <span class="text-danger">*</span></label>
            <select id="targetStudentSelect" required class="form-select form-select-lg border-2 rounded-3 cursor-pointer fw-semibold text-dark">
              <option value="" disabled selected>-- เลือกรายชื่อนักเรียน --</option>
            </select>
            <?php if ($mode_param === 'self'): ?>
            <p id="selfEvalNotice" class="mt-2 text-primary small fw-bold mb-0">
              <i class="bi bi-info-circle-fill"></i> ระบบจำกัดการเลือกเฉพาะข้อมูลของคุณเนื่องจากกำลังทำโหมด "ตนเองประเมิน"
            </p>
            <?php endif; ?>
          </div>
          <div class="col-md-4 col-sm-12 mt-3 mt-md-0 text-start">
            <label for="testPhaseSelect" class="form-label fw-bold text-secondary small text-uppercase tracking-wider">รอบการประเมิน <span class="text-danger">*</span></label>
            <select id="testPhaseSelect" required class="form-select form-select-lg border-2 rounded-3 cursor-pointer fw-semibold text-dark">
              <?php if ($sessionUser['role'] === 'expert'): ?>
              <option value="task" selected>ภารงานในหน่วยการเรียน (Task)</option>
              <?php else: ?>
              <option value="pretest">ก่อนเรียน (Pretest - T1)</option>
              <option value="task">ภารงานในหน่วยการเรียน (Task)</option>
              <option value="posttest" selected>หลังเรียน (Posttest - T2)</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-3 col-sm-12 text-md-end mt-3 mt-md-0">
            <span id="loadOldDataStatus" class="badge fs-7 p-2.5 rounded-pill d-none"></span>
          </div>
        </div>
      </div>

      <!-- คำชี้แจงและหมายเหตุประกอบการใช้เกณฑ์ -->
      <div class="card border-0 rounded-3 p-4 mb-4" style="background-color: #fafaf9; border: 1px solid #e7e5e4 !important;">
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-text-fill text-primary"></i> คำชี้แจงการประเมิน</h6>
        <p class="small text-secondary mb-3" style="line-height: 1.6;">
          ให้ผู้ประเมินอ่านเกณฑ์แต่ละหัวข้อ แล้วเลือกระดับคุณภาพที่ตรงกับผลงานมากที่สุด คะแนนของแต่ละรายการคำนวณจากระดับคะแนนที่กำกับไว้ในหัวตาราง คูณด้วยตัวคูณของรายการนั้น เช่น รายการที่ได้ระดับดี ซึ่งมีระดับคะแนนเท่ากับ 3 และมีตัวคูณ 3 จะได้คะแนนเท่ากับ 9 คะแนน
        </p>
        
        <div class="accordion" id="rubricNotesAccordion">
          <div class="accordion-item border-0 bg-transparent">
            <h2 class="accordion-header" id="headingNotes">
              <button class="accordion-button collapsed p-0 bg-transparent text-primary fw-bold small shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNotes" aria-expanded="false" aria-controls="collapseNotes" style="font-size: 0.85rem; border: none;">
                <i class="bi bi-info-circle-fill me-1"></i> ดูหมายเหตุประกอบการใช้เกณฑ์ประเมินเพิ่มเติม (6 ข้อสำคัญ)
              </button>
            </h2>
            <div id="collapseNotes" class="accordion-collapse collapse" aria-labelledby="headingNotes" data-bs-parent="#rubricNotesAccordion">
              <div class="accordion-body p-0 pt-3 text-secondary small" style="line-height: 1.6; font-size: 0.82rem;">
                <ol class="ps-3 mb-0">
                  <li class="mb-2"><strong>การคำนวณคะแนน</strong>: ทุกรายการใช้ระดับคุณภาพฐานเดียวกัน คือ ดีมาก = 4, ดี = 3, ปานกลาง = 2, พอใช้ = 1 และปรับปรุง = 0 น้ำหนักความสำคัญที่ต่างกันของแต่ละรายการสะท้อนผ่านตัวคูณในคอลัมน์ท้ายตาราง คะแนนเต็มรายข้อและสัดส่วนคะแนนรายด้านคงเดิมทุกประการ</li>
                  <li class="mb-2"><strong>ความหมายของคำว่า "ประเด็น"</strong>: ในรายการที่ 1.1 คำว่า ประเด็น หมายถึง สาระสำคัญหนึ่งเรื่องที่ผู้เขียนนำเสนอ มิได้ผูกกับจำนวนย่อหน้า เนื่องจากส่วนคำนำและส่วนสรุปมีเพียงส่วนละหนึ่งย่อหน้า ขณะที่ส่วนเนื้อเรื่องมีได้หลายย่อหน้า การนับประเด็นจึงเหมาะสมกว่าการนับย่อหน้า</li>
                  <li class="mb-2"><strong>การนับข้อบกพร่องซ้ำ</strong>: ข้อบกพร่องประเภทเดียวกันที่เกิดกับคำเดียวกันซ้ำตลอดทั้งชิ้นงาน ให้นับเป็น 1 แห่ง เพื่อมิให้ผู้เรียนถูกลดคะแนนซ้ำจากข้อผิดพลาดเดียวกัน</li>
                  <li class="mb-2"><strong>องค์ประกอบของเรียงความ</strong>: รายการที่ 2.1 พิจารณาองค์ประกอบ 3 ส่วน ได้แก่ คำนำ เนื้อเรื่อง และสรุป ตามที่ผู้วิจัยกำหนดไว้ในบทที่ 2 มิได้นับชื่อเรื่องเป็นองค์ประกอบ เนื่องจากชื่อเรื่องถูกกำหนดไว้ในภาระงานแล้ว จึงมิใช่ผลผลิตจากความสามารถในการเขียนของผู้เรียน</li>
                  <li class="mb-2"><strong>นิยามศัพท์เฉพาะ</strong>: คำว่า ความเหมาะสมของเนื้อหา และ ระดับภาษาที่เหมาะสม ผู้วิจัยกำหนดขอบเขตไว้ในนิยามศัพท์เฉพาะ บทที่ 3</li>
                  <li class="mb-0"><strong>การบันทึกข้อมูลเชิงคุณภาพ</strong>: ร่องรอยการเขียนที่โดดเด่นหรือข้อบกพร่องเฉพาะที่มิได้ระบุไว้ในคำอธิบายคุณภาพ ให้ผู้ประเมินบันทึกแยกไว้ต่างหาก เพื่อนำไปใช้ประกอบการอภิอภิปรายผลการวิจัย</li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- แถบความคืบหน้าการกรอกรูบริก -->
      <div class="card border-0 shadow-sm bg-white p-3 mb-4" id="progressContainer">
        <div class="row align-items-center">
          <div class="col-md-8 col-12">
            <div class="progress rounded-pill" style="height: 12px;">
              <div id="evaluationProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
          <div class="col-md-4 col-12 text-md-end mt-2 mt-md-0">
            <span id="progressText" class="fw-bold small text-secondary">ตอบแล้ว 0 จาก 11 ข้อ</span>
          </div>
        </div>
      </div>

      <!-- จุดแสดงผลข้อเกณฑ์ประเมินที่สร้างจาก JS -->
      <div id="rubricContainer" class="space-y-4">
        <div class="text-center text-muted py-5 fw-bold">กรุณาเลือกนักเรียนผู้รับการประเมินด้านบนก่อนเพื่อโหลดเกณฑ์คะแนน</div>
      </div>

      <?php if ($mode_param === 'peer'): ?>
      <!-- ส่วนข้อเสนอแนะเพื่อการพัฒนาผลงาน (Feedback for Peer Development) -->
      <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #fffbeb 0%, #fef9c3 100%); border-left: 4px solid #f59e0b !important;" id="peerFeedbackSection">
        <h5 class="fw-bold text-dark mb-1"><i class="bi bi-chat-right-quote-fill text-warning"></i> ข้อเสนอแนะเพื่อการพัฒนาผลงานเพื่อน (Feedback for Peer Development)</h5>
        <p class="text-muted small mb-4">คำชี้แจง: เขียนคำแนะนำเชิงสร้างสรรค์ให้ครบถ้วนที่ 3 หัวข้อ เพื่อส่งเสริมมิตรภาพและการรับบทผลตอบรับอย่างสร้างสรรค์</p>
        <div class="row g-4">
          <div class="col-md-4 col-sm-12">
            <div class="card border-0 rounded-3 p-3 bg-white shadow-sm h-100" style="border-top: 3px solid #10b981 !important;">
              <label class="form-label fw-bold text-success-emphasis small mb-2"><i class="bi bi-star-fill text-success"></i> จุดแข็งและด้านที่ดีของเรียงความ</label>
              <p class="small text-muted mb-2" style="font-size:0.78rem;">ระบุสิ่งที่น่าประทับใจและคุณค่าที่โดดเด่นของงานเขียนชิ้นนี้</p>
              <textarea id="peerStrengthField" name="peer_strength" class="form-control form-control-sm flex-grow-1" rows="5" placeholder="ระบุข้อดี จุดเด่น สิ่งที่น่ายกย่อง..."></textarea>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="card border-0 rounded-3 p-3 bg-white shadow-sm h-100" style="border-top: 3px solid #f59e0b !important;">
              <label class="form-label fw-bold text-warning-emphasis small mb-2"><i class="bi bi-arrow-up-circle-fill text-warning"></i> จุดที่ควรปรับปรุงและข้อเสนอแนะ</label>
              <p class="small text-muted mb-2" style="font-size:0.78rem;">ระบุจุดบกพร่องที่พบและเสนอวิธีการแก้ไขอย่างตรงไปตรงมา</p>
              <textarea id="peerImprovementField" name="peer_improvement" class="form-control form-control-sm flex-grow-1" rows="5" placeholder="ระบุจุดบกพร่อง วิธีแก้..."></textarea>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="card border-0 rounded-3 p-3 bg-white shadow-sm h-100" style="border-top: 3px solid #8b5cf6 !important;">
              <label class="form-label fw-bold" style="color:#6d28d9; font-size:0.83rem;"><i class="bi bi-emoji-smile-fill" style="color:#8b5cf6"></i> ข้อความให้กำลังใจเพื่อน</label>
              <p class="small text-muted mb-2" style="font-size:0.78rem;">เขียนข้อความให้กำลังใจและส่งเสริมเพื่อนให้อยากพัฒนาต่อไป</p>
              <textarea id="peerEncouragementField" name="peer_encouragement" class="form-control form-control-sm flex-grow-1" rows="5" placeholder="เขียนให้กำลังใจ..."></textarea>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="mt-4 pt-4 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <span class="text-muted small">กรุณาตรวจสอบรายละเอียดเกณฑ์ประเมินรูบริกก่อนคลิกส่งข้อมูล</span>
        <div class="d-flex align-items-center gap-3">
          <div id="formErrorMsg" class="d-none text-danger fw-bold small">⚠️ กรุณากรอกคะแนนประเมินให้ครบทั้งหมดทุกหัวข้อ</div>
          <button type="submit" id="submitBtn" disabled class="btn btn-success btn-lg px-5 py-3 rounded-3 fw-bold fs-6 shadow">
            บันทึกผลการประเมิน
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  let currentMode = "<?php echo $currentMode; ?>";
  let modeParam = "<?php echo $mode_param; ?>";
  let studentDB = {};

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

  // โหลดรายชื่อนักเรียนจาก API
  async function loadStudents() {
    try {
      const response = await fetch(`api.php?action=get_students_list&_t=${new Date().getTime()}`);
      const res = await response.json();
      if (res.success) {
        studentDB = res.students;
      } else {
        console.error("Failed to load students:", res.error);
      }
    } catch (err) {
      console.error("Error loading students list:", err);
    }
  }

  // เติมรายชื่อนักเรียนลงใน Dropdown
  function populateStudentSelect() {
    const select = document.getElementById('targetStudentSelect');
    select.innerHTML = '<option value="" disabled selected>-- เลือกรายชื่อนักเรียน --</option>';
    
    const sortedKeys = Object.keys(studentDB).sort();
    sortedKeys.forEach(id => {
      const option = document.createElement('option');
      option.value = id;
      option.textContent = `${id} - ${studentDB[id]}`;
      select.appendChild(option);
    });
  }

  // สร้างคาร์ดรูบริก
  function buildRubric() {
    const container = document.getElementById('rubricContainer');
    container.innerHTML = ''; 
    
    rubricData.forEach((section) => {
      const sectionDiv = document.createElement('div');
      sectionDiv.className = "card border-0 shadow-sm mb-4 bg-white";
      
      const headerDiv = document.createElement('div');
      headerDiv.className = "card-header bg-primary text-white py-3 fw-bold";
      headerDiv.textContent = section.section;
      sectionDiv.appendChild(headerDiv);

      const cardBody = document.createElement('div');
      cardBody.className = "card-body p-4";

      section.items.forEach((item) => {
        const itemDiv = document.createElement('div');
        itemDiv.className = "mb-4 border-bottom pb-4 text-start";
        
        let levelsHTML = '';
        item.levels.forEach(level => {
          levelsHTML += `
            <div class="col">
              <input type="radio" name="item_${item.id}" value="${level.score}" data-multiplier="${item.multiplier}" data-raw="${level.score}" id="opt_${item.id}_${level.score}" class="score-radio" required onchange="calculateRealTimeFormScore()">
              <label for="opt_${item.id}_${level.score}" class="rubric-card w-100 text-start">
                <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                  <span class="fw-bold fs-6 text-dark">${level.label}</span>
                  <div class="check-circle"><i class="bi bi-check-lg"></i></div>
                </div>
                <p class="text-secondary mb-0" style="font-size: 13px; line-height: 1.5; font-weight: 400;">${level.desc}</p>
              </label>
            </div>
          `;
        });

        itemDiv.innerHTML = `
          <div class="mb-3">
            <h5 class="fw-bold mb-0 text-slate-800">${item.name}</h5>
          </div>
          <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">${levelsHTML}</div>
        `;
        cardBody.appendChild(itemDiv);
      });
      sectionDiv.appendChild(cardBody);
      container.appendChild(sectionDiv);
    });
  }

  // อัปเดตแถบความคืบหน้า (Progress Bar)
  function calculateRealTimeFormScore() {
    const totalInputs = document.querySelectorAll('input[type="radio"].score-radio:checked');
    const percent = Math.round((totalInputs.length / 11) * 100);
    
    const progress = document.getElementById('evaluationProgress');
    const progressText = document.getElementById('progressText');
    
    if (progress) {
      progress.style.width = `${percent}%`;
      progress.setAttribute('aria-valuenow', percent);
    }
    if (progressText) {
      progressText.textContent = `ตอบแล้ว ${totalInputs.length} จาก 11 ข้อ`;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    if (totalInputs.length === 11) {
      submitBtn.disabled = false;
      if(progress) {
        progress.classList.remove('bg-info');
        progress.classList.add('bg-success');
      }
    } else {
      submitBtn.disabled = true;
      if(progress) {
        progress.classList.remove('bg-success');
        progress.classList.add('bg-info');
      }
    }
  }

  // ดึงข้อมูลการประเมินเก่ามาใส่ในฟอร์ม
  async function checkExistingEvaluation(studentId) {
    const rubricCont = document.getElementById('rubricContainer');
    const statusBadge = document.getElementById('loadOldDataStatus');
    const progressCont = document.getElementById('progressContainer');
    
    rubricCont.classList.remove('opacity-60', 'pointer-events-none');
    document.getElementById('evalForm').reset();
    document.getElementById('targetStudentSelect').value = studentId;
    if(progressCont) progressCont.classList.remove('d-none');
    calculateRealTimeFormScore(); // รีเซ็ต

    statusBadge.textContent = "กำลังค้นหาคะแนนเดิม...";
    statusBadge.className = "badge bg-info text-white fs-8 px-3 py-2 rounded-pill";
    statusBadge.classList.remove('d-none');

    const testPhase = document.getElementById('testPhaseSelect') ? document.getElementById('testPhaseSelect').value : 'posttest';

    try {
      const response = await fetch('api.php?action=get_single_evaluation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          studentId: studentId,
          evaluatorType: currentMode,
          evaluatorName: currentUser.name,
          testPhase: testPhase
        })
      });
      const res = await response.json();
      
      if(res.success && res.found) {
        statusBadge.textContent = "✓ พบข้อมูลประเมินเดิมแล้ว (โหมดแก้ไข)";
        statusBadge.className = "badge bg-success text-white fs-8 px-3 py-2 rounded-pill";
        
        for (const [itemId, rawValue] of Object.entries(res.scores)) {
          const inputs = document.querySelectorAll(`input[name="item_${itemId}"]`);
          if(inputs.length > 0) {
             const multiplier = parseFloat(inputs[0].dataset.multiplier);
             const scoreLevel = Math.round(parseFloat(rawValue) / multiplier);
             const targetRadio = document.getElementById(`opt_${itemId}_${scoreLevel}`);
             if(targetRadio) targetRadio.checked = true;
          }
        }
        calculateRealTimeFormScore();
      } else {
        statusBadge.textContent = "✏️ การประเมินผลงานรายการใหม่";
        statusBadge.className = "badge bg-secondary text-white fs-8 px-3 py-2 rounded-pill";
      }
      // เติมคำแนะนำเชิงคุณภาพจากเพื่อน (ถ้ามี)
      if (modeParam === 'peer' && res.found && res.peerFeedback) {
        const f = res.peerFeedback;
        const strEl = document.getElementById('peerStrengthField');
        const impEl = document.getElementById('peerImprovementField');
        const encEl = document.getElementById('peerEncouragementField');
        if (strEl) strEl.value = f.strength || '';
        if (impEl) impEl.value = f.improvement || '';
        if (encEl) encEl.value = f.encouragement || '';
      }
    } catch (err) {
      console.error(err);
      statusBadge.textContent = "⚠️ ไม่สามารถตรวจสอบประวัติได้";
      statusBadge.className = "badge bg-danger text-white fs-8 px-3 py-2 rounded-pill";
    }
  }

  // คำนวณหาผลรวมของคะแนนตามเกณฑ์สูตรคำนวณ
  function calculateHiddenScore() {
    const radios = document.querySelectorAll('input[type="radio"].score-radio:checked');
    let total = 0;
    radios.forEach(radio => {
      total += (parseFloat(radio.value) * parseFloat(radio.dataset.multiplier));
    });

    let levelText = '';
    if(total >= 49) levelText = 'ดีมาก';
    else if(total >= 37) levelText = 'ดี';
    else if(total >= 25) levelText = 'ปานกลาง';
    else if(total >= 13) levelText = 'พอใช้';
    else levelText = 'ต้องปรับปรุง';

    return { total, levelText };
  }

  // การเปลี่ยนตัวเลือกผู้ถูกประเมิน หรือ รอบการประเมิน
  function handleTargetOrPhaseChange() {
    const studentSelect = document.getElementById('targetStudentSelect');
    const id = studentSelect.value;
    if (id) {
      checkExistingEvaluation(id);
    }
  }
  document.getElementById('targetStudentSelect').addEventListener('change', handleTargetOrPhaseChange);
  if (document.getElementById('testPhaseSelect')) {
    document.getElementById('testPhaseSelect').addEventListener('change', handleTargetOrPhaseChange);
  }

  // บันทึกฟอร์ม
  document.getElementById('evalForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const tSelect = document.getElementById('targetStudentSelect');
    const studentId = tSelect.value;
    if(!studentId) {
      showToast("กรุณาเลือกรายชื่อนักเรียนก่อนส่งผลคะแนน", "error");
      return;
    }
    const studentName = studentDB[studentId];

    const radios = document.querySelectorAll('input[type="radio"].score-radio:checked');
    if (radios.length < 11) {
      document.getElementById('formErrorMsg').classList.remove('d-none');
      showToast("กรุณากรอกคะแนนประเมินให้ครบถ้วนทุกข้อเกณฑ์", "error");
      return;
    }
    
    const btn = document.getElementById('submitBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึกคะแนน...`;
    btn.disabled = true;

    const calcResult = calculateHiddenScore();
    const testPhase = document.getElementById('testPhaseSelect') ? document.getElementById('testPhaseSelect').value : 'posttest';
    const payload = {
      studentId: studentId,
      studentName: studentName,
      evaluatorType: currentMode,
      evaluatorName: currentUser.name,
      testPhase: testPhase,
      scores: {},
      totalScore: calcResult.total,
      qualityLevel: calcResult.levelText
    };

    document.querySelectorAll('input[type="radio"].score-radio:checked').forEach(radio => {
      const id = radio.name.replace('item_', '');
      payload.scores[id] = parseFloat(radio.value) * parseFloat(radio.dataset.multiplier);
    });

    // เพิ่มคำแนะนำเชิงคุณภาพจากเพื่อน ถ้าอยู่ในโหมด peer
    if (modeParam === 'peer') {
      payload.peerStrength     = (document.getElementById('peerStrengthField')     || {}).value || '';
      payload.peerImprovement  = (document.getElementById('peerImprovementField')  || {}).value || '';
      payload.peerEncouragement= (document.getElementById('peerEncouragementField')|| {}).value || '';
    }

    try {
      const response = await fetch('api.php?action=save_evaluation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ data: payload })
      });
      const res = await response.json();
      
      if(res.success) {
        window.location.href = 'success.php';
      } else {
        showToast("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " + res.error, "error");
      }
    } catch (err) {
      showToast("ไม่สามารถเชื่อมต่อฐานข้อมูลปลายทางได้", "error");
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  // ทำการทำงานเริ่มต้นแบบเงียบ
  (async function init() {
    await loadStudents();
    populateStudentSelect();
    buildRubric();

    const tSelect = document.getElementById('targetStudentSelect');
    const rubricCont = document.getElementById('rubricContainer');
    const progressCont = document.getElementById('progressContainer');

    if (modeParam === 'self') {
      tSelect.value = currentUser.id;
      tSelect.disabled = true;
      checkExistingEvaluation(currentUser.id);
    } else {
      tSelect.value = "";
      tSelect.disabled = false;
      rubricCont.classList.add('opacity-60', 'pointer-events-none');
      if (progressCont) progressCont.classList.add('d-none');
    }
  })();
</script>

<?php
require_once 'footer.php';
?>
