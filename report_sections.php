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

    <h3 class="sub-title">คะแนนรายรอบการประเมิน</h3>
    <table>
      <thead>
        <tr>
          <th style="width:24%">รอบการประเมิน</th>
          <th class="num" style="width:11%">ครูประเมิน</th>
          <th class="num" style="width:11%">ตนเอง</th>
          <th class="num" style="width:11%">เพื่อน</th>
          <th class="num" style="width:11%">ผู้เชี่ยวชาญ</th>
          <th class="num" style="width:12%">เฉลี่ยทั้งชั้น</th>
          <th class="num" style="width:10%">สูง/ต่ำกว่า</th>
          <th style="width:10%">ระดับคุณภาพ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sum['achievement'] as $ph => $a): ?>
        <tr<?php echo ($ph === 'posttest') ? ' class="hi"' : ''; ?>>
          <td><?php echo rp_esc($a['label']); ?><?php echo rp_bar($a['teacher'], 60, $ph === 'pretest' ? 'pre' : 'post'); ?></td>
          <td class="num"><b><?php echo rp_num($a['teacher'], 1); ?></b></td>
          <td class="num"><?php echo rp_num($a['self'], 1); ?></td>
          <td class="num"><?php echo rp_num($a['peer'], 1); ?></td>
          <td class="num"><?php echo rp_num($a['expert'], 1); ?></td>
          <td class="num"><?php echo rp_num($a['class_mean'], 1); ?></td>
          <td class="num"><?php echo rp_diff($a['vs_class'], 1); ?></td>
          <td><?php echo rp_level_badge($a['level']); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="note">
      คะแนนของครูผู้สอนเป็นคะแนนที่ใช้ตัดสินผลสัมฤทธิ์ ส่วนคะแนนของตนเองและเพื่อนเป็นข้อมูลประกอบ
      เพื่อให้เห็นว่านักเรียนประเมินงานของตนเองใกล้เคียงกับครูเพียงใด
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

/* ------------------------------- 4) คะแนนรายเกณฑ์จากผู้ประเมินทุกฝ่าย */

function rs_eval_detail($full, $no = '4') {
    rs_title($no, 'คะแนนรายเกณฑ์จากผู้ประเมินทุกฝ่าย', 'ครู · ตนเอง · เพื่อน · ผู้เชี่ยวชาญ แยกตามรอบ', 'sec-eval');
    $any = false;
    foreach (report_eval_phases() as $ph => $label) {
        $rows = $full['evals'][$ph] ?? [];
        if (!$rows) continue;
        $any = true;
        echo '<h3 class="sub-title">' . rp_esc($label) . '</h3>';
        ?>
        <table>
          <thead>
            <tr>
              <th style="width:32%">เกณฑ์การประเมิน</th>
              <th class="num" style="width:8%">เต็ม</th>
              <?php foreach ($rows as $r): ?>
              <th class="wrap"><?php echo rp_esc($r['type_label']); ?><br>
                <span class="muted" style="font-weight:400;"><?php echo rp_esc($r['evaluator_name'] ?: '—'); ?></span></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach (report_criteria() as $c): ?>
            <tr>
              <td><?php echo rp_esc($c['id'] . ' ' . $c['name']); ?></td>
              <td class="num"><?php echo rp_num($c['max'], 0); ?></td>
              <?php foreach ($rows as $r): ?>
              <td class="num"><?php echo rp_num($r['scores'][$c['id']] ?? null, 2); ?></td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td>รวม</td>
              <td class="num">60</td>
              <?php foreach ($rows as $r): ?>
              <td class="num"><?php echo rp_num($r['total'], 2); ?></td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <td colspan="2">ระดับคุณภาพ · วันที่ประเมิน</td>
              <?php foreach ($rows as $r): ?>
              <td class="wrap"><?php echo rp_level_badge($r['level']); ?><br>
                <span class="muted"><?php echo rp_esc(rp_when($r['timestamp'])); ?></span></td>
              <?php endforeach; ?>
            </tr>
          </tfoot>
        </table>
        <?php
        // ความคิดเห็นเชิงบรรยายที่แนบมากับการประเมินรอบนี้ (ส่วนใหญ่มาจากเพื่อน)
        foreach ($rows as $r) {
            if ($r['strength'] === '' && $r['improvement'] === '' && $r['encouragement'] === '') continue;
            echo '<div class="box info" style="margin-top:6px;"><h4>ความคิดเห็นจาก' . rp_esc($r['type_label'])
               . ($r['evaluator_name'] !== '' ? ' · ' . rp_esc($r['evaluator_name']) : '') . '</h4><div class="kv">';
            if ($r['strength'] !== '')      echo '<div><b>จุดแข็ง:</b> ' . rp_esc($r['strength']) . '</div>';
            if ($r['improvement'] !== '')   echo '<div><b>ควรปรับปรุง:</b> ' . rp_esc($r['improvement']) . '</div>';
            if ($r['encouragement'] !== '') echo '<div><b>กำลังใจ:</b> ' . rp_esc($r['encouragement']) . '</div>';
            echo '</div></div>';
        }
    }
    if (!$any) rs_empty('ยังไม่มีการประเมินให้คะแนนในรอบใดเลย');
}

/* --------------------------------------------------- 5) ผลงาน (สรุป) */

function rs_works($sum, $no = '5') {
    rs_title($no, 'ผลงานของนักเรียน', 'เรียงความทุกรอบ และผลตรวจโดยผู้ช่วย AI', 'sec-works');
    ?>
    <table>
      <thead>
        <tr>
          <th style="width:28%">รอบงาน</th>
          <th class="num" style="width:11%">สถานะ</th>
          <th class="num" style="width:11%">จำนวนคำ</th>
          <th class="wrap" style="width:16%">บันทึกล่าสุด</th>
          <th class="num" style="width:12%">คะแนน AI</th>
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
      คะแนนของ AI เป็นเพียงข้อมูลประกอบการพัฒนางานเขียน (เต็ม <?php echo rp_num(ai_rubric_max(), 0); ?> คะแนนเฉพาะข้อที่ AI ตรวจได้)
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
        </div>
        <?php
    }
    if (!$any) rs_empty('ยังไม่มีเรียงความที่บันทึกไว้ในระบบ');
}

/* --------------------------------------------- 7) ผลตรวจของ AI ทุกรอบ */

function rs_ai($full, $no = '7') {
    rs_title($no, 'ผลตรวจจากผู้ช่วย AI ทุกรอบงาน', 'จุดแข็ง จุดที่ควรปรับปรุง และพัฒนาการระหว่างรอบตรวจ', 'sec-ai');
    $any = false;
    foreach (report_essay_phases() as $ph => $label) {
        $fb = $full['ai'][$ph] ?? null;
        if (!$fb) continue;
        $any = true;
        ?>
        <h3 class="sub-title"><?php echo rp_esc($label); ?>
          — <?php echo rp_num($fb['total_score'], 2); ?>/<?php echo rp_num($fb['max_score'], 0); ?> คะแนน
          <?php echo rp_level_badge($fb['quality_level']); ?>
          <span class="muted" style="font-weight:400;">(ตรวจครั้งที่ <?php echo (int)($fb['review_round'] ?? 1); ?>
            · <?php echo rp_esc(rp_when($fb['created_at'])); ?>)</span>
        </h3>

        <?php if (!empty($fb['overall'])): ?>
        <div class="kv" style="margin-bottom:5px;"><?php echo rp_esc($fb['overall']); ?></div>
        <?php endif; ?>

        <?php if (!empty($fb['draft_compare']['has_baseline'])): $dc = $fb['draft_compare']; ?>
        <div class="box <?php echo ($dc['delta'] > 0) ? 'good' : (($dc['delta'] < 0) ? 'watch' : 'info'); ?>" style="margin-bottom:6px;">
          <h4>เทียบกับ <?php echo rp_esc($dc['label']); ?></h4>
          <div class="kv">คะแนน <?php echo rp_num($dc['base_total'], 2); ?> → <?php echo rp_num($dc['total'], 2); ?>
            <?php echo rp_diff($dc['delta'], 2); ?>
            · <?php echo ($dc['delta'] > 0) ? 'ดีขึ้นตามที่ควรเป็น'
                 : (($dc['delta'] < 0) ? 'คะแนนถอยลง' : 'คะแนนเท่าเดิม ยังไม่ดีขึ้น'); ?>
            · ดีขึ้น <?php echo (int)$dc['up']; ?> ข้อ · ลดลง <?php echo (int)$dc['down']; ?> ข้อ
            · เท่าเดิม <?php echo (int)$dc['same']; ?> ข้อ</div>
          <?php if (!empty($dc['same_text'])): ?>
          <div class="kv"><b>ข้อความเหมือนฉบับตั้งต้นทุกตัวอักษร</b> — นักเรียนยังไม่ได้แก้ไขงาน</div>
          <?php elseif (!empty($dc['identical'])): ?>
          <div class="kv"><b>คะแนนรายข้อเท่ากันทุกข้อ</b> — งานเปลี่ยนแล้วแต่ยังไม่ถึงระดับถัดไปสักข้อ</div>
          <?php endif; ?>
          <?php if (!empty($dc['comment'])): ?><div class="kv"><?php echo rp_esc($dc['comment']); ?></div><?php endif; ?>
          <?php
          // ข้อที่คะแนนขยับ พร้อมข้อความที่ AI ยกมาเทียบให้เห็นว่าต่างกันตรงไหน
          foreach (($dc['criteria'] ?? []) as $c) {
              if ($c['dir'] === 'same' && trim((string)$c['note']) === '') continue;
              $head = 'ข้อ ' . $c['id'] . ' ' . $c['name'];
              $body = ($c['dir'] === 'up') ? 'ดีขึ้น ' : (($c['dir'] === 'down') ? 'ลดลง ' : 'เท่าเดิม ');
              $body .= rp_num($c['base_weighted'], 2) . ' → ' . rp_num($c['weighted'], 2);
              if (trim((string)$c['note']) !== '') $body .= ' · ' . $c['note'];
              echo '<div class="kv"><b>' . rp_esc($head) . ':</b> ' . rp_esc($body) . '</div>';
          }
          ?>
        </div>
        <?php endif; ?>

        <div class="twocol">
          <div class="box good">
            <h4>จุดแข็งที่ AI พบ</h4>
            <?php if (!empty($fb['strengths'])): ?>
            <ul><?php foreach ($fb['strengths'] as $s): ?><li><?php echo rp_esc($s); ?></li><?php endforeach; ?></ul>
            <?php else: ?><div class="muted">— ไม่มีข้อมูล —</div><?php endif; ?>
          </div>
          <div class="box watch">
            <h4>จุดที่ควรปรับปรุง</h4>
            <?php if (!empty($fb['improvements'])): ?>
            <ul>
              <?php foreach ($fb['improvements'] as $im): ?>
              <li><?php echo rp_esc(($im['criterion'] !== '' ? 'ข้อ ' . $im['criterion'] . ': ' : '') . $im['issue']); ?>
                <?php if (!empty($im['suggestion'])): ?><div class="muted">แนวทางแก้: <?php echo rp_esc($im['suggestion']); ?></div><?php endif; ?>
                <?php if (!empty($im['example'])): ?><div class="muted">ตัวอย่างหลังแก้: <?php echo rp_esc($im['example']); ?></div><?php endif; ?>
              </li>
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
        <?php
    }
    if (!$any) rs_empty('ยังไม่เคยให้ AI ตรวจเรียงความของนักเรียนคนนี้');
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
