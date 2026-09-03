<?php
/**
 * report_sections.php — ส่วนเนื้อหาของ "รายงานผลการเรียนรู้รายบุคคล"
 * ---------------------------------------------------------------------------
 * แต่ละฟังก์ชัน = 1 ส่วนของรายงาน พิมพ์ HTML ออกมาตรง ๆ
 * ใช้ร่วมกันระหว่าง student_report_print.php (เอกสารสำหรับพิมพ์)
 * และ student_report.php (หน้าเว็บฉบับเต็ม) เพื่อให้ทั้งสองแสดงเนื้อหาชุดเดียวกันเสมอ
 *
 * $sum  = report_student_summary()      $full = report_full_data()[student_id]
 * $ins  = report_student_insights()
 */

require_once 'report_data.php';
require_once 'report_analysis.php';
require_once 'report_print_ui.php';

/** หัวข้อประจำส่วน (มี id ไว้ให้หน้าเว็บทำสารบัญลิงก์ไปได้) */
function rs_title($no, $text, $sub = '', $id = '') {
    echo '<h2 class="sec-title"' . ($id ? ' id="' . rp_esc($id) . '"' : '') . '>'
       . ($no !== '' ? 'ส่วนที่ ' . rp_esc($no) . ' · ' : '') . rp_esc($text)
       . ($sub ? ' <span>' . rp_esc($sub) . '</span>' : '') . '</h2>';
}

/** ข้อความว่าง ๆ เมื่อยังไม่มีข้อมูลในส่วนนั้น */
function rs_empty($msg) {
    echo '<div class="note">' . rp_esc($msg) . '</div>';
}

/* ------------------------------------------------------------ หัวเอกสาร */

function rs_doc_head($stu, $title = 'รายงานผลการเรียนรู้รายบุคคล') {
    ?>
    <div class="doc-head">
      <h1><?php echo rp_esc($title); ?></h1>
      <div class="sub">รายวิชาภาษาไทย · การพัฒนาความสามารถในการเขียนเรียงความ</div>
    </div>
    <div class="idbox">
      <div><b>ชื่อ-สกุล:</b> <?php echo rp_esc($stu['student_name'] ?: '—'); ?></div>
      <div><b>รหัสนักเรียน:</b> <?php echo rp_esc($stu['student_id']); ?></div>
      <?php if (($stu['classroom'] ?? '') !== ''): ?><div><b>ห้อง:</b> <?php echo rp_esc($stu['classroom']); ?></div><?php endif; ?>
      <?php if (($stu['student_group'] ?? '') !== ''): ?><div><b>กลุ่ม:</b> <?php echo rp_esc($stu['student_group']); ?></div><?php endif; ?>
      <div class="grow"><b>ออกรายงาน:</b> <?php echo rp_esc(rp_thai_date()); ?></div>
    </div>
    <?php
}

/** รายชื่อทุกส่วนของรายงาน ตามลำดับที่พิมพ์จริง — ใช้ทำสารบัญ และอ้างอิงเลขส่วนให้ตรงกันทุกจุด */
function rs_section_list() {
    return [
        ['no' => '1',  'id' => 'sec-achievement', 'title' => 'ผลสัมฤทธิ์'],
        ['no' => '2',  'id' => 'sec-analysis',     'title' => 'บทวิเคราะห์รายบุคคล'],
        ['no' => '3',  'id' => 'sec-criteria',     'title' => 'สถิติรายเกณฑ์'],
        ['no' => '4',  'id' => 'sec-eval',         'title' => 'คะแนนรายเกณฑ์จากระบบตรวจอัตโนมัติ'],
        ['no' => '5',  'id' => 'sec-works',        'title' => 'ผลงานของนักเรียน'],
        ['no' => '6',  'id' => 'sec-essays',       'title' => 'เรียงความฉบับเต็มทุกรอบ'],
        ['no' => '7',  'id' => 'sec-ai',           'title' => 'ผลตรวจจากระบบตรวจอัตโนมัติทุกรอบงาน'],
        ['no' => '8',  'id' => 'sec-reflect',      'title' => 'บันทึกสะท้อนคิดของนักเรียน'],
        ['no' => '9',  'id' => 'sec-peer',         'title' => 'การประเมินร่วมกับเพื่อน'],
        ['no' => '10', 'id' => 'sec-overview',     'title' => 'ภาพรวมทั้งหมด'],
    ];
}

/** ส่วนนำ — เกริ่นนำที่มาและขอบเขตของรายงานก่อนเข้าเนื้อหา */
function rs_intro($sum) {
    $stu = $sum['student'];
    ?>
    <div class="front-sec">
      <h2 class="sec-title front-title">ส่วนนำ</h2>
      <p>
        รายงานฉบับนี้จัดทำขึ้นเพื่อสรุปผลการเรียนรู้ในรายวิชาภาษาไทย ด้านการพัฒนาความสามารถในการเขียนเรียงความ
        ของ <b><?php echo rp_esc($stu['student_name'] ?: '—'); ?></b> (รหัสนักเรียน <?php echo rp_esc($stu['student_id']); ?>)
        โดยรวบรวมข้อมูลจริงทั้งหมดที่บันทึกไว้ในระบบประเมินการเขียนเรียงความ ได้แก่ คะแนนผลสัมฤทธิ์ของคุณครูผู้สอน
        บทวิเคราะห์รายบุคคล สถิติรายเกณฑ์เทียบก่อนเรียน-หลังเรียน คะแนนและหมายเหตุจากระบบตรวจอัตโนมัติ
        ผลงานเรียงความฉบับเต็มทุกรอบ บันทึกสะท้อนคิดของนักเรียน และผลการประเมินร่วมกับเพื่อน
        เพื่อให้คุณครู นักเรียน และผู้ปกครองเห็นภาพรวมพัฒนาการของนักเรียนคนนี้ได้ครบถ้วนในเอกสารฉบับเดียว
      </p>
      <p>
        รายงานแบ่งออกเป็น 10 ส่วนตามสารบัญด้านล่าง เนื้อหาทุกส่วนคำนวณและเรียบเรียงจากข้อมูลจริงในระบบ
        ส่วนใดที่ยังไม่มีข้อมูลเพียงพอจะระบุไว้ตรง ๆ ในส่วนนั้น
      </p>
    </div>
    <?php
}

/** สารบัญ — ลิงก์ไปยังแต่ละส่วนตามลำดับจริงของรายงาน */
function rs_toc() {
    ?>
    <div class="front-sec toc">
      <h2 class="sec-title front-title">สารบัญ</h2>
      <ol class="toc-list">
        <?php foreach (rs_section_list() as $s): ?>
        <li><a href="#<?php echo rp_esc($s['id']); ?>">
          <span class="toc-title"><?php echo rp_esc($s['title']); ?></span>
        </a></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <?php
}

/* --------------------------------------------------- 1) ผลสัมฤทธิ์ */

function rs_achievement($sum, $no = '1') {
    $g     = $sum['growth'];
    $class = $sum['class'];
    rs_title($no, 'ผลสัมฤทธิ์', 'คะแนนเต็ม 60 คะแนน ตามเกณฑ์การประเมินของครูผู้สอน', 'sec-achievement');
    ?>
    <div class="cards">
      <div class="card">
        <div class="lbl">คะแนนก่อนเรียน</div>
        <div class="val"><?php echo rp_num($g['pre'], 1); ?></div>
        <div class="foot"><?php echo $g['level_from'] !== '' ? rp_esc($g['level_from']) : 'ยังไม่มีคะแนน'; ?></div>
      </div>
      <div class="card">
        <div class="lbl">คะแนนหลังเรียน</div>
        <div class="val"><?php echo rp_num($g['post'], 1); ?></div>
        <div class="foot"><?php echo $g['level_to'] !== '' ? rp_esc($g['level_to']) : 'ยังไม่มีคะแนน'; ?></div>
      </div>
      <div class="card">
        <div class="lbl">พัฒนาการ</div>
        <div class="val"><?php echo rp_diff($g['diff'], 1); ?></div>
        <div class="foot"><?php echo ($g['pct'] === null) ? 'ต้องมีคะแนนทั้งสองรอบ' : 'คิดเป็น ' . rp_num($g['pct'], 1) . '%'; ?></div>
      </div>
      <div class="card">
        <div class="lbl">เทียบค่าเฉลี่ยของชั้น</div>
        <div class="val"><?php echo rp_diff($sum['achievement']['posttest']['vs_class'], 1); ?></div>
        <div class="foot">ชั้นเรียนเฉลี่ย <?php echo rp_num($class['phase']['posttest']['mean'] ?? null, 1); ?> คะแนน</div>
      </div>
      <div class="card">
        <div class="lbl">ลำดับในชั้น (หลังเรียน)</div>
        <div class="val"><?php echo $sum['rank_post'] ? (int)$sum['rank_post']['rank'] : '—'; ?></div>
        <div class="foot"><?php echo $sum['rank_post'] ? 'จาก ' . (int)$sum['rank_post']['of'] . ' คนที่มีคะแนน' : 'ยังไม่มีคะแนน'; ?></div>
      </div>
      <div class="card">
        <div class="lbl">ความครบถ้วนของงาน</div>
        <div class="val"><?php echo (int)$sum['done']; ?>/<?php echo (int)$sum['done_total']; ?></div>
        <div class="foot">ชิ้นงานที่ส่งแล้ว</div>
      </div>
    </div>

    <h3 class="sub-title">คะแนนรายรอบการประเมิน (ครูผู้สอน)</h3>
    <table>
      <thead>
        <tr>
          <th style="width:34%">รอบการประเมิน</th>
          <th class="num" style="width:16%">คะแนนที่ครูให้</th>
          <th class="num" style="width:16%">เฉลี่ยทั้งชั้น</th>
          <th class="num" style="width:14%">สูง/ต่ำกว่า</th>
          <th style="width:20%">ระดับคุณภาพ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sum['achievement'] as $ph => $a): ?>
        <tr<?php echo ($ph === 'posttest') ? ' class="hi"' : ''; ?>>
          <td><?php echo rp_esc($a['label']); ?><?php echo rp_bar($a['teacher'], 60, $ph === 'pretest' ? 'pre' : 'post'); ?></td>
          <td class="num"><b><?php echo rp_num($a['teacher'], 1); ?></b></td>
          <td class="num"><?php echo rp_num($a['class_mean'], 1); ?></td>
          <td class="num"><?php echo rp_diff($a['vs_class'], 1); ?></td>
          <td><?php echo rp_level_badge($a['level']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="note">
      คะแนนในตารางนี้เป็นคะแนนของคุณครูผู้สอนเท่านั้น ซึ่งเป็นคะแนนที่ใช้ตัดสินผลสัมฤทธิ์ตามเกณฑ์ของสถานศึกษา
      (คะแนนที่นักเรียนประเมินตนเองและเพื่อนประเมินให้ ดูได้ในส่วนที่ 4 "คะแนนรายเกณฑ์จากระบบตรวจอัตโนมัติ"
      และส่วนที่ 9 "การประเมินร่วมกับเพื่อน")
    </div>
    <?php
}

/* --------------------------------------------------- 2) บทวิเคราะห์ */

function rs_analysis($ins, $no = '2') {
    rs_title($no, 'บทวิเคราะห์รายบุคคล', 'สรุปจากข้อมูลจริงทั้งหมดของนักเรียนคนนี้', 'sec-analysis');
    if (!$ins) { rs_empty('ยังมีข้อมูลไม่พอสำหรับวิเคราะห์'); return; }
    foreach ($ins as $it) {
        $tone = in_array($it['tone'] ?? 'info', ['good', 'warn', 'info'], true) ? $it['tone'] : 'info';
        echo '<div class="ins ' . $tone . '"><h4>' . rp_esc($it['title']) . '</h4>';
        if (trim((string)($it['text'] ?? '')) !== '') echo '<div>' . rp_esc($it['text']) . '</div>';
        if (!empty($it['items'])) {
            echo '<ul>';
            foreach ($it['items'] as $li) echo '<li>' . rp_esc($li) . '</li>';
            echo '</ul>';
        }
        echo '</div>';
    }
}

/* --------------------------------------------------- 3) สถิติรายเกณฑ์ */

function rs_criteria($sum, $no = '3') {
    $g = $sum['growth'];
    rs_title($no, 'สถิติรายเกณฑ์', 'เทียบก่อนเรียนกับหลังเรียน และเทียบกับค่าเฉลี่ยของชั้น', 'sec-criteria');
    ?>
    <table>
      <thead>
        <tr>
          <th style="width:34%">เกณฑ์การประเมิน</th>
          <th class="num" style="width:9%">เต็ม</th>
          <th class="num" style="width:11%">ก่อนเรียน</th>
          <th class="num" style="width:11%">หลังเรียน</th>
          <th class="num" style="width:11%">พัฒนาการ</th>
          <th class="num" style="width:12%">เฉลี่ยทั้งชั้น<br>(หลังเรียน)</th>
          <th style="width:12%">สัดส่วนที่ทำได้</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (report_criteria() as $c):
          $row = $sum['criteria'][$c['id']]; ?>
        <tr>
          <td><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?></td>
          <td class="num"><?php echo rp_num($c['max'], 0); ?></td>
          <td class="num"><?php echo rp_num($row['pre'], 2); ?></td>
          <td class="num"><b><?php echo rp_num($row['post'], 2); ?></b></td>
          <td class="num"><?php echo rp_diff($row['diff'], 2); ?></td>
          <td class="num"><?php echo rp_num($row['class_post'], 2); ?></td>
          <td><?php echo rp_bar($row['post'], $c['max'], 'post'); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td>รวม</td>
          <td class="num">60</td>
          <td class="num"><?php echo rp_num($g['pre'], 2); ?></td>
          <td class="num"><?php echo rp_num($g['post'], 2); ?></td>
          <td class="num"><?php echo rp_diff($g['diff'], 2); ?></td>
          <td class="num"><?php echo rp_num($sum['class']['phase']['posttest']['mean'] ?? null, 2); ?></td>
          <td><?php echo rp_bar($g['post'], 60, 'post'); ?></td>
        </tr>
      </tfoot>
    </table>
    <div class="note"><?php echo rp_esc(report_criteria_explanation($sum)); ?></div>

    <div class="twocol" style="margin-top:8px;">
      <div class="box good">
        <h4>จุดแข็งของนักเรียน</h4>
        <?php if ($sum['strong']): ?>
        <ul>
          <?php foreach ($sum['strong'] as $c): ?>
          <li><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?> — ทำได้ <?php echo rp_num($c['post'], 2); ?>/<?php echo rp_num($c['max'], 0); ?> (<?php echo rp_num($c['pct'], 0); ?>%)</li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?><div class="muted">ยังไม่มีคะแนนหลังเรียนสำหรับจัดอันดับ</div><?php endif; ?>
      </div>
      <div class="box watch">
        <h4>จุดที่ควรพัฒนาต่อ</h4>
        <?php if ($sum['weak']): ?>
        <ul>
          <?php foreach ($sum['weak'] as $c): ?>
          <li><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?> — ทำได้ <?php echo rp_num($c['post'], 2); ?>/<?php echo rp_num($c['max'], 0); ?> (<?php echo rp_num($c['pct'], 0); ?>%)</li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?><div class="muted">ยังไม่มีคะแนนหลังเรียนสำหรับจัดอันดับ</div><?php endif; ?>
      </div>
    </div>
    <?php
}

/* ------------------------------- 4) คะแนนรายเกณฑ์จากระบบตรวจอัตโนมัติ */

function rs_eval_detail($full, $no = '4') {
    rs_title($no, 'คะแนนรายเกณฑ์จากระบบตรวจอัตโนมัติ', 'แยกตามรอบ พร้อมหมายเหตุจากระบบตรวจรายเกณฑ์', 'sec-eval');
    $any = false;
    foreach (report_essay_phases() as $ph => $label) {
        $fb = $full['ai'][$ph] ?? null;
        if (!$fb || empty($fb['scores'])) continue;
        $any = true;
        ?>
        <h3 class="sub-title"><?php echo rp_esc($label); ?>
          <span class="muted" style="font-weight:400;">(ตรวจครั้งที่ <?php echo (int)($fb['review_round'] ?? 1); ?>
            · <?php echo rp_esc(rp_when($fb['created_at'])); ?>)</span>
        </h3>
        <table>
          <thead>
            <tr>
              <th style="width:26%">เกณฑ์การประเมิน</th>
              <th class="num" style="width:8%">เต็ม</th>
              <th class="num" style="width:12%">คะแนนที่ระบบให้</th>
              <th style="width:54%">หมายเหตุจากระบบตรวจ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (report_criteria() as $c):
              $sc = $fb['scores'][$c['id']] ?? null; ?>
            <tr>
              <td><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?></td>
              <td class="num"><?php echo rp_num($c['max'], 0); ?></td>
              <td class="num"><?php echo $sc ? rp_num($sc['weighted'] ?? null, 2) : '<span class="muted">—</span>'; ?></td>
              <td><?php echo ($sc && trim((string)($sc['reason'] ?? '')) !== '')
                    ? rp_esc($sc['reason']) : '<span class="muted">— ไม่มีหมายเหตุ —</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>รวม</td>
              <td class="num">60</td>
              <td class="num"><?php echo rp_num($fb['total_score'] ?? null, 2); ?></td>
              <td><?php echo rp_level_badge($fb['quality_level'] ?? ''); ?></td>
            </tr>
          </tfoot>
        </table>
        <?php if (!empty($fb['overall'])): ?>
        <div class="box info" style="margin-top:6px;">
          <h4>ความคิดเห็นภาพรวมจากระบบตรวจอัตโนมัติ</h4>
          <div class="kv"><?php echo rp_esc($fb['overall']); ?></div>
        </div>
        <?php endif; ?>
        <?php
    }
    if (!$any) rs_empty('ยังไม่เคยให้ระบบตรวจอัตโนมัติตรวจเรียงความของนักเรียนคนนี้ในรอบใดเลย');
}

/* --------------------------------------------------- 5) ผลงาน (สรุป) */

function rs_works($sum, $no = '5') {
    rs_title($no, 'ผลงานของนักเรียน', 'เรียงความทุกรอบ และผลตรวจโดยระบบตรวจอัตโนมัติ', 'sec-works');
    ?>
    <table>
      <thead>
        <tr>
          <th style="width:28%">รอบงาน</th>
          <th class="num" style="width:11%">สถานะ</th>
          <th class="num" style="width:11%">จำนวนคำ</th>
          <th class="wrap" style="width:16%">บันทึกล่าสุด</th>
          <th class="num" style="width:12%">คะแนนอัตโนมัติ</th>
          <th class="num" style="width:10%">ครั้งที่ตรวจ</th>
          <th class="num" style="width:12%">เทียบฉบับตั้งต้น</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sum['works'] as $ph => $w): ?>
        <tr>
          <td><?php echo rp_esc($w['label']); ?></td>
          <td class="num"><?php echo $w['submitted'] ? '<span class="pill lv-3">ส่งแล้ว</span>' : '<span class="pill lv-0">ยังไม่ส่ง</span>'; ?></td>
          <td class="num"><?php echo $w['submitted'] ? number_format($w['word_count']) : '—'; ?></td>
          <td class="wrap"><?php echo rp_esc($w['submitted'] ? rp_when($w['updated_at']) : '—'); ?></td>
          <td class="num"><?php echo ($w['ai_total'] === null) ? '<span class="muted">ยังไม่ตรวจ</span>'
              : rp_num($w['ai_total'], 1) . ' <span class="muted">/ ' . rp_num($w['ai_max'], 0) . '</span>'; ?></td>
          <td class="num"><?php echo $w['ai_round'] > 0 ? (int)$w['ai_round'] : '—'; ?></td>
          <td class="num"><?php echo ($w['ai_delta'] === null)
              ? '<span class="muted">—</span>'
              : rp_diff($w['ai_delta'], 2)
                . ($w['ai_base'] !== '' ? ' <span class="muted">(' . rp_esc($w['ai_base']) . ')</span>' : ''); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="note">
      คะแนนของระบบเป็นเพียงข้อมูลประกอบการพัฒนางานเขียน (เต็ม <?php echo rp_num(ai_rubric_max(), 0); ?> คะแนนเฉพาะข้อที่ระบบตรวจได้)
      ไม่ถูกนำไปรวมกับคะแนนจริงของครู · ช่อง "เทียบฉบับตั้งต้น" คือส่วนต่างจากฉบับที่ต้องนำมาเทียบตามคู่ที่ครูกำหนด
      (D1.2 เทียบ D1.1 · D2.2 เทียบ D2.1 · หลังเรียน เทียบ ก่อนเรียน) — รอบที่ไม่มีคู่เทียบจะขึ้นเป็น —
    </div>

    <h3 class="sub-title">ใบตรวจสอบความครบถ้วนของชิ้นงาน (<?php echo (int)$sum['done']; ?>/<?php echo (int)$sum['done_total']; ?> ชิ้น)</h3>
    <div class="checklist">
      <?php foreach ($sum['checklist'] as $it): ?>
      <div class="item">
        <span><?php echo rp_esc($it['label']); ?></span>
        <span class="<?php echo $it['done'] ? 'yes' : 'no'; ?>"><?php echo $it['done'] ? '✓ ส่งแล้ว' : '✗ ยังไม่ส่ง'; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php
}

/* --------------------------------------------- 6) เรียงความฉบับเต็ม */

function rs_essays($full, $no = '6') {
    $target = report_word_target();
    rs_title($no, 'เรียงความฉบับเต็มทุกรอบ', 'ข้อความที่นักเรียนบันทึกไว้ในระบบ', 'sec-essays');
    $any = false;
    foreach (report_essay_phases() as $ph => $label) {
        $e = $full['essays'][$ph] ?? null;
        if (!$e || empty($e['has_text'])) continue;
        $any = true;
        $wc  = (int)$e['word_count'];
        $note = ($wc < $target['min']) ? ' · สั้นกว่าเกณฑ์' : (($wc > $target['max']) ? ' · ยาวกว่าเกณฑ์' : ' · อยู่ในเกณฑ์');
        ?>
        <div class="essay-doc">
          <div class="head">
            <b><?php echo rp_esc($label); ?></b>
            <span class="meta"><?php echo number_format($wc); ?> คำ<?php echo rp_esc($note); ?>
              · บันทึกล่าสุด <?php echo rp_esc(rp_when($e['updated_at'])); ?></span>
          </div>
          <?php if ($e['topic'] !== ''): ?>
          <div class="topic"><b>หัวข้อที่ครูกำหนด:</b> <?php echo rp_esc($e['topic']); ?></div>
          <?php endif; ?>

          <?php if (trim($e['intro']) !== ''): ?>
          <div class="part">คำนำ</div>
          <p><?php echo nl2br(rp_esc($e['intro'])); ?></p>
          <?php endif; ?>

          <?php if ($e['body']): ?>
          <div class="part">เนื้อเรื่อง</div>
          <?php foreach ($e['body'] as $bp): ?>
          <p><?php echo nl2br(rp_esc($bp)); ?></p>
          <?php endforeach; ?>
          <?php endif; ?>

          <?php if (trim($e['conclusion']) !== ''): ?>
          <div class="part">สรุป</div>
          <p><?php echo nl2br(rp_esc($e['conclusion'])); ?></p>
          <?php endif; ?>

          <?php $fb = $full['ai'][$ph] ?? null; if ($fb): ?>
          <div class="box info" style="margin-top:8px;">
            <h4>หมายเหตุจากระบบตรวจ</h4>
            <?php if (!empty($fb['overall'])): ?>
            <div class="kv"><?php echo rp_esc($fb['overall']); ?></div>
            <?php endif; ?>
            <div class="kv">คะแนนที่ระบบให้ <?php echo rp_num($fb['total_score'] ?? null, 2); ?>/<?php echo rp_num($fb['max_score'] ?? null, 0); ?>
              <?php echo rp_level_badge($fb['quality_level'] ?? ''); ?></div>
          </div>
          <?php endif; ?>
        </div>
        <?php
    }
    if (!$any) rs_empty('ยังไม่มีเรียงความที่บันทึกไว้ในระบบ');
}

/* --------------------------------------------- 7) ผลตรวจของระบบทุกรอบ */

function rs_ai($full, $no = '7') {
    rs_title($no, 'ผลตรวจจากระบบตรวจอัตโนมัติทุกรอบงาน', 'จุดแข็ง จุดที่ควรปรับปรุง และพัฒนาการระหว่างรอบตรวจ', 'sec-ai');

    // แถวข้อมูลของทุกรอบที่มีผลตรวจ — ใช้ทำตาราง "ภาพรวมพัฒนาการ" ให้เห็นแนวโน้มทั้งหมดในสายตาเดียว
    $phaseRows = [];
    foreach (report_essay_phases() as $ph => $label) {
        $fb = $full['ai'][$ph] ?? null;
        if ($fb) $phaseRows[$ph] = ['label' => $label, 'fb' => $fb];
    }
    if (!$phaseRows) { rs_empty('ยังไม่เคยให้ระบบตรวจเรียงความของนักเรียนคนนี้'); return; }

    echo '<h3 class="sub-title">ภาพรวมพัฒนาการทุกรอบ</h3>';
    ?>
    <table style="margin-bottom:10px;">
      <thead>
        <tr>
          <th style="width:30%">รอบงาน</th>
          <th class="num" style="width:16%">คะแนนที่ระบบให้</th>
          <th style="width:22%">ระดับคุณภาพ</th>
          <th style="width:32%">เทียบกับฉบับตั้งต้น</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($phaseRows as $r):
          $fb = $r['fb'];
          $dc = $fb['draft_compare'] ?? null;
          $hasDc = !empty($dc['has_baseline']); ?>
        <tr>
          <td><?php echo rp_esc($r['label']); ?>
            <span class="muted">(ครั้งที่ <?php echo (int)($fb['review_round'] ?? 1); ?> · <?php echo rp_esc(rp_when($fb['created_at'])); ?>)</span></td>
          <td class="num"><?php echo rp_num($fb['total_score'] ?? null, 2); ?>/<?php echo rp_num($fb['max_score'] ?? null, 0); ?></td>
          <td><?php echo rp_level_badge($fb['quality_level'] ?? ''); ?></td>
          <td><?php
            if (!$hasDc) { echo '<span class="muted">— ไม่มีคู่เทียบ —</span>'; }
            else {
                echo rp_diff($dc['delta'], 2) . ' คะแนน';
                echo ($dc['delta'] > 0) ? ' (ดีขึ้น)' : (($dc['delta'] < 0) ? ' (ถอยลง)' : ' (เท่าเดิม)');
            }
          ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php foreach ($phaseRows as $ph => $r): $fb = $r['fb']; ?>
    <h3 class="sub-title"><?php echo rp_esc($r['label']); ?>
      — <?php echo rp_num($fb['total_score'], 2); ?>/<?php echo rp_num($fb['max_score'], 0); ?> คะแนน
      <?php echo rp_level_badge($fb['quality_level']); ?>
    </h3>

    <?php if (!empty($fb['overall'])): ?>
    <div class="kv" style="margin-bottom:5px;"><?php echo rp_esc($fb['overall']); ?></div>
    <?php endif; ?>

    <?php if (!empty($fb['draft_compare']['has_baseline'])): $dc = $fb['draft_compare'];
          // คู่คนละหัวข้อ (หลังเรียน↔ก่อนเรียน) เทียบที่คุณภาพเนื้อหาตามเกณฑ์ ไม่ใช่เทียบว่าแก้ข้อความตรงไหน
          $dcNewTopic = (($dc['kind'] ?? '') === 'newtopic'); ?>
    <div class="box <?php echo ($dc['delta'] > 0) ? 'good' : (($dc['delta'] < 0) ? 'watch' : 'info'); ?>" style="margin-bottom:6px;">
      <h4><?php echo $dcNewTopic ? 'พัฒนาการเทียบกับ ' : 'เทียบกับ '; ?><?php echo rp_esc($dc['label']); ?><?php
          echo $dcNewTopic ? ' (คนละหัวข้อ — เทียบที่คุณภาพเนื้อหาตามเกณฑ์)' : ''; ?></h4>
      <div class="kv">คะแนน <?php echo rp_num($dc['base_total'], 2); ?> → <?php echo rp_num($dc['total'], 2); ?>
        <?php echo rp_diff($dc['delta'], 2); ?>
        · ดีขึ้น <?php echo (int)$dc['up']; ?> ข้อ · ลดลง <?php echo (int)$dc['down']; ?> ข้อ
        · เท่าเดิม <?php echo (int)$dc['same']; ?> ข้อ</div>
      <?php if (!empty($dc['same_text'])): ?>
      <div class="kv"><b>ข้อความเหมือนฉบับตั้งต้นทุกตัวอักษร</b> —
        <?php echo $dcNewTopic ? 'ทั้งที่เป็นคนละหัวข้อ ควรตรวจสอบว่าส่งงานผิดฉบับหรือไม่' : 'นักเรียนยังไม่ได้แก้ไขงาน'; ?></div>
      <?php elseif (!empty($dc['identical'])): ?>
      <div class="kv"><b>คะแนนรายข้อเท่ากันทุกข้อ</b> —
        <?php echo $dcNewTopic ? 'ความสามารถในการเขียนยังอยู่ระดับเดิมทุกด้าน' : 'งานเปลี่ยนแล้วแต่ยังไม่ถึงระดับถัดไปสักข้อ'; ?></div>
      <?php endif; ?>
      <?php if (!empty($dc['comment'])): ?><div class="kv"><?php echo rp_esc($dc['comment']); ?></div><?php endif; ?>
      <?php
      // ข้อที่คะแนนขยับ — รวมเป็นบรรทัดเดียวต่อข้อ อ่านง่ายกว่าไล่ทีละข้อ
      $moved = [];
      foreach (($dc['criteria'] ?? []) as $c) {
          if ($c['dir'] === 'same' && trim((string)$c['note']) === '') continue;
          $arrow = ($c['dir'] === 'up') ? '↑' : (($c['dir'] === 'down') ? '↓' : '→');
          $line = $arrow . ' ข้อ ' . $c['id'] . ' ' . $c['name'] . ' (' . rp_num($c['base_weighted'], 2) . '→' . rp_num($c['weighted'], 2) . ')';
          if (trim((string)$c['note']) !== '') $line .= ': ' . $c['note'];
          $moved[] = $line;
      }
      if ($moved): ?>
      <ul class="mb-0"><?php foreach ($moved as $ln) echo '<li>' . rp_esc($ln) . '</li>'; ?></ul>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="twocol">
      <div class="box good">
        <h4>จุดแข็งที่ระบบพบ</h4>
        <?php if (!empty($fb['strengths'])): ?>
        <ul><?php foreach ($fb['strengths'] as $s): ?><li><?php echo rp_esc($s); ?></li><?php endforeach; ?></ul>
        <?php else: ?><div class="muted">— ไม่มีข้อมูล —</div><?php endif; ?>
      </div>
      <div class="box watch">
        <h4>จุดที่ควรปรับปรุง</h4>
        <?php if (!empty($fb['improvements'])): ?>
        <ul>
          <?php foreach ($fb['improvements'] as $im):
            $line = ($im['criterion'] !== '' ? 'ข้อ ' . $im['criterion'] . ': ' : '') . $im['issue'];
            if (!empty($im['suggestion'])) $line .= ' — แนวทางแก้: ' . $im['suggestion']; ?>
          <li><?php echo rp_esc($line); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?><div class="muted">— ไม่มีข้อมูล —</div><?php endif; ?>
      </div>
    </div>

    <?php if (!empty($fb['next_steps'])): ?>
    <div class="box info" style="margin-top:6px;">
      <h4>สิ่งที่ควรทำต่อในงานเขียนชิ้นถัดไป</h4>
      <ul><?php foreach ($fb['next_steps'] as $s): ?><li><?php echo rp_esc($s); ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($fb['encouragement'])): ?>
    <div class="note"><?php echo rp_esc($fb['encouragement']); ?></div>
    <?php endif; ?>
    <?php endforeach;
}

/* ------------------------------------- 8) เครื่องมือสะท้อนคิด */

function rs_reflection($full, $no = '8') {
    rs_title($no, 'บันทึกสะท้อนคิดของนักเรียน', 'ปัญหาการเขียน · การตรวจสอบตนเอง · การสะท้อนการเรียนรู้', 'sec-reflect');
    $any = false;
    foreach ([1, 2] as $u) {
        $prob = $full['problems'][$u]    ?? null;
        $chk  = $full['checklists'][$u]  ?? null;
        $ref  = $full['reflections'][$u] ?? null;
        if (!$prob && !$chk && !$ref) continue;
        $any = true;
        echo '<h3 class="sub-title">หน่วยการเรียนที่ ' . $u . '</h3>';

        if ($prob && $prob['items']) { ?>
        <table>
          <thead><tr><th style="width:30%">เกณฑ์</th><th style="width:35%">ปัญหาที่นักเรียนพบ</th>
                     <th style="width:35%">แนวทางแก้ที่วางไว้</th></tr></thead>
          <tbody>
            <?php foreach ($prob['items'] as $it): ?>
            <tr>
              <td><?php echo rp_esc($it['label']); ?></td>
              <td><?php echo $it['problem'] !== '' ? nl2br(rp_esc($it['problem'])) : '<span class="muted">—</span>'; ?></td>
              <td><?php echo $it['solution'] !== '' ? nl2br(rp_esc($it['solution'])) : '<span class="muted">—</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php }

        if ($chk) {
            $pillOf = function ($v) {
                if ($v === 'ครบถ้วน')      return '<span class="pill lv-3">ครบถ้วน</span>';
                if ($v === 'บางส่วน')      return '<span class="pill lv-2">บางส่วน</span>';
                if ($v === 'ต้องปรับปรุง') return '<span class="pill lv-0">ต้องปรับปรุง</span>';
                return ($v === '') ? '<span class="muted">—</span>' : '<span class="pill lv-2">' . rp_esc($v) . '</span>';
            };
            ?>
            <h4 class="sub-title" style="font-size:15pt;">รายการตรวจสอบตนเอง</h4>
            <div class="checklist">
              <?php foreach ($chk['items'] as $it): ?>
              <div class="item"><span><?php echo rp_esc($it['label']); ?></span><span><?php echo $pillOf($it['value']); ?></span></div>
              <?php endforeach; ?>
            </div>
            <?php if ($chk['notes'] !== ''): ?>
            <div class="note"><b>บันทึกเพิ่มเติมของนักเรียน:</b> <?php echo nl2br(rp_esc($chk['notes'])); ?></div>
            <?php endif;
        }

        if ($ref) {
            $has = false;
            foreach ($ref['fields'] as $f) { if ($f['text'] !== '') $has = true; }
            if ($has) { ?>
            <h4 class="sub-title" style="font-size:15pt;">บันทึกสะท้อนการเรียนรู้</h4>
            <div class="twocol">
              <?php foreach ($ref['fields'] as $f): ?>
              <div class="box info">
                <h4><?php echo rp_esc($f['label']); ?></h4>
                <div><?php echo $f['text'] !== '' ? nl2br(rp_esc($f['text'])) : '<span class="muted">— ยังไม่ได้เขียน —</span>'; ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php }
        }
    }
    if (!$any) rs_empty('ยังไม่มีบันทึกสะท้อนคิดในหน่วยใดเลย');
}

/* ------------------------------------- 9) การประเมินเพื่อน */

function rs_peer($full, $no = '9') {
    rs_title($no, 'การประเมินร่วมกับเพื่อน', 'ทั้งที่เพื่อนประเมินให้ และที่นักเรียนไปประเมินให้เพื่อน', 'sec-peer');
    $recv  = $full['peer_received'] ?? [];
    $given = $full['peer_given'] ?? [];

    // สรุปข้อมูลสะท้อนคิดของนักเรียนเอง (ปัญหาการเขียน · การตรวจสอบตนเอง · การสะท้อนการเรียนรู้)
    // เป็นย่อหน้าเดียว ไว้เทียบมุมมองของนักเรียนเองกับสิ่งที่เพื่อนประเมินให้ด้านล่าง
    $synth = report_reflection_synthesis($full);
    if ($synth) {
        echo '<h3 class="sub-title">สรุปมุมมองของนักเรียนเอง (เทียบกับการประเมินของเพื่อน)</h3>';
        foreach ($synth as $s) {
            echo '<div class="kv" style="margin-bottom:6px;"><b>หน่วยการเรียนที่ ' . (int)$s['unit'] . ':</b> '
               . rp_esc($s['text']) . '</div>';
        }
    }

    if ($recv) {
        echo '<h3 class="sub-title">ผลการประเมินที่ได้รับจากเพื่อน (' . count($recv) . ' คน)</h3>';
        $show = array_slice($recv, 0, 4);
        ?>
        <table>
          <thead>
            <tr><th style="width:34%">เกณฑ์</th>
              <?php foreach ($show as $r): ?><th class="wrap"><?php echo rp_esc($r['reviewer_name'] ?: 'เพื่อน'); ?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach (report_peer_criteria() as $k => $label): ?>
            <tr>
              <td><?php echo rp_esc($label); ?></td>
              <?php foreach ($show as $r): ?>
              <td class="num"><?php echo ($r['scores'][$k]['value'] ?? '') !== ''
                  ? rp_esc($r['scores'][$k]['value']) : '<span class="muted">—</span>'; ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php
        foreach ($show as $r) {
            if ($r['strength'] === '' && $r['improvement'] === '' && $r['encouragement'] === '') continue;
            echo '<div class="box good" style="margin-top:6px;"><h4>ข้อคิดเห็นจาก '
               . rp_esc($r['reviewer_name'] ?: 'เพื่อน') . '</h4><div class="kv">';
            if ($r['strength'] !== '')      echo '<div><b>จุดแข็ง:</b> ' . nl2br(rp_esc($r['strength'])) . '</div>';
            if ($r['improvement'] !== '')   echo '<div><b>ควรปรับปรุง:</b> ' . nl2br(rp_esc($r['improvement'])) . '</div>';
            if ($r['encouragement'] !== '') echo '<div><b>กำลังใจ:</b> ' . nl2br(rp_esc($r['encouragement'])) . '</div>';
            echo '</div></div>';
        }
    } else {
        rs_empty('ยังไม่มีเพื่อนคนใดประเมินผลงานของนักเรียนคนนี้');
    }

    if ($given) {
        echo '<h3 class="sub-title">ผลงานที่นักเรียนไปช่วยประเมินให้เพื่อน (' . count($given) . ' คน)</h3>';
        ?>
        <table>
          <thead><tr><th style="width:26%">เจ้าของผลงาน</th><th style="width:37%">จุดแข็งที่นักเรียนเห็น</th>
                     <th style="width:37%">ข้อเสนอแนะที่ให้เพื่อน</th></tr></thead>
          <tbody>
            <?php foreach ($given as $r): ?>
            <tr>
              <td><?php echo rp_esc($r['owner_name'] ?: $r['owner_id']); ?></td>
              <td><?php echo $r['strength'] !== '' ? nl2br(rp_esc($r['strength'])) : '<span class="muted">—</span>'; ?></td>
              <td><?php echo $r['improvement'] !== '' ? nl2br(rp_esc($r['improvement'])) : '<span class="muted">—</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php
    }
}

/* ------------------------------------- 10) ภาพรวมทั้งหมด */

function rs_overview($sum, $full, $ins, $no = '10') {
    rs_title($no, 'ภาพรวมทั้งหมด', 'สรุปทุกส่วนของรายงานนี้ไว้ในที่เดียว', 'sec-overview');
    $g = $sum['growth'];
    ?>
    <div class="cards">
      <div class="card">
        <div class="lbl">ผลสัมฤทธิ์ (ครูให้)</div>
        <div class="val"><?php echo rp_num($g['post'], 1); ?>/60</div>
        <div class="foot"><?php echo $g['level_to'] !== '' ? rp_esc($g['level_to']) : 'ยังไม่มีคะแนน'; ?></div>
      </div>
      <div class="card">
        <div class="lbl">พัฒนาการก่อน→หลังเรียน</div>
        <div class="val"><?php echo rp_diff($g['diff'], 1); ?></div>
        <div class="foot"><?php echo ($g['pct'] === null) ? '—' : 'คิดเป็น ' . rp_num($g['pct'], 1) . '%'; ?></div>
      </div>
      <div class="card">
        <div class="lbl">ความครบถ้วนของงาน</div>
        <div class="val"><?php echo (int)$sum['done']; ?>/<?php echo (int)$sum['done_total']; ?></div>
        <div class="foot">ชิ้นงานที่ส่งแล้ว</div>
      </div>
      <div class="card">
        <div class="lbl">ผลตรวจจากระบบตรวจอัตโนมัติ</div>
        <div class="val"><?php echo count($full['ai'] ?? []); ?></div>
        <div class="foot">รอบงานที่ระบบตรวจแล้ว</div>
      </div>
      <div class="card">
        <div class="lbl">เพื่อนประเมินให้</div>
        <div class="val"><?php echo count($full['peer_received'] ?? []); ?></div>
        <div class="foot">คน</div>
      </div>
      <div class="card">
        <div class="lbl">ประเมินให้เพื่อน</div>
        <div class="val"><?php echo count($full['peer_given'] ?? []); ?></div>
        <div class="foot">คน</div>
      </div>
    </div>

    <h3 class="sub-title">บทสรุปภาพรวม</h3>
    <?php
    $good = count(array_filter($ins, function ($i) { return ($i['tone'] ?? '') === 'good'; }));
    $warn = count(array_filter($ins, function ($i) { return ($i['tone'] ?? '') === 'warn'; }));

    $parts = [];
    if ($g['diff'] !== null) {
        $parts[] = ((float)$g['diff'] > 0)
            ? 'นักเรียนคนนี้มีพัฒนาการโดยรวมในทางบวก คะแนนผลสัมฤทธิ์เพิ่มขึ้นจาก ' . rp_num($g['pre'], 1)
              . ' เป็น ' . rp_num($g['post'], 1) . ' คะแนน จากคะแนนเต็ม 60 คะแนน'
            : (((float)$g['diff'] < 0)
                ? 'คะแนนผลสัมฤทธิ์ของนักเรียนคนนี้ลดลงจาก ' . rp_num($g['pre'], 1) . ' เหลือ ' . rp_num($g['post'], 1)
                  . ' คะแนน ควรได้รับการดูแลเพิ่มเติม'
                : 'คะแนนผลสัมฤทธิ์ของนักเรียนคนนี้คงที่ที่ ' . rp_num($g['post'], 1) . ' คะแนน ทั้งก่อนและหลังเรียน');
    } else {
        $parts[] = 'ยังไม่มีคะแนนของคุณครูครบทั้งก่อนเรียนและหลังเรียน จึงยังสรุปพัฒนาการโดยรวมไม่ได้';
    }
    if (!empty($sum['strong'])) {
        $parts[] = 'จุดแข็งที่ชัดเจนที่สุดคือเกณฑ์ ' . implode(', ', array_map(function ($c) { return $c['id']; }, array_slice($sum['strong'], 0, 3)));
    }
    if (!empty($sum['weak'])) {
        $parts[] = 'ส่วนเกณฑ์ที่ควรพัฒนาต่อคือ ' . implode(', ', array_map(function ($c) { return $c['id']; }, array_slice($sum['weak'], 0, 3)));
    }
    $parts[] = 'ส่งงานแล้ว ' . (int)$sum['done'] . ' จาก ' . (int)$sum['done_total'] . ' ชิ้น'
             . (((int)$sum['done'] >= (int)$sum['done_total']) ? ' ครบถ้วนทุกชิ้น' : ' ยังขาดอยู่บางส่วน');
    if (!empty($sum['latest_ai']['next_steps'])) {
        $parts[] = 'ระบบตรวจอัตโนมัติแนะนำให้ทำต่อคือ ' . reset($sum['latest_ai']['next_steps']);
    }
    $synth = report_reflection_synthesis($full);
    if ($synth) {
        $parts[] = 'ด้านการสะท้อนคิดของนักเรียนเอง ' . implode(' ', array_map(function ($s) { return $s['text']; }, $synth));
    }
    if ($good + $warn > 0) {
        $parts[] = 'เมื่อพิจารณาบทวิเคราะห์รายบุคคลทั้งหมด พบประเด็นเชิงบวก ' . $good . ' ประเด็น และประเด็นที่ควรดูแลเพิ่มเติม ' . $warn . ' ประเด็น';
    }
    echo '<p style="line-height:1.9;">' . rp_esc(implode(' ', $parts)) . '</p>';
    ?>
    <div class="note">
      สรุปนี้รวบรวมข้อมูลจากทุกส่วนของรายงานฉบับนี้ (ส่วนที่ 1-9) เข้าด้วยกัน — โปรดดูรายละเอียดของแต่ละหัวข้อในส่วนที่เกี่ยวข้องด้านบน
    </div>
    <?php
}

/* ------------------------------------- ท้ายเอกสาร */

function rs_signature($sum) {
    ?>
    <div class="signrow">
      <div class="sign"><div class="line"></div>ลงชื่อครูผู้สอน</div>
      <div class="sign"><div class="line"></div>ลงชื่อนักเรียน</div>
      <div class="sign"><div class="line"></div>ลงชื่อผู้ปกครองรับทราบ</div>
    </div>
    <div class="foot-note">
      รายงานฉบับนี้สร้างจากข้อมูลในระบบประเมินการเขียนเรียงความ เมื่อ <?php echo rp_esc(rp_thai_date()); ?>
      · ค่าเฉลี่ยของชั้นคิดจากนักเรียน <?php echo (int)$sum['class']['count']; ?> คนที่อยู่ในขอบเขตของรายงานนี้
      · บทวิเคราะห์คำนวณจากข้อมูลจริงในระบบทั้งหมด
    </div>
    <?php
}
