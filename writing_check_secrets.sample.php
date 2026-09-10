<?php
/**
 * writing_check_secrets.sample.php — ตัวอย่างไฟล์เก็บค่า API ของระบบ
 * ---------------------------------------------------------------------------
 * ใช้เมื่อต้องการตั้งค่าระบบตรวจอัตโนมัติจาก "ไฟล์บนเซิร์ฟเวอร์" แทนการกรอกในหน้าเว็บ
 * (ปลอดภัยกว่า เพราะคีย์ไม่ถูกเก็บในฐานข้อมูล)
 *
 * วิธีใช้:
 *   1. คัดลอกไฟล์นี้เป็นชื่อ  writing_check_secrets.php  (โฟลเดอร์เดียวกับ writing_check_config.php)
 *   2. แก้ค่าข้างล่างให้ตรงกับผู้ให้บริการที่เลือก
 *   3. ไฟล์ writing_check_secrets.php อยู่ใน .gitignore แล้ว จึงไม่ถูกอัปขึ้น git
 *
 * ถ้าไม่สร้างไฟล์นี้ ระบบจะใช้ค่าที่คุณครูกรอกในหน้า "ตั้งค่าระบบตรวจอัตโนมัติ" แทน
 * (ค่าจากไฟล์นี้มีลำดับความสำคัญเหนือกว่าค่าที่กรอกในเว็บเสมอ)
 *
 * ตัวเลือกที่ "มีโควตาให้ใช้ฟรี" — ดูรายละเอียดใน AUTOCHECK_SETUP.md
 *   gemini     : https://aistudio.google.com/apikey        โมเดล gemini-3.6-flash
 *   typhoon    : https://opentyphoon.ai/                   โมเดล typhoon-v2.1-12b-instruct
 *   openrouter : https://openrouter.ai/keys                โมเดลที่ลงท้ายด้วย :free
 *   groq       : https://console.groq.com/keys             โมเดล llama-3.3-70b-versatile
 *
 * ตัวเลือกคุณภาพสูง (ไม่มีโควตาฟรี ต้องเติมเครดิตเอง)
 *   claude     : https://console.anthropic.com/settings/keys   โมเดล claude-opus-5
 *   openai     : https://platform.openai.com/api-keys          โมเดล gpt-5.6-terra
 *
 * ตัวเลือก custom : เซิร์ฟเวอร์อะไรก็ได้ที่พูด "มาตรฐาน OpenAI" (มี /chat/completions)
 *   เช่น DeepSeek, Together, Fireworks หรือเครื่องในโรงเรียนที่รัน Ollama / LM Studio / vLLM
 *   ให้กรอก $ai_base_url เป็น URL ที่ลงท้ายด้วย /v1 เช่น http://192.168.1.10:11434/v1
 *   (เครื่องในโรงเรียนที่ไม่ต้องใช้คีย์ ให้ใส่ $ai_api_key เป็นข้อความอะไรก็ได้ที่ไม่ว่าง เช่น 'local')
 */

$ai_provider = 'gemini';              // gemini | typhoon | openrouter | groq | claude | openai | custom
$ai_model    = 'gemini-3.6-flash';    // เว้นว่างไว้ = ใช้โมเดลเริ่มต้นของผู้ให้บริการ
$ai_api_key  = 'ใส่ API key ของคุณที่นี่';
$ai_base_url = '';                    // เว้นว่างไว้ = ใช้ค่าเริ่มต้น (ระบุเฉพาะกรณี provider = custom)
