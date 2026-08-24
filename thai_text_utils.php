<?php
// thai_text_utils.php
// เครื่องมือช่วยจัดการข้อความภาษาไทยให้ถูกต้อง (ภาษาไทยไม่มีช่องว่างคั่นระหว่างคำ
// การนับ/แยกด้วยช่องว่างแบบเดิมจึงนับได้ผิดมาก เช่น ทั้งย่อหน้าอาจถูกนับเป็น "1 คำ")
//
// ใช้ ICU word break iterator (extension intl) เป็นหลัก เพราะตัดคำภาษาไทยแบบพจนานุกรมได้ถูกต้อง
// ทุกฟังก์ชันมี fallback แบบแยกด้วยช่องว่างไว้เผื่อเซิร์ฟเวอร์ไม่มี extension intl

// ตัดข้อความออกเป็น "ส่วนย่อย" ตามขอบเขต ICU word break แต่ละส่วนบอกด้วยว่าเป็น "คำ" จริง
// (isWord = true) หรือเป็นช่องว่าง/เครื่องหมายวรรคตอนที่คั่นระหว่างคำ (isWord = false)
// เก็บส่วนที่ไม่ใช่คำไว้ด้วย เพื่อนำไปประกอบกลับเป็นข้อความเดิมได้ครบ (ใช้แสดงผลได้)
function thai_word_segments(string $text): array
{
    if ($text === '') {
        return [];
    }

    if (class_exists('IntlBreakIterator')) {
        try {
            $iterator = IntlBreakIterator::createWordInstance('th');
            $iterator->setText($text);

            $segments = [];
            $prev = $iterator->first();
            foreach ($iterator as $curr) {
                if ($curr === $prev) {
                    continue;
                }
                $chunk = substr($text, $prev, $curr - $prev);
                $isWord = $iterator->getRuleStatus() >= IntlBreakIterator::WORD_NONE_LIMIT;
                $segments[] = ['text' => $chunk, 'isWord' => $isWord];
                $prev = $curr;
            }
            return $segments;
        } catch (\Throwable $e) {
            // ตกไป fallback ด้านล่าง
        }
    }

    // fallback: แยกด้วยช่องว่าง (ใช้ได้ดีกับข้อความอังกฤษ หรือกรณีไม่มี extension intl)
    $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $segments = [];
    if ($parts) {
        foreach ($parts as $p) {
            $segments[] = ['text' => $p, 'isWord' => trim($p) !== ''];
        }
    }
    return $segments;
}

// นับจำนวนคำในข้อความ (ดูรายละเอียดอัลกอริทึมใน thai_word_segments)
function count_thai_words(string $text): int
{
    $text = trim($text);
    if ($text === '') {
        return 0;
    }

    $count = 0;
    foreach (thai_word_segments($text) as $seg) {
        if ($seg['isWord']) {
            $count++;
        }
    }
    return $count;
}

// นับจำนวนประโยคโดยประมาณ — ภาษาไทยไม่มีเครื่องหมายจบประโยคบังคับแบบภาษาอังกฤษ
// จึงนับตามเครื่องหมายจบประโยคที่พบ (. ! ? หรือหลายตัวติดกันอย่าง "..." นับเป็น 1) ต่อย่อหน้า
// ถ้าย่อหน้าใดไม่มีเครื่องหมายจบประโยคเลย ให้นับเป็นอย่างน้อย 1 ประโยค
// หมายเหตุ: เป็นค่าประมาณเท่านั้น ไม่ใช่การตรวจไวยากรณ์ประโยคจริง
function count_thai_sentences(string $text): int
{
    $text = trim($text);
    if ($text === '') {
        return 0;
    }

    $paragraphs = preg_split('/\n+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $count = 0;
    foreach ($paragraphs as $para) {
        $para = trim($para);
        if ($para === '') {
            continue;
        }
        $pieces = preg_split('/[.!?]+/u', $para, -1, PREG_SPLIT_NO_EMPTY);
        $pieces = array_filter($pieces, function ($p) { return trim($p) !== ''; });
        $count += max(count($pieces), 1);
    }
    return $count;
}

// แปลงข้อความเป็น HTML โดยครอบแต่ละ "คำ" ด้วย <span class="thai-word"> เพื่อแสดงขอบเขตการตัดคำ
// ส่วนที่ไม่ใช่คำ (ช่องว่าง/เครื่องหมายวรรคตอน) จะถูก escape และแปลงขึ้นบรรทัดใหม่เป็น <br> ตามปกติ
function render_thai_segmented_html(string $text): string
{
    $segments = thai_word_segments($text);
    if (!$segments) {
        return '';
    }

    $html = '';
    foreach ($segments as $seg) {
        $escaped = nl2br(htmlspecialchars($seg['text'], ENT_QUOTES, 'UTF-8'), false);
        $html .= $seg['isWord'] ? ('<span class="thai-word">' . $escaped . '</span>') : $escaped;
    }
    return $html;
}

// โหลดพจนานุกรมคำไทย (data/thai_dictionary.txt — มาจาก LibreOffice Thai dictionary, MPL-2.0)
// เก็บแคชไว้ในตัวแปร static ระหว่างการทำงานของ request เดียวกัน (ไม่ต้องอ่านไฟล์ซ้ำ)
function load_thai_dictionary(): array
{
    static $dict = null;
    if ($dict !== null) {
        return $dict;
    }

    $dict = [];
    $path = __DIR__ . '/data/thai_dictionary.txt';
    if (is_file($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $word) {
                $dict[$word] = true;
            }
        }
    }
    return $dict;
}

// โหลดรายการ "คำที่ยืนยันว่าถูกต้องแล้ว" จากตาราง thai_confirmed_words (นักเรียน/ครูกดยืนยันไว้)
// ใช้ยกเว้นไม่ให้ find_misspelled_thai_words() ฟ้องคำเหล่านี้ซ้ำอีก
function load_confirmed_thai_words(PDO $pdo): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    try {
        $rows = $pdo->query('SELECT word FROM thai_confirmed_words')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($rows as $w) {
            $cache[$w] = true;
        }
    } catch (\Throwable $e) {
        // เงียบไว้ — ถ้าตารางยังไม่ถูกสร้าง ให้ถือว่ายังไม่มีคำที่ยืนยัน
    }
    return $cache;
}

// บันทึกว่ามีคนยืนยันว่าคำนี้สะกดถูกต้อง (นักเรียนหรือครู) — กดซ้ำได้ ไม่เกิดข้อผิดพลาด
function confirm_thai_word(PDO $pdo, string $word, string $confirmedBy, string $role): bool
{
    $word = trim($word);
    if ($word === '') {
        return false;
    }
    $stmt = $pdo->prepare('INSERT IGNORE INTO thai_confirmed_words (word, confirmed_by, confirmed_role) VALUES (?, ?, ?)');
    return $stmt->execute([$word, $confirmedBy, $role]);
}

// ตรวจหาคำที่ "อาจสะกดผิด" โดยเทียบกับพจนานุกรมคำไทย (ตรวจแบบมีคำนี้ในพจนานุกรมหรือไม่เท่านั้น
// ไม่ใช่การตรวจไวยากรณ์/ความหมาย/บริบท) คืนค่าเป็นรายการคำที่ไม่ซ้ำ ตามลำดับที่พบในข้อความ
//
// ข้อจำกัดสำคัญ: คำเฉพาะ ชื่อคน คำสแลง คำทับศัพท์ใหม่ ๆ ที่ไม่มีในพจนานุกรม จะถูกขึ้นเตือนด้วย
// ทั้งที่สะกดถูก (false positive) ผลลัพธ์จึงเป็น "คำที่ควรลองตรวจดู" ไม่ใช่ "คำที่ผิดแน่นอน"
// นักเรียน/ครูกดยืนยันคำเหล่านี้ได้ (ดู confirm_thai_word) — ผ่าน $confirmedWords จะไม่ถูกฟ้องอีก
function find_misspelled_thai_words(string $text, array $confirmedWords = [], int $limit = 30): array
{
    $text = trim($text);
    if ($text === '') {
        return [];
    }

    $dict = load_thai_dictionary();
    if (!$dict) {
        // ไม่มีพจนานุกรมให้เทียบ (เช่นไฟล์หาย) — ไม่ฟันธงว่าอะไรผิด ดีกว่าฟ้อง false positive มั่ว ๆ
        return [];
    }

    $seen = [];
    $misspelled = [];
    foreach (thai_word_segments($text) as $seg) {
        if (!$seg['isWord']) {
            continue;
        }
        $word = trim($seg['text']);
        if ($word === '' || isset($seen[$word])) {
            continue;
        }
        $seen[$word] = true;

        // ข้ามคำที่ไม่มีตัวอักษรไทยเลย (ตัวเลข/คำอังกฤษ/สัญลักษณ์) เพราะพจนานุกรมนี้มีแต่คำไทย
        if (!preg_match('/[\x{0E01}-\x{0E2E}]/u', $word)) {
            continue;
        }
        // คำสั้นมาก (1 ตัวอักษร) มักเกิดจากขอบเขตการตัดคำที่ไม่ลงตัว ข้ามไปกันจับผิดเกินจริง
        if (mb_strlen($word, 'UTF-8') <= 1) {
            continue;
        }

        if (isset($dict[$word]) || isset($confirmedWords[$word])) {
            continue;
        }
        // "ๆ" (ไม้ยมก แปลว่าซ้ำคำ) และ "ฯ" (ไปยาลน้อย ย่อคำ) มักถูกตัดคำติดท้ายคำหลัก
        // แต่พจนานุกรมมักเก็บเฉพาะคำหลักไว้ — ลองตัดตัวท้ายเหล่านี้ออกก่อนตัดสินว่าสะกดผิด
        // (ใช้ mb_substr เพราะ "ๆ"/"ฯ" เป็นอักขระหลายไบต์ ตัดด้วย rtrim ตรง ๆ จะตัดผิดไบต์)
        $lastChar = mb_substr($word, -1, 1, 'UTF-8');
        if ($lastChar === 'ๆ' || $lastChar === 'ฯ') {
            $stripped = mb_substr($word, 0, mb_strlen($word, 'UTF-8') - 1, 'UTF-8');
            if ($stripped !== '' && (isset($dict[$stripped]) || isset($confirmedWords[$stripped]))) {
                continue;
            }
        }

        $misspelled[] = $word;
        if (count($misspelled) >= $limit) {
            break;
        }
    }
    return $misspelled;
}
