/*
 * thai_review.js — หน้าต่างตรวจสอบเรียงความแบบเต็มหน้า (ใช้ร่วมกันโดย essay_writer.php, essay_viewer.php, essay_print.php)
 *
 * แสดงเรียงความทั้งฉบับ (คำนำ + เนื้อเรื่องทุกย่อหน้า + สรุป ต่อเนื่องกันเหมือนอ่านจริง) พร้อมไฮไลต์
 * คำที่ระบบตรวจพบว่า "อาจสะกดผิด" (เทียบกับพจนานุกรมคำไทย) และคำที่ "เขียนด้วยภาษาอื่นปนอยู่"
 * (ดู thai_text_utils.php ฝั่งเซิร์ฟเวอร์)
 *
 * แก้ได้ "ทุกคำ" ไม่ใช่เฉพาะคำที่ถูกไฮไลต์ — คลิกคำไหนก็ได้ในหน้าต่างนี้เพื่อ
 *   "✏️ แก้ตรงนี้" (แก้เฉพาะจุดที่คลิก) · "✏️ แก้ทุกจุด" (แก้คำเดียวกันทั้งฉบับ)
 *   "🔗 รวมกับคำหน้า/คำหลัง" (กรณีตัวตัดคำแยกคำที่ถูกอยู่แล้วออกจากกันเพี้ยน ๆ)
 *   "🗑️ ลบ" (ลบคำนั้นออกจากเนื้อหา) และเฉพาะคำที่ถูกไฮไลต์จะมี "✓ ถูกต้อง" (ไม่ให้ฟ้องซ้ำอีกทั้งระบบ)
 *   กับ "🔧 เว้นวรรคให้ถูก" (เฉพาะกรณีเว้นวรรครอบ "ๆ" ไม่ถูก)
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
          — <strong>คลิกคำไหนก็ได้เพื่อแก้ไข</strong> ไม่จำเป็นต้องเป็นคำที่ไฮไลต์ (คำที่ถูกอยู่แล้วแต่ถูกแยกคำเพี้ยน
          กด "🔗 รวมกับคำหน้า/คำหลัง" ให้เป็นคำเดียวแล้วพิมพ์แก้ได้เลย) ระบบตรวจเทียบกับพจนานุกรมเท่านั้น
          ชื่อเฉพาะ คำสแลง หรือศัพท์เฉพาะทางที่ถูกต้องอยู่แล้วอาจถูกไฮไลต์ด้วย กด "✓ ถูกต้อง" เพื่อไม่ให้ฟ้องซ้ำอีก
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
      /* ทุกคำคลิกได้ — ใช้ box-shadow/มาร์กเกอร์ที่ไม่กินพื้นที่ เพื่อไม่ให้ระยะห่างตัวอักษรขยับตอนชี้ */
      #thaiReviewModal .trw-word { cursor: pointer; border-radius: 3px; }
      #thaiReviewModal .trw-word:hover { background: #e7f1ff; box-shadow: 0 0 0 1px #91b7f5; }
      #thaiReviewModal .trw-word.trw-edited { background: #d8f5e3; box-shadow: 0 0 0 1px #34a853; }
      #thaiReviewModal .trw-flag { border-radius: 3px; padding: 0 1px; }
      #thaiReviewModal .trw-flag.trw-kind-misspell { background: #fff2a8; border-bottom: 2px dotted #d99a00; }
      #thaiReviewModal .trw-flag.trw-kind-foreign { background: #e0d6ff; border-bottom: 2px dotted #7c4dff; }
      #thaiReviewModal .trw-flag.trw-kind-spacing { background: #ffd8c2; border-bottom: 2px dotted #e8590c; }
      #thaiReviewModal .trw-word.trw-editing, #thaiReviewModal .trw-word.trw-editing:hover { background: transparent; box-shadow: none; }
      #thaiReviewModal .trw-word input { font: inherit; min-width: 5em; border: 1px solid #999; border-radius: 4px; padding: 0 4px; }
      #thaiReviewModal .trw-actions { display: inline-flex; flex-wrap: wrap; gap: 4px; margin-left: 4px; vertical-align: middle; }
      #thaiReviewModal .trw-actions button { font-size: .78rem; border: none; border-radius: 6px; padding: 2px 8px; cursor: pointer; }
      #thaiReviewModal .trw-act-fixspacing { background: #ffe4cf; color: #a03e00; font-weight: 600; }
      #thaiReviewModal .trw-act-confirm { background: #d4edda; color: #155724; }
      #thaiReviewModal .trw-act-edit { background: #cfe2ff; color: #084298; }
      #thaiReviewModal .trw-act-editall { background: #e2d6ff; color: #4b2ea8; }
      #thaiReviewModal .trw-act-mergeprev, #thaiReviewModal .trw-act-mergenext { background: #d5f0ee; color: #0b5f5c; }
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
    // ผูกคลิกไว้ที่กล่องเนื้อหาแทนการผูกทีละคำ (เรียงความหนึ่งฉบับมีคำเป็นพัน — ผูกทีละคำจะหนักเครื่อง)
    modalEl.querySelector('.trw-body').addEventListener('click', onBodyClick);

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
      ? `พบคำที่น่าสงสัย — ${parts.join(' · ')} (ไม่ซ้ำ) — คลิกคำที่ไฮไลต์เพื่อตรวจสอบ หรือคลิกคำอื่นเพื่อแก้ไขได้ทุกคำ`
      : 'ไม่พบคำที่น่าสงสัยแล้วในตอนนี้ — คลิกคำใดก็ได้ถ้าต้องการแก้ไขเอง';
    modalEl.querySelector('.trw-save').disabled = !state.dirty;
  }

  // จัดประเภทคำ ('misspell' | 'foreign' | 'spacing' | null) ตามชุดคำที่น่าสงสัยปัจจุบัน
  function classifyWord(word, sets) {
    if (sets.misspelledSet.has(word)) return 'misspell';
    if (sets.foreignSet.has(word)) return 'foreign';
    if (sets.spacingSet.has(word)) return 'spacing';
    return null;
  }

  let uidCounter = 0;

  // ครอบ "ทุกคำ" ด้วย span ไม่ใช่เฉพาะคำที่น่าสงสัย เพื่อให้คลิกแก้ได้ทุกคำ — คำที่สะกดถูกอยู่แล้ว
  // แต่ตัวตัดคำแยกขอบเขตเพี้ยนไปนิดเดียวก็แก้/รวมคำได้ ทั้งที่ระบบไม่ได้ฟ้องว่าผิด
  function makeWordSpan(text) {
    const span = document.createElement('span');
    span.className = 'trw-word';
    span.textContent = text;
    span.dataset.word = text;
    span.dataset.uid = 'w' + (uidCounter++);
    const kind = classifyWord(text, state);
    if (kind) {
      span.classList.add('trw-flag', 'trw-kind-' + kind);
      span.dataset.kind = kind;
    }
    return span;
  }

  function renderParagraph(container, text) {
    container.textContent = '';
    segmentText(text).forEach((seg) => {
      container.appendChild(seg.isWord ? makeWordSpan(seg.text) : document.createTextNode(seg.text));
    });
  }

  // escape สำหรับฝังในค่า attribute selector ที่ครอบด้วยเครื่องหมายคำพูดคู่ เช่น [data-word="..."]
  function attrEscape(s) {
    return s.replace(/["\\]/g, '\\$&');
  }

  // ทุกจุดที่เป็นคำเดียวกันในเรียงความทั้งฉบับ
  function wordSpans(word) {
    return Array.from(modalEl.querySelectorAll('.trw-word[data-word="' + attrEscape(word) + '"]'));
  }

  // คำที่ติดกันโดยไม่มีอะไรคั่น (dir = -1 คำหน้า, 1 คำหลัง) — ใช้กับการรวมคำที่ถูกแยกผิด
  function adjacentWord(span, dir) {
    const node = dir < 0 ? span.previousSibling : span.nextSibling;
    return (node && node.nodeType === Node.ELEMENT_NODE && node.classList.contains('trw-word')) ? node : null;
  }

  // ล้างสถานะไฮไลต์ของคำ (ยืนยันว่าถูก/แก้แล้ว/รวมคำแล้ว — ยังไม่ผ่านการตรวจใหม่จึงยังไม่ฟ้อง)
  function clearFlag(span) {
    delete span.dataset.kind;
    span.classList.remove('trw-flag', 'trw-kind-misspell', 'trw-kind-foreign', 'trw-kind-spacing');
  }

  // เปลี่ยนข้อความในคำหนึ่ง ๆ (ยังคงเป็นคำที่คลิกแก้ซ้ำได้อยู่)
  function setWordText(span, text, markEdited) {
    span.textContent = text;
    span.dataset.word = text;
    clearFlag(span);
    if (markEdited) span.classList.add('trw-edited');
  }

  function onBodyClick(ev) {
    if (!ev.target.closest) return;
    if (ev.target.closest('.trw-actions')) return;   // คลิกปุ่มในกล่องตัวเลือก ไม่ใช่คลิกคำ
    const span = ev.target.closest('.trw-word');
    if (!span) return;
    if (span.classList.contains('trw-editing')) return;  // กำลังพิมพ์แก้อยู่ในช่องนี้
    openWordActions(span);
  }

  // กล่องตัวเลือกของคำที่คลิก — ปุ่มที่ขึ้นต่างกันตามบริบทของคำนั้น
  function openWordActions(span) {
    // ปิดกล่องตัวเลือกที่เปิดค้างไว้จุดอื่นก่อน (เปิดได้ทีละจุด)
    modalEl.querySelectorAll('.trw-actions').forEach(el => el.remove());

    const kind = span.dataset.kind || '';
    const sameCount = wordSpans(span.dataset.word).length;
    // หาคำข้างเคียงไว้ก่อนแทรกกล่องตัวเลือก มิฉะนั้นกล่องจะไปคั่นระหว่างคำจนหาคำหลังไม่เจอ
    const prevWord = adjacentWord(span, -1);
    const nextWord = adjacentWord(span, 1);

    const btn = (cls, label) => '<button type="button" class="' + cls + '">' + label + '</button>';
    const actions = document.createElement('span');
    actions.className = 'trw-actions';
    actions.dataset.for = span.dataset.uid;
    actions.innerHTML = [
      kind === 'spacing' ? btn('trw-act-fixspacing', '🔧 เว้นวรรคให้ถูก') : '',
      kind ? btn('trw-act-confirm', '✓ ถูกต้อง') : '',
      btn('trw-act-edit', '✏️ แก้ตรงนี้'),
      sameCount > 1 ? btn('trw-act-editall', '✏️ แก้ทุกจุด (' + sameCount + ')') : '',
      prevWord ? btn('trw-act-mergeprev', '🔗 รวมกับคำหน้า') : '',
      nextWord ? btn('trw-act-mergenext', '🔗 รวมกับคำหลัง') : '',
      btn('trw-act-delete', '🗑️ ลบ'),
      btn('trw-act-cancel', '✕')
    ].join('');
    span.after(actions);

    const close = () => actions.remove();
    const on = (cls, fn) => {
      const el = actions.querySelector('.' + cls);
      if (el) el.addEventListener('click', () => { close(); fn(); });
    };
    actions.querySelector('.trw-act-cancel').addEventListener('click', close);
    on('trw-act-fixspacing', () => fixSpacing(span));
    on('trw-act-confirm', () => confirmWord(span));
    on('trw-act-edit', () => editWord(span, false));
    on('trw-act-editall', () => editWord(span, true));
    on('trw-act-mergeprev', () => mergeWords(prevWord, span));
    on('trw-act-mergenext', () => mergeWords(span, nextWord));
    on('trw-act-delete', () => deleteWord(span));
  }

  // ลบคำนี้ออกจากคำที่น่าสงสัยทุกรายการ (ยืนยัน/แก้ไขแล้วจะไม่ถูกฟ้องคำนี้อีก)
  function forgetWord(word) {
    state.misspelledSet.delete(word);
    state.foreignSet.delete(word);
    state.spacingSet.delete(word);
  }

  async function confirmWord(span) {
    const word = span.dataset.word;
    try {
      const res = await postJSON('confirm_thai_word', { word });
      if (!res || !res.success) throw new Error(res && res.error);
    } catch (e) {
      alert('ยืนยันคำไม่สำเร็จ ลองใหม่อีกครั้ง');
      return;
    }
    forgetWord(word);
    wordSpans(word).forEach(clearFlag);   // เอาไฮไลต์ออกแต่ยังคลิกแก้ได้เหมือนคำอื่น ๆ
    updateStatus();
  }

  // applyAll = true → แก้ "ทุกจุด" ที่เป็นคำเดียวกันในเรียงความทั้งฉบับ (เหมาะกับคำที่สะกดผิดซ้ำ ๆ)
  // applyAll = false → แก้เฉพาะจุดที่คลิก (ค่าเริ่มต้น — คำทั่วไปอย่าง "การ" "และ" ห้ามแก้เหมารวม)
  function editWord(span, applyAll) {
    span.classList.add('trw-editing');
    const original = span.textContent;
    const originalWord = span.dataset.word;  // เก็บไว้ก่อนแก้ เผื่อคำเดียวกันนี้ปรากฏหลายจุดในเรียงความ
    const targets = applyAll ? wordSpans(originalWord) : [span];
    span.textContent = '';
    const input = document.createElement('input');
    input.type = 'text';
    input.value = original;
    input.size = Math.max(6, original.length + 2);   // คำที่เพิ่งรวมกันอาจยาวกว่าช่องขนาดปกติ
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
      span.classList.remove('trw-editing');
      if (commit && !isBlank && raw !== original) {
        if (applyAll) forgetWord(originalWord);
        targets.forEach(el => setWordText(el, raw, true));
        state.dirty = true;
      } else {
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

  // รวมคำที่ตัวตัดคำแยกออกจากกันทั้งที่เป็นคำเดียวกัน (เช่น "ประเทศ"+"ไทย") ให้เป็นก้อนเดียว
  // ข้อความรวมแล้วเท่าเดิมทุกตัวอักษร จึงยังไม่นับเป็นการแก้ไข แล้วเปิดช่องพิมพ์ให้ทันที
  // เพราะจุดประสงค์ของการรวมคำคือแก้ทั้งคำในคราวเดียว (กด Esc ถ้าแค่อยากดูเฉย ๆ)
  function mergeWords(first, second) {
    if (!first || !second) return;
    const merged = first.textContent + second.textContent;
    second.remove();
    setWordText(first, merged, false);
    editWord(first, false);
  }

  // เช็คว่าข้อความที่ต่อจากนี้ขึ้นต้นด้วย "คำ" จริง ๆ หรือไม่ (ใช้ segmentText ตัวเดียวกับที่ใช้ตัดคำ
  // เพื่อให้สอดคล้องกับตัวตรวจฝั่งเซิร์ฟเวอร์) — ถ้าขึ้นต้นด้วยช่องว่างหรือเครื่องหมายวรรคตอน
  // (เช่น "," ",") ถือว่ามีตัวคั่นอยู่แล้ว ไม่ต้องเติมช่องว่างซ้ำ
  function textStartsWithWord(text) {
    if (!text) return false;
    const seg = segmentText(text)[0];
    return !!(seg && seg.isWord);
  }

  // เติมช่องว่างคั่น "ท้าย" โหนดนี้ (โหนดอาจเป็นข้อความธรรมดาหรือ span ของคำข้าง ๆ ก็ได้)
  function ensureSpaceAfterNode(node) {
    if (!node) return;
    if (node.nodeType === Node.TEXT_NODE) {
      if (!/[ \t\n]$/.test(node.textContent)) node.textContent += ' ';
    } else {
      node.after(document.createTextNode(' '));
    }
  }

  // เติมช่องว่างคั่น "หน้า" โหนดนี้ — ถ้าขึ้นต้นด้วยเครื่องหมายวรรคตอนอยู่แล้วก็ไม่ต้องเติม
  function ensureSpaceBeforeNode(node) {
    if (!node) return;
    if (node.nodeType === Node.TEXT_NODE) {
      if (textStartsWithWord(node.textContent)) node.textContent = ' ' + node.textContent;
    } else {
      node.before(document.createTextNode(' '));
    }
  }

  // แก้เว้นวรรครอบ "ๆ" ให้ถูกต้องแบบอัตโนมัติ (ไม่พึ่งให้ผู้ใช้พิมพ์เว้นวรรคเอง เพราะจุดที่ต้องเว้น
  // วรรค "หลัง" ๆ อยู่นอกขอบเขตคำที่ถูกไฮไลต์ — พิมพ์แก้ในช่องแก้คำธรรมดาจะเติมได้แค่ฝั่งหน้าเท่านั้น)
  // เว้นวรรคให้ทั้งสองด้านเสมอ (เฉพาะฝั่งที่ติดกับ "คำ" จริง ๆ ไม่แทรกซ้ำถ้ามีเครื่องหมายวรรคตอน
  // คั่นอยู่แล้ว) ทำกับทุกจุดที่เป็นคำเดียวกันในเรียงความทั้งฉบับ
  function fixSpacing(span) {
    const word = span.dataset.word;
    const stem = (word !== 'ๆ' && word.slice(-1) === 'ๆ') ? word.slice(0, -1) : '';
    wordSpans(word).forEach((el) => {
      const next = el.nextSibling;
      const frag = document.createDocumentFragment();
      if (stem) {
        // ตัดคำที่ซ้ำ (stem) กับ "ๆ" ออกจากกัน แล้วแทรกช่องว่างคั่นกลาง
        frag.appendChild(makeWordSpan(stem));
        frag.appendChild(document.createTextNode(' '));
      } else {
        ensureSpaceAfterNode(el.previousSibling);
      }
      frag.appendChild(makeWordSpan('ๆ'));
      el.replaceWith(frag);
      ensureSpaceBeforeNode(next);
    });
    forgetWord(word);
    state.dirty = true;
    updateStatus();
  }

  // ลบคำนี้ออกจากเนื้อหาเลย (เช่น คำซ้ำ/คำที่ไม่ควรอยู่) พร้อมยุบช่องว่างซ้ำที่อาจเกิดขึ้น
  function deleteWord(span) {
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
    // ถ้ายังมีคำเดียวกันเหลืออยู่จุดอื่น ให้ฟ้องต่อไปตามเดิม
    if (wordSpans(word).length === 0) forgetWord(word);
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
  // options.focusWord   : string (ไม่บังคับ) คำที่ให้เลื่อนไปหาและเปิดกล่องตัวเลือกให้ทันทีตอนเปิดหน้าต่าง
  // options.focusIndex  : number (ไม่บังคับ) ลำดับที่ของคำนั้น (เริ่มที่ 0) กรณีคำเดียวกันมีหลายจุด
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
    if (options.focusWord) focusWord(options.focusWord, options.focusIndex || 0);
  }

  // เลื่อนไปยังคำที่ผู้ใช้คลิกมาจากหน้าตัวอย่างข้างนอก แล้วเปิดกล่องตัวเลือกให้เลย
  function focusWord(word, index) {
    const spans = wordSpans(word);
    const span = spans[index] || spans[0];
    if (!span) return;
    try { span.scrollIntoView({ block: 'center' }); } catch (e) { span.scrollIntoView(); }
    openWordActions(span);
  }

  // ลำดับที่ของคำนี้ในบรรดาคำที่เขียนเหมือนกันภายใน root — ส่งต่อเป็น focusIndex ให้ open()
  // เพื่อให้หน้าต่างตรวจสอบเปิดตรง "จุดที่คลิก" ไม่ใช่จุดแรกที่เจอ
  function wordIndexIn(root, el) {
    const text = el.textContent;
    let idx = 0;
    const all = root.querySelectorAll('.thai-word');
    for (let i = 0; i < all.length; i++) {
      if (all[i] === el) return idx;
      if (all[i].textContent === text) idx++;
    }
    return 0;
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

  window.ThaiReview = { open, segmentText, renderStaticHTML, wordIndexIn };
})(window, document);
