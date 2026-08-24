<?php
// thai_text_utils.php
// เครื่องมือช่วยนับคำภาษาไทยให้ถูกต้อง (ภาษาไทยไม่มีช่องว่างคั่นระหว่างคำ
// การนับด้วยการแยกช่องว่าง/บรรทัดแบบเดิมจึงนับได้ผิดมาก เช่น ทั้งย่อหน้าอาจถูกนับเป็น "1 คำ")

// นับจำนวนคำในข้อความ โดยใช้ ICU word break iterator (รองรับภาษาไทยแบบพจนานุกรม)
// หากเซิร์ฟเวอร์ไม่มี extension intl จะ fallback ไปแยกด้วยช่องว่างเหมือนเดิม
function count_thai_words(string $text): int
{
    $text = trim($text);
    if ($text === '') {
        return 0;
    }

    if (class_exists('IntlBreakIterator')) {
        try {
            $iterator = IntlBreakIterator::createWordInstance('th');
            $iterator->setText($text);

            $count = 0;
            $prev = $iterator->first();
            foreach ($iterator as $curr) {
                if ($curr === $prev) {
                    continue;
                }
                // status >= WORD_NONE_LIMIT หมายถึงเป็น "คำ" จริง (ไม่ใช่ช่องว่าง/เครื่องหมายวรรคตอน)
                if ($iterator->getRuleStatus() >= IntlBreakIterator::WORD_NONE_LIMIT) {
                    $count++;
                }
                $prev = $curr;
            }
            return $count;
        } catch (\Throwable $e) {
            // ตกไป fallback ด้านล่าง
        }
    }

    return count(preg_split('/[\s\n\r]+/u', $text, -1, PREG_SPLIT_NO_EMPTY));
}
