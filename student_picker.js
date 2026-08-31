/**
 * student_picker.js — กล่องเลือกรายชื่อนักเรียนแบบ "พิมพ์ค้นหาได้"
 * ---------------------------------------------------------------------------
 * ใช้ยกระดับ <select> ธรรมดาให้ค้นหาด้วยการพิมพ์ได้ โดยไม่ต้องแก้โค้ดเดิมของแต่ละหน้า
 *
 * วิธีใช้: ใส่ data-search-select ให้ <select> ที่ต้องการ เช่น
 *     <select id="aiStudentSelect" class="form-select" data-search-select>...</select>
 *
 * หลักการทำงาน
 *   • <select> ตัวจริงยังอยู่ครบ (ซ่อนไว้) — โค้ดเดิมที่อ่าน .value / ผูก onchange
 *     / ส่งฟอร์มด้วย name= จึงทำงานเหมือนเดิมทุกประการ
 *   • สร้างช่องพิมพ์ + รายการให้เลือกซ้อนทับไว้ด้านหน้า เมื่อผู้ใช้เลือกจะเซ็ตค่า
 *     กลับไปที่ <select> แล้วยิง event 'change' ให้โค้ดเดิมทำงานต่อ
 *   • เฝ้าดู <option> ด้วย MutationObserver — หน้าไหนโหลดรายชื่อจาก API ทีหลัง
 *     (เขียนทับ innerHTML) รายการค้นหาจะอัปเดตตามให้อัตโนมัติ
 *   • เมนูวางแบบ position:fixed และผูกไว้กับ <body> จึงไม่ถูกกล่อง modal
 *     หรือกล่องที่มี overflow ตัดขอบ
 */
(function () {
  'use strict';

  var instances = [];
  var openInst  = null;

  /* ---------------------------------------------------- เครื่องมือช่วยเล็ก ๆ */

  // ตัดช่องว่างและแปลงเป็นตัวพิมพ์เล็ก เพื่อให้พิมพ์ค้นแบบหลวม ๆ ได้ (ไทย/อังกฤษ/ตัวเลข)
  function norm(s) {
    return String(s == null ? '' : s).toLowerCase().replace(/\s+/g, '');
  }

  function escHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // ตรงกันเมื่อทุกคำที่พิมพ์ปรากฏอยู่ในข้อความ (พิมพ์ "606 สมชาย" ก็เจอ)
  function isMatch(text, query) {
    var hay = norm(text);
    var parts = String(query).trim().toLowerCase().split(/\s+/);
    for (var i = 0; i < parts.length; i++) {
      if (parts[i] && hay.indexOf(norm(parts[i])) === -1) return false;
    }
    return true;
  }

  // ให้ช่องพิมพ์มีขนาด/มุมโค้ง/เส้นขอบเหมือน <select> เดิม หน้าตาจึงไม่เพี้ยน
  function inheritClasses(sel) {
    var out = [];
    if (sel.classList.contains('form-select-sm')) out.push('form-control-sm');
    if (sel.classList.contains('form-select-lg')) out.push('form-control-lg');
    sel.classList.forEach(function (c) {
      if (/^(rounded|border|shadow|px-|py-|fw-|text-)/.test(c) && c !== 'form-select') out.push(c);
    });
    return out.join(' ');
  }

  /* ------------------------------------------------------------- ตัวคอมโพเนนต์ */

  function SearchSelect(sel) {
    var self = this;
    this.sel         = sel;
    this.rows        = [];   // รายการที่เลือกได้ทั้งหมด { value, label, selected }
    this.shown       = [];   // รายการที่ผ่านตัวกรองการค้นหาในขณะนี้
    this.active      = -1;   // แถวที่ไฮไลต์ไว้ (สำหรับปุ่มลูกศร/Enter)
    this.placeholder = sel.getAttribute('data-search-placeholder') || 'พิมพ์ค้นหาด้วยรหัส หรือ ชื่อ...';

    // ---- โครงสร้าง DOM ----
    var wrap = document.createElement('div');
    wrap.className = 'ss-wrap';
    sel.parentNode.insertBefore(wrap, sel);
    wrap.appendChild(sel);
    sel.classList.add('ss-native');
    sel.setAttribute('tabindex', '-1');
    sel.setAttribute('aria-hidden', 'true');

    var ctrl = document.createElement('div');
    ctrl.className = 'ss-control';

    var input = document.createElement('input');
    input.type = 'text';
    input.className = ('form-control ss-input ' + inheritClasses(sel)).trim();
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    if (sel.id) input.setAttribute('aria-controls', sel.id + '__ssmenu');

    var caret = document.createElement('span');
    caret.className = 'ss-caret';
    caret.innerHTML = '<i class="bi bi-search"></i>';

    ctrl.appendChild(input);
    ctrl.appendChild(caret);
    wrap.appendChild(ctrl);

    var menu = document.createElement('div');
    menu.className = 'ss-menu';
    if (sel.id) menu.id = sel.id + '__ssmenu';
    var list = document.createElement('div');
    list.className = 'ss-list';
    list.setAttribute('role', 'listbox');
    var empty = document.createElement('div');
    empty.className = 'ss-empty d-none';
    empty.textContent = 'ไม่พบรายชื่อที่ตรงกับคำค้น';
    var count = document.createElement('div');
    count.className = 'ss-count';
    menu.appendChild(list);
    menu.appendChild(empty);
    menu.appendChild(count);
    document.body.appendChild(menu);

    if (sel.style.minWidth) wrap.style.minWidth = sel.style.minWidth;
    if (sel.style.width)    wrap.style.width    = sel.style.width;

    this.wrap = wrap; this.ctrl = ctrl; this.input = input;
    this.menu = menu; this.list = list; this.empty = empty; this.count = count;

    // ---- เหตุการณ์ ----
    // คลิก <label for="..."> ที่ชี้มาที่ <select> เดิม → ย้ายโฟกัสมาที่ช่องพิมพ์แทน
    sel.addEventListener('focus', function () { input.focus(); });

    input.addEventListener('focus', function () { self.open(); });
    input.addEventListener('mousedown', function () { if (!self.isOpen()) self.open(); });
    input.addEventListener('input', function () { self.render(input.value); });
    input.addEventListener('keydown', function (e) { self.onKeyDown(e); });
    input.addEventListener('blur', function () {
      // ปล่อยให้ mousedown บนรายการทำงานเสร็จก่อนค่อยปิด
      setTimeout(function () { if (document.activeElement !== input) self.close(); }, 0);
    });

    list.addEventListener('mousedown', function (e) {
      var row = e.target.closest('.ss-item');
      if (!row) return;
      e.preventDefault();               // กันไม่ให้ช่องพิมพ์เสียโฟกัสก่อนเลือก
      self.choose(row.getAttribute('data-value'));
    });

    // <option> ถูกเขียนใหม่ (เช่นโหลดรายชื่อจาก API เสร็จ) → อัปเดตรายการค้นหาตาม
    this.observer = new MutationObserver(function () { self.sync(); });
    this.observer.observe(sel, { childList: true, subtree: true });

    this.sync();
  }

  SearchSelect.prototype.isOpen = function () { return openInst === this; };

  // อ่าน <option> ทั้งหมดมาเก็บไว้ แล้วอัปเดตข้อความในช่องพิมพ์ให้ตรงกับค่าที่เลือกอยู่
  SearchSelect.prototype.sync = function () {
    var opts = this.sel.options;
    var rows = [], ph = '';
    for (var i = 0; i < opts.length; i++) {
      var o = opts[i];
      var label = (o.textContent || '').replace(/\s+/g, ' ').trim();
      // ตัวเลือกว่าง/ที่ถูก disabled ใช้เป็นข้อความบอกใบ้ ไม่ใช่รายชื่อจริง
      if (o.disabled || o.value === '') { if (!ph) ph = label; continue; }
      rows.push({ value: o.value, label: label });
    }
    this.rows = rows;
    this.hintText = ph;
    this.label = this.labelFor(this.sel.value);
    this.input.placeholder = this.label ? this.label : (ph || this.placeholder);
    if (!this.isOpen()) this.input.value = this.label;
    else this.render(this.input.value);
    // ไม่มีรายชื่อให้เลือกเลย = ยังโหลดไม่เสร็จ/ไม่มีข้อมูล → กันไม่ให้กดค้นหา
    this.input.disabled = this.sel.disabled;
    return this;
  };

  SearchSelect.prototype.labelFor = function (v) {
    for (var i = 0; i < this.rows.length; i++) if (this.rows[i].value === v) return this.rows[i].label;
    return '';
  };

  SearchSelect.prototype.open = function () {
    if (openInst && openInst !== this) openInst.close();
    this.sync();
    openInst = this;
    this.input.value = '';                       // เคลียร์ให้พิมพ์ค้นได้ทันที
    this.input.placeholder = this.label || this.hintText || this.placeholder;
    this.menu.classList.add('show');
    this.input.setAttribute('aria-expanded', 'true');
    this.render('');
    this.position();
  };

  SearchSelect.prototype.close = function () {
    if (openInst === this) openInst = null;
    this.menu.classList.remove('show');
    this.input.setAttribute('aria-expanded', 'false');
    this.input.value = this.label;               // ไม่ได้เลือกอะไร → คืนค่าเดิม
    this.input.placeholder = this.label ? this.label : (this.hintText || this.placeholder);
    this.active = -1;
  };

  // วางเมนูให้ชิดกล่อง และพลิกขึ้นด้านบนเมื่อพื้นที่ด้านล่างไม่พอ
  SearchSelect.prototype.position = function () {
    var r = this.ctrl.getBoundingClientRect();
    var below = window.innerHeight - r.bottom;
    var above = r.top;
    var w = Math.max(r.width, Math.min(280, window.innerWidth - 16));
    var left = r.left;
    if (left + w > window.innerWidth - 8) left = window.innerWidth - 8 - w;
    this.menu.style.left  = Math.max(8, left) + 'px';
    this.menu.style.width = w + 'px';
    if (below < 220 && above > below) {
      this.menu.style.top = '';
      this.menu.style.bottom = (window.innerHeight - r.top + 4) + 'px';
      this.list.style.maxHeight = Math.max(120, Math.min(320, above - 60)) + 'px';
    } else {
      this.menu.style.bottom = '';
      this.menu.style.top = (r.bottom + 4) + 'px';
      this.list.style.maxHeight = Math.max(120, Math.min(320, below - 60)) + 'px';
    }
  };

  SearchSelect.prototype.render = function (query) {
    var q = (query || '').trim();
    var cur = this.sel.value;
    this.shown = q ? this.rows.filter(function (r) { return isMatch(r.label, q); }) : this.rows.slice();

    var html = '';
    for (var i = 0; i < this.shown.length; i++) {
      var r = this.shown[i];
      html += '<div class="ss-item' + (r.value === cur ? ' selected' : '') + '" role="option"'
            + ' data-value="' + escHtml(r.value) + '" data-i="' + i + '">'
            + escHtml(r.label)
            + '</div>';
    }
    this.list.innerHTML = html;
    this.empty.classList.toggle('d-none', this.shown.length > 0);
    this.count.textContent = this.rows.length
      ? (q ? 'พบ ' + this.shown.length + ' จาก ' + this.rows.length + ' คน' : 'ทั้งหมด ' + this.rows.length + ' คน')
      : 'ยังไม่มีรายชื่อ';

    // ไฮไลต์คนที่เลือกอยู่ก่อน ถ้าไม่มีก็ไฮไลต์รายการแรก
    this.active = -1;
    for (var j = 0; j < this.shown.length; j++) if (this.shown[j].value === cur) { this.active = j; break; }
    if (this.active === -1 && this.shown.length) this.active = 0;
    this.paintActive(true);
  };

  SearchSelect.prototype.paintActive = function (scroll) {
    var els = this.list.children;
    for (var i = 0; i < els.length; i++) els[i].classList.toggle('active', i === this.active);
    if (scroll && this.active >= 0 && els[this.active]) {
      var el = els[this.active];
      var top = el.offsetTop, bottom = top + el.offsetHeight;
      if (top < this.list.scrollTop) this.list.scrollTop = top;
      else if (bottom > this.list.scrollTop + this.list.clientHeight) this.list.scrollTop = bottom - this.list.clientHeight;
    }
  };

  SearchSelect.prototype.onKeyDown = function (e) {
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (!this.isOpen()) { this.open(); return; }
      if (!this.shown.length) return;
      this.active += (e.key === 'ArrowDown' ? 1 : -1);
      if (this.active < 0) this.active = this.shown.length - 1;
      if (this.active >= this.shown.length) this.active = 0;
      this.paintActive(true);
    } else if (e.key === 'Enter') {
      if (this.isOpen() && this.active >= 0 && this.shown[this.active]) {
        e.preventDefault();
        this.choose(this.shown[this.active].value);
      }
    } else if (e.key === 'Escape') {
      if (this.isOpen()) { e.preventDefault(); this.close(); }
    } else if (e.key === 'Tab') {
      this.close();
    }
  };

  // เลือกรายชื่อ → เซ็ตค่าให้ <select> ตัวจริง แล้วยิง change ให้โค้ดเดิมทำงานต่อ
  SearchSelect.prototype.choose = function (v) {
    var changed = (this.sel.value !== v);
    this.sel.value = v;
    this.label = this.labelFor(v);
    this.close();
    this.input.blur();
    if (changed) {
      this.sel.dispatchEvent(new Event('input',  { bubbles: true }));
      this.sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  /* ------------------------------------------------------------ ส่วนเชื่อมภายนอก */

  function enhance(sel) {
    if (!sel || sel.tagName !== 'SELECT' || sel.__ss) return sel && sel.__ss;
    var inst = new SearchSelect(sel);
    sel.__ss = inst;
    instances.push(inst);
    return inst;
  }

  function enhanceAll(root) {
    (root || document).querySelectorAll('select[data-search-select]').forEach(enhance);
  }

  // ปิดเมนูเมื่อคลิกที่อื่น และเลื่อน/ย่อขยายจอแล้วให้เมนูตามกล่องไปด้วย
  document.addEventListener('mousedown', function (e) {
    if (openInst && !openInst.wrap.contains(e.target) && !openInst.menu.contains(e.target)) openInst.close();
  });
  window.addEventListener('scroll', function () { if (openInst) openInst.position(); }, true);
  window.addEventListener('resize', function () { if (openInst) openInst.position(); });

  window.SearchSelect = {
    enhance: enhance,
    enhanceAll: enhanceAll,
    // เรียกเมื่อเปลี่ยนค่า <select> ด้วยโค้ด (การเซ็ต .value ตรง ๆ ไม่มี event ให้ดักฟัง)
    refresh: function (sel) {
      if (typeof sel === 'string') sel = document.getElementById(sel);
      if (sel && sel.__ss) sel.__ss.sync();
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { enhanceAll(); });
  } else {
    enhanceAll();
  }
})();
