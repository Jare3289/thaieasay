/* ai_review.js — ตัวช่วยฝั่งหน้าเว็บของระบบ "ให้ข้อเสนอแนะเรียงความอัตโนมัติด้วย AI"
   ใช้ร่วมกันระหว่างหน้า ai_feedback.php (หน้าเต็ม) และ essay_writer.php (แผงย่อในหน้าเขียน)
   ทุกฟังก์ชันขึ้นต้นด้วย ai เพื่อไม่ให้ชนกับฟังก์ชันเดิมของแต่ละหน้า */

const AI_PHASE_LABEL_MAP = {
  pretest:  'ก่อนเรียน (Pre-test)',
  task1_d1: 'ภาระงานหน่วยที่ 1 · ร่างที่ 1',
  task1_d2: 'ภาระงานหน่วยที่ 1 · ร่างที่ 2',
  task2_d1: 'ภาระงานหน่วยที่ 2 · ร่างที่ 1',
  task2_d2: 'ภาระงานหน่วยที่ 2 · ร่างที่ 2',
  posttest: 'หลังเรียน (Post-test)'
};

function aiEsc(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function aiPhaseLabel(phase) {
  return AI_PHASE_LABEL_MAP[phase] || phase;
}

/* ---- เรียก API ---- */

// สถานะฟีเจอร์ AI ของผู้ใช้ปัจจุบัน (เปิดใช้ไหม / ตั้งค่าแล้วหรือยัง / โควตาเหลือเท่าไร)
async function aiGetStatus() {
  try {
    const res  = await fetch('api.php?action=get_ai_status');
    const data = await res.json();
    return data.success ? data : null;
  } catch (err) {
    console.error('aiGetStatus', err);
    return null;
  }
}

// ดึงผลตรวจที่บันทึกไว้แล้วของเรียงความ 1 ฉบับ (คืน null ถ้ายังไม่เคยตรวจ)
async function aiGetFeedback(studentId, phase) {
  try {
    const params = new URLSearchParams({ action: 'get_ai_feedback', essay_phase: phase });
    if (studentId) params.set('student_id', studentId);
    const res  = await fetch('api.php?' + params.toString());
    const data = await res.json();
    return (data.success && data.feedback) ? data.feedback : null;
  } catch (err) {
    console.error('aiGetFeedback', err);
    return null;
  }
}

// สั่งให้ AI ตรวจ — คืน {success, feedback, error, quota_left}
async function aiRequestReview(studentId, phase) {
  try {
    const body = { action: 'ai_review_essay', essay_phase: phase };
    if (studentId) body.student_id = studentId;
    const res = await fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return await res.json();
  } catch (err) {
    console.error('aiRequestReview', err);
    return { success: false, error: 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่อีกครั้ง' };
  }
}

/* ---- สร้าง HTML ---- */

function aiEmptyHTML(msg) {
  return `<div class="text-center text-muted py-5">
    <i class="bi bi-robot fs-1 d-block mb-3 opacity-50"></i>${aiEsc(msg)}
  </div>`;
}

function aiLoadingHTML(title, subtitle) {
  return `<div class="text-center py-5">
    <div class="spinner-border text-primary mb-3"></div>
    <div class="fw-bold text-dark">${aiEsc(title)}</div>
    ${subtitle ? `<div class="text-muted small mt-1">${aiEsc(subtitle)}</div>` : ''}
  </div>`;
}

function aiErrorHTML(msg) {
  return `<div class="alert alert-danger border-0 rounded-3 mb-0">
    <i class="bi bi-x-octagon me-2"></i>${aiEsc(msg)}
  </div>`;
}

/**
 * สร้าง HTML ของผลตรวจ AI
 * opts.compact = true : แบบย่อ (ใช้ในหน้าเขียนเรียงความ — ไม่แสดงตารางคะแนนรายข้อ)
 * opts.deleteAction    : โค้ด onclick ของปุ่มลบ (ครูเท่านั้น) เว้นว่างไว้ = ไม่แสดงปุ่ม
 */
function aiFeedbackHTML(fb, opts) {
  opts = opts || {};
  const pct = fb.max_score > 0 ? Math.round((fb.total_score / fb.max_score) * 100) : 0;

  const strengths = (fb.strengths || []).map(s =>
    `<div class="p-3 rounded-3 mb-2 ai-strength-card"><i class="bi bi-check-circle-fill text-success me-2"></i>${aiEsc(s)}</div>`
  ).join('') || '<div class="text-muted small mb-2">— ไม่มีข้อมูล —</div>';

  const improvements = (fb.improvements || []).map((it, i) => {
    const critName = (fb.scores && fb.scores[it.criterion]) ? fb.scores[it.criterion].name : '';
    return `<div class="p-3 rounded-3 mb-2 ai-improve-card">
      <div class="fw-bold text-dark mb-1">
        <span class="badge bg-warning text-dark me-1">${i + 1}</span>
        ${it.criterion ? `ข้อ ${aiEsc(it.criterion)} ${aiEsc(critName)}` : 'จุดที่ควรปรับปรุง'}
      </div>
      ${it.issue ? `<div class="small mb-2"><span class="fw-semibold text-danger-emphasis">สิ่งที่พบ:</span> ${aiEsc(it.issue)}</div>` : ''}
      ${it.suggestion ? `<div class="small mb-2"><span class="fw-semibold text-success-emphasis">ลองแก้แบบนี้:</span> ${aiEsc(it.suggestion)}</div>` : ''}
      ${it.example ? `<div class="small fst-italic text-muted"><i class="bi bi-quote me-1"></i>${aiEsc(it.example)}</div>` : ''}
    </div>`;
  }).join('') || '<div class="text-muted small mb-2">— ไม่มีข้อมูล —</div>';

  const nextSteps = (fb.next_steps || []).map(s =>
    `<div class="p-3 rounded-3 mb-2 ai-next-card"><i class="bi bi-arrow-right-circle-fill text-primary me-2"></i>${aiEsc(s)}</div>`
  ).join('');

  // ตารางคะแนนรายเกณฑ์ (ซ่อนในโหมดย่อ)
  let scoreTable = '';
  if (!opts.compact) {
    const critRows = Object.keys(fb.scores || {}).sort().map(k => {
      const c = fb.scores[k];
      const cpct = c.max > 0 ? Math.round((c.weighted / c.max) * 100) : 0;
      return `<tr class="ai-crit-row">
        <td class="text-nowrap fw-semibold">${aiEsc(k)}</td>
        <td>${aiEsc(c.name || '')}${c.reason ? `<div class="text-muted small mt-1">${aiEsc(c.reason)}</div>` : ''}</td>
        <td class="text-center text-nowrap fw-bold">${c.weighted} <span class="text-muted fw-normal">/ ${c.max}</span></td>
        <td style="min-width:110px;"><div class="ai-score-bar"><span style="width:${cpct}%"></span></div></td>
      </tr>`;
    }).join('');
    scoreTable = `
      <h6 class="fw-bold text-dark mt-4 mb-2"><i class="bi bi-list-ol me-2"></i>คะแนนรายเกณฑ์ (ประมาณการ)</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr><th style="width:60px;">ข้อ</th><th>เกณฑ์ / เหตุผล</th>
                <th class="text-center" style="width:110px;">คะแนน</th><th style="width:130px;">สัดส่วน</th></tr>
          </thead>
          <tbody>${critRows || '<tr><td colspan="4" class="text-muted text-center">— ไม่มีข้อมูลคะแนน —</td></tr>'}</tbody>
        </table>
      </div>`;
  }

  const when = fb.created_at ? String(fb.created_at).replace('T', ' ').slice(0, 16) : '-';
  const deleteBtn = opts.deleteAction
    ? `<button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="${opts.deleteAction}">
         <i class="bi bi-trash me-1"></i>ลบผลตรวจนี้</button>` : '';

  return `
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clipboard2-check text-primary me-2"></i>ผลการตรวจโดย AI</h6>
        <div class="text-muted small mt-1">
          ${aiEsc(fb.phase_label || aiPhaseLabel(fb.essay_phase))}${fb.student_name ? ' · ' + aiEsc(fb.student_name) : ''}
        </div>
      </div>
      ${deleteBtn}
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="p-3 rounded-3 bg-light text-center h-100">
          <div class="text-muted small mb-1">คะแนนโดยประมาณจาก AI</div>
          <div class="fs-3 fw-bold text-primary">${fb.total_score} <span class="fs-6 text-muted fw-normal">/ ${fb.max_score}</span></div>
          <div class="ai-score-bar mt-2"><span style="width:${pct}%"></span></div>
          <div class="text-muted mt-2" style="font-size:0.75rem;">
            ไม่รวมข้อ 4.3 ความเรียบร้อย/ลายมือ ซึ่งต้องดูจากต้นฉบับที่เขียนด้วยมือ
          </div>
        </div>
      </div>
      <div class="col-md-8">
        <div class="p-3 rounded-3 bg-light h-100">
          <div class="mb-2">
            <span class="badge bg-primary-subtle text-primary-emphasis">ระดับคุณภาพโดยประมาณ: ${aiEsc(fb.quality_level || '-')}</span>
          </div>
          <div class="text-dark" style="line-height:1.8;">${aiEsc(fb.overall || '')}</div>
        </div>
      </div>
    </div>

    <h6 class="fw-bold text-success mb-2"><i class="bi bi-hand-thumbs-up-fill me-2"></i>จุดแข็งของงานชิ้นนี้</h6>
    ${strengths}

    <h6 class="fw-bold text-warning-emphasis mt-4 mb-2"><i class="bi bi-tools me-2"></i>จุดที่ควรปรับปรุง พร้อมวิธีแก้</h6>
    ${improvements}

    ${nextSteps ? `<h6 class="fw-bold text-primary mt-4 mb-2"><i class="bi bi-list-check me-2"></i>สิ่งที่ควรทำต่อในร่างถัดไป</h6>${nextSteps}` : ''}

    ${fb.encouragement ? `<div class="alert alert-success border-0 rounded-3 mt-4 mb-0">
      <i class="bi bi-heart-fill me-2"></i>${aiEsc(fb.encouragement)}</div>` : ''}

    ${scoreTable}

    <div class="text-muted mt-3" style="font-size:0.75rem;">
      <i class="bi bi-cpu me-1"></i>ตรวจโดยโมเดล ${aiEsc(fb.model || '-')} (${aiEsc(fb.provider || '-')}) · เมื่อ ${aiEsc(when)}
      ${fb.requested_role === 'teacher' ? ' · สั่งตรวจโดยคุณครู' : ''}
      <br><i class="bi bi-info-circle me-1"></i>ข้อเสนอแนะนี้เป็นแนวทางเพื่อพัฒนางานเขียน ไม่ใช่คะแนนจริง และไม่ถูกนำไปรวมกับคะแนนของครู เพื่อน หรือการประเมินตนเอง
    </div>`;
}
