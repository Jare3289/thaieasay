<?php
/**
 * ai_feedback.php — หน้า "ผู้ช่วย AI ตรวจเรียงความ"
 * ---------------------------------------------------------------------------
 * ใช้ได้ทั้งนักเรียน ครู และผู้เชี่ยวชาญ (เห็นข้อมูลต่างกันตามบทบาท)
 *   - นักเรียน  : กดให้ AI ตรวจเรียงความของตนเอง และดูข้อเสนอแนะย้อนหลังทุกรอบ
 *   - ครู       : ตรวจแทนนักเรียนคนใดก็ได้ ดูภาพรวมทั้งชั้น ลบผลที่ไม่เหมาะสม และตั้งค่า AI
 *   - ผู้เชี่ยวชาญ: ดูผลตรวจของนักเรียนได้อย่างเดียว (ไม่สั่งตรวจ ไม่ตั้งค่า)
 */
$page_title = 'ผู้ช่วย AI ตรวจเรียงความ - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';
require_login();
require_once 'ai_config.php';
require_once 'header.php';

$aiRole      = $sessionUser['role'];
$aiIsTeacher = ($aiRole === 'teacher');
$aiIsStudent = ($aiRole === 'student');
$aiPhases    = ai_all_phases();
?>

<div id="view-ai-feedback" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <!-- หัวเรื่อง -->
  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #0d7377 100%);">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-robot me-2"></i>ผู้ช่วย AI ตรวจเรียงความ</h4>
          <p class="text-white-50 mb-0 small">
            ให้ AI อ่านเรียงความแล้วชี้จุดแข็ง จุดที่ควรปรับปรุง และแนวทางแก้ไขตามเกณฑ์การประเมินของคุณครู
          </p>
        </div>
        <span class="badge bg-white text-dark px-3 py-2 fs-7 fw-bold">
          <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($sessionUser['name']); ?>
        </span>
      </div>
    </div>
    <div class="bg-light border-top px-4 py-2 small text-muted">
      <i class="bi bi-info-circle me-1"></i>
      ข้อเสนอแนะและคะแนนจาก AI เป็นเพียง<strong>แนวทางเพื่อพัฒนางานเขียน</strong>
      ไม่ถูกนำไปรวมกับคะแนนจริงของครู เพื่อน หรือการประเมินตนเองในระบบประเมิน —
      ข้อที่ AI ตรวจจากไฟล์พิมพ์แทนไม่ได้ (ความเรียบร้อย/ลายมือ) คุณครูเลือกระดับคะแนนเองได้ที่
      <strong>ท้ายตาราง &quot;คะแนนรายเกณฑ์ (ประมาณการ)&quot;</strong> ของผลตรวจแต่ละฉบับ
      เพื่อให้เห็นคะแนนรวม<strong>เต็ม 60 ตามเกณฑ์จริง</strong> —
      หากนักเรียนแก้ไขต้นฉบับหลังตรวจ ระบบจะจัดเข้า<strong>คิวรอตรวจใหม่</strong>ให้อัตโนมัติ
    </div>
  </div>

  <!-- แถบสถานะระบบ AI -->
  <div id="aiStatusBar" class="alert border-0 rounded-3 small d-none" role="alert"></div>

<?php if (!$aiIsStudent): ?>
  <!-- คิวรอตรวจใหม่: นักเรียนแก้ไขต้นฉบับหลังจาก AI ตรวจไปแล้ว (ซ่อนไว้เมื่อคิวว่าง) -->
  <div id="aiRecheckCard" class="card border-0 shadow-sm rounded-4 mb-4 d-none" style="border-top:4px solid #f59e0b !important;">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h6 class="fw-bold text-dark mb-0">
            <i class="bi bi-arrow-repeat text-warning me-2"></i>คิวรอตรวจใหม่
            <span id="aiRecheckCount" class="badge bg-warning text-dark ms-1">0</span>
          </h6>
          <div class="text-muted small mt-1">
            เรียงความที่นักเรียนแก้ไขต้นฉบับหลังจาก AI ตรวจไปแล้ว — ผลตรวจเดิมยังเป็นของฉบับก่อนแก้
          </div>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="loadRecheckQueue()">
            <i class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
          </button>
<?php if ($aiIsTeacher): ?>
          <button id="rcStartBtn" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold" onclick="startRecheckQueue()">
            <i class="bi bi-stars me-1"></i>ให้ AI ตรวจใหม่ทั้งคิว
          </button>
          <button id="rcStopBtn" class="btn btn-outline-danger btn-sm rounded-pill px-3 d-none" onclick="stopBatchReview()">
            <i class="bi bi-stop-fill me-1"></i>หยุด
          </button>
<?php endif; ?>
        </div>
      </div>
      <div id="rcProgressWrap" class="mt-3 d-none">
        <div class="d-flex justify-content-between small fw-bold mb-1">
          <span id="rcProgressLabel">กำลังตรวจ...</span>
          <span id="rcProgressCount">0 / 0</span>
        </div>
        <div class="progress" style="height:10px;">
          <div id="rcProgressBar" class="progress-bar" role="progressbar"
               style="width:0%; background:linear-gradient(90deg,#f59e0b,#d97706);"></div>
        </div>
      </div>
      <div id="rcLog" class="mt-3 d-none border rounded-3" style="max-height:240px; overflow:auto;"></div>
    </div>
    <div class="card-body p-0">
      <div id="aiRecheckList" style="max-height:300px; overflow:auto;"></div>
    </div>
  </div>
<?php endif; ?>

<?php if (!$aiIsStudent): ?>
  <!-- แท็บหลัก — แยก "รายบุคคล" ออกจาก "ภาพรวมทั้งชั้น" ให้แต่ละหน้าเหลือเฉพาะสิ่งที่ต้องดูจริง -->
  <ul class="nav nav-pills ai-main-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-person" type="button" role="tab">
        <i class="bi bi-person-lines-fill me-1"></i>ผลตรวจรายบุคคล
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-class" type="button" role="tab">
        <i class="bi bi-people-fill me-1"></i>ภาพรวมผลตรวจ AI ทั้งชั้น
      </button>
    </li>
  </ul>
<?php endif; ?>

<div class="tab-content">
  <!-- ======================= แท็บที่ 1: ผลตรวจรายบุคคล ======================= -->
  <div class="tab-pane fade show active" id="tab-person" role="tabpanel">

<?php if (!$aiIsStudent): ?>
  <!-- เลือกนักเรียนที่จะดูผล (ครู/ผู้เชี่ยวชาญ) — รอบงานไม่ต้องเลือก เพราะขึ้นให้ครบทุกฉบับอยู่แล้ว -->
  <div class="card border-0 shadow-sm rounded-4 mb-3" style="border-top:4px solid #0d7377 !important;">
    <div class="card-body p-4">
      <label class="form-label fw-bold small">นักเรียน <span class="text-muted fw-normal">(เฉพาะกลุ่มตัวอย่าง · พิมพ์ค้นหาด้วยรหัส/ชื่อได้)</span></label>
      <select id="aiStudentSelect" class="form-select border-2 rounded-3" onchange="onSelectionChange()"
              data-search-select data-search-placeholder="พิมพ์ค้นหาด้วยรหัส หรือ ชื่อนักเรียน...">
        <option value="">— กำลังโหลดรายชื่อ —</option>
      </select>
      <div id="aiQuotaText" class="text-muted small mt-3"></div>
    </div>
  </div>
<?php else: ?>
  <div id="aiQuotaText" class="text-muted small mb-3"></div>
<?php endif; ?>

  <!-- แถบสรุปย่อของนักเรียนคนที่เลือก — เห็นตัวเลขสำคัญในบรรทัดเดียว
       ส่วนสรุปฉบับเต็มย้ายไปอยู่หน้า ai_student_summary.php แล้ว จะได้ไม่บังผลตรวจรายฉบับในหน้านี้ -->
  <div id="aiPersonStrip" class="mb-3"></div>

  <!-- ผลตรวจทุกรอบงานในหน้าเดียว — คลิกการ์ดเพื่อดูรายละเอียดการให้คะแนนและข้อเสนอแนะ -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <h6 class="fw-bold text-dark mb-0">
      <i class="bi bi-grid-3x2-gap-fill text-primary me-2"></i>ผลตรวจของ AI ทุกรอบงาน
    </h6>
    <span class="text-muted small"><i class="bi bi-hand-index-thumb me-1"></i>คลิกการ์ดคะแนนเพื่อดูรายละเอียด</span>
  </div>
  <div id="aiPhaseCards" class="mb-4">
    <div class="text-center text-muted py-4">
      <i class="bi bi-hourglass-split me-2"></i>กำลังโหลด...
    </div>
  </div>

  <!-- รายละเอียดการให้คะแนนและข้อมูลย้อนกลับของฉบับที่เลือก -->
  <div id="aiFeedbackPanel"></div>

  </div><!-- /tab-person -->

<?php if (!$aiIsStudent): ?>
  <!-- ======================= แท็บที่ 2: ภาพรวมผลตรวจ AI ทั้งชั้น ======================= -->
  <div class="tab-pane fade" id="tab-class" role="tabpanel">

    <!-- ตัวเลขเด่น ๆ ของทั้งชั้น — ตอบคำถามที่ครูถามบ่อยที่สุดก่อน ไม่ต้องไล่อ่านทั้งตาราง -->
    <div id="aiClassHighlights" class="mb-4">
      <div class="text-center text-muted py-4"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลด...</div>
    </div>

    <!-- ค่าเฉลี่ยทั้งชั้นรายรอบงาน (ย้ายมาจากแท็บรายบุคคล) -->
    <div id="aiClassAvgCards" class="mb-4"></div>

    <!-- ภาพรวมการนำเสนอรายรอบงาน — ใช้เมื่อ AI ตรวจครบทั้งรอบแล้ว -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #b45309 !important;">
      <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
          <div>
            <h6 class="fw-bold text-dark mb-0">
              <i class="bi bi-journal-richtext text-warning me-2"></i>ภาพรวมการนำเสนอรายรอบงาน
            </h6>
            <div class="text-muted small mt-1">
              เมื่อ AI ตรวจครบทั้งรอบแล้ว ให้ AI อ่านงานทั้งชั้นรวดเดียว แล้วสรุปว่านักเรียนนำเสนอไปทางใด
              มีประเด็นใดน่าสนใจ และมีข้อสังเกตอะไรที่เป็นประโยชน์ต่อการอ่านผลวิจัย
            </div>
          </div>
        </div>
        <div class="row g-2 mt-2">
          <div class="col-md-5">
            <select id="ovPhase" class="form-select form-select-sm rounded-3" onchange="paintPhaseOverview()">
              <?php foreach ($aiPhases as $ph): ?>
              <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
<?php if ($aiIsTeacher): ?>
          <div class="col-md-7 d-flex gap-2">
            <button id="ovRunBtn" class="btn btn-sm fw-bold rounded-pill px-4 text-white"
                    style="background:linear-gradient(135deg,#b45309,#6d28d9);" onclick="runPhaseOverview()">
              <i class="bi bi-stars me-1"></i>ให้ AI เขียนภาพรวมรอบนี้
            </button>
            <span id="ovHint" class="small text-muted align-self-center"></span>
          </div>
<?php endif; ?>
        </div>
      </div>
      <div class="card-body p-4">
        <div id="ovBox">
          <div class="text-center text-muted py-4"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลด...</div>
        </div>
      </div>
    </div>

<?php if ($aiIsTeacher): ?>
  <!-- แถบเครื่องมือของคุณครู — เก็บงานตั้งค่า/ตรวจทั้งรอบไว้ในลิ้นชัก ไม่ให้บังแผงผลตรวจซึ่งใช้บ่อยที่สุด -->
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="fw-bold text-muted small me-1"><i class="bi bi-tools me-1"></i>เครื่องมือของคุณครู</span>
    <button id="aiBatchToggleBtn" class="btn btn-outline-primary btn-sm rounded-pill px-3" type="button"
            data-bs-toggle="collapse" data-bs-target="#aiBatchCard">
      <i class="bi bi-lightning-charge-fill me-1"></i>ตรวจทั้งรอบรวดเดียว
      <!-- ป้ายเตือนเมื่อมีคิวตรวจค้างอยู่ ครูจะได้ไม่พลาดแม้การ์ดนี้จะพับอยู่ -->
      <span id="aiBatchResumeBadge" class="badge rounded-pill bg-warning text-dark ms-1 d-none">
        ค้าง <span id="aiBatchResumeBadgeCount">0</span>
      </span>
    </button>
    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button"
            data-bs-toggle="collapse" data-bs-target="#aiSettingsCard">
      <i class="bi bi-sliders me-1"></i>ตั้งค่าผู้ช่วย AI
    </button>
  </div>
  <div class="collapse" id="aiBatchCard">
  <!-- ตรวจทั้งรอบรวดเดียว (ครูเท่านั้น) -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #6d28d9 !important;">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
      <h6 class="fw-bold text-dark mb-0">
        <i class="bi bi-lightning-charge-fill text-warning me-2"></i>ตรวจทั้งรอบรวดเดียว
      </h6>
      <div class="text-muted small mt-1">
        เลือกรอบงานแล้วกดครั้งเดียว ระบบจะไล่ตรวจเรียงความของนักเรียนทีละฉบับจนครบ
      </div>
    </div>
    <div class="card-body p-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label fw-bold small">รอบงานที่จะตรวจ</label>
          <select id="batchPhase" class="form-select border-2 rounded-3" onchange="loadBatchTargets()">
            <?php foreach ($aiPhases as $ph): ?>
            <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-bold small">ห้องเรียน</label>
          <select id="batchRoom" class="form-select border-2 rounded-3" onchange="loadBatchTargets()">
            <option value="">ทุกห้องเรียน</option>
          </select>
        </div>
        <div class="col-md-5">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="batchSkipDone" checked onchange="paintBatchSummary()">
            <label class="form-check-label small" for="batchSkipDone">
              ข้ามฉบับที่เคยตรวจแล้ว <span class="text-muted">(ประหยัดโควตา — ฉบับที่แก้ไขต้นฉบับหลังตรวจจะยังถูกตรวจใหม่ให้)</span>
            </label>
          </div>
          <div class="d-flex gap-2">
            <button id="batchStartBtn" class="btn fw-bold rounded-pill px-4 text-white flex-grow-1"
                    style="background:linear-gradient(135deg,#6d28d9,#0d7377);" onclick="startBatchReview()">
              <i class="bi bi-play-fill me-1"></i>เริ่มตรวจทั้งรอบ
            </button>
            <button id="batchStopBtn" class="btn btn-outline-danger rounded-pill px-3 d-none" onclick="stopBatchReview()">
              <i class="bi bi-stop-fill me-1"></i>หยุด
            </button>
          </div>
          <!-- ฉบับที่ตรวจไม่สำเร็จในรอบที่แล้ว ถือว่ายังไม่ได้ตรวจ — กดตรวจซ้ำเฉพาะกลุ่มนั้นได้เลย -->
          <button id="batchRetryBtn" class="btn btn-outline-warning fw-bold rounded-pill px-4 w-100 mt-2 d-none"
                  onclick="retryFailedBatch()">
            <i class="bi bi-arrow-clockwise me-1"></i>ตรวจซ้ำเฉพาะที่ไม่สำเร็จ (<span id="batchRetryCount">0</span> ฉบับ)
          </button>
          <!-- ตรวจใหม่ทุกรอบรวดเดียว — ใช้เมื่อเกณฑ์/คำสั่งตรวจเปลี่ยน แล้วอยากให้ผลทั้งชุดมาจากกติกาเดียวกัน
               ระบบไล่ตามลำดับการเรียนเสมอ ฉบับตั้งต้นจึงถูกตรวจก่อนร่างหลังที่ต้องเทียบกับมัน -->
          <button id="batchAllBtn" class="btn btn-outline-danger fw-bold rounded-pill px-4 w-100 mt-2"
                  onclick="startBatchReviewAllPhases()">
            <i class="bi bi-arrow-repeat me-1"></i>ตรวจใหม่ทุกรอบรวดเดียว (ทั้ง <?php echo count($aiPhases); ?> รอบ)
          </button>
        </div>
      </div>

      <!-- คิวที่ค้างอยู่จากการตรวจครั้งก่อน (โควตาหมด / กดหยุด / เผลอปิดหน้าไปกลางคัน)
           ระบบจำรายการที่ยังไม่ได้ตรวจไว้ในเครื่อง จึงกด "ตรวจต่อ" ได้โดยไม่ต้องเริ่มใหม่ทั้งชุด -->
      <div id="batchResumeWrap" class="alert alert-warning border-0 rounded-3 mt-3 mb-0 d-none">
        <div class="fw-bold mb-1">
          <i class="bi bi-hourglass-split me-1"></i>มีคิวตรวจค้างอยู่จากครั้งที่แล้ว
        </div>
        <div id="batchResumeDetail" class="small mb-2"></div>
        <div class="d-flex flex-wrap gap-2">
          <button id="batchResumeBtn" class="btn btn-warning fw-bold rounded-pill px-4" onclick="resumeBatchReview()">
            <i class="bi bi-play-circle-fill me-1"></i>ตรวจต่อจากที่ค้างไว้ (<span id="batchResumeCount">0</span> ฉบับ)
          </button>
          <button class="btn btn-outline-secondary rounded-pill px-3" onclick="discardBatchResume()">
            <i class="bi bi-x-circle me-1"></i>ล้างคิวที่ค้าง
          </button>
        </div>
      </div>

      <div id="batchSummary" class="mt-3 small text-muted">กำลังโหลดรายการ...</div>
      <div class="mt-2 small text-muted">
        <i class="bi bi-info-circle me-1"></i>ปุ่ม <strong>&quot;ตรวจใหม่ทุกรอบรวดเดียว&quot;</strong>
        ใช้ตอนที่อยากให้ผลตรวจทั้งชุดมาจากกติกาเดียวกัน — ระบบจะไล่ตรวจ
        <strong>ทุกรอบตามลำดับการเรียน</strong> (ก่อนเรียน → D1.1 → D1.2 → D2.1 → D2.2 → หลังเรียน)
        ฉบับตั้งต้นจึงถูกตรวจก่อนร่างหลังเสมอ ผลเทียบร่างจึงครบ · ตรวจซ้ำแม้ฉบับที่เคยตรวจแล้ว
        และคะแนนที่คุณครูปรับไว้รายข้อจะถูกล้าง
      </div>

      <div id="batchProgressWrap" class="mt-3 d-none">
        <div class="d-flex justify-content-between small fw-bold mb-1">
          <span id="batchProgressLabel">กำลังตรวจ...</span>
          <span id="batchProgressCount">0 / 0</span>
        </div>
        <div class="progress" style="height:10px;">
          <div id="batchProgressBar" class="progress-bar" role="progressbar"
               style="width:0%; background:linear-gradient(90deg,#6d28d9,#0d7377);"></div>
        </div>
      </div>

      <div id="batchLog" class="mt-3 d-none border rounded-3" style="max-height:320px; overflow:auto;"></div>
    </div>
  </div>
  </div>
  <div class="collapse" id="aiSettingsCard">
  <!-- ตั้งค่า AI (เฉพาะครู) -->
  <div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
      <h6 class="fw-bold text-dark mb-0"><i class="bi bi-sliders text-primary me-2"></i>ตั้งค่าผู้ช่วย AI</h6>
    </div>
    <div id="aiSettingsBody">
      <div class="card-body p-4">
        <div class="alert alert-primary border-0 rounded-3 small">
          <i class="bi bi-key-fill me-1"></i>
          ต้องมี <strong>API key</strong> ของผู้ให้บริการ AI ก่อนจึงจะใช้งานได้ —
          มีหลายเจ้าที่<strong>ให้ใช้ฟรี</strong> ดูวิธีขอทีละขั้นได้ในไฟล์ <code>AI_SETUP.md</code>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-bold small">ผู้ให้บริการ AI</label>
            <select id="aiProvider" class="form-select border-2 rounded-3" onchange="onProviderChange()"></select>
            <div class="form-text small" id="aiProviderHint"></div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold small">ชื่อโมเดล</label>
            <div class="input-group">
              <input type="text" id="aiModel" class="form-control border-2 rounded-start-3" placeholder="เช่น gemini-3.6-flash">
              <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="useDefaultModel()"
                      title="ล้างช่องนี้เพื่อกลับไปใช้โมเดลเริ่มต้นของผู้ให้บริการ">
                <i class="bi bi-arrow-counterclockwise"></i> ใช้ค่าเริ่มต้น
              </button>
            </div>
            <div class="form-text small">
              เว้นว่างไว้เพื่อใช้โมเดลเริ่มต้นของผู้ให้บริการ (แนะนำ — ระบบจะตามรุ่นใหม่ให้เองเมื่อผู้ให้บริการเลิกใช้รุ่นเก่า)
            </div>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-bold small">API key</label>
            <input type="password" id="aiApiKey" class="form-control border-2 rounded-3" autocomplete="off"
                   placeholder="วาง API key ที่นี่ (เว้นว่างไว้ = ใช้คีย์เดิม)">
            <div class="form-text small" id="aiKeyHint"></div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold small">เว็บที่ให้บริการ (Base URL)</label>
            <input type="text" id="aiBaseUrl" class="form-control border-2 rounded-3" placeholder="ใช้ค่าเริ่มต้น">
          </div>
        </div>

        <hr class="my-4">

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="aiEnabled">
          <label class="form-check-label fw-bold small" for="aiEnabled">เปิดใช้งานผู้ช่วย AI</label>
        </div>
        <div class="form-text small">
          ปิดสวิตช์นี้เมื่อไม่ต้องการให้สั่งตรวจเพิ่ม — ผลตรวจที่บันทึกไว้แล้วยังแสดงให้นักเรียนดูได้ตามปกติ
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-4">
            <label class="form-label fw-bold small" for="aiDailyLimit">โควตาการตรวจต่อวัน (ครั้ง)</label>
            <input type="number" id="aiDailyLimit" class="form-control border-2 rounded-3"
                   min="50" max="5000" step="10">
            <div class="form-text small" id="aiDailyLimitHint"></div>
          </div>
          <div class="col-md-8 d-flex align-items-end">
            <div class="form-text small mb-1">
              <i class="bi bi-battery-half me-1"></i>
              เพดานนี้เป็นของ<strong>ระบบเรา</strong> ไว้กันเผลอสั่งตรวจรัวจนโควตาฟรีของผู้ให้บริการหมด
              — นับเฉพาะ<strong>ครั้งที่ตรวจสำเร็จ</strong> และรีเซ็ตทุกเที่ยงคืน
              ถ้าตรวจทั้งชั้นหลายรอบในวันเดียวแล้วโควตาไม่พอ ปรับเพิ่มตรงนี้ได้เลย
              แต่ผู้ให้บริการ AI ยังมีเพดานของตัวเองอีกชั้นหนึ่ง (ปรับตรงนี้ไม่ได้ช่วยให้เกินเพดานของเขา)
            </div>
          </div>
        </div>
        <div class="alert alert-secondary border-0 rounded-3 small mt-3 mb-0">
          <i class="bi bi-person-lock me-1"></i>
          <strong>คุณครูเป็นผู้สั่งตรวจเพียงผู้เดียว</strong> — นักเรียนกดให้ AI ตรวจเองไม่ได้
          เห็นได้เฉพาะผลที่คุณครูตรวจให้แล้วเท่านั้น
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
          <button class="btn btn-outline-danger rounded-pill px-3" onclick="clearApiKey()">
            <i class="bi bi-trash me-1"></i>ลบ API key
          </button>
          <button class="btn btn-primary rounded-pill px-4 fw-bold" id="aiSaveSettingsBtn" onclick="saveAiSettings()">
            <i class="bi bi-check2-circle me-1"></i>บันทึกการตั้งค่า
          </button>
        </div>

        <div id="aiUsageBox" class="mt-4 small text-muted"></div>
      </div>
    </div>
  </div>
  </div>
<?php endif; ?>

  <!-- ตารางภาพรวมผลตรวจทั้งชั้น รายคน × รายรอบงาน -->
  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table text-secondary me-2"></i>ภาพรวมผลตรวจ AI ทั้งชั้น</h6>
          <div class="text-muted small mt-1">คะแนนรวมของนักเรียนแต่ละคนทุกรอบงาน พร้อมค่าเฉลี่ยรายบุคคลและค่าเฉลี่ยทั้งชั้น
            · ใช้ช่องกรองเพื่อดูเฉพาะกลุ่ม เช่น คนที่คะแนนต่ำลงในร่างที่ 2</div>
        </div>
        <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="loadAiOverview()">
          <i class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
        </button>
      </div>
      <div class="row g-2 mt-2">
        <div class="col-sm-6 col-lg-4">
          <input type="search" id="aiOverviewSearch" class="form-control form-control-sm rounded-3"
                 placeholder="ค้นหารหัส / ชื่อ / ห้อง" oninput="paintAiOverview()">
        </div>
        <div class="col-sm-6 col-lg-3">
          <select id="aiOverviewPhase" class="form-select form-select-sm rounded-3" onchange="paintAiOverview()">
            <option value="">ทุกรอบงาน (แสดงครบทุกคอลัมน์)</option>
            <?php foreach ($aiPhases as $ph): ?>
            <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-6 col-lg-3">
          <select id="aiOverviewFocus" class="form-select form-select-sm rounded-3" onchange="paintAiOverview()"
                  title="คัดเฉพาะนักเรียนที่เข้าเงื่อนไข เพื่อดูเป็นกลุ่ม ๆ ได้เร็วขึ้น">
            <option value="">ดูทุกคน (ไม่กรอง)</option>
            <option value="down">คะแนนต่ำลงกว่าฉบับตั้งต้น</option>
            <option value="flat">คะแนนเท่าเดิม ไม่ขยับ</option>
            <option value="up">คะแนนดีขึ้น</option>
            <option value="low">ระดับคุณภาพ พอใช้ / ต้องปรับปรุง</option>
            <option value="notdone">ยังตรวจไม่ครบทุกรอบที่แสดงอยู่</option>
            <option value="adjusted">มีข้อที่ครูปรับคะแนนไว้</option>
          </select>
        </div>
        <div class="col-lg-2 d-flex align-items-center">
          <div>
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="aiOverviewNeedScore" onchange="paintAiOverview()">
              <label class="form-check-label small" for="aiOverviewNeedScore">เฉพาะที่รอคะแนนจากครู</label>
            </div>
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="aiOverviewNeedRecheck" onchange="paintAiOverview()">
              <label class="form-check-label small" for="aiOverviewNeedRecheck">เฉพาะที่รอตรวจใหม่</label>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      <div id="aiOverviewBox" style="max-height:520px; overflow:auto;">
        <div class="text-center text-muted py-5"><i class="bi bi-hourglass-split me-2"></i>กำลังโหลด...</div>
      </div>
    </div>
  </div>
  </div><!-- /tab-class -->
<?php endif; ?>
</div><!-- /tab-content -->
</div>

<?php if ($aiIsTeacher): ?>
<!-- หน้าต่างปรับคะแนนรายข้อ: ครูปรับคะแนนเอง หรือสั่งให้ AI ตรวจเฉพาะข้อนั้นใหม่ -->
<div class="modal fade" id="aiCritModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header border-bottom">
        <div>
          <h6 class="fw-bold text-dark mb-0" id="aiCritTitle">ปรับคะแนนรายข้อ</h6>
          <div class="text-muted small mt-1" id="aiCritSubtitle"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body p-4" id="aiCritBody"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
  /* แท็บหลักของหน้า — แยกงาน "ดูรายคน" กับ "ดูทั้งชั้น" ออกจากกันให้ชัด */
  .ai-main-tabs .nav-link {
    color: var(--primary-navy);
    font-weight: 700;
    border-radius: 999px;
    padding: 8px 20px;
  }
  .ai-main-tabs .nav-link.active {
    background: linear-gradient(135deg, #6d28d9, #0d7377);
    color: #ffffff;
  }

  /* แถบสรุปย่อของนักเรียนที่กำลังเปิดดู (แท็บรายบุคคล) */
  .ai-person-strip {
    background: linear-gradient(135deg, #faf7ff 0%, #f0fdfa 100%);
    border: 1px solid #ddd6fe;
  }
  .ai-person-figure { line-height: 1.1; }
  .ai-person-figure .num { font-size: 1.55rem; font-weight: 800; color: #4c1d95; }
  .ai-person-figure .cap { font-size: 0.75rem; color: #64748b; }

  /* การ์ดแนวทางการนำเสนอในภาพรวมรายรอบงาน */
  .ov-theme { background: #fffdf5; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; }

  /* การ์ดตัวเลขเด่นของทั้งชั้น (แท็บภาพรวม) */
  .ai-hl-card { background: #ffffff; border: 1px solid var(--border-gray); }
  .ai-hl-card .num { font-size: 1.7rem; font-weight: 800; line-height: 1.1; }

  /* การ์ดคะแนนรายรอบงาน — คลิกเพื่อเปิดรายละเอียดการให้คะแนนและข้อมูลย้อนกลับ */
  .ai-phase-card {
    background: #ffffff;
    border: 2px solid var(--border-gray);
    transition: all 0.2s ease-in-out;
    display: flex;
    flex-direction: column;
  }
  .ai-phase-card[role="button"] { cursor: pointer; }
  .ai-phase-card[role="button"]:hover {
    border-color: #6d28d9;
    box-shadow: 0 6px 18px rgba(109, 40, 217, 0.12);
    transform: translateY(-2px);
  }
  .ai-phase-card[role="button"]:focus-visible { outline: 3px solid #ddd6fe; outline-offset: 2px; }
  .ai-phase-card-active {
    border-color: #6d28d9;
    background: #faf7ff;
    box-shadow: 0 6px 18px rgba(109, 40, 217, 0.15);
  }
  .ai-phase-card-empty { background: #f8fafc; border-style: dashed; }
  /* การ์ดค่าเฉลี่ยทั้งชั้น (ครู/ผู้เชี่ยวชาญ) — แยกโทนจากการ์ดของนักเรียนให้ไม่สับสน */
  .ai-phase-card-class { border-color: #99f6e4; background: #ffffff; }
  .ai-class-avg-wrap { background: #f0fdfa; border: 1px dashed #99f6e4; }
  .ai-phase-card .ai-score-bar { height: 8px; }

  /* ตารางภาพรวมผลตรวจ AI — ใช้รูปแบบหัวตารางเดียวกับ "รายงานการส่งงานรายบุคคล" (submission_report.php) */
  .ai-report-table { min-width: 760px; }
  .ai-report-table th, .ai-report-table td { white-space: nowrap; text-align: center; font-size: 0.86rem; }
  .ai-report-table thead th {
    background: var(--light-slate);
    color: var(--primary-navy);
    font-weight: 700;
    border-bottom: 2px solid var(--border-gray);
    vertical-align: middle;
    position: sticky;
    z-index: 2;
  }
  .ai-report-table thead .report-head-group th { top: 0; }
  .ai-report-table thead .report-head-sub th   { top: 42px; }
  .ai-report-table thead .grp-unit1 { background: #eff6ff; color: #1d4ed8; }
  .ai-report-table thead .grp-unit2 { background: #eef2ff; color: #3730a3; }
  .ai-report-table tbody td { border-bottom: 1px solid var(--border-gray); }
  .ai-report-table tbody .stu-id { font-family: monospace; color: #64748b; font-weight: 600; text-align: left; }
  .ai-report-table tbody .stu-name { font-weight: 600; color: var(--primary-navy); text-align: left; white-space: normal; }
  .ai-report-table tbody tr:hover td { background: var(--light-blue); }
  .ai-cell-score { cursor: pointer; color: #0f172a; }
  .ai-cell-score:hover { background: #ede9fe !important; }
  .ai-cell-empty { color: #cbd5e1; }
  .ai-cell-avg { background: #f8fafc; }
  .ai-cell-wait { color: #d97706; font-weight: 700; margin-left: 2px; }
  .ai-cell-stale { background: #fffbeb; }
  .ai-cell-stale-icon { color: #d97706; margin-left: 3px; }
  .ai-stu-link { color: var(--primary-navy); text-decoration: none; }
  .ai-stu-link:hover { text-decoration: underline; color: #6d28d9; }
  /* สัญลักษณ์คู่เทียบตามที่ครูกำหนด (ร่างหลังต้องดีกว่าร่างก่อน) */
  .ai-cell-pair-up   { color: #0d9488; margin-left: 3px; font-size: 0.72rem; }
  .ai-cell-pair-flat { color: #d97706; margin-left: 3px; font-size: 0.72rem; }
  .ai-cell-pair-down { color: #dc2626; margin-left: 3px; font-size: 0.72rem; }
  /* ฉบับที่ครูตรวจทานคะแนนของ AI ไว้รายข้อ */
  .ai-cell-ov { color: #6d28d9; margin-left: 3px; font-size: 0.72rem; }
  .ai-recheck-row { cursor: pointer; }
  .ai-recheck-row:hover { background: #fffbeb; }
  .ai-report-avg td {
    background: #f1f5f9;
    color: var(--primary-navy);
    border-top: 2px solid var(--border-gray);
    position: sticky;
    bottom: 0;
    z-index: 1;
  }
</style>

<script src="ai_review.js"></script>
<script>
const AI_ROLE       = <?php echo json_encode($aiRole); ?>;
const AI_IS_STUDENT = <?php echo $aiIsStudent ? 'true' : 'false'; ?>;
const AI_IS_TEACHER = <?php echo $aiIsTeacher ? 'true' : 'false'; ?>;
const AI_MY_ID      = <?php echo json_encode($sessionUser['id']); ?>;
const AI_PHASES     = <?php echo json_encode($aiPhases); ?>;
// ลักษณะงานเขียนของแต่ละรอบ (เชิงบรรยาย/เชิงวิจารณ์ + คำสำคัญที่ครูกำหนด)
// ใช้บอกบนการ์ดว่ารอบนั้นเป็นงานชนิดใด ครูจะได้รู้ว่า AI ตรวจด้วยหลักอะไร
const AI_PHASE_STYLE = <?php
    $aiStyleMap = [];
    foreach ($aiPhases as $aiPh) {
        $aiSt = ai_essay_style($aiPh);
        if (!$aiSt) continue;
        $aiStyleMap[$aiPh] = [
            'kind' => $aiSt['kind'],
            'name' => $aiSt['name'],
            'keys' => array_values(array_filter((array)($aiSt['keys'] ?? []))),
        ];
    }
    echo json_encode($aiStyleMap, JSON_UNESCAPED_UNICODE);
?>;
// เกณฑ์ของข้อที่ AI เป็นผู้ตรวจ — ใช้แสดงคำอธิบายระดับคะแนนตอนคุณครูปรับคะแนนรายข้อ
const AI_RUBRIC_ITEMS = <?php
    echo json_encode(array_values(array_filter(ai_rubric(), function ($it) { return $it['ai']; })),
                     JSON_UNESCAPED_UNICODE);
?>;

let aiStatus    = null;   // สถานะฟีเจอร์ AI ของผู้ใช้คนนี้
let aiProviders = [];     // รายชื่อผู้ให้บริการ (เฉพาะครู)

// ป้ายชื่อรอบงานและตัวหนีอักขระ ใช้ของกลางจาก ai_review.js
const AI_PHASE_LABELS = AI_PHASE_LABEL_MAP;
const esc = aiEsc;

function currentStudentId() {
  if (AI_IS_STUDENT) return AI_MY_ID;
  const el = document.getElementById('aiStudentSelect');
  return el ? el.value : '';
}
// รอบงานที่กำลังเปิดดูรายละเอียดอยู่ ('' = ยังไม่ได้เลือกการ์ดไหน)
let selectedPhase  = '';
let aiAllFeedback  = {};   // รหัสรอบงาน => ผลตรวจฉบับเต็ม (โหลดครั้งเดียวได้ครบทุกรอบ)
let aiEssayStatus  = {};   // รหัสรอบงาน => สถานะเรียงความ {word_count, too_short, updated_at}
let aiCardsReady   = false;   // โหลดข้อมูลการ์ดครบแล้วหรือยัง (กันการวาดการ์ดเปล่าแวบหนึ่งตอนเปิดหน้า)

function currentPhase() {
  return selectedPhase;
}

// ---------------------------------------------------------------- สถานะระบบ
async function loadAiStatus() {
  const st = await aiGetStatus();
  if (st) aiStatus = st;
  paintStatusBar();
  updateReviewButton();
}

function paintStatusBar() {
  const bar = document.getElementById('aiStatusBar');
  if (!aiStatus) { bar.classList.add('d-none'); return; }

  let cls = 'alert-success', html = '';
  if (!aiStatus.enabled) {
    cls = 'alert-secondary';
    html = '<i class="bi bi-pause-circle me-1"></i>คุณครูปิดการใช้งานผู้ช่วย AI ไว้ชั่วคราว — ยังดูผลตรวจเดิมที่เคยบันทึกไว้ได้';
  } else if (!aiStatus.configured) {
    cls = 'alert-warning';
    html = AI_IS_TEACHER
      ? '<i class="bi bi-exclamation-triangle me-1"></i>ยังไม่ได้ตั้งค่า API key — กดปุ่ม "ตั้งค่าผู้ช่วย AI" ด้านล่างเพื่อใส่คีย์ก่อนใช้งาน'
      : '<i class="bi bi-exclamation-triangle me-1"></i>ระบบ AI ยังไม่พร้อมใช้งาน กรุณาแจ้งคุณครูให้ตั้งค่าก่อน';
  } else if (!AI_IS_TEACHER) {
    cls = 'alert-info';
    html = '<i class="bi bi-eye me-1"></i>หน้านี้แสดงผลตรวจที่คุณครูให้ AI ตรวจไว้แล้วครบทุกรอบงาน — คลิกการ์ดคะแนนเพื่อดูรายละเอียด';
  } else {
    html = '<i class="bi bi-check-circle me-1"></i>ผู้ช่วย AI พร้อมใช้งาน';
  }
  bar.className = 'alert border-0 rounded-3 small ' + cls;
  bar.innerHTML = html;
  bar.classList.remove('d-none');
}

// ข้อความโควตา/เหตุผลที่ยังสั่งตรวจไม่ได้ (ปุ่มสั่งตรวจย้ายไปอยู่บนการ์ดแต่ละใบแล้ว)
function reviewBlockReason() {
  if (!AI_IS_TEACHER) return 'เฉพาะคุณครูเท่านั้นที่สั่งให้ AI ตรวจได้';
  if (!aiStatus) return 'กำลังตรวจสอบสถานะระบบ AI';
  if (!aiStatus.can_review) return aiStatus.enabled ? 'ยังไม่ได้ตั้งค่า API key' : 'ผู้ช่วย AI ถูกปิดใช้งานอยู่';
  if (aiStatus.quota_left <= 0) return 'วันนี้ใช้โควตาครบแล้ว (' + aiStatus.quota_limit + ' ครั้ง/วัน)';
  if (!currentStudentId()) return 'กรุณาเลือกนักเรียนก่อน';
  return '';
}

function updateReviewButton() {
  const quota = document.getElementById('aiQuotaText');
  if (!quota) return;
  if (!AI_IS_TEACHER) { quota.textContent = ''; return; }

  const reason = reviewBlockReason();
  quota.innerHTML = reason
    ? '<i class="bi bi-info-circle me-1"></i>' + esc(reason)
    : '<i class="bi bi-battery-half me-1"></i>วันนี้ใช้ไปแล้ว ' + aiStatus.quota_used + ' จาก ' + aiStatus.quota_limit
      + ' ครั้ง · เรียงความต้องยาวอย่างน้อย ' + aiStatus.min_words + ' คำ';
  paintPhaseCards();   // ปุ่มบนการ์ดเปิด/ปิดตามสถานะโควตาด้วย
}

// ------------------------------------------------------------ ดึง/แสดงผลตรวจ
// โหลดผลตรวจ "ทุกรอบงาน" ของนักเรียนคนที่เลือกในคำขอเดียว แล้ววาดเป็นการ์ดคะแนนทั้งหมด
async function loadFeedback() {
  const cards = document.getElementById('aiPhaseCards');
  const panel = document.getElementById('aiFeedbackPanel');
  const sid   = currentStudentId();

  aiAllFeedback = {};
  aiEssayStatus = {};
  aiCardsReady  = false;
  panel.innerHTML = '';
  const strip = document.getElementById('aiPersonStrip');
  if (strip) strip.innerHTML = '';

  if (!sid) {
    paintPhaseCards();   // ยังไม่เลือกนักเรียน — ขึ้นกล่องบอกให้เลือกก่อน
    return;
  }

  cards.innerHTML = `<div class="col-12"><div class="card border-0 shadow-sm rounded-4"><div class="card-body">`
    + aiLoadingHTML('กำลังโหลดผลตรวจทุกรอบงาน...') + `</div></div></div>`;

  try {
    const params = new URLSearchParams({ action: 'get_ai_feedback' });
    if (!AI_IS_STUDENT) params.set('student_id', sid);
    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    if (data.success) {
      (data.list || []).forEach(fb => { aiAllFeedback[fb.essay_phase] = fb; });
      aiEssayStatus = data.essays || {};
      // ผลตรวจเก่าที่บันทึกไว้ก่อนมีระบบเทียบร่าง — คำนวณผลเทียบจากคะแนนที่มีอยู่ให้เลย
      aiAttachDraftCompare(aiAllFeedback);
    }
  } catch (err) {
    cards.innerHTML = `<div class="col-12">${aiErrorHTML('โหลดผลตรวจไม่สำเร็จ กรุณาลองใหม่อีกครั้ง')}</div>`;
    return;
  }

  // รอบที่เคยเปิดดูอยู่ยังมีผลตรวจไหม ถ้าไม่มีก็กลับไปหน้ารวมการ์ด
  if (selectedPhase && !aiAllFeedback[selectedPhase]) selectedPhase = '';
  aiCardsReady = true;
  paintPhaseCards();
  paintSelectedFeedback();
}

/* ============================================================
   แถบสรุปย่อของนักเรียนที่กำลังเปิดดู
   ตัวเลขสำคัญไม่กี่ตัว + ปุ่มเปิด "สรุปภาพรวมผลงานเขียน" ซึ่งแยกไปเป็นอีกหน้าหนึ่งแล้ว
   (ai_student_summary.php) หน้านี้จึงเหลือแต่การ์ดคะแนนกับรายละเอียดรายฉบับ อ่านง่ายขึ้น
   ============================================================ */

// รอบที่มีผลตรวจแล้ว เรียงตามลำดับการเรียน
function aiReviewedPhases() {
  return AI_PHASES.filter(ph => aiAllFeedback[ph]);
}

// ผลของคู่เทียบที่ครูกำหนด (D1.1→D1.2, D2.1→D2.2, ก่อนเรียน→หลังเรียน) ของนักเรียนคนที่เปิดอยู่
function personPairStat() {
  const targets = Object.keys(AI_BASELINE_PAIRS);
  const done = targets.filter(ph => aiAllFeedback[ph]
    && aiAllFeedback[ph].draft_compare && aiAllFeedback[ph].draft_compare.has_baseline);
  return {
    total: targets.length,
    done:  done.length,
    ok:    done.filter(ph => aiAllFeedback[ph].draft_compare.delta > 0).length,
  };
}

function paintPersonStrip() {
  const box = document.getElementById('aiPersonStrip');
  if (!box) return;
  const sid = currentStudentId();
  const phases = aiReviewedPhases();

  if (!sid) { box.innerHTML = ''; return; }
  if (!aiCardsReady) return;

  const summaryUrl = 'ai_student_summary.php?student_id=' + encodeURIComponent(sid);
  const openBtn = `<a href="${summaryUrl}" target="_blank" rel="noopener"
       class="btn fw-bold rounded-pill px-4 text-white text-nowrap"
       style="background:linear-gradient(135deg,#6d28d9,#0d7377);">
       <i class="bi bi-clipboard2-data me-1"></i>เปิดสรุปภาพรวมผลงานเขียน
       <i class="bi bi-box-arrow-up-right ms-1 small"></i></a>`;

  if (!phases.length) {
    box.innerHTML = `<div class="ai-person-strip rounded-4 p-3 d-flex align-items-center
                                justify-content-between flex-wrap gap-3">
      <div class="text-muted small"><i class="bi bi-inbox me-1"></i>นักเรียนคนนี้ยังไม่มีผลตรวจของ AI สักฉบับ</div>
      ${openBtn}
    </div>`;
    return;
  }

  const last    = aiAllFeedback[phases[phases.length - 1]];
  const who     = last.student_name || '';
  const fullMax = Number(last.full_max || last.max_score || 60);
  const vals    = phases.map(ph => aiCombinedOf(aiAllFeedback[ph])).filter(v => v !== null);
  const avg     = vals.reduce((a, b) => a + b, 0) / vals.length;
  const pair    = personPairStat();

  const pairCls = !pair.done ? 'bg-secondary-subtle text-secondary-emphasis'
    : (pair.ok === pair.done ? 'bg-success-subtle text-success-emphasis'
                             : 'bg-warning-subtle text-warning-emphasis');
  const pairText = !pair.done
    ? 'ยังเทียบร่างไม่ได้'
    : `เทียบร่างดีขึ้น ${pair.ok}/${pair.done} คู่`;

  box.innerHTML = `<div class="ai-person-strip rounded-4 p-3 d-flex align-items-center
                              justify-content-between flex-wrap gap-3">
    <div class="d-flex align-items-center gap-4 flex-wrap">
      ${who ? `<div class="fw-bold text-dark"><i class="bi bi-person-fill text-primary me-1"></i>${esc(who)}</div>` : ''}
      <div class="ai-person-figure">
        <div class="num">${phases.length}<span class="fs-6 fw-normal text-muted">/${AI_PHASES.length}</span></div>
        <div class="cap">ตรวจแล้ว (รอบงาน)</div>
      </div>
      <div class="ai-person-figure">
        <div class="num">${aiNum1(avg)}<span class="fs-6 fw-normal text-muted">/${aiNum(fullMax)}</span></div>
        <div class="cap">คะแนนเฉลี่ยทุกรอบ</div>
      </div>
      <div class="ai-person-figure">
        <div class="num">${aiNum(aiCombinedOf(last))}<span class="fs-6 fw-normal text-muted">/${aiNum(fullMax)}</span></div>
        <div class="cap">รอบล่าสุด · ${esc(AI_PHASE_SHORT_MAP[last.essay_phase] || last.essay_phase)}</div>
      </div>
      <span class="badge rounded-pill px-3 py-2 ${pairCls}">
        <i class="bi bi-arrow-left-right me-1"></i>${esc(pairText)}</span>
    </div>
    ${openBtn}
  </div>`;
}

// จัดกลุ่มการ์ดตามลักษณะงาน: แบบวัดก่อน-หลังเรียนอยู่คู่กันแถวหนึ่ง
// ส่วนภาระงานระหว่างเรียนทั้ง 4 ร่างเรียงอยู่ในแถวเดียวกันอีกแถวหนึ่ง
const AI_CARD_GROUPS = [
  {
    key: 'test', title: 'แบบวัดความสามารถ (ก่อนเรียน – หลังเรียน)', icon: 'bi-clipboard2-pulse',
    phases: ['pretest', 'posttest'], cols: 'row-cols-1 row-cols-md-2',
  },
  {
    key: 'task', title: 'ภาระงานระหว่างเรียน', icon: 'bi-journal-text',
    phases: ['task1_d1', 'task1_d2', 'task2_d1', 'task2_d2'], cols: 'row-cols-1 row-cols-sm-2 row-cols-lg-4',
  },
];

// ค่าเฉลี่ยของทั้งชั้นรายรอบงาน คิดจากตารางภาพรวมที่โหลดไว้แล้ว (ครู/ผู้เชี่ยวชาญเท่านั้น)
function aiClassAverages() {
  const acc = {};
  (aiOverviewList || []).forEach(r => {
    if (!acc[r.essay_phase]) acc[r.essay_phase] = { sum: 0, cnt: 0, max: Number(r.full_max) || 60 };
    acc[r.essay_phase].sum += Number(r.combined_total);
    acc[r.essay_phase].cnt++;
  });
  Object.keys(acc).forEach(ph => { acc[ph].avg = acc[ph].sum / acc[ph].cnt; });
  return acc;
}

// การ์ดค่าเฉลี่ยทั้งชั้นของรอบงานหนึ่ง — หน้าตาเดียวกับการ์ดของนักเรียน แต่เป็นสีเขียวอมฟ้าและกดไม่ได้
function classPhaseCardHTML(ph, stat) {
  const label = AI_PHASE_LABELS[ph] || ph;
  if (!stat || !stat.cnt) {
    return `<div class="col">
      <div class="ai-phase-card ai-phase-card-empty ai-phase-card-class h-100 p-3 rounded-4">
        <div class="fw-bold text-dark mb-2">${esc(label)}</div>
        <div class="text-muted"><i class="bi bi-dash-circle me-1"></i>ยังไม่มีผลตรวจในรอบนี้</div>
      </div>
    </div>`;
  }

  const pct = stat.max > 0 ? Math.round((stat.avg / stat.max) * 100) : 0;

  // ถ้ากำลังเปิดดูนักเรียนอยู่ ให้เทียบคะแนนของคนนี้กับค่าเฉลี่ยไปเลย
  const mine = (currentStudentId() && aiAllFeedback[ph]) ? aiCombinedOf(aiAllFeedback[ph]) : null;
  let cmp = '';
  if (mine !== null) {
    const d  = Math.round((mine - stat.avg) * 100) / 100;
    const up = d > 0, same = (d === 0);
    cmp = `<div class="small mt-2 ${same ? 'text-muted' : (up ? 'text-success-emphasis' : 'text-danger-emphasis')}">
      <i class="bi ${same ? 'bi-dash-lg' : (up ? 'bi-arrow-up-short' : 'bi-arrow-down-short')} me-1"></i>
      นักเรียนคนนี้ได้ ${aiNum(mine)} — ${same ? 'เท่ากับค่าเฉลี่ย' : (up ? 'สูงกว่าค่าเฉลี่ย ' : 'ต่ำกว่าค่าเฉลี่ย ') + aiNum1(Math.abs(d)) + ' คะแนน'}
    </div>`;
  }

  return `<div class="col">
    <div class="ai-phase-card ai-phase-card-class h-100 p-3 rounded-4">
      <div class="d-flex align-items-start justify-content-between gap-2 mb-2 flex-wrap">
        <span class="fw-bold text-dark">${esc(label)}</span>
        <span class="badge bg-light text-secondary border">${stat.cnt} ฉบับ</span>
      </div>
      <div class="d-flex align-items-end gap-2" title="ค่าเฉลี่ยจริง ${aiNum(stat.avg)} คะแนน">
        <div class="display-6 fw-bold lh-1" style="color:#0d7377;">${aiNum1(stat.avg)}</div>
        <div class="text-muted pb-1 text-nowrap">/ ${aiNum(stat.max)} คะแนน</div>
      </div>
      <div class="ai-crit-bar mt-2"><span style="width:${pct}%; background:linear-gradient(90deg,#0d7377,#14b8a6);"></span></div>
      <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-1">
        <span class="badge bg-info-subtle text-info-emphasis">ระดับเฉลี่ย: ${esc(aiLevelFromScore(stat.avg))}</span>
        <span class="text-muted small">เฉลี่ยทั้งชั้น</span>
      </div>
      ${cmp}
    </div>
  </div>`;
}

// กลุ่มการ์ด "ค่าเฉลี่ยทั้งชั้นรายรอบงาน" — อยู่ในแท็บ "ภาพรวมผลตรวจ AI ทั้งชั้น"
function classAverageGroupsHTML() {
  if (AI_IS_STUDENT) return '';
  const stats = aiClassAverages();
  if (!Object.keys(stats).length) return '';

  const groups = AI_CARD_GROUPS.map(g => {
    const phases = g.phases.filter(ph => AI_PHASES.indexOf(ph) >= 0);
    if (!phases.length) return '';
    return `<div class="mb-4">
      <div class="fw-bold text-secondary small text-uppercase mb-2">
        <i class="bi ${g.icon} me-1"></i>${esc(g.title)}
      </div>
      <div class="row ${g.cols} g-3">${phases.map(ph => classPhaseCardHTML(ph, stats[ph])).join('')}</div>
    </div>`;
  }).join('');

  const total = (aiOverviewList || []).length;
  return `<div class="ai-class-avg-wrap p-3 p-md-4 rounded-4 mt-2">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill text-info me-2"></i>ค่าเฉลี่ยทั้งชั้นรายรอบงาน</h6>
        <div class="text-muted small mt-1">คิดจากผลตรวจของ AI ทั้งหมด ${total} ฉบับที่บันทึกไว้ในระบบ</div>
      </div>
    </div>
    ${groups}
  </div>`;
}

// วาดการ์ดค่าเฉลี่ยทั้งชั้นลงในแท็บภาพรวม (แยกจากการ์ดของนักเรียนรายคนแล้ว)
function paintClassAverages() {
  const box = document.getElementById('aiClassAvgCards');
  if (!box) return;
  // ยังไม่มีข้อมูลทั้งชั้น (กำลังโหลดอยู่) → ปล่อยว่างไว้ ให้การ์ดตัวเลขเด่นเป็นคนบอกสถานะแทน
  const html = classAverageGroupsHTML();
  if (html) box.innerHTML = html;
}

/* ============================================================
   ตัวเลขเด่น ๆ ของทั้งชั้น — สิ่งที่ครูอยากรู้ก่อนเปิดตาราง
   ============================================================ */
function paintClassHighlights() {
  const box = document.getElementById('aiClassHighlights');
  if (!box) return;
  const list = aiOverviewList || [];
  if (!list.length) {
    box.innerHTML = '<div class="text-center text-muted py-4">'
      + '<i class="bi bi-inbox fs-3 d-block mb-2"></i>ยังไม่มีผลตรวจของ AI ในระบบ</div>';
    return;
  }

  const fullMax  = Number(list[0].full_max) || 60;
  const students = new Set(list.map(r => r.student_id));
  const avg      = list.reduce((a, r) => a + Number(r.combined_total), 0) / list.length;
  const waiting  = list.filter(r => !r.manual_done).length;
  const stale    = list.filter(r => r.needs_recheck).length;

  // คู่เทียบตามที่ครูกำหนด — นับว่าฉบับร่างหลัง "ดีขึ้นจริง" กี่ฉบับจากที่เทียบได้
  const pairRows = list.filter(r => r.draft_delta !== null && r.draft_delta !== undefined);
  const pairOk   = pairRows.filter(r => r.draft_delta > 0).length;
  const pairPct  = pairRows.length ? Math.round((pairOk / pairRows.length) * 100) : 0;

  const tile = (icon, color, label, value, sub) => `<div class="col">
    <div class="ai-hl-card h-100 p-3 rounded-4">
      <div class="text-muted small mb-1"><i class="bi ${icon} me-1" style="color:${color};"></i>${esc(label)}</div>
      <div class="num" style="color:${color};">${value}</div>
      <div class="text-muted mt-1" style="font-size:0.78rem;">${sub}</div>
    </div>
  </div>`;

  const pairColor = !pairRows.length ? '#64748b' : (pairPct >= 80 ? '#0d9488' : (pairPct >= 50 ? '#d97706' : '#dc2626'));

  box.innerHTML = `
    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-stars text-warning me-2"></i>ตัวเลขเด่นของทั้งชั้น</h6>
    <div class="row row-cols-2 row-cols-lg-4 g-3">
      ${tile('bi-people-fill', '#6d28d9', 'นักเรียนที่มีผลตรวจ',
             students.size + ' <span class="fs-6 fw-normal text-muted">คน</span>',
             'จากผลตรวจทั้งหมด ' + list.length + ' ฉบับ')}
      ${tile('bi-bullseye', '#0d7377', 'คะแนนเฉลี่ยทั้งชั้น',
             aiNum1(avg) + ' <span class="fs-6 fw-normal text-muted">/ ' + aiNum(fullMax) + '</span>',
             'ระดับ ' + esc(aiLevelFromScore(avg)))}
      ${tile('bi-arrow-left-right', pairColor, 'ร่างหลังดีขึ้นจริง',
             (pairRows.length ? pairPct + '<span class="fs-6 fw-normal text-muted">%</span>' : '—'),
             pairRows.length
               ? pairOk + ' จาก ' + pairRows.length + ' ฉบับที่เทียบกับฉบับตั้งต้นได้'
               : 'ยังไม่มีคู่ไหนที่ตรวจครบทั้งสองฉบับ')}
      ${tile('bi-clipboard-check', (waiting || stale) ? '#b45309' : '#0d9488', 'ค้างอยู่ตอนนี้',
             (waiting + stale) + ' <span class="fs-6 fw-normal text-muted">ฉบับ</span>',
             'รอคะแนนครู ' + waiting + ' · รอตรวจใหม่ ' + stale)}
    </div>
    <div class="text-muted mt-2" style="font-size:0.78rem;">
      <i class="bi bi-info-circle me-1"></i>&quot;ร่างหลังดีขึ้นจริง&quot; นับเฉพาะคู่ที่ครูกำหนดให้เทียบกัน
      (D1.2 เทียบ D1.1 · D2.2 เทียบ D2.1 · หลังเรียน เทียบ ก่อนเรียน)
      <br><i class="bi bi-shield-check me-1"></i>ตอนตรวจร่างหลัง AI จะยึดคะแนนรายข้อของฉบับตั้งต้นเป็นจุดตั้งต้น
      มองหาสิ่งที่นักเรียนแก้จนดีขึ้นก่อนเสมอ และจะลดคะแนนข้อใดได้ก็ต่อเมื่อยกข้อความจริงมายืนยันได้ว่าร่างหลังทำได้แย่ลง
    </div>`;
}

/* ============================================================
   ภาพรวมการนำเสนอรายรอบงาน (ทั้งชั้น)
   AI อ่านผลตรวจของทุกคนในรอบนั้นรวดเดียว แล้วสรุปว่านำเสนอไปทางใด มีอะไรน่าสนใจ
   ตัวเลขทั้งหมดในกล่องนี้ระบบคำนวณเอง ไม่ได้ให้ AI นับ
   ============================================================ */
let aiOverviews = {};      // รหัสรอบงาน => ภาพรวมที่เคยสร้างไว้
let ovRunning   = false;

async function loadPhaseOverviews() {
  const box = document.getElementById('ovBox');
  if (!box) return;
  try {
    const res  = await fetch('api.php?action=get_ai_phase_overview');
    const data = await res.json();
    aiOverviews = (data.success && data.overviews) ? data.overviews : {};
  } catch (err) {
    aiOverviews = {};
  }
  paintPhaseOverview();
}

// จำนวนฉบับที่ตรวจแล้วในรอบนั้น (ใช้บอกครูว่าพร้อมให้เขียนภาพรวมหรือยัง)
function ovReviewedCount(phase) {
  return (aiOverviewList || []).filter(r => r.essay_phase === phase).length;
}

function paintPhaseOverview() {
  const box = document.getElementById('ovBox');
  const sel = document.getElementById('ovPhase');
  if (!box || !sel) return;
  const phase = sel.value;
  const ov    = aiOverviews[phase];
  const done  = ovReviewedCount(phase);

  const hint = document.getElementById('ovHint');
  if (hint) {
    hint.innerHTML = done < 2
      ? '<i class="bi bi-exclamation-circle me-1"></i>รอบนี้ตรวจแล้ว ' + done + ' ฉบับ (ต้องมีอย่างน้อย 2 ฉบับ)'
      : '<i class="bi bi-check2-circle me-1"></i>รอบนี้ตรวจแล้ว ' + done + ' ฉบับ พร้อมเขียนภาพรวม';
  }
  const btn = document.getElementById('ovRunBtn');
  if (btn && !ovRunning) {
    btn.disabled = (done < 2);
    btn.innerHTML = '<i class="bi bi-stars me-1"></i>' + (ov ? 'เขียนภาพรวมรอบนี้ใหม่' : 'ให้ AI เขียนภาพรวมรอบนี้');
  }

  if (!ov) {
    box.innerHTML = `<div class="text-center text-muted py-4">
      <i class="bi bi-journal-text fs-3 d-block mb-2 opacity-50"></i>
      ยังไม่เคยให้ AI เขียนภาพรวมของรอบนี้
      ${AI_IS_TEACHER
        ? (done < 2
            ? '<div class="small mt-2">ให้ AI ตรวจเรียงความในรอบนี้อย่างน้อย 2 ฉบับก่อน</div>'
            : '<div class="small mt-2">กดปุ่ม &quot;ให้ AI เขียนภาพรวมรอบนี้&quot; ด้านบนได้เลย</div>')
        : '<div class="small mt-2">รอคุณครูสั่งให้ AI เขียนภาพรวมของรอบนี้</div>'}
    </div>`;
    return;
  }
  box.innerHTML = overviewHTML(ov);
}

// รายการหัวข้อย่อยแบบมีสัญลักษณ์นำ
function ovList(items, icon, color) {
  if (!items || !items.length) return '<div class="text-muted small">— ไม่มีข้อมูล —</div>';
  return items.map(t => `<div class="d-flex align-items-start gap-2 mb-2">
    <i class="bi ${icon} mt-1" style="color:${color};"></i>
    <span class="small" style="line-height:1.8;">${esc(t)}</span>
  </div>`).join('');
}

function overviewHTML(ov) {
  const st = ov.stats || {};
  const when = ov.updated_at ? String(ov.updated_at).replace('T', ' ').slice(0, 16) : '';

  // แถบตัวเลขของรอบนั้น — ระบบคำนวณเอง ไม่ได้มาจาก AI
  const pair = st.pair;
  const chips = [
    `<span class="ai-progress-chip"><span class="text-muted">ตรวจแล้ว</span>
       <span class="fw-bold">${st.n || ov.essay_count || 0} ฉบับ</span></span>`,
    `<span class="ai-progress-chip"><span class="text-muted">คะแนนเฉลี่ย</span>
       <span class="fw-bold">${aiNum(st.mean)}</span><span class="text-muted">/ ${aiNum(st.max_score)}</span></span>`,
    st.mean_words ? `<span class="ai-progress-chip"><span class="text-muted">ความยาวเฉลี่ย</span>
       <span class="fw-bold">${st.mean_words} คำ</span></span>` : '',
    pair ? `<span class="ai-progress-chip"><span class="text-muted">เทียบ ${esc(AI_PHASE_SHORT_MAP[pair.base_phase] || pair.base_phase)}</span>
       ${aiDeltaBadge(pair.mean_delta)}
       <span class="text-muted">ดีขึ้น ${pair.improved}/${pair.n} ฉบับ</span></span>` : ''
  ].filter(Boolean).join('');

  const themes = (ov.themes || []).map((t, i) => `
    <div class="ov-theme p-3 rounded-3 mb-2">
      <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
        <span class="fw-bold text-dark small">
          <span class="badge bg-warning-subtle text-warning-emphasis me-1">แนวที่ ${i + 1}</span>${esc(t.theme)}
        </span>
        ${t.how_many ? `<span class="badge bg-light text-secondary border text-nowrap">${esc(t.how_many)}</span>` : ''}
      </div>
      ${t.example ? `<div class="small text-muted mt-1"><i class="bi bi-quote me-1"></i>${esc(t.example)}</div>` : ''}
    </div>`).join('') || '<div class="text-muted small">— ไม่มีข้อมูล —</div>';

  return `
    <div class="d-flex flex-wrap gap-2 mb-3">${chips}</div>

    <div class="alert border-0 rounded-3" style="background:#fffbeb; border-left:4px solid #f59e0b !important;">
      <div class="fw-bold text-dark mb-1"><i class="bi bi-eye me-2"></i>ภาพรวมการนำเสนอของทั้งชั้น</div>
      <div class="text-dark" style="line-height:1.9;">${esc(ov.overview)}</div>
    </div>

    <h6 class="fw-bold text-dark mt-4 mb-2"><i class="bi bi-signpost-split text-warning me-2"></i>นักเรียนนำเสนอไปทางใดบ้าง</h6>
    ${themes}

    <div class="row g-4 mt-1">
      <div class="col-lg-6">
        <h6 class="fw-bold text-primary mb-2"><i class="bi bi-lightbulb-fill me-2"></i>ประเด็นที่น่าสนใจ</h6>
        ${ovList(ov.interesting, 'bi-stars', '#6d28d9')}
      </div>
      <div class="col-lg-6">
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-clipboard2-data me-2"></i>ข้อสังเกตต่อผลวิจัย</h6>
        ${ovList(ov.observations, 'bi-graph-up', '#0d7377')}
      </div>
    </div>

    <div class="row g-4 mt-1">
      <div class="col-lg-6">
        <h6 class="fw-bold text-success mb-2"><i class="bi bi-hand-thumbs-up-fill me-2"></i>จุดที่ทั้งชั้นทำได้ดี</h6>
        ${ovList(ov.common_strengths, 'bi-check-circle-fill', '#16a34a')}
      </div>
      <div class="col-lg-6">
        <h6 class="fw-bold text-warning-emphasis mb-2"><i class="bi bi-tools me-2"></i>จุดบกพร่องที่พบซ้ำทั้งชั้น</h6>
        ${ovList(ov.common_problems, 'bi-exclamation-triangle-fill', '#d97706')}
      </div>
    </div>

    ${(ov.teaching_notes && ov.teaching_notes.length) ? `
    <h6 class="fw-bold text-primary mt-4 mb-2"><i class="bi bi-mortarboard-fill me-2"></i>สิ่งที่ควรทำต่อในคาบถัดไป</h6>
    <div class="row row-cols-1 row-cols-md-2 g-2">
      ${ov.teaching_notes.map((t, i) => `<div class="col"><div class="ai-summary-step p-3 rounded-3 h-100 small">
        <span class="badge bg-primary-subtle text-primary-emphasis me-1">${i + 1}</span>${esc(t)}</div></div>`).join('')}
    </div>` : ''}

    <div class="alert border-0 rounded-3 mt-4 mb-0 small" style="background:#f1f5f9;">
      <i class="bi bi-exclamation-circle me-1"></i>
      <strong>ข้อสังเกตนี้ไม่ใช่ผลทดสอบทางสถิติ</strong> — ตัวเลขในกล่องนี้เป็นค่าบรรยาย (ค่าเฉลี่ย จำนวนฉบับที่ดีขึ้น)
      ที่ระบบคำนวณเอง ส่วนการทดสอบนัยสำคัญด้วย <strong>Paired t-test</strong> อยู่ในหน้า
      <a href="research_analysis.php" class="alert-link">วิเคราะห์สถิติงานวิจัย</a> ให้อ้างอิงค่าจากหน้านั้นในการรายงานผล
    </div>

    <div class="text-muted mt-3" style="font-size:0.75rem;">
      <i class="bi bi-cpu me-1"></i>เขียนโดยโมเดล ${esc(ov.model || '-')} (${esc(ov.provider || '-')})
      ${when ? ' · เมื่อ ' + esc(when) : ''} · จากผลตรวจ ${ov.essay_count || 0} ฉบับ
    </div>`;
}

<?php if ($aiIsTeacher): ?>
async function runPhaseOverview() {
  if (ovRunning) return;
  const sel   = document.getElementById('ovPhase');
  const phase = sel.value;
  const done  = ovReviewedCount(phase);
  if (done < 2) { showToast('รอบนี้ยังมีผลตรวจไม่พอสำหรับเขียนภาพรวม', 'error'); return; }
  if (aiOverviews[phase] && !confirm(`เขียนภาพรวมของ "${AI_PHASE_LABELS[phase]}" ใหม่ทับของเดิมใช่ไหม?`)) return;

  ovRunning = true;
  const btn = document.getElementById('ovRunBtn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>กำลังเขียน...'; }
  document.getElementById('ovBox').innerHTML =
    aiLoadingHTML('AI กำลังอ่านงานทั้งชั้นแล้วเขียนภาพรวม', 'ปกติใช้เวลาประมาณ 20-60 วินาที กรุณาอย่าปิดหน้านี้');

  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'ai_phase_overview', essay_phase: phase })
    });
    const data = await res.json();
    if (!data.success) {
      showToast(data.error || 'เขียนภาพรวมไม่สำเร็จ', 'error');
      document.getElementById('ovBox').innerHTML = aiErrorHTML(data.error || 'เขียนภาพรวมไม่สำเร็จ');
      return;
    }
    aiOverviews[phase] = data.overview;
    if (typeof data.quota_left === 'number' && aiStatus) {
      aiStatus.quota_left = data.quota_left;
      aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
    }
    showToast('เขียนภาพรวมของรอบนี้เรียบร้อยแล้ว');
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
    document.getElementById('ovBox').innerHTML = aiErrorHTML('เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
  } finally {
    ovRunning = false;
    paintPhaseOverview();
    updateReviewButton();
  }
}
<?php endif; ?>

// การ์ด 1 ใบต่อ 1 รอบงาน — เห็นคะแนนของตัวเองครบทุกฉบับในหน้าเดียว
function paintPhaseCards() {
  const box = document.getElementById('aiPhaseCards');
  if (!box) return;
  const sid = currentStudentId();
  // ยังโหลดผลตรวจของนักเรียนไม่เสร็จ → ปล่อยข้อความ "กำลังโหลด" ไว้
  if (!aiCardsReady && sid) return;

  const blocked = reviewBlockReason();

  const studentCards = (aiCardsReady && sid) ? AI_CARD_GROUPS.map(g => {
    const phases = g.phases.filter(ph => AI_PHASES.indexOf(ph) >= 0);
    if (!phases.length) return '';

    // แถวก่อน-หลังเรียน: ถ้ามีผลตรวจครบทั้งคู่ ให้สรุปพัฒนาการไว้ที่หัวกลุ่มเลย
    let extra = '';
    if (g.key === 'test') {
      const pre  = aiCombinedOf(aiAllFeedback['pretest']);
      const post = aiCombinedOf(aiAllFeedback['posttest']);
      if (pre !== null && post !== null) {
        const diff = Math.round((post - pre) * 100) / 100;
        const up   = diff > 0, same = (diff === 0);
        extra = `<span class="badge rounded-pill px-3 py-2 ${same ? 'bg-secondary-subtle text-secondary-emphasis'
                  : (up ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis')}">
          <i class="bi ${same ? 'bi-dash-lg' : (up ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow')} me-1"></i>
          พัฒนาการหลังเรียน ${same ? 'เท่าเดิม' : (up ? '+' : '') + aiNum(diff) + ' คะแนน'}</span>`;
      }
    }

    const cards = phases.map(ph => phaseCardHTML(ph, blocked)).join('');
    return `<div class="mb-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <span class="fw-bold text-secondary small text-uppercase">
          <i class="bi ${g.icon} me-1"></i>${esc(g.title)}
        </span>
        ${extra}
      </div>
      <div class="row ${g.cols} g-3">${cards}</div>
    </div>`;
  }).join('')
  : `<div class="mb-4">${emptyBox('เลือกนักเรียนด้านบนเพื่อดูผลตรวจของ AI ทุกรอบงาน')}</div>`;

  box.innerHTML = studentCards;

  paintPersonStrip();     // แถบสรุปย่อใช้ข้อมูลชุดเดียวกัน วาดพร้อมกันเสมอ
  paintClassAverages();   // การ์ดค่าเฉลี่ยทั้งชั้นมีบรรทัดเทียบกับนักเรียนคนที่เลือกอยู่ด้วย
}

// การ์ดคะแนนของรอบงานหนึ่ง
function phaseCardHTML(ph, blocked) {
  const fb    = aiAllFeedback[ph];
  const essay = aiEssayStatus[ph];
  const label = AI_PHASE_LABELS[ph] || ph;
  const on    = (selectedPhase === ph);

  let body, foot = '', cls = 'ai-phase-card', badges = '';

  if (fb) {
    // บรรทัดเทียบกับฉบับตั้งต้นตามคู่ที่ครูกำหนด — เห็นตั้งแต่บนการ์ด ไม่ต้องคลิกเข้าไปดู
    const dc = fb.draft_compare;
    let draftLine = '';
    if (dc && dc.pairable) {
      if (dc.has_baseline) {
        const okCls = dc.delta > 0 ? 'text-success-emphasis'
          : (dc.delta === 0 ? 'text-warning-emphasis' : 'text-danger-emphasis');
        draftLine = `<div class="small mt-2 d-flex align-items-center gap-2 flex-wrap">
          <span class="text-muted"><i class="bi bi-arrow-left-right me-1"></i>เทียบ ${esc(dc.short)}:</span>
          ${aiDeltaBadge(dc.delta)}
          <span class="${okCls} fw-semibold">${dc.delta > 0 ? 'ดีขึ้น' : (dc.delta === 0 ? 'ยังไม่ดีขึ้น' : 'ถอยลง')}</span>
        </div>`;
      } else {
        draftLine = `<div class="small mt-2 text-muted">
          <i class="bi bi-arrow-left-right me-1"></i>ต้องเทียบกับ ${esc(dc.short || '')} — ฉบับนั้นยังไม่มีผลตรวจ
        </div>`;
      }
    }
    const fullMax  = Number(fb.full_max || fb.max_score || 60);
    const combined = aiCombinedOf(fb);
    const pct      = fullMax > 0 ? Math.round((combined / fullMax) * 100) : 0;
    const level    = (fb.manual_done && fb.full_quality_level) ? fb.full_quality_level : (fb.quality_level || '-');
    if (!fb.manual_done) {
      badges += '<span class="badge bg-warning text-dark">รอคะแนนครู</span>';
    }
    if (fb.needs_recheck) {
      badges += '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning">'
              + '<i class="bi bi-arrow-repeat me-1"></i>รอตรวจใหม่</span>';
    }
    // ตรวจซ้ำแล้วกี่ครั้ง — คลิกเข้าไปดูรายละเอียดจะเห็นว่ารอบนี้เปลี่ยนจากครั้งก่อนตรงไหน
    if (Number(fb.review_round || 1) > 1) {
      badges += `<span class="badge ai-round-badge">ตรวจครั้งที่ ${Number(fb.review_round)}</span>`;
    }
    body = `<div class="d-flex align-items-end gap-2">
        <div class="display-6 fw-bold text-primary lh-1">${aiNum(combined)}</div>
        <div class="text-muted pb-1">/ ${aiNum(fullMax)} คะแนน</div>
      </div>
      <div class="ai-score-bar mt-2"><span style="width:${pct}%"></span></div>
      <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-1">
        <span class="badge bg-primary-subtle text-primary-emphasis">ระดับ${fb.manual_done ? '' : 'โดยประมาณ'}: ${esc(level)}</span>
        <span class="text-muted small">AI ${aiNum(fb.total_score)}/${aiNum(fb.max_score)}
          · ครู ${fb.manual_done ? aiNum(fb.teacher_total) : '—'}/${aiNum(fb.manual_max)}</span>
      </div>
      ${draftLine}`;
    foot = `<span class="fw-semibold text-primary"><i class="bi bi-card-list me-1"></i>${on ? 'กำลังแสดงรายละเอียด' : 'ดูรายละเอียดการให้คะแนน'}</span>`;
  } else if (essay) {
    cls += ' ai-phase-card-empty';
    body = `<div class="text-muted"><i class="bi bi-hourglass-split me-1"></i>เขียนแล้ว ${essay.word_count} คำ · ยังไม่มีผลตรวจ</div>`
         + (essay.too_short ? `<div class="small text-warning-emphasis mt-1">
              <i class="bi bi-exclamation-triangle me-1"></i>สั้นกว่าเกณฑ์ ยังส่งให้ AI ตรวจไม่ได้</div>` : '');
  } else {
    cls += ' ai-phase-card-empty';
    body = '<div class="text-muted"><i class="bi bi-file-earmark-x me-1"></i>ยังไม่ได้เขียนเรียงความรอบนี้</div>';
  }

  // ปุ่มสั่งตรวจของคุณครู อยู่บนการ์ดแต่ละใบ (ไม่ต้องเลือกรอบงานจากช่องเลือกอีก)
  let reviewBtn = '';
  if (AI_IS_TEACHER && essay && !essay.too_short) {
    const dis = blocked ? ' disabled' : '';
    reviewBtn = `<button class="btn btn-sm rounded-pill px-3 fw-bold text-white ai-phase-review-btn"
            style="background:linear-gradient(135deg,#6d28d9,#0d7377);"${dis}
            title="${esc(blocked || 'ส่งเรียงความรอบนี้ให้ AI ตรวจ')}"
            onclick="event.stopPropagation(); runAiReview('${esc(ph)}')">
      <i class="bi bi-stars me-1"></i>${fb ? 'ตรวจใหม่' : 'ให้ AI ตรวจ'}</button>`;
  }

  return `<div class="col">
    <div class="${cls}${on ? ' ai-phase-card-active' : ''} h-100 p-3 rounded-4"
         ${fb ? `role="button" tabindex="0" onclick="selectPhase('${esc(ph)}')"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectPhase('${esc(ph)}');}"` : ''}>
      <div class="d-flex align-items-start justify-content-between gap-2 mb-2 flex-wrap">
        <span class="fw-bold text-dark">${esc(label)}${aiStyleChip(ph)}</span>
        <span class="d-flex gap-1 flex-wrap">${badges}</span>
      </div>
      ${body}
      <div class="d-flex align-items-center justify-content-between gap-2 mt-3 flex-wrap">
        <span class="small">${foot}</span>
        ${reviewBtn}
      </div>
    </div>
  </div>`;
}

// ป้ายเล็ก ๆ บอกชนิดงานเขียนของรอบนั้น (หน่วยที่ 1 มีคำสำคัญกำกับใน tooltip ด้วย)
function aiStyleChip(ph) {
  const st = AI_PHASE_STYLE[ph];
  if (!st) return '';
  const tip = st.keys && st.keys.length
    ? `${st.name} · ต้องกล่าวถึงและเชื่อมโยง: ${st.keys.join(' + ')}`
    : `${st.name} — ต้องแสดงทรรศนะและการประเมินค่า ไม่ใช่เล่าเรื่องเฉย ๆ`;
  const cls = st.kind === 'critical' ? 'bg-primary-subtle text-primary-emphasis' : 'bg-info-subtle text-info-emphasis';
  return ` <span class="badge rounded-pill fw-normal ${cls}" style="font-size:0.66rem;"
                 title="${esc(tip)}">${esc(st.name.replace('เรียงความ', ''))}</span>`;
}

// คลิกการ์ด → เปิด/ปิดรายละเอียดของรอบนั้น (คลิกใบเดิมซ้ำ = ปิด)
function selectPhase(phase) {
  selectedPhase = (selectedPhase === phase) ? '' : phase;
  paintPhaseCards();
  paintSelectedFeedback();
  if (selectedPhase) {
    const panel = document.getElementById('aiFeedbackPanel');
    if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// วาดรายละเอียดของฉบับที่เลือกไว้ (ไม่ได้เลือก = ไม่แสดงอะไร ให้การ์ดเป็นพระเอก)
function paintSelectedFeedback() {
  const panel = document.getElementById('aiFeedbackPanel');
  if (!panel) return;
  const fb = selectedPhase ? aiAllFeedback[selectedPhase] : null;
  if (!fb) { panel.innerHTML = ''; return; }
  renderFeedback(fb);
}

function emptyBox(msg) {
  return `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">${aiEmptyHTML(msg)}</div></div>`;
}

// วาดรายละเอียดของฉบับที่เลือกลงในการ์ดใหญ่ใต้แถวการ์ดคะแนน (ครูเห็นปุ่มลบด้วย)
function renderFeedback(fb) {
  const phase = fb.essay_phase || selectedPhase;
  document.getElementById('aiFeedbackPanel').innerHTML =
    `<div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #6d28d9 !important;">
       <div class="card-header bg-white border-bottom py-2 px-4 rounded-top-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
         <span class="fw-bold text-dark small">
           <i class="bi bi-card-list text-primary me-2"></i>รายละเอียดการให้คะแนนและข้อมูลย้อนกลับ ·
           ${esc(fb.phase_label || AI_PHASE_LABELS[phase] || phase)}
         </span>
         <button class="btn btn-link btn-sm text-secondary text-decoration-none p-0" onclick="selectPhase('${esc(phase)}')">
           <i class="bi bi-x-lg me-1"></i>ปิดรายละเอียด
         </button>
       </div>
       <div class="card-body p-4">`
    + aiFeedbackHTML(fb, {
        deleteAction:  AI_IS_TEACHER ? 'deleteFeedback()'  : '',
        manualAction:  AI_IS_TEACHER ? 'saveManualScores()' : '',
        recheckAction: AI_IS_TEACHER ? `runAiReview('${phase}')` : '',
        critEditFn:    AI_IS_TEACHER ? 'aiOpenCritPanel' : ''
      })
    + `</div></div>`;
}

// -------------------------------------------------------------- สั่งให้ตรวจ
// เรียกจากปุ่มบนการ์ดของรอบงานนั้น ๆ หรือจากปุ่ม "ตรวจใหม่" ในแถบเตือนของรายละเอียด
let aiReviewRunning = false;

async function runAiReview(phase) {
  const sid = currentStudentId();
  phase = phase || currentPhase();
  if (!sid)   { showToast('กรุณาเลือกนักเรียนก่อน', 'error'); return; }
  if (!phase) { showToast('กรุณาเลือกรอบงานที่จะตรวจ', 'error'); return; }
  if (aiReviewRunning) return;

  // รอบที่ต้องเทียบกับฉบับตั้งต้น (D1.2 / D2.2 / หลังเรียน) — ถ้าฉบับตั้งต้นยังไม่ถูกตรวจ
  // ระบบจะเทียบคะแนนรายข้อให้ไม่ได้ และ AI ก็ไม่มีคะแนนของฉบับตั้งต้นไว้ยึดเป็นจุดตั้งต้นด้วย
  // ร่างหลังจึงอาจได้คะแนนต่ำกว่าร่างแรกทั้งที่นักเรียนแก้งานมาแล้ว — เตือนให้ตรวจฉบับตั้งต้นก่อน
  const basePh = AI_BASELINE_PAIRS[phase];
  if (basePh && !aiAllFeedback[basePh]) {
    const ok = confirm(`รอบ "${AI_PHASE_LABELS[phase]}" ต้องเทียบกับ "${AI_PHASE_LABELS[basePh]}" เสมอ\n`
      + `แต่ ${AI_PHASE_SHORT_MAP[basePh]} ยังไม่มีผลตรวจของ AI\n\n`
      + `ถ้าตรวจตอนนี้ AI จะไม่มีคะแนนของฉบับตั้งต้นไว้ยึดเป็นจุดตั้งต้น คะแนนของร่างหลัง\n`
      + `จึงอาจออกมาต่ำกว่าร่างแรกทั้งที่นักเรียนแก้งานมาแล้ว และจะไม่มีผลเทียบรายข้อให้ดูด้วย\n\n`
      + `แนะนำให้กด "ยกเลิก" แล้วสั่งตรวจ ${AI_PHASE_SHORT_MAP[basePh]} ก่อน `
      + `หรือกด "ตกลง" ถ้าต้องการตรวจรอบนี้ต่อไป`);
    if (!ok) return;
  }

  // ตรวจใหม่ทั้งฉบับ = AI อ่านงานใหม่หมดทุกข้อ คะแนนที่ครูปรับไว้รายข้อจึงถูกล้างทิ้ง
  const ovCount = Number((aiAllFeedback[phase] || {}).override_count || 0);
  if (ovCount > 0) {
    const ok = confirm(`ฉบับนี้มีคะแนนที่ผ่านการตรวจทานของครูไว้แล้ว ${ovCount} ข้อ\n`
      + `การให้ AI ตรวจใหม่ทั้งฉบับจะล้างคะแนนที่ปรับไว้ทั้งหมด แล้วใช้คะแนนชุดใหม่ของ AI แทน\n\n`
      + `ถ้าต้องการแก้เฉพาะบางข้อ ให้กด "ยกเลิก" แล้วใช้ปุ่มปรับคะแนนท้ายแถวในตารางคะแนนรายเกณฑ์แทน`);
    if (!ok) return;
  }

  aiReviewRunning = true;
  selectedPhase = phase;
  document.querySelectorAll('.ai-phase-review-btn').forEach(b => { b.disabled = true; });
  document.getElementById('aiFeedbackPanel').innerHTML =
    `<div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body">`
    + aiLoadingHTML('AI กำลังอ่านเรียงความและเขียนข้อเสนอแนะ', 'ปกติใช้เวลาประมาณ 15-40 วินาที กรุณาอย่าปิดหน้านี้')
    + `</div></div>`;

  try {
    const data = await aiRequestReview(sid, phase);
    if (!data.success) {
      showToast(data.error || 'ตรวจไม่สำเร็จ', 'error');
      document.getElementById('aiFeedbackPanel').innerHTML = aiErrorHTML(data.error || 'ตรวจไม่สำเร็จ');
      return;
    }
    if (aiStatus) {
      aiStatus.quota_left = data.quota_left;
      aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
    }
    aiAllFeedback[phase] = data.feedback;
    aiAttachDraftCompare(aiAllFeedback);
    paintPhaseCards();
    renderFeedback(data.feedback);
    showToast('AI ตรวจเรียงความเรียบร้อยแล้ว');
    if (!AI_IS_STUDENT) { loadAiOverview(); loadRecheckQueue(); }
  } finally {
    aiReviewRunning = false;
    updateReviewButton();
  }
}

// ครูบันทึกคะแนนข้อที่ AI ตรวจแทนไม่ได้ (เช่น 4.3 ความเรียบร้อย/ลายมือ) — รวมกับคะแนน AI ให้ครบเต็ม 60
async function saveManualScores() {
  // การ์ดเกณฑ์เป็นปุ่มวิทยุแบบเดียวกับหน้า evaluation.php — เก็บเฉพาะข้อที่คุณครูเลือกไว้จริง
  const scores = {};
  document.querySelectorAll('.ai-manual-input:checked').forEach(el => {
    scores[el.dataset.manualId] = Number(el.value);
  });

  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save_ai_manual_score',
        student_id: currentStudentId(),
        essay_phase: currentPhase(),
        scores
      })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'บันทึกคะแนนไม่สำเร็จ', 'error'); return; }
    if (data.feedback) {
      aiAllFeedback[data.feedback.essay_phase] = data.feedback;
      paintPhaseCards();
      renderFeedback(data.feedback);
    }
    showToast(Object.keys(scores).length
      ? 'บันทึกคะแนนของครูเรียบร้อยแล้ว'
      : 'ล้างคะแนนที่กรอกในหน้านี้แล้ว — ถ้ามีคะแนนในแบบประเมิน ระบบจะกลับไปใช้ค่านั้นให้');
    loadAiOverview();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}

async function deleteFeedback() {
  if (!confirm('ยืนยันลบผลตรวจของ AI สำหรับเรียงความฉบับนี้?')) return;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete_ai_feedback', student_id: currentStudentId(), essay_phase: currentPhase() })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'ลบไม่สำเร็จ', 'error'); return; }
    showToast('ลบผลตรวจเรียบร้อยแล้ว');
    selectedPhase = '';
    loadFeedback();
    loadAiOverview();
    loadRecheckQueue();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}

// ------------------------------------------------- ปรับคะแนนรายข้อ (เฉพาะครู)
// คุณครูไม่เห็นด้วยกับคะแนนที่ AI ให้ข้อไหน เปิดหน้าต่างนี้จากปุ่มท้ายแถวในตารางคะแนนรายเกณฑ์
// ทำได้ 2 ทาง: ปรับคะแนนเองพร้อมเหตุผล หรือสั่งให้ AI ตรวจเฉพาะข้อนั้นใหม่ (ใส่คำสั่งเพิ่มเติมได้)
// ไม่ว่าทางไหน คะแนนที่ AI ให้ครั้งแรกยังถูกเก็บไว้เสมอ กดคืนค่าได้ตลอด
let aiCritId      = '';    // รหัสข้อที่กำลังเปิดหน้าต่างอยู่
let aiCritBusy    = false; // กันกดซ้ำระหว่างรอผลจาก AI

function aiCritItem(id) {
  return (AI_RUBRIC_ITEMS || []).find(it => String(it.id) === String(id)) || null;
}

// เกณฑ์ในระบบเก็บเป็นข้อความเดียว "4=..., 3=..., 2=..." — แยกออกมาเป็นคำอธิบายรายระดับ
function aiSplitGuide(guide) {
  const out = {};
  String(guide || '').split(/,\s*(?=[0-4]\s*=)/).forEach(part => {
    const m = part.match(/^\s*([0-4])\s*=\s*([\s\S]*)$/);
    if (m) out[m[1]] = m[2].trim();
  });
  return out;
}

function aiOpenCritPanel(critId) {
  const fb = selectedPhase ? aiAllFeedback[selectedPhase] : null;
  const it = aiCritItem(critId);
  const c  = (fb && fb.scores) ? fb.scores[critId] : null;
  if (!fb || !it || !c) { showToast('ไม่พบคะแนนของข้อนี้', 'error'); return; }

  aiCritId = critId;
  const modalEl = document.getElementById('aiCritModal');
  if (!modalEl) return;

  const ov     = c.overridden ? (c.override || {}) : null;
  const curRaw = ov ? Number(ov.raw) : Number(c.raw);
  const guide  = aiSplitGuide(it.guide);

  document.getElementById('aiCritTitle').textContent = `ข้อ ${it.id} ${it.name}`;
  document.getElementById('aiCritSubtitle').innerHTML =
    `${esc(fb.phase_label || AI_PHASE_LABELS[selectedPhase] || '')}`
    + `${fb.student_name ? ' · ' + esc(fb.student_name) : ''}`
    + ` · คะแนนเต็มข้อนี้ ${it.max} คะแนน (คะแนนดิบ 0-4 คูณ ${it.multiplier})`;

  // การ์ดเลือกระดับคะแนน 0-4 หน้าตาเดียวกับแบบประเมินของครู
  const cards = [4, 3, 2, 1, 0].map(n => `
    <div class="col">
      <input type="radio" name="aiCritRaw" value="${n}" id="aiCritRaw_${n}"
             class="score-radio ai-crit-radio"${Number(curRaw) === n ? ' checked' : ''}>
      <label for="aiCritRaw_${n}" class="rubric-card w-100 text-start">
        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
          <span class="fw-bold fs-6 text-dark">${n} <span class="text-muted fw-normal small">= ${aiNum1(n * it.multiplier)} คะแนน</span></span>
          <div class="check-circle"><i class="bi bi-check-lg"></i></div>
        </div>
        <p class="text-secondary mb-0" style="font-size:13px; line-height:1.5;">${esc(guide[n] || '')}</p>
      </label>
    </div>`).join('');

  const aiWeighted = ov ? Number(c.ai_weighted) : Number(c.weighted);
  const aiRaw      = ov ? Number(c.ai_raw)      : Number(c.raw);
  const aiReason   = ov ? String(c.ai_reason || '') : String(c.reason || '');

  document.getElementById('aiCritBody').innerHTML = `
    <div class="p-3 rounded-3 mb-4" style="background:#f8fafc; border-left:4px solid #6d28d9;">
      <div class="fw-bold text-dark mb-1"><i class="bi bi-robot me-2 text-primary"></i>คะแนนที่ AI ให้ไว้ครั้งแรก</div>
      <div class="fs-5 fw-bold text-primary">${aiNum1(aiRaw)} <span class="text-muted fs-6 fw-normal">(= ${aiNum(aiWeighted)} / ${it.max} คะแนน)</span></div>
      ${aiReason ? `<div class="text-muted small mt-2">${esc(aiReason)}</div>` : ''}
      ${ov ? `<hr class="my-2">
        <div class="small">
          <span class="badge ai-ov-badge ${ov.source === 'ai_recheck' ? 'ai-ov-recheck' : 'ai-ov-teacher'}">
            ${ov.source === 'ai_recheck' ? 'AI ตรวจข้อนี้ใหม่' : 'ครูปรับคะแนน'}</span>
          <span class="fw-bold ms-2">คะแนนที่ใช้อยู่ตอนนี้: ${aiNum1(ov.raw)} (= ${aiNum(ov.weighted)} / ${it.max})</span>
          ${ov.reason ? `<div class="text-muted mt-1">${esc(ov.reason)}</div>` : ''}
          ${ov.instruction ? `<div class="text-muted mt-1"><span class="fw-semibold">คำสั่งที่ครูให้ AI:</span> <span class="fst-italic">${esc(ov.instruction)}</span></div>` : ''}
        </div>` : ''}
    </div>

    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-pencil-square me-2"></i>1. ปรับคะแนนข้อนี้เอง</h6>
    <div class="text-muted small mb-3">เลือกระดับคะแนนดิบที่คุณครูเห็นว่าเหมาะสม แล้วเขียนเหตุผลกำกับไว้ (เหตุผลจะแสดงคู่กับคะแนนเสมอ)</div>
    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3 mb-3">${cards}</div>
    <div class="mb-3">
      <label class="form-label small fw-semibold text-dark" for="aiCritReason">เหตุผลที่ปรับคะแนน</label>
      <textarea id="aiCritReason" class="form-control rounded-3" rows="2" maxlength="800"
                placeholder="เช่น ย่อหน้าที่ 2 ยังอยู่ในขอบเขตของหัวข้อ AI ตัดคะแนนแรงเกินไป">${ov && ov.source !== 'ai_recheck' ? esc(ov.reason || '') : ''}</textarea>
    </div>
    <div class="d-flex justify-content-end gap-2 flex-wrap mb-4">
      ${ov ? `<button class="btn btn-outline-secondary rounded-pill px-3" onclick="aiClearCritOverride()">
        <i class="bi bi-arrow-counterclockwise me-1"></i>คืนค่าคะแนนของ AI
      </button>` : ''}
      <button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="aiSaveCritOverride()">
        <i class="bi bi-check2-circle me-1"></i>บันทึกคะแนนที่ปรับ
      </button>
    </div>

    <hr class="my-4">

    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-arrow-repeat me-2"></i>2. ให้ AI ตรวจเฉพาะข้อนี้ใหม่</h6>
    <div class="text-muted small mb-3">
      AI จะอ่านเรียงความทั้งฉบับอีกครั้งแต่ให้คะแนน<strong>เฉพาะข้อ ${esc(it.id)}</strong> ข้ออื่นไม่ขยับ
      · ระบบไม่บอกคะแนนเดิมให้ AI รู้ เพื่อให้ตรวจใหม่แบบสด ๆ · ใช้โควตา 1 ครั้ง
    </div>
    <div class="mb-3">
      <label class="form-label small fw-semibold text-dark" for="aiCritInstr">คำสั่งเพิ่มเติมถึง AI (ไม่ใส่ก็ได้)</label>
      <textarea id="aiCritInstr" class="form-control rounded-3" rows="3" maxlength="800"
                placeholder="เช่น ให้ดูย่อหน้าที่ 3 ด้วย นักเรียนยกตัวอย่างไว้แล้ว / ข้อนี้ให้นับเฉพาะคำเชื่อมที่ใช้ผิด ไม่ต้องนับคำซ้ำ"></textarea>
      <div class="form-text">คำสั่งนี้ใช้กับการตรวจข้อนี้ครั้งนี้เท่านั้น และถูกบันทึกไว้ให้ตรวจสอบย้อนหลังได้</div>
    </div>
    <div class="d-flex justify-content-end">
      <button class="btn btn-success rounded-pill px-4 fw-bold" id="aiCritRecheckBtn" onclick="aiRecheckCriterion()">
        <i class="bi bi-stars me-1"></i>ให้ AI ตรวจข้อนี้ใหม่
      </button>
    </div>`;

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function aiCloseCritPanel() {
  const modalEl = document.getElementById('aiCritModal');
  if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
}

// นำผลที่ได้กลับมาวาดใหม่ทั้งการ์ดคะแนนและรายละเอียด
function aiApplyCritResult(fb) {
  if (!fb) return;
  aiAllFeedback[fb.essay_phase] = fb;
  aiAttachDraftCompare(aiAllFeedback);
  paintPhaseCards();
  renderFeedback(fb);
  loadAiOverview();
}

async function aiSaveCritOverride() {
  const picked = document.querySelector('.ai-crit-radio:checked');
  if (!picked) { showToast('กรุณาเลือกระดับคะแนนก่อน', 'error'); return; }
  const reason = (document.getElementById('aiCritReason').value || '').trim();
  if (!reason) {
    if (!confirm('ยังไม่ได้เขียนเหตุผลที่ปรับคะแนน\n\nเหตุผลช่วยให้ย้อนกลับมาอธิบายได้ว่าทำไมคะแนนข้อนี้จึงต่างจากที่ AI ให้\nต้องการบันทึกโดยไม่ใส่เหตุผลหรือไม่?')) return;
  }
  await aiPostCritOverride({ raw: Number(picked.value), reason });
}

async function aiClearCritOverride() {
  if (!confirm('คืนค่าคะแนนข้อนี้กลับไปใช้คะแนนที่ AI ให้ไว้เดิมหรือไม่?')) return;
  await aiPostCritOverride({ raw: '', reason: '' });
}

async function aiPostCritOverride(payload) {
  if (aiCritBusy) return;
  aiCritBusy = true;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(Object.assign({
        action:      'save_ai_score_override',
        student_id:  currentStudentId(),
        essay_phase: selectedPhase,
        criterion:   aiCritId
      }, payload))
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'บันทึกไม่สำเร็จ', 'error'); return; }
    aiApplyCritResult(data.feedback);
    aiCloseCritPanel();
    showToast(data.cleared ? 'คืนค่าคะแนนของ AI เรียบร้อยแล้ว' : 'บันทึกคะแนนที่ปรับเรียบร้อยแล้ว');
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  } finally {
    aiCritBusy = false;
  }
}

async function aiRecheckCriterion() {
  if (aiCritBusy) return;
  const instruction = (document.getElementById('aiCritInstr').value || '').trim();
  const btn = document.getElementById('aiCritRecheckBtn');

  aiCritBusy = true;
  if (btn) {
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>AI กำลังตรวจข้อนี้...';
  }
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action:      'ai_recheck_criterion',
        student_id:  currentStudentId(),
        essay_phase: selectedPhase,
        criterion:   aiCritId,
        instruction
      })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'ตรวจไม่สำเร็จ', 'error'); return; }
    if (aiStatus && typeof data.quota_left === 'number') {
      aiStatus.quota_left = data.quota_left;
      aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
      paintStatusBar();
    }
    aiApplyCritResult(data.feedback);
    aiCloseCritPanel();
    showToast(`AI ตรวจข้อ ${aiCritId} ใหม่เรียบร้อยแล้ว`);
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  } finally {
    aiCritBusy = false;
    if (btn) {
      btn.disabled  = false;
      btn.innerHTML = '<i class="bi bi-stars me-1"></i>ให้ AI ตรวจข้อนี้ใหม่';
    }
  }
}

// สลับไปแท็บ "ผลตรวจรายบุคคล" (ไม่มีแท็บสำหรับนักเรียน จึงไม่ต้องทำอะไร)
function showPersonTab() {
  const btn = document.querySelector('.ai-main-tabs [data-bs-target="#tab-person"]');
  if (btn && window.bootstrap && bootstrap.Tab) bootstrap.Tab.getOrCreateInstance(btn).show();
}

function onSelectionChange() {
  selectedPhase = '';
  updateReviewButton();
  loadFeedback();
}

// ------------------------------------------ คิวรอตรวจใหม่ (ครู/ผชช.)
// นักเรียนกดบันทึกเรียงความที่เคยให้ AI ตรวจไปแล้ว → ฝั่งเซิร์ฟเวอร์ทำเครื่องหมายไว้ให้เอง
// หน้านี้จึงแค่ดึงรายการมาแสดง และให้คุณครูสั่งตรวจใหม่ทั้งคิวได้ในคลิกเดียว
let aiRecheckList = [];

async function loadRecheckQueue() {
  const card = document.getElementById('aiRecheckCard');
  if (!card) return;
  try {
    const res  = await fetch('api.php?action=get_ai_recheck_queue');
    const data = await res.json();
    aiRecheckList = (data.success && Array.isArray(data.queue)) ? data.queue : [];
  } catch (err) {
    aiRecheckList = [];
  }
  paintRecheckQueue();
}

function paintRecheckQueue() {
  const card = document.getElementById('aiRecheckCard');
  const list = document.getElementById('aiRecheckList');
  if (!card || !list) return;

  // คิวว่าง = ไม่ต้องรบกวนสายตา ซ่อนการ์ดทั้งใบ
  if (!aiRecheckList.length) {
    card.classList.add('d-none');
    list.innerHTML = '';
    return;
  }
  card.classList.remove('d-none');
  document.getElementById('aiRecheckCount').textContent = aiRecheckList.length;

  const ready = aiRecheckList.filter(r => !r.too_short).length;
  const btn = document.getElementById('rcStartBtn');
  if (btn) {
    btn.disabled = (ready === 0);
    btn.innerHTML = `<i class="bi bi-stars me-1"></i>ให้ AI ตรวจใหม่ทั้งคิว (${ready})`;
  }

  list.innerHTML = aiRecheckList.map(r => {
    const when = r.recheck_marked_at ? String(r.recheck_marked_at).replace('T', ' ').slice(0, 16) : '';
    return `<div class="px-4 py-2 border-bottom small d-flex align-items-center gap-2 flex-wrap ai-recheck-row"
                 onclick="jumpTo('${esc(r.student_id)}','${esc(r.essay_phase)}')">
      <span class="fw-semibold text-nowrap">${esc(r.student_id)}</span>
      <span class="flex-grow-1">${esc(r.student_name || '-')}${r.classroom
        ? ` <span class="badge bg-info-subtle text-info-emphasis">ห้อง ${esc(r.classroom)}</span>` : ''}</span>
      <span class="badge bg-light text-dark border text-nowrap">${esc(r.phase_label)}</span>
      ${r.too_short
        ? '<span class="badge bg-secondary-subtle text-secondary-emphasis text-nowrap">สั้นเกินเกณฑ์ ยังตรวจไม่ได้</span>'
        : '<span class="badge bg-warning text-dark text-nowrap">รอตรวจใหม่</span>'}
      ${when ? `<span class="text-muted text-nowrap">แก้เมื่อ ${esc(when)}</span>` : ''}
    </div>`;
  }).join('');
}

// ------------------------------------------------- ภาพรวมทั้งชั้น (ครู/ผชช.)
let aiOverviewList = [];   // ผลตรวจทั้งชั้นที่โหลดมาแล้ว (กรองในหน้าเว็บ ไม่ต้องยิง API ซ้ำ)

async function loadAiOverview() {
  const box = document.getElementById('aiOverviewBox');
  if (!box) return;
  try {
    const res  = await fetch('api.php?action=get_all_ai_feedback');
    const data = await res.json();
    if (!data.success) { box.innerHTML = `<div class="text-center text-muted py-4">${esc(data.error)}</div>`; return; }
    aiOverviewList = data.list || [];
    paintAiOverview();
    paintClassHighlights();   // ตัวเลขเด่นและการ์ดค่าเฉลี่ยใช้ข้อมูลชุดเดียวกันนี้
    paintClassAverages();
    paintPhaseOverview();     // ป้ายบอกจำนวนฉบับที่ตรวจแล้วอิงข้อมูลชุดนี้
  } catch (err) {
    box.innerHTML = '<div class="text-center text-muted py-4">โหลดข้อมูลไม่สำเร็จ</div>';
  }
}

// คอลัมน์ของตารางภาพรวม — จัดหัวตารางแบบเดียวกับ "รายงานการส่งงานรายบุคคล" ในหน้า submission_report.php
const AI_OVERVIEW_COLS = [
  { key: 'pretest',  label: 'ก่อนเรียน',  grp: '' },
  { key: 'task1_d1', label: 'D1.1',       grp: 'grp-unit1' },
  { key: 'task1_d2', label: 'D1.2',       grp: 'grp-unit1' },
  { key: 'task2_d1', label: 'D2.1',       grp: 'grp-unit2' },
  { key: 'task2_d2', label: 'D2.2',       grp: 'grp-unit2' },
  { key: 'posttest', label: 'หลังเรียน',  grp: '' },
];

// รวมผลตรวจรายฉบับให้เป็นรายบุคคล 1 แถว (คอลัมน์ละ 1 รอบงาน)
function aiOverviewByStudent() {
  const map = new Map();
  aiOverviewList.forEach(r => {
    let stu = map.get(r.student_id);
    if (!stu) {
      stu = {
        student_id:   r.student_id,
        student_name: r.student_name,
        classroom:    r.classroom,
        cells:        {},
      };
      map.set(r.student_id, stu);
    }
    stu.cells[r.essay_phase] = r;
  });
  return Array.from(map.values());
}

function paintAiOverview() {
  const box = document.getElementById('aiOverviewBox');
  if (!box) return;
  if (!aiOverviewList.length) {
    box.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-3 d-block mb-2"></i>ยังไม่มีผลตรวจของ AI</div>';
    return;
  }

  const kw        = (document.getElementById('aiOverviewSearch').value || '').trim().toLowerCase();
  const phase     = document.getElementById('aiOverviewPhase').value;
  const needScore = document.getElementById('aiOverviewNeedScore').checked;
  const needRecheckOnly = document.getElementById('aiOverviewNeedRecheck').checked;
  const focus     = document.getElementById('aiOverviewFocus').value;

  // เลือกรอบงานไว้ = ดูเฉพาะคอลัมน์นั้น (ค่าเฉลี่ยคิดจากคอลัมน์ที่แสดงอยู่)
  const cols = phase ? AI_OVERVIEW_COLS.filter(c => c.key === phase) : AI_OVERVIEW_COLS;
  const fullMax = aiOverviewList[0] ? aiOverviewList[0].full_max : 60;

  // ส่วนต่างเทียบกับฉบับตั้งต้นของฉบับหนึ่ง (null = ไม่มีคู่เทียบ หรือยังไม่ได้ตรวจฉบับตั้งต้น)
  const deltaOf = r => ((r.draft_delta === null || r.draft_delta === undefined) ? null : Number(r.draft_delta));

  const students = aiOverviewByStudent().filter(stu => {
    const shown = cols.map(c => stu.cells[c.key]).filter(Boolean);
    if (!shown.length) return false;                                   // ไม่มีผลตรวจในคอลัมน์ที่ดูอยู่
    if (needScore && !shown.some(r => !r.manual_done)) return false;   // ให้คะแนนครบแล้วทุกฉบับ
    if (needRecheckOnly && !shown.some(r => r.needs_recheck)) return false;  // ไม่มีฉบับที่รอตรวจใหม่

    // ตัวกรองแบบเจาะกลุ่ม — ดูเฉพาะคนที่เข้าเงื่อนไขในคอลัมน์ที่กำลังแสดงอยู่
    if (focus === 'down' && !shown.some(r => deltaOf(r) !== null && deltaOf(r) < 0)) return false;
    if (focus === 'flat' && !shown.some(r => deltaOf(r) === 0)) return false;
    if (focus === 'up'   && !shown.some(r => deltaOf(r) !== null && deltaOf(r) > 0)) return false;
    if (focus === 'low'  && !shown.some(r => ['พอใช้', 'ต้องปรับปรุง'].indexOf(r.quality_level) >= 0)) return false;
    if (focus === 'notdone' && shown.length >= cols.length) return false;
    if (focus === 'adjusted' && !shown.some(r => Number(r.override_count || 0) > 0)) return false;

    if (!kw) return true;
    return [stu.student_id, stu.student_name, stu.classroom].join(' ').toLowerCase().indexOf(kw) >= 0;
  });

  if (!students.length) {
    box.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-search fs-3 d-block mb-2"></i>ไม่พบรายการที่ตรงกับตัวกรอง</div>';
    return;
  }

  // ผลรวมรายคอลัมน์ ไว้คิดค่าเฉลี่ยทั้งชั้นในแถวท้ายตาราง
  const colSum = {}, colCount = {};
  cols.forEach(c => { colSum[c.key] = 0; colCount[c.key] = 0; });
  let gradedCells = 0, waiting = 0, stale = 0;

  const rows = students.map(stu => {
    let sum = 0, cnt = 0;
    const cells = cols.map(c => {
      const r = stu.cells[c.key];
      if (!r) {
        return '<td class="ai-cell-empty"><i class="bi bi-dash-circle" title="ยังไม่ได้ให้ AI ตรวจรอบนี้"></i></td>';
      }
      sum += r.combined_total; cnt++;
      colSum[c.key] += r.combined_total; colCount[c.key]++;
      gradedCells++;
      if (!r.manual_done) waiting++;
      if (r.needs_recheck) stale++;
      const tip = `${aiEsc(r.phase_label)} · AI ${aiNum(r.total_score)}/${aiNum(r.max_score)}`
                + ` · ครู ${r.manual_done ? aiNum(r.teacher_total) : 'ยังไม่ให้'}/${aiNum(r.full_max - r.max_score)}`
                + (r.teacher_source === 'evaluation' ? ' (จากแบบประเมิน)' : '')
                + ` · รวม ${aiNum(r.combined_total)}/${aiNum(r.full_max)}`
                + (r.quality_level ? ` · ระดับ ${aiEsc(r.quality_level)}` : '')
                + (r.needs_recheck ? ' · ต้นฉบับถูกแก้หลังตรวจ รอตรวจใหม่' : '')
                + ((r.draft_delta === null || r.draft_delta === undefined)
                    ? ''
                    : ` · เทียบ ${aiEsc(r.baseline_label)} ${r.draft_delta > 0 ? 'ดีขึ้น' : (r.draft_delta < 0 ? 'ต่ำกว่า' : 'เท่ากัน')}`
                      + (r.draft_delta ? ` ${aiNum(Math.abs(r.draft_delta))} คะแนน` : ''))
                + ((Number(r.review_round || 1) > 1) ? ` · ตรวจครั้งที่ ${Number(r.review_round)}` : '');
      const draftMark = (r.draft_delta === null || r.draft_delta === undefined)
        ? ''
        : (r.draft_delta > 0
            ? '<i class="bi bi-arrow-up-right-circle-fill ai-cell-pair-up"></i>'
            : (r.draft_delta < 0
                ? '<i class="bi bi-arrow-down-right-circle-fill ai-cell-pair-down"></i>'
                : '<i class="bi bi-dash-circle-fill ai-cell-pair-flat"></i>'));
      // ข้อที่ครูตรวจทานคะแนนแล้ว ทำเครื่องหมายไว้ให้เห็นในตารางภาพรวมด้วย
      const ovMark = Number(r.override_count || 0) > 0
        ? `<i class="bi bi-sliders ai-cell-ov" title="ครูตรวจทานคะแนนไว้ ${Number(r.override_count)} ข้อ"></i>`
        : '';
      return `<td class="ai-cell-score${r.needs_recheck ? ' ai-cell-stale' : ''}" title="${tip}"
                  onclick="jumpTo('${esc(stu.student_id)}','${esc(c.key)}')">
        <span class="fw-bold">${aiNum(r.combined_total)}</span>${r.manual_done
          ? ''
          : '<span class="ai-cell-wait" title="ยังรอคะแนนข้อที่คุณครูต้องให้เอง">*</span>'}${r.needs_recheck
          ? '<i class="bi bi-arrow-repeat ai-cell-stale-icon" title="ต้นฉบับถูกแก้หลังตรวจ รอตรวจใหม่"></i>'
          : ''}${draftMark}${ovMark}
      </td>`;
    }).join('');

    const avgRaw = cnt ? (sum / cnt) : null;
    const avg    = cnt ? aiNum(avgRaw) : '-';
    return `<tr>
      <td class="stu-id text-start">${esc(stu.student_id)}</td>
      <td class="stu-name">
        <a href="ai_student_summary.php?student_id=${encodeURIComponent(stu.student_id)}" target="_blank" rel="noopener"
           class="ai-stu-link" title="เปิดสรุปภาพรวมผลงานเขียนของนักเรียนคนนี้ในหน้าใหม่">
          ${esc(stu.student_name || '-')}<i class="bi bi-box-arrow-up-right ms-1 small text-muted"></i>
        </a>${stu.classroom
        ? ` <span class="badge bg-info-subtle text-info-emphasis small">ห้อง ${esc(stu.classroom)}</span>` : ''}</td>
      ${cells}
      <td class="ai-cell-avg fw-bold">${avg}<span class="text-muted fw-normal small"> / ${aiNum(fullMax)}</span></td>
      <td class="ai-cell-avg" title="ระดับคุณภาพจากคะแนนเฉลี่ย ${avg} คะแนน">${aiLevelBadge(aiLevelFromScore(avgRaw))}</td>
    </tr>`;
  }).join('');

  // แถวค่าเฉลี่ยทั้งชั้น (เฉลี่ยเฉพาะฉบับที่ตรวจแล้วในแต่ละรอบ)
  let allSum = 0, allCount = 0;
  const avgCells = cols.map(c => {
    if (!colCount[c.key]) return '<td class="ai-cell-empty">-</td>';
    allSum += colSum[c.key]; allCount += colCount[c.key];
    return `<td class="fw-bold" title="เฉลี่ยจาก ${colCount[c.key]} ฉบับ">${aiNum(colSum[c.key] / colCount[c.key])}</td>`;
  }).join('');
  const allAvgRaw = allCount ? (allSum / allCount) : null;
  const allAvg    = allCount ? aiNum(allAvgRaw) : '-';

  const headGroups = phase
    ? `<tr class="report-head-group">
         <th rowspan="2" class="text-start align-middle">รหัสนักเรียน</th>
         <th rowspan="2" class="text-start align-middle">ชื่อ-สกุลผู้เรียน</th>
         <th rowspan="2" class="align-middle">${esc(cols[0].label)}</th>
         <th rowspan="2" class="align-middle">เฉลี่ย</th>
         <th rowspan="2" class="align-middle">ระดับ</th>
       </tr>
       <tr class="report-head-sub"></tr>`
    : `<tr class="report-head-group">
         <th rowspan="2" class="text-start align-middle">รหัสนักเรียน</th>
         <th rowspan="2" class="text-start align-middle">ชื่อ-สกุลผู้เรียน</th>
         <th rowspan="2" class="align-middle">ก่อนเรียน</th>
         <th colspan="2" class="grp-unit1">หน่วยการเรียนที่ 1</th>
         <th colspan="2" class="grp-unit2">หน่วยการเรียนที่ 2</th>
         <th rowspan="2" class="align-middle">หลังเรียน</th>
         <th rowspan="2" class="align-middle">เฉลี่ย</th>
         <th rowspan="2" class="align-middle">ระดับ</th>
       </tr>
       <tr class="report-head-sub">
         <th class="grp-unit1">D1.1</th>
         <th class="grp-unit1">D1.2</th>
         <th class="grp-unit2">D2.1</th>
         <th class="grp-unit2">D2.2</th>
       </tr>`;

  box.innerHTML = `
    <table class="table table-hover align-middle mb-0 ai-report-table">
      <thead>${headGroups}</thead>
      <tbody>${rows}</tbody>
      <tfoot>
        <tr class="ai-report-avg">
          <td colspan="2" class="text-start fw-bold">เฉลี่ยทั้งชั้น (${students.length} คน)</td>
          ${avgCells}
          <td class="fw-bold">${allAvg}</td>
          <td>${aiLevelBadge(aiLevelFromScore(allAvgRaw))}</td>
        </tr>
      </tfoot>
    </table>
    <div class="px-3 py-2 small text-muted border-top bg-light">
      คะแนนในตารางคือคะแนนรวมเต็ม ${aiNum(fullMax)} (AI ประเมิน + ข้อที่คุณครูให้เอง) · คลิกที่ช่องคะแนนเพื่อเปิดผลตรวจฉบับนั้น
      · แสดง ${students.length} คน จากผลตรวจ ${gradedCells} ฉบับ
      <br>คอลัมน์ <strong>ระดับ</strong> คิดจากคะแนน<strong>เฉลี่ย</strong>ของช่องที่แสดงอยู่ ตามเกณฑ์เดียวกับหน้าประเมิน
      (ดีมาก 49 ขึ้นไป · ดี 37-48 · ปานกลาง 25-36 · พอใช้ 13-24 · ต้องปรับปรุง ต่ำกว่า 13)
      ${waiting > 0 ? `<br><span class="text-warning-emphasis fw-semibold">
          <i class="bi bi-asterisk me-1"></i>ช่องที่มีเครื่องหมาย * ยังรอคุณครูให้คะแนนข้อที่ AI ตรวจแทนไม่ได้ อีก ${waiting} ฉบับ</span>` : ''}
      ${stale > 0 ? `<br><span class="text-warning-emphasis fw-semibold">
          <i class="bi bi-arrow-repeat me-1"></i>ช่องที่มีสัญลักษณ์วนซ้ำ คือฉบับที่นักเรียนแก้ไขต้นฉบับหลัง AI ตรวจ รอตรวจใหม่ ${stale} ฉบับ</span>` : ''}
      <br><i class="bi bi-arrow-up-right-circle-fill ai-cell-pair-up"></i> = ร่างหลังได้คะแนนสูงกว่าฉบับตั้งต้น ·
      <i class="bi bi-exclamation-circle-fill ai-cell-pair-flat"></i> = ยังไม่สูงกว่าฉบับตั้งต้น
      (D1.2 เทียบ D1.1 · D2.2 เทียบ D2.1 · หลังเรียน เทียบ ก่อนเรียน) ·
      คลิก<strong>ชื่อนักเรียน</strong>เพื่อเปิดหน้าสรุปภาพรวมผลงานเขียนของคนนั้นในแท็บใหม่
    </div>`;
}

// เปิดผลตรวจของนักเรียน 1 คน 1 รอบงาน — เรียกจากตารางภาพรวมและจากคิวรอตรวจใหม่
// (ทั้งสองที่อยู่คนละแท็บกับการ์ดผลตรวจ จึงต้องสลับแท็บกลับมาให้ด้วย)
async function jumpTo(sid, phase) {
  const sSel = document.getElementById('aiStudentSelect');
  if (sSel && sSel.value !== sid) { sSel.value = sid; }
  showPersonTab();
  selectedPhase = '';
  updateReviewButton();
  await loadFeedback();
  if (aiAllFeedback[phase]) {
    selectPhase(phase);
  } else {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

async function loadStudents() {
  const sel = document.getElementById('aiStudentSelect');
  if (!sel) return;
  try {
    // รายชื่อในช่องนี้ใช้เฉพาะ "กลุ่มตัวอย่าง" (ผู้เชี่ยวชาญถูกบังคับกลุ่มทดลองที่ฝั่งเซิร์ฟเวอร์อยู่แล้ว)
    const res  = await fetch('api.php?action=get_students_list' + (window.TEG ? TEG.sampleParam() : ''));
    const data = await res.json();
    if (!data.success) { sel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>'; return; }
    const opts = Object.keys(data.students).map(id =>
      `<option value="${esc(id)}">${esc(id)} — ${esc(data.students[id])}</option>`).join('');
    sel.innerHTML = '<option value="">— เลือกนักเรียน —</option>' + opts;
  } catch (err) {
    sel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>';
  }
}

<?php if ($aiIsTeacher): ?>
// --------------------------------------------- ตรวจทั้งรอบรวดเดียว (ครู)
// ตรวจทีละฉบับจากฝั่งหน้าเว็บ ไม่ยิงรวดเดียวไปที่เซิร์ฟเวอร์ เพราะ PHP บนโฮสต์มีเพดาน
// เวลาทำงาน และการเว้นจังหวะระหว่างฉบับช่วยไม่ให้ชนลิมิต "จำนวนคำขอต่อนาที" ของผู้ให้บริการ
let batchTargets = [];      // รายการเรียงความในรอบที่เลือก
let batchRunning = false;   // กำลังตรวจอยู่หรือไม่
let batchStopRequested = false;

const BATCH_GAP_MS       = 2000;   // เว้นจังหวะระหว่างฉบับ
const BATCH_RATELIMIT_MS = 30000;  // ถ้าโดนจำกัดอัตราคำขอ ให้พักนานขึ้นแล้วลองใหม่ 1 ครั้ง

const sleep = ms => new Promise(r => setTimeout(r, ms));

// รายชื่อห้องเรียน (ใช้กรองก่อนตรวจ เผื่อครูอยากตรวจทีละห้อง)
async function loadBatchRooms() {
  try {
    const res  = await fetch('api.php?action=get_students_full');
    const data = await res.json();
    if (!data.success || !Array.isArray(data.students)) return;
    const rooms = [...new Set(data.students.map(s => s.classroom).filter(Boolean))].sort();
    const sel = document.getElementById('batchRoom');
    sel.innerHTML = '<option value="">ทุกห้องเรียน</option>'
      + rooms.map(r => `<option value="${esc(r)}">ห้อง ${esc(r)}</option>`).join('');
  } catch (err) { /* ไม่มีตัวกรองห้องก็ยังตรวจทั้งรอบได้ */ }
}

async function loadBatchTargets() {
  const box = document.getElementById('batchSummary');
  box.textContent = 'กำลังโหลดรายการ...';
  try {
    const params = new URLSearchParams({
      action: 'get_ai_batch_targets',
      essay_phase: document.getElementById('batchPhase').value
    });
    const room = document.getElementById('batchRoom').value;
    if (room) params.set('classroom', room);

    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    if (!data.success) { box.textContent = data.error || 'โหลดรายการไม่สำเร็จ'; batchTargets = []; return; }
    batchTargets = data.targets || [];
    batchTargets.__tooShort = data.too_short || 0;
    batchTargets.__minWords = data.min_words || 0;
    paintBatchSummary();
  } catch (err) {
    box.textContent = 'โหลดรายการไม่สำเร็จ';
    batchTargets = [];
  }
}

// รายการที่จะตรวจจริงในรอบนี้ (ขึ้นกับว่าติ๊ก "ข้ามฉบับที่เคยตรวจแล้ว" ไว้ไหม)
// ฉบับที่นักเรียนแก้ต้นฉบับหลังตรวจ ถือว่ายังไม่ได้ตรวจฉบับล่าสุด จึงไม่ถูกข้ามแม้ติ๊กไว้
function batchQueue() {
  const skipDone = document.getElementById('batchSkipDone').checked;
  return skipDone ? batchTargets.filter(t => !t.reviewed || t.needs_recheck) : batchTargets.slice();
}

function paintBatchSummary() {
  const box = document.getElementById('batchSummary');
  const btn = document.getElementById('batchStartBtn');
  if (!batchTargets.length) {
    box.innerHTML = '<i class="bi bi-inbox me-1"></i>ยังไม่มีเรียงความที่ส่งเข้ามาในรอบนี้';
    btn.disabled = true;
    return;
  }
  const queue = batchQueue();
  const done  = batchTargets.filter(t => t.reviewed).length;
  const tooShort = batchTargets.__tooShort || 0;
  const reQueued = batchTargets.filter(t => t.needs_recheck).length;
  // เคยสั่งตรวจแล้วแต่ผลไม่สมบูรณ์ (AI ให้คะแนนไม่ครบ) — นับเป็นยังไม่ตรวจ ต้องตรวจใหม่
  const failedBefore = batchTargets.filter(t => t.failed_before).length;

  // ประเมินเวลาคร่าว ๆ: AI ใช้เวลาราว 25 วินาทีต่อฉบับ บวกจังหวะพักระหว่างฉบับ
  const mins = Math.max(1, Math.round(queue.length * (25000 + BATCH_GAP_MS) / 60000));

  let html = `<i class="bi bi-list-check me-1"></i>ส่งแล้ว <strong>${batchTargets.length}</strong> ฉบับ`
    + ` · ตรวจไปแล้ว <strong>${done}</strong> · <strong class="text-primary">จะตรวจรอบนี้ ${queue.length} ฉบับ</strong>`;
  if (reQueued > 0) {
    html += `<br><i class="bi bi-arrow-repeat text-warning me-1"></i>`
      + `<strong class="text-warning-emphasis">รอตรวจใหม่ ${reQueued} ฉบับ</strong> (นักเรียนแก้ไขต้นฉบับหลัง AI ตรวจไปแล้ว)`;
  }
  if (failedBefore > 0) {
    html += `<br><i class="bi bi-exclamation-octagon text-danger me-1"></i>`
      + `<strong class="text-danger-emphasis">เคยตรวจไม่สำเร็จ ${failedBefore} ฉบับ</strong>`
      + ` — ผลตรวจครั้งนั้นให้คะแนนไม่ครบ ระบบถือว่ายังไม่ได้ตรวจ และจะตรวจให้ใหม่ในรอบนี้`;
  }
  if (tooShort > 0) {
    html += `<br><i class="bi bi-exclamation-triangle text-warning me-1"></i>`
      + `ข้าม ${tooShort} ฉบับที่สั้นกว่า ${batchTargets.__minWords} คำ (ระบบไม่ส่งให้ AI ตรวจ)`;
  }
  if (queue.length > 0) {
    html += `<br><i class="bi bi-clock me-1"></i>ใช้เวลาประมาณ ${mins} นาที — เปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`;
    if (aiStatus && aiStatus.quota_left < queue.length) {
      html += `<br><i class="bi bi-battery-low text-danger me-1"></i>`
        + `<strong class="text-danger">โควตาวันนี้เหลือ ${aiStatus.quota_left} ครั้ง ไม่พอตรวจครบ</strong> — `
        + `ระบบจะตรวจเท่าที่โควตาเหลือ แล้วจำคิวที่เหลือไว้ให้กด "ตรวจต่อจากที่ค้างไว้" ในวันถัดไป`;
    }
  }
  box.innerHTML = html;
  btn.disabled = (queue.length === 0);
}

// ---- ตัวช่วยแสดงผลระหว่างตรวจเป็นชุด (ใช้ร่วมกันทั้ง "ตรวจทั้งรอบ" และ "ตรวจใหม่ทั้งคิว") ----
const REVIEW_UI_BATCH   = { wrap: 'batchProgressWrap', label: 'batchProgressLabel',
                            count: 'batchProgressCount', bar: 'batchProgressBar', log: 'batchLog' };
const REVIEW_UI_RECHECK = { wrap: 'rcProgressWrap', label: 'rcProgressLabel',
                            count: 'rcProgressCount', bar: 'rcProgressBar', log: 'rcLog' };

function reviewLogLine(ui, icon, cls, name, msg) {
  const log = document.getElementById(ui.log);
  if (!log) return;
  log.classList.remove('d-none');
  const row = document.createElement('div');
  row.className = 'px-3 py-2 border-bottom small d-flex gap-2 align-items-start';
  row.innerHTML = `<i class="bi ${icon} ${cls} mt-1"></i>`
    + `<span class="flex-grow-1"><strong>${esc(name)}</strong>`
    + (msg ? ` <span class="text-muted">— ${esc(msg)}</span>` : '') + '</span>';
  log.appendChild(row);
  log.scrollTop = log.scrollHeight;
}

function setReviewProgress(ui, done, total, label) {
  const wrap = document.getElementById(ui.wrap);
  if (!wrap) return;
  wrap.classList.remove('d-none');
  document.getElementById(ui.count).textContent = done + ' / ' + total;
  document.getElementById(ui.label).textContent = label;
  const pct = total > 0 ? Math.round((done / total) * 100) : 0;
  document.getElementById(ui.bar).style.width = pct + '%';
}

/* ---------------------------------------- คิวที่ค้างอยู่ (ตรวจต่อจากเดิม)
   การตรวจเป็นชุดอาจหยุดกลางคันได้หลายแบบ — โควตารายวันหมด, ครูกดหยุด, เน็ตหลุด
   หรือเผลอปิดหน้าไป ระบบจึงจำ "รายการที่ยังไม่ได้ตรวจ" ไว้ในเครื่องของครูหลังตรวจทุกฉบับ
   ครั้งหน้าเปิดหน้านี้จะมีปุ่มให้ตรวจต่อจากจุดเดิมได้ทันที ไม่ต้องเริ่มใหม่ทั้งชุดให้เปลืองโควตา */
const BATCH_RESUME_KEY  = 'aiBatchResume_v1';
const BATCH_RESUME_DAYS = 30;   // คิวที่ค้างนานเกินนี้ถือว่าเก่าเกินไป ไม่ต้องเสนอให้ตรวจต่อ

function loadBatchResume() {
  try {
    const raw = localStorage.getItem(BATCH_RESUME_KEY);
    if (!raw) return null;
    const st = JSON.parse(raw);
    if (!st || !Array.isArray(st.remaining) || !st.remaining.length) return null;
    // คิวเก่าเก็บไว้ก็ไม่ตรงกับงานปัจจุบันแล้ว — ทิ้งไปเลยดีกว่าเสนอให้ตรวจผิดชุด
    if (st.saved_at && (Date.now() - st.saved_at) > BATCH_RESUME_DAYS * 86400000) {
      localStorage.removeItem(BATCH_RESUME_KEY);
      return null;
    }
    return st;
  } catch (err) { return null; }
}

function saveBatchResume(state) {
  try { localStorage.setItem(BATCH_RESUME_KEY, JSON.stringify(state)); } catch (err) { /* เต็ม/ปิดไว้ ก็ยังตรวจต่อได้ในรอบนี้ */ }
}

function clearBatchResume() {
  try { localStorage.removeItem(BATCH_RESUME_KEY); } catch (err) { /* ไม่เป็นไร */ }
}

// อธิบายคิวที่ค้างให้ครูเห็นว่าเป็นชุดไหน ค้างเพราะอะไร และเหลือรอบไหนบ้าง
// autoOpen = กางการ์ด "ตรวจทั้งรอบรวดเดียว" ให้เอง (ใช้ตอนเปิดหน้าเว็บ ครูจะได้เห็นคิวที่ค้างทันที)
function paintBatchResume(autoOpen) {
  const wrap  = document.getElementById('batchResumeWrap');
  const badge = document.getElementById('aiBatchResumeBadge');
  if (!wrap) return;
  const st = loadBatchResume();
  if (!st || batchRunning) {
    wrap.classList.add('d-none');
    if (badge) badge.classList.add('d-none');
    return;
  }

  if (badge) {
    document.getElementById('aiBatchResumeBadgeCount').textContent = st.remaining.length;
    badge.classList.remove('d-none');
  }
  if (autoOpen) {
    const card = document.getElementById('aiBatchCard');
    if (card) card.classList.add('show');   // กางการ์ดแบบเดียวกับที่ Bootstrap ทำ
  }

  document.getElementById('batchResumeCount').textContent = st.remaining.length;

  // สรุปว่าเหลือรอบไหนบ้าง กี่ฉบับ (เรียงตามลำดับการเรียน)
  const byPhase = {};
  st.remaining.forEach(t => { byPhase[t.essay_phase] = (byPhase[t.essay_phase] || 0) + 1; });
  const phaseText = AI_PHASES.filter(ph => byPhase[ph])
    .map(ph => `${AI_PHASE_SHORT_MAP[ph] || AI_PHASE_LABELS[ph] || ph} ${byPhase[ph]} ฉบับ`).join(' · ');

  const when   = st.saved_at ? new Date(st.saved_at).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }) : '';
  const reason = st.reason === 'quota'   ? 'โควตาการตรวจของวันนั้นหมดก่อน'
               : st.reason === 'stopped' ? 'คุณครูกดหยุดไว้'
               : 'การตรวจหยุดไปกลางคัน (เช่น ปิดหน้าเว็บหรือเน็ตหลุด)';

  let html = `<strong>${esc(st.label || 'ตรวจเป็นชุด')}</strong>`
    + ` — ตรวจไปแล้ว ${st.done || 0} จาก ${st.total || 0} ฉบับ แล้ว${reason}`
    + (when ? ` เมื่อ ${esc(when)}` : '');
  if (st.room) html += `<br><i class="bi bi-house-door me-1"></i>เฉพาะห้อง ${esc(st.room)}`;
  if (phaseText) html += `<br><i class="bi bi-list-ol me-1"></i>ที่ยังไม่ได้ตรวจ: ${esc(phaseText)}`;
  if (st.reason === 'quota' && aiStatus && typeof aiStatus.quota_left === 'number') {
    html += aiStatus.quota_left > 0
      ? `<br><i class="bi bi-battery-half me-1"></i>วันนี้โควตาเหลือ ${aiStatus.quota_left} ครั้ง — กดตรวจต่อได้เลย`
      : `<br><i class="bi bi-battery text-danger me-1"></i>วันนี้โควตาหมดแล้ว กลับมากด "ตรวจต่อ" พรุ่งนี้ `
        + `หรือเพิ่มโควตาต่อวันได้ในการ์ด "ตั้งค่าผู้ช่วย AI"`;
  }
  document.getElementById('batchResumeDetail').innerHTML = html;
  wrap.classList.remove('d-none');
}

function discardBatchResume() {
  if (!confirm('ล้างคิวที่ค้างไว้ใช่ไหม?\n\nฉบับที่ยังไม่ได้ตรวจจะไม่หายไปไหน — ยังสั่งตรวจใหม่ได้จากปุ่ม "เริ่มตรวจทั้งรอบ" ตามปกติ')) return;
  clearBatchResume();
  paintBatchResume();
}

// ตรวจต่อจากจุดที่ค้างไว้ — ใช้รายการเดิมที่บันทึกไว้ ไม่เริ่มนับหนึ่งใหม่
async function resumeBatchReview() {
  if (batchRunning) { showToast('กำลังตรวจชุดอื่นอยู่ กรุณารอให้เสร็จก่อน', 'error'); return; }
  const st = loadBatchResume();
  if (!st) { paintBatchResume(); return; }

  const items = st.remaining;
  const mins  = Math.max(1, Math.round(items.length * (25000 + BATCH_GAP_MS) / 60000));
  const quota = (aiStatus && typeof aiStatus.quota_left === 'number') ? aiStatus.quota_left : null;
  if (!confirm(`ตรวจต่อจากที่ค้างไว้ ${items.length} ฉบับ ใช่ไหม?\n\n`
      + `• ชุดเดิม: ${st.label || 'ตรวจเป็นชุด'} (ตรวจไปแล้ว ${st.done || 0} จาก ${st.total || 0} ฉบับ)\n`
      + `• ระบบจะตรวจเฉพาะฉบับที่ยังไม่ได้ตรวจในชุดนั้น ฉบับที่ตรวจไปแล้วจะไม่ถูกตรวจซ้ำ\n`
      + (quota !== null && quota < items.length
          ? `• โควตาวันนี้เหลือ ${quota} ครั้ง ไม่พอตรวจครบ ระบบจะตรวจเท่าที่เหลือแล้วจำคิวที่เหลือไว้ให้อีกครั้ง\n` : '')
      + `\nใช้เวลาประมาณ ${mins} นาที กรุณาเปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`)) return;

  batchRunning = true;
  batchStopRequested = false;
  document.getElementById('batchResumeWrap').classList.add('d-none');
  document.getElementById('batchStartBtn').disabled = true;
  document.getElementById('batchAllBtn').disabled = true;
  document.getElementById('batchStopBtn').classList.remove('d-none');
  document.getElementById('batchPhase').disabled = true;
  document.getElementById('batchRoom').disabled  = true;
  document.getElementById('batchLog').innerHTML  = '';

  let res = { ok: 0, failed: 0, failedItems: [] };
  try {
    // นับต่อจากของเดิม เพื่อให้ตัวเลขสรุปในคิวที่ค้างยังเป็นชุดเดียวกัน
    res = await runReviewQueue(items, REVIEW_UI_BATCH, {
      kind: st.kind, label: st.label, room: st.room || '',
      total: st.total || items.length,
    });
  } finally {
    batchRunning = false;
    batchFailedItems = res.failedItems || [];
    document.getElementById('batchStartBtn').disabled = false;
    document.getElementById('batchAllBtn').disabled = false;
    document.getElementById('batchStopBtn').classList.add('d-none');
    document.getElementById('batchPhase').disabled = false;
    document.getElementById('batchRoom').disabled  = false;
    document.getElementById('batchProgressLabel').textContent =
      `ตรวจต่อเสร็จสิ้น — สำเร็จ ${res.ok} ฉบับ`
      + (res.failed ? ` · ไม่สำเร็จ ${res.failed} ฉบับ (ถือว่ายังไม่ได้ตรวจ)` : '');
    showToast(`ตรวจต่อเสร็จแล้ว: สำเร็จ ${res.ok} ฉบับ` + (res.failed ? `, ไม่สำเร็จ ${res.failed} ฉบับ` : ''),
      res.failed ? 'error' : 'success');
    loadBatchTargets();
    updateReviewButton();
    loadAiOverview();
    loadRecheckQueue();
    loadFeedback();
    paintBatchRetry();
    paintBatchResume();
  }
}

function stopBatchReview() {
  batchStopRequested = true;
  ['batchProgressLabel', 'rcProgressLabel'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = 'กำลังหยุดหลังตรวจฉบับปัจจุบันเสร็จ...';
  });
}

/**
 * ไล่ให้ AI ตรวจตามรายการที่ส่งมาทีละฉบับ
 * items = [{ student_id, student_name, essay_phase, note }]
 * คืนค่า { ok, failed, failedItems } — ผู้เรียกเป็นคนจัดการปุ่ม/ข้อความสรุปเอง
 *
 * ฉบับที่ตรวจไม่สำเร็จจะไม่ถูกทำเครื่องหมายว่าตรวจแล้วเด็ดขาด (เซิร์ฟเวอร์ก็ไม่บันทึกผลให้)
 * และถูกส่งกลับไปใน failedItems เพื่อให้ครูกดตรวจซ้ำเฉพาะกลุ่มนั้นได้ทันที
 *
 * resume = { kind, label, room, total, offset } — ถ้าส่งมา ระบบจะจำ "รายการที่ยังไม่ได้ตรวจ"
 * ไว้ในเครื่องหลังตรวจทุกฉบับ ครูจึงกดตรวจต่อจากจุดเดิมได้ถ้าชุดนี้หยุดกลางคัน
 */
async function runReviewQueue(items, ui, resume = null) {
  let ok = 0, failed = 0, i = 0;
  const failedItems = [];
  let stopReason = '';

  // บันทึกสถานะคิวหลังตรวจทุกฉบับ (extra = ฉบับที่ยังไม่ได้ตรวจและต้องเอากลับเข้าคิวด้วย)
  const keepResume = (extra) => {
    if (!resume) return;
    const remaining = (extra || []).concat(items.slice(i));
    if (!remaining.length) { clearBatchResume(); return; }
    const total = resume.total || items.length;
    saveBatchResume({
      kind:      resume.kind || 'phase',
      label:     resume.label || 'ตรวจเป็นชุด',
      room:      resume.room || '',
      remaining: remaining,
      done:      total - remaining.length,
      total:     total,
      reason:    stopReason,
      saved_at:  Date.now(),
    });
  };

  for (const t of items) {
    if (batchStopRequested) {
      reviewLogLine(ui, 'bi-stop-circle', 'text-secondary', 'หยุดตามคำสั่ง', `ตรวจไปแล้ว ${i} ฉบับ`);
      stopReason = 'stopped';
      keepResume();
      break;
    }
    const who = t.student_name + (t.note ? ` (${t.note})` : '');
    setReviewProgress(ui, i, items.length, `กำลังตรวจ: ${who}`);

    let data = await aiRequestReview(t.student_id, t.essay_phase);

    // โดนจำกัดจำนวนคำขอต่อนาที → พักแล้วลองใหม่อีกครั้งเดียว
    if (!data.success && /โควตาฟรีของผู้ให้บริการ|429/.test(data.error || '')) {
      reviewLogLine(ui, 'bi-hourglass-split', 'text-warning', who, 'ผู้ให้บริการจำกัดอัตราคำขอ กำลังพักแล้วลองใหม่');
      await sleep(BATCH_RATELIMIT_MS);
      // กดหยุดระหว่างพัก — ฉบับนี้ยังไม่ได้ตรวจ ต้องอยู่ในคิวที่ค้างไว้ด้วย
      if (batchStopRequested) { stopReason = 'stopped'; keepResume(); break; }
      data = await aiRequestReview(t.student_id, t.essay_phase);
    }

    i++;
    if (data.success) {
      ok++;
      t.reviewed = true;
      t.needs_recheck = false;
      const fb = data.feedback || {};
      // รอบที่มีคู่เทียบ: บอกในบรรทัดสรุปเลยว่าดีขึ้นจากฉบับตั้งต้นเท่าไร ไม่ต้องเปิดดูทีละคน
      const dc   = (fb.draft_compare && fb.draft_compare.has_baseline) ? fb.draft_compare : null;
      const diff = dc
        ? ` · เทียบ ${dc.short} ${dc.delta > 0 ? '▲ +' + aiNum(dc.delta)
            : (dc.delta < 0 ? '▼ ' + aiNum(dc.delta) : 'เท่าเดิม')}`
          + ` (${aiNum(dc.base_total)} → ${aiNum(dc.total)})`
        : '';
      reviewLogLine(ui, 'bi-check-circle-fill', 'text-success', who,
        `${fb.total_score}/${fb.max_score} · ${fb.quality_level || '-'}${diff}`);
      if (typeof data.quota_left === 'number' && aiStatus) {
        aiStatus.quota_left = data.quota_left;
        aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
      }
    } else {
      failed++;
      // ตรวจไม่ผ่าน = ยังไม่ได้ตรวจ ต้องไม่ถูกนับเป็น "ตรวจแล้ว" และต้องตรวจซ้ำได้
      t.reviewed = false;
      t.failed_before = true;
      failedItems.push(t);
      reviewLogLine(ui, 'bi-x-circle-fill', 'text-danger', who,
        (data.error || 'ตรวจไม่สำเร็จ') + ' — ถือว่ายังไม่ได้ตรวจ ตรวจซ้ำได้');
      // โควตารายวันหมด = ตรวจต่อไปก็ไม่ผ่าน หยุดทั้งชุดเลยดีกว่าปล่อยให้พังทีละฉบับ
      // ฉบับที่เพิ่งโดนปฏิเสธเพราะโควตายังไม่ได้ตรวจจริง จึงเอากลับเข้าคิวที่ค้างไว้ด้วย
      if (/ใช้ AI ตรวจครบ/.test(data.error || '')) {
        reviewLogLine(ui, 'bi-battery', 'text-danger', 'หยุดอัตโนมัติ',
          'โควตารายวันหมดแล้ว — จำคิวที่เหลือไว้ให้ กดปุ่ม "ตรวจต่อจากที่ค้างไว้" ได้เลยเมื่อโควตากลับมา');
        stopReason = 'quota';
        keepResume([t]);
        break;
      }
    }

    keepResume();
    setReviewProgress(ui, i, items.length, `ตรวจแล้ว ${i} จาก ${items.length} ฉบับ`);
    if (i < items.length && !batchStopRequested) await sleep(BATCH_GAP_MS);
  }
  return { ok, failed, failedItems };
}

// ---- ตรวจซ้ำเฉพาะฉบับที่ตรวจไม่สำเร็จในรอบล่าสุด ----
let batchFailedItems = [];   // รายการที่ตรวจไม่ผ่าน รอให้ครูกดตรวจซ้ำ

function paintBatchRetry() {
  const btn = document.getElementById('batchRetryBtn');
  if (!btn) return;
  const n = batchFailedItems.length;
  document.getElementById('batchRetryCount').textContent = n;
  btn.classList.toggle('d-none', n === 0);
  btn.disabled = batchRunning;
}

async function retryFailedBatch() {
  if (batchRunning) { showToast('กำลังตรวจชุดอื่นอยู่ กรุณารอให้เสร็จก่อน', 'error'); return; }
  const items = batchFailedItems.slice();
  if (!items.length) return;
  if (!confirm(`ให้ AI ตรวจซ้ำ ${items.length} ฉบับที่ตรวจไม่สำเร็จ ใช่ไหม?\n\n`
      + `ฉบับเหล่านี้ยังไม่มีผลตรวจในระบบ (ระบบไม่บันทึกผลที่ไม่สมบูรณ์)`)) return;

  batchRunning = true;
  batchStopRequested = false;
  const btn = document.getElementById('batchRetryBtn');
  if (btn) btn.disabled = true;
  document.getElementById('batchStartBtn').disabled = true;
  document.getElementById('batchStopBtn').classList.remove('d-none');
  document.getElementById('batchLog').innerHTML = '';

  let res = { ok: 0, failed: 0, failedItems: [] };
  try {
    res = await runReviewQueue(items, REVIEW_UI_BATCH, {
      kind: 'retry', label: 'ตรวจซ้ำเฉพาะฉบับที่ตรวจไม่สำเร็จ', room: '',
    });
  } finally {
    batchRunning = false;
    batchFailedItems = res.failedItems || [];
    document.getElementById('batchStartBtn').disabled = false;
    document.getElementById('batchStopBtn').classList.add('d-none');
    document.getElementById('batchProgressLabel').textContent =
      `ตรวจซ้ำเสร็จสิ้น — สำเร็จ ${res.ok} ฉบับ` + (res.failed ? ` · ยังไม่สำเร็จ ${res.failed} ฉบับ` : '');
    showToast(`ตรวจซ้ำเสร็จแล้ว: สำเร็จ ${res.ok} ฉบับ` + (res.failed ? `, ยังไม่สำเร็จ ${res.failed} ฉบับ` : ''),
      res.failed ? 'error' : 'success');
    loadBatchTargets();
    updateReviewButton();
    loadAiOverview();
    loadRecheckQueue();
    loadFeedback();
    paintBatchRetry();
    paintBatchResume();
  }
}

async function startBatchReview() {
  if (batchRunning) return;
  const queue = batchQueue();
  if (!queue.length) return;

  const phase      = document.getElementById('batchPhase').value;
  const phaseLabel = AI_PHASE_LABELS[phase] || '';
  const reQueued   = queue.filter(t => t.needs_recheck).length;
  // ฉบับที่เคยตรวจแล้วอาจมีคะแนนที่ครูปรับไว้รายข้อ ซึ่งจะถูกล้างเมื่อ AI ตรวจใหม่ทั้งฉบับ
  const doneAgain  = queue.filter(t => t.reviewed).length;
  if (!confirm(`เริ่มให้ AI ตรวจ ${queue.length} ฉบับของ "${phaseLabel}" ใช่ไหม?\n`
      + (reQueued ? `(ในจำนวนนี้เป็นฉบับที่แก้ไขต้นฉบับแล้วรอตรวจใหม่ ${reQueued} ฉบับ)\n` : '')
      + (doneAgain ? `(มีฉบับที่เคยตรวจไปแล้ว ${doneAgain} ฉบับ — ถ้าฉบับใดมีคะแนนที่ครูปรับไว้รายข้อ จะถูกล้างและใช้คะแนนชุดใหม่แทน)\n` : '')
      + `\nใช้เวลาประมาณ ${Math.max(1, Math.round(queue.length * 27000 / 60000))} นาที `
      + `กรุณาเปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`)) return;

  batchRunning = true;
  batchStopRequested = false;
  document.getElementById('batchStartBtn').disabled = true;
  document.getElementById('batchStopBtn').classList.remove('d-none');
  document.getElementById('batchPhase').disabled = true;
  document.getElementById('batchRoom').disabled = true;
  document.getElementById('batchLog').innerHTML = '';

  let res = { ok: 0, failed: 0, failedItems: [] };
  try {
    res = await runReviewQueue(queue.map(t => ({
      student_id:   t.student_id,
      student_name: t.student_name,
      essay_phase:  phase,
      note:         t.needs_recheck ? 'ตรวจใหม่' : (t.failed_before ? 'เคยตรวจไม่สำเร็จ' : ''),
    })), REVIEW_UI_BATCH, {
      kind:  'phase',
      label: `ตรวจทั้งรอบ "${phaseLabel}"`,
      room:  document.getElementById('batchRoom').value || '',
    });
  } finally {
    batchRunning = false;
    // ฉบับที่ตรวจไม่ผ่าน ยังไม่ถือว่าตรวจแล้ว — เก็บไว้ให้ครูกดตรวจซ้ำได้ทันที
    batchFailedItems = res.failedItems || [];
    document.getElementById('batchStopBtn').classList.add('d-none');
    document.getElementById('batchPhase').disabled = false;
    document.getElementById('batchRoom').disabled = false;
    document.getElementById('batchProgressLabel').textContent =
      `เสร็จสิ้น — สำเร็จ ${res.ok} ฉบับ`
      + (res.failed ? ` · ไม่สำเร็จ ${res.failed} ฉบับ (ถือว่ายังไม่ได้ตรวจ)` : '');
    showToast(`ตรวจเสร็จแล้ว: สำเร็จ ${res.ok} ฉบับ`
      + (res.failed ? `, ไม่สำเร็จ ${res.failed} ฉบับ — กด "ตรวจซ้ำเฉพาะที่ไม่สำเร็จ" ได้เลย` : ''),
      res.failed ? 'error' : 'success');
    loadBatchTargets();
    updateReviewButton();
    loadAiOverview();
    loadRecheckQueue();
    loadFeedback();
    paintBatchRetry();
    paintBatchResume();
  }
}

/* --------------------------------------- ตรวจใหม่ "ทุกรอบ" รวดเดียว (ครู)
   ใช้เมื่อกติกาการตรวจเปลี่ยน แล้วอยากให้ผลตรวจของทั้งชั้นทุกรอบมาจากกติกาชุดเดียวกัน
   ไล่ตามลำดับการเรียนเสมอ (ก่อนเรียน → D1.1 → D1.2 → D2.1 → D2.2 → หลังเรียน)
   เพราะร่างหลังต้องเทียบกับฉบับตั้งต้น ถ้าตรวจสลับลำดับ ผลเทียบร่างจะอ้างคะแนนชุดเก่า */

// รวมรายการเรียงความของทุกรอบเป็นคิวเดียว (เรียงตามลำดับการเรียน) ตามห้องที่กรองอยู่
async function loadAllPhaseTargets(room) {
  const items = [];
  let tooShort = 0;
  for (const ph of AI_PHASES) {
    const params = new URLSearchParams({ action: 'get_ai_batch_targets', essay_phase: ph });
    if (room) params.set('classroom', room);
    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'โหลดรายการไม่สำเร็จ');
    tooShort += Number(data.too_short || 0);
    (data.targets || []).forEach(t => items.push({
      student_id:   t.student_id,
      student_name: t.student_name,
      essay_phase:  ph,
      note:         AI_PHASE_SHORT_MAP[ph] || AI_PHASE_LABELS[ph] || ph,
    }));
  }
  return { items, tooShort };
}

async function startBatchReviewAllPhases() {
  if (batchRunning) { showToast('กำลังตรวจชุดอื่นอยู่ กรุณารอให้เสร็จก่อน', 'error'); return; }
  const allBtn = document.getElementById('batchAllBtn');
  const room   = document.getElementById('batchRoom').value;

  allBtn.disabled = true;
  allBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังรวบรวมรายการทุกรอบ...';
  let items = [], tooShort = 0;
  try {
    ({ items, tooShort } = await loadAllPhaseTargets(room));
  } catch (err) {
    showToast('โหลดรายการไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 'error');
    allBtn.disabled = false;
    allBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>ตรวจใหม่ทุกรอบรวดเดียว (ทั้ง ' + AI_PHASES.length + ' รอบ)';
    return;
  }
  allBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>ตรวจใหม่ทุกรอบรวดเดียว (ทั้ง ' + AI_PHASES.length + ' รอบ)';
  allBtn.disabled = false;

  if (!items.length) {
    showToast('ยังไม่มีเรียงความที่ส่งเข้ามาในรอบใดเลย', 'error');
    return;
  }

  const mins  = Math.max(1, Math.round(items.length * (25000 + BATCH_GAP_MS) / 60000));
  const quota = (aiStatus && typeof aiStatus.quota_left === 'number') ? aiStatus.quota_left : null;
  if (!confirm(`ตรวจใหม่ทุกรอบรวดเดียว ${items.length} ฉบับ`
      + (room ? ` (เฉพาะห้อง ${room})` : ' (ทุกห้องเรียน)') + ` ใช่ไหม?\n\n`
      + `• ตรวจซ้ำทุกฉบับ รวมฉบับที่เคยตรวจแล้ว — คะแนนที่คุณครูปรับไว้รายข้อจะถูกล้างทั้งหมด\n`
      + `• ไล่ตามลำดับการเรียน ฉบับตั้งต้นจะถูกตรวจก่อนร่างหลังเสมอ\n`
      + (tooShort ? `• ข้าม ${tooShort} ฉบับที่สั้นกว่าเกณฑ์ ระบบไม่ส่งให้ AI ตรวจ\n` : '')
      + (quota !== null && quota < items.length
          ? `• โควตาวันนี้เหลือ ${quota} ครั้ง ไม่พอตรวจครบ ระบบจะตรวจเท่าที่เหลือ `
            + `แล้วจำคิวที่ค้างไว้ให้กด "ตรวจต่อจากที่ค้างไว้" ภายหลัง\n` : '')
      + `\nใช้เวลาประมาณ ${mins} นาที กรุณาเปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`)) return;

  batchRunning = true;
  batchStopRequested = false;
  allBtn.disabled = true;
  document.getElementById('batchStartBtn').disabled = true;
  document.getElementById('batchRetryBtn').disabled = true;
  document.getElementById('batchStopBtn').classList.remove('d-none');
  document.getElementById('batchPhase').disabled = true;
  document.getElementById('batchRoom').disabled  = true;
  document.getElementById('batchLog').innerHTML  = '';

  let res = { ok: 0, failed: 0, failedItems: [] };
  try {
    res = await runReviewQueue(items, REVIEW_UI_BATCH, {
      kind: 'all', label: 'ตรวจใหม่ทุกรอบรวดเดียว', room: room || '',
    });
  } finally {
    batchRunning = false;
    batchFailedItems = res.failedItems || [];
    allBtn.disabled = false;
    document.getElementById('batchStartBtn').disabled = false;
    document.getElementById('batchStopBtn').classList.add('d-none');
    document.getElementById('batchPhase').disabled = false;
    document.getElementById('batchRoom').disabled  = false;
    document.getElementById('batchProgressLabel').textContent =
      `ตรวจใหม่ทุกรอบเสร็จสิ้น — สำเร็จ ${res.ok} ฉบับ`
      + (res.failed ? ` · ไม่สำเร็จ ${res.failed} ฉบับ (ถือว่ายังไม่ได้ตรวจ)` : '');
    showToast(`ตรวจใหม่ทุกรอบเสร็จแล้ว: สำเร็จ ${res.ok} ฉบับ`
      + (res.failed ? `, ไม่สำเร็จ ${res.failed} ฉบับ — กด "ตรวจซ้ำเฉพาะที่ไม่สำเร็จ" ได้เลย` : ''),
      res.failed ? 'error' : 'success');
    loadBatchTargets();
    updateReviewButton();
    loadAiOverview();
    loadRecheckQueue();
    loadFeedback();
    paintBatchRetry();
    paintBatchResume();
  }
}

// --------------------------------------- ตรวจใหม่ทั้งคิว (ต้นฉบับถูกแก้หลังตรวจ)
async function startRecheckQueue() {
  if (batchRunning) { showToast('กำลังตรวจชุดอื่นอยู่ กรุณารอให้เสร็จก่อน', 'error'); return; }
  const items = aiRecheckList.filter(r => !r.too_short);
  if (!items.length) { showToast('ไม่มีฉบับที่พร้อมตรวจใหม่', 'error'); return; }
  if (!confirm(`ให้ AI ตรวจใหม่ ${items.length} ฉบับที่นักเรียนแก้ไขต้นฉบับแล้ว ใช่ไหม?\n\n`
      + `ใช้เวลาประมาณ ${Math.max(1, Math.round(items.length * 27000 / 60000))} นาที `
      + `กรุณาเปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`)) return;

  batchRunning = true;
  batchStopRequested = false;
  const startBtn = document.getElementById('rcStartBtn');
  const stopBtn  = document.getElementById('rcStopBtn');
  if (startBtn) startBtn.disabled = true;
  if (stopBtn)  stopBtn.classList.remove('d-none');
  document.getElementById('rcLog').innerHTML = '';

  let res = { ok: 0, failed: 0 };
  try {
    res = await runReviewQueue(items.map(r => ({
      student_id:   r.student_id,
      student_name: r.student_name,
      essay_phase:  r.essay_phase,
      note:         r.phase_label,
    })), REVIEW_UI_RECHECK, {
      kind: 'recheck', label: 'ตรวจใหม่ทั้งคิว (นักเรียนแก้ต้นฉบับหลัง AI ตรวจ)', room: '',
    });
  } finally {
    batchRunning = false;
    if (startBtn) startBtn.disabled = false;
    if (stopBtn)  stopBtn.classList.add('d-none');
    document.getElementById('rcProgressLabel').textContent =
      `เสร็จสิ้น — สำเร็จ ${res.ok} ฉบับ` + (res.failed ? ` · ไม่สำเร็จ ${res.failed} ฉบับ` : '');
    showToast(`ตรวจใหม่เสร็จแล้ว: สำเร็จ ${res.ok} ฉบับ` + (res.failed ? `, ไม่สำเร็จ ${res.failed} ฉบับ` : ''),
      res.failed ? 'error' : 'success');
    updateReviewButton();
    loadAiOverview();
    loadRecheckQueue();
    loadFeedback();
    if (document.getElementById('batchSummary')) loadBatchTargets();
    paintBatchResume();
  }
}

// ------------------------------------------------------- ตั้งค่า AI (ครู)
async function loadAiSettings() {
  try {
    const res  = await fetch('api.php?action=get_ai_settings');
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'โหลดการตั้งค่าไม่สำเร็จ', 'error'); return; }
    aiProviders = data.providers;

    const sel = document.getElementById('aiProvider');
    sel.innerHTML = data.providers.map(p =>
      `<option value="${esc(p.key)}">${esc(p.label)}</option>`).join('');
    sel.value = data.settings.provider;

    document.getElementById('aiModel').value    = data.settings.model || '';
    document.getElementById('aiBaseUrl').value  = data.settings.base_url || '';
    document.getElementById('aiEnabled').checked = !!data.settings.enabled;

    const limInput = document.getElementById('aiDailyLimit');
    if (limInput) {
      limInput.value = data.settings.daily_limit || '';
      limInput.min   = data.settings.daily_limit_min || 50;
      limInput.max   = data.settings.daily_limit_max || 5000;
      document.getElementById('aiDailyLimitHint').textContent =
        `ตั้งได้ ${data.settings.daily_limit_min}–${data.settings.daily_limit_max} ครั้ง/วัน `
        + `(ค่าเริ่มต้นของระบบคือ ${data.settings.daily_limit_default} ครั้ง)`;
    }

    const hint = document.getElementById('aiKeyHint');
    if (data.settings.locked_by_file) {
      hint.innerHTML = '<span class="text-success"><i class="bi bi-shield-lock me-1"></i>ใช้คีย์จากไฟล์ ai_secrets.php บนเซิร์ฟเวอร์ '
        + '(' + esc(data.settings.api_key_masked) + ') — ค่าที่กรอกในหน้านี้จะไม่ถูกใช้</span>';
    } else if (data.settings.has_key) {
      hint.innerHTML = 'มีคีย์บันทึกไว้แล้ว: <code>' + esc(data.settings.api_key_masked) + '</code> · เว้นว่างไว้ = ใช้คีย์เดิม';
    } else {
      hint.textContent = 'ยังไม่มี API key ในระบบ';
    }

    onProviderChange(true);
    renderUsage(data.usage);
  } catch (err) {
    console.error(err);
  }
}

function onProviderChange(initial) {
  const key = document.getElementById('aiProvider').value;
  const p   = aiProviders.find(x => x.key === key);
  const hint = document.getElementById('aiProviderHint');
  if (!p) { hint.textContent = ''; return; }
  hint.innerHTML = p.key_url
    ? `ขอ API key ฟรีได้ที่ <a href="${esc(p.key_url)}" target="_blank" rel="noopener">${esc(p.key_url)}</a>`
    : 'กรอก Base URL ของเซิร์ฟเวอร์เองในช่องด้านขวา';
  // เปลี่ยนผู้ให้บริการ = เสนอโมเดล/URL เริ่มต้นของเจ้านั้นให้ (ไม่ทับตอนโหลดหน้าครั้งแรก)
  if (!initial) {
    document.getElementById('aiModel').value   = p.default_model || '';
    document.getElementById('aiBaseUrl').value = p.default_base_url || '';
  }
}

// ล้างชื่อโมเดลที่บันทึกไว้ เพื่อกลับไปใช้ค่าเริ่มต้นของผู้ให้บริการที่กำหนดไว้ในโค้ด
// (ใช้เมื่อผู้ให้บริการเลิกให้บริการโมเดลรุ่นเดิม แล้วระบบขึ้นว่า "ไม่พบโมเดลที่ตั้งค่าไว้")
function useDefaultModel() {
  document.getElementById('aiModel').value = '';
  showToast('ล้างชื่อโมเดลแล้ว — กด "บันทึกการตั้งค่า" เพื่อใช้โมเดลเริ่มต้นของผู้ให้บริการ');
}

function renderUsage(usage) {
  const box = document.getElementById('aiUsageBox');
  if (!usage || !usage.length) { box.innerHTML = '<i class="bi bi-graph-up me-1"></i>ยังไม่มีการเรียกใช้ AI'; return; }
  const rows = usage.map(u =>
    `<tr><td>${esc(u.d)}</td><td class="text-center">${u.total}</td><td class="text-center text-success">${u.ok}</td>
     <td class="text-center text-danger">${u.total - u.ok}</td></tr>`).join('');
  box.innerHTML = `<div class="fw-bold mb-2"><i class="bi bi-graph-up me-1"></i>การเรียกใช้ AI ย้อนหลัง 7 วัน</div>
    <table class="table table-sm table-bordered mb-0" style="max-width:420px;">
      <thead class="table-light"><tr><th>วันที่</th><th class="text-center">รวม</th><th class="text-center">สำเร็จ</th><th class="text-center">ไม่สำเร็จ</th></tr></thead>
      <tbody>${rows}</tbody></table>`;
}

async function saveAiSettings() {
  const btn = document.getElementById('aiSaveSettingsBtn');
  btn.disabled = true;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'save_ai_settings',
        provider: document.getElementById('aiProvider').value,
        model: document.getElementById('aiModel').value.trim(),
        base_url: document.getElementById('aiBaseUrl').value.trim(),
        api_key: document.getElementById('aiApiKey').value.trim(),
        enabled: document.getElementById('aiEnabled').checked,
        daily_limit: document.getElementById('aiDailyLimit').value.trim()
      })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'บันทึกไม่สำเร็จ', 'error'); return; }
    document.getElementById('aiApiKey').value = '';
    showToast('บันทึกการตั้งค่าเรียบร้อยแล้ว');
    await loadAiSettings();
    await loadAiStatus();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  } finally {
    btn.disabled = false;
  }
}

async function clearApiKey() {
  if (!confirm('ยืนยันลบ API key ออกจากระบบ? ผู้ช่วย AI จะใช้งานไม่ได้จนกว่าจะใส่คีย์ใหม่')) return;
  try {
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'save_ai_settings', api_key: '__CLEAR__' })
    });
    const data = await res.json();
    if (!data.success) { showToast(data.error || 'ลบไม่สำเร็จ', 'error'); return; }
    showToast('ลบ API key เรียบร้อยแล้ว');
    await loadAiSettings();
    await loadAiStatus();
  } catch (err) {
    showToast('เชื่อมต่อไม่สำเร็จ', 'error');
  }
}
<?php endif; ?>

// Init
(async function () {
  const url = new URLSearchParams(window.location.search);
  const ph  = url.get('phase');

  if (!AI_IS_STUDENT) {
    await loadStudents();
    const sid = url.get('student_id');
    const sel = document.getElementById('aiStudentSelect');
    if (sid && sel) sel.value = sid;
  }
<?php if ($aiIsTeacher): ?>
  await loadAiSettings();
<?php endif; ?>
  await loadAiStatus();
<?php if ($aiIsTeacher): ?>
  await loadBatchRooms();
  if (ph && AI_PHASE_LABELS[ph]) document.getElementById('batchPhase').value = ph;
  await loadBatchTargets();
  // มีคิวค้างจากการตรวจครั้งก่อนไหม (โควตาหมด/กดหยุด/ปิดหน้าไปกลางคัน) → กางการ์ดเสนอให้ตรวจต่อ
  paintBatchResume(true);
<?php endif; ?>
  await loadFeedback();
  // มีรอบงานระบุมาทาง URL (เช่นลิงก์จากหน้าเรียงความนักเรียน) → เปิดรายละเอียดฉบับนั้นให้ทันที
  if (ph && aiAllFeedback[ph]) selectPhase(ph);
  if (!AI_IS_STUDENT) {
    loadAiOverview();
    loadRecheckQueue();
    loadPhaseOverviews();
  }
})();
</script>

<?php require_once 'footer.php'; ?>
