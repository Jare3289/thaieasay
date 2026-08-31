<?php
/**
 * ai_student_summary.php — หน้า "สรุปภาพรวมผลงานเขียนรายบุคคล" (แยกออกมาเป็นหน้าของตัวเอง)
 * ---------------------------------------------------------------------------
 * เปิดจากหน้า "ผู้ช่วย AI ตรวจเรียงความ" (ai_feedback.php) โดยคลิกที่นักเรียนรายคน
 * แยกออกมาเพื่อให้หน้าตรวจเหลือแต่ของที่ใช้บ่อย และหน้าสรุปนี้อ่านได้เต็มจอโดยไม่ต้องเลื่อนผ่านอย่างอื่น
 *
 * เนื้อหาทั้งหมดคิดจากผลตรวจที่บันทึกไว้แล้วทุกรอบ ไม่ได้เรียก AI ใหม่ จึงไม่เปลืองโควตา
 *   - ครู/ผู้เชี่ยวชาญ : เลือกดูนักเรียนคนใดก็ได้ผ่าน ?student_id=
 *   - นักเรียน        : เห็นของตนเองเท่านั้น
 */
$page_title = 'สรุปภาพรวมผลงานเขียน - ผู้ช่วย AI ตรวจเรียงความ';
require_once 'auth_helper.php';
require_login();
require_once 'ai_config.php';
require_once 'header.php';

$aiRole      = $sessionUser['role'];
$aiIsStudent = ($aiRole === 'student');
$aiPhases    = ai_all_phases();
$aiViewId    = $aiIsStudent
    ? (string)$sessionUser['id']
    : (isset($_GET['student_id']) ? trim((string)$_GET['student_id']) : '');
?>

<div id="view-ai-summary" class="text-start">
  <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <a href="ai_feedback.php<?php echo $aiViewId !== '' ? '?student_id=' . urlencode($aiViewId) : ''; ?>"
       class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าผู้ช่วย AI ตรวจเรียงความ
    </a>
    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-print-none" onclick="window.print()">
      <i class="bi bi-printer me-1"></i>พิมพ์หน้านี้
    </button>
  </div>

  <!-- หัวเรื่อง -->
  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-4">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 50%, #0d7377 100%);">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-clipboard2-data me-2"></i>สรุปภาพรวมผลงานเขียน</h4>
          <p class="text-white-50 mb-0 small">
            รวมผลตรวจของ AI ทุกรอบงานไว้ในหน้าเดียว — ทำอะไรได้ดีแล้ว ต้องแก้อะไรก่อนเขียนครั้งถัดไป
            และร่างหลังดีขึ้นกว่าร่างก่อนจริงหรือไม่
          </p>
        </div>
        <span id="aiSumWho" class="badge bg-white text-dark px-3 py-2 fs-7 fw-bold">
          <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($sessionUser['name']); ?>
        </span>
      </div>
    </div>
<?php if (!$aiIsStudent): ?>
    <div class="bg-light border-top px-4 py-3 d-print-none">
      <label class="form-label fw-bold small mb-1">นักเรียนที่กำลังดู <span class="text-muted fw-normal">(เฉพาะกลุ่มตัวอย่าง · พิมพ์ค้นหาด้วยรหัส/ชื่อได้)</span></label>
      <div class="row g-2">
        <div class="col-md-8">
          <select id="aiSumStudent" class="form-select border-2 rounded-3" onchange="onSummaryStudentChange()"
                  data-search-select data-search-placeholder="พิมพ์ค้นหาด้วยรหัส หรือ ชื่อนักเรียน...">
            <option value="">— กำลังโหลดรายชื่อ —</option>
          </select>
        </div>
        <div class="col-md-4">
          <a id="aiSumBackLink" href="ai_feedback.php" class="btn btn-outline-primary rounded-pill w-100">
            <i class="bi bi-card-list me-1"></i>ดูผลตรวจรายฉบับของคนนี้
          </a>
        </div>
      </div>
    </div>
<?php endif; ?>
  </div>

  <div id="aiSumBody">
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body">
      <div class="text-center py-5">
        <div class="spinner-border text-primary mb-3"></div>
        <div class="fw-bold text-dark">กำลังโหลดผลตรวจทุกรอบงาน...</div>
      </div>
    </div></div>
  </div>
</div>

<script src="ai_review.js"></script>
<script src="ai_summary.js"></script>
<script>
const AI_IS_STUDENT = <?php echo $aiIsStudent ? 'true' : 'false'; ?>;
const AI_MY_ID      = <?php echo json_encode($sessionUser['id']); ?>;
const AI_PHASES     = <?php echo json_encode($aiPhases); ?>;
let   aiSumStudentId = <?php echo json_encode($aiViewId); ?>;

// โหลดผลตรวจทุกรอบของนักเรียนคนที่เลือก แล้ววาดหน้าสรุปทั้งหน้า
async function loadSummary() {
  const box = document.getElementById('aiSumBody');
  const sid = AI_IS_STUDENT ? AI_MY_ID : aiSumStudentId;

  if (!sid) {
    box.innerHTML = `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">`
      + aiEmptyHTML('เลือกนักเรียนด้านบนเพื่อดูสรุปภาพรวมผลงานเขียน') + `</div></div>`;
    return;
  }

  box.innerHTML = `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">`
    + aiLoadingHTML('กำลังโหลดผลตรวจทุกรอบงาน...') + `</div></div>`;

  let all = {};
  try {
    const params = new URLSearchParams({ action: 'get_ai_feedback' });
    if (!AI_IS_STUDENT) params.set('student_id', sid);
    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    if (!data.success) {
      box.innerHTML = aiErrorHTML(data.error || 'โหลดผลตรวจไม่สำเร็จ');
      return;
    }
    (data.list || []).forEach(fb => { all[fb.essay_phase] = fb; });
  } catch (err) {
    box.innerHTML = aiErrorHTML('โหลดผลตรวจไม่สำเร็จ กรุณาลองใหม่อีกครั้ง');
    return;
  }

  // ผลตรวจเก่าที่บันทึกไว้ก่อนมีระบบเทียบร่าง — คำนวณผลเทียบจากคะแนนที่มีอยู่ให้เลย
  aiAttachDraftCompare(all);

  const phases = AI_PHASES.filter(ph => all[ph]);
  const who    = phases.length ? (all[phases[0]].student_name || '') : '';
  if (who) {
    document.getElementById('aiSumWho').innerHTML = '<i class="bi bi-person-fill me-1"></i>' + aiEsc(who);
  }
  const back = document.getElementById('aiSumBackLink');
  if (back) back.href = 'ai_feedback.php?student_id=' + encodeURIComponent(sid);

  box.innerHTML = aiStudentSummaryHTML(all, AI_PHASES);
}

<?php if (!$aiIsStudent): ?>
async function loadSummaryStudents() {
  const sel = document.getElementById('aiSumStudent');
  if (!sel) return;
  try {
    // รายชื่อในช่องนี้ใช้เฉพาะ "กลุ่มตัวอย่าง" (ผู้เชี่ยวชาญถูกบังคับกลุ่มทดลองที่ฝั่งเซิร์ฟเวอร์อยู่แล้ว)
    const res  = await fetch('api.php?action=get_students_list' + (window.TEG ? TEG.sampleParam() : ''));
    const data = await res.json();
    if (!data.success) { sel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>'; return; }
    const opts = Object.keys(data.students).map(id =>
      `<option value="${aiEsc(id)}">${aiEsc(id)} — ${aiEsc(data.students[id])}</option>`).join('');
    sel.innerHTML = '<option value="">— เลือกนักเรียน —</option>' + opts;
    if (aiSumStudentId) sel.value = aiSumStudentId;
    if (window.SearchSelect) SearchSelect.refresh(sel);
  } catch (err) {
    sel.innerHTML = '<option value="">โหลดรายชื่อไม่สำเร็จ</option>';
  }
}

// เปลี่ยนนักเรียน = อัปเดต URL ด้วย เพื่อให้กดรีเฟรช/บุ๊กมาร์กแล้วยังอยู่ที่คนเดิม
function onSummaryStudentChange() {
  aiSumStudentId = document.getElementById('aiSumStudent').value;
  const url = new URL(window.location.href);
  if (aiSumStudentId) url.searchParams.set('student_id', aiSumStudentId);
  else url.searchParams.delete('student_id');
  window.history.replaceState({}, '', url);
  loadSummary();
}
<?php endif; ?>

(async function () {
<?php if (!$aiIsStudent): ?>
  await loadSummaryStudents();
<?php endif; ?>
  await loadSummary();
})();
</script>

<?php require_once 'footer.php'; ?>
