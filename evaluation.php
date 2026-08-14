<?php
$page_title = 'แบบประเมิน - ระบบประเมินเรียงความ';
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
        // นักเรียนประเมินเฉพาะ หน่วยที่ 1 เท่านั้น (ไม่มี Pretest/Posttest)
        // ครูยังคงเห็นรอบ ก่อนเรียน/หน่วยที่ 1/หลังเรียน เพื่อใช้วัดผลก่อน-หลังเรียนในงานวิจัย
        $isStudentEval = ($sessionUser['role'] === 'student');
        $phaseColClass = $isStudentEval ? 'col-12' : 'col-md-4 col-6';
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

  <!-- ส่วนเนื้อหาเรียงความ: แยกออกจาก evalSection เป็น section ลอย (fixed) ฝั่งขวา เต็มครึ่งจอ ติดตามหน้าจอเสมอเวลาเลื่อน -->
  <aside id="essayFloating" class="card border-0 shadow-lg d-none" style="overflow: hidden; border: 1px solid #e7e5e4 !important;">
    <div class="d-flex align-items-center justify-content-between gap-2 px-4 py-3 border-bottom" style="background-color: #fffdf0;">
      <h6 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-text text-primary me-2"></i>เนื้อหาเรียงความที่นักเรียนบันทึกไว้ (Student Essay Content)</h6>
      <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-mono small flex-shrink-0" id="essayPanelWordCount">0 คำ</span>
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
    const tInput = document.getElementById('targetStudentInput');
    if (tInput) tInput.value = studentId;
    if(progressCont) progressCont.classList.remove('d-none');
    calculateRealTimeFormScore(); // รีเซ็ต

    statusBadge.textContent = "กำลังค้นหาคะแนนเดิม...";
    statusBadge.className = "badge bg-info text-white fs-8 px-3 py-2 rounded-pill";
    statusBadge.classList.remove('d-none');

    const testPhase = document.getElementById('selectedTestPhase') ? document.getElementById('selectedTestPhase').value : 'task1';

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
      // โหลดเรียงความของนักเรียนมาแสดงประกอบการให้คะแนนด้วย
      fetchStudentEssayForEvaluation(studentId, testPhase);
    } catch (err) {
      console.error(err);
      statusBadge.textContent = "⚠️ ไม่สามารถตรวจสอบประวัติได้";
      statusBadge.className = "badge bg-danger text-white fs-8 px-3 py-2 rounded-pill";
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
  function formatEssayHTML(contentStr) {
    // เก็บย่อหน้าพร้อม "ส่วน" (intro/body/concl) เพื่อทำป้ายบอกส่วนแบบแอบ ๆ
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
    if (!paras.length) return '<div class="no-content">— ยังไม่มีเนื้อหาเรียงความ —</div>';
    // ทำเครื่องหมาย data-sec เฉพาะย่อหน้า "แรก" ของแต่ละส่วน เพื่อวางป้ายเพียงจุดเดียวต่อส่วน
    let seenBody = false;
    return paras.map(p => {
      let mark = '';
      if (p.sec === 'intro') mark = ' data-sec="intro"';
      else if (p.sec === 'concl') mark = ' data-sec="concl"';
      else if (p.sec === 'body' && !seenBody) { mark = ' data-sec="body"'; seenBody = true; }
      return `<p class="essay-para"${mark}>${nl2brSafe(p.text)}</p>`;
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
    if (!essay || essay.classList.contains('d-none')) return;
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
  }

  // ชื่อหัวกระดาษตามรอบการประเมิน
  const essayFormTitleByPhase = {
    pretest:  'แบบวัดความสามารถก่อนเรียน',
    posttest: 'แบบวัดความสามารถหลังเรียน',
    task1:    'แบบฝึกภาระงาน หน่วยที่ 1'
  };

  // ภาระงานมีร่าง D1/D2 แต่ให้คะแนนเฉพาะร่างที่ 2 (D2) — จึงดึงเรียงความร่าง D2 มาแสดงเวลาประเมินหน่วยภาระงาน
  // (คะแนนยังบันทึกภายใต้รอบ task1 ตามเดิม เพื่อไม่ให้กระทบแดชบอร์ด/การส่งออก)
  const gradingEssayPhase = { pretest: 'pretest', task1: 'task1_d2', posttest: 'posttest' };

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

    let levelText = '';
    if(total >= 49) levelText = 'ดีมาก';
    else if(total >= 37) levelText = 'ดี';
    else if(total >= 25) levelText = 'ปานกลาง';
    else if(total >= 13) levelText = 'พอใช้';
    else levelText = 'ต้องปรับปรุง';

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
        // อยู่ที่หน้าประเมินเดิม (ไม่ redirect ไปหน้าอื่น) แล้วโหลดข้อมูลที่บันทึกกลับมาให้เป็นโหมดแก้ไข
        showToast("บันทึกผลการประเมินเรียบร้อยแล้ว ✓ (คุณยังอยู่ที่หน้าประเมินเดิม)", "success");
        btn.innerHTML = originalText;
        checkExistingEvaluation(studentId);
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
      checkExistingEvaluation(currentUser.id);
    } else {
      if (tInput) { tInput.value = ''; tInput.disabled = false; }
      dimRubric();
    }
  })();
</script>

</div><!-- /.eval-fullwidth -->

<?php
require_once 'footer.php';
?>
