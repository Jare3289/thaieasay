/*
 * thai_review.js — หน้าต่างตรวจสอบเรียงความแบบเต็มหน้า (ใช้ร่วมกันโดย essay_writer.php, essay_viewer.php, essay_print.php)
 *
 * แสดงเรียงความทั้งฉบับ (คำนำ + เนื้อเรื่องทุกย่อหน้า + สรุป ต่อเนื่องกันเหมือนอ่านจริง) พร้อมไฮไลต์
 * คำที่ระบบตรวจพบว่า "อาจสะกดผิด" (เทียบกับพจนานุกรมคำไทย) และคำที่ "เขียนด้วยภาษาอื่นปนอยู่"
 * (ดู thai_text_utils.php ฝั่งเซิร์ฟเวอร์) คลิกคำที่ไฮไลต์เพื่อ "✓ ยืนยันว่าถูกต้อง" (จะไม่ถูกฟ้อง
 * อีกทั้งระบบ) "✏️ แก้คำ" (แก้ตรงจุดแล้วบันทึก) หรือ "🗑️ ลบ" (ลบคำนั้นออกจากเนื้อหาเลย)
 */
(function (window, document) {
  'use strict';

  let segmenter = null;
  if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
    try { segmenter = new Intl.Segmenter('th', { granularity: 'word' }); } catch (e) { segmenter = null; }
  }

  // อนุโลมให้ "เ" สองตัวติดกัน (เ + เ) แทน "แ" ได้ก่อนตัดคำ (คนละอักขระ แต่หน้าตาเหมือนกันมาก
  // เป็นการพิมพ์ผิดที่พบบ่อย) มิฉะนั้นตัวตัดคำจะสับสนและตัดขอบเขตคำผิดเพี้ยนไปทั้งประโยค
  function segmentText(text) {
    text = text.replace(/เเ/g, 'แ');
    if (segmenter) {
      const out = [];
      for (const part of segmenter.segment(text)) {
        out.push({ text: part.segment, isWord: part.isWordLike });
      }
      return out;
    }
    // fallback: แยกด้วยช่องว่าง
    return text.split(/(\s+)/).filter(s => s !== '').map(s => ({ text: s, isWord: /\S/.test(s) }));
  }

  async function postJSON(action, payload) {
    const res = await fetch('api.php?action=' + action, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload || {})
    });
    return res.json();
  }

  // สีไฮไลต์คำที่น่าสงสัย 3 ประเภท — ฝังทันทีตอนโหลดสคริปต์ (ไม่รอเปิดหน้าต่างตรวจสอบ) เพื่อให้
  // renderStaticHTML() ใช้แสดงผลนอกหน้าต่างตรวจสอบได้เลย (เช่น ตัวอย่างแบบเรียลไทม์ใน essay_writer.php)
  const staticStyle = document.createElement('style');
  staticStyle.textContent = `
    .trw-static-flag { border-radius: 3px; padding: 0 1px; }
    .trw-static-flag.trw-kind-misspell { background: #fff2a8; border-bottom: 2px dotted #d99a00; }
    .trw-static-flag.trw-kind-foreign { background: #e0d6ff; border-bottom: 2px dotted #7c4dff; }
    .trw-static-flag.trw-kind-spacing { background: #ffd8c2; border-bottom: 2px dotted #e8590c; }
  `;
  document.head.appendChild(staticStyle);

  let modalEl = null;
  let state = null; // { paragraphs, misspelledSet, foreignSet, spacingSet, dirty, onSave }

  function ensureModal() {
    if (modalEl) return modalEl;
    modalEl = document.createElement('div');
    modalEl.id = 'thaiReviewModal';
    modalEl.innerHTML = `
      <div class="trw-backdrop"></div>
      <div class="trw-dialog" role="dialog" aria-modal="true">
        <div class="trw-header">
          <div class="trw-title"><i class="bi bi-search"></i> ตรวจสอบการสะกดคำทั้งหน้า</div>
          <button type="button" class="trw-close" aria-label="ปิด">&times;</button>
        </div>
        <div class="trw-note">
          <span class="trw-legend"><span class="trw-swatch trw-swatch-misspell"></span>อาจสะกดผิด</span>
          <span class="trw-legend"><span class="trw-swatch trw-swatch-foreign"></span>เขียนด้วยภาษาอื่นปนอยู่</span>
          <span class="trw-legend"><span class="trw-swatch trw-swatch-spacing"></span>เว้นวรรครอบ "ๆ" ไม่ถูก</span>
          — คลิกคำที่ไฮไลต์เพื่อยืนยันว่าถูกต้อง แก้ไข หรือลบ ระบบตรวจเทียบกับพจนานุกรมเท่านั้น
          ชื่อเฉพาะ คำสแลง หรือศัพท์เฉพาะทางที่ถูกต้องอยู่แล้วอาจถูกไฮไลต์ด้วย กด "ถูกต้อง" เพื่อไม่ให้ฟ้องซ้ำอีก
        </div>
        <div class="trw-status"></div>
        <div class="trw-body"></div>
        <div class="trw-footer">
          <span class="trw-save-msg"></span>
          <button type="button" class="trw-btn trw-btn-secondary trw-cancel">ปิด</button>
          <button type="button" class="trw-btn trw-btn-primary trw-save" disabled>บันทึกการแก้ไข</button>
        </div>
      </div>`;
    document.body.appendChild(modalEl);

    const style = document.createElement('style');
    style.textContent = `
      #thaiReviewModal { position: fixed; inset: 0; z-index: 2000; display: none; }
      #thaiReviewModal.open { display: block; }
      #thaiReviewModal .trw-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.5); }
      #thaiReviewModal .trw-dialog {
        position: relative; max-width: 760px; margin: 4vh auto; background: #fff; border-radius: 12px;
        max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,.3);
        font-family: "TH Sarabun PSK", "THSarabunPSK", "TH SarabunPSK", "TH Sarabun New", "Sarabun", "Leelawadee UI", "Tahoma", sans-serif;
      }
      #thaiReviewModal .trw-header { display:flex; align-items:center; justify-content:space-between; padding: 14px 20px; border-bottom: 1px solid #eee; }
      #thaiReviewModal .trw-title { font-weight: 700; font-size: 1.1rem; }
      #thaiReviewModal .trw-close { background: none; border: none; font-size: 1.6rem; line-height: 1; cursor: pointer; color: #888; }
      #thaiReviewModal .trw-note { padding: 10px 20px; font-size: .85rem; color: #6c5300; background: #fff9e6; border-bottom: 1px solid #f5e6a8; }
      #thaiReviewModal .trw-legend { display: inline-flex; align-items: center; gap: 4px; margin-right: 10px; font-weight: 600; }
      #thaiReviewModal .trw-swatch { display: inline-block; width: 12px; height: 12px; border-radius: 3px; }
      #thaiReviewModal .trw-swatch-misspell { background: #fff2a8; border-bottom: 2px solid #d99a00; }
      #thaiReviewModal .trw-swatch-foreign { background: #e0d6ff; border-bottom: 2px solid #7c4dff; }
      #thaiReviewModal .trw-swatch-spacing { background: #ffd8c2; border-bottom: 2px solid #e8590c; }
      #thaiReviewModal .trw-status { padding: 8px 20px 0; font-size: .9rem; color: #444; }
      #thaiReviewModal .trw-body { padding: 12px 20px 20px; overflow-y: auto; line-height: 2; font-size: 1rem; white-space: pre-wrap; flex: 1; }
      #thaiReviewModal .trw-para { margin: 0 0 14px; }
      #thaiReviewModal .trw-flag { cursor: pointer; border-radius: 3px; padding: 0 1px; }
      #thaiReviewModal .trw-flag.trw-kind-misspell { background: #fff2a8; border-bottom: 2px dotted #d99a00; }
      #thaiReviewModal .trw-flag.trw-kind-foreign { background: #e0d6ff; border-bottom: 2px dotted #7c4dff; }
      #thaiReviewModal .trw-flag.trw-kind-spacing { background: #ffd8c2; border-bottom: 2px dotted #e8590c; }
      #thaiReviewModal .trw-flag.trw-editing { background: transparent; }
      #thaiReviewModal .trw-flag input { font: inherit; width: 8em; border: 1px solid #999; border-radius: 4px; padding: 0 4px; }
      #thaiReviewModal .trw-actions { display: inline-flex; gap: 4px; margin-left: 4px; white-space: nowrap; }
      #thaiReviewModal .trw-actions button { font-size: .78rem; border: none; border-radius: 6px; padding: 2px 8px; cursor: pointer; }
      #thaiReviewModal .trw-act-fixspacing { background: #ffe4cf; color: #a03e00; font-weight: 600; }
      #thaiReviewModal .trw-act-confirm { background: #d4edda; color: #155724; }
      #thaiReviewModal .trw-act-edit { background: #cfe2ff; color: #084298; }
      #thaiReviewModal .trw-act-delete { background: #f8d7da; color: #842029; }
      #thaiReviewModal .trw-act-cancel { background: #eee; color: #555; }
      #thaiReviewModal .trw-footer { display:flex; align-items:center; justify-content:flex-end; gap: 10px; padding: 12px 20px; border-top: 1px solid #eee; }
      #thaiReviewModal .trw-save-msg { margin-right: auto; font-size: .85rem; color: #157347; }
      #thaiReviewModal .trw-btn { border: none; border-radius: 8px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
      #thaiReviewModal .trw-btn-secondary { background: #eee; color: #333; }
      #thaiReviewModal .trw-btn-primary { background: #0d7377; color: #fff; }
      #thaiReviewModal .trw-btn-primary:disabled { background: #a9c9ca; cursor: default; }
    `;
    document.head.appendChild(style);

    modalEl.querySelector('.trw-backdrop').addEventListener('click', closeModal);
    modalEl.querySelector('.trw-close').addEventListener('click', closeModal);
    modalEl.querySelector('.trw-cancel').addEventListener('click', closeModal);
    modalEl.querySelector('.trw-save').addEventListener('click', saveEdits);

    return modalEl;
  }

  function closeModal() {
    if (!modalEl) return;
    if (state && state.dirty) {
      if (!window.confirm('มีการแก้ไขที่ยังไม่ได้บันทึก ต้องการปิดหน้าต่างนี้เลยหรือไม่?')) return;
    }
    modalEl.classList.remove('open');
    state = null;
  }

  function updateStatus() {
    const statusEl = modalEl.querySelector('.trw-status');
    const nMis = state.misspelledSet.size;
    const nForeign = state.foreignSet.size;
    const nSpacing = state.spacingSet.size;
    const parts = [];
    if (nMis > 0) parts.push(`อาจสะกดผิด ${nMis} คำ`);
    if (nForeign > 0) parts.push(`เขียนด้วยภาษาอื่นปน ${nForeign} คำ`);
    if (nSpacing > 0) parts.push(`เว้นวรรครอบ "ๆ" ไม่ถูก ${nSpacing} คำ`);
    statusEl.textContent = parts.length > 0
      ? `พบคำที่น่าสงสัย — ${parts.join(' · ')} (ไม่ซ้ำ) — คลิกคำที่ไฮไลต์เพื่อตรวจสอบ`
      : 'ไม่พบคำที่น่าสงสัยแล้วในตอนนี้';
    modalEl.querySelector('.trw-save').disabled = !state.dirty;
  }

  // จัดประเภทคำ ('misspell' | 'foreign' | 'spacing' | null) ตามชุดคำที่น่าสงสัยปัจจุบัน
  function classifyWord(word, sets) {
    if (sets.misspelledSet.has(word)) return 'misspell';
    if (sets.foreignSet.has(word)) return 'foreign';
    if (sets.spacingSet.has(word)) return 'spacing';
    return null;
  }

  function renderParagraph(container, text) {
    container.textContent = '';
    segmentText(text).forEach((seg) => {
      const kind = seg.isWord ? classifyWord(seg.text, state) : null;
      if (kind) {
        const span = document.createElement('span');
        span.className = 'trw-flag trw-kind-' + kind;
        span.textContent = seg.text;
        span.dataset.word = seg.text;
        span.dataset.kind = kind;
        span.dataset.uid = 'w' + (uidCounter++);
        span.addEventListener('click', onFlagClick);
        container.appendChild(span);
      } else {
        container.appendChild(document.createTextNode(seg.text));
      }
    });
  }

  let uidCounter = 0;

  function onFlagClick(ev) {
    const span = ev.currentTarget;
    if (span.classList.contains('trw-editing')) return;
    // ปิดกล่องตัวเลือกที่เปิดค้างไว้จุดอื่นก่อน (เปิดได้ทีละจุด)
    modalEl.querySelectorAll('.trw-actions').forEach(el => el.remove());

    const actions = document.createElement('span');
    actions.className = 'trw-actions';
    actions.dataset.for = span.dataset.uid;
    const isSpacing = span.dataset.kind === 'spacing';
    actions.innerHTML = `
      ${isSpacing ? '<button type="button" class="trw-act-fixspacing">🔧 เว้นวรรคให้ถูก</button>' : ''}
      <button type="button" class="trw-act-confirm">✓ ถูกต้อง</button>
      <button type="button" class="trw-act-edit">✏️ แก้คำ</button>
      <button type="button" class="trw-act-delete">🗑️ ลบ</button>
      <button type="button" class="trw-act-cancel">✕</button>`;
    span.after(actions);

    actions.querySelector('.trw-act-cancel').addEventListener('click', () => actions.remove());
    actions.querySelector('.trw-act-confirm').addEventListener('click', () => confirmWord(span, actions));
    actions.querySelector('.trw-act-edit').addEventListener('click', () => editWord(span, actions));
    actions.querySelector('.trw-act-delete').addEventListener('click', () => deleteWord(span, actions));
    if (isSpacing) {
      actions.querySelector('.trw-act-fixspacing').addEventListener('click', () => fixSpacing(span, actions));
    }
  }

  // ลบคำนี้ออกจากคำที่น่าสงสัยทุกรายการ (ยืนยัน/แก้ไขแล้วจะไม่ถูกฟ้องคำนี้อีก)
  function forgetWord(word) {
    state.misspelledSet.delete(word);
    state.foreignSet.delete(word);
    state.spacingSet.delete(word);
  }

  async function confirmWord(span, actions) {
    const word = span.dataset.word;
    actions.remove();
    try {
      const res = await postJSON('confirm_thai_word', { word });
      if (!res || !res.success) throw new Error(res && res.error);
    } catch (e) {
      alert('ยืนยันคำไม่สำเร็จ ลองใหม่อีกครั้ง');
      return;
    }
    forgetWord(word);
    modalEl.querySelectorAll('.trw-flag[data-word="' + attrEscape(word) + '"]').forEach(el => {
      el.replaceWith(document.createTextNode(el.textContent));
    });
    updateStatus();
  }

  // escape สำหรับฝังในค่า attribute selector ที่ครอบด้วยเครื่องหมายคำพูดคู่ เช่น [data-word="..."]
  function attrEscape(s) {
    return s.replace(/["\\]/g, '\\$&');
  }

  function editWord(span, actions) {
    actions.remove();
    span.classList.add('trw-editing');
    const original = span.textContent;
    const originalWord = span.dataset.word; // เก็บไว้ก่อนแก้ เผื่อคำเดียวกันนี้ปรากฏหลายจุดในเรียงความ
    span.textContent = '';
    const input = document.createElement('input');
    input.type = 'text';
    input.value = original;
    span.appendChild(input);
    input.focus();
    input.select();

    let finished = false;
    const finish = (commit) => {
      if (finished) return;
      finished = true;
      // ไม่ใช้ .trim() กับค่าที่จะนำไปแทนที่ เพราะบางกรณี (เช่นแก้เว้นวรรครอบ "ๆ") ผู้ใช้ตั้งใจ
      // เคาะช่องว่างไว้หน้า/หลังคำ — trim() จะลบช่องว่างที่ตั้งใจพิมพ์นั้นทิ้งไปเสีย
      const raw = commit ? input.value : original;
      const isBlank = raw.trim() === '';
      if (commit && !isBlank && raw !== original) {
        forgetWord(originalWord);
        // แก้ "ทุกจุด" ที่เป็นคำเดียวกันในเรียงความทั้งฉบับ ไม่ใช่แค่จุดที่คลิก
        modalEl.querySelectorAll('.trw-flag[data-word="' + attrEscape(originalWord) + '"]').forEach(el => {
          el.replaceWith(document.createTextNode(raw));
        });
        state.dirty = true;
      } else {
        span.classList.remove('trw-editing');
        span.textContent = isBlank ? original : raw;
      }
      updateStatus();
    };

    input.addEventListener('keydown', (ev) => {
      if (ev.key === 'Enter') { ev.preventDefault(); finish(true); }
      else if (ev.key === 'Escape') { ev.preventDefault(); finish(false); }
    });
    input.addEventListener('blur', () => finish(true));
  }

  // แก้เว้นวรรครอบ "ๆ" ให้ถูกต้องแบบอัตโนมัติ (ไม่พึ่งให้ผู้ใช้พิมพ์เว้นวรรคเอง เพราะจุดที่ต้องเว้น
  // วรรค "หลัง" ๆ อยู่นอกขอบเขตคำที่ถูกไฮไลต์ — พิมพ์แก้ในช่องแก้คำธรรมดาจะเติมได้แค่ฝั่งหน้าเท่านั้น)
  // เว้นวรรคให้ทั้งสองด้านเสมอ ทำกับทุกจุดที่เป็นคำเดียวกันในเรียงความทั้งฉบับ
  function fixSpacing(span, actions) {
    actions.remove();
    const word = span.dataset.word;
    const stem = (word !== 'ๆ' && word.slice(-1) === 'ๆ') ? word.slice(0, -1) : '';
    modalEl.querySelectorAll('.trw-flag[data-word="' + attrEscape(word) + '"]').forEach((el) => {
      const prev = el.previousSibling;
      const next = el.nextSibling;
      const frag = document.createDocumentFragment();
      if (stem) {
        // ตัดคำที่ซ้ำ (stem) กับ "ๆ" ออกจากกัน แล้วแทรกช่องว่างคั่นกลาง
        frag.appendChild(document.createTextNode(stem + ' '));
      } else if (prev && prev.nodeType === Node.TEXT_NODE && !/[ \t]$/.test(prev.textContent)) {
        prev.textContent += ' ';
      }
      frag.appendChild(document.createTextNode('ๆ'));
      el.replaceWith(frag);
      if (next) {
        if (next.nodeType === Node.TEXT_NODE) {
          if (!/^[ \t]/.test(next.textContent)) {
            next.textContent = ' ' + next.textContent;
          }
        } else {
          next.before(document.createTextNode(' '));
        }
      }
    });
    forgetWord(word);
    state.dirty = true;
    updateStatus();
  }

  // ลบคำนี้ออกจากเนื้อหาเลย (เช่น คำซ้ำ/คำที่ไม่ควรอยู่) พร้อมยุบช่องว่างซ้ำที่อาจเกิดขึ้น
  function deleteWord(span, actions) {
    actions.remove();
    const word = span.dataset.word;
    const prev = span.previousSibling;
    const next = span.nextSibling;
    span.remove();
    // เช่น "กิน game มาก" ลบ "game" แล้วเหลือช่องว่างสองด้านติดกัน → ยุบเหลือช่องเดียว
    if (prev && next && prev.nodeType === Node.TEXT_NODE && next.nodeType === Node.TEXT_NODE) {
      if (/[ \t]$/.test(prev.textContent) && /^[ \t]/.test(next.textContent)) {
        next.textContent = next.textContent.replace(/^[ \t]+/, '');
      }
    }
    forgetWord(word);
    state.dirty = true;
    updateStatus();
  }

  async function saveEdits() {
    const saveBtn = modalEl.querySelector('.trw-save');
    const msgEl = modalEl.querySelector('.trw-save-msg');
    saveBtn.disabled = true;
    msgEl.textContent = 'กำลังบันทึก...';

    // ปิดกล่องตัวเลือก (✓ ถูกต้อง / ✏️ แก้คำ / 🗑️ ลบ / ✕) ที่อาจเปิดค้างไว้ก่อนอ่านเนื้อหา
    // มิฉะนั้นข้อความปุ่มเหล่านี้จะติดปนไปกับเนื้อหาที่บันทึกจริง
    modalEl.querySelectorAll('.trw-actions').forEach(el => el.remove());

    const paragraphEls = Array.from(modalEl.querySelectorAll('.trw-para'));
    const paragraphs = state.paragraphs.map((p, i) => ({ label: p.label, text: paragraphEls[i].textContent }));

    try {
      await state.onSave(paragraphs);
    } catch (e) {
      msgEl.textContent = '';
      alert('บันทึกไม่สำเร็จ: ' + (e && e.message ? e.message : 'เกิดข้อผิดพลาด'));
      saveBtn.disabled = false;
      return;
    }

    state.paragraphs = paragraphs;
    state.dirty = false;

    // ตรวจคำผิดซ้ำอีกครั้งด้วยข้อความที่แก้ไขแล้ว เผื่อการแก้คำสร้างคำที่น่าสงสัยใหม่ขึ้นมา
    try {
      const combined = paragraphs.map(p => p.text).join('\n');
      const res = await postJSON('check_thai_spelling', { text: combined });
      state.misspelledSet = new Set(res && res.success ? res.misspelled : []);
      state.foreignSet = new Set(res && res.success ? res.foreign : []);
      state.spacingSet = new Set(res && res.success ? res.spacing : []);
    } catch (e) { /* เงียบไว้ — คงรายการเดิมถ้าตรวจซ้ำไม่สำเร็จ */ }

    renderAll();
    msgEl.textContent = '✓ บันทึกแล้ว';
    setTimeout(() => { if (msgEl) msgEl.textContent = ''; }, 3000);
  }

  function renderAll() {
    const body = modalEl.querySelector('.trw-body');
    body.textContent = '';
    state.paragraphs.forEach(p => {
      const container = document.createElement('div');
      container.className = 'trw-para';
      body.appendChild(container);
      renderParagraph(container, p.text);
    });
    updateStatus();
  }

  // เปิดหน้าต่างตรวจสอบ
  // options.paragraphs : [{ label, text }]  ข้อความเรียงความทั้งฉบับ เรียงตามลำดับ (คำนำ/เนื้อเรื่อง.../สรุป)
  // options.misspelled  : string[]  คำที่ตรวจพบว่าอาจสะกดผิด (จาก api.php?action=check_thai_spelling)
  // options.foreign      : string[]  คำที่เขียนด้วยภาษาอื่นปนอยู่ (จาก api.php?action=check_thai_spelling)
  // options.spacing      : string[]  คำที่เว้นวรรครอบ "ๆ" ไม่ถูก (จาก api.php?action=check_thai_spelling)
  // options.onSave(paragraphs) : async function — รับ paragraphs ที่แก้ไขแล้ว ไปบันทึกกลับ (throw หากบันทึกไม่สำเร็จ)
  function open(options) {
    ensureModal();
    state = {
      paragraphs: (options.paragraphs || []).filter(p => p.text && p.text.trim() !== ''),
      misspelledSet: new Set(options.misspelled || []),
      foreignSet: new Set(options.foreign || []),
      spacingSet: new Set(options.spacing || []),
      dirty: false,
      onSave: options.onSave || (async () => {})
    };
    renderAll();
    modalEl.classList.add('open');
  }

  // สร้าง HTML แบบอ่านอย่างเดียว (ไม่มีปุ่มโต้ตอบ) พร้อมไฮไลต์คำที่น่าสงสัย — ใช้แสดงตัวอย่างแบบเรียลไทม์
  // หรือรายการ "บันทึกไว้แล้ว" โดยไม่ต้องเปิดหน้าต่างตรวจสอบ (ดู essay_writer.php)
  // sets: { misspelled, foreign, spacing } เป็น array ของคำ — wrapClass ใช้ครอบ <p> แต่ละย่อหน้า (ใส่ null ได้ถ้าไม่ต้องการ)
  function renderStaticHTML(text, sets) {
    const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const wrapSets = {
      misspelledSet: new Set(sets.misspelled || []),
      foreignSet: new Set(sets.foreign || []),
      spacingSet: new Set(sets.spacing || [])
    };
    let html = '';
    segmentText(text).forEach(seg => {
      const e = esc(seg.text).replace(/\n/g, '<br>');
      const kind = seg.isWord ? classifyWord(seg.text, wrapSets) : null;
      html += kind ? `<span class="thai-word trw-static-flag trw-kind-${kind}">${e}</span>`
                   : (seg.isWord ? `<span class="thai-word">${e}</span>` : e);
    });
    return html;
  }

  window.ThaiReview = { open, segmentText, renderStaticHTML };
})(window, document);
