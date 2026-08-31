/* ai_summary.js — "สรุปภาพรวมผลงานเขียนรายบุคคล" ของระบบผู้ช่วย AI
   ใช้ในหน้า ai_student_summary.php (หน้าสรุปแยกที่เด้งขึ้นมาเมื่อคลิกดูนักเรียนรายคน)
   คิดจากผลตรวจที่บันทึกไว้แล้วทุกรอบ ไม่เรียก AI ใหม่ จึงไม่เปลืองโควตา
   ต้องโหลด ai_review.js ก่อนไฟล์นี้ (ใช้ aiEsc / aiNum / aiLevelFromScore / AI_BASELINE_PAIRS ร่วมกัน) */

/* ---- ตัวช่วยพื้นฐาน ---- */

// รอบที่มีผลตรวจแล้ว เรียงตามลำดับการเรียน
function aiSumReviewedPhases(all, phases) {
  return phases.filter(ph => all[ph]);
}

// สีของแถบตามระดับความสำเร็จของเกณฑ์นั้น
function aiSumPctColor(pct) {
  if (pct >= 80) return '#0d9488';
  if (pct >= 60) return '#2563eb';
  if (pct >= 40) return '#d97706';
  return '#dc2626';
}

// สรุปคะแนนรายเกณฑ์ข้ามทุกรอบ: id => {name, max, sum, cnt, avg, pct, lost, times}
function aiSumCriteria(all, phases) {
  const acc = {};
  const touch = (id, name, max) => {
    if (!acc[id]) acc[id] = { id, name: name || '', max: Number(max) || 0, sum: 0, cnt: 0, times: 0 };
    if (name && !acc[id].name) acc[id].name = name;
    return acc[id];
  };

  aiSumReviewedPhases(all, phases).forEach(ph => {
    const fb = all[ph];
    Object.keys(fb.scores || {}).forEach(id => {
      const c = fb.scores[id];
      const a = touch(id, c.name, c.max);
      a.sum += Number(c.weighted); a.cnt++;
    });
    // ข้อที่ครูให้เอง (4.3) นับด้วยเมื่อมีคะแนนแล้ว จะได้เห็นภาพครบทุกเกณฑ์
    Object.keys(fb.teacher_scores || {}).forEach(id => {
      const c = fb.teacher_scores[id];
      const a = touch(id, c.name, c.max);
      a.sum += Number(c.weighted); a.cnt++;
    });
    // นับว่าเกณฑ์ข้อไหนถูก AI ชี้ว่าควรปรับปรุงกี่รอบ
    (fb.improvements || []).forEach(it => {
      const id = (it.criterion || '').trim();
      if (id && acc[id]) acc[id].times++;
    });
  });

  return Object.values(acc).map(a => {
    const avg = a.cnt ? a.sum / a.cnt : 0;
    return Object.assign(a, {
      avg:  Math.round(avg * 100) / 100,
      pct:  a.max > 0 ? Math.round((avg / a.max) * 100) : 0,
      lost: Math.round((a.max - avg) * 100) / 100
    });
  }).sort((x, y) => x.id.localeCompare(y.id, 'th', { numeric: true }));
}

/* ---- กราฟเส้นพัฒนาการคะแนนรวมข้ามรอบงาน (SVG ล้วน ไม่ต้องพึ่งไลบรารีภายนอก) ----
   แกนนอนขึ้นครบทุกรอบงานเสมอ (ก่อนเรียน · D1.1 · D1.2 · D2.1 · D2.2 · หลังเรียน)
   รอบที่ยังไม่ได้ตรวจจะเว้นไว้เป็นจุดจาง ๆ ให้เห็นว่ายังขาดช่วงไหน */
function aiSumTrendSVG(slots, fullMax) {
  const W = 560, H = 236, L = 38, R = 14, T = 18, B = 40;
  const pw = W - L - R, phh = H - T - B;
  const n  = slots.length;
  const yOf = v => T + (1 - (v / fullMax)) * phh;
  const xOf = i => n > 1 ? L + (i / (n - 1)) * pw : L + pw / 2;
  const base = T + phh;

  // เส้นอ้างอิงตามเกณฑ์ระดับคุณภาพ ให้เห็นว่าคะแนนอยู่ช่วงระดับไหน
  const marks = [
    { v: 49, label: 'ดีมาก' }, { v: 37, label: 'ดี' },
    { v: 25, label: 'ปานกลาง' }, { v: 13, label: 'พอใช้' }
  ].filter(m => m.v < fullMax);

  const grid = marks.map(m => `
    <line x1="${L}" y1="${yOf(m.v).toFixed(1)}" x2="${W - R}" y2="${yOf(m.v).toFixed(1)}"
          stroke="#e2e8f0" stroke-width="1" stroke-dasharray="4 4"></line>
    <text x="${L - 6}" y="${(yOf(m.v) + 3).toFixed(1)}" text-anchor="end"
          font-size="9" fill="#94a3b8">${aiEsc(m.label)}</text>`).join('');

  // ลำดับของรอบที่มีคะแนนแล้ว (ใช้ลากเส้นเชื่อม)
  const have = slots.map((sl, i) => (sl.value === null || sl.value === undefined) ? -1 : i).filter(i => i >= 0);

  // เชื่อมทีละช่วง — ถ้าข้ามรอบที่ยังไม่ได้ตรวจ ให้เป็นเส้นประ บอกว่าช่วงนั้นไม่มีข้อมูล
  const segs = have.slice(1).map((cur, k) => {
    const prev = have[k];
    const gap  = (cur - prev) > 1;
    return `<line x1="${xOf(prev).toFixed(1)}" y1="${yOf(slots[prev].value).toFixed(1)}"
                  x2="${xOf(cur).toFixed(1)}"  y2="${yOf(slots[cur].value).toFixed(1)}"
                  stroke="url(#aiTrendGrad)" stroke-width="3" stroke-linecap="round"
                  ${gap ? 'stroke-dasharray="6 5" stroke-opacity="0.55"' : ''}></line>`;
  }).join('');

  const area = have.length > 1
    ? `<polygon fill="url(#aiTrendFill)" points="${xOf(have[0]).toFixed(1)},${base}
        ${have.map(i => xOf(i).toFixed(1) + ',' + yOf(slots[i].value).toFixed(1)).join(' ')}
        ${xOf(have[have.length - 1]).toFixed(1)},${base}"></polygon>`
    : '';

  // ดึงป้ายของจุดริมซ้าย/ริมขวาเข้ามาเล็กน้อย ไม่ให้ตัวเลขล้นไปทับป้ายระดับหรือขอบกราฟ
  const labelX = i => Math.max(L + 14, Math.min(W - R - 14, xOf(i)));

  const dots = slots.map((sl, i) => {
    const x = xOf(i).toFixed(1), lx = labelX(i).toFixed(1);
    // รอบที่ยังไม่ได้ตรวจ: จุดจาง ๆ บนแกน พร้อมป้าย "ยังไม่ตรวจ"
    if (sl.value === null || sl.value === undefined) {
      return `<line x1="${x}" y1="${T}" x2="${x}" y2="${base}" stroke="#f1f5f9" stroke-width="1"></line>
        <circle cx="${x}" cy="${base}" r="3.5" fill="#ffffff" stroke="#cbd5e1" stroke-width="2"></circle>
        <text x="${lx}" y="${base - 9}" text-anchor="middle" font-size="9" fill="#cbd5e1">ยังไม่ตรวจ</text>
        <text x="${lx}" y="${H - 12}" text-anchor="middle" font-size="10" fill="#cbd5e1">${aiEsc(sl.label)}</text>`;
    }
    return `<circle cx="${x}" cy="${yOf(sl.value).toFixed(1)}" r="5.5" fill="#ffffff" stroke="#6d28d9" stroke-width="3"></circle>
      <text x="${lx}" y="${(yOf(sl.value) - 12).toFixed(1)}" text-anchor="middle"
            font-size="12" font-weight="700" fill="#4c1d95">${aiNum(sl.value)}</text>
      <text x="${lx}" y="${H - 12}" text-anchor="middle" font-size="10" font-weight="600" fill="#475569">${aiEsc(sl.label)}</text>`;
  }).join('');

  return `<svg viewBox="0 0 ${W} ${H}" width="100%" height="236" role="img"
               aria-label="กราฟพัฒนาการคะแนนรวมของทุกรอบงาน">
    <defs>
      <linearGradient id="aiTrendGrad" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0%" stop-color="#6d28d9"></stop><stop offset="100%" stop-color="#0d7377"></stop>
      </linearGradient>
      <linearGradient id="aiTrendFill" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#6d28d9" stop-opacity="0.18"></stop>
        <stop offset="100%" stop-color="#6d28d9" stop-opacity="0"></stop>
      </linearGradient>
    </defs>
    ${grid}
    <line x1="${L}" y1="${base}" x2="${W - R}" y2="${base}" stroke="#cbd5e1" stroke-width="1"></line>
    ${area}${segs}${dots}
  </svg>`;
}

// การ์ดตัวเลขสรุป 1 ใบ
function aiSumTile(icon, label, value, sub, color) {
  return `<div class="col">
    <div class="ai-stat-tile h-100 p-3 rounded-4">
      <div class="text-muted small mb-1"><i class="bi ${icon} me-1" style="color:${color};"></i>${aiEsc(label)}</div>
      <div class="fs-4 fw-bold lh-1" style="color:${color};">${value}</div>
      ${sub ? `<div class="text-muted mt-1" style="font-size:0.78rem;">${sub}</div>` : ''}
    </div>
  </div>`;
}

/* ============================================================
   คู่เทียบตามที่คุณครูกำหนด — คำถามเดียวที่ต้องตอบให้ได้ในหน้านี้
   D1.2 ต้องดีกว่า D1.1 · D2.2 ต้องดีกว่า D2.1 · หลังเรียน ต้องดีกว่าก่อนเรียน
   ============================================================ */

// การ์ดคู่เทียบ 1 คู่ (คลิกไม่ได้ — เป็นบทสรุป ไม่ใช่ปุ่ม)
function aiSumPairCard(targetPhase, all) {
  const basePh = AI_BASELINE_PAIRS[targetPhase];
  const fb     = all[targetPhase];
  const head   = `<div class="ai-pair-head">
      <span class="ai-pair-from">${aiEsc(aiPhaseShort(basePh))}</span>
      <i class="bi bi-arrow-right mx-1"></i>
      <span class="ai-pair-to">${aiEsc(aiPhaseShort(targetPhase))}</span>
    </div>`;

  // ยังไม่ได้ตรวจฉบับปลายทาง หรือยังไม่ได้ตรวจฉบับตั้งต้น → บอกให้ครูรู้ว่าขาดอะไร
  const d = fb ? fb.draft_compare : null;
  if (!d || !d.has_baseline) {
    const missing = !fb
      ? 'ยังไม่มีผลตรวจของ ' + aiPhaseShort(targetPhase)
      : 'ยังไม่มีผลตรวจของ ' + aiPhaseShort(basePh) + ' จึงยังเทียบไม่ได้';
    return `<div class="col">
      <div class="ai-pair-card ai-pair-wait h-100 p-3 rounded-4">
        ${head}
        <div class="text-muted small mt-2"><i class="bi bi-hourglass-split me-1"></i>${aiEsc(missing)}</div>
      </div>
    </div>`;
  }

  const up = d.delta > 0, flat = (d.delta === 0);
  const cls = up ? 'ai-pair-up' : (flat ? 'ai-pair-flat' : 'ai-pair-down');
  const verdict = up
    ? '<i class="bi bi-check-circle-fill me-1"></i>ดีขึ้นตามที่ควรเป็น'
    : (flat ? '<i class="bi bi-exclamation-triangle-fill me-1"></i>คะแนนเท่าเดิม ยังไม่ดีขึ้น'
            : '<i class="bi bi-arrow-down-circle-fill me-1"></i>คะแนนถอยลง');


  // คนละหัวข้อ (หลังเรียน↔ก่อนเรียน) เป็นงานเขียนคนละชิ้น จึงพูดถึงพัฒนาการ ไม่ใช่การแก้งาน
  const newTopic = (d.kind || aiBaselineKind(targetPhase)) === 'newtopic';
  const warn = d.same_text
    ? (newTopic
        ? '<div class="ai-pair-warn mt-2"><i class="bi bi-files me-1"></i>ส่งข้อความเดิมของฉบับก่อนเรียนมาทั้งฉบับ ทั้งที่คนละหัวข้อ</div>'
        : '<div class="ai-pair-warn mt-2"><i class="bi bi-files me-1"></i>ข้อความเหมือนฉบับตั้งต้นทุกตัวอักษร — ยังไม่ได้แก้งาน</div>')
    : (d.identical
        ? '<div class="ai-pair-warn mt-2"><i class="bi bi-exclamation-triangle me-1"></i>คะแนนรายข้อเท่ากันทุกข้อ</div>'
        : '');

  return `<div class="col">
    <div class="ai-pair-card ${cls} h-100 p-3 rounded-4">
      ${head}
      <div class="d-flex align-items-end gap-2 mt-2">
        <div class="fs-5 fw-bold text-muted lh-1">${aiNum(d.base_total)}</div>
        <i class="bi bi-arrow-right text-muted mb-1"></i>
        <div class="display-6 fw-bold lh-1">${aiNum(d.total)}</div>
        <div class="text-muted pb-1 small text-nowrap">/ ${aiNum(d.max_score)}</div>
      </div>
      <div class="mt-2">${aiDeltaBadge(d.delta)}</div>
      <div class="ai-pair-verdict mt-2">${verdict}</div>
      <div class="small text-muted mt-2">
        ดีขึ้น ${d.up} ข้อ · ลดลง ${d.down} ข้อ · เท่าเดิม ${d.same} ข้อ
      </div>
      ${warn}
    </div>
  </div>`;
}

// แถวการ์ดคู่เทียบทั้งหมด + บทสรุปหนึ่งบรรทัดว่าผ่านกี่คู่
function aiSumPairsHTML(all) {
  const targets = Object.keys(AI_BASELINE_PAIRS);
  const cards   = targets.map(ph => aiSumPairCard(ph, all)).join('');

  const done = targets.filter(ph => all[ph] && all[ph].draft_compare && all[ph].draft_compare.has_baseline);
  const ok   = done.filter(ph => all[ph].draft_compare.delta > 0).length;
  let line = 'ยังไม่มีคู่ไหนที่เทียบได้ครบทั้งสองฉบับ — ให้ AI ตรวจฉบับตั้งต้นก่อน แล้วจึงตรวจร่างถัดไป';
  let lineCls = 'ai-pair-summary-wait';
  if (done.length) {
    if (ok === done.length) {
      line = `ผ่านเกณฑ์ทุกคู่ที่เทียบได้ (${ok} จาก ${done.length} คู่) — ร่างหลังได้คะแนนสูงกว่าร่างก่อนทุกคู่`;
      lineCls = 'ai-pair-summary-ok';
    } else {
      line = `ดีขึ้น ${ok} จาก ${done.length} คู่ที่เทียบได้ — คู่ที่ยังไม่ดีขึ้นควรกลับไปดูผลเทียบรายข้อประกอบ`;
      lineCls = 'ai-pair-summary-warn';
    }
  }

  return `<h6 class="fw-bold text-dark mb-1">
      <i class="bi bi-arrow-left-right text-primary me-2"></i>เทียบตามคู่ที่คุณครูกำหนด
      <span class="text-muted fw-normal small">(ร่างที่ 2 เทียบร่างที่ 1 หัวข้อเดียวกัน ·
        หลังเรียนเทียบก่อนเรียนซึ่งเป็นคนละหัวข้อ จึงเทียบที่คุณภาพเนื้อหาตามเกณฑ์)</span>
    </h6>
    <div class="text-muted small mb-2">
      ร่างที่ 2 ของแต่ละหน่วยต้องดีกว่าร่างที่ 1 ของหน่วยเดียวกัน และหลังเรียนต้องดีกว่าก่อนเรียน
      — เทียบเฉพาะคู่เหล่านี้เท่านั้น ไม่เทียบข้ามหน่วย
    </div>
    <div class="ai-pair-summary ${lineCls} mb-3">${aiEsc(line)}</div>
    <div class="row row-cols-1 row-cols-md-3 g-3">${cards}</div>`;
}

/* ============================================================
   สรุปภาพรวมรายบุคคลทั้งหน้า
   all    = { รหัสรอบงาน => ผลตรวจฉบับเต็ม }
   phases = ลำดับรอบงานทั้งหมดของระบบ
   ============================================================ */
function aiStudentSummaryHTML(all, phases) {
  const reviewed = aiSumReviewedPhases(all, phases);
  if (!reviewed.length) {
    return `<div class="card border-0 shadow-sm rounded-4"><div class="card-body">
      ${aiEmptyHTML('ยังไม่มีผลตรวจของ AI สำหรับนักเรียนคนนี้')}</div></div>`;
  }

  const first   = all[reviewed[0]];
  const last    = all[reviewed[reviewed.length - 1]];
  const fullMax = Number(last.full_max || last.max_score || 60);

  // แกนของกราฟใช้ "ทุกรอบงาน" เสมอ ส่วนสถิติ (เฉลี่ย/สูงสุด/พัฒนาการ) คิดจากรอบที่ตรวจแล้วเท่านั้น
  const slots = phases.map(ph => ({
    phase: ph,
    label: aiPhaseShort(ph),
    value: all[ph] ? aiCombinedOf(all[ph]) : null
  }));
  const points = slots.filter(sl => sl.value !== null);
  const values = points.map(p => p.value);
  const avg    = values.reduce((a, b) => a + b, 0) / values.length;
  const best   = points.reduce((a, b) => (b.value > a.value ? b : a), points[0]);
  const diff   = Math.round((last.combined_total - first.combined_total) * 100) / 100;
  const gained = reviewed.length > 1 ? diff : null;

  const tiles = [
    aiSumTile('bi-clipboard2-check', 'ตรวจแล้ว', reviewed.length + ' <span class="fs-6 fw-normal text-muted">ฉบับ</span>',
      'จากทั้งหมด ' + phases.length + ' รอบงาน', '#6d28d9'),
    aiSumTile('bi-bullseye', 'คะแนนเฉลี่ย', aiNum(avg) + ' <span class="fs-6 fw-normal text-muted">/ ' + aiNum(fullMax) + '</span>',
      'ระดับ ' + aiEsc(aiLevelFromScore(avg)), '#0d7377'),
    aiSumTile('bi-trophy', 'คะแนนดีที่สุด', aiNum(best.value) + ' <span class="fs-6 fw-normal text-muted">/ ' + aiNum(fullMax) + '</span>',
      'รอบ ' + aiEsc(best.label), '#b45309'),
    gained === null
      ? aiSumTile('bi-hourglass', 'พัฒนาการ', '—', 'ต้องมีอย่างน้อย 2 รอบจึงเทียบได้', '#64748b')
      : aiSumTile(gained > 0 ? 'bi-graph-up-arrow' : (gained < 0 ? 'bi-graph-down-arrow' : 'bi-dash-lg'),
          'พัฒนาการ', (gained > 0 ? '+' : '') + aiNum(gained) + ' <span class="fs-6 fw-normal text-muted">คะแนน</span>',
          aiEsc(aiPhaseShort(reviewed[0])) + ' → ' + aiEsc(aiPhaseShort(reviewed[reviewed.length - 1])),
          gained > 0 ? '#0d9488' : (gained < 0 ? '#dc2626' : '#64748b'))
  ].join('');

  // ---- กราฟรายเกณฑ์ (เฉลี่ยทุกรอบ) ----
  const crits = aiSumCriteria(all, phases);
  const critBars = crits.map(c => `
    <div class="mb-2">
      <div class="d-flex justify-content-between align-items-center small">
        <span class="text-truncate me-2"><span class="fw-semibold">${aiEsc(c.id)}</span> ${aiEsc(c.name)}</span>
        <span class="fw-bold text-nowrap" style="color:${aiSumPctColor(c.pct)};">${c.pct}%
          <span class="text-muted fw-normal">(${aiNum(c.avg)}/${aiNum(c.max)})</span></span>
      </div>
      <div class="ai-crit-bar mt-1"><span style="width:${c.pct}%; background:${aiSumPctColor(c.pct)};"></span></div>
    </div>`).join('');

  // ---- จุดแข็ง: เกณฑ์ที่ทำได้ดีสม่ำเสมอ + ข้อความชมจาก AI ----
  const strongCrits = crits.filter(c => c.pct >= 75).sort((a, b) => b.pct - a.pct).slice(0, 4);
  const strongList = strongCrits.length
    ? strongCrits.map(c => `<div class="d-flex align-items-start gap-2 mb-2">
        <i class="bi bi-check-circle-fill text-success mt-1"></i>
        <span class="small"><span class="fw-semibold">ข้อ ${aiEsc(c.id)} ${aiEsc(c.name)}</span>
          <span class="text-muted">— ทำได้ ${c.pct}% ของคะแนนเต็ม${c.cnt > 1 ? ' สม่ำเสมอทั้ง ' + c.cnt + ' รอบ' : ''}</span></span>
      </div>`).join('')
    : '<div class="text-muted small mb-2">ยังไม่มีเกณฑ์ข้อไหนที่ทำได้ถึง 75% — ลองไล่แก้จากรายการทางขวาทีละข้อ</div>';

  // ข้อความชมจาก AI (เอาของรอบล่าสุดที่มี ไม่ให้ซ้ำกัน)
  const seenStr = new Set();
  const praise = [];
  [...reviewed].reverse().forEach(ph => {
    (all[ph].strengths || []).forEach(t => {
      const k = String(t).trim();
      if (k && !seenStr.has(k) && praise.length < 3) { seenStr.add(k); praise.push({ text: k, ph }); }
    });
  });
  const praiseList = praise.map(x => `<div class="ai-summary-quote small mb-2">
      <span class="badge bg-success-subtle text-success-emphasis me-1">${aiEsc(aiPhaseShort(x.ph))}</span>${aiEsc(x.text)}
    </div>`).join('');

  // ---- จุดที่ต้องแก้: เรียงจากเกณฑ์ที่เสียคะแนนมากที่สุด ----
  const weak = crits.filter(c => c.pct < 100).sort((a, b) => (b.lost - a.lost) || (b.times - a.times)).slice(0, 3);
  const weakList = weak.length ? weak.map((c, i) => {
    // หยิบคำแนะนำของเกณฑ์ข้อนี้จากรอบล่าสุดที่ AI พูดถึง
    let tip = null, tipPhase = '';
    [...reviewed].reverse().some(ph => {
      const hit = (all[ph].improvements || []).find(it => (it.criterion || '').trim() === c.id);
      if (hit) { tip = hit; tipPhase = ph; return true; }
      return false;
    });
    return `<div class="ai-summary-todo p-3 rounded-3 mb-2">
      <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-1">
        <span class="fw-bold text-dark small">
          <span class="badge bg-danger-subtle text-danger-emphasis me-1">อันดับ ${i + 1}</span>
          ข้อ ${aiEsc(c.id)} ${aiEsc(c.name)}
        </span>
        <span class="small fw-semibold text-danger-emphasis text-nowrap">
          เสียเฉลี่ย ${aiNum(c.lost)} คะแนน/รอบ${c.times ? ' · AI ทัก ' + c.times + ' รอบ' : ''}
        </span>
      </div>
      ${tip && tip.issue ? `<div class="small mb-1"><span class="fw-semibold text-danger-emphasis">บกพร่องอะไร:</span> ${aiEsc(tip.issue)}</div>` : ''}
      ${tip && tip.suggestion ? `<div class="small"><span class="fw-semibold text-success-emphasis">แก้อย่างไร:</span> ${aiEsc(tip.suggestion)}</div>` : ''}
      ${tip ? `<div class="text-muted mt-1" style="font-size:0.75rem;">
        <i class="bi bi-info-circle me-1"></i>จากผลตรวจรอบ ${aiEsc(aiPhaseShort(tipPhase))}</div>` : ''}
    </div>`;
  }).join('') : '<div class="text-muted small">ยอดเยี่ยมมาก — ยังไม่พบเกณฑ์ข้อไหนที่เสียคะแนน</div>';

  // ---- สิ่งที่ควรทำก่อนเขียนครั้งถัดไป (จากรอบล่าสุด) ----
  const steps = [];
  const seenStep = new Set();
  [...reviewed].reverse().forEach(ph => {
    (all[ph].next_steps || []).forEach(t => {
      const k = String(t).trim();
      if (k && !seenStep.has(k) && steps.length < 4) { seenStep.add(k); steps.push(k); }
    });
  });
  const stepList = steps.length ? `
    <h6 class="fw-bold text-primary mt-4 mb-2"><i class="bi bi-list-check me-2"></i>เช็กลิสต์ก่อนลงมือเขียนครั้งถัดไป</h6>
    <div class="row row-cols-1 row-cols-md-2 g-2">
      ${steps.map((t, i) => `<div class="col"><div class="ai-summary-step p-3 rounded-3 h-100 small">
        <span class="badge bg-primary-subtle text-primary-emphasis me-1">${i + 1}</span>${aiEsc(t)}</div></div>`).join('')}
    </div>` : '';

  // ---- คำชื่นชมภาพรวม ----
  let praiseLine;
  if (gained !== null && gained > 0) {
    praiseLine = `เก่งมาก! คะแนนขยับขึ้น ${aiNum(gained)} คะแนนจากรอบแรก แสดงว่าที่แก้ไปได้ผลจริง `
               + `รักษาวิธีทำงานแบบนี้ไว้แล้วเก็บอีก ${weak.length ? 'ข้อ ' + weak[0].id : 'อีกนิด'} ให้ได้ในครั้งถัดไป`;
  } else if (gained !== null && gained < 0) {
    praiseLine = `รอบนี้คะแนนลดลง ${aiNum(Math.abs(gained))} คะแนน ไม่เป็นไรเลย — งานเขียนพัฒนาได้ด้วยการแก้ทีละจุด `
               + `ลองกลับไปดูว่ารอบที่ทำได้ดีที่สุด (${best.label}) เราทำอะไรต่างออกไป`;
  } else {
    praiseLine = `ตั้งใจดีมากที่ส่งงานมาให้ตรวจ — โฟกัสที่รายการ "ต้องแก้ให้ได้" ด้านบนทีละข้อ แล้วคะแนนจะขยับขึ้นแน่นอน`;
  }
  const encouragement = (last.encouragement || '').trim();

  return `
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #0d7377 !important;">
      <div class="card-body p-4">
        <div class="row row-cols-2 row-cols-lg-4 g-3">${tiles}</div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4" style="border-top:4px solid #6d28d9 !important;">
      <div class="card-body p-4">${aiSumPairsHTML(all)}</div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4">
        <div class="row g-4">
          <div class="col-lg-7">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-graph-up me-2"></i>พัฒนาการคะแนนรวม (เต็ม ${aiNum(fullMax)})</h6>
            <div class="ai-chart-box p-2 rounded-4">${aiSumTrendSVG(slots, fullMax)}</div>
            <div class="text-muted mt-1" style="font-size:0.75rem;">
              <i class="bi bi-info-circle me-1"></i>แกนนอนคือรอบงานทั้งหมด ${phases.length} รอบ
              — รอบที่ยังไม่ได้ตรวจจะเว้นไว้ และช่วงที่ข้ามรอบจะลากเป็นเส้นประ
            </div>
          </div>
          <div class="col-lg-5">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-bar-chart-steps me-2"></i>ความสำเร็จรายเกณฑ์ (เฉลี่ยทุกรอบ)</h6>
            <div class="ai-chart-box p-3 rounded-4">${critBars || '<div class="text-muted small">— ไม่มีข้อมูล —</div>'}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4">
        <div class="row g-4">
          <div class="col-lg-6">
            <h6 class="fw-bold text-success mb-2"><i class="bi bi-hand-thumbs-up-fill me-2"></i>ข้อดีที่ทำได้แล้ว — รักษาไว้</h6>
            ${strongList}
            ${praiseList ? `<div class="mt-3">${praiseList}</div>` : ''}
          </div>
          <div class="col-lg-6">
            <h6 class="fw-bold text-warning-emphasis mb-2"><i class="bi bi-tools me-2"></i>ต้องแก้ให้ได้ในครั้งถัดไป</h6>
            ${weakList}
          </div>
        </div>

        ${stepList}

        <div class="alert border-0 rounded-3 mt-4 mb-0" style="background:#f0fdf4; border-left:4px solid #16a34a !important;">
          <div class="fw-bold text-success mb-1"><i class="bi bi-heart-fill me-2"></i>คำชื่นชมและกำลังใจ</div>
          <div class="small text-dark" style="line-height:1.8;">${aiEsc(praiseLine)}</div>
          ${encouragement ? `<div class="small text-muted fst-italic mt-2">
            <i class="bi bi-quote me-1"></i>${aiEsc(encouragement)}</div>` : ''}
        </div>

        <div class="text-muted mt-3" style="font-size:0.75rem;">
          <i class="bi bi-info-circle me-1"></i>สรุปนี้คำนวณจากผลตรวจที่บันทึกไว้แล้วทุกรอบ
          ไม่ได้เรียก AI ใหม่ และไม่ถูกนำไปรวมกับคะแนนจริงในระบบประเมิน
        </div>
      </div>
    </div>`;
}
