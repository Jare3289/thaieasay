<?php
/**
 * report_analysis.php — "บทวิเคราะห์รายบุคคล" ของรายงานผลการเรียนรู้
 * ---------------------------------------------------------------------------
 * อ่านข้อมูลที่รวบรวมไว้แล้ว (report_student_summary + report_full_data)
 * แล้วสรุปเป็นข้อสังเกตภาษาไทยที่ครูและนักเรียนอ่านแล้วนำไปใช้ต่อได้ทันที
 *
 * ทุกข้อความในไฟล์นี้คำนวณจากข้อมูลจริงในระบบเท่านั้น ไม่ได้เรียก AI
 * และไม่เดาแทนผู้ใช้ — ถ้าข้อมูลไม่พอจะบอกตรง ๆ ว่ายังสรุปไม่ได้
 *
 * โครงสร้างที่คืนกลับ: รายการของ
 *   ['key', 'title', 'tone' => good|warn|info, 'text', 'items' => [...]]
 */

require_once 'report_data.php';

/** ตัวเลขในประโยคบรรยาย (ตัดทศนิยมที่ไม่จำเป็นออก) */
function ra_n($v, $digits = 2) {
    if ($v === null) return '—';
    $s = number_format((float)$v, $digits);
    if (strpos($s, '.') !== false) $s = rtrim(rtrim($s, '0'), '.');
    return $s;
}

/** เรียงรายการเกณฑ์ตามค่าที่กำหนด แล้วคืนเฉพาะ n อันดับแรก */
function ra_top(array $rows, $field, $desc, $limit) {
    usort($rows, function ($a, $b) use ($field, $desc) {
        if ($a[$field] == $b[$field]) return strcmp($a['id'], $b['id']);
        return $desc ? (($a[$field] < $b[$field]) ? 1 : -1) : (($a[$field] > $b[$field]) ? 1 : -1);
    });
    return array_slice($rows, 0, $limit);
}

/**
 * บทวิเคราะห์รายบุคคล
 * $sum  = ผลจาก report_student_summary()
 * $full = ผลจาก report_full_data()[student_id]
 */
function report_student_insights(array $sum, array $full) {
    $out    = [];
    $g      = $sum['growth'];
    $class  = $sum['class'];
    $target = report_word_target();

    // ------------------------------------------------ 1) พัฒนาการโดยรวม
    if ($g['diff'] === null) {
        $out[] = [
            'key' => 'growth', 'title' => 'พัฒนาการก่อนเรียน → หลังเรียน', 'tone' => 'info',
            'text' => 'ยังสรุปพัฒนาการไม่ได้ เพราะยังมีคะแนนของคุณครูไม่ครบทั้งรอบก่อนเรียนและหลังเรียน',
        ];
    } else {
        $d    = (float)$g['diff'];
        $cls  = ($class['growth']['mean'] === null) ? null : (float)$class['growth']['mean'];
        $tone = ($d > 0) ? 'good' : (($d < 0) ? 'warn' : 'info');
        $txt  = ($d > 0)
            ? 'คะแนนเพิ่มขึ้น ' . ra_n($d, 2) . ' คะแนน (จาก ' . ra_n($g['pre'], 2) . ' เป็น ' . ra_n($g['post'], 2)
              . ' คิดเป็น ' . ra_n($g['pct'], 1) . '%)'
            : (($d < 0)
                ? 'คะแนนลดลง ' . ra_n(abs($d), 2) . ' คะแนน (จาก ' . ra_n($g['pre'], 2) . ' เหลือ ' . ra_n($g['post'], 2) . ')'
                : 'คะแนนเท่าเดิมทั้งก่อนและหลังเรียน (' . ra_n($g['post'], 2) . ' คะแนน)');
        if ($g['level_from'] !== '' && $g['level_to'] !== '') {
            $txt .= ($g['level_from'] === $g['level_to'])
                ? ' ระดับคุณภาพยังอยู่ที่ "' . $g['level_to'] . '" เท่าเดิม'
                : ' ระดับคุณภาพเปลี่ยนจาก "' . $g['level_from'] . '" เป็น "' . $g['level_to'] . '"';
        }
        if ($cls !== null) {
            $gap = $d - $cls;
            $txt .= ' · ทั้งชั้นพัฒนาขึ้นเฉลี่ย ' . ra_n($cls, 2) . ' คะแนน '
                . (($gap > 0.05) ? 'นักเรียนคนนี้พัฒนาได้มากกว่าค่าเฉลี่ย ' . ra_n($gap, 2) . ' คะแนน'
                : (($gap < -0.05) ? 'นักเรียนคนนี้พัฒนาได้น้อยกว่าค่าเฉลี่ย ' . ra_n(abs($gap), 2) . ' คะแนน'
                : 'ซึ่งใกล้เคียงกับค่าเฉลี่ยของชั้น'));
        }
        if (!empty($sum['rank_growth'])) {
            $txt .= ' · อยู่ลำดับที่ ' . $sum['rank_growth']['rank'] . ' จาก ' . $sum['rank_growth']['of']
                  . ' คนเมื่อเรียงตามพัฒนาการ';
        }
        $out[] = ['key' => 'growth', 'title' => 'พัฒนาการก่อนเรียน → หลังเรียน', 'tone' => $tone, 'text' => $txt];
    }

    // ------------------------------------------------ 2) เกณฑ์ที่ขึ้น/ลงชัดเจน
    $withDiff = array_values(array_filter($sum['criteria'], function ($c) { return $c['diff'] !== null; }));
    if ($withDiff) {
        $up   = array_values(array_filter(ra_top($withDiff, 'diff', true, 3), function ($c) { return $c['diff'] > 0; }));
        $down = array_values(array_filter($withDiff, function ($c) { return $c['diff'] < 0; }));
        $items = [];
        foreach ($up as $c) {
            $items[] = 'พัฒนาขึ้น — ' . $c['id'] . ' ' . $c['name'] . ' เพิ่มขึ้น ' . ra_n($c['diff'], 2)
                     . ' คะแนน (' . ra_n($c['pre'], 2) . ' → ' . ra_n($c['post'], 2) . ' จากเต็ม ' . ra_n($c['max'], 0) . ')';
        }
        foreach ($down as $c) {
            $items[] = 'ถดถอย — ' . $c['id'] . ' ' . $c['name'] . ' ลดลง ' . ra_n(abs($c['diff']), 2)
                     . ' คะแนน (' . ra_n($c['pre'], 2) . ' → ' . ra_n($c['post'], 2) . ')';
        }
        $out[] = [
            'key' => 'criteria_move', 'title' => 'เกณฑ์ที่เปลี่ยนแปลงชัดเจนที่สุด',
            'tone' => $down ? 'warn' : 'good',
            'text' => $items ? '' : 'คะแนนรายเกณฑ์คงที่ทุกข้อ ไม่มีข้อใดขยับขึ้นหรือลง',
            'items' => $items,
        ];
    }

    // ------------------------------------------------ 3) เกณฑ์ที่ยังต่ำกว่าค่าเฉลี่ยของชั้น
    $below = [];
    foreach ($sum['criteria'] as $c) {
        if ($c['post'] === null || $c['class_post'] === null) continue;
        if ($c['post'] < $c['class_post'] - 0.01) {
            $below[] = $c['id'] . ' ' . $c['name'] . ' (ได้ ' . ra_n($c['post'], 2)
                     . ' ขณะที่ทั้งชั้นเฉลี่ย ' . ra_n($c['class_post'], 2) . ')';
        }
    }
    if ($below) {
        $out[] = [
            'key' => 'below_class', 'title' => 'เกณฑ์ที่ยังทำได้ต่ำกว่าค่าเฉลี่ยของชั้น (หลังเรียน)',
            'tone' => 'warn', 'text' => 'ควรใช้เป็นลำดับแรกในการซ่อมเสริม', 'items' => $below,
        ];
    } elseif ($sum['achievement']['posttest']['teacher'] !== null) {
        $out[] = [
            'key' => 'below_class', 'title' => 'เทียบรายเกณฑ์กับค่าเฉลี่ยของชั้น (หลังเรียน)',
            'tone' => 'good', 'text' => 'ทำได้เท่ากับหรือสูงกว่าค่าเฉลี่ยของชั้นทุกเกณฑ์',
        ];
    }

    // ------------------------------------------------ 4) การประเมินตนเองแม่นแค่ไหน
    $selfGaps = [];
    foreach ($sum['achievement'] as $ph => $a) {
        if ($a['teacher'] === null || $a['self'] === null) continue;
        $selfGaps[] = ['label' => $a['label'], 'gap' => (float)$a['self'] - (float)$a['teacher']];
    }
    if ($selfGaps) {
        $avg   = array_sum(array_column($selfGaps, 'gap')) / count($selfGaps);
        $items = [];
        foreach ($selfGaps as $sg) {
            $items[] = $sg['label'] . ': ' . (($sg['gap'] > 0) ? 'ให้ตนเองสูงกว่าครู ' . ra_n($sg['gap'], 2)
                     : (($sg['gap'] < 0) ? 'ให้ตนเองต่ำกว่าครู ' . ra_n(abs($sg['gap']), 2) : 'ตรงกับครูพอดี'))
                     . (($sg['gap'] == 0) ? '' : ' คะแนน');
        }
        if (abs($avg) <= 3) {
            $tone = 'good';
            $txt  = 'ประเมินตนเองใกล้เคียงกับคุณครูมาก (ต่างเฉลี่ยเพียง ' . ra_n(abs($avg), 2)
                  . ' คะแนน) แสดงว่านักเรียนเข้าใจเกณฑ์และมองเห็นคุณภาพงานของตนเองได้ตรงตามจริง';
        } elseif ($avg > 3) {
            $tone = 'warn';
            $txt  = 'ประเมินตนเองสูงกว่าคุณครูเฉลี่ย ' . ra_n($avg, 2)
                  . ' คะแนน แสดงว่ายังมองไม่เห็นข้อบกพร่องบางจุดของงานตนเอง '
                  . 'ควรอ่านเกณฑ์ทีละข้อพร้อมชี้ข้อความในงานจริงประกอบก่อนให้คะแนนตนเองครั้งต่อไป';
        } else {
            $tone = 'info';
            $txt  = 'ประเมินตนเองต่ำกว่าคุณครูเฉลี่ย ' . ra_n(abs($avg), 2)
                  . ' คะแนน นักเรียนอาจยังไม่มั่นใจในงานของตนเอง ทั้งที่ทำได้ดีกว่าที่คิด';
        }
        $out[] = ['key' => 'self_accuracy', 'title' => 'ความแม่นยำในการประเมินตนเอง',
                  'tone' => $tone, 'text' => $txt, 'items' => $items];
    }

    // ------------------------------------------------ 5) มุมมองของเพื่อนเทียบกับครู
    $peerGaps = [];
    foreach ($sum['achievement'] as $ph => $a) {
        if ($a['teacher'] === null || $a['peer'] === null) continue;
        $peerGaps[] = (float)$a['peer'] - (float)$a['teacher'];
    }
    if ($peerGaps) {
        $avg = array_sum($peerGaps) / count($peerGaps);
        $out[] = [
            'key' => 'peer_view', 'title' => 'มุมมองของเพื่อนเทียบกับคุณครู', 'tone' => 'info',
            'text' => (abs($avg) <= 3)
                ? 'เพื่อนให้คะแนนใกล้เคียงกับคุณครู (ต่างเฉลี่ย ' . ra_n(abs($avg), 2) . ' คะแนน)'
                : (($avg > 0)
                    ? 'เพื่อนให้คะแนนสูงกว่าคุณครูเฉลี่ย ' . ra_n($avg, 2) . ' คะแนน'
                    : 'เพื่อนให้คะแนนต่ำกว่าคุณครูเฉลี่ย ' . ra_n(abs($avg), 2) . ' คะแนน'),
        ];
    }

    // ------------------------------------------------ 6) ความยาวงานเขียนเทียบเกณฑ์
    $lens = [];
    foreach ($sum['works'] as $ph => $w) {
        if (!$w['submitted']) continue;
        $lens[] = ['label' => $w['label'], 'wc' => (int)$w['word_count']];
    }
    if ($lens) {
        $short = array_values(array_filter($lens, function ($l) use ($target) { return $l['wc'] < $target['min']; }));
        $long  = array_values(array_filter($lens, function ($l) use ($target) { return $l['wc'] > $target['max']; }));
        $first = $lens[0]['wc'];
        $last  = $lens[count($lens) - 1]['wc'];
        $txt   = 'เขียนแล้ว ' . count($lens) . ' ฉบับ ความยาวเฉลี่ย '
               . ra_n(array_sum(array_column($lens, 'wc')) / count($lens), 0) . ' คำ '
               . '(เกณฑ์ของครู ' . $target['min'] . '-' . $target['max'] . ' คำ)';
        if (count($lens) > 1) {
            $txt .= ' · ฉบับแรกเขียน ' . $first . ' คำ ฉบับล่าสุด ' . $last . ' คำ';
        }
        $items = [];
        foreach ($short as $l) $items[] = 'สั้นกว่าเกณฑ์ — ' . $l['label'] . ' (' . $l['wc'] . ' คำ)';
        foreach ($long as $l)  $items[] = 'ยาวเกินเกณฑ์ — ' . $l['label'] . ' (' . $l['wc'] . ' คำ)';
        $out[] = [
            'key' => 'length', 'title' => 'ความยาวของงานเขียนเทียบกับเกณฑ์',
            'tone' => ($short || $long) ? 'warn' : 'good',
            'text' => $txt . (($short || $long) ? '' : ' · อยู่ในเกณฑ์ทุกฉบับ'),
            'items' => $items,
        ];
    }

    // ------------------------------------------------ 7) ร่างหลังดีขึ้นกว่าร่างก่อนจริงไหม
    // เทียบตามคู่ที่ครูกำหนดเท่านั้น: D1.2 กับ D1.1 · D2.2 กับ D2.1 · หลังเรียน กับ ก่อนเรียน
    $paired = [];
    $repeat = [];
    foreach (($full['ai'] ?? []) as $ph => $fb) {
        foreach (($fb['improvements'] ?? []) as $im) {
            $cid = trim((string)($im['criterion'] ?? ''));
            if ($cid !== '') $repeat[$cid] = (int)($repeat[$cid] ?? 0) + 1;
        }
        $dc = $fb['draft_compare'] ?? null;
        if (is_array($dc) && !empty($dc['has_baseline'])) {
            $paired[] = [
                'label' => $fb['phase_label'],
                'base'  => (string)$dc['short'],
                'delta' => (float)$dc['delta'],
                'up'    => (int)$dc['up'],
                'down'  => (int)$dc['down'],
            ];
        }
    }
    if ($paired) {
        $items = [];
        $sumD  = 0;
        $okN   = 0;
        foreach ($paired as $r) {
            $sumD += $r['delta'];
            if ($r['delta'] > 0) $okN++;
            $items[] = $r['label'] . ' เทียบกับ ' . $r['base'] . ': คะแนน '
                     . (($r['delta'] > 0) ? 'ดีขึ้น ' . ra_n($r['delta'], 2)
                     : (($r['delta'] < 0) ? 'ต่ำกว่า ' . ra_n(abs($r['delta']), 2) : 'เท่ากัน'))
                     . ' · ดีขึ้น ' . $r['up'] . ' ข้อ · ลดลง ' . $r['down'] . ' ข้อ';
        }
        $avgD = $sumD / count($paired);
        $out[] = [
            'key' => 'ai_response', 'title' => 'ร่างหลังดีขึ้นกว่าร่างก่อนจริงหรือไม่ (เทียบตามคู่ที่ครูกำหนด)',
            'tone' => ($okN === count($paired)) ? 'good' : (($okN === 0) ? 'warn' : 'info'),
            'text' => 'เทียบได้ ' . count($paired) . ' คู่ · ร่างหลังได้คะแนนสูงกว่า ' . $okN . ' คู่ '
                    . '· ส่วนต่างเฉลี่ย ' . (($avgD >= 0) ? '+' : '') . ra_n($avgD, 2) . ' คะแนนต่อคู่',
            'items' => $items,
        ];
    } elseif (!empty($full['ai'])) {
        $out[] = [
            'key' => 'ai_response', 'title' => 'ร่างหลังดีขึ้นกว่าร่างก่อนจริงหรือไม่', 'tone' => 'info',
            'text' => 'ยังไม่มีคู่ไหนที่ AI ตรวจครบทั้งสองฉบับ (ร่างที่ 1 คู่ร่างที่ 2 หรือก่อนเรียนคู่หลังเรียน) '
                    . 'จึงยังบอกไม่ได้ว่าร่างหลังดีขึ้นจริงหรือไม่',
        ];
    }
    if ($repeat) {
        arsort($repeat);
        $items = [];
        foreach (array_slice($repeat, 0, 4, true) as $cid => $n) {
            if ($n < 2) continue;
            $name = '';
            foreach (report_criteria() as $c) { if ($c['id'] === $cid) $name = $c['name']; }
            $items[] = 'ข้อ ' . $cid . ' ' . $name . ' — ถูกชี้ซ้ำ ' . $n . ' ครั้ง';
        }
        if ($items) {
            $out[] = [
                'key' => 'ai_repeat', 'title' => 'ปัญหาที่ AI ชี้ซ้ำหลายรอบ (จุดอ่อนเรื้อรัง)',
                'tone' => 'warn', 'text' => 'จุดเหล่านี้ควรฝึกเป็นเรื่อง ๆ ไม่ใช่แก้เฉพาะงานชิ้นเดียว',
                'items' => $items,
            ];
        }
    }

    // ------------------------------------------------ 8) นักเรียนรู้ตัวตรงกับที่เสียคะแนนจริงไหม
    $selfProb = [];
    foreach (($full['problems'] ?? []) as $u => $rec) {
        foreach ($rec['items'] as $k => $it) $selfProb[str_replace('_', '.', $k)] = true;
    }
    if ($selfProb) {
        $weakIds = array_column($sum['weak'], 'id');
        $match   = array_values(array_intersect(array_keys($selfProb), $weakIds));
        $missed  = array_values(array_diff($weakIds, array_keys($selfProb)));
        $names   = function ($ids) use ($sum) {
            $o = [];
            foreach ($ids as $id) {
                $o[] = $id . ' ' . ($sum['criteria'][$id]['name'] ?? '');
            }
            return $o;
        };
        $txt = 'นักเรียนบันทึกปัญหาการเขียนของตนเองไว้ ' . count($selfProb) . ' เกณฑ์';
        $items = [];
        if ($match)  $items[] = 'รู้ตัวตรงจุด — ' . implode(', ', $names($match));
        if ($missed) $items[] = 'ยังไม่ได้บันทึกไว้ ทั้งที่เป็นจุดที่เสียคะแนนจริง — ' . implode(', ', $names($missed));
        $out[] = [
            'key' => 'self_awareness', 'title' => 'ปัญหาที่นักเรียนบันทึกเอง เทียบกับจุดที่เสียคะแนนจริง',
            'tone' => $match ? 'good' : 'info', 'text' => $txt, 'items' => $items,
        ];
    }

    // ------------------------------------------------ 9) ความครบถ้วนของงานและเครื่องมือสะท้อนคิด
    $missing = [];
    foreach ($sum['checklist'] as $it) { if (!$it['done']) $missing[] = $it['label']; }
    $out[] = [
        'key' => 'completeness', 'title' => 'ความครบถ้วนของงาน',
        'tone' => $missing ? 'warn' : 'good',
        'text' => 'ส่งงานแล้ว ' . $sum['done'] . ' จาก ' . $sum['done_total'] . ' ชิ้น'
                . ($missing ? ' — ยังขาดอยู่ ' . count($missing) . ' ชิ้น' : ' ครบถ้วนทุกชิ้น'),
        'items' => $missing,
    ];

    // ------------------------------------------------ 10) สิ่งที่ควรทำต่อ
    $todo = [];
    foreach (array_slice($sum['weak'], 0, 2) as $c) {
        $todo[] = 'ฝึกเกณฑ์ข้อ ' . $c['id'] . ' ' . $c['name'] . ' ซึ่งยังทำได้ '
                . ra_n($c['post'], 2) . ' จาก ' . ra_n($c['max'], 0) . ' คะแนน';
    }
    if (!empty($sum['latest_ai']['next_steps'])) {
        foreach (array_slice($sum['latest_ai']['next_steps'], 0, 3) as $ns) {
            $todo[] = $ns . ' (จากผลตรวจของ AI)';
        }
    }
    if ($missing) $todo[] = 'ส่งงานที่ยังค้างอยู่ให้ครบ: ' . implode(', ', array_slice($missing, 0, 4));
    if ($todo) {
        $out[] = ['key' => 'next', 'title' => 'สิ่งที่ควรทำต่อเป็นลำดับแรก', 'tone' => 'info',
                  'text' => '', 'items' => $todo];
    }

    return $out;
}
