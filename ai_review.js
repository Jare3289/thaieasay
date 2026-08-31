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

// ชื่อสั้น ใช้บนป้าย/แกนกราฟที่พื้นที่จำกัด
const AI_PHASE_SHORT_MAP = {
  pretest:  'ก่อนเรียน',
  task1_d1: 'D1.1',
  task1_d2: 'D1.2',
  task2_d1: 'D2.1',
  task2_d2: 'D2.2',
  posttest: 'หลังเรียน'
};

// คู่รอบงานที่ต้องเทียบกันเสมอตามที่คุณครูกำหนด (ตรงกับ ai_baseline_pairs() ฝั่งเซิร์ฟเวอร์)
const AI_BASELINE_PAIRS = {
  task1_d2: 'task1_d1',
  task2_d2: 'task2_d1',
  posttest: 'pretest'
};

function aiPhaseLabel(phase) {
  return AI_PHASE_LABEL_MAP[phase] || phase;
}

function aiPhaseShort(phase) {
  return AI_PHASE_SHORT_MAP[phase] || phase;
}

/* ---- ตัวเลข/ระดับคุณภาพ (ใช้ร่วมกันทุกหน้าของระบบผู้ช่วย AI) ---- */

// ตัดทศนิยมท้ายที่ไม่จำเป็นออก (45.00 → 45, 45.50 → 45.5)
function aiNum(v) {
  const n = Math.round(parseFloat(v) * 100) / 100;
  return isNaN(n) ? '-' : String(n);
}

// ทศนิยม 1 ตำแหน่ง — ใช้กับค่าเฉลี่ยบนการ์ด ไม่ให้ตัวเลขยาวจนตกบรรทัด
function aiNum1(v) {
  const n = Math.round(parseFloat(v) * 10) / 10;
  return isNaN(n) ? '-' : String(n);
}

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
  'ต้องปรับปรุง': 'background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;'
};

function aiLevelBadge(level) {
  if (!level) return '<span class="text-muted">-</span>';
  const style = AI_LEVEL_STYLE[level] || 'background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;';
  return `<span class="badge rounded-pill px-3 py-2 fw-semibold" style="${style}">${aiEsc(level)}</span>`;
}

// คะแนนรวม (เต็ม 60) ของผลตรวจ 1 ฉบับ — ยังไม่มีผลตรวจคืน null
function aiCombinedOf(fb) {
  if (!fb) return null;
  const v = Number(fb.combined_total != null ? fb.combined_total : fb.total_score);
  return isNaN(v) ? null : v;
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

/* ---- ตัวช่วยแสดงส่วนต่างของคะแนน ---- */

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

/* ---- เทียบกับ "ฉบับตั้งต้น" ตามคู่ที่ครูกำหนด (D1.2↔D1.1 · D2.2↔D2.1 · หลังเรียน↔ก่อนเรียน) ---- */

// ป้ายทิศทางที่คำนวณจากคะแนนจริง ใช้เมื่อ AI ไม่ได้ระบุคำตัดสินมา (เช่นผลตรวจที่บันทึกไว้ก่อนหน้า)
function aiDirBadge(dir) {
  if (dir === 'up')   return '<span class="badge ai-tag-better"><i class="bi bi-arrow-up-circle me-1"></i>คะแนนสูงขึ้น</span>';
  if (dir === 'down') return '<span class="badge ai-tag-worse"><i class="bi bi-arrow-down-circle me-1"></i>คะแนนต่ำลง</span>';
  return '<span class="badge ai-tag-same"><i class="bi bi-dash-circle me-1"></i>คะแนนเท่าเดิม</span>';
}

// ป้ายคำตัดสินรายข้อที่ AI ให้มา (ดีขึ้น / เท่าเดิม / แย่ลง)
function aiVerdictBadge(v) {
  if (v === 'better') return '<span class="badge ai-tag-better"><i class="bi bi-arrow-up-circle me-1"></i>ดีขึ้น</span>';
  if (v === 'worse')  return '<span class="badge ai-tag-worse"><i class="bi bi-arrow-down-circle me-1"></i>แย่ลง</span>';
  if (v === 'same')   return '<span class="badge ai-tag-same"><i class="bi bi-dash-circle me-1"></i>เท่าเดิม</span>';
  return '';
}

/**
 * คำนวณผลเทียบกับฉบับตั้งต้นจาก "ผลตรวจสองฉบับที่บันทึกไว้แล้ว"
 * ใช้เป็นตัวสำรองเมื่อฐานข้อมูลยังไม่มีคอลัมน์เทียบ หรือผลตรวจถูกบันทึกไว้ก่อนมีฟีเจอร์นี้
 * ได้โครงสร้างเดียวกับ ai_draft_progress() ฝั่งเซิร์ฟเวอร์ แต่ไม่มีข้อความ before/after ที่ AI ยกมา
 */
function aiComputeDraftCompare(fb, baseFb) {
  const phase  = fb.essay_phase || '';
  const basePh = AI_BASELINE_PAIRS[phase] || '';
  if (!basePh) return { has_baseline: false, pairable: false };

  const info = { pairable: true, phase: basePh, label: aiPhaseLabel(basePh), short: aiPhaseShort(basePh) };
  if (!baseFb || !baseFb.scores) return Object.assign({ has_baseline: false }, info);

  const criteria = [];
  let up = 0, down = 0, same = 0;
  Object.keys(fb.scores || {}).sort().forEach(id => {
    const c = fb.scores[id], b = baseFb.scores[id];
    if (!c || !b) return;
    const bw = Math.round(Number(b.weighted) * 100) / 100;
    const cw = Math.round(Number(c.weighted) * 100) / 100;
    const delta = Math.round((cw - bw) * 100) / 100;
    const dir = delta > 0 ? 'up' : (delta < 0 ? 'down' : 'same');
    if (dir === 'up') up++; else if (dir === 'down') down++; else same++;
    criteria.push({
      id, name: c.name || b.name || '', max: Number(c.max) || 0,
      base_raw: Number(b.raw) || 0, raw: Number(c.raw) || 0,
      base_weighted: bw, weighted: cw, delta, dir,
      before: '', after: '', verdict: '', note: ''
    });
  });

  const baseTotal = Math.round(Number(baseFb.total_score) * 100) / 100;
  const total     = Math.round(Number(fb.total_score) * 100) / 100;
  return Object.assign({
    has_baseline:    true,
    estimated:       true,          // คำนวณจากคะแนนที่บันทึกไว้ ไม่ได้มาจากการเทียบข้อความของ AI
    this_short:      aiPhaseShort(phase),
    reviewed_at:     baseFb.updated_at || baseFb.created_at || '',
    base_total:      baseTotal,
    total:           total,
    delta:           Math.round((total - baseTotal) * 100) / 100,
    max_score:       Number(fb.max_score) || 0,
    base_quality:    baseFb.quality_level || '',
    quality:         fb.quality_level || '',
    quality_changed: !!(baseFb.quality_level && fb.quality_level && baseFb.quality_level !== fb.quality_level),
    base_words:      0,
    words:           0,
    up, down, same,
    improved:        (total - baseTotal) > 0,
    identical:       (total === baseTotal && up === 0 && down === 0 && same > 0),
    same_text:       false,
    criteria,
    comment:         '',
    changes:         []
  }, info);
}

/**
 * เติมผลเทียบ "ฉบับตั้งต้น" ให้ผลตรวจทุกฉบับที่โหลดมา
 * ฉบับที่เซิร์ฟเวอร์เทียบมาให้แล้วจะคงของเดิมไว้ ส่วนฉบับเก่าที่ยังไม่มีจะคำนวณจากคะแนนที่บันทึกไว้แทน
 * all = { รหัสรอบงาน => ผลตรวจ }
 */
function aiAttachDraftCompare(all) {
  Object.keys(all || {}).forEach(ph => {
    const fb = all[ph];
    if (!fb) return;
    if (fb.draft_compare && fb.draft_compare.has_baseline) return;
    const basePh = AI_BASELINE_PAIRS[ph];
    if (!basePh) { fb.draft_compare = { has_baseline: false, pairable: false }; return; }
    fb.draft_compare = aiComputeDraftCompare(fb, all[basePh]);
  });
  return all;
}

// ป้ายบอกสถานะของแต่ละส่วนในเรียงความเมื่อเทียบกับฉบับตั้งต้น
const AI_EDIT_STATUS = {
  edited:  { label: 'แก้ไข',       icon: 'bi-pencil-fill',      cls: 'ai-edit-edited'  },
  added:   { label: 'เพิ่มใหม่',    icon: 'bi-plus-circle-fill', cls: 'ai-edit-added'   },
  removed: { label: 'ตัดออก',      icon: 'bi-dash-circle-fill', cls: 'ai-edit-removed' },
  same:    { label: 'ไม่ได้แก้',    icon: 'bi-dash',             cls: 'ai-edit-same'    }
};

/**
 * รายการ "สิ่งที่เปลี่ยนไปจากฉบับตั้งต้น" ทีละส่วนของเรียงความ
 * มาจากการเทียบข้อความที่ระบบทำเอง จึงบอกได้แน่นอนว่าส่วนไหนถูกแก้ ส่วนไหนไม่ได้แตะเลย
 */
function aiEditListHTML(d) {
  const edits = d.edits || [];
  if (!edits.length) return '';

  const sum = d.edit_summary || {};
  const head = (sum.touched === 0)
    ? '<span class="text-danger-emphasis fw-semibold">ไม่มีส่วนไหนถูกแก้เลย — ทุกส่วนเหมือนฉบับตั้งต้นทุกตัวอักษร</span>'
    : `แก้ไข ${sum.edited || 0} ส่วน · เพิ่มใหม่ ${sum.added || 0} ส่วน · ตัดออก ${sum.removed || 0} ส่วน `
      + `<span class="text-muted">· ไม่ได้แก้ ${sum.same || 0} ส่วน</span>`;

  const rows = edits.map(e => {
    const st = AI_EDIT_STATUS[e.status] || AI_EDIT_STATUS.same;
    const words = (e.status === 'edited')
      ? `<span class="text-muted small text-nowrap">${e.base_words} → ${e.words} คำ</span>`
      : (e.status === 'added'   ? `<span class="text-muted small text-nowrap">${e.words} คำ</span>`
      : (e.status === 'removed' ? `<span class="text-muted small text-nowrap">เดิม ${e.base_words} คำ</span>` : ''));

    const quote = (list, tag, cls) => (list && list.length)
      ? `<div class="ai-edit-quote ${cls}">
           <span class="ai-draft-tag ${cls === 'ai-edit-in' ? 'ai-draft-tag-after' : 'ai-draft-tag-before'}">${tag}</span>
           ${list.map(t => aiEsc(t)).join(' <span class="text-muted">/</span> ')}
         </div>`
      : '';

    return `<div class="ai-edit-row ${st.cls}">
      <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <span class="fw-semibold small">
          <i class="bi ${st.icon} me-1"></i>${aiEsc(e.label)}
          <span class="badge ai-edit-badge ms-1">${st.label}</span>
        </span>
        ${words}
      </div>
      ${quote(e.added, 'เพิ่มเข้ามา', 'ai-edit-in')}
      ${quote(e.removed, 'ถูกตัดออก', 'ai-edit-out')}
    </div>`;
  }).join('');

  return `<div class="ai-edit-box mt-3">
    <div class="fw-bold text-dark mb-1">
      <i class="bi bi-pencil-square text-primary me-2"></i>สิ่งที่เปลี่ยนไปจาก ${aiEsc(d.short)}
    </div>
    <div class="small mb-2">${head}</div>
    ${rows}
    <div class="text-muted mt-2" style="font-size:0.75rem;">
      <i class="bi bi-info-circle me-1"></i>ส่วนนี้ระบบเทียบข้อความของสองฉบับเอง ไม่ได้ถาม AI
      จึงยืนยันได้ว่าส่วนที่ขึ้นว่า &quot;ไม่ได้แก้&quot; คือเหมือนเดิมทุกตัวอักษรจริง
      ${d.edits_live ? '<br><i class="bi bi-clock-history me-1"></i>เทียบจากต้นฉบับล่าสุดของทั้งสองฉบับ '
        + '(ผลตรวจนี้บันทึกไว้ก่อนระบบจะเก็บผลเทียบให้)' : ''}
    </div>
  </div>`;
}

/**
 * กล่อง "ฉบับนี้ต่างจากฉบับตั้งต้นอย่างไร"
 * เทียบ "คนละฉบับ" ตามคู่ที่ครูกำหนด เพื่อตอบคำถามเดียวว่า "คะแนนดีขึ้นจริงไหม และต่างกันตรงไหน"
 */
function aiDraftCompareHTML(fb, opts) {
  opts = opts || {};
  const d = fb.draft_compare;
  if (!d || !d.pairable) return '';

  // มีคู่ให้เทียบ แต่ฉบับตั้งต้นยังไม่ถูก AI ตรวจ → บอกครูว่าต้องตรวจฉบับนั้นก่อนจึงจะเทียบได้
  // (โหมดย่อในหน้าเขียนเรียงความไม่ต้องขึ้น เพราะนักเรียนสั่งตรวจเองไม่ได้อยู่แล้ว)
  if (!d.has_baseline) {
    if (opts.compact) return '';
    return `<div class="ai-draft-box ai-draft-box-wait rounded-3 p-3 mb-3">
      <div class="fw-bold text-dark"><i class="bi bi-arrow-left-right text-secondary me-2"></i>ยังเทียบกับ ${aiEsc(d.label || '')} ไม่ได้</div>
      <div class="small text-muted mt-1">
        รอบนี้ต้องเทียบกับ <strong>${aiEsc(d.label || '')}</strong> เสมอ
        แต่ฉบับนั้นยังไม่มีผลตรวจของ AI — ให้ AI ตรวจฉบับตั้งต้นก่อน แล้วสั่งตรวจรอบนี้ใหม่อีกครั้ง
        ระบบจะเทียบคะแนนรายข้อให้เอง
      </div>
      ${fb.draft_comment ? `<div class="ai-progress-comment mt-2">
        <span class="fw-semibold">AI เทียบจากตัวข้อความไว้ว่า:</span> ${aiEsc(fb.draft_comment)}</div>` : ''}
    </div>`;
  }

  const up = d.delta > 0, flat = (d.delta === 0);
  const headCls = up ? 'ai-draft-box-up' : (flat ? 'ai-draft-box-flat' : 'ai-draft-box-down');

  // คำตัดสินบรรทัดเดียวที่ครูอ่านแล้วรู้ทันทีว่า "ผ่าน" เกณฑ์ที่ตั้งไว้ไหม (ร่างหลังต้องดีขึ้นและต้องต่าง)
  const verdict = up
    ? `<span class="ai-draft-verdict ai-draft-verdict-ok"><i class="bi bi-check-circle-fill me-1"></i>คะแนนดีขึ้นจาก ${aiEsc(d.short)} อยู่ ${aiFmt(Math.abs(d.delta))} คะแนน</span>`
    : (flat
        ? `<span class="ai-draft-verdict ai-draft-verdict-warn"><i class="bi bi-exclamation-triangle-fill me-1"></i>คะแนนเท่ากับ ${aiEsc(d.short)} — ยังไม่ดีขึ้น</span>`
        : `<span class="ai-draft-verdict ai-draft-verdict-bad"><i class="bi bi-arrow-down-circle-fill me-1"></i>คะแนนต่ำกว่า ${aiEsc(d.short)} อยู่ ${aiFmt(Math.abs(d.delta))} คะแนน</span>`);

  // ธงเตือนเมื่อผลออกมา "ผิดจากที่ควรเป็น" — ครูจะได้รู้ว่าควรกลับไปดูงานจริง
  const flags = [];
  if (d.same_text) {
    flags.push(`<div class="ai-draft-flag ai-draft-flag-bad"><i class="bi bi-files me-1"></i>
      <strong>ข้อความเหมือนฉบับ ${aiEsc(d.short)} ทุกตัวอักษร</strong> — นักเรียนยังไม่ได้แก้ไขงานเลย คะแนนจึงไม่ควรต่างกัน</div>`);
  } else if (d.identical) {
    flags.push(`<div class="ai-draft-flag ai-draft-flag-warn"><i class="bi bi-exclamation-triangle me-1"></i>
      <strong>คะแนนรายข้อเท่ากันทุกข้อ</strong> — งานเปลี่ยนไปแล้วแต่ยังไม่ถึงระดับถัดไปสักข้อ
      ลองอ่านคอลัมน์ &quot;ต้องแก้อะไรจึงจะขยับขึ้น&quot; ด้านล่างประกอบ</div>`);
  }
  if (!up && !d.same_text && !d.identical) {
    flags.push(`<div class="ai-draft-flag ai-draft-flag-warn"><i class="bi bi-graph-down me-1"></i>
      คะแนนรวมยัง<strong>ไม่ดีขึ้น</strong>จาก ${aiEsc(d.short)} — มีข้อที่ดีขึ้น ${d.up} ข้อ แต่ถอยลง ${d.down} ข้อ</div>`);
  }

  const chips = [
    `<span class="ai-progress-chip">
       <span class="text-muted">คะแนน AI</span>
       <span class="fw-bold">${aiFmt(d.base_total)} → ${aiFmt(d.total)}</span>
       <span class="text-muted">/ ${aiFmt(d.max_score)}</span>
       ${aiDeltaBadge(d.delta)}
     </span>`,
    (d.base_quality || d.quality)
      ? `<span class="ai-progress-chip">
           <span class="text-muted">ระดับคุณภาพ</span>
           <span class="fw-bold">${aiEsc(d.base_quality || '-')} → ${aiEsc(d.quality || '-')}</span>
           ${d.quality_changed ? '<span class="badge bg-primary-subtle text-primary-emphasis">เปลี่ยนระดับ</span>' : ''}
         </span>`
      : '',
    `<span class="ai-progress-chip">
       <span class="ai-delta ai-delta-up"><i class="bi bi-arrow-up-short"></i>ดีขึ้น ${d.up} ข้อ</span>
       <span class="ai-delta ai-delta-down"><i class="bi bi-arrow-down-short"></i>ลดลง ${d.down} ข้อ</span>
       <span class="ai-delta ai-delta-same"><i class="bi bi-dash"></i>เท่าเดิม ${d.same} ข้อ</span>
     </span>`,
    (d.base_words || d.words)
      ? `<span class="ai-progress-chip"><span class="text-muted">จำนวนคำ</span>
           <span class="fw-bold">${d.base_words || '-'} → ${d.words || '-'}</span></span>`
      : ''
  ].filter(Boolean).join('');

  /* ตารางเทียบรายเกณฑ์ — ออกแบบให้ "อ่านจบได้ในแถวเดียว"
     แต่ละแถวตอบ 4 เรื่องเรียงกัน: ข้อนี้คือเกณฑ์อะไร · คะแนนจากเท่าไรเป็นเท่าไร ·
     อะไรเพิ่มเข้ามา/อะไรหายไป · เพราะอะไรคะแนนจึงขยับหรือไม่ขยับ */
  let table = '';
  if (!opts.compact && (d.criteria || []).length) {
    // ผลตรวจเก่าที่ AI ยังไม่ได้อธิบายรายข้อ — บอกครั้งเดียวเหนือตาราง ไม่ต้องย้ำทุกแถว
    const hasWhy = (d.criteria || []).some(c => c.reason || c.change || c.added || c.removed || c.before || c.after);
    const noWhyNote = hasWhy ? '' : `<div class="ai-draft-note mb-2">
      <i class="bi bi-info-circle me-1"></i><span class="fw-semibold">ยังไม่มีคำอธิบายรายข้อจาก AI ในรอบนี้</span>
      — ตารางจึงบอกได้เฉพาะคะแนนที่ขยับ สั่งให้ AI ตรวจรอบนี้ใหม่
      เพื่อให้ AI บอกว่าแต่ละข้อมีอะไรเพิ่มเข้ามา อะไรหายไป และทำไมคะแนนจึงเปลี่ยน
    </div>`;

    const rows = d.criteria.map(c => {
      const rowCls = c.dir === 'up' ? 'ai-draft-row-up' : (c.dir === 'down' ? 'ai-draft-row-down' : '');
      const lost   = Math.round((c.max - c.weighted) * 100) / 100;

      // ประโยคบอกที่มาที่ไปของคะแนนข้อนี้ อ่านได้โดยไม่ต้องเหลือบไปดูคอลัมน์อื่น
      const move = c.dir === 'same'
        ? `ได้ <span class="fw-bold">${aiFmt(c.weighted)}</span> คะแนนเท่าเดิมทั้งสองฉบับ`
        : `จาก <span class="fw-bold">${aiFmt(c.base_weighted)}</span> คะแนน `
          + `เป็น <span class="fw-bold">${aiFmt(c.weighted)}</span> คะแนน`;

      // อะไรเพิ่มเข้ามา / อะไรหายไป — ใช้ของใหม่ก่อน ถ้าไม่มีค่อยถอยไปใช้ before/after แบบเดิม
      const addTxt = c.added   || c.after;
      const remTxt = c.removed || c.before;
      const gain = addTxt ? `<div class="ai-why-line ai-why-add">
          <span class="ai-why-tag ai-why-tag-add">เพิ่มเข้ามา</span>${aiEsc(addTxt)}</div>` : '';
      const loss = remTxt ? `<div class="ai-why-line ai-why-rem">
          <span class="ai-why-tag ai-why-tag-rem">หายไป / ของเดิม</span>${aiEsc(remTxt)}</div>` : '';

      // เหตุผลว่าทำไมคะแนนถึงขยับ (หรือไม่ขยับ)
      const whyTxt = c.reason || c.change;
      const whyLabel = c.dir === 'up' ? 'ทำไมคะแนนถึงสูงขึ้น'
                     : (c.dir === 'down' ? 'ทำไมคะแนนถึงต่ำลง' : 'ทำไมคะแนนถึงเท่าเดิม');
      const why = whyTxt ? `<div class="ai-why-reason">
          <i class="bi bi-arrow-return-right me-1"></i>
          <span class="fw-semibold">${whyLabel}:</span> ${aiEsc(whyTxt)}</div>` : '';

      return `<tr class="${rowCls}">
        <td class="text-nowrap fw-semibold align-top">${aiEsc(c.id)}</td>
        <td class="align-top">
          <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
            <span class="fw-bold text-dark">${aiEsc(c.name || '')}</span>
            ${aiVerdictBadge(c.verdict) || aiDirBadge(c.dir)}
          </div>
          <div class="ai-why-move mt-1">${move}
            <span class="text-muted">จากเต็ม ${aiFmt(c.max)} คะแนน</span>
            ${lost > 0 ? `<span class="text-muted">· ยังเสียอยู่ ${aiFmt(lost)} คะแนน</span>` : ''}
          </div>
          ${gain}${loss}${why}
          ${c.note ? `<div class="ai-draft-note mt-1"><i class="bi bi-lightbulb me-1"></i>
            <span class="fw-semibold">ทำอย่างไรให้ขยับขึ้นอีก:</span> ${aiEsc(c.note)}</div>` : ''}
        </td>
        <td class="text-center align-top" style="min-width:104px;">
          <div class="text-muted small text-nowrap">${aiEsc(d.short)} ${aiFmt(c.base_weighted)}</div>
          <div class="fw-bold text-nowrap" style="font-size:1.05rem;">${aiFmt(c.weighted)}
            <span class="text-muted fw-normal small">/ ${aiFmt(c.max)}</span></div>
          <div class="mt-1">${aiDeltaBadge(c.delta, { unit: false })}</div>
        </td>
      </tr>`;
    }).join('');

    table = `${noWhyNote}<div class="table-responsive mt-2">
      <table class="table table-sm table-bordered align-middle mb-0 bg-white ai-why-table">
        <thead class="table-light">
          <tr><th style="width:52px;">ข้อ</th>
              <th>เกณฑ์ · คะแนนเปลี่ยนจากเท่าไรเป็นเท่าไร และเพราะอะไร</th>
              <th class="text-center" style="width:118px;">คะแนน</th></tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>`;
  }

  const when = d.reviewed_at ? String(d.reviewed_at).replace('T', ' ').slice(0, 16) : '';

  return `<div class="ai-draft-box ${headCls} rounded-3 p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
      <span class="fw-bold text-dark">
        <i class="bi bi-arrow-left-right text-primary me-2"></i>เทียบกับ ${aiEsc(d.label || '')}
      </span>
      ${when ? `<span class="text-muted small">ฉบับตั้งต้นตรวจเมื่อ ${aiEsc(when)}</span>` : ''}
    </div>
    <div class="mb-2">${verdict}</div>
    ${flags.join('')}
    <div class="d-flex flex-wrap gap-2 mt-2">${chips}</div>
    ${d.comment ? `<div class="ai-progress-comment mt-2">${aiEsc(d.comment)}</div>` : ''}
    ${opts.compact ? '' : aiEditListHTML(d)}
    ${table}
    <div class="text-muted mt-2" style="font-size:0.75rem;">
      <i class="bi bi-info-circle me-1"></i>ส่วนต่างคำนวณจากคะแนนที่ AI ให้จริงทั้งสองฉบับ
      ไม่ได้เชื่อคำบรรยายของ AI อย่างเดียว
      ${d.estimated ? '<br><i class="bi bi-lightbulb me-1"></i>ผลตรวจฉบับนี้บันทึกไว้ก่อนระบบจะเทียบร่างให้อัตโนมัติ '
        + 'จึงเทียบได้เฉพาะคะแนน — สั่งให้ AI ตรวจรอบนี้ใหม่ เพื่อให้ AI ยกข้อความของทั้งสองฉบับมาวางคู่กันให้เห็น' : ''}
    </div>
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
    return `<div class="ai-improve-card rounded-3 mb-3 overflow-hidden">
      <div class="ai-improve-head d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-bold text-dark">
          <span class="badge bg-warning text-dark me-1">จุดที่ ${i + 1}</span>
          ${it.criterion ? `ข้อ ${aiEsc(it.criterion)} ${aiEsc(critName)}` : 'ภาพรวมของงานเขียน'}
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
                <th class="text-center" style="width:110px;">คะแนน</th>
                <th style="width:130px;">สัดส่วน</th></tr>
          </thead>
          <tbody>${critRows || '<tr><td colspan="4" class="text-muted text-center">— ไม่มีข้อมูลคะแนน —</td></tr>'}${manualRows}</tbody>
          <tfoot class="table-light">
            <tr class="fw-bold">
              <td colspan="2" class="text-end">คะแนนรวมตามเกณฑ์ของครู</td>
              <td class="text-center text-nowrap">${combined} <span class="text-muted fw-normal">/ ${fullMax}</span></td>
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

    ${aiDraftCompareHTML(fb, opts)}

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
