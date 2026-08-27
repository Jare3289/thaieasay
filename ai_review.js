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

/* ล้างระดับคะแนนที่คุณครูเลือกไว้ของข้อหนึ่ง (กลับไปเป็น "ยังไม่ให้คะแนน") */
function aiClearManualInput(itemId) {
  document.querySelectorAll('.ai-manual-input[data-manual-id="' + itemId + '"]')
    .forEach(el => { el.checked = false; });
}

/* ---- เทียบกับการตรวจครั้งก่อน (ตรวจครั้งที่ 2 ขึ้นไป) ---- */

// ตัดทศนิยมที่ไม่จำเป็นออก (40.00 → 40, 40.50 → 40.5)
function aiFmt(n) {
  const v = Number(n || 0);
  return (Math.round(v * 100) / 100).toString();
}

// ป้ายบอกส่วนต่างของคะแนน (+2 / -1.5 / เท่าเดิม)
function aiDeltaBadge(delta, opts) {
  opts = opts || {};
  const d = Math.round(Number(delta || 0) * 100) / 100;
  const cls  = d > 0 ? 'ai-delta-up' : (d < 0 ? 'ai-delta-down' : 'ai-delta-same');
  const icon = d > 0 ? 'bi-arrow-up-short' : (d < 0 ? 'bi-arrow-down-short' : 'bi-dash');
  const text = d > 0 ? '+' + aiFmt(d) : (d < 0 ? aiFmt(d) : 'เท่าเดิม');
  return `<span class="ai-delta ${cls}"><i class="bi ${icon}"></i>${aiEsc(text)}${
    (d !== 0 && opts.unit !== false) ? ' คะแนน' : ''}</span>`;
}

// ป้ายสถานะของจุดที่ควรปรับปรุง เมื่อเทียบกับครั้งก่อน
function aiStatusBadge(status) {
  if (status === 'open')    return '<span class="badge ai-tag-open"><i class="bi bi-arrow-repeat me-1"></i>จุดเดิม · ยังต้องแก้</span>';
  if (status === 'partial') return '<span class="badge ai-tag-partial"><i class="bi bi-hourglass-split me-1"></i>แก้แล้วบางส่วน</span>';
  if (status === 'new')     return '<span class="badge ai-tag-new"><i class="bi bi-plus-circle me-1"></i>จุดใหม่ในฉบับแก้ไข</span>';
  return '';
}

/**
 * กล่อง "ฉบับแก้ไขนี้เปลี่ยนไปจากการตรวจครั้งก่อนอย่างไร"
 * แสดงเฉพาะเมื่อฉบับนี้ถูกตรวจมาแล้วอย่างน้อย 2 ครั้ง (fb.progress.has_prev = true)
 */
function aiProgressHTML(fb, opts) {
  opts = opts || {};
  const p = fb.progress;
  if (!p || !p.has_prev) return '';

  const prevAt = p.prev_at ? String(p.prev_at).replace('T', ' ').slice(0, 16) : '';

  // แถบสรุปตัวเลข: คะแนนรวมของ AI ขยับเท่าไร ระดับคุณภาพเปลี่ยนไหม ข้อไหนขึ้น/ลง/เท่าเดิม
  const chips = [
    `<span class="ai-progress-chip">
       <span class="text-muted">คะแนน AI</span>
       <span class="fw-bold">${aiFmt(p.prev_total)} → ${aiFmt(p.total)}</span>
       <span class="text-muted">/ ${aiFmt(p.max_score)}</span>
       ${aiDeltaBadge(p.total_delta)}
     </span>`,
    (p.prev_quality || p.quality)
      ? `<span class="ai-progress-chip">
           <span class="text-muted">ระดับคุณภาพ</span>
           <span class="fw-bold">${aiEsc(p.prev_quality || '-')} → ${aiEsc(p.quality || '-')}</span>
           ${p.quality_changed ? '<span class="badge bg-primary-subtle text-primary-emphasis">เปลี่ยนระดับ</span>' : ''}
         </span>`
      : '',
    `<span class="ai-progress-chip">
       <span class="ai-delta ai-delta-up"><i class="bi bi-arrow-up-short"></i>ดีขึ้น ${p.up} ข้อ</span>
       <span class="ai-delta ai-delta-down"><i class="bi bi-arrow-down-short"></i>ลดลง ${p.down} ข้อ</span>
       <span class="ai-delta ai-delta-same"><i class="bi bi-dash"></i>เท่าเดิม ${p.same} ข้อ</span>
     </span>`
  ].filter(Boolean).join('');

  // 3 คอลัมน์: แก้ได้แล้ว / ยังต้องแก้ต่อ / จุดใหม่
  const listCol = (title, icon, cls, items, emptyMsg) => {
    const rows = items.map(it => {
      const head = it.criterion ? `ข้อ ${aiEsc(it.criterion)}${it.name ? ' ' + aiEsc(it.name) : ''}` : 'ภาพรวมของงานเขียน';
      const dl   = (it.delta === null || it.delta === undefined) ? '' : aiDeltaBadge(it.delta, { unit: false });
      return `<div class="ai-progress-item">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
          <span class="fw-semibold">${head}</span>${dl}
        </div>
        ${it.issue ? `<div class="text-muted small mt-1">${aiEsc(it.issue)}</div>` : ''}
      </div>`;
    }).join('');
    return `<div class="col-lg-4">
      <div class="ai-progress-col ${cls} h-100">
        <div class="ai-progress-col-head"><i class="bi ${icon} me-1"></i>${aiEsc(title)}
          <span class="badge bg-white text-secondary border ms-1">${items.length}</span></div>
        ${rows || `<div class="text-muted small">${aiEsc(emptyMsg)}</div>`}
      </div>
    </div>`;
  };

  const cols = `<div class="row g-2 mt-1">
    ${listCol('แก้ได้แล้วจากครั้งก่อน', 'bi-check2-circle', 'ai-progress-fixed', p.fixed || [],
              'ครั้งนี้ยังไม่มีจุดใดที่ครั้งก่อนแจ้งไว้แล้วแก้ได้ครบ')}
    ${listCol('ยังต้องแก้ต่อ', 'bi-arrow-repeat', 'ai-progress-still', p.still || [],
              'ไม่มีจุดเดิมค้างอยู่แล้ว')}
    ${listCol('จุดใหม่ในฉบับแก้ไข', 'bi-plus-circle', 'ai-progress-new', p.new || [],
              'ไม่มีจุดใหม่เพิ่มขึ้นมา')}
  </div>`;

  // สิ่งที่ AI เขียนบรรยายเอง (มีเมื่อโมเดลตอบกลับมาครบ)
  const resolved = (p.ai_resolved || []).map(t =>
    `<li>${aiEsc(t)}</li>`).join('');
  const regress  = (p.ai_regressions || []).map(t =>
    `<li>${aiEsc(t)}</li>`).join('');
  const notes = `
    ${resolved ? `<div class="ai-progress-note ai-progress-note-good mt-2">
      <div class="fw-semibold"><i class="bi bi-stars me-1"></i>AI เห็นว่านักเรียนแก้จุดเหล่านี้ได้แล้ว</div>
      <ul class="mb-0 mt-1 small">${resolved}</ul></div>` : ''}
    ${regress ? `<div class="ai-progress-note ai-progress-note-warn mt-2">
      <div class="fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>จุดที่เคยทำได้ดี แต่ฉบับนี้กลับถอยลง</div>
      <ul class="mb-0 mt-1 small">${regress}</ul></div>` : ''}`;

  // ตารางคะแนนรายข้อ ครั้งก่อน → ครั้งนี้ (เฉพาะข้อที่เปลี่ยน) — ไม่แสดงในโหมดย่อ
  let changeTable = '';
  const changed = (p.criteria || []).filter(c => c.dir !== 'same');
  if (!opts.compact && changed.length) {
    const rows = changed.map(c => `<tr>
      <td class="text-nowrap fw-semibold">${aiEsc(c.id)}</td>
      <td>${aiEsc(c.name || '')}</td>
      <td class="text-center text-nowrap text-muted">${aiFmt(c.prev_weighted)}</td>
      <td class="text-center text-nowrap fw-bold">${aiFmt(c.weighted)} <span class="text-muted fw-normal">/ ${aiFmt(c.max)}</span></td>
      <td class="text-center text-nowrap">${aiDeltaBadge(c.delta, { unit: false })}</td>
    </tr>`).join('');
    changeTable = `<div class="table-responsive mt-2">
      <table class="table table-sm table-bordered align-middle mb-0 bg-white">
        <thead class="table-light">
          <tr><th style="width:60px;">ข้อ</th><th>เกณฑ์ที่คะแนนเปลี่ยน</th>
              <th class="text-center" style="width:90px;">ครั้งก่อน</th>
              <th class="text-center" style="width:110px;">ครั้งนี้</th>
              <th class="text-center" style="width:110px;">เปลี่ยนแปลง</th></tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
  }

  return `<div class="ai-progress-box rounded-3 p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
      <span class="fw-bold text-dark">
        <i class="bi bi-clock-history text-primary me-2"></i>ผลการตรวจครั้งที่ ${p.round} — เทียบกับครั้งที่ ${p.prev_round}
      </span>
      ${prevAt ? `<span class="text-muted small">ครั้งก่อนตรวจเมื่อ ${aiEsc(prevAt)}</span>` : ''}
    </div>
    <div class="d-flex flex-wrap gap-2">${chips}</div>
    ${p.comment ? `<div class="ai-progress-comment mt-2">${aiEsc(p.comment)}</div>` : ''}
    ${notes}
    ${cols}
    ${changeTable}
  </div>`;
}

/**
 * สร้าง HTML ของผลตรวจ AI
 * opts.compact = true : แบบย่อ (ใช้ในหน้าเขียนเรียงความ — ไม่แสดงตารางคะแนนรายข้อ)
 * opts.deleteAction    : โค้ด onclick ของปุ่มลบ (ครูเท่านั้น) เว้นว่างไว้ = ไม่แสดงปุ่ม
 * opts.manualAction    : โค้ด onclick ของปุ่มบันทึกคะแนนที่ครูให้เอง (ครูเท่านั้น) เว้นว่าง = ดูอย่างเดียว
 * opts.recheckAction   : โค้ด onclick ของปุ่ม "ให้ AI ตรวจใหม่" ที่ขึ้นเมื่อต้นฉบับถูกแก้หลังตรวจ (ครูเท่านั้น)
 */
function aiFeedbackHTML(fb, opts) {
  opts = opts || {};

  // คะแนนแบ่งเป็น 2 ส่วน: ส่วนที่ AI ประเมินได้ (58) + ส่วนที่ครูต้องให้เอง (2) = เต็ม 60 ตามเกณฑ์จริงของครู
  const manualItems   = fb.manual_items || [];
  const teacherScores = fb.teacher_scores || {};
  const manualMax     = Number(fb.manual_max || 0);
  const fullMax       = Number(fb.full_max || fb.max_score || 0);
  const teacherTotal  = Number(fb.teacher_total || 0);
  const combined      = Number(fb.combined_total != null ? fb.combined_total : fb.total_score);
  const manualDone    = manualItems.length > 0 ? !!fb.manual_done : true;
  // คะแนนข้อที่ครูให้เองมาจากไหน: 'evaluation' = ดึงจากแบบประเมินในหน้าประเมิน, 'ai_page' = กรอกในหน้านี้
  const fromEval      = (fb.teacher_source === 'evaluation');
  const evalNote      = fromEval
    ? `ดึงมาจากแบบประเมินของคุณครูในหน้าประเมินแล้ว`
      + (fb.teacher_by ? ` (ผู้ประเมิน: ${aiEsc(fb.teacher_by)}` + (fb.teacher_at ? ` · ${aiEsc(String(fb.teacher_at).replace('T', ' ').slice(0, 16))}` : '') + ')' : '')
    : '';
  const pct           = fullMax > 0 ? Math.round((combined / fullMax) * 100) : 0;

  // ตรวจซ้ำหรือไม่ — ใช้ตัดสินว่าจะแสดงกล่องเทียบกับครั้งก่อน และป้ายสถานะรายจุดหรือเปล่า
  const prog     = (fb.progress && fb.progress.has_prev) ? fb.progress : null;
  const stillSet = prog ? new Set((prog.still || []).map(x => x.criterion || '')) : null;
  const newSet   = prog ? new Set((prog.new   || []).map(x => x.criterion || '')) : null;
  const prevOf   = {};
  if (prog) (prog.criteria || []).forEach(c => { prevOf[c.id] = c; });

  const strengths = (fb.strengths || []).map(s =>
    `<div class="p-3 rounded-3 mb-2 ai-strength-card"><i class="bi bi-check-circle-fill text-success me-2"></i>${aiEsc(s)}</div>`
  ).join('') || '<div class="text-muted small mb-2">— ไม่มีข้อมูล —</div>';

  // จุดที่ควรปรับปรุง — แยกให้เห็นชัดเป็น 2 ช่อง "บกพร่องอะไร" กับ "แก้อย่างไร"
  // พร้อมบอกว่าเป็นเกณฑ์ข้อไหน และข้อนั้น AI ให้กี่คะแนน เสียคะแนนไปเท่าไร
  const improvements = (fb.improvements || []).map((it, i) => {
    const c        = (fb.scores && fb.scores[it.criterion]) ? fb.scores[it.criterion] : null;
    const critName = c ? (c.name || '') : '';
    const lost     = c ? Math.round((c.max - c.weighted) * 100) / 100 : 0;
    const scoreTag = c
      ? `AI ให้ ${c.weighted} / ${c.max} คะแนน${lost > 0 ? ' · เสียไป ' + lost + ' คะแนน' : ''}`
      : '';
    // ตรวจซ้ำ: จุดนี้เป็นจุดเดิมที่ยังแก้ไม่ตก หรือเป็นจุดที่เพิ่งพบในฉบับแก้ไข
    const key    = it.criterion || '';
    const status = prog
      ? (it.status || (stillSet.has(key) ? 'open' : (newSet.has(key) ? 'new' : '')))
      : '';
    const critDelta = (prog && prevOf[key]) ? aiDeltaBadge(prevOf[key].delta, { unit: false }) : '';
    return `<div class="ai-improve-card rounded-3 mb-3 overflow-hidden">
      <div class="ai-improve-head d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-bold text-dark">
          <span class="badge bg-warning text-dark me-1">จุดที่ ${i + 1}</span>
          ${it.criterion ? `ข้อ ${aiEsc(it.criterion)} ${aiEsc(critName)}` : 'ภาพรวมของงานเขียน'}
          ${aiStatusBadge(status)}${critDelta ? ' ' + critDelta : ''}
        </span>
        ${scoreTag ? `<span class="badge bg-white border text-secondary fw-semibold">${aiEsc(scoreTag)}</span>` : ''}
      </div>
      <div class="p-3">
        <div class="ai-fix-block ai-fix-issue mb-2">
          <div class="ai-fix-head text-danger-emphasis"><i class="bi bi-exclamation-octagon-fill me-1"></i>บกพร่องอะไร</div>
          <div class="small">${it.issue ? aiEsc(it.issue) : '— AI ไม่ได้ระบุ —'}</div>
        </div>
        <div class="ai-fix-block ai-fix-how">
          <div class="ai-fix-head text-success-emphasis"><i class="bi bi-wrench-adjustable me-1"></i>แก้อย่างไร</div>
          <div class="small">${it.suggestion ? aiEsc(it.suggestion) : '— AI ไม่ได้ระบุ —'}</div>
          ${it.example ? `<div class="ai-fix-example mt-2 small">
            <span class="fw-semibold"><i class="bi bi-quote me-1"></i>ตัวอย่างหลังแก้:</span>
            <span class="fst-italic">${aiEsc(it.example)}</span>
          </div>` : ''}
        </div>
      </div>
    </div>`;
  }).join('') || '<div class="text-muted small mb-2">— ไม่มีข้อมูล —</div>';

  // แถบเตือนเมื่อนักเรียนแก้ไขต้นฉบับหลังจาก AI ตรวจไปแล้ว — ผลตรวจที่เห็นเป็นของฉบับก่อนแก้
  const markedAt = fb.recheck_marked_at ? String(fb.recheck_marked_at).replace('T', ' ').slice(0, 16) : '';
  const recheckBox = fb.needs_recheck
    ? `<div class="ai-recheck-alert rounded-3 p-3 mb-3 d-flex align-items-start gap-3 flex-wrap">
         <i class="bi bi-arrow-repeat fs-4 text-warning-emphasis"></i>
         <div class="flex-grow-1">
           <div class="fw-bold text-warning-emphasis">ต้นฉบับถูกแก้ไขหลังจาก AI ตรวจ — อยู่ในคิวรอตรวจใหม่</div>
           <div class="small text-muted">
             ผลตรวจและคะแนนด้านล่างเป็นของฉบับ<strong>ก่อนแก้ไข</strong>
             ${markedAt ? ` · นักเรียนบันทึกฉบับแก้ไขเมื่อ ${aiEsc(markedAt)}` : ''}
             ${opts.recheckAction ? '' : ' · รอคุณครูสั่งให้ AI ตรวจใหม่อีกครั้ง'}
           </div>
         </div>
         ${opts.recheckAction ? `<button class="btn btn-warning fw-bold rounded-pill px-4" onclick="${opts.recheckAction}">
           <i class="bi bi-stars me-1"></i>ให้ AI ตรวจใหม่ตอนนี้
         </button>` : ''}
       </div>`
    : '';

  const nextSteps = (fb.next_steps || []).map(s =>
    `<div class="p-3 rounded-3 mb-2 ai-next-card"><i class="bi bi-arrow-right-circle-fill text-primary me-2"></i>${aiEsc(s)}</div>`
  ).join('');

  // ---- ส่วนคะแนนที่ "ครูต้องให้เอง" (AI ตรวจจากไฟล์พิมพ์แทนไม่ได้ เช่น ข้อ 4.3 ลายมือ/ความเรียบร้อย) ----
  // แถวต่อท้ายตารางคะแนนรายเกณฑ์ ให้เห็นคะแนนครบทุกข้อในตารางเดียว ส่วนช่องให้คะแนนอยู่ใต้ตาราง
  const manualRows = manualItems.map(it => {
    const cur  = teacherScores[it.id];
    const has  = !!cur;
    const cpct = (has && it.max > 0) ? Math.round((cur.weighted / it.max) * 100) : 0;
    const lv   = has ? (it.levels || []).find(l => Number(l.score) === Number(cur.raw)) : null;
    return `<tr class="ai-crit-row" data-manual-row="1">
      <td class="text-nowrap fw-semibold">${aiEsc(it.id)}</td>
      <td>${aiEsc(it.name)}
        <div class="text-muted small mt-1">
          <i class="bi bi-person-check me-1"></i>ครูเป็นผู้ให้คะแนนข้อนี้${lv ? ' · ระดับ ' + aiEsc(lv.label) : ''}
          ${has && fromEval ? ' · <span class="text-success-emphasis"><i class="bi bi-link-45deg"></i>ดึงจากแบบประเมินของครู</span>' : ''}
          ${opts.manualAction && !has ? ' · <span class="text-warning-emphasis">เลือกระดับได้ที่แผงใต้ตารางนี้</span>' : ''}
        </div>
      </td>
      <td class="text-center text-nowrap fw-bold">
        ${has ? `${cur.weighted} <span class="text-muted fw-normal">/ ${it.max}</span>`
              : `<span class="text-warning-emphasis small">รอครูให้คะแนน</span>`}
      </td>
      ${prog ? '<td class="text-center text-muted small" title="ข้อนี้คุณครูให้คะแนนเอง ไม่ได้เปลี่ยนตามรอบที่ AI ตรวจ">—</td>' : ''}
      <td style="min-width:110px;"><div class="ai-score-bar"><span style="width:${cpct}%"></span></div></td>
    </tr>`;
  }).join('');

  // แผงให้คะแนนของครู — วางไว้ใต้ตาราง "คะแนนรายเกณฑ์ (ประมาณการ)" และใช้การ์ดเกณฑ์
  // หน้าตาเดียวกับข้อ 4.3 ความเรียบร้อย ในหน้า evaluation.php เพื่อให้คุณครูอ่านเกณฑ์แล้วเลือกได้ทันที
  let manualBox = '';
  if (manualItems.length && !opts.compact) {
    const blocks = manualItems.map(it => {
      const cur    = teacherScores[it.id];
      const rawVal = cur ? String(cur.raw) : '';
      const levels = (it.levels && it.levels.length)
        ? it.levels
        : [4, 3, 2, 1, 0].map(n => ({ score: n, label: String(n), desc: '' }));
      const lv = cur ? levels.find(l => Number(l.score) === Number(cur.raw)) : null;

      // ป้ายบอกว่าข้อนี้คุณครูให้ไว้เท่าไรแล้ว (หรือยังไม่ได้ให้)
      const given = cur
        ? `<span class="badge rounded-pill px-3 py-2" style="background:#d1fae5; color:#065f46; border:1px solid #a7f3d0;">
             <i class="bi ${fromEval ? 'bi-link-45deg' : 'bi-person-check'} me-1"></i>คะแนนที่ให้ไว้: ${lv ? aiEsc(lv.label) + ' · ' : ''}${cur.weighted} / ${it.max} คะแนน${fromEval ? ' (จากแบบประเมิน)' : ''}</span>`
        : `<span class="badge rounded-pill px-3 py-2" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
             <i class="bi bi-hourglass-split me-1"></i>ยังไม่ได้ให้คะแนนข้อนี้</span>`;

      // ผู้ที่ไม่มีสิทธิ์ให้คะแนน (นักเรียน/ผู้เชี่ยวชาญ) เห็นเป็นบรรทัดสรุปอย่างเดียว
      if (!opts.manualAction) {
        return `<div class="mb-2">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-bold text-dark">ข้อ ${aiEsc(it.id)} ${aiEsc(it.name)}
              <span class="text-muted fw-normal">(คะแนนเต็ม ${it.max})</span></span>
            ${given}
          </div>
          ${lv && lv.desc ? `<div class="text-muted small mt-1">${aiEsc(lv.desc)}</div>` : ''}
        </div>`;
      }

      const cards = levels.map(l => `
        <div class="col">
          <input type="radio" name="ai_manual_${aiEsc(it.id)}" value="${l.score}" data-manual-id="${aiEsc(it.id)}"
                 id="aiopt_${aiEsc(it.id)}_${l.score}" class="score-radio ai-manual-input"${rawVal === String(l.score) ? ' checked' : ''}>
          <label for="aiopt_${aiEsc(it.id)}_${l.score}" class="rubric-card w-100 text-start">
            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
              <span class="fw-bold fs-6 text-dark">${aiEsc(l.label)}</span>
              <div class="check-circle"><i class="bi bi-check-lg"></i></div>
            </div>
            <p class="text-secondary mb-0" style="font-size: 13px; line-height: 1.5; font-weight: 400;">${aiEsc(l.desc || '')}</p>
          </label>
        </div>`).join('');

      return `<div class="mb-3 text-start">
        <div class="mb-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h6 class="fw-bold mb-0 text-dark">ข้อ ${aiEsc(it.id)} ${aiEsc(it.name)}
            <span class="text-muted fw-normal">(คะแนนเต็ม ${it.max})</span></h6>
          <div class="d-flex align-items-center flex-wrap gap-2">
            ${given}
            <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0"
                    onclick="aiClearManualInput('${aiEsc(it.id)}')">
              <i class="bi bi-eraser me-1"></i>ล้างคะแนนข้อนี้
            </button>
          </div>
        </div>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3">${cards}</div>
      </div>`;
    }).join('');

    manualBox = `<div class="card border-0 rounded-4 mt-3" style="background:#f4f9f4; border-left:4px solid #198754 !important;">
      <div class="card-body p-3 p-md-4">
        <h6 class="fw-bold text-success mb-1">
          <i class="bi bi-pencil-square me-2"></i>คะแนนส่วนที่คุณครูให้เอง
          <span class="text-muted fw-normal small">(เต็ม ${manualMax} คะแนน)</span>
        </h6>
        <div class="text-muted small mb-3">
          ข้อนี้ AI ประเมินจากไฟล์ที่พิมพ์ไม่ได้ ต้องดูจากต้นฉบับที่นักเรียนเขียนด้วยมือ —
          ${opts.manualAction ? 'เลือกระดับคะแนนแล้วกดบันทึก คะแนนจะไปรวมกับคะแนนของ AI ให้ครบเต็ม ' + fullMax
                              : 'คุณครูเป็นผู้ให้คะแนนข้อนี้'}
        </div>
        ${evalNote ? `<div class="alert border-0 rounded-3 small py-2 px-3 mb-3"
                           style="background:#ecfdf5; border-left:4px solid #10b981 !important;">
          <i class="bi bi-link-45deg me-1"></i>${evalNote}
          ${opts.manualAction ? ' — ระบบเลือกระดับตามคะแนนนั้นไว้ให้แล้ว ถ้าตรงแล้วไม่ต้องทำอะไรต่อ หรือเลือกใหม่แล้วกดบันทึกเพื่อแก้เฉพาะในหน้านี้' : ''}
        </div>` : ''}
        ${blocks}
        ${opts.manualAction ? `<div class="d-flex justify-content-end">
          <button class="btn btn-success rounded-pill px-4 fw-bold" onclick="${opts.manualAction}">
            <i class="bi bi-check2-circle me-1"></i>บันทึกคะแนนของครู
          </button>
        </div>` : ''}
      </div>
    </div>`;
  }

  // ตารางคะแนนรายเกณฑ์ (ซ่อนในโหมดย่อ)
  let scoreTable = '';
  if (!opts.compact) {
    const critRows = Object.keys(fb.scores || {}).sort().map(k => {
      const c = fb.scores[k];
      const cpct = c.max > 0 ? Math.round((c.weighted / c.max) * 100) : 0;
      const pc   = prog ? prevOf[k] : null;
      return `<tr class="ai-crit-row">
        <td class="text-nowrap fw-semibold">${aiEsc(k)}</td>
        <td>${aiEsc(c.name || '')}${c.reason ? `<div class="text-muted small mt-1">${aiEsc(c.reason)}</div>` : ''}</td>
        <td class="text-center text-nowrap fw-bold">${c.weighted} <span class="text-muted fw-normal">/ ${c.max}</span></td>
        ${prog ? `<td class="text-center text-nowrap small">${pc
          ? `<span class="text-muted">${aiFmt(pc.prev_weighted)}</span> ${aiDeltaBadge(pc.delta, { unit: false })}`
          : '<span class="text-muted">—</span>'}</td>` : ''}
        <td style="min-width:110px;"><div class="ai-score-bar"><span style="width:${cpct}%"></span></div></td>
      </tr>`;
    }).join('');
    scoreTable = `
      <h6 class="fw-bold text-dark mt-4 mb-2"><i class="bi bi-list-ol me-2"></i>คะแนนรายเกณฑ์ (ประมาณการ)</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr><th style="width:60px;">ข้อ</th><th>เกณฑ์ / เหตุผล</th>
                <th class="text-center" style="width:110px;">คะแนน</th>
                ${prog ? '<th class="text-center" style="width:130px;">เทียบครั้งก่อน</th>' : ''}
                <th style="width:130px;">สัดส่วน</th></tr>
          </thead>
          <tbody>${critRows || `<tr><td colspan="${prog ? 5 : 4}" class="text-muted text-center">— ไม่มีข้อมูลคะแนน —</td></tr>`}${manualRows}</tbody>
          <tfoot class="table-light">
            <tr class="fw-bold">
              <td colspan="2" class="text-end">คะแนนรวมตามเกณฑ์ของครู</td>
              <td class="text-center text-nowrap">${combined} <span class="text-muted fw-normal">/ ${fullMax}</span></td>
              ${prog ? `<td class="text-center text-nowrap" title="ส่วนต่างเฉพาะคะแนนที่ AI ประเมิน (${aiFmt(prog.prev_total)} → ${aiFmt(prog.total)})">
                ${aiDeltaBadge(prog.total_delta, { unit: false })}</td>` : ''}
              <td><div class="ai-score-bar"><span style="width:${pct}%"></span></div></td>
            </tr>
          </tfoot>
        </table>
      </div>
      ${manualBox}`;
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
          ${Number(fb.review_round || 1) > 1
            ? `<span class="badge ai-round-badge ms-1"><i class="bi bi-arrow-repeat me-1"></i>ตรวจครั้งที่ ${Number(fb.review_round)}</span>`
            : ''}
        </div>
      </div>
      ${deleteBtn}
    </div>

    ${recheckBox}

    ${aiProgressHTML(fb, opts)}

    <div class="row g-3 mb-4">
      <div class="col-lg-5">
        <div class="p-3 rounded-3 bg-light h-100">
          <div class="text-muted small mb-1">คะแนนรวมตามเกณฑ์ของครู</div>
          <div class="d-flex align-items-end gap-2">
            <div class="fs-2 fw-bold text-primary lh-1">${combined}</div>
            <div class="text-muted fw-normal pb-1">/ ${fullMax}</div>
            ${manualDone ? '' : '<span class="badge bg-warning text-dark ms-auto mb-1">ยังให้คะแนนไม่ครบ</span>'}
          </div>
          <div class="ai-score-bar mt-2"><span style="width:${pct}%"></span></div>
          <div class="d-flex justify-content-between small mt-2">
            <span><i class="bi bi-robot me-1 text-primary"></i>AI ประเมิน</span>
            <span class="fw-semibold">${fb.total_score} / ${fb.max_score}</span>
          </div>
          <div class="d-flex justify-content-between small">
            <span><i class="bi ${fromEval ? 'bi-link-45deg' : 'bi-person-check'} me-1 text-success"></i>ครูให้เอง${fromEval ? ' (จากแบบประเมิน)' : ''}</span>
            <span class="fw-semibold">${manualDone ? teacherTotal : '—'} / ${manualMax}</span>
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="p-3 rounded-3 bg-light h-100">
          <div class="mb-2 d-flex flex-wrap gap-2">
            <span class="badge bg-primary-subtle text-primary-emphasis">
              ระดับคุณภาพ${manualDone ? '' : 'โดยประมาณ'}: ${aiEsc((manualDone && fb.full_quality_level) ? fb.full_quality_level : (fb.quality_level || '-'))}
            </span>
            ${manualDone ? '' : `<span class="badge bg-light text-muted border">ยังขาดคะแนนข้อที่ครูต้องให้เอง (เต็ม ${manualMax})</span>`}
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
      <br><i class="bi bi-info-circle me-1"></i>ข้อเสนอแนะนี้เป็นแนวทางเพื่อพัฒนางานเขียน คะแนนรวมเต็ม ${fullMax} คิดจากคะแนนที่ AI ประเมิน (${fb.max_score}) บวกข้อที่คุณครูให้เอง (${manualMax})
      และไม่ถูกนำไปรวมกับคะแนนจริงของครู เพื่อน หรือการประเมินตนเองในระบบประเมิน
    </div>`;
}
