<?php
/**
 * ai_config.php — ระบบให้ข้อเสนอแนะเรียงความอัตโนมัติด้วย AI
 * ---------------------------------------------------------------------------
 * ไฟล์นี้รวม "สมอง" ของฟีเจอร์ AI ไว้ที่เดียว ได้แก่
 *   1) การอ่าน/บันทึกการตั้งค่า AI (ผู้ให้บริการ, โมเดล, API key, สิทธิ์ของนักเรียน)
 *   2) เกณฑ์การประเมิน (rubric) ฉบับย่อที่ส่งให้ AI ใช้ตรวจ
 *   3) การสร้างคำสั่ง (prompt) ภาษาไทย และการเรียก API ของผู้ให้บริการ
 *   4) การแปลงคำตอบ JSON ของ AI ให้เป็นโครงสร้างที่ระบบใช้ต่อได้
 *
 * ค่าลับ (API key) เก็บได้ 2 ทาง — ระบบจะใช้ไฟล์ก่อนเสมอ:
 *   ก) ไฟล์ ai_secrets.php (ไม่ถูก commit ขึ้น git — ดู ai_secrets.sample.php)
 *   ข) หน้าตั้งค่าในเว็บ (ครูกรอกเอง เก็บลงตาราง app_settings)
 *
 * หมายเหตุสำคัญเชิงงานวิจัย: คะแนนและข้อเสนอแนะจาก AI ถูกเก็บใน
 * ตาราง essay_ai_feedback แยกต่างหาก "ไม่ปะปน" กับตาราง evaluations
 * ที่ใช้เก็บคะแนนของครู/เพื่อน/ตนเอง จึงไม่กระทบข้อมูลวิจัยเดิม
 */

if (!defined('AI_FEEDBACK_LOADED')) {
    define('AI_FEEDBACK_LOADED', true);

    // โควตากันการกดรัวจนเปลืองโควตาฟรีของผู้ให้บริการ (ต่อคน ต่อวัน)
    // ครูเป็นผู้สั่งตรวจเพียงผู้เดียว และตรวจทีละทั้งรอบได้ (39 คน/รอบ × 6 รอบ = 234)
    // จึงตั้งไว้ให้พอตรวจทั้งชั้นครบทุกรอบในวันเดียวแล้วยังเหลือสำหรับตรวจซ้ำบางฉบับ
    define('AI_DAILY_LIMIT_TEACHER', 400);
    // ความยาวขั้นต่ำที่ยอมให้ส่งตรวจ (กันการส่งงานเปล่า ๆ ไปเปลืองโควตา)
    define('AI_MIN_WORDS', 40);
    // ตัดข้อความที่ยาวเกินไปก่อนส่ง (กันค่าใช้จ่าย/ข้อจำกัด token)
    define('AI_MAX_CHARS', 12000);
}

/**
 * รายชื่อผู้ให้บริการ AI ที่รองรับ พร้อมค่าเริ่มต้นที่ "มีโควตาให้ใช้ฟรี"
 * - gemini    : Google AI Studio — มีชั้นใช้งานฟรี เหมาะกับโรงเรียนมากที่สุด
 * - typhoon   : OpenTyphoon (SCB 10X) — โมเดลที่เก่งภาษาไทยโดยเฉพาะ มีชั้นฟรี
 * - openrouter: รวมหลายโมเดล มีรุ่นลงท้าย :free ให้ใช้ฟรี
 * - groq      : เร็วมาก มีชั้นใช้งานฟรี
 * - custom    : เซิร์ฟเวอร์อื่นที่ใช้มาตรฐาน OpenAI (กรอก base URL เอง)
 */
function ai_providers() {
    return [
        'gemini' => [
            'label'    => 'Google Gemini (AI Studio) — มีโควตาฟรี',
            'kind'     => 'gemini',
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'model'    => 'gemini-3.6-flash',
            'key_url'  => 'https://aistudio.google.com/apikey',
        ],
        'typhoon' => [
            'label'    => 'OpenTyphoon (โมเดลไทย) — มีโควตาฟรี',
            'kind'     => 'openai',
            'base_url' => 'https://api.opentyphoon.ai/v1',
            'model'    => 'typhoon-v2.1-12b-instruct',
            'key_url'  => 'https://opentyphoon.ai/',
        ],
        'openrouter' => [
            'label'    => 'OpenRouter — มีโมเดลลงท้าย :free',
            'kind'     => 'openai',
            'base_url' => 'https://openrouter.ai/api/v1',
            'model'    => 'google/gemini-2.0-flash-exp:free',
            'key_url'  => 'https://openrouter.ai/keys',
        ],
        'groq' => [
            'label'    => 'Groq — มีโควตาฟรี ตอบเร็วมาก',
            'kind'     => 'openai',
            'base_url' => 'https://api.groq.com/openai/v1',
            'model'    => 'llama-3.3-70b-versatile',
            'key_url'  => 'https://console.groq.com/keys',
        ],
        'custom' => [
            'label'    => 'อื่น ๆ (เซิร์ฟเวอร์มาตรฐาน OpenAI)',
            'kind'     => 'openai',
            'base_url' => '',
            'model'    => '',
            'key_url'  => '',
        ],
    ];
}

/** รอบงานทั้งหมดที่มีเรียงความ (ใช้ตรวจความถูกต้องของพารามิเตอร์) */
function ai_all_phases() {
    return ['pretest', 'task1_d1', 'task1_d2', 'task2_d1', 'task2_d2', 'posttest'];
}

/** ป้ายชื่อรอบงานภาษาไทย */
function ai_phase_label($phase) {
    $map = [
        'pretest'  => 'ก่อนเรียน (Pre-test)',
        'task1_d1' => 'ภาระงานหน่วยที่ 1 · ร่างที่ 1',
        'task1_d2' => 'ภาระงานหน่วยที่ 1 · ร่างที่ 2',
        'task2_d1' => 'ภาระงานหน่วยที่ 2 · ร่างที่ 1',
        'task2_d2' => 'ภาระงานหน่วยที่ 2 · ร่างที่ 2',
        'posttest' => 'หลังเรียน (Post-test)',
    ];
    return isset($map[$phase]) ? $map[$phase] : $phase;
}

/**
 * อ่านการตั้งค่า AI ทั้งหมด (มี cache ต่อ 1 request)
 * ลำดับความสำคัญ: ไฟล์ ai_secrets.php > ค่าที่ครูกรอกในเว็บ > ค่าเริ่มต้น
 */
function ai_settings(PDO $pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;

    $providers = ai_providers();
    $s = [
        'provider'        => 'gemini',
        'model'           => '',
        'base_url'        => '',
        'api_key'         => '',
        'key_source'      => 'none',   // none | file | db
        'enabled'         => true,     // เปิดใช้ฟีเจอร์ AI ทั้งระบบหรือไม่
    ];

    // 1) ค่าที่ครูกรอกผ่านหน้าเว็บ (ตาราง app_settings)
    try {
        $rows = $pdo->query("SELECT skey, svalue FROM app_settings WHERE skey LIKE 'ai\\_%'")->fetchAll();
        foreach ($rows as $r) {
            switch ($r['skey']) {
                case 'ai_provider':        if ($r['svalue'] !== '') $s['provider'] = $r['svalue']; break;
                case 'ai_model':           $s['model']    = (string)$r['svalue']; break;
                case 'ai_base_url':        $s['base_url'] = (string)$r['svalue']; break;
                case 'ai_api_key':
                    if ((string)$r['svalue'] !== '') { $s['api_key'] = (string)$r['svalue']; $s['key_source'] = 'db'; }
                    break;
                case 'ai_enabled':         $s['enabled']  = ((string)$r['svalue'] !== '0'); break;
            }
        }
    } catch (Exception $e) { /* ตารางอาจยังไม่ถูกสร้าง — ใช้ค่าเริ่มต้น */ }

    // 2) ไฟล์ ai_secrets.php บนเซิร์ฟเวอร์ (สำคัญกว่าค่าที่กรอกในเว็บ)
    if (file_exists(__DIR__ . '/ai_secrets.php')) {
        $ai_provider = $ai_model = $ai_base_url = $ai_api_key = null;
        require __DIR__ . '/ai_secrets.php';
        if (!empty($ai_provider)) $s['provider'] = $ai_provider;
        if (!empty($ai_model))    $s['model']    = $ai_model;
        if (!empty($ai_base_url)) $s['base_url'] = $ai_base_url;
        if (!empty($ai_api_key)) { $s['api_key'] = $ai_api_key; $s['key_source'] = 'file'; }
    }

    // เติมค่าเริ่มต้นของผู้ให้บริการที่เลือก
    if (!isset($providers[$s['provider']])) $s['provider'] = 'gemini';
    $p = $providers[$s['provider']];
    $s['kind'] = $p['kind'];
    if ($s['model'] === '')    $s['model']    = $p['model'];
    if ($s['base_url'] === '') $s['base_url'] = $p['base_url'];
    $s['base_url']  = rtrim($s['base_url'], '/');
    $s['configured'] = ($s['api_key'] !== '' && $s['model'] !== '' && $s['base_url'] !== '');

    $cache = $s;
    return $cache;
}

/** บันทึกการตั้งค่า AI (เฉพาะคีย์ที่ส่งมา) แล้วล้าง cache ในหน่วยความจำ */
function ai_save_setting(PDO $pdo, $key, $value) {
    $allowed = ['ai_provider', 'ai_model', 'ai_base_url', 'ai_api_key', 'ai_enabled'];
    if (!in_array($key, $allowed, true)) return false;
    $stmt = $pdo->prepare('
        INSERT INTO app_settings (skey, svalue) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_at = CURRENT_TIMESTAMP
    ');
    return $stmt->execute([$key, (string)$value]);
}

/** ปิดบัง API key ก่อนส่งกลับหน้าเว็บ (ไม่ส่งคีย์จริงออกไปเด็ดขาด) */
function ai_mask_key($key) {
    $len = strlen((string)$key);
    if ($len === 0) return '';
    if ($len <= 8) return str_repeat('•', $len);
    return substr($key, 0, 4) . str_repeat('•', 8) . substr($key, -4);
}

/**
 * เกณฑ์การประเมินฉบับย่อสำหรับ AI (ตรงกับ rubric ในหน้า evaluation.php)
 * key = รหัสข้อ, name = ชื่อข้อ, max = คะแนนเต็มหลังถ่วงน้ำหนัก, multiplier = ตัวคูณ
 * ai = ตรวจด้วย AI ได้หรือไม่ (ข้อ 4.3 ความเรียบร้อย/ลายมือ ตรวจจากไฟล์พิมพ์ไม่ได้)
 */
function ai_rubric() {
    return [
        ['id' => '1.1', 'name' => 'ความตรงประเด็น',              'multiplier' => 3,    'max' => 12, 'ai' => true,
         'guide' => '4=เนื้อหาสัมพันธ์กับหัวข้อทุกส่วน ไม่มีประเด็นนอกขอบเขต, 3=มีประเด็นนอกขอบเขต 1 ประเด็น, 2=นอกขอบเขต 2 ประเด็น, 1=นอกขอบเขต 3 ประเด็นขึ้นไป, 0=ไม่สัมพันธ์กับหัวข้อ'],
        ['id' => '1.2', 'name' => 'แก่นเรื่องชัดเจน',             'multiplier' => 1.5,  'max' => 6,  'ai' => true,
         'guide' => '4=ระบุประเด็นหลักในคำนำ ย้ำในสรุป และทุกย่อหน้าสัมพันธ์กับประเด็นหลัก, 3=ระบุประเด็นหลัก 2 ใน 3 ส่วน, 2=ระบุเพียง 1 ส่วน, 1=ไม่ระบุทั้งคำนำและสรุป แต่ยังพอสรุปได้จากเนื้อเรื่อง, 0=ระบุประเด็นหลักไม่ได้'],
        ['id' => '1.3', 'name' => 'การขยายความและเหตุผล',         'multiplier' => 2.25, 'max' => 9,  'ai' => true,
         'guide' => '4=ทุกประเด็นมีเหตุผล/ตัวอย่างสนับสนุน 2 รายการขึ้นไป, 3=ประเด็นละ 1 รายการ, 2=มี 1-2 ประเด็นที่ขาดการสนับสนุน, 1=ขาดตั้งแต่ 3 ประเด็นขึ้นไป, 0=ไม่มีการขยายความ'],
        ['id' => '2.1', 'name' => 'ความครบถ้วนขององค์ประกอบ',      'multiplier' => 2,    'max' => 8,  'ai' => true,
         'guide' => '4=ครบคำนำ/เนื้อเรื่อง/สรุป แยกย่อหน้าชัดเจน, 3=ครบแต่สัดส่วนความยาวไม่สมดุล, 2=ครบแต่ไม่แยกขอบเขตชัดเจน, 1=ขาดไป 1 ส่วน, 0=ขาดตั้งแต่ 2 ส่วนขึ้นไป'],
        ['id' => '2.2', 'name' => 'การลำดับประเด็นเป็นระบบ',       'multiplier' => 1,    'max' => 4,  'ai' => true,
         'guide' => '4=ลำดับถูกต้อง มีทิศทางชัดเจน, 3=มีย่อหน้าวางผิดที่ 1 ย่อหน้า, 2=วางผิดที่ 2 ย่อหน้า, 1=วางผิดที่ 3 ย่อหน้า, 0=ไม่เป็นระบบ'],
        ['id' => '3.1', 'name' => 'การใช้ประโยคถูกต้อง',           'multiplier' => 1,    'max' => 4,  'ai' => true,
         'guide' => '4=ถูกต้องทั้งหมดและโครงสร้างหลากหลาย, 3=ผิดไม่เกิน 2 ประโยค, 2=ผิด 3-5 ประโยค, 1=ผิด 6-8 ประโยค, 0=ผิดตั้งแต่ 9 ประโยคขึ้นไป'],
        ['id' => '3.2', 'name' => 'การเลือกใช้คำ',                 'multiplier' => 1.5,  'max' => 6,  'ai' => true,
         'guide' => '4=เลือกคำและคำเชื่อมถูกต้องทั้งหมด กระชับสละสลวย, 3=คำเชื่อมคลาดเคลื่อนไม่เกิน 2 แห่ง, 2=คลาดเคลื่อน 3-5 แห่ง, 1=ใช้คำผิดความหมาย 6-8 แห่ง, 0=ผิดตั้งแต่ 9 แห่งขึ้นไป'],
        ['id' => '3.3', 'name' => 'ระดับภาษาเหมาะสม',              'multiplier' => 1.25, 'max' => 5,  'ai' => true,
         'guide' => '4=ใช้ภาษากึ่งทางการขึ้นไปสม่ำเสมอ ไม่มีภาษาพูดปน, 3=ภาษาพูดปนไม่เกิน 2 ตำแหน่ง, 2=ปน 3-5 ตำแหน่ง, 1=ปน 6-8 ตำแหน่ง, 0=ใช้ภาษาพูดตลอดหรือปนตั้งแต่ 9 ตำแหน่ง'],
        ['id' => '4.1', 'name' => 'การสะกดคำถูกต้อง',              'multiplier' => 0.5,  'max' => 2,  'ai' => true,
         'guide' => '4=สะกดถูกทุกคำ, 3=ผิด 1-2 แห่ง, 2=ผิด 3-5 แห่ง, 1=ผิด 6-8 แห่ง, 0=ผิดตั้งแต่ 9 แห่งขึ้นไป'],
        ['id' => '4.2', 'name' => 'การเว้นวรรค',                   'multiplier' => 0.5,  'max' => 2,  'ai' => true,
         'guide' => '4=เว้นวรรคถูกต้องทั้งหมด, 3=ผิด 1-2 จุด, 2=ผิด 3-5 จุด, 1=ผิด 6-8 จุด, 0=ผิดตั้งแต่ 9 จุดขึ้นไป'],
        // ข้อที่ AI ตรวจแทนไม่ได้ — ครูกรอกคะแนนเองในหน้า "ผู้ช่วย AI" (ระดับคะแนนตรงกับ rubric ในหน้า evaluation.php)
        ['id' => '4.3', 'name' => 'ความเรียบร้อย (ลายมือ/ความสะอาด)', 'multiplier' => 0.5, 'max' => 2, 'ai' => false,
         'guide' => 'ตรวจจากต้นฉบับลายมือเท่านั้น — AI ไม่ประเมินข้อนี้',
         'levels' => [
             ['score' => 4, 'label' => 'ดีมาก',   'desc' => 'ผลงานสะอาด เป็นระเบียบ ลายมืออ่านง่าย ไม่ปรากฏรอยขูดลบขีดฆ่า'],
             ['score' => 3, 'label' => 'ดี',      'desc' => 'ผลงานสะอาดเรียบร้อย ลายมืออ่านง่าย ปรากฏรอยขูดลบขีดฆ่า 1 ถึง 2 จุด'],
             ['score' => 2, 'label' => 'ปานกลาง', 'desc' => 'ผลงานค่อนข้างเรียบร้อย ลายมืออ่านง่าย ปรากฏรอยขูดลบขีดฆ่า 3 ถึง 5 จุด'],
             ['score' => 1, 'label' => 'พอใช้',   'desc' => 'ผลงานไม่เรียบร้อย ปรากฏรอยขูดลบขีดฆ่า 6 ถึง 8 จุด'],
             ['score' => 0, 'label' => 'ปรับปรุง', 'desc' => 'ผลงานไม่เรียบร้อย ปรากฏรอยขูดลบขีดฆ่าตั้งแต่ 9 จุดขึ้นไป หรือลายมืออ่านยาก'],
         ]],
    ];
}

/** คะแนนเต็มรวมของข้อที่ AI ประเมินได้ (58 จาก 60 — ยกเว้นข้อ 4.3) */
function ai_rubric_max() {
    $sum = 0;
    foreach (ai_rubric() as $it) { if ($it['ai']) $sum += $it['max']; }
    return $sum;
}

/** ข้อที่ AI ตรวจแทนไม่ได้ ครูต้องให้คะแนนเองจากต้นฉบับ (ปัจจุบันคือข้อ 4.3 ความเรียบร้อย/ลายมือ) */
function ai_rubric_manual() {
    $out = [];
    foreach (ai_rubric() as $it) { if (!$it['ai']) $out[] = $it; }
    return $out;
}

/** คะแนนเต็มของข้อที่ครูต้องให้เอง (2 จาก 60) */
function ai_manual_max() {
    $sum = 0;
    foreach (ai_rubric_manual() as $it) { $sum += $it['max']; }
    return $sum;
}

/** คะแนนเต็มทั้งฉบับตามเกณฑ์จริงของครู = ข้อที่ AI ตรวจ + ข้อที่ครูให้เอง (60) */
function ai_full_max() {
    return ai_rubric_max() + ai_manual_max();
}

/**
 * รอบการประเมินในหน้า evaluation.php ที่ตรงกับรอบงานของ AI
 * ('' = รอบนั้นคุณครูไม่ได้ให้คะแนนในระบบประเมิน เช่นร่างที่ 1 ซึ่งครูให้คะแนนเฉพาะร่างที่ 2)
 */
function ai_eval_phase($aiPhase) {
    $map = [
        'pretest'  => 'pretest',
        'task1_d2' => 'task1',
        'task2_d2' => 'task2',
        'posttest' => 'posttest',
    ];
    return isset($map[$aiPhase]) ? $map[$aiPhase] : '';
}

/**
 * ดึงคะแนนข้อที่ AI ตรวจแทนไม่ได้ (ปัจจุบันคือ 4.3 ความเรียบร้อย) จาก "แบบประเมินของคุณครู"
 * ที่บันทึกไว้แล้วในหน้า evaluation.php เพื่อไม่ให้คุณครูต้องกรอกคะแนนซ้ำสองที่
 *
 * ตาราง evaluations เก็บคะแนน "หลังถ่วงน้ำหนัก" ไว้ จึงหารด้วยตัวคูณกลับเป็นระดับ 0-4
 * ระบุ $studentId = เฉพาะคนนั้น, ไม่ระบุ = ทุกคน (ใช้กับตารางภาพรวมทั้งชั้น)
 * คืนค่า: [รหัสนักเรียน => [รอบงานของ AI => ['scores', 'total', 'by', 'at']]]
 */
function ai_teacher_eval_manual_all(PDO $pdo, $studentId = null) {
    $out    = [];
    $manual = ai_rubric_manual();
    if (!$manual) return $out;

    try {
        $cols = [];
        foreach ($manual as $it) {
            $cols[$it['id']] = 'score_' . str_replace('.', '_', $it['id']);
        }
        $sql = 'SELECT student_id, test_phase, evaluator_name, timestamp, ' . implode(', ', array_values($cols))
             . ' FROM evaluations WHERE evaluator_type = ?';
        $params = ['ครูประเมิน'];
        if ($studentId !== null && $studentId !== '') {
            $sql .= ' AND student_id = ?';
            $params[] = $studentId;
        }
        $sql .= ' ORDER BY timestamp ASC';   // เรียงเก่า→ใหม่ แถวหลังทับแถวก่อน จึงได้การประเมินล่าสุด
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // จับคู่รอบการประเมิน (pretest/task1/task2/posttest) กลับเป็นรอบงานของ AI
        $phaseBack = [];
        foreach (ai_all_phases() as $ph) {
            $ep = ai_eval_phase($ph);
            if ($ep !== '') $phaseBack[$ep] = $ph;
        }

        while ($r = $stmt->fetch()) {
            $ph = $phaseBack[(string)$r['test_phase']] ?? '';
            if ($ph === '') continue;

            $scores = [];
            $total  = 0.0;
            foreach ($manual as $it) {
                $col = $cols[$it['id']];
                if (!array_key_exists($col, $r) || $r[$col] === null || $r[$col] === '') continue;
                $weighted = round((float)$r[$col], 2);
                $mult     = (float)$it['multiplier'];
                $raw      = ($mult > 0) ? round($weighted / $mult) : 0;
                if ($raw < 0) $raw = 0;
                if ($raw > 4) $raw = 4;
                $scores[$it['id']] = [
                    'raw'      => (float)$raw,
                    'weighted' => $weighted,
                    'max'      => $it['max'],
                    'name'     => $it['name'],
                ];
                $total += $weighted;
            }
            if (!$scores) continue;

            $out[$r['student_id']][$ph] = [
                'scores' => $scores,
                'total'  => round($total, 2),
                'by'     => (string)$r['evaluator_name'],
                'at'     => (string)$r['timestamp'],
            ];
        }
    } catch (Exception $e) {
        // ฐานข้อมูลเก่ายังไม่มีคอลัมน์ test_phase หรืออ่านไม่ได้ — ถือว่าไม่มีคะแนนให้ดึง
    }
    return $out;
}

/** คะแนนข้อที่ครูให้ไว้ในแบบประเมิน ของนักเรียนคนเดียว: [รอบงานของ AI => [...]] */
function ai_teacher_eval_manual(PDO $pdo, $studentId) {
    if ($studentId === '' || $studentId === null) return [];
    $all = ai_teacher_eval_manual_all($pdo, $studentId);
    return $all[$studentId] ?? [];
}

/** แปลงคะแนนดิบ 0-4 ที่ครูกรอกในข้อที่ AI ตรวจแทนไม่ได้ ให้เป็นคะแนนถ่วงน้ำหนักพร้อมคะแนนรวม */
function ai_build_manual_scores($rawScores) {
    if (!is_array($rawScores)) $rawScores = [];
    $scores = [];
    $total  = 0.0;
    foreach (ai_rubric_manual() as $it) {
        if (!array_key_exists($it['id'], $rawScores)) continue;
        $val = $rawScores[$it['id']];
        if ($val === '' || $val === null || !is_numeric($val)) continue;  // เว้นว่าง = ยังไม่ให้คะแนนข้อนี้
        $raw = (float)$val;
        if ($raw < 0) $raw = 0;
        if ($raw > 4) $raw = 4;
        $weighted = round($raw * $it['multiplier'], 2);
        $scores[$it['id']] = [
            'raw'      => $raw,
            'weighted' => $weighted,
            'max'      => $it['max'],
            'name'     => $it['name'],
        ];
        $total += $weighted;
    }
    return ['scores' => $scores, 'total' => round($total, 2)];
}

/**
 * เติมข้อมูลฝั่ง "คะแนนที่ครูให้เอง" และคะแนนรวมเต็ม 60 ลงในผลตรวจ 1 ชุด
 * เพื่อให้หน้าเว็บแสดงคะแนนเต็มจริงตามเกณฑ์ของครูได้ ไม่ใช่แค่ 58 คะแนนที่ AI ตรวจได้
 */
function ai_attach_manual(array $data, $teacherScores = [], $teacherTotal = 0.0, $teacherBy = '', $teacherAt = '', $teacherSource = '') {
    if (!is_array($teacherScores)) $teacherScores = [];
    $items = [];
    foreach (ai_rubric_manual() as $it) {
        $items[] = [
            'id'     => $it['id'],
            'name'   => $it['name'],
            'max'    => $it['max'],
            'guide'  => $it['guide'],
            'levels' => isset($it['levels']) ? $it['levels'] : [],
        ];
    }
    $data['teacher_scores']  = $teacherScores;
    $data['teacher_total']   = round((float)$teacherTotal, 2);
    $data['teacher_by']      = (string)$teacherBy;
    $data['teacher_at']      = (string)$teacherAt;
    // 'evaluation' = ดึงมาจากแบบประเมินในหน้า evaluation.php, 'ai_page' = ครูกรอกในหน้าผู้ช่วย AI
    $data['teacher_source']  = (string)$teacherSource;
    $data['manual_items']    = $items;
    $data['manual_max']      = ai_manual_max();
    $data['full_max']        = ai_full_max();
    $data['combined_total']  = round((float)($data['total_score'] ?? 0) + $data['teacher_total'], 2);
    $data['manual_done']     = (count($teacherScores) >= count($items));
    // ระดับคุณภาพ "ของจริง" คิดได้ต่อเมื่อครูให้คะแนนข้อที่ AI ตรวจแทนไม่ได้ครบแล้วเท่านั้น
    $data['full_quality_level'] = $data['manual_done'] ? ai_quality_level($data['combined_total']) : '';
    return $data;
}

/** แปลงคะแนนรวม (เต็ม 60) เป็นระดับคุณภาพ — ใช้เกณฑ์เดียวกับหน้า evaluation.php */
function ai_quality_level($total60) {
    if ($total60 >= 49) return 'ดีมาก';
    if ($total60 >= 37) return 'ดี';
    if ($total60 >= 25) return 'ปานกลาง';
    if ($total60 >= 13) return 'พอใช้';
    return 'ต้องปรับปรุง';
}

/** คำสั่งกำกับบทบาทของ AI (System Prompt) */
function ai_system_prompt() {
    return implode("\n", [
        'คุณคือ "ครูภาษาไทย" ผู้เชี่ยวชาญการสอนเขียนเรียงความระดับมัธยมศึกษาตอนปลาย',
        'หน้าที่ของคุณคืออ่านเรียงความของนักเรียนแล้วให้ "ข้อเสนอแนะเพื่อพัฒนา" อย่างละเอียด ตรงจุด และให้กำลังใจ',
        '',
        'กติกาสำคัญที่ต้องทำตามอย่างเคร่งครัด:',
        '1. ห้ามเขียนเรียงความใหม่ให้นักเรียน ห้ามให้ย่อหน้าตัวอย่างที่ลอกไปใช้แทนได้ทันที',
        '   ให้ชี้จุดที่ควรแก้ พร้อมอธิบายวิธีแก้และยกตัวอย่าง "วลีสั้น ๆ" ไม่เกิน 1 ประโยคเท่านั้น',
        '2. อ้างอิงข้อความจริงจากเรียงความเสมอ (ยกคำ/วลีที่พบมาให้เห็น) ห้ามเดาหรือแต่งข้อมูลที่ไม่มีในงาน',
        '3. ใช้ภาษาไทยที่สุภาพ อบอุ่น เข้าใจง่าย เรียกนักเรียนว่า "นักเรียน" พูดกับนักเรียนโดยตรง',
        '4. ให้คะแนนตามเกณฑ์ที่กำหนดอย่างตรงไปตรงมา ไม่ให้คะแนนสูงเกินจริงเพื่อเอาใจ',
        '5. ตอบกลับเป็น JSON เพียงอย่างเดียว ห้ามมีข้อความอื่นนอกวงเล็บปีกกา ห้ามใส่ ```',
    ]);
}

/** สร้างคำสั่งหลัก (User Prompt) จากเนื้อหาเรียงความจริง */
function ai_build_prompt($topic, $phase, $intro, array $bodyArr, $conclusion, $wordCount, array $spellHints = []) {
    $rubricLines = [];
    foreach (ai_rubric() as $it) {
        if (!$it['ai']) continue;
        $rubricLines[] = "- ข้อ {$it['id']} {$it['name']} (คะแนนดิบ 0-4, คะแนนเต็มหลังถ่วงน้ำหนัก {$it['max']})\n  เกณฑ์: {$it['guide']}";
    }

    $bodyText = '';
    foreach ($bodyArr as $i => $p) {
        $bodyText .= 'ย่อหน้าที่ ' . ($i + 1) . ": " . $p . "\n";
    }
    if ($bodyText === '') $bodyText = "(นักเรียนยังไม่ได้เขียนส่วนเนื้อเรื่อง)\n";

    $hintBlock = '';
    if (!empty($spellHints)) {
        $hintBlock = "\nคำที่ระบบตรวจอัตโนมัติสงสัยว่าอาจสะกดผิด (ใช้ประกอบการพิจารณาข้อ 4.1 เท่านั้น "
            . "บางคำอาจเป็นชื่อเฉพาะที่สะกดถูกอยู่แล้ว โปรดใช้วิจารณญาณ):\n"
            . implode(', ', array_slice($spellHints, 0, 40)) . "\n";
    }

    return implode("\n", [
        'ต่อไปนี้คือเรียงความของนักเรียน โปรดอ่านให้ละเอียดแล้วประเมินตามเกณฑ์',
        '',
        '=== ข้อมูลงาน ===',
        'หัวข้อที่ครูกำหนด: ' . ($topic !== '' ? $topic : '(ครูยังไม่ได้กำหนดหัวข้อ — ให้ประเมินความเป็นเอกภาพของเนื้อหาแทน)'),
        'รอบงาน: ' . ai_phase_label($phase),
        'จำนวนคำที่ระบบนับได้: ' . (int)$wordCount . ' คำ (เกณฑ์ของครูคือ 250-300 คำ)',
        '',
        '=== ส่วนคำนำ ===',
        ($intro !== '' ? $intro : '(นักเรียนยังไม่ได้เขียนส่วนคำนำ)'),
        '',
        '=== ส่วนเนื้อเรื่อง ===',
        rtrim($bodyText),
        '',
        '=== ส่วนสรุป ===',
        ($conclusion !== '' ? $conclusion : '(นักเรียนยังไม่ได้เขียนส่วนสรุป)'),
        $hintBlock,
        '=== เกณฑ์การให้คะแนน (คะแนนดิบข้อละ 0-4) ===',
        implode("\n", $rubricLines),
        '',
        '=== รูปแบบคำตอบ (ตอบเป็น JSON เท่านั้น) ===',
        '{',
        '  "overall": "สรุปภาพรวมของเรียงความ 3-5 ประโยค บอกว่างานชิ้นนี้ทำอะไรได้ดีและควรพัฒนาเรื่องใดเป็นอันดับแรก",',
        '  "strengths": ["จุดแข็งที่พบจริงในงาน พร้อมยกข้อความประกอบ", "..." ],',
        '  "improvements": [',
        '    {',
        '      "criterion": "1.1",',
        '      "issue": "ปัญหาที่พบ พร้อมยกข้อความจริงจากเรียงความมาอ้างอิง",',
        '      "suggestion": "วิธีแก้ที่นักเรียนลงมือทำได้ทันที อธิบายเป็นขั้นตอนสั้น ๆ",',
        '      "example": "ตัวอย่างการปรับแก้แบบวลีสั้น ๆ ไม่เกิน 1 ประโยค (ถ้าไม่มีให้ใส่ค่าว่าง)"',
        '    }',
        '  ],',
        '  "scores": { "1.1": 0-4, "1.2": 0-4, "1.3": 0-4, "2.1": 0-4, "2.2": 0-4, "3.1": 0-4, "3.2": 0-4, "3.3": 0-4, "4.1": 0-4, "4.2": 0-4 },',
        '  "score_reasons": { "1.1": "เหตุผลสั้น ๆ ว่าทำไมได้คะแนนระดับนี้", "1.2": "..." },',
        '  "next_steps": ["สิ่งที่ควรลงมือทำเป็นอันดับแรกในการแก้ร่างถัดไป", "..."],',
        '  "encouragement": "ข้อความให้กำลังใจ 1-2 ประโยค"',
        '}',
        '',
        'ข้อกำหนดเพิ่มเติม: strengths ให้ 2-4 ข้อ, improvements ให้ 3-6 ข้อ (เรียงจากสำคัญที่สุดก่อน),',
        'next_steps ให้ 2-4 ข้อ, ต้องให้คะแนนครบทั้ง 10 ข้อ และ score_reasons ต้องมีครบทุกข้อเช่นกัน',
    ]);
}

/**
 * ส่ง HTTP POST แบบ JSON (ใช้ cURL ถ้ามี ไม่มีก็ถอยไปใช้ stream context)
 * คืนค่า ['ok' => bool, 'status' => int, 'body' => string, 'error' => string]
 */
function ai_http_post_json($url, array $headers, array $payload, $timeout = 90) {
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);
        $body   = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'เชื่อมต่อผู้ให้บริการ AI ไม่ได้: ' . $err];
        }
        return ['ok' => ($status >= 200 && $status < 300), 'status' => $status, 'body' => (string)$body, 'error' => ''];
    }

    // สำรอง: โฮสต์ที่ไม่ได้เปิด cURL แต่เปิด allow_url_fopen
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => implode("\r\n", array_merge(['Content-Type: application/json'], $headers)),
        'content'       => $json,
        'timeout'       => $timeout,
        'ignore_errors' => true,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'เซิร์ฟเวอร์เรียก API ภายนอกไม่ได้ (ต้องเปิด cURL หรือ allow_url_fopen)'];
    }
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $status = (int)$m[1];
        }
    }
    return ['ok' => ($status >= 200 && $status < 300), 'status' => $status, 'body' => (string)$body, 'error' => ''];
}

/** ดึงข้อความผิดพลาดที่อ่านรู้เรื่องออกจากคำตอบ error ของผู้ให้บริการ */
function ai_extract_api_error($body, $status) {
    $obj = json_decode((string)$body, true);
    $msg = '';
    if (is_array($obj)) {
        if (isset($obj['error']['message'])) $msg = (string)$obj['error']['message'];
        elseif (isset($obj['error']) && is_string($obj['error'])) $msg = $obj['error'];
        elseif (isset($obj['message'])) $msg = (string)$obj['message'];
    }
    if ($msg === '') $msg = mb_substr(strip_tags((string)$body), 0, 300, 'UTF-8');

    if ($status === 401 || $status === 403) {
        return 'API key ไม่ถูกต้องหรือหมดสิทธิ์ใช้งาน (' . $msg . ')';
    }
    if ($status === 429) {
        return 'ใช้โควตาฟรีของผู้ให้บริการครบแล้วในช่วงนี้ กรุณารอสักครู่แล้วลองใหม่ (' . $msg . ')';
    }
    if ($status === 404) {
        return 'ไม่พบโมเดลที่ตั้งค่าไว้ กรุณาตรวจสอบชื่อโมเดลในหน้าตั้งค่า AI (' . $msg . ')';
    }
    return 'ผู้ให้บริการ AI ตอบกลับข้อผิดพลาด (HTTP ' . $status . '): ' . $msg;
}

/**
 * เรียกโมเดล AI ให้ตอบเป็นข้อความ
 * คืนค่า ['ok' => bool, 'text' => string, 'error' => string]
 */
function ai_call_model(array $s, $systemPrompt, $userPrompt) {
    if (!$s['configured']) {
        return ['ok' => false, 'text' => '', 'finish' => '',
                'error' => 'ยังไม่ได้ตั้งค่า AI (ขาด API key หรือชื่อโมเดล) กรุณาตั้งค่าในหน้า "ผู้ช่วย AI"'];
    }

    if ($s['kind'] === 'gemini') {
        $url = $s['base_url'] . '/models/' . rawurlencode($s['model']) . ':generateContent';
        $payload = [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents'           => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
            'generationConfig'   => [
                'temperature'      => 0.4,
                'responseMimeType' => 'application/json',
                // เดิมตั้งไว้ 4096 ซึ่งน้อยเกินไป: คำตอบเป็นภาษาไทย (กินโทเคนมากกว่าอังกฤษหลายเท่า)
                // และโมเดลรุ่นใหม่ยังใช้โทเคนส่วนหนึ่งไป "คิด" ก่อนตอบ พอโควตาหมดกลางคัน
                // JSON จะถูกตัดครึ่งแล้วอ่านไม่ออก ("รูปแบบไม่ใช่ JSON")
                'maxOutputTokens'  => 32768,
            ],
        ];
        $res = ai_http_post_json($url, ['x-goog-api-key: ' . $s['api_key']], $payload);
        if ($res['error'] !== '') return ['ok' => false, 'text' => '', 'error' => $res['error']];
        if (!$res['ok'])          return ['ok' => false, 'text' => '', 'error' => ai_extract_api_error($res['body'], $res['status'])];

        $obj  = json_decode($res['body'], true);
        $text = '';
        if (isset($obj['candidates'][0]['content']['parts']) && is_array($obj['candidates'][0]['content']['parts'])) {
            foreach ($obj['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text'])) $text .= $part['text'];
            }
        }
        $reason = isset($obj['candidates'][0]['finishReason']) ? (string)$obj['candidates'][0]['finishReason'] : '';
        if (trim($text) === '') {
            return ['ok' => false, 'text' => '', 'finish' => $reason,
                    'error' => 'AI ไม่ได้ส่งเนื้อหากลับมา' . ($reason !== '' ? " (สาเหตุ: $reason)" : '')];
        }
        return ['ok' => true, 'text' => $text, 'error' => '', 'finish' => $reason];
    }

    // มาตรฐาน OpenAI (typhoon / openrouter / groq / custom)
    $url  = $s['base_url'] . '/chat/completions';
    $head = ['Authorization: Bearer ' . $s['api_key']];
    if ($s['provider'] === 'openrouter') {
        // OpenRouter แนะนำให้ระบุที่มาของคำขอ
        $head[] = 'X-Title: Thai Essay Feedback';
    }
    $base = [
        'model'       => $s['model'],
        'messages'    => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        'temperature' => 0.4,
        'max_tokens'  => 8192,
    ];

    // ลองใช้โหมดบังคับ JSON ก่อน ถ้าโมเดลไม่รองรับค่อยยิงซ้ำแบบธรรมดา
    $res = ai_http_post_json($url, $head, array_merge($base, ['response_format' => ['type' => 'json_object']]));
    if ($res['error'] !== '') return ['ok' => false, 'text' => '', 'error' => $res['error']];
    if (!$res['ok'] && $res['status'] === 400) {
        $res = ai_http_post_json($url, $head, $base);
        if ($res['error'] !== '') return ['ok' => false, 'text' => '', 'error' => $res['error']];
    }
    if (!$res['ok']) return ['ok' => false, 'text' => '', 'error' => ai_extract_api_error($res['body'], $res['status'])];

    $obj  = json_decode($res['body'], true);
    $text   = isset($obj['choices'][0]['message']['content']) ? (string)$obj['choices'][0]['message']['content'] : '';
    $reason = isset($obj['choices'][0]['finish_reason']) ? (string)$obj['choices'][0]['finish_reason'] : '';
    if (trim($text) === '') return ['ok' => false, 'text' => '', 'finish' => $reason, 'error' => 'AI ไม่ได้ส่งเนื้อหากลับมา'];
    return ['ok' => true, 'text' => $text, 'error' => '', 'finish' => $reason];
}

/** ดึงก้อน JSON ออกจากข้อความที่ AI ตอบ (เผื่อมี ``` หรือคำอธิบายห่อไว้) */
function ai_extract_json($text) {
    $t = trim((string)$text);
    $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
    $t = preg_replace('/\s*```$/', '', $t);
    $obj = json_decode($t, true);
    if (is_array($obj)) return $obj;

    $start = strpos($t, '{');
    $end   = strrpos($t, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $obj = json_decode(substr($t, $start, $end - $start + 1), true);
        if (is_array($obj)) return $obj;
    }
    // ทางสุดท้าย: คำตอบอาจถูกตัดกลางคันเพราะชนเพดานโทเคน — ลองกู้เท่าที่มี
    return ai_salvage_json($t);
}

/**
 * กู้ JSON ที่ถูกตัดกลางคัน (เช่นโมเดลตอบยาวจนชนเพดานโทเคน)
 * วิธี: ตัดค่าที่ค้างครึ่ง ๆ ทิ้ง แล้วปิดวงเล็บที่ยังเปิดค้างอยู่ให้ครบ
 * คืน array ถ้ากู้สำเร็จ หรือ null ถ้ากู้ไม่ได้
 */
function ai_salvage_json($text) {
    $t = (string)$text;
    $start = strpos($t, '{');
    if ($start === false) return null;
    $t = substr($t, $start);

    // เดินอ่านทีละอักขระเพื่อหาว่าข้อความจบลงกลางสตริงหรือไม่
    $inStr = false; $esc = false; $strStart = -1;
    for ($i = 0, $len = strlen($t); $i < $len; $i++) {
        $c = $t[$i];
        if ($inStr) {
            if ($esc)            { $esc = false; continue; }
            if ($c === '\\')     { $esc = true;  continue; }
            if ($c === '"')      { $inStr = false; }
            continue;
        }
        if ($c === '"') { $inStr = true; $strStart = $i; }
    }
    // จบกลางสตริง → ตัดสตริงที่ค้างทิ้งทั้งตัว
    if ($inStr && $strStart >= 0) $t = substr($t, 0, $strStart);

    // เก็บกวาดเศษท้ายที่ไม่สมบูรณ์ (คอมมา, โคลอน, ชื่อคีย์ที่ยังไม่มีค่า)
    $t = rtrim($t);
    for ($pass = 0; $pass < 3; $pass++) {
        $t = preg_replace('/\s*[,:]\s*$/', '', $t);
        $t = preg_replace('/,\s*"[^"]*"\s*$/', '', $t);   // ,"key" ค้าง
        $t = preg_replace('/\{\s*"[^"]*"\s*$/', '{', $t); // {"key" ค้าง
        $t = rtrim($t);
    }

    // นับวงเล็บที่ยังเปิดค้าง (นอกสตริง) แล้วปิดให้ครบตามลำดับย้อนกลับ
    $stack = []; $inStr = false; $esc = false;
    for ($i = 0, $len = strlen($t); $i < $len; $i++) {
        $c = $t[$i];
        if ($inStr) {
            if ($esc)        { $esc = false; continue; }
            if ($c === '\\') { $esc = true;  continue; }
            if ($c === '"')  { $inStr = false; }
            continue;
        }
        if ($c === '"')                 { $inStr = true; }
        elseif ($c === '{' || $c === '[') { $stack[] = $c; }
        elseif ($c === '}' || $c === ']') { array_pop($stack); }
    }
    while (!empty($stack)) {
        $t .= (array_pop($stack) === '{') ? '}' : ']';
    }

    $obj = json_decode($t, true);
    return is_array($obj) ? $obj : null;
}

/** ทำความสะอาดข้อความจาก AI ก่อนเก็บลงฐานข้อมูล */
function ai_clean_text($v, $maxLen = 2000) {
    $s = trim(preg_replace('/\s+/u', ' ', (string)$v));
    if (mb_strlen($s, 'UTF-8') > $maxLen) $s = mb_substr($s, 0, $maxLen, 'UTF-8') . '…';
    return $s;
}

/**
 * แปลงคำตอบดิบของ AI เป็นโครงสร้างมาตรฐานของระบบ พร้อมคำนวณคะแนนถ่วงน้ำหนัก
 * คืนค่า ['ok' => bool, 'data' => array, 'error' => string]
 */
function ai_parse_feedback($rawText) {
    $obj = ai_extract_json($rawText);
    if (!is_array($obj)) {
        $peek = ai_clean_text(mb_substr(trim((string)$rawText), 0, 160, 'UTF-8'), 200);
        return ['ok' => false, 'data' => [], 'error' =>
            'อ่านคำตอบของ AI ไม่ได้ (ไม่ใช่ JSON ที่สมบูรณ์) กรุณากดตรวจใหม่อีกครั้ง'
            . ($peek !== '' ? ' — คำตอบที่ได้ขึ้นต้นว่า: "' . $peek . '"' : ' — AI ไม่ได้ส่งข้อความใด ๆ กลับมา')];
    }

    $strengths = [];
    if (isset($obj['strengths']) && is_array($obj['strengths'])) {
        foreach ($obj['strengths'] as $v) {
            $t = ai_clean_text(is_array($v) ? implode(' ', $v) : $v, 600);
            if ($t !== '') $strengths[] = $t;
        }
    }

    $improvements = [];
    if (isset($obj['improvements']) && is_array($obj['improvements'])) {
        foreach ($obj['improvements'] as $v) {
            if (is_array($v)) {
                $item = [
                    'criterion'  => ai_clean_text($v['criterion'] ?? '', 20),
                    'issue'      => ai_clean_text($v['issue'] ?? '', 800),
                    'suggestion' => ai_clean_text($v['suggestion'] ?? '', 800),
                    'example'    => ai_clean_text($v['example'] ?? '', 400),
                ];
                if ($item['issue'] !== '' || $item['suggestion'] !== '') $improvements[] = $item;
            } else {
                $t = ai_clean_text($v, 800);
                if ($t !== '') $improvements[] = ['criterion' => '', 'issue' => $t, 'suggestion' => '', 'example' => ''];
            }
        }
    }

    $nextSteps = [];
    if (isset($obj['next_steps']) && is_array($obj['next_steps'])) {
        foreach ($obj['next_steps'] as $v) {
            $t = ai_clean_text(is_array($v) ? implode(' ', $v) : $v, 500);
            if ($t !== '') $nextSteps[] = $t;
        }
    }

    // คะแนน: รับคะแนนดิบ 0-4 ต่อข้อ แล้วคูณตัวถ่วงน้ำหนักตามเกณฑ์จริงของครู
    $rawScores = (isset($obj['scores']) && is_array($obj['scores'])) ? $obj['scores'] : [];
    $reasons   = (isset($obj['score_reasons']) && is_array($obj['score_reasons'])) ? $obj['score_reasons'] : [];
    $scores    = [];
    $total     = 0.0;
    $graded    = 0;
    foreach (ai_rubric() as $it) {
        if (!$it['ai']) continue;
        if (!array_key_exists($it['id'], $rawScores) || !is_numeric($rawScores[$it['id']])) continue;
        $raw = (float)$rawScores[$it['id']];
        if ($raw < 0) $raw = 0;
        if ($raw > 4) $raw = 4;
        $weighted = round($raw * $it['multiplier'], 2);
        $scores[$it['id']] = [
            'raw'      => $raw,
            'weighted' => $weighted,
            'max'      => $it['max'],
            'name'     => $it['name'],
            'reason'   => ai_clean_text($reasons[$it['id']] ?? '', 500),
        ];
        $total += $weighted;
        $graded++;
    }

    $maxScore = ai_rubric_max();
    $total    = round($total, 2);
    // เทียบระดับคุณภาพบนสเกลเต็ม 60 (เกณฑ์เดียวกับครู) โดยเทียบสัดส่วนจากข้อที่ AI ตรวจได้
    $scaled60 = ($maxScore > 0) ? ($total / $maxScore) * 60 : 0;

    $data = [
        'overall'       => ai_clean_text($obj['overall'] ?? ($obj['summary'] ?? ''), 2000),
        'strengths'     => $strengths,
        'improvements'  => $improvements,
        'next_steps'    => $nextSteps,
        'encouragement' => ai_clean_text($obj['encouragement'] ?? '', 600),
        'scores'        => $scores,
        'total_score'   => $total,
        'max_score'     => $maxScore,
        'graded_items'  => $graded,
        'quality_level' => ($graded > 0) ? ai_quality_level($scaled60) : '',
    ];

    if ($data['overall'] === '' && empty($improvements) && empty($strengths)) {
        return ['ok' => false, 'data' => [], 'error' => 'AI ตอบกลับมาไม่ครบถ้วน กรุณากดตรวจใหม่อีกครั้ง'];
    }
    return ['ok' => true, 'data' => $data, 'error' => ''];
}

/**
 * ลายนิ้วมือของเนื้อหาเรียงความ 1 ฉบับ (ส่วนนำ + เนื้อเรื่องทุกย่อหน้า + สรุป)
 * ใช้เทียบว่าต้นฉบับถูกแก้ไขไปจากตอนที่ AI ตรวจไว้หรือไม่ — ถ้าเหมือนเดิมเป๊ะ ไม่ต้องตรวจใหม่
 * ไม่รวบช่องว่างให้ เพราะการเว้นวรรคเป็นเกณฑ์การให้คะแนนข้อ 4.2 อยู่แล้ว
 */
function ai_essay_hash($intro, $bodyArr, $conclusion) {
    if (!is_array($bodyArr)) {
        $bodyArr = ($bodyArr === null || $bodyArr === '') ? [] : [(string)$bodyArr];
    }
    $parts = array_merge(
        [trim((string)$intro)],
        array_map(function ($p) { return trim((string)$p); }, $bodyArr),
        [trim((string)$conclusion)]
    );
    return sha1(implode("\x1f", $parts));
}

/**
 * นักเรียนบันทึกต้นฉบับใหม่ → ถ้าเรียงความฉบับนี้เคยให้ AI ตรวจไว้แล้วและเนื้อหาเปลี่ยนไปจริง
 * ให้ทำเครื่องหมาย "รอตรวจใหม่" ไว้ในผลตรวจเดิม เพื่อเข้าคิวให้คุณครูสั่ง AI ตรวจซ้ำ
 * ถ้านักเรียนแก้แล้วย้อนกลับมาเหมือนเดิมเป๊ะ ระบบจะถอดออกจากคิวให้เอง
 * คืนค่า true เมื่อฉบับนี้ถูกจัดเข้าคิวตรวจใหม่
 */
function ai_mark_essay_recheck(PDO $pdo, $studentId, $phase, $hash) {
    try {
        $stmt = $pdo->prepare('SELECT essay_hash FROM essay_ai_feedback WHERE student_id = ? AND essay_phase = ?');
        $stmt->execute([$studentId, $phase]);
        $row = $stmt->fetch();
        if (!$row) return false;   // ยังไม่เคยให้ AI ตรวจฉบับนี้ — ไม่มีอะไรต้องเข้าคิว

        // ผลตรวจเก่าที่บันทึกไว้ก่อนมีระบบคิว (ไม่มีลายนิ้วมือ) ถือว่าต้องตรวจใหม่ไว้ก่อน
        $old  = (string)($row['essay_hash'] ?? '');
        $same = ($old !== '' && $old === $hash);

        $upd = $pdo->prepare('
            UPDATE essay_ai_feedback
               SET recheck_needed = ?, recheck_marked_at = ?
             WHERE student_id = ? AND essay_phase = ?
        ');
        $upd->execute([$same ? 0 : 1, $same ? null : date('Y-m-d H:i:s'), $studentId, $phase]);
        return !$same;
    } catch (Exception $e) {
        return false;   // ฐานข้อมูลยังไม่มีคอลัมน์คิว — ไม่ควรทำให้การบันทึกเรียงความพัง
    }
}

/** นับจำนวนครั้งที่ผู้ใช้คนนี้เรียก AI ไปแล้ววันนี้ (กันการกดรัวจนโควตาฟรีหมด) */
function ai_usage_today(PDO $pdo, $userId) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM ai_usage_log WHERE user_id = ? AND created_at >= CURDATE()');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['c'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/** บันทึกการเรียกใช้ AI 1 ครั้ง */
function ai_log_usage(PDO $pdo, $userId, $role, $studentId, $phase, $ok, $errorMsg = '') {
    try {
        $stmt = $pdo->prepare('
            INSERT INTO ai_usage_log (user_id, user_role, student_id, essay_phase, success, error_message)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $role, $studentId, $phase, $ok ? 1 : 0, mb_substr((string)$errorMsg, 0, 400, 'UTF-8')]);
    } catch (Exception $e) { /* บันทึกไม่ได้ไม่ควรทำให้ฟีเจอร์หลักพัง */ }
}

/** แปลงแถวในตาราง essay_ai_feedback ให้เป็นโครงสร้างที่หน้าเว็บใช้ได้ทันที */
function ai_feedback_row_to_array(array $row, array $evalManual = null) {
    $decode = function ($v) {
        $d = json_decode((string)$v, true);
        return is_array($d) ? $d : [];
    };
    $out = [
        'student_id'    => $row['student_id'],
        'student_name'  => isset($row['student_name']) ? formatNamePrefix($row['student_name']) : '',
        'classroom'     => $row['classroom'] ?? '',
        'essay_phase'   => $row['essay_phase'],
        'phase_label'   => ai_phase_label($row['essay_phase']),
        'overall'       => (string)$row['overall_comment'],
        'strengths'     => $decode($row['strengths']),
        'improvements'  => $decode($row['improvements']),
        'next_steps'    => $decode($row['next_steps']),
        'encouragement' => (string)$row['encouragement'],
        'scores'        => $decode($row['scores']),
        'total_score'   => (float)$row['total_score'],
        'max_score'     => (float)$row['max_score'],
        'quality_level' => (string)$row['quality_level'],
        'model'         => (string)$row['model'],
        'provider'      => (string)$row['provider'],
        'requested_by'  => (string)$row['requested_by'],
        'requested_role'=> (string)$row['requested_role'],
        'created_at'    => (string)$row['created_at'],
        // ต้นฉบับถูกแก้ไขหลัง AI ตรวจหรือยัง (ใช้แสดงป้าย "รอตรวจใหม่" และจัดคิวตรวจซ้ำ)
        'needs_recheck'     => !empty($row['recheck_needed']),
        'recheck_marked_at' => (string)($row['recheck_marked_at'] ?? ''),
    ];
    $tScores = $decode($row['teacher_scores'] ?? '');
    $tTotal  = $row['teacher_total'] ?? 0;
    $tBy     = $row['teacher_by'] ?? '';
    $tAt     = (string)($row['teacher_scored_at'] ?? '');
    $tSource = $tScores ? 'ai_page' : '';

    // ยังไม่ได้กรอกคะแนนข้อนี้ในหน้าผู้ช่วย AI แต่คุณครูเคยให้คะแนนไว้ในแบบประเมินแล้ว
    // → ดึงคะแนนจากหน้า evaluation.php มาใช้เลย ไม่ต้องกรอกซ้ำ
    if (!$tScores && $evalManual !== null) {
        $ev = $evalManual[$row['essay_phase']] ?? null;
        if ($ev && !empty($ev['scores'])) {
            $tScores = $ev['scores'];
            $tTotal  = $ev['total'];
            $tBy     = $ev['by'];
            $tAt     = $ev['at'];
            $tSource = 'evaluation';
        }
    }

    return ai_attach_manual($out, $tScores, $tTotal, $tBy, $tAt, $tSource);
}
