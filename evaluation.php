<?php
$page_title = 'แบบประเมิน - ระบบประเมินเรียงความ';
require_once 'auth_helper.php';

// ดึงโหมดที่ระบุมา
$mode_param = isset($_GET['mode']) ? $_GET['mode'] : '';
$currentMode = '';

// รอบที่ระบุมาทาง URL (เช่นลิงก์จากรายการ "สิ่งที่ยังไม่ได้ทำ") เพื่อข้ามหน้าเลือกรอบไปเริ่มประเมินรอบนั้นทันที
$phase_param = isset($_GET['phase']) ? $_GET['phase'] : '';
if (!in_array($phase_param, ['pretest', 'task1', 'task2', 'posttest'], true)) {
    $phase_param = '';
}

// นักเรียนที่ระบุมาทาง URL (เช่นลิงก์ "ให้คะแนน" จากรายการงานที่ครูยังไม่ได้ตรวจ)
// เพื่อกระโดดไปที่ "คนนี้ + รอบนี้" ได้ทันทีโดยไม่ต้องค้นรายชื่อเอง — รับเฉพาะรหัสนักเรียนเท่านั้น
$student_param = isset($_GET['student']) ? trim((string)$_GET['student']) : '';
if (!preg_match('/^[A-Za-z0-9_-]{1,20}$/', $student_param)) {
    $student_param = '';
}

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

<?php /* เนื้อหาหน้าประเมินอยู่ภายใน .app-content ตามปกติ เพื่อให้แถบบน (topbar) และเลย์เอาต์แถบข้างวางถูกตำแหน่ง */ ?>
<div class="eval-fullwidth">
<div id="view-evaluation" class="text-start">
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

<?php if ($sessionUser['role'] === 'teacher'): ?>
  <!-- แดชบอร์ดการส่งงาน (ยุบ/ขยายได้) — ช่วยให้คุณครูเห็นสถานะการส่งงานของนักเรียนขณะให้คะแนน -->
  <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
    <button type="button" class="w-100 border-0 text-start px-4 py-3 d-flex align-items-center justify-content-between"
            style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%); color:#fff;"
            data-bs-toggle="collapse" data-bs-target="#evalSubDash" aria-expanded="false" onclick="loadEvalSubmissionDash()">
      <span class="fw-bold"><i class="bi bi-table me-2"></i>แดชบอร์ดการส่งงานของนักเรียน</span>
      <span class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark" id="evalDashRate">-</span>
        <i class="bi bi-chevron-down"></i>
      </span>
    </button>
    <div class="collapse" id="evalSubDash">
      <div class="p-3">
        <div class="row g-2 mb-3" id="evalDashStats">
          <div class="col-6 col-md-3"><div class="p-2 rounded-3 bg-light text-center"><div class="small text-muted">นักเรียนทั้งหมด</div><div class="fw-bold fs-5" id="evalDashTotal">-</div></div></div>
          <div class="col-6 col-md-3"><div class="p-2 rounded-3 bg-light text-center"><div class="small text-muted">ส่งครบทุกชิ้น</div><div class="fw-bold fs-5 text-success" id="evalDashComplete">-</div></div></div>
          <div class="col-6 col-md-3"><div class="p-2 rounded-3 bg-light text-center"><div class="small text-muted">ยังไม่ครบ</div><div class="fw-bold fs-5 text-warning" id="evalDashPartial">-</div></div></div>
          <div class="col-6 col-md-3"><div class="p-2 rounded-3 bg-light text-center"><div class="small text-muted">อัตราการส่ง</div><div class="fw-bold fs-5 text-primary" id="evalDashRate2">-</div></div></div>
        </div>
        <div class="table-responsive" style="max-height: 340px; overflow-y:auto;">
          <table class="table table-sm table-hover align-middle mb-0" style="min-width: 720px;">
            <thead class="table-light" style="position:sticky; top:0; z-index:1;">
              <tr class="text-center small">
                <th class="text-start">รหัส</th>
                <th class="text-start">ชื่อ-สกุล</th>
                <th>ก่อนเรียน</th><th>D1.1</th><th>D1.2</th><th>D2.1</th><th>D2.2</th><th>หลังเรียน</th>
                <th>สะท้อนคิด<br>ห1</th><th>สะท้อนคิด<br>ห2</th><th>รวม</th>
              </tr>
            </thead>
            <tbody id="evalDashBody">
              <tr><td colspan="11" class="text-center text-muted py-3">กดเพื่อโหลดข้อมูล...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="text-end mt-2">
          <a href="submission_report.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">เปิดรายงานฉบับเต็ม <i class="bi bi-box-arrow-up-right"></i></a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // โหลดข้อมูลแดชบอร์ดการส่งงานแบบครั้งเดียว (lazy) เมื่อกดเปิด และรีเฟรชเมื่อเปลี่ยนกลุ่มการวิจัย
    let evalDashLoaded = false;
    async function loadEvalSubmissionDash(force) {
      if (evalDashLoaded && !force) return;
      evalDashLoaded = true;
      const body = document.getElementById('evalDashBody');
      const param = (window.TEG ? TEG.param() : '');
      try {
        const res = await (await fetch('api.php?action=get_submission_report' + param)).json();
        if (!res.success) { body.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-3">' + (res.error || 'โหลดข้อมูลไม่สำเร็จ') + '</td></tr>'; return; }
        const rows = res.report || [];
        const ic = on => on ? '<i class="bi bi-check-circle-fill text-primary"></i>' : '<i class="bi bi-dash-circle text-secondary opacity-50"></i>';
        const CELLS = ['pretest','d1_1','d1_2','d2_1','d2_2','posttest'];
        const REFL = ['problems1','checklist1','reflection1','problems2','checklist2','reflection2'];
        let complete = 0, doneCells = 0;
        const totalCells = rows.length * 12;
        const html = rows.map(s => {
          const essayDone = CELLS.reduce((a,k)=>a+(s[k]?1:0),0);
          const r1 = ['problems1','checklist1','reflection1'].every(k=>s[k]);
          const r2 = ['problems2','checklist2','reflection2'].every(k=>s[k]);
          const d = essayDone + REFL.reduce((a,k)=>a+(s[k]?1:0),0);
          doneCells += d;
          if (d === 12) complete++;
          return '<tr class="text-center small">' +
            '<td class="text-start font-monospace">' + s.student_id + '</td>' +
            '<td class="text-start">' + (s.student_name || '-') + '</td>' +
            CELLS.map(k => '<td>' + ic(!!s[k]) + '</td>').join('') +
            '<td>' + ic(r1) + '</td><td>' + ic(r2) + '</td>' +
            '<td><span class="badge ' + (d===12?'bg-success':'bg-secondary') + ' rounded-pill">' + d + '/12</span></td>' +
          '</tr>';
        }).join('');
        body.innerHTML = html || '<tr><td colspan="11" class="text-center text-muted py-3">ไม่พบข้อมูลนักเรียนในกลุ่มนี้</td></tr>';
        const total = rows.length;
        const rate = totalCells ? Math.round(doneCells / totalCells * 100) : 0;
        document.getElementById('evalDashTotal').textContent = total;
        document.getElementById('evalDashComplete').textContent = complete;
        document.getElementById('evalDashPartial').textContent = total - complete;
        document.getElementById('evalDashRate2').textContent = rate + '%';
        document.getElementById('evalDashRate').textContent = 'ส่งครบ ' + complete + '/' + total;
      } catch (e) {
        body.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-3">เกิดข้อผิดพลาดในการเชื่อมต่อ</td></tr>';
      }
    }
    // เมื่อเปลี่ยนกลุ่มการวิจัยบนแถบบน ให้รีเฟรชแดชบอร์ดถ้าเปิดอยู่
    (function(){
      const prev = window.onTEGChange;
      window.onTEGChange = function(){ if (typeof prev === 'function') prev(); if (evalDashLoaded) loadEvalSubmissionDash(true); };
    })();
  </script>
<?php endif; ?>

  <!-- Phase Picker Overlay (แสดงก่อนเริ่มกรอกแบบประเมิน) -->
  <div id="phasePicker" class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5 <?php echo ($sessionUser['role'] === 'expert') ? 'd-none' : ''; ?>">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-journal-check me-2"></i>เลือกรอบการประเมิน</h4>
          <p class="text-white-50 mb-0 small">กรุณาเลือกรอบการประเมินก่อนเริ่มกรอกแบบประเมิน</p>
        </div>
        <span class="badge bg-white text-dark px-3 py-2 fs-6 fw-bold"><?php echo $currentMode; ?></span>
      </div>
    </div>
    <div class="p-4 bg-white">
      <?php
        // นักเรียนประเมินเฉพาะ ภาระงานหน่วยที่ 1 / หน่วยที่ 2 (ไม่มี Pretest/Posttest)
        // ครูยังคงเห็นรอบ ก่อนเรียน/หน่วยที่ 1/หน่วยที่ 2/หลังเรียน เพื่อใช้วัดผลก่อน-หลังเรียนในงานวิจัย
        $isStudentEval = ($sessionUser['role'] === 'student');
        $phaseColClass = $isStudentEval ? 'col-md-6 col-12' : 'col-md-3 col-6';
      ?>
      <div class="row g-3">
        <?php if (!$isStudentEval): ?>
        <div class="<?php echo $phaseColClass; ?>">
          <button type="button" class="phase-btn w-100 btn btn-outline-primary rounded-3 p-3 text-center fw-bold" data-phase="pretest" onclick="selectPhase('pretest')">
            <div class="fs-2 mb-2">📝</div>
            <div class="fw-bold">ก่อนเรียน</div>
            <div class="text-muted small">Pretest (T1)</div>
          </button>
        </div>
        <?php endif; ?>
        <div class="<?php echo $phaseColClass; ?>">
          <button type="button" class="phase-btn w-100 btn btn-outline-success rounded-3 p-3 text-center fw-bold" data-phase="task1" onclick="selectPhase('task1')">
            <div class="fs-2 mb-2">📚</div>
            <div class="fw-bold">ภาระงาน หน่วยที่ 1</div>
            <div class="text-muted small">ให้คะแนนจากร่างที่ 2 (D2)</div>
          </button>
        </div>
        <div class="<?php echo $phaseColClass; ?>">
          <button type="button" class="phase-btn w-100 btn btn-outline-success rounded-3 p-3 text-center fw-bold" data-phase="task2" onclick="selectPhase('task2')">
            <div class="fs-2 mb-2">📗</div>
            <div class="fw-bold">ภาระงาน หน่วยที่ 2</div>
            <div class="text-muted small">ให้คะแนนจากร่างที่ 2 (D2)</div>
          </button>
        </div>
        <?php if (!$isStudentEval): ?>
        <div class="<?php echo $phaseColClass; ?>">
          <button type="button" class="phase-btn w-100 btn btn-outline-danger rounded-3 p-3 text-center fw-bold" data-phase="posttest" onclick="selectPhase('posttest')">
            <div class="fs-2 mb-2">🎓</div>
            <div class="fw-bold">หลังเรียน</div>
            <div class="text-muted small">Posttest (T2)</div>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div id="evalSection" class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5 <?php echo ($sessionUser['role'] === 'expert') ? '' : 'd-none'; ?>">
    <div class="p-4 position-relative text-white" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h4 class="fw-bold mb-1">กรอกแบบประเมินความสามารถงานเขียน</h4>
          <p class="text-white-50 mb-0 small font-light">โปรดรีวิวย่อหน้าผลงานแล้วทำเครื่องหมายเลือกเกณฑ์คุณภาพที่ตรงตามจริง (ซ่อนตัวเลขคะแนน/ตัวคูณลดอคติ)</p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span id="selectedPhaseBadge" class="badge bg-white text-dark px-3 py-2 fs-7 fw-bold"></span>
          <span id="roleBadge" class="badge bg-white bg-opacity-25 text-white px-3 py-2 fs-7"><?php echo $currentMode; ?></span>
          <?php if ($sessionUser['role'] !== 'expert'): ?>
          <button type="button" class="btn btn-sm btn-light rounded-pill px-3 fw-bold" onclick="resetPhase()">
            <i class="bi bi-arrow-left-short"></i> เปลี่ยนรอบ
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <form id="evalForm" class="p-4">
      <input type="hidden" id="selectedTestPhase" value="<?php echo ($sessionUser['role'] === 'expert') ? 'task1' : ''; ?>">
      <!-- ข้อมูลนักเรียนเป้าหมายที่ได้รับการประเมิน -->
      <div class="card border-0 rounded-3 p-4 mb-4" style="background-color: var(--light-blue);">
        <div class="row align-items-end">
          <div class="col-md-8 col-sm-12">
            <?php if ($mode_param === 'peer'): ?>
            <!-- โหมดเพื่อนประเมิน (นักเรียน): เลือกกลุ่มด้วยปุ่มในฟอร์ม เพราะนักเรียนไม่มีปุ่มกลุ่มบน navbar -->
            <div class="mb-3">
              <label class="form-label fw-bold text-secondary small text-uppercase tracking-wider d-block mb-2">
                เลือกกลุ่ม (รายชื่อจะแสดงเฉพาะกลุ่มที่เลือก)
              </label>
              <div id="groupFilterButtons" class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-3 fw-bold group-btn active" data-group="">ทุกกลุ่ม</button>
                <button type="button" class="btn btn-outline-primary rounded-3 fw-bold group-btn" data-group="กลุ่มทดลอง">🧪 กลุ่มทดลอง</button>
                <button type="button" class="btn btn-outline-primary rounded-3 fw-bold group-btn" data-group="กลุ่มตัวอย่าง">📋 กลุ่มตัวอย่าง</button>
              </div>
              <input type="hidden" id="groupFilterValue" value="">
            </div>
            <?php endif; ?>

            <!-- ระบุรหัสหรือชื่อนักเรียนเป้าหมาย แล้วข้อมูลจะปรากฏขึ้น -->
            <label for="targetStudentInput" class="form-label fw-bold text-secondary small text-uppercase tracking-wider">ระบุรหัสหรือชื่อนักเรียนที่เป็นเป้าหมายผู้ถูกประเมิน <span class="text-danger">*</span></label>
            <!-- ช่องเดียว: คลิกเพื่อเลือกจากรายการ (dropdown) หรือพิมพ์ค้นหาด้วยรหัส/ชื่อก็ได้ -->
            <div class="input-group input-group-lg shadow-sm">
              <span class="input-group-text bg-white border-2 border-end-0"><i class="bi bi-person-vcard text-primary"></i></span>
              <input type="text" id="targetStudentInput" list="targetStudentOptions" autocomplete="off" class="form-control border-2 border-start-0 fw-semibold text-dark" placeholder="คลิกเพื่อเลือกนักเรียน หรือพิมพ์ค้นหาด้วยรหัส/ชื่อ...">
              <button type="button" id="loadStudentBtn" class="btn btn-primary fw-bold px-4"><i class="bi bi-box-arrow-in-down me-1"></i> แสดงข้อมูล</button>
            </div>
            <datalist id="targetStudentOptions"></datalist>
            <div id="targetStudentResolved" class="mt-2 small fw-bold text-success d-none"></div>
            <div id="targetStudentError" class="mt-2 small fw-bold text-danger d-none"></div>
            <?php if ($mode_param === 'self'): ?>
            <p id="selfEvalNotice" class="mt-2 text-primary small fw-bold mb-0">
              <i class="bi bi-info-circle-fill"></i> ระบบจำกัดการเลือกเฉพาะข้อมูลของคุณเนื่องจากกำลังทำโหมด "ตนเองประเมิน"
            </p>
            <?php endif; ?>
            <?php if ($mode_param === 'peer'): ?>
            <p id="peerLockNotice" class="mt-2 text-success small fw-bold mb-0 d-none">
              <i class="bi bi-lock-fill"></i> ระบบล็อกผู้ถูกประเมินตามคู่ที่คุณจับกับเพื่อนไว้แล้ว (จับคู่ไป-กลับอัตโนมัติ) ไม่ต้องระบุเอง
            </p>
            <p id="peerFallbackNotice" class="mt-2 text-danger small fw-bold mb-0 d-none">
              <i class="bi bi-exclamation-triangle-fill"></i> ยังไม่มีการจับคู่สำหรับหน่วยนี้ กรุณาไปที่หน้า
              <a href="peer_matching.php" class="fw-bold text-decoration-underline">🤝 จับคู่ประเมินเพื่อน</a>
              เพื่อส่งคำขอจับคู่กับเพื่อนก่อน แล้วจึงกลับมาประเมิน
            </p>
            <?php endif; ?>
            <?php if ($mode_param === 'expert'): ?>
            <p class="mt-2 text-primary small fw-bold mb-0">
              <i class="bi bi-lock-fill"></i> ระบบจำกัดเฉพาะนักเรียน "กลุ่มทดลอง" หน่วยที่ 1 เท่านั้น
            </p>
            <?php endif; ?>
          </div>
          <?php if ($sessionUser['role'] === 'expert'): ?>
          <!-- ผู้เชี่ยวชาญตรวจเฉพาะ หน่วยที่ 1 เท่านั้น (ล็อกไว้ ไม่มีหน่วยที่ 2 และ Pretest/Posttest) -->
          <div class="col-md-4 col-sm-12 mt-3 mt-md-0">
            <label class="form-label fw-bold text-secondary small text-uppercase tracking-wider">หน่วยการเรียน <span class="text-danger">*</span></label>
            <div class="d-flex gap-2">
              <span class="btn btn-success btn-sm fw-bold flex-fill rounded-3 py-2 disabled" aria-disabled="true">
                📚 หน่วยที่ 1
              </span>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-md-<?php echo ($sessionUser['role'] === 'expert') ? '12 mt-2' : '4'; ?> col-sm-12 text-md-end mt-3 mt-md-0">
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
              <div id="evaluationProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
            </div>
          </div>
          <div class="col-md-4 col-12 text-md-end mt-2 mt-md-0">
            <span id="progressText" class="fw-bold small text-secondary">ตอบแล้ว 0 จาก 11 ข้อ</span>
          </div>
        </div>

        <!-- สรุปคะแนนที่คำนวณอัตโนมัติ (แสดงสด), คะแนน/ระดับเดิมที่เคยประเมินไว้, คะแนนรอบคู่เทียบ (ก่อน-หลังเรียน / หน่วย 1-2) และคะแนนรวมจากระบบตรวจอัตโนมัติ -->
        <div class="row g-3 mt-1">
          <!-- คะแนนที่ระบบคำนวณอัตโนมัติจากตัวเลือกปัจจุบัน -->
          <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div class="d-flex align-items-center justify-content-between rounded-3 px-3 py-2 h-100" style="background:#eff6ff; border:1px solid #bfdbfe;">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-calculator-fill text-primary fs-5"></i>
                <span class="fw-bold small text-primary-emphasis">คะแนนที่คำนวณอัตโนมัติ</span>
              </div>
              <div class="text-end">
                <span id="liveScoreValue" class="fw-bold fs-5 text-primary">0</span>
                <span class="text-muted small">/ 60</span>
                <span id="liveScoreLevel" class="badge bg-primary-subtle text-primary-emphasis ms-1">—</span>
              </div>
            </div>
          </div>
          <!-- คะแนน/ระดับเดิมที่เคยบันทึกไว้ (โหมดแก้ไข) -->
          <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div id="originalScoreBox" class="d-none d-flex align-items-center justify-content-between rounded-3 px-3 py-2 h-100" style="background:#fefce8; border:1px solid #fde68a;">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-warning fs-5"></i>
                <span class="fw-bold small" style="color:#92400e;">คะแนนเดิมที่เคยให้</span>
              </div>
              <div class="text-end">
                <span id="originalScoreValue" class="fw-bold fs-5" style="color:#b45309;">0</span>
                <span class="text-muted small">/ 60</span>
                <span id="originalScoreLevel" class="badge ms-1" style="background:#fde68a; color:#92400e;">—</span>
              </div>
            </div>
          </div>
          <!-- คะแนนรอบคู่เทียบ (pretest↔posttest, task1↔task2) ให้ดูพัฒนาการของนักเรียนตอนให้คะแนน -->
          <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div id="comparisonScoreBox" class="d-none d-flex align-items-center justify-content-between rounded-3 px-3 py-2 h-100" style="background:#ecfdf5; border:1px solid #a7f3d0;">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-graph-up-arrow text-success fs-5"></i>
                <span class="fw-bold small" id="comparisonScoreLabel" style="color:#065f46;">คะแนนรอบเปรียบเทียบ</span>
              </div>
              <div class="text-end">
                <span id="comparisonScoreValue" class="fw-bold fs-5" style="color:#047857;">0</span>
                <span class="text-muted small">/ 60</span>
                <span id="comparisonScoreLevel" class="badge ms-1" style="background:#a7f3d0; color:#065f46;">—</span>
              </div>
            </div>
          </div>
          <!-- คะแนนรวมที่ระบบตรวจอัตโนมัติตรวจให้ในรอบเดียวกัน (ข้อมูลประกอบการพิจารณา ไม่ใช่คะแนนที่บันทึก) -->
          <div class="col-xl-3 col-lg-6 col-md-6 col-12">
            <div id="aiScoreBox" class="d-none d-flex align-items-center justify-content-between rounded-3 px-3 py-2 h-100" style="background:#f5f3ff; border:1px solid #ddd6fe;">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-check fs-5" style="color:#7c3aed;"></i>
                <span class="fw-bold small" id="aiScoreLabel" style="color:#5b21b6;">คะแนนรวมอัตโนมัติ</span>
              </div>
              <div class="text-end">
                <span id="aiScoreValue" class="fw-bold fs-5" style="color:#6d28d9;">0</span>
                <span class="text-muted small">/ <span id="aiScoreMax">60</span></span>
                <span id="aiScoreLevel" class="badge ms-1" style="background:#ede9fe; color:#5b21b6;">—</span>
              </div>
            </div>
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
              <textarea id="peerStrengthField" name="peer_strength" class="form-control form-control-sm flex-grow-1" rows="5" placeholder="ระบุข้อดี จุดเด่น สิ่งที่น่ายกย่อง..." oninput="scheduleEvalDraftSave()"></textarea>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="card border-0 rounded-3 p-3 bg-white shadow-sm h-100" style="border-top: 3px solid #f59e0b !important;">
              <label class="form-label fw-bold text-warning-emphasis small mb-2"><i class="bi bi-arrow-up-circle-fill text-warning"></i> จุดที่ควรปรับปรุงและข้อเสนอแนะ</label>
              <p class="small text-muted mb-2" style="font-size:0.78rem;">ระบุจุดบกพร่องที่พบและเสนอวิธีการแก้ไขอย่างตรงไปตรงมา</p>
              <textarea id="peerImprovementField" name="peer_improvement" class="form-control form-control-sm flex-grow-1" rows="5" placeholder="ระบุจุดบกพร่อง วิธีแก้..." oninput="scheduleEvalDraftSave()"></textarea>
            </div>
          </div>
          <div class="col-md-4 col-sm-12">
            <div class="card border-0 rounded-3 p-3 bg-white shadow-sm h-100" style="border-top: 3px solid #8b5cf6 !important;">
              <label class="form-label fw-bold" style="color:#6d28d9; font-size:0.83rem;"><i class="bi bi-emoji-smile-fill" style="color:#8b5cf6"></i> ข้อความให้กำลังใจเพื่อน</label>
              <p class="small text-muted mb-2" style="font-size:0.78rem;">เขียนข้อความให้กำลังใจและส่งเสริมเพื่อนให้อยากพัฒนาต่อไป</p>
              <textarea id="peerEncouragementField" name="peer_encouragement" class="form-control form-control-sm flex-grow-1" rows="5" placeholder="เขียนให้กำลังใจ..." oninput="scheduleEvalDraftSave()"></textarea>
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

  <!-- ส่วนเนื้อหาเรียงความ: แยกออกจาก evalSection เป็น section ลอย (fixed) ฝั่งขวา เต็มครึ่งจอ ติดตามหน้าจอเสมอเวลาเลื่อน -->
  <aside id="essayFloating" class="card border-0 shadow-lg d-none" style="overflow: hidden; border: 1px solid #e7e5e4 !important;">
    <div class="essay-panel-header d-flex align-items-center justify-content-between gap-2 px-4 py-3 border-bottom" style="background-color: #fffdf0;" onclick="toggleEssayCollapse()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleEssayCollapse();}">
      <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-file-earmark-text text-primary"></i>
        <span class="essay-panel-title-full">เนื้อหาเรียงความที่นักเรียนบันทึกไว้ (Student Essay Content)</span>
      </h6>
      <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-mono small" id="essayPanelWordCount">0 คำ</span>
        <button type="button" class="essay-collapse-btn" id="essayCollapseBtn" title="ย่อ/ขยายกล่องเรียงความ" onclick="event.stopPropagation(); toggleEssayCollapse();">
          <i class="bi bi-dash-lg" id="essayCollapseIcon"></i>
        </button>
      </div>
    </div>
    <div class="essay-doc-scroll">
      <div class="essay-sheet">
        <!-- หัวกระดาษแบบข้อสอบ: ชื่อแบบวัด → ชื่อเรื่อง → ชื่อ/ชั้น/รหัสประจำตัวนักเรียน เจ้าของผลงาน -->
        <div class="essay-doc-formtitle" id="essayPanelFormTitle">แบบวัดความสามารถก่อนเรียน</div>
        <h1 class="essay-doc-title" id="essayPanelTitle">—</h1>
        <div class="essay-doc-author" id="essayPanelAuthor"></div>
        <div class="essay-doc-content" id="essayPanelContent">
          <!-- เนื้อหาเรียงความ (พร้อมเส้นบรรทัดและเลขบรรทัดทุกบรรทัด) -->
        </div>
      </div>
    </div>
  </aside>
</div>

<style>
.phase-btn:hover, .phase-btn.active {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  transition: all 0.2s ease;
}
.phase-btn {
  transition: all 0.2s ease;
  min-height: 130px;
}

/* ใช้เลย์เอาต์เต็มความกว้างจอสำหรับหน้าประเมิน */
.eval-fullwidth { width: 100%; }

/* ส่วนเนื้อหาเรียงความ — section ลอย (fixed) ฝั่งขวา เต็มครึ่งจอ ติดตามหน้าจอเสมอเวลาเลื่อน (แสดงแบบเอกสารเหมือน essay_print.php) */
#essayFloating {
  background: #ffffff;
  margin-top: 1.5rem;   /* จอเล็ก/กลาง: แสดงเป็นบล็อกปกติใต้แบบประเมิน */
}
.essay-doc-scroll {
  max-height: 70vh;
  overflow-y: auto;
  background: #eceff3;           /* พื้นเทาอ่อนเพื่อให้แผ่นกระดาษ A4 สีขาวเด่นเหมือนโปรแกรมดูเอกสาร */
  padding: 16px 10px;
}
/* จอใหญ่ (lg ขึ้นไป): แบ่งครึ่งจอ ซ้าย = แบบประเมิน / ขวา = เรียงความ (ลอยติดขอบขวา ตามการเลื่อนเสมอ) ให้กว้างเท่า ๆ กัน */
@media (min-width: 992px) {
  #essayFloating {
    position: fixed;
    top: 66px;
    right: 0;
    width: 50vw;
    height: calc(100vh - 66px);
    margin-top: 0;
    z-index: 1019;
    border-radius: 0;
    border-left: 3px solid var(--primary-navy, #0d3b66) !important;
  }
  .essay-doc-scroll { max-height: calc(100vh - 66px - 62px); }
  /* เว้นครึ่งขวาไว้ให้กล่องเรียงความ แบบประเมินจึงกว้างเท่ากันในครึ่งซ้าย */
  #view-evaluation.essay-open { padding-right: calc(50vw + 1.5rem); }
  /* ย่อกล่องเรียงความไว้แล้ว → ไม่ต้องเว้นที่ครึ่งขวาอีกต่อไป (แบบประเมินขยายเต็มความกว้างคืน) */
  #view-evaluation.essay-open.essay-collapsed-open { padding-right: 0; }
}

/* ปุ่มย่อ/ขยายกล่องเรียงความ */
.essay-panel-header { cursor: pointer; user-select: none; }
.essay-collapse-btn {
  border: none;
  background: transparent;
  color: #92642a;
  cursor: pointer;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 1rem;
  line-height: 1;
  transition: background .15s ease;
}
.essay-collapse-btn:hover { background: rgba(0,0,0,0.06); }

/* สถานะย่อ: เหลือเป็นปุ่มกลมเล็ก ๆ (เฉพาะไอคอน) ที่มุมขวาล่าง ไม่บังปุ่มบันทึกผลการประเมิน */
#essayFloating.essay-collapsed {
  position: fixed !important;
  /* bottom เว้นสูงกว่ากล่อง toast แจ้งเตือน (.toast-container-custom อยู่มุมเดียวกันที่ bottom:20px) ไม่ให้ซ้อนทับกัน */
  inset: auto 16px 90px auto !important;
  top: auto !important;
  width: 46px !important;
  min-width: 0 !important;
  max-width: none !important;
  height: 46px !important;
  margin: 0 !important;
  padding: 0 !important;
  border-radius: 50% !important;
  background: #fffdf0;
  box-shadow: 0 6px 18px rgba(0,0,0,0.22);
  opacity: 0.92;
  z-index: 1035;
  border: 1px solid #e7e5e4 !important;
}
#essayFloating.essay-collapsed:hover { opacity: 1; }
/* ย่อแล้วซ่อนทุกอย่างที่ทำให้กล่องกว้าง: เนื้อเรียงความ ชื่อหัวข้อ และป้ายจำนวนคำ */
#essayFloating.essay-collapsed .essay-doc-scroll,
#essayFloating.essay-collapsed .essay-panel-header h6,
#essayFloating.essay-collapsed #essayPanelWordCount { display: none !important; }
#essayFloating.essay-collapsed .essay-panel-header {
  width: 46px;
  height: 46px;
  padding: 0 !important;
  gap: 0 !important;
  justify-content: center !important;
  border-bottom: none;
  border-radius: 50%;
}
#essayFloating.essay-collapsed .essay-collapse-btn {
  padding: 0;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.05rem;
}

/* แผ่นกระดาษสัดส่วน A4 (210:297 ≈ 1:1.414) — ขยายให้กว้างขึ้นด้านละ ~1 นิ้ว มีระยะขอบเหมือนกระดาษจริง */
.essay-sheet {
  width: 100%;
  max-width: 832px;             /* กว้างขึ้นจากเดิม 640px ≈ ด้านละ 1 นิ้ว (96px) */
  min-height: 1176px;           /* ≈ 832 × 1.414 ให้ได้สัดส่วน A4 */
  margin: 0 auto;
  padding: 56px 52px 64px;      /* ระยะขอบกระดาษ */
  background: #ffffff;
  color: #1a1a1a;
  box-shadow: 0 3px 14px rgba(0,0,0,0.14);
  border: 1px solid #dfe3e8;
  font-family: "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
}
/* ชื่อแบบวัด (หัวกระดาษบนสุด) — 22px */
.essay-doc-formtitle {
  text-align: center;
  font-size: 22px;
  font-weight: 700;
  color: #0d3b66;
  margin: 0 0 0.3rem;
}
/* ชื่อเรื่อง — 20px */
.essay-doc-title {
  text-align: center;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.35;
  color: #1a1a1a;
  margin: 0 0 0.4rem;
}
/* บรรทัดเจ้าของผลงาน: ชื่อ / ชั้น / รหัสประจำตัวนักเรียน — 18px */
.essay-doc-author {
  text-align: center;
  font-size: 18px;
  font-weight: 600;
  color: #444;
  margin: 0 0 1rem;
  padding-bottom: 0.6rem;
  border-bottom: 2px solid #e7e5e4;
}
.essay-doc-author:empty { display: none; }
/* เนื้อความแบบกระดาษมีเส้นบรรทัด (ruled paper) + เว้นที่ซ้ายสำหรับเลขบรรทัด + เว้นที่ขวาสำหรับป้ายส่วน */
.essay-doc-content {
  position: relative;
  padding-left: 2.8em;
  padding-right: 2.6em;        /* เว้นที่ริมขวาไว้ให้ป้ายบอกส่วน (คำนำ/เนื้อเรื่อง/สรุป) แบบไม่กระทบเนื้อหา */
  font-size: 20px;             /* ขนาดเนื้อหาบนกระดาษ A4 */
  line-height: 36px;          /* ต้องตรงกับ LH ในสคริปต์สร้างเลขบรรทัด */
  text-align: justify;
  background-image: linear-gradient(to bottom, transparent 0, transparent 35px, #e3e7ec 35px, #e3e7ec 36px);
  background-size: 100% 36px;
  background-position: 0 0;
}
/* ป้ายบอกส่วน (คำนำ/เนื้อเรื่อง/สรุป) — แอบเล็ก ๆ ที่ริมขวา ไม่รบกวนการอ่าน */
.essay-doc-content .section-tag {
  position: absolute;
  right: 0;
  width: 2.4em;
  text-align: left;
  color: #cbd0d8;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  user-select: none;
  pointer-events: none;
}
.essay-doc-content .essay-para {
  margin: 0;
  text-indent: 2.5em;
}
/* แสดงขอบเขตการตัดคำ (อ่านอย่างเดียว) — เส้นประบาง ๆ ใต้แต่ละคำ ไม่รบกวนการอ่าน */
.essay-doc-content .thai-word {
  border-bottom: 1px dotted #c9c2b3;
}
.essay-doc-content .lnum {
  position: absolute;
  left: 0;
  width: 2.1em;
  text-align: right;
  color: #9aa1ac;
  font-size: 0.9rem;
  font-family: "Tahoma", sans-serif;
  user-select: none;
}
.essay-doc-content .no-content {
  color: #888;
  font-style: italic;
  text-indent: 0;
  text-align: center;
  padding: 2rem 0;
  background: none;
}
</style>

<script>
  let currentMode = "<?php echo $currentMode; ?>";
  let modeParam = "<?php echo $mode_param; ?>";
  const initialPhaseParam = "<?php echo $phase_param; ?>"; // รอบที่ระบุมาจาก URL (ถ้ามี) — ใช้ข้ามหน้าเลือกรอบไปเริ่มประเมินทันที
  const initialStudentParam = "<?php echo $student_param; ?>"; // นักเรียนที่ระบุมาจาก URL (ถ้ามี) — ใช้เปิดฟอร์มของคนนั้นทันที
  let studentDB = {};

  // กันข้อมูลหายกรณีเน็ตหลุด/เซสชันหมดอายุ/ปิดแท็บกลางคัน — เก็บคะแนนที่กำลังให้ + ความเห็นเชิงคุณภาพไว้ใน localStorage ของเครื่อง
  const DRAFT_OWNER_ID = "<?php echo isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : ''; ?>";
  function evalDraftKey(studentId, phase) {
    return `thaieasay_eval_draft_${DRAFT_OWNER_ID}_${currentMode}_${studentId}_${phase}`;
  }
  let evalDraftTimer = null;
  function scheduleEvalDraftSave() {
    clearTimeout(evalDraftTimer);
    evalDraftTimer = setTimeout(saveEvalDraftToLocalStorage, 500);
  }
  function readCurrentEvalFormState() {
    const scores = {};
    document.querySelectorAll('input[type="radio"].score-radio:checked').forEach(radio => {
      scores[radio.name.replace('item_', '')] = radio.value;
    });
    const feedback = (modeParam === 'peer') ? {
      strength: (document.getElementById('peerStrengthField') || {}).value || '',
      improvement: (document.getElementById('peerImprovementField') || {}).value || '',
      encouragement: (document.getElementById('peerEncouragementField') || {}).value || ''
    } : null;
    return { scores, feedback };
  }
  function saveEvalDraftToLocalStorage() {
    const studentId = getTargetId();
    const phase = document.getElementById('selectedTestPhase') ? document.getElementById('selectedTestPhase').value : '';
    if (!studentId || !phase) return;

    const { scores, feedback } = readCurrentEvalFormState();
    const hasAny = Object.keys(scores).length > 0
      || (feedback && (feedback.strength || feedback.improvement || feedback.encouragement));
    try {
      if (hasAny) {
        localStorage.setItem(evalDraftKey(studentId, phase), JSON.stringify({ scores, feedback, savedAt: Date.now() }));
      } else {
        localStorage.removeItem(evalDraftKey(studentId, phase));
      }
    } catch (e) { /* localStorage เต็มหรือถูกปิดใช้งาน — ข้ามไปเงียบ ๆ ไม่กระทบการให้คะแนน */ }
  }
  function clearEvalDraftFromLocalStorage(studentId, phase) {
    try { localStorage.removeItem(evalDraftKey(studentId, phase)); } catch (e) {}
  }
  // ตรวจคะแนน/ความเห็นที่ค้างใน localStorage ของนักเรียน+รอบนี้ (กรอกไว้แต่ไม่ทันกดบันทึกจากครั้งก่อน) แล้วเสนอกู้คืนให้ผู้ใช้เลือกเอง
  function checkAndOfferEvalDraftRestore(studentId, phase) {
    let draft = null;
    try {
      const raw = localStorage.getItem(evalDraftKey(studentId, phase));
      if (raw) draft = JSON.parse(raw);
    } catch (e) { draft = null; }
    if (!draft) return;

    const current = readCurrentEvalFormState();
    const same = JSON.stringify(draft.scores || {}) === JSON.stringify(current.scores)
      && JSON.stringify(draft.feedback || null) === JSON.stringify(current.feedback);
    if (same) {
      // ร่างในเครื่องตรงกับข้อมูลที่แสดงอยู่แล้ว (บันทึกสำเร็จไปก่อนหน้านี้) — ล้างทิ้งได้เลย ไม่ต้องถาม
      clearEvalDraftFromLocalStorage(studentId, phase);
      return;
    }

    const savedAtText = new Date(draft.savedAt).toLocaleString('th-TH');
    const wantRestore = confirm(
      `พบคะแนน/ความเห็นที่เคยกรอกไว้เมื่อ ${savedAtText} แต่ยังไม่ได้กดบันทึก (อาจเกิดจากเน็ตหลุดหรือเซสชันหมดอายุกลางคัน)\n\nต้องการกู้คืนกลับมาแทนที่ข้อมูลที่แสดงอยู่หรือไม่?`
    );
    if (wantRestore) {
      document.querySelectorAll('input[type="radio"].score-radio').forEach(r => { r.checked = false; });
      Object.entries(draft.scores || {}).forEach(([itemId, val]) => {
        const el = document.getElementById(`opt_${itemId}_${val}`);
        if (el) el.checked = true;
      });
      if (draft.feedback) {
        const strEl = document.getElementById('peerStrengthField');
        const impEl = document.getElementById('peerImprovementField');
        const encEl = document.getElementById('peerEncouragementField');
        if (strEl) strEl.value = draft.feedback.strength || '';
        if (impEl) impEl.value = draft.feedback.improvement || '';
        if (encEl) encEl.value = draft.feedback.encouragement || '';
      }
      calculateRealTimeFormScore();
      showToast('กู้คืนคะแนน/ความเห็นที่ยังไม่ได้บันทึกเรียบร้อยแล้ว อย่าลืมกดบันทึกอีกครั้ง', 'success');
    } else {
      clearEvalDraftFromLocalStorage(studentId, phase);
    }
  }

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

  // ค้นหาข้อเกณฑ์ (item) จากรหัส เช่น "1.1" เพื่อดึงตัวคูณและป้ายระดับคะแนนมาแสดงคะแนนเดิมรายข้อ
  function findRubricItemById(itemId) {
    for (const section of rubricData) {
      const found = section.items.find(i => i.id === itemId);
      if (found) return found;
    }
    return null;
  }

  // ไม่บังคับเลือกกลุ่มในฟอร์มอีกต่อไป — โหมดครูควบคุมกลุ่มจากปุ่มบน navbar (จุดเดียวของทั้งระบบ)
  function groupRequired() { return false; }

  // ค่ากลุ่มที่ใช้กรองรายชื่อ
  //  - โหมดครู: ใช้กลุ่มที่เลือกจากปุ่มบน navbar (ค่ากลาง TEG)
  //  - โหมดเพื่อน (นักเรียน): ใช้ปุ่มเลือกกลุ่มในฟอร์ม
  function getGroupValue() {
    if (modeParam === 'teacher') return (window.TEG ? TEG.filterValue() : '');
    const active = document.querySelector('#groupFilterButtons .group-btn.active');
    if (active) return active.dataset.group || '';
    const el = document.getElementById('groupFilterValue');
    return el ? el.value : '';
  }
  // แปลงข้อความที่พิมพ์ (รหัส / "รหัส - ชื่อ" / ชื่อ) → รหัสนักเรียนที่ตรงกัน (ค้นได้ทั้งรหัสและชื่อ)
  function resolveStudentId(raw) {
    const q = (raw || '').trim();
    if (!q) return '';
    if (studentDB[q]) return q;                          // ตรงรหัสพอดี
    const prefix = q.split(' - ')[0].trim();             // รูปแบบ "รหัส - ชื่อ" จากรายการแนะนำ
    if (studentDB[prefix]) return prefix;
    const lower = q.toLowerCase();
    const exact = Object.keys(studentDB).filter(id => (studentDB[id] || '').toLowerCase() === lower);
    if (exact.length === 1) return exact[0];             // ชื่อตรงทั้งหมดและมีคนเดียว
    const partial = Object.keys(studentDB).filter(id => (studentDB[id] || '').toLowerCase().includes(lower));
    if (partial.length === 1) return partial[0];         // ชื่อบางส่วนและเหลือผลเดียว
    return '';
  }

  // รหัสนักเรียนเป้าหมาย — ตีความจากช่องเดียว (เลือกจากรายการ หรือพิมพ์รหัส/ชื่อ)
  function getTargetId() {
    const el = document.getElementById('targetStudentInput');
    return resolveStudentId(el ? el.value : '');
  }

  // ตั้งค่าช่องเป้าหมายให้แสดงเป็น "รหัส - ชื่อ"
  function syncTargetSelection(id) {
    const inp = document.getElementById('targetStudentInput');
    if (inp && studentDB[id] !== undefined) inp.value = `${id} - ${studentDB[id]}`;
  }

  // โหลดรายชื่อนักเรียนจาก API
  async function loadStudents() {
    try {
      const gval = getGroupValue();
      // ครูยังไม่เลือกกลุ่ม → ยังไม่โหลดรายชื่อ (บังคับเลือกกลุ่มก่อน)
      if (groupRequired() && !gval) { studentDB = {}; return; }

      const params = [];
      // นักเรียน (โหมดประเมินตนเอง/ประเมินเพื่อน) เห็นเฉพาะรายชื่อเพื่อนห้องเดียวกัน
      if (modeParam === 'peer' || modeParam === 'self') params.push('classmates=1');
      // ตัวกรองกลุ่ม (ทดลอง/ตัวอย่าง) — ผู้เชี่ยวชาญถูกบังคับกลุ่มทดลองที่ฝั่งเซิร์ฟเวอร์
      if (gval) params.push('group=' + encodeURIComponent(gval));
      const qs = params.length ? '&' + params.join('&') : '';
      const response = await fetch(`api.php?action=get_students_list${qs}&_t=${new Date().getTime()}`);
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

  // เติมรายชื่อนักเรียนลงใน datalist ของช่องเดียว (คลิกเพื่อเลือก หรือพิมพ์ค้นด้วยรหัส/ชื่อ)
  function populateStudentDatalist() {
    const dl = document.getElementById('targetStudentOptions');
    if (!dl) return;
    const sortedKeys = Object.keys(studentDB).sort();
    dl.innerHTML = '';
    sortedKeys.forEach(id => {
      const option = document.createElement('option');
      // value = "รหัส - ชื่อ" เพื่อให้พิมพ์ค้นได้ทั้งรหัสและชื่อ และเลือกจากรายการได้
      option.value = `${id} - ${studentDB[id]}`;
      dl.appendChild(option);
    });
  }

  // ปิด/เปิดการใช้งานส่วนรูบริกให้จางลงเมื่อยังไม่มีเป้าหมาย
  function dimRubric() {
    const rubricCont = document.getElementById('rubricContainer');
    const progressCont = document.getElementById('progressContainer');
    if (rubricCont) rubricCont.classList.add('opacity-60', 'pointer-events-none');
    if (progressCont) progressCont.classList.add('d-none');
  }

  // ตรวจสอบรหัสนักเรียนที่พิมพ์ แล้วโหลดข้อมูล (ข้อมูลจะปรากฏเมื่อรหัสถูกต้อง)
  function resolveTargetStudent() {
    const resolvedEl = document.getElementById('targetStudentResolved');
    const errEl = document.getElementById('targetStudentError');
    if (resolvedEl) resolvedEl.classList.add('d-none');
    if (errEl) errEl.classList.add('d-none');

    // อ่านสิ่งที่ผู้ใช้ระบุจากช่องเดียว (เลือกจากรายการ หรือพิมพ์รหัส/ชื่อ)
    const inp = document.getElementById('targetStudentInput');
    const rawTyped = inp ? inp.value.trim() : '';
    const hasTyped = rawTyped !== '';
    const id = getTargetId();

    if (!id) {
      if (errEl) {
        errEl.textContent = hasTyped
          ? `⚠️ ไม่พบนักเรียนที่ตรงกับ "${rawTyped}" (ลองพิมพ์รหัส หรือ ชื่อให้ชัดเจนขึ้น)`
          : '⚠️ กรุณาเลือกนักเรียนจากรายการ หรือพิมพ์ค้นหาด้วยรหัส/ชื่อก่อน';
        errEl.classList.remove('d-none');
      }
      dimRubric();
      return;
    }
    // พบแล้ว → ซิงก์ทั้ง dropdown และช่องค้นหา แล้วแสดงชื่อ/โหลดข้อมูลเดิม
    syncTargetSelection(id);
    if (resolvedEl) {
      resolvedEl.textContent = `✓ ผู้ถูกประเมิน: ${id} - ${studentDB[id]}`;
      resolvedEl.classList.remove('d-none');
    }
    checkExistingEvaluation(id);
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
              <input type="radio" name="item_${item.id}" value="${level.score}" data-multiplier="${item.multiplier}" data-raw="${level.score}" id="opt_${item.id}_${level.score}" class="score-radio" required onchange="calculateRealTimeFormScore(); scheduleEvalDraftSave();">
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
          <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-slate-800">${item.name}</h5>
            <div class="d-flex align-items-center flex-wrap gap-2">
              <span id="compareScore_${item.id}" class="badge d-none rounded-pill px-3 py-2" style="background:#d1fae5; color:#065f46; border:1px solid #a7f3d0;">
                <i class="bi bi-graph-up-arrow me-1"></i><span class="compare-score-text"></span>
              </span>
              <span id="prevScore_${item.id}" class="badge d-none rounded-pill px-3 py-2" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                <i class="bi bi-clock-history me-1"></i><span class="prev-score-text"></span>
              </span>
              <span id="aiScore_${item.id}" class="badge d-none rounded-pill px-3 py-2" style="background:#ede9fe; color:#5b21b6; border:1px solid #ddd6fe;">
                <i class="bi bi-file-earmark-check me-1"></i><span class="ai-score-text"></span>
              </span>
            </div>
          </div>
          <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">${levelsHTML}</div>
          <div id="aiNote_${item.id}" class="ai-eval-note d-none mt-3"></div>
        `;
        cardBody.appendChild(itemDiv);
      });
      sectionDiv.appendChild(cardBody);
      container.appendChild(sectionDiv);
    });
  }

  // แปลงคะแนนรวมเป็นระดับคุณภาพ (ใช้เกณฑ์เดียวกันทุกบทบาททุกภาระงาน)
  function scoreLevelText(total) {
    if (total >= 49) return 'ดีมาก';
    if (total >= 37) return 'ดี';
    if (total >= 25) return 'ปานกลาง';
    if (total >= 13) return 'พอใช้';
    return 'ต้องปรับปรุง';
  }

  // อัปเดตแถบความคืบหน้า (Progress Bar) และคะแนนที่คำนวณอัตโนมัติแบบสด
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

    // อัปเดตคะแนนที่คำนวณอัตโนมัติแบบสด ให้ผู้ประเมินเห็นคะแนน/ระดับทันทีทุกบทบาททุกภาระงาน
    let liveTotal = 0;
    totalInputs.forEach(radio => {
      liveTotal += (parseFloat(radio.value) * parseFloat(radio.dataset.multiplier));
    });
    const liveTotalRounded = Math.round(liveTotal * 100) / 100;
    const liveValueEl = document.getElementById('liveScoreValue');
    const liveLevelEl = document.getElementById('liveScoreLevel');
    if (liveValueEl) liveValueEl.textContent = liveTotalRounded;
    if (liveLevelEl) liveLevelEl.textContent = totalInputs.length > 0 ? scoreLevelText(liveTotal) : '—';

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

  // ตัวนับลำดับคำขอ — กันกรณีคำตอบของคำขอเก่ากลับมาช้ากว่าคำขอใหม่แล้วเขียนทับสถานะบนหน้าจอ
  // (เคยทำให้ป้ายสถานะขึ้นว่า "รายการใหม่" ทั้งที่โหลดคะแนนเดิมของรอบที่เลือกมาแล้ว)
  let evalLoadSeq = 0;

  // ดึงข้อมูลการประเมินเก่ามาใส่ในฟอร์ม
  async function checkExistingEvaluation(studentId) {
    const rubricCont = document.getElementById('rubricContainer');
    const statusBadge = document.getElementById('loadOldDataStatus');
    const progressCont = document.getElementById('progressContainer');

    // ยังไม่ได้เลือกรอบ (ก่อนเรียน/หน่วยที่ 1/หน่วยที่ 2/หลังเรียน) → ยังไม่ต้องโหลดอะไร
    // เพราะการค้นหาโดยไม่มีรอบจะไม่พบข้อมูลเสมอ และทำให้ขึ้นว่า "รายการใหม่" ทั้งที่มีคะแนนเดิมอยู่
    const selectedPhase = document.getElementById('selectedTestPhase') ? document.getElementById('selectedTestPhase').value : '';
    if (!selectedPhase) return false;
    const mySeq = ++evalLoadSeq;
    
    rubricCont.classList.remove('opacity-60', 'pointer-events-none');
    document.getElementById('evalForm').reset();
    const tInput = document.getElementById('targetStudentInput');
    if (tInput) tInput.value = studentId;
    if(progressCont) progressCont.classList.remove('d-none');
    // ซ่อนกล่องคะแนนเดิมไว้ก่อน จนกว่าจะพบข้อมูลประเมินเดิมของนักเรียนคนนี้
    const origBox = document.getElementById('originalScoreBox');
    if (origBox) origBox.classList.add('d-none');
    // ซ่อนป้ายคะแนนเดิมรายข้อทั้งหมดไว้ก่อนเช่นกัน (เผื่อสลับไปดูนักเรียนคนอื่น)
    document.querySelectorAll('[id^="prevScore_"]').forEach(el => el.classList.add('d-none'));
    // ซ่อนกล่อง/ป้ายคะแนนรอบคู่เทียบไว้ก่อนเช่นกัน (เผื่อสลับไปดูนักเรียนคนอื่น)
    const cmpBox = document.getElementById('comparisonScoreBox');
    if (cmpBox) cmpBox.classList.add('d-none');
    document.querySelectorAll('[id^="compareScore_"]').forEach(el => el.classList.add('d-none'));
    // ซ่อนป้าย/หมายเหตุจากระบบตรวจของทุกข้อไว้ก่อน (ผลตรวจเป็นของนักเรียนคนเดิม/รอบเดิม)
    clearAiNotes();
    calculateRealTimeFormScore(); // รีเซ็ต

    statusBadge.textContent = "กำลังค้นหาคะแนนเดิม...";
    statusBadge.className = "badge bg-info text-white fs-8 px-3 py-2 rounded-pill";
    statusBadge.classList.remove('d-none');

    const testPhase = selectedPhase;

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
      if (mySeq !== evalLoadSeq) return false; // มีคำขอใหม่กว่าตามมาแล้ว — ทิ้งผลลัพธ์นี้ไป

      if(res.success && res.found) {
        statusBadge.textContent = `✓ บันทึกแล้ว · ${phaseLabels[testPhase] || testPhase} (เปิดแก้ไขได้)`;
        statusBadge.className = "badge bg-success text-white fs-8 px-3 py-2 rounded-pill";

        for (const [itemId, rawValue] of Object.entries(res.scores)) {
          const inputs = document.querySelectorAll(`input[name="item_${itemId}"]`);
          if(inputs.length > 0) {
             const multiplier = parseFloat(inputs[0].dataset.multiplier);
             const scoreLevel = Math.round(parseFloat(rawValue) / multiplier);
             const targetRadio = document.getElementById(`opt_${itemId}_${scoreLevel}`);
             if(targetRadio) targetRadio.checked = true;
          }

          // แสดงป้ายคะแนนเดิมของข้อนี้ (เคยให้ระดับ/คะแนนเท่าไรไว้ในรอบนี้) ไว้เทียบกับตัวเลือกที่กำลังจะให้ครั้งนี้
          // ใช้คำว่า "คะแนนเดิม" ให้ตรงกับกล่องสรุปด้านบน — ไม่ใช้คำว่า "ครั้งก่อน/ครั้งหลัง" เพราะสับสนกับรอบคู่เทียบ (ก่อนเรียน/หลังเรียน ฯลฯ)
          const badge = document.getElementById(`prevScore_${itemId}`);
          if (badge) {
            const item = findRubricItemById(itemId);
            const pointsRaw = parseFloat(rawValue);
            const level = item ? Math.round(pointsRaw / item.multiplier) : null;
            const levelInfo = item && level !== null ? item.levels.find(l => l.score === level) : null;
            const pointsText = Math.round(pointsRaw * 100) / 100;
            const textEl = badge.querySelector('.prev-score-text');
            if (textEl) {
              textEl.textContent = `คะแนนเดิม: ${levelInfo ? levelInfo.label + ' · ' : ''}${pointsText} คะแนน`;
            }
            badge.classList.remove('d-none');
          }
        }

        // แสดงคะแนน/ระดับเดิมที่เคยบันทึกไว้ (อันเดิมให้อะไร ระดับอะไร)
        // ใช้ค่าที่บันทึกในฐานข้อมูลก่อน ถ้าไม่มีจึงคำนวณจากคะแนนรายข้อ
        let origTotal = (res.totalScore !== undefined && res.totalScore !== null)
          ? parseFloat(res.totalScore)
          : Object.values(res.scores).reduce((sum, v) => sum + parseFloat(v), 0);
        const origLevel = (res.qualityLevel && String(res.qualityLevel).trim() !== '')
          ? res.qualityLevel
          : scoreLevelText(origTotal);
        const origBox   = document.getElementById('originalScoreBox');
        const origValEl = document.getElementById('originalScoreValue');
        const origLvlEl = document.getElementById('originalScoreLevel');
        if (origValEl) origValEl.textContent = Math.round(origTotal * 100) / 100;
        if (origLvlEl) origLvlEl.textContent = origLevel;
        if (origBox)   origBox.classList.remove('d-none');

        calculateRealTimeFormScore();
      } else {
        statusBadge.textContent = `✏️ ยังไม่ได้บันทึก · ${phaseLabels[testPhase] || testPhase} (รายการใหม่)`;
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
      // มีคะแนน/ความเห็นที่เคยกรอกไว้แต่ไม่ทันกดบันทึกค้างอยู่ในเครื่องหรือไม่ (เน็ตหลุด/เซสชันหมดอายุกลางคัน) — เสนอกู้คืน
      checkAndOfferEvalDraftRestore(studentId, testPhase);
      // โหลดเรียงความของนักเรียนมาแสดงประกอบการให้คะแนนด้วย
      fetchStudentEssayForEvaluation(studentId, testPhase);
      // โหลดคะแนนของรอบคู่เทียบ (ก่อนเรียน↔หลังเรียน, หน่วย 1↔หน่วย 2) มาแสดงเทียบพัฒนาการ
      fetchComparisonPhaseScores(studentId, testPhase);
      // โหลดผลตรวจของระบบมาแปะเป็น "หมายเหตุจากระบบตรวจ" ในแต่ละข้อย่อย (ไว้ประกอบการตัดสินใจ ไม่ใช่คะแนนจริง)
      fetchAiNotesForEvaluation(studentId, testPhase);
      return !!(res.success && res.found);
    } catch (err) {
      console.error(err);
      statusBadge.textContent = "⚠️ ไม่สามารถตรวจสอบประวัติได้";
      statusBadge.className = "badge bg-danger text-white fs-8 px-3 py-2 rounded-pill";
      return false;
    }
  }

  // แปลงเนื้อหาเรียงความให้แสดงเป็นย่อหน้าเรียงความล้วน (คล้ายเอกสารใน essay_print.php)
  // ไม่มีกล่องสีหรือป้ายกำกับส่วน เพื่อให้ผู้ประเมินอ่านได้เหมือนเรียงความจริง
  function escapeHTML(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function nl2brSafe(s) {
    return escapeHTML(s).replace(/\n/g, '<br>');
  }

  // ตัดคำภาษาไทยด้วย Intl.Segmenter (ตัวเดียวกับที่ใช้นับคำในหน้าเขียนเรียงความ) เพื่อแสดงขอบเขตคำ
  // ให้ผู้ประเมินเห็นว่าระบบตัดคำตรงไหนบ้าง — เป็นการแสดงผลอย่างเดียว ไม่ใช่การแก้ไข
  let __evalWordSegmenter = null;
  if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
    try { __evalWordSegmenter = new Intl.Segmenter('th', { granularity: 'word' }); } catch (e) { __evalWordSegmenter = null; }
  }
  function wordSegmentedHTML(s) {
    if (!__evalWordSegmenter) return nl2brSafe(s);
    let html = '';
    for (const part of __evalWordSegmenter.segment(s)) {
      const esc = escapeHTML(part.segment).replace(/\n/g, '<br>');
      html += part.isWordLike ? `<span class="thai-word">${esc}</span>` : esc;
    }
    return html;
  }

  // แยกเนื้อหาเรียงความ (JSON หรือข้อความล้วน) ออกเป็นย่อหน้าพร้อม "ส่วน" (intro/body/concl)
  function parseEssayParas(contentStr) {
    const paras = []; // { text, sec }  (sec = 'intro' | 'body' | 'concl' | '')
    if (contentStr) {
      let parsed = null;
      try { parsed = JSON.parse(contentStr); } catch (e) { parsed = null; }
      if (parsed && typeof parsed === 'object' && parsed.introduction !== undefined) {
        if (parsed.introduction && parsed.introduction.trim()) paras.push({ text: parsed.introduction, sec: 'intro' });
        if (Array.isArray(parsed.body)) {
          parsed.body.forEach(p => { if (p && p.trim()) paras.push({ text: p, sec: 'body' }); });
        }
        if (parsed.conclusion && parsed.conclusion.trim()) paras.push({ text: parsed.conclusion, sec: 'concl' });
      } else {
        // ข้อความล้วน — แยกย่อหน้าด้วยการเว้นบรรทัด (ตรวจส่วนไม่ได้)
        String(contentStr).split(/\n{2,}/).forEach(p => { if (p.trim()) paras.push({ text: p, sec: '' }); });
      }
    }
    return paras;
  }

  function formatEssayHTML(contentStr) {
    const paras = parseEssayParas(contentStr);
    if (!paras.length) return '<div class="no-content">— ยังไม่มีเนื้อหาเรียงความ —</div>';
    // ทำเครื่องหมาย data-sec เฉพาะย่อหน้า "แรก" ของแต่ละส่วน เพื่อวางป้ายเพียงจุดเดียวต่อส่วน
    let seenBody = false;
    return paras.map(p => {
      let mark = '';
      if (p.sec === 'intro') mark = ' data-sec="intro"';
      else if (p.sec === 'concl') mark = ' data-sec="concl"';
      else if (p.sec === 'body' && !seenBody) { mark = ' data-sec="body"'; seenBody = true; }
      return `<p class="essay-para"${mark}>${wordSegmentedHTML(p.text)}</p>`;
    }).join('');
  }

  // วางป้ายบอกส่วน (คำนำ/เนื้อเรื่อง/สรุป) แบบแอบเล็ก ๆ ที่ริมขวา ตรงกับย่อหน้าแรกของแต่ละส่วน
  function addEssaySectionTags() {
    const box = document.getElementById('essayPanelContent');
    if (!box) return;
    box.querySelectorAll('.section-tag').forEach(el => el.remove());
    const labelMap = { intro: 'คำนำ', body: 'เนื้อเรื่อง', concl: 'สรุป' };
    box.querySelectorAll('.essay-para[data-sec]').forEach(p => {
      const key = p.getAttribute('data-sec');
      if (!labelMap[key]) return;
      const tag = document.createElement('span');
      tag.className = 'section-tag';
      tag.textContent = labelMap[key];
      tag.style.top = p.offsetTop + 'px'; // จัดให้ตรงกับย่อหน้า
      box.appendChild(tag);
    });
  }

  // สร้างเลขบรรทัด "ทุกบรรทัด" (1, 2, 3, ...) ที่ขอบซ้ายของเนื้อความ ให้ตรงกับเส้นบรรทัดของกระดาษ
  // คำนวณจำนวนบรรทัดจริงหลังจัดหน้าเสร็จ แล้ววางตัวเลขตามระยะบรรทัด (LH)
  function addEssayLineNumbers() {
    const box = document.getElementById('essayPanelContent');
    if (!box) return;
    box.querySelectorAll('.lnum').forEach(el => el.remove());
    if (box.querySelector('.no-content')) return; // ไม่มีเนื้อหา → ไม่ต้องใส่เลขบรรทัด
    const LH = parseFloat(getComputedStyle(box).lineHeight) || 36; // ต้องตรงกับ line-height ของ .essay-doc-content
    const lines = Math.round(box.clientHeight / LH);
    for (let i = 1; i <= lines; i++) {
      const s = document.createElement('span');
      s.className = 'lnum';
      s.textContent = i;
      s.style.top = ((i - 1) * LH) + 'px';
      box.appendChild(s);
    }
  }

  // คำนวณเลขบรรทัดใหม่เมื่อปรับขนาดหน้าจอ (ความกว้างเปลี่ยน จำนวนบรรทัดเปลี่ยน)
  let essayLnumTimer = null;
  window.addEventListener('resize', () => {
    const essay = document.getElementById('essayFloating');
    if (!essay || essay.classList.contains('d-none') || essay.classList.contains('essay-collapsed')) return;
    clearTimeout(essayLnumTimer);
    essayLnumTimer = setTimeout(() => { addEssayLineNumbers(); addEssaySectionTags(); }, 150);
  });

  // แสดง/ซ่อน section เรียงความแบบลอย (fixed) ฝั่งขวา และเว้นที่ฝั่งขวาของแบบประเมินไม่ให้ถูกทับ
  // มีเรียงความ → แสดงกล่องลอยฝั่งขวา ติดตามหน้าจอเสมอ | ไม่มีเรียงความ → ซ่อน และแบบประเมินเต็มความกว้างตามเดิม
  function toggleEssayColumn(show) {
    const essay = document.getElementById('essayFloating');
    const view = document.getElementById('view-evaluation');
    if (essay) essay.classList.toggle('d-none', !show);
    if (view) view.classList.toggle('essay-open', show);
    if (show) {
      // เปิดกล่องเรียงความขึ้นมาใหม่ → ใช้สถานะย่อ/ขยายล่าสุดที่ผู้ประเมินเคยตั้งไว้ (จำไว้ข้ามนักเรียน)
      let collapsed = false;
      try { collapsed = localStorage.getItem('teg_essay_panel_collapsed') === '1'; } catch (e) {}
      setEssayCollapsed(collapsed);
    }
  }

  // ย่อ/ขยายกล่องเรียงความ — ย่อไว้จะลอยเป็นปุ่มเล็กที่มุมขวาล่าง ไม่บังแบบประเมิน จำค่าไว้ให้ทุกครั้งที่เปิดกล่องนี้
  function setEssayCollapsed(collapsed) {
    const essay = document.getElementById('essayFloating');
    const view = document.getElementById('view-evaluation');
    const icon = document.getElementById('essayCollapseIcon');
    if (!essay) return;
    essay.classList.toggle('essay-collapsed', collapsed);
    if (view) view.classList.toggle('essay-collapsed-open', collapsed);
    if (icon) icon.className = collapsed ? 'bi bi-arrows-angle-expand' : 'bi bi-dash-lg';
    try { localStorage.setItem('teg_essay_panel_collapsed', collapsed ? '1' : '0'); } catch (e) {}
    if (!collapsed) {
      // ขยายกลับมา → คำนวณเลขบรรทัด/ป้ายส่วนใหม่ (กล่องเพิ่งกลับมาแสดงเต็ม)
      requestAnimationFrame(() => requestAnimationFrame(() => { addEssayLineNumbers(); addEssaySectionTags(); }));
    }
  }
  function toggleEssayCollapse() {
    const essay = document.getElementById('essayFloating');
    if (!essay) return;
    setEssayCollapsed(!essay.classList.contains('essay-collapsed'));
  }

  // ชื่อหัวกระดาษตามรอบการประเมิน
  const essayFormTitleByPhase = {
    pretest:  'แบบวัดความสามารถก่อนเรียน',
    posttest: 'แบบวัดความสามารถหลังเรียน',
    task1:    'แบบฝึกภาระงาน หน่วยที่ 1',
    task2:    'แบบฝึกภาระงาน หน่วยที่ 2'
  };

  // ภาระงานมีร่าง D1/D2 แต่ให้คะแนนเฉพาะร่างที่ 2 (D2) — จึงดึงเรียงความร่าง D2 มาแสดงเวลาประเมินหน่วยภาระงาน
  // (คะแนนยังบันทึกภายใต้รอบ task1 ตามเดิม เพื่อไม่ให้กระทบแดชบอร์ด/การส่งออก)
  const gradingEssayPhase = { pretest: 'pretest', task1: 'task1_d2', task2: 'task2_d2', posttest: 'posttest' };

  // รอบคู่เทียบพัฒนาการ: ก่อนเรียน↔หลังเรียน, หน่วย 1↔หน่วย 2 — ให้เห็นว่าคะแนนแต่ละด้านพัฒนาไปจากเดิมตรงไหนตอนกำลังให้คะแนน
  const comparisonPhaseMap = {
    pretest:  { phase: 'posttest', label: 'คะแนนหลังเรียน (เทียบพัฒนาการ)', shortLabel: 'หลังเรียน' },
    posttest: { phase: 'pretest',  label: 'คะแนนก่อนเรียน (เทียบพัฒนาการ)', shortLabel: 'ก่อนเรียน' },
    task1:    { phase: 'task2',    label: 'คะแนนหน่วยที่ 2 (เทียบพัฒนาการ)', shortLabel: 'หน่วย 2' },
    task2:    { phase: 'task1',    label: 'คะแนนหน่วยที่ 1 (เทียบพัฒนาการ)', shortLabel: 'หน่วย 1' }
  };

  // ดึงคะแนนของรอบคู่เทียบ (ของผู้ประเมินคนเดียวกัน) มาแสดงทั้งยอดรวมและรายข้อ ให้เทียบพัฒนาการได้ตอนกำลังให้คะแนน
  async function fetchComparisonPhaseScores(studentId, testPhase) {
    const cfg = comparisonPhaseMap[testPhase];
    const box = document.getElementById('comparisonScoreBox');
    if (!cfg || !studentId) return;

    try {
      const response = await fetch('api.php?action=get_single_evaluation', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          studentId: studentId,
          evaluatorType: currentMode,
          evaluatorName: currentUser.name,
          testPhase: cfg.phase
        })
      });
      const res = await response.json();
      if (!res.success || !res.found) return; // ยังไม่เคยประเมินรอบคู่เทียบ — ไม่ต้องแสดงอะไร

      // ป้ายรายข้อ: คะแนนของรอบคู่เทียบ วางคู่กับป้าย "ครั้งก่อน" เพื่อดูพัฒนาการทีละข้อ
      for (const [itemId, rawValue] of Object.entries(res.scores)) {
        const badge = document.getElementById(`compareScore_${itemId}`);
        if (!badge) continue;
        const item = findRubricItemById(itemId);
        const pointsRaw = parseFloat(rawValue);
        const level = item ? Math.round(pointsRaw / item.multiplier) : null;
        const levelInfo = item && level !== null ? item.levels.find(l => l.score === level) : null;
        const pointsText = Math.round(pointsRaw * 100) / 100;
        const textEl = badge.querySelector('.compare-score-text');
        if (textEl) {
          textEl.textContent = `${cfg.shortLabel}: ${levelInfo ? levelInfo.label + ' · ' : ''}${pointsText} คะแนน`;
        }
        badge.classList.remove('d-none');
      }

      // กล่องสรุปคะแนนรวมของรอบคู่เทียบ
      let cmpTotal = (res.totalScore !== undefined && res.totalScore !== null)
        ? parseFloat(res.totalScore)
        : Object.values(res.scores).reduce((sum, v) => sum + parseFloat(v), 0);
      const cmpLevel = (res.qualityLevel && String(res.qualityLevel).trim() !== '')
        ? res.qualityLevel
        : scoreLevelText(cmpTotal);
      const labelEl = document.getElementById('comparisonScoreLabel');
      const valEl = document.getElementById('comparisonScoreValue');
      const lvlEl = document.getElementById('comparisonScoreLevel');
      if (labelEl) labelEl.textContent = cfg.label;
      if (valEl) valEl.textContent = Math.round(cmpTotal * 100) / 100;
      if (lvlEl) lvlEl.textContent = cmpLevel;
      if (box) box.classList.remove('d-none');
    } catch (err) {
      console.error('Error fetching comparison phase evaluation:', err);
    }
  }

  /* ============================================================
     หมายเหตุจากระบบตรวจรายข้อ (ครู/ผู้เชี่ยวชาญเท่านั้น)
     ดึงผลตรวจของระบบตรวจอัตโนมัติในรอบเดียวกันมาแปะไว้ใต้ข้อย่อยแต่ละข้อ ให้เห็นว่า
     ระบบให้ข้อนี้ไว้กี่คะแนน เพราะอะไร พบข้อบกพร่องอะไร และเสนอให้แก้อย่างไร
     เป็นเพียงข้อมูลประกอบ ไม่ถูกกรอกลงฟอร์มและไม่นับเป็นคะแนนจริงของผู้ประเมิน
     ============================================================ */

  // ล้างป้ายและกล่องหมายเหตุจากระบบตรวจของทุกข้อ (รวมทั้งกล่องคะแนนรวมอัตโนมัติด้านบน)
  function clearAiNotes() {
    const aiBox = document.getElementById('aiScoreBox');
    if (aiBox) {
      aiBox.classList.add('d-none');
      aiBox.removeAttribute('title');
    }
    document.querySelectorAll('[id^="aiScore_"]').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('[id^="aiNote_"]').forEach(el => {
      el.classList.add('d-none');
      el.innerHTML = '';
    });
  }

  async function fetchAiNotesForEvaluation(studentId, testPhase) {
    clearAiNotes();
    // นักเรียน (โหมดตนเอง/เพื่อน) ไม่เห็นหมายเหตุนี้ — API จะบังคับเป็นผลของตัวนักเรียนเองซึ่งไม่ตรงกับผู้ถูกประเมิน
    if (!studentId || (modeParam !== 'teacher' && modeParam !== 'expert')) return;

    const phase = gradingEssayPhase[testPhase] || testPhase;
    try {
      const res = await fetch('api.php?action=get_ai_feedback'
        + '&student_id=' + encodeURIComponent(studentId)
        + '&essay_phase=' + encodeURIComponent(phase));
      const data = await res.json();
      if (!data.success || !data.feedback) return;   // ยังไม่เคยให้ระบบตรวจรอบนี้ — ไม่ต้องแสดงอะไร
      renderAiNotes(data.feedback);
    } catch (err) {
      console.error('Error fetching auto-check notes:', err);
    }
  }

  /* กล่อง "คะแนนรวมอัตโนมัติ" ด้านบน — วางคู่กับคะแนนที่คำนวณอัตโนมัติ/คะแนนเดิม/รอบคู่เทียบ
     ให้ผู้ประเมินเห็นยอดรวมที่ระบบให้ไว้ทั้งฉบับ ไม่ต้องไล่อ่านทีละข้อหรือเปิดหน้าระบบตรวจอัตโนมัติ
     - ครูให้คะแนนข้อที่ระบบตรวจแทนไม่ได้ครบแล้ว → แสดงคะแนนรวมเต็ม 60 พร้อมระดับคุณภาพจริง
     - ยังไม่ครบ → แสดงเฉพาะส่วนที่ระบบตรวจ (เต็มตาม max_score) และกำกับว่าเป็นระดับโดยประมาณ
     เป็นข้อมูลประกอบเท่านั้น ไม่ถูกนำไปบันทึกเป็นคะแนนของผู้ประเมิน */
  function renderAiTotalScore(fb) {
    const box = document.getElementById('aiScoreBox');
    if (!box || !fb || fb.incomplete) return;   // ยังไม่มีผลตรวจที่มีคะแนน — ไม่ต้องแสดงกล่องนี้

    const aiTotal    = Number(fb.total_score) || 0;
    const aiMax      = Number(fb.max_score) || 0;
    const fullMax    = Number(fb.full_max || fb.max_score || 60);
    const manualMax  = Number(fb.manual_max || (fullMax - aiMax)) || 0;
    const manualDone = !!fb.manual_done;
    const combined   = Number(fb.combined_total != null ? fb.combined_total : aiTotal) || 0;

    const shownValue = manualDone ? combined : aiTotal;
    const shownMax   = manualDone ? fullMax : aiMax;
    const level      = (manualDone && fb.full_quality_level) ? fb.full_quality_level : (fb.quality_level || '—');

    const labelEl = document.getElementById('aiScoreLabel');
    const valEl   = document.getElementById('aiScoreValue');
    const maxEl   = document.getElementById('aiScoreMax');
    const lvlEl   = document.getElementById('aiScoreLevel');
    if (labelEl) labelEl.textContent = manualDone ? 'คะแนนรวมอัตโนมัติ' : 'คะแนนอัตโนมัติ (เฉพาะข้อที่ระบบตรวจ)';
    if (valEl)   valEl.textContent = Math.round(shownValue * 100) / 100;
    if (maxEl)   maxEl.textContent = Math.round(shownMax * 100) / 100;
    if (lvlEl)   lvlEl.textContent = manualDone ? level : (level !== '—' ? level + ' (โดยประมาณ)' : '—');

    // รายละเอียดการรวมคะแนน (ชี้ค้างไว้เพื่อดู) — ระบบตรวจได้กี่คะแนน ครูให้ข้อที่ระบบตรวจแทนไม่ได้กี่คะแนน
    const tips = [`ระบบ ${Math.round(aiTotal * 100) / 100} / ${Math.round(aiMax * 100) / 100} คะแนน`];
    if (manualMax > 0) {
      tips.push(`ข้อที่ระบบตรวจแทนไม่ได้ (คุณครูให้) ${manualDone ? Math.round(Number(fb.teacher_total || 0) * 100) / 100 : 'ยังไม่ครบ'} / ${Math.round(manualMax * 100) / 100} คะแนน`);
    }
    if (fb.needs_recheck) tips.push('ต้นฉบับถูกแก้หลังระบบตรวจ — รอตรวจใหม่');
    tips.push('เป็นข้อมูลประกอบการพิจารณาเท่านั้น ไม่ใช่คะแนนที่บันทึก');
    box.setAttribute('title', tips.join(' · '));

    box.classList.remove('d-none');
  }

  function renderAiNotes(fb) {
    const scores = fb.scores || {};
    renderAiTotalScore(fb);

    // รวมข้อเสนอแนะของระบบตามรหัสเกณฑ์ เพื่อแปะไว้ใต้ข้อนั้น ๆ
    const fixesByItem = {};
    (fb.improvements || []).forEach(it => {
      const key = (it && it.criterion) ? String(it.criterion).trim() : '';
      if (!key) return;
      if (!fixesByItem[key]) fixesByItem[key] = [];
      fixesByItem[key].push(it);
    });

    // คะแนนที่คุณครูกรอกเองในหน้าระบบตรวจอัตโนมัติ (ข้อที่ระบบตรวจแทนไม่ได้ เช่น 4.3 ความเรียบร้อย)
    const teacherScores = fb.teacher_scores || {};
    const manualIds = (fb.manual_items || []).map(m => m.id);

    rubricData.forEach(section => {
      section.items.forEach(item => {
        const badge  = document.getElementById(`aiScore_${item.id}`);
        const noteEl = document.getElementById(`aiNote_${item.id}`);
        if (!noteEl) return;

        const sc     = scores[item.id];
        const manual = teacherScores[item.id];
        const isManualItem = manualIds.indexOf(item.id) >= 0;
        const rows = [];
        let headText = '';
        let badgeText = '';

        if (sc) {
          const levelInfo = item.levels.find(l => Number(l.score) === Number(sc.raw));
          const weighted  = Math.round(parseFloat(sc.weighted) * 100) / 100;
          badgeText = `ระบบให้: ${levelInfo ? levelInfo.label + ' · ' : ''}${weighted} คะแนน`;
          headText  = `หมายเหตุจากระบบตรวจ · ข้อ ${item.id} — ระบบให้ ${weighted} / ${sc.max} คะแนน`
                    + (levelInfo ? ` (ระดับ ${levelInfo.label})` : '');
          if (sc.reason) {
            rows.push(`<div class="ai-eval-note-row"><span class="ai-eval-note-label text-primary-emphasis">เหตุผลประกอบ:</span> ${escapeHTML(sc.reason)}</div>`);
          }
        } else if (isManualItem) {
          // ข้อที่ระบบตรวจแทนไม่ได้ — แสดงคะแนนที่คุณครูกรอกไว้ในหน้าระบบตรวจอัตโนมัติ (ถ้ามี)
          if (manual) {
            const levelInfo = item.levels.find(l => Number(l.score) === Number(manual.raw));
            const weighted  = Math.round(parseFloat(manual.weighted) * 100) / 100;
            // คะแนนข้อนี้อาจมาจากแบบประเมินหน้านี้เอง (ดึงไปแสดงในหน้าตรวจอัตโนมัติ) หรือครูกรอกไว้ในหน้าระบบตรวจอัตโนมัติ
            const fromEval  = (fb.teacher_source === 'evaluation');
            badgeText = `${fromEval ? 'คะแนนที่บันทึกไว้' : 'คุณครูให้ไว้ในหน้าตรวจอัตโนมัติ'}: ${levelInfo ? levelInfo.label + ' · ' : ''}${weighted} คะแนน`;
            headText  = `หมายเหตุ · ข้อ ${item.id}`;
            rows.push(`<div class="ai-eval-note-row">ข้อนี้ระบบประเมินจากไฟล์ที่พิมพ์ไม่ได้ — ${fromEval
              ? 'คะแนนข้างต้นคือคะแนนที่บันทึกไว้ในแบบประเมินนี้ และถูกนำไปรวมเป็นคะแนนเต็ม 60 ในหน้าระบบตรวจอัตโนมัติให้แล้ว'
              : 'คะแนนข้างต้นเป็นคะแนนที่คุณครูกรอกไว้เองในหน้าระบบตรวจอัตโนมัติ'}</div>`);
          } else {
            badgeText = 'ระบบตรวจข้อนี้แทนไม่ได้';
            headText  = `หมายเหตุจากระบบตรวจ · ข้อ ${item.id}`;
            rows.push(`<div class="ai-eval-note-row">ข้อนี้ต้องดูจากต้นฉบับลายมือ ระบบจึงไม่ได้ให้คะแนนไว้</div>`);
          }
        }

        (fixesByItem[item.id] || []).forEach(fix => {
          if (fix.issue) {
            rows.push(`<div class="ai-eval-note-row"><span class="ai-eval-note-label text-danger-emphasis">บกพร่องอะไร:</span> ${escapeHTML(fix.issue)}</div>`);
          }
          if (fix.suggestion) {
            rows.push(`<div class="ai-eval-note-row"><span class="ai-eval-note-label text-success-emphasis">แก้อย่างไร:</span> ${escapeHTML(fix.suggestion)}</div>`);
          }
          if (fix.example) {
            rows.push(`<div class="ai-eval-note-row fst-italic text-muted"><i class="bi bi-quote me-1"></i>ตัวอย่างหลังแก้: ${escapeHTML(fix.example)}</div>`);
          }
        });

        if (!headText && !rows.length) return;   // ข้อนี้ระบบไม่ได้พูดถึงเลย

        if (badge && badgeText) {
          const textEl = badge.querySelector('.ai-score-text');
          if (textEl) textEl.textContent = badgeText;
          badge.classList.remove('d-none');
        }
        noteEl.innerHTML =
          `<div class="ai-eval-note-head"><i class="bi bi-file-earmark-check me-1"></i>${escapeHTML(headText || ('หมายเหตุจากระบบตรวจ · ข้อ ' + item.id))}</div>`
          + rows.join('')
          + `<div class="text-muted mt-2" style="font-size:0.75rem;">
               <i class="bi bi-info-circle me-1"></i>เป็นข้อมูลจากระบบตรวจอัตโนมัติเพื่อประกอบการพิจารณาเท่านั้น คะแนนที่บันทึกจริงคือคะแนนที่ท่านเลือกด้านบน
             </div>`;
        noteEl.classList.remove('d-none');
      });
    });
  }

  async function fetchStudentEssayForEvaluation(studentId, testPhase) {
    const formTitleEl = document.getElementById('essayPanelFormTitle');
    const titleEl = document.getElementById('essayPanelTitle');
    const authorEl = document.getElementById('essayPanelAuthor');
    const contentEl = document.getElementById('essayPanelContent');
    const countEl = document.getElementById('essayPanelWordCount');

    // ไม่มีรหัสนักเรียน หรือเด็กไม่ได้บันทึกเรียงความ → ซ่อนคอลัมน์เรียงความ คงเหลือเฉพาะแบบประเมินเต็มความกว้าง
    if (!studentId) {
      toggleEssayColumn(false);
      return;
    }

    try {
      const fetchPhase = gradingEssayPhase[testPhase] || testPhase;
      const response = await fetch(`api.php?action=get_essay&studentId=${studentId}&essay_phase=${fetchPhase}`);
      const data = await response.json();

      if (data.success && data.found) {
        // หัวกระดาษ: ชื่อแบบวัดตามรอบ
        if (formTitleEl) formTitleEl.textContent = essayFormTitleByPhase[testPhase] || 'แบบวัดความสามารถ';
        // ชื่อเรื่อง
        titleEl.textContent = data.data.essay_title || 'ไม่มีชื่อเรื่อง';
        // ชื่อ / ชั้น / รหัสประจำตัวนักเรียน ของเจ้าของผลงาน
        const ownerName = studentDB[studentId] || data.data.student_name || '';
        const ownerRoom = data.data.classroom || '';
        if (authorEl) {
          authorEl.textContent =
            `ชื่อ ${ownerName || '—'}` +
            `   ชั้น ${ownerRoom || '—'}` +
            `   รหัสประจำตัวนักเรียน ${studentId}`;
        }
        contentEl.innerHTML = formatEssayHTML(data.data.essay_content);
        countEl.textContent = `${data.data.word_count || 0} คำ`;
        toggleEssayColumn(true);
        // รอให้จัดหน้าเสร็จก่อนคำนวณ/วางเลขบรรทัดและป้ายบอกส่วน (กล่องเพิ่งแสดง)
        requestAnimationFrame(() => requestAnimationFrame(() => { addEssayLineNumbers(); addEssaySectionTags(); }));
      } else {
        toggleEssayColumn(false);
      }
    } catch (err) {
      console.error("Error fetching student essay for evaluation:", err);
      toggleEssayColumn(false);
    }
  }

  // คำนวณหาผลรวมของคะแนนตามเกณฑ์สูตรคำนวณ
  function calculateHiddenScore() {
    const radios = document.querySelectorAll('input[type="radio"].score-radio:checked');
    let total = 0;
    radios.forEach(radio => {
      total += (parseFloat(radio.value) * parseFloat(radio.dataset.multiplier));
    });

    const levelText = scoreLevelText(total);

    return { total, levelText };
  }

  // การระบุรหัสผู้ถูกประเมิน → กดปุ่ม "แสดงข้อมูล" หรือกด Enter เพื่อโหลดข้อมูล
  (function bindTargetInput() {
    const btn = document.getElementById('loadStudentBtn');
    const input = document.getElementById('targetStudentInput');
    if (btn) btn.addEventListener('click', resolveTargetStudent);
    if (input) {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); resolveTargetStudent(); }
      });
      // เลือกจากรายการ (datalist) ให้โหลดข้อมูลทันที
      input.addEventListener('change', () => {
        if (getTargetId()) resolveTargetStudent();
      });
    }
  })();

  // ปุ่มเลือกกลุ่มบน navbar เปลี่ยน (เฉพาะโหมดครู) → โหลดรายชื่อกลุ่มใหม่แล้วรีเซ็ตเป้าหมาย
  window.onTEGChange = async function() {
    if (modeParam !== 'teacher' || !window.TEG) return; // โหมดอื่นไม่ยุ่งกับกลุ่ม
    await loadStudents();
    populateStudentDatalist();
    // รีเซ็ตการระบุเป้าหมายเพราะรายชื่ออาจเปลี่ยนกลุ่ม
    const tInput = document.getElementById('targetStudentInput');
    if (tInput) tInput.value = '';
    const resolvedEl = document.getElementById('targetStudentResolved');
    const errEl = document.getElementById('targetStudentError');
    if (resolvedEl) resolvedEl.classList.add('d-none');
    if (errEl) errEl.classList.add('d-none');
    dimRubric();
  };

  // เลือกกลุ่มด้วยปุ่ม → โหลดรายชื่อใหม่แล้วรีเซ็ตการระบุรหัสเป้าหมาย
  (function bindGroupFilter() {
    const btns = document.querySelectorAll('#groupFilterButtons .group-btn');
    if (!btns.length) return;
    btns.forEach(btn => {
      btn.addEventListener('click', async () => {
        btns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const hidden = document.getElementById('groupFilterValue');
        if (hidden) hidden.value = btn.dataset.group || '';
        // โหมดครู: จำค่ากลุ่มไว้ให้ทุกหน้าครูใช้ร่วมกัน
        if (modeParam === 'teacher' && window.TEG) TEG.set((btn.dataset.group || '') === '' ? 'all' : btn.dataset.group);

        await loadStudents();
        populateStudentDatalist();

        const phase = document.getElementById('selectedTestPhase') ? document.getElementById('selectedTestPhase').value : '';
        // โหมดเพื่อน: ถ้าเลือกรอบแล้ว ให้ดึงคู่ที่ล็อกไว้ใหม่ตามรายชื่อที่กรอง
        if (modeParam === 'peer' && phase) {
          applyPeerPairing(phase);
          return;
        }
        // โหมดอื่น: รีเซ็ตการระบุเป้าหมาย
        const tInput = document.getElementById('targetStudentInput');
        if (tInput) { tInput.value = ''; tInput.disabled = false; }
        const resolvedEl = document.getElementById('targetStudentResolved');
        const errEl = document.getElementById('targetStudentError');
        if (resolvedEl) resolvedEl.classList.add('d-none');
        if (errEl) errEl.classList.add('d-none');
        dimRubric();
      });
    });
  })();

  // phase picker functions
  const phaseLabels = {
    pretest:  'ก่อนเรียน (Pretest - T1)',
    task1:    'ภาระงาน หน่วยที่ 1 (Task 1)',
    task2:    'ภาระงาน หน่วยที่ 2 (Task 2)',
    posttest: 'หลังเรียน (Posttest - T2)'
  };
  function selectPhase(phase) {
    document.getElementById('selectedTestPhase').value = phase;
    const picker = document.getElementById('phasePicker');
    const section = document.getElementById('evalSection');
    if (picker && !picker.classList.contains('d-none')) {
      picker.classList.add('d-none');
      section.classList.remove('d-none');
    }
    const badge = document.getElementById('selectedPhaseBadge');
    if (badge) badge.textContent = phaseLabels[phase] || phase;

    // update expert unit toggle button (เหลือเฉพาะหน่วยที่ 1)
    const btn1 = document.getElementById('btnTask1');
    if (btn1 && phase === 'task1') {
      btn1.className = 'btn btn-success btn-sm fw-bold flex-fill rounded-3 py-2';
    }

    // โหมดเพื่อนประเมิน: ดึงคู่ที่ครูจับไว้มาล็อกให้อัตโนมัติเมื่อเลือกรอบแล้ว
    if (modeParam === 'peer') {
      applyPeerPairing(phase);
      return;
    }

    // reload existing if student already specified
    const id = getTargetId();
    if (id && studentDB[id]) checkExistingEvaluation(id);
  }

  // ดึงคู่ประเมินเพื่อนที่ครูจับไว้ตามรอบ แล้วตั้งเป็นค่า default ที่ล็อกไว้
  // ถ้าไม่มีคู่สำหรับรอบนั้น → fallback กลับไปใช้ dropdown เดิมพร้อมข้อความเตือน
  async function applyPeerPairing(phase) {
    const tInput = document.getElementById('targetStudentInput');
    const loadBtn = document.getElementById('loadStudentBtn');
    const resolvedEl = document.getElementById('targetStudentResolved');
    const lockNotice = document.getElementById('peerLockNotice');
    const fallbackNotice = document.getElementById('peerFallbackNotice');
    if (lockNotice) lockNotice.classList.add('d-none');
    if (fallbackNotice) fallbackNotice.classList.add('d-none');

    try {
      const res = await (await fetch(`api.php?action=get_my_peer_partner&round=${phase}&_t=${Date.now()}`)).json();
      if (res.success && res.partner && studentDB[res.partner]) {
        // มีคู่ → ตั้งค่าและล็อกช่องระบุเป้าหมาย
        syncTargetSelection(res.partner);
        if (tInput) tInput.disabled = true;
        if (loadBtn) loadBtn.disabled = true;
        if (resolvedEl) { resolvedEl.textContent = `✓ ผู้ถูกประเมิน: ${res.partner} - ${studentDB[res.partner]}`; resolvedEl.classList.remove('d-none'); }
        if (lockNotice) lockNotice.classList.remove('d-none');
        checkExistingEvaluation(res.partner);
      } else {
        // ไม่มีคู่ → เปิดช่องให้ระบุเองพร้อมข้อความเตือน (fallback)
        if (tInput) { tInput.disabled = false; tInput.value = ''; }
        if (loadBtn) loadBtn.disabled = false;
        if (resolvedEl) resolvedEl.classList.add('d-none');
        if (fallbackNotice) fallbackNotice.classList.remove('d-none');
        dimRubric();
      }
    } catch (err) {
      console.error('ไม่สามารถโหลดคู่ประเมินเพื่อนได้:', err);
      // เผื่อ error ให้ fallback เป็นช่องระบุรหัสเดิม
      if (tInput) tInput.disabled = false;
      if (loadBtn) loadBtn.disabled = false;
      if (fallbackNotice) fallbackNotice.classList.remove('d-none');
    }
  }
  function resetPhase() {
    document.getElementById('evalSection').classList.add('d-none');
    document.getElementById('phasePicker').classList.remove('d-none');
    // clear active state
    document.querySelectorAll('.phase-btn').forEach(b => b.classList.remove('active', 'btn-primary','btn-success','btn-warning','btn-danger'));
  }

  // บันทึกฟอร์ม
  document.getElementById('evalForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const studentId = getTargetId();
    if(!studentId || !studentDB[studentId]) {
      showToast("กรุณาระบุรหัสนักเรียนที่ถูกต้องก่อนส่งผลคะแนน", "error");
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
    const testPhase = document.getElementById('selectedTestPhase') ? document.getElementById('selectedTestPhase').value : 'task1';
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
        // บันทึกขึ้นเซิร์ฟเวอร์สำเร็จแล้ว ไม่ต้องเก็บร่างสำรองในเครื่องอีกต่อไป
        clearEvalDraftFromLocalStorage(studentId, testPhase);
        // อยู่ที่หน้าประเมินเดิม (ไม่ redirect ไปหน้าอื่น) แล้วโหลดข้อมูลที่บันทึกกลับมาให้เป็นโหมดแก้ไข
        showToast(`บันทึกผลการประเมิน ${phaseLabels[testPhase] || testPhase} เรียบร้อยแล้ว ✓ (คุณยังอยู่ที่หน้าประเมินเดิม)`, "success");
        btn.innerHTML = originalText;
        btn.disabled = false; // เปิดปุ่มคืน เผื่อผู้ประเมินต้องการแก้คะแนนแล้วบันทึกซ้ำได้ทันที
        // อ่านกลับจากฐานข้อมูลเพื่อ "พิสูจน์" ว่าคะแนนของรอบนี้ถูกบันทึกจริง
        // ถ้าอ่านกลับไม่เจอ ต้องเตือนทันที ห้ามปล่อยให้ฟอร์มว่างเปล่าโดยไม่บอกอะไรเลย
        const reloaded = await checkExistingEvaluation(studentId);
        if (!reloaded) {
          showToast("⚠️ บันทึกแล้วแต่อ่านคะแนนของรอบนี้กลับมาไม่ได้ กรุณารีเฟรชหน้าเว็บแล้วตรวจสอบอีกครั้ง", "error");
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        showToast("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " + res.error, "error");
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    } catch (err) {
      showToast("ไม่สามารถเชื่อมต่อฐานข้อมูลปลายทางได้", "error");
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  // ทำการทำงานเริ่มต้นแบบเงียบ
  (async function init() {
    // โหมดครูใช้กลุ่มจากปุ่มบน navbar อัตโนมัติ (ผ่าน getGroupValue → TEG) จึงไม่ต้องตั้งค่าปุ่มในฟอร์ม

    await loadStudents();
    populateStudentDatalist();
    buildRubric();

    const tInput = document.getElementById('targetStudentInput');
    const loadBtn = document.getElementById('loadStudentBtn');
    const resolvedEl = document.getElementById('targetStudentResolved');

    if (modeParam === 'self') {
      // โหมดประเมินตนเอง → ล็อกเป้าหมายเป็นของตนเองและโหลดข้อมูลทันที
      if (tInput) { tInput.value = `${currentUser.id} - ${studentDB[currentUser.id] || ''}`; tInput.disabled = true; }
      if (loadBtn) loadBtn.disabled = true;
      if (resolvedEl && studentDB[currentUser.id]) {
        resolvedEl.textContent = `✓ ผู้ถูกประเมิน: ${currentUser.id} - ${studentDB[currentUser.id]}`;
        resolvedEl.classList.remove('d-none');
      }
      // ยังไม่โหลดคะแนนเดิมตรงนี้ — ต้องรู้ "รอบ" ก่อน (selectPhase จะสั่งโหลดให้เอง)
      // มิฉะนั้นจะยิงคำขอด้วยรอบว่าง ซึ่งไม่มีวันพบข้อมูล และคำตอบที่กลับมาช้าจะไปทับสถานะที่ถูกต้อง
    } else {
      if (tInput) { tInput.value = ''; tInput.disabled = false; }
      dimRubric();
    }

    // มีนักเรียนระบุมาจาก URL (ลิงก์ "ให้คะแนน" จากตารางคะแนนครูประเมิน) → เติมช่องเป้าหมายไว้ก่อนเลือกรอบ
    // ต้องเติมก่อน selectPhase เพราะ selectPhase จะสั่งโหลดคะแนนเดิมของคนนั้นในรอบนั้นให้ทันที
    const canPickTarget = (modeParam === 'teacher' || modeParam === 'expert');
    if (initialStudentParam && canPickTarget && tInput) {
      if (studentDB[initialStudentParam] !== undefined) {
        tInput.value = `${initialStudentParam} - ${studentDB[initialStudentParam]}`;
      } else {
        tInput.value = initialStudentParam;  // ไม่อยู่ในรายชื่อกลุ่มที่เลือกอยู่ — ให้ resolveTargetStudent แจ้งเตือนเอง
      }
    }

    // มีรอบระบุมาจาก URL (เช่นลิงก์จากรายการ "สิ่งที่ยังไม่ได้ทำ") → ข้ามหน้าเลือกรอบไปเริ่มประเมินรอบนั้นทันที
    if (initialPhaseParam) selectPhase(initialPhaseParam);

    // ระบุนักเรียนมาด้วย → แสดงชื่อผู้ถูกประเมินให้ชัด
    // ถ้ามีรอบมาด้วย selectPhase โหลดคะแนนเดิมของรอบนั้นไปแล้ว จึงไม่ยิงคำขอซ้ำให้ชนกันเอง
    if (initialStudentParam && canPickTarget) {
      if (initialPhaseParam && studentDB[initialStudentParam] !== undefined) {
        if (resolvedEl) {
          resolvedEl.textContent = `✓ ผู้ถูกประเมิน: ${initialStudentParam} - ${studentDB[initialStudentParam]}`;
          resolvedEl.classList.remove('d-none');
        }
      } else {
        resolveTargetStudent();   // ยังไม่รู้รอบ หรือหาชื่อในรายชื่อกลุ่มที่เลือกอยู่ไม่เจอ
      }
    }
  })();
</script>

</div><!-- /.eval-fullwidth -->

<?php
require_once 'footer.php';
?>
