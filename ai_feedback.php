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
  <!-- เลือกนักเรียนที่จะดูผล (ครู/ผู้เชี่ยวชาญ) — รอบงานไม่ต้องเลือก เพราะขึ้นให้ครบทุกฉบับอยู่แล้ว -->
  <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #0d7377 !important;">
    <div class="card-body p-4">
      <label class="form-label fw-bold small">นักเรียน</label>
      <select id="aiStudentSelect" class="form-select border-2 rounded-3" onchange="onSelectionChange()">
        <option value="">— กำลังโหลดรายชื่อ —</option>
      </select>
      <div id="aiQuotaText" class="text-muted small mt-3"></div>
    </div>
  </div>
<?php else: ?>
  <div id="aiQuotaText" class="text-muted small mb-3"></div>
<?php endif; ?>

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

<?php if ($aiIsTeacher): ?>
  <!-- แถบเครื่องมือของคุณครู — เก็บงานตั้งค่า/ตรวจทั้งรอบไว้ในลิ้นชัก ไม่ให้บังแผงผลตรวจซึ่งใช้บ่อยที่สุด -->
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <span class="fw-bold text-muted small me-1"><i class="bi bi-tools me-1"></i>เครื่องมือของคุณครู</span>
    <button class="btn btn-outline-primary btn-sm rounded-pill px-3" type="button"
            data-bs-toggle="collapse" data-bs-target="#aiBatchCard">
      <i class="bi bi-lightning-charge-fill me-1"></i>ตรวจทั้งรอบรวดเดียว
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
        </div>
      </div>

      <div id="batchSummary" class="mt-3 small text-muted">กำลังโหลดรายการ...</div>

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

<?php if (!$aiIsStudent): ?>
  <!-- ภาพรวมทั้งชั้น (ครู/ผู้เชี่ยวชาญ) -->
  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4 rounded-top-4">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h6 class="fw-bold text-dark mb-0"><i class="bi bi-table text-secondary me-2"></i>ภาพรวมผลตรวจ AI ทั้งชั้น</h6>
          <div class="text-muted small mt-1">คะแนนรวมของนักเรียนแต่ละคนทุกรอบงาน พร้อมค่าเฉลี่ยรายบุคคลและค่าเฉลี่ยทั้งชั้น</div>
        </div>
        <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="loadAiOverview()">
          <i class="bi bi-arrow-clockwise me-1"></i>รีเฟรช
        </button>
      </div>
      <div class="row g-2 mt-2">
        <div class="col-sm-6 col-lg-5">
          <input type="search" id="aiOverviewSearch" class="form-control form-control-sm rounded-3"
                 placeholder="ค้นหารหัส / ชื่อ / ห้อง" oninput="paintAiOverview()">
        </div>
        <div class="col-sm-6 col-lg-4">
          <select id="aiOverviewPhase" class="form-select form-select-sm rounded-3" onchange="paintAiOverview()">
            <option value="">ทุกรอบงาน (แสดงครบทุกคอลัมน์)</option>
            <?php foreach ($aiPhases as $ph): ?>
            <option value="<?php echo $ph; ?>"><?php echo htmlspecialchars(ai_phase_label($ph)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-3 d-flex align-items-center">
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
<?php endif; ?>
</div>

<style>
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

  if (!sid) {
    cards.innerHTML = `<div class="col-12">${emptyBox('เลือกนักเรียนด้านบนเพื่อดูผลตรวจของ AI ทุกรอบงาน')}</div>`;
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

// คะแนนรวม (เต็ม 60) ของฉบับหนึ่ง — ยังไม่มีผลตรวจคืน null
function aiCombinedOf(fb) {
  if (!fb) return null;
  const v = Number(fb.combined_total != null ? fb.combined_total : fb.total_score);
  return isNaN(v) ? null : v;
}

// การ์ด 1 ใบต่อ 1 รอบงาน — เห็นคะแนนของตัวเองครบทุกฉบับในหน้าเดียว
function paintPhaseCards() {
  const box = document.getElementById('aiPhaseCards');
  if (!box || !aiCardsReady || !currentStudentId()) return;

  const blocked = reviewBlockReason();

  box.innerHTML = AI_CARD_GROUPS.map(g => {
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
  }).join('');
}

// การ์ดคะแนนของรอบงานหนึ่ง
function phaseCardHTML(ph, blocked) {
  const fb    = aiAllFeedback[ph];
  const essay = aiEssayStatus[ph];
  const label = AI_PHASE_LABELS[ph] || ph;
  const on    = (selectedPhase === ph);

  let body, foot = '', cls = 'ai-phase-card', badges = '';

  if (fb) {
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
    body = `<div class="d-flex align-items-end gap-2">
        <div class="display-6 fw-bold text-primary lh-1">${aiNum(combined)}</div>
        <div class="text-muted pb-1">/ ${aiNum(fullMax)} คะแนน</div>
      </div>
      <div class="ai-score-bar mt-2"><span style="width:${pct}%"></span></div>
      <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-1">
        <span class="badge bg-primary-subtle text-primary-emphasis">ระดับ${fb.manual_done ? '' : 'โดยประมาณ'}: ${esc(level)}</span>
        <span class="text-muted small">AI ${aiNum(fb.total_score)}/${aiNum(fb.max_score)}
          · ครู ${fb.manual_done ? aiNum(fb.teacher_total) : '—'}/${aiNum(fb.manual_max)}</span>
      </div>`;
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
        <span class="fw-bold text-dark">${esc(label)}</span>
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
        recheckAction: AI_IS_TEACHER ? `runAiReview('${phase}')` : ''
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

// แปลงคะแนนรวม (เต็ม 60) เป็นระดับคุณภาพ — เกณฑ์เดียวกับหน้า evaluation.php และ ai_config.php
function aiLevelFromScore(total60) {
  const n = parseFloat(total60);
  if (isNaN(n)) return '';
  if (n >= 49) return 'ดีมาก';
  if (n >= 37) return 'ดี';
  if (n >= 25) return 'ปานกลาง';
  if (n >= 13) return 'พอใช้';
  return 'ต้องปรับปรุง';
}

// ป้ายระดับคุณภาพ ใช้สีเดียวกับการ์ดเกณฑ์ในหน้าประเมิน (ดีมาก=เขียวอมฟ้า ... ต้องปรับปรุง=แดง)
const AI_LEVEL_STYLE = {
  'ดีมาก':       'background:#ccfbf1; color:#0f766e; border:1px solid #99f6e4;',
  'ดี':          'background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe;',
  'ปานกลาง':     'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;',
  'พอใช้':       'background:#fffbeb; color:#b45309; border:1px solid #fde68a;',
  'ต้องปรับปรุง': 'background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;',
};
function aiLevelBadge(level) {
  if (!level) return '<span class="text-muted">-</span>';
  const style = AI_LEVEL_STYLE[level] || 'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;';
  return `<span class="badge rounded-pill px-3 py-2 fw-semibold" style="${style}">${esc(level)}</span>`;
}

// ตัดทศนิยมท้ายที่ไม่จำเป็นออก (45.00 → 45, 45.50 → 45.5)
function aiNum(v) {
  const n = Math.round(parseFloat(v) * 100) / 100;
  return isNaN(n) ? '-' : String(n);
}

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

  // เลือกรอบงานไว้ = ดูเฉพาะคอลัมน์นั้น (ค่าเฉลี่ยคิดจากคอลัมน์ที่แสดงอยู่)
  const cols = phase ? AI_OVERVIEW_COLS.filter(c => c.key === phase) : AI_OVERVIEW_COLS;
  const fullMax = aiOverviewList[0] ? aiOverviewList[0].full_max : 60;

  const students = aiOverviewByStudent().filter(stu => {
    const shown = cols.map(c => stu.cells[c.key]).filter(Boolean);
    if (!shown.length) return false;                                   // ไม่มีผลตรวจในคอลัมน์ที่ดูอยู่
    if (needScore && !shown.some(r => !r.manual_done)) return false;   // ให้คะแนนครบแล้วทุกฉบับ
    if (needRecheckOnly && !shown.some(r => r.needs_recheck)) return false;  // ไม่มีฉบับที่รอตรวจใหม่
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
                + (r.needs_recheck ? ' · ต้นฉบับถูกแก้หลังตรวจ รอตรวจใหม่' : '');
      return `<td class="ai-cell-score${r.needs_recheck ? ' ai-cell-stale' : ''}" title="${tip}"
                  onclick="jumpTo('${esc(stu.student_id)}','${esc(c.key)}')">
        <span class="fw-bold">${aiNum(r.combined_total)}</span>${r.manual_done
          ? ''
          : '<span class="ai-cell-wait" title="ยังรอคะแนนข้อที่คุณครูต้องให้เอง">*</span>'}${r.needs_recheck
          ? '<i class="bi bi-arrow-repeat ai-cell-stale-icon" title="ต้นฉบับถูกแก้หลังตรวจ รอตรวจใหม่"></i>'
          : ''}
      </td>`;
    }).join('');

    const avgRaw = cnt ? (sum / cnt) : null;
    const avg    = cnt ? aiNum(avgRaw) : '-';
    return `<tr>
      <td class="stu-id text-start">${esc(stu.student_id)}</td>
      <td class="stu-name">${esc(stu.student_name || '-')}${stu.classroom
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
    </div>`;
}

async function jumpTo(sid, phase) {
  const sSel = document.getElementById('aiStudentSelect');
  if (sSel && sSel.value !== sid) { sSel.value = sid; }
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
    const res  = await fetch('api.php?action=get_students_list');
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

  // ประเมินเวลาคร่าว ๆ: AI ใช้เวลาราว 25 วินาทีต่อฉบับ บวกจังหวะพักระหว่างฉบับ
  const mins = Math.max(1, Math.round(queue.length * (25000 + BATCH_GAP_MS) / 60000));

  let html = `<i class="bi bi-list-check me-1"></i>ส่งแล้ว <strong>${batchTargets.length}</strong> ฉบับ`
    + ` · ตรวจไปแล้ว <strong>${done}</strong> · <strong class="text-primary">จะตรวจรอบนี้ ${queue.length} ฉบับ</strong>`;
  if (reQueued > 0) {
    html += `<br><i class="bi bi-arrow-repeat text-warning me-1"></i>`
      + `<strong class="text-warning-emphasis">รอตรวจใหม่ ${reQueued} ฉบับ</strong> (นักเรียนแก้ไขต้นฉบับหลัง AI ตรวจไปแล้ว)`;
  }
  if (tooShort > 0) {
    html += `<br><i class="bi bi-exclamation-triangle text-warning me-1"></i>`
      + `ข้าม ${tooShort} ฉบับที่สั้นกว่า ${batchTargets.__minWords} คำ (ระบบไม่ส่งให้ AI ตรวจ)`;
  }
  if (queue.length > 0) {
    html += `<br><i class="bi bi-clock me-1"></i>ใช้เวลาประมาณ ${mins} นาที — เปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`;
    if (aiStatus && aiStatus.quota_left < queue.length) {
      html += `<br><i class="bi bi-battery-low text-danger me-1"></i>`
        + `<strong class="text-danger">โควตาวันนี้เหลือ ${aiStatus.quota_left} ครั้ง ไม่พอตรวจครบ</strong> — ระบบจะตรวจเท่าที่โควตาเหลือ`;
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
 * คืนค่า { ok, failed } — ผู้เรียกเป็นคนจัดการปุ่ม/ข้อความสรุปเอง
 */
async function runReviewQueue(items, ui) {
  let ok = 0, failed = 0, i = 0;
  for (const t of items) {
    if (batchStopRequested) {
      reviewLogLine(ui, 'bi-stop-circle', 'text-secondary', 'หยุดตามคำสั่ง', `ตรวจไปแล้ว ${i} ฉบับ`);
      break;
    }
    const who = t.student_name + (t.note ? ` (${t.note})` : '');
    setReviewProgress(ui, i, items.length, `กำลังตรวจ: ${who}`);

    let data = await aiRequestReview(t.student_id, t.essay_phase);

    // โดนจำกัดจำนวนคำขอต่อนาที → พักแล้วลองใหม่อีกครั้งเดียว
    if (!data.success && /โควตาฟรีของผู้ให้บริการ|429/.test(data.error || '')) {
      reviewLogLine(ui, 'bi-hourglass-split', 'text-warning', who, 'ผู้ให้บริการจำกัดอัตราคำขอ กำลังพักแล้วลองใหม่');
      await sleep(BATCH_RATELIMIT_MS);
      if (batchStopRequested) break;
      data = await aiRequestReview(t.student_id, t.essay_phase);
    }

    i++;
    if (data.success) {
      ok++;
      t.reviewed = true;
      t.needs_recheck = false;
      const fb = data.feedback || {};
      reviewLogLine(ui, 'bi-check-circle-fill', 'text-success', who,
        `${fb.total_score}/${fb.max_score} · ${fb.quality_level || '-'}`);
      if (typeof data.quota_left === 'number' && aiStatus) {
        aiStatus.quota_left = data.quota_left;
        aiStatus.quota_used = aiStatus.quota_limit - data.quota_left;
      }
    } else {
      failed++;
      reviewLogLine(ui, 'bi-x-circle-fill', 'text-danger', who, data.error || 'ตรวจไม่สำเร็จ');
      // โควตารายวันหมด = ตรวจต่อไปก็ไม่ผ่าน หยุดทั้งชุดเลยดีกว่าปล่อยให้พังทีละฉบับ
      if (/ใช้ AI ตรวจครบ/.test(data.error || '')) {
        reviewLogLine(ui, 'bi-battery', 'text-danger', 'หยุดอัตโนมัติ', 'โควตารายวันหมดแล้ว');
        break;
      }
    }

    setReviewProgress(ui, i, items.length, `ตรวจแล้ว ${i} จาก ${items.length} ฉบับ`);
    if (i < items.length && !batchStopRequested) await sleep(BATCH_GAP_MS);
  }
  return { ok, failed };
}

async function startBatchReview() {
  if (batchRunning) return;
  const queue = batchQueue();
  if (!queue.length) return;

  const phase      = document.getElementById('batchPhase').value;
  const phaseLabel = AI_PHASE_LABELS[phase] || '';
  const reQueued   = queue.filter(t => t.needs_recheck).length;
  if (!confirm(`เริ่มให้ AI ตรวจ ${queue.length} ฉบับของ "${phaseLabel}" ใช่ไหม?\n`
      + (reQueued ? `(ในจำนวนนี้เป็นฉบับที่แก้ไขต้นฉบับแล้วรอตรวจใหม่ ${reQueued} ฉบับ)\n` : '')
      + `\nใช้เวลาประมาณ ${Math.max(1, Math.round(queue.length * 27000 / 60000))} นาที `
      + `กรุณาเปิดหน้านี้ค้างไว้จนกว่าจะเสร็จ`)) return;

  batchRunning = true;
  batchStopRequested = false;
  document.getElementById('batchStartBtn').disabled = true;
  document.getElementById('batchStopBtn').classList.remove('d-none');
  document.getElementById('batchPhase').disabled = true;
  document.getElementById('batchRoom').disabled = true;
  document.getElementById('batchLog').innerHTML = '';

  let res = { ok: 0, failed: 0 };
  try {
    res = await runReviewQueue(queue.map(t => ({
      student_id:   t.student_id,
      student_name: t.student_name,
      essay_phase:  phase,
      note:         t.needs_recheck ? 'ตรวจใหม่' : '',
    })), REVIEW_UI_BATCH);
  } finally {
    batchRunning = false;
    document.getElementById('batchStopBtn').classList.add('d-none');
    document.getElementById('batchPhase').disabled = false;
    document.getElementById('batchRoom').disabled = false;
    document.getElementById('batchProgressLabel').textContent =
      `เสร็จสิ้น — สำเร็จ ${res.ok} ฉบับ` + (res.failed ? ` · ไม่สำเร็จ ${res.failed} ฉบับ` : '');
    showToast(`ตรวจเสร็จแล้ว: สำเร็จ ${res.ok} ฉบับ` + (res.failed ? `, ไม่สำเร็จ ${res.failed} ฉบับ` : ''),
      res.failed ? 'error' : 'success');
    loadBatchTargets();
    updateReviewButton();
    loadAiOverview();
    loadRecheckQueue();
    loadFeedback();
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
    })), REVIEW_UI_RECHECK);
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
        enabled: document.getElementById('aiEnabled').checked
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
<?php endif; ?>
  await loadFeedback();
  // มีรอบงานระบุมาทาง URL (เช่นลิงก์จากหน้าเรียงความนักเรียน) → เปิดรายละเอียดฉบับนั้นให้ทันที
  if (ph && aiAllFeedback[ph]) selectPhase(ph);
  if (!AI_IS_STUDENT) {
    loadAiOverview();
    loadRecheckQueue();
  }
})();
</script>

<?php require_once 'footer.php'; ?>
