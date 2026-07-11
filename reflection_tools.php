<?php
$page_title = 'เครื่องมือสะท้อนคิดและการประเมินเพิ่มเติม';
require_once 'auth_helper.php';
require_login(); // ล็อกอินก่อนเข้าหน้านี้

require_once 'header.php';
$role = $sessionUser['role'];
?>

<div class="container py-4 text-start">
  <!-- หัวข้อใหญ่และปุ่มย้อนกลับ -->
  <div class="mb-3">
    <a href="index.php" class="btn btn-link text-decoration-none text-secondary fw-bold p-0">
      <i class="bi bi-arrow-left-short"></i> กลับหน้าเมนูหลัก
    </a>
  </div>

  <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-5">
    <div class="p-4 text-white" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h4 class="fw-bold mb-1"><i class="bi bi-journal-text"></i> เครื่องมือการสะท้อนคิดและการประเมินสะสม</h4>
          <p class="text-white-50 mb-0 small font-light">แบบบันทึกเชิงคุณภาพ รายการตรวจสอบตนเอง และประเมินผลการเรียนรู้สะท้อนคิด</p>
        </div>
        <span class="badge bg-white text-dark px-3 py-2 fs-6 fw-bold">
          <?php echo $role === 'teacher' ? '👩‍🏫 ครูผู้สอน' : '👨‍🎓 นักเรียน'; ?>
        </span>
      </div>
    </div>

    <div class="card-body p-4">
      <?php if ($role === 'student'): ?>
        <!-- ==========================================
             STUDENT VIEW (แบบฟอร์มบันทึกของนักเรียน)
             ========================================== -->
        <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-pill" id="studentTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill fw-bold" id="obstacles-tab" data-bs-toggle="tab" data-bs-target="#obstacles" type="button" role="tab" aria-controls="obstacles" aria-selected="true">
              📝 1. ปัญหาการเขียน
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-bold" id="checklist-tab" data-bs-toggle="tab" data-bs-target="#checklist" type="button" role="tab" aria-controls="checklist" aria-selected="false">
              📋 2. ตรวจสอบตนเอง
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill fw-bold" id="reflection-tab" data-bs-toggle="tab" data-bs-target="#reflection" type="button" role="tab" aria-controls="reflection" aria-selected="false">
              💡 3. สะท้อนการเรียนรู้
            </button>
          </li>
        </ul>

        <div class="tab-content" id="studentTabContent">
          <!-- 1. แบบบันทึกปัญหาการเขียน -->
          <div class="tab-pane fade show active" id="obstacles" role="tabpanel" aria-labelledby="obstacles-tab">
            <form id="formObstacles">
              <h5 class="fw-bold text-dark mb-3">แบบบันทึกปัญหาการเขียนจากร่างแรก</h5>
              <p class="text-muted small mb-3">คำชี้แจง: ให้วิเคราะห์และบันทึกอุปสรรคหรือข้อบกพร่องที่พบจากร่างแรก พร้อมเสนอแนวทางการแก้ไขเพื่อปรับปรุง</p>
              
              <div class="table-responsive rounded-3 border mb-4">
                <table class="table table-striped table-hover align-middle mb-0">
                  <thead class="table-primary text-dark">
                    <tr>
                      <th style="width: 25%;">ด้านการประเมิน</th>
                      <th style="width: 38%;">ปัญหาที่พบ (Obstacle)</th>
                      <th style="width: 37%;">แนวทางแก้ไขปรับปรุง (Action Plan)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- ด้านเนื้อหาสาระ -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">1) ด้านเนื้อหาสาระ</td></tr>
                    <tr>
                      <td class="ps-3 small">1.1 ความตรงประเด็น</td>
                      <td><textarea name="prob_1_1" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_1_1" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">1.2 แก่นเรื่องที่ชัดเจน</td>
                      <td><textarea name="prob_1_2" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_1_2" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">1.3 การขยายความและให้เหตุผล</td>
                      <td><textarea name="prob_1_3" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_1_3" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    
                    <!-- ด้านองค์ประกอบและการลำดับ -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">2) ด้านองค์ประกอบและการลำดับเรื่อง</td></tr>
                    <tr>
                      <td class="ps-3 small">2.1 ความครบถ้วนขององค์ประกอบ</td>
                      <td><textarea name="prob_2_1" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_2_1" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">2.2 การลำดับประเด็นเป็นระบบ</td>
                      <td><textarea name="prob_2_2" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_2_2" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    
                    <!-- ด้านการใช้สำนวนภาษา -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">3) ด้านการใช้สำนวนภาษา</td></tr>
                    <tr>
                      <td class="ps-3 small">3.1 การใช้ประโยคถูกต้อง</td>
                      <td><textarea name="prob_3_1" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_3_1" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">3.2 การเลือกใช้คำ</td>
                      <td><textarea name="prob_3_2" class="form-control form-control-sm" rows="2" placeholder="ระjuปัญหา..."></textarea></td>
                      <td><textarea name="sol_3_2" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">3.3 ระดับภาษาเหมาะสม</td>
                      <td><textarea name="prob_3_3" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_3_3" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    
                    <!-- ด้านอักขรวิธีและกลไกการเขียน -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">4) ด้านอักขรวิธีและกลไกการเขียน</td></tr>
                    <tr>
                      <td class="ps-3 small">4.1 การสะกดคำถูกต้อง</td>
                      <td><textarea name="prob_4_1" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_4_1" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">4.2 การเว้นวรรค</td>
                      <td><textarea name="prob_4_2" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_4_2" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                    <tr>
                      <td class="ps-3 small">4.3 ความเรียบร้อยของลายมือและกระดาษ</td>
                      <td><textarea name="prob_4_3" class="form-control form-control-sm" rows="2" placeholder="ระบุปัญหา..."></textarea></td>
                      <td><textarea name="sol_4_3" class="form-control form-control-sm" rows="2" placeholder="ระบุแนวทางแก้ไข..."></textarea></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow"><i class="bi bi-save"></i> บันทึกอุปสรรคการเขียน</button>
            </form>
          </div>

          <!-- 2. แบบรายการตรวจสอบตนเอง -->
          <div class="tab-pane fade" id="checklist" role="tabpanel" aria-labelledby="checklist-tab">
            <form id="formChecklist">
              <h5 class="fw-bold text-dark mb-3">แบบรายการตรวจสอบตนเอง (Self-Checklist)</h5>
              <p class="text-muted small mb-3">คำชี้แจง: ให้พิจารณาผลงานเขียนร่างสุดท้ายของตนเองอย่างละเอียด แล้วทำเครื่องหมายเลือกในตารางตามความจริง</p>
              
              <div class="table-responsive rounded-3 border mb-4">
                <table class="table table-striped table-hover align-middle mb-0 text-center">
                  <thead class="table-success text-dark">
                    <tr>
                      <th style="width: 25%;" class="text-start">ด้านการประเมิน</th>
                      <th style="width: 45%;" class="text-start">รายการตรวจสอบ</th>
                      <th style="width: 10%;">ปฏิบัติได้ครบถ้วน</th>
                      <th style="width: 10%;">ปฏิบัติได้บางส่วน</th>
                      <th style="width: 10%;">ต้องปรับปรุง</th>
                    </tr>
                  </thead>
                  <tbody class="text-start">
                    <!-- ด้านเนื้อหา -->
                    <tr class="table-light"><td colspan="5" class="fw-bold">1. ด้านเนื้อหาสาระ</td></tr>
                    <tr>
                      <td class="small">1.1 ตรงประเด็น</td>
                      <td class="small text-muted">เนื้อหาทุกส่วนสัมพันธ์กับหัวข้ออย่างสมบูรณ์ และนำเสนอได้ตรงประเด็นตลอดทั้งเรื่อง</td>
                      <td class="text-center"><input type="radio" name="chk_1_1" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_1_1" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_1_1" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">1.2 แก่นเรื่องชัดเจน</td>
                      <td class="small text-muted">งานเขียนมีแก่นเรื่องที่ชัดเจน และเนื้อหาทุกส่วนมีความสัมพันธ์เชื่อมโยงกันตลอดทั้งเรื่อง</td>
                      <td class="text-center"><input type="radio" name="chk_1_2" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_1_2" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_1_2" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">1.3 การขยายความและเหตุผล</td>
                      <td class="small text-muted">มีการขยายความประเด็นต่าง ๆ ได้อย่างลึกซึ้ง พร้อมทั้งให้เหตุผลสนับสนุนและยกตัวอย่างประกอบที่น่าเชื่อถือ</td>
                      <td class="text-center"><input type="radio" name="chk_1_3" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_1_3" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_1_3" value="ต้องปรับปรุง"></td>
                    </tr>

                    <!-- ด้านองค์ประกอบ -->
                    <tr class="table-light"><td colspan="5" class="fw-bold">2. ด้านองค์ประกอบและการลำดับเรื่อง</td></tr>
                    <tr>
                      <td class="small">2.1 องค์ประกอบครบถ้วน</td>
                      <td class="small text-muted">งานเขียนมีองค์ประกอบ 3 ส่วน ครบถ้วนในสัดส่วนที่เหมาะสม และมีกลวิธีนำเสนอที่น่าสนใจ</td>
                      <td class="text-center"><input type="radio" name="chk_2_1" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_2_1" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_2_1" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">2.2 ลำดับประเด็นเป็นระบบ</td>
                      <td class="small text-muted">การวางตำแหน่งย่อหน้าจัดเรียงตามลำดับเหตุผลได้ถูกต้อง มีทิศทางชัดเจน และไม่สลับประเด็นไปมา</td>
                      <td class="text-center"><input type="radio" name="chk_2_2" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_2_2" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_2_2" value="ต้องปรับปรุง"></td>
                    </tr>

                    <!-- ด้านสำนวนภาษา -->
                    <tr class="table-light"><td colspan="5" class="fw-bold">3. ด้านการใช้สำนวนภาษา</td></tr>
                    <tr>
                      <td class="small">3.1 ประโยคถูกต้อง</td>
                      <td class="small text-muted">ประโยคที่ใช้มีความถูกต้องตามหลักภาษาทั้งหมด และมีการใช้โครงสร้างประโยคที่หลากหลาย</td>
                      <td class="text-center"><input type="radio" name="chk_3_1" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_3_1" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_3_1" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">3.2 การเลือกใช้คำ</td>
                      <td class="small text-muted">เลือกใช้คำศัพท์และคำเชื่อมได้ถูกต้องแม่นยำ สื่อความหมายได้อย่างชัดเจนและสละสลวย</td>
                      <td class="text-center"><input type="radio" name="chk_3_2" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_3_2" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_3_2" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">3.3 ระดับภาษาเหมาะสม</td>
                      <td class="small text-muted">ใช้ภาษาระดับทางการได้อย่างถูกต้องและสม่ำเสมอตลอดทั้งงานเขียน โดยไม่ปรากฏภาษาพูดหรือภาษาปาก</td>
                      <td class="text-center"><input type="radio" name="chk_3_3" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_3_3" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_3_3" value="ต้องปรับปรุง"></td>
                    </tr>

                    <!-- ด้านอักขรวิธี -->
                    <tr class="table-light"><td colspan="5" class="fw-bold">4. ด้านอักขรวิธีและกลไกการเขียน</td></tr>
                    <tr>
                      <td class="small">4.1 การสะกดคำถูกต้อง</td>
                      <td class="small text-muted">เขียนสะกดคำและใช้อักษรย่อได้ถูกต้องแม่นยำตามพจนานุกรมทุกแห่งตลอดทั้งงานเขียน</td>
                      <td class="text-center"><input type="radio" name="chk_4_1" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_4_1" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_4_1" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">4.2 การเว้นวรรค</td>
                      <td class="small text-muted">เว้นวรรคตอนระหว่างคำและประโยคได้ถูกต้องตามหลักเกณฑ์ของภาษา</td>
                      <td class="text-center"><input type="radio" name="chk_4_2" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_4_2" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_4_2" value="ต้องปรับปรุง"></td>
                    </tr>
                    <tr>
                      <td class="small">4.3 ความเรียบร้อย</td>
                      <td class="small text-muted">ผลงานมีความสะอาด เป็นระเบียบ จัดหน้ากระดาษเหมาะสม และไม่มีการเขียนฉีกคำ</td>
                      <td class="text-center"><input type="radio" name="chk_4_3" value="ครบถ้วน" required></td>
                      <td class="text-center"><input type="radio" name="chk_4_3" value="บางส่วน"></td>
                      <td class="text-center"><input type="radio" name="chk_4_3" value="ต้องปรับปรุง"></td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="mb-4">
                <label for="checklistNotes" class="form-label fw-bold text-dark">บันทึกเพิ่มเติม (Additional Notes)</label>
                <textarea id="checklistNotes" name="notes" class="form-control" rows="3" placeholder="ระบุสิ่งที่พบระหว่างตรวจสอบ..."></textarea>
              </div>

              <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow"><i class="bi bi-save"></i> บันทึกรายการตรวจสอบ</button>
            </form>
          </div>

          <!-- 3. แบบบันทึกการสะท้อนการเรียนรู้ -->
          <div class="tab-pane fade" id="reflection" role="tabpanel" aria-labelledby="reflection-tab">
            <form id="formReflection">
              <h5 class="fw-bold text-dark mb-3">แบบบันทึกการสะท้อนการเรียนรู้ (Learning Reflection)</h5>
              <p class="text-muted small mb-3">คำชี้แจง: ให้เขียนสะท้อนการเรียนรู้หลังจากปรับปรุงเรียงความฉบับสมบูรณ์เรียบร้อยแล้ว</p>
              
              <div class="row g-4 mb-4">
                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-4 border-info">
                    <label class="form-label fw-bold text-info-emphasis small mb-2">1. ด้านเนื้อหาสาระและองค์ประกอบ</label>
                    <p class="small text-secondary mb-2" style="font-size: 0.8rem;">นักเรียนคิดว่าตนเองสามารถถ่ายทอดแก่นเรื่องและการลำดับประเด็นได้ตรงตามที่วางแผนไว้หรือไม่ และมีจุดใดที่พัฒนาขึ้นจากงานร่างแรกอย่างชัดเจน?</p>
                    <textarea name="content_structure" class="form-control" rows="4" required placeholder="อภิปรายรายละเอียดพัฒนาการ..."></textarea>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-4 border-primary">
                    <label class="form-label fw-bold text-primary-emphasis small mb-2">2. ด้านการใช้สำนวนภาษาและอักขรวิธี</label>
                    <p class="small text-secondary mb-2" style="font-size: 0.8rem;">นักเรียนพบปัญหาใดในการเลือกระดับภาษาให้เหมาะสมกับประเภทงานเขียนหรือการใช้คำเชื่อม และนักเรียนมีวิธีการแก้ไขปัญหาเหล่านั้นอย่างไรเพื่อให้ผลงานมีความถูกต้องตามเกณฑ์ประเมิน?</p>
                    <textarea name="language_mechanics" class="form-control" rows="4" required placeholder="อภิปรายปัญหาอักขรวิธีและการแก้ไข..."></textarea>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-4 border-success">
                    <label class="form-label fw-bold text-success-emphasis small mb-2">3. การนำข้อเสนอแนะไปปรับปรุงงาน</label>
                    <p class="small text-secondary mb-2" style="font-size: 0.8rem;">ข้อเสนอแนะใดจากการประเมินของเพื่อนหรือครูที่นักเรียนคิดว่ามีประโยชน์ที่สุด และนักเรียนได้นำมาปรับปรุงในผลงานฉบับสมบูรณ์อย่างไร?</p>
                    <textarea name="feedback_applied" class="form-control" rows="4" required placeholder="ระบุการปรับแก้จากคำติชม..."></textarea>
                  </div>
                </div>

                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 rounded-3 p-3 bg-white shadow-sm border-start border-4 border-warning">
                    <label class="form-label fw-bold text-warning-emphasis small mb-2">4. การประยุกต์ใช้และเป้าหมายในอนาคต</label>
                    <p class="small text-secondary mb-2" style="font-size: 0.8rem;">นักเรียนคิดว่าทักษะการเขียนเรียงความและการตรวจสอบผลงานอย่างเป็นระบบ สามารถนำไปใช้ประโยชน์ในการศึกษาต่อหรือการพัฒนาตนเองในอนาคตได้อย่างไร?</p>
                    <textarea name="future_goals" class="form-control" rows="4" required placeholder="ระบุประโยชน์ในการต่อยอดศึกษาต่อ..."></textarea>
                  </div>
                </div>
              </div>

              <button type="submit" class="btn btn-info rounded-pill px-4 fw-bold shadow text-white"><i class="bi bi-save"></i> บันทึกการสะท้อนคิด</button>
            </form>
          </div>
        </div>

      <?php else: ?>
        <!-- ==========================================
             TEACHER VIEW (แดชบอร์ดของคุณครู)
             ========================================== -->
        <ul class="nav nav-tabs nav-fill mb-4" id="teacherTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold text-dark" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab" aria-controls="summary" aria-selected="true">
              📊 1. รายงานภาพรวมห้องเรียน
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-dark" id="inspector-tab" data-bs-toggle="tab" data-bs-target="#inspector" type="button" role="tab" aria-controls="inspector" aria-selected="false">
              🔍 2. ตรวจสอบรายบุคคล
            </button>
          </li>
        </ul>

        <div class="tab-content" id="teacherTabContent">
          <!-- 1. แดชบอร์ดภาพรวมกิจกรรมห้องเรียน -->
          <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
            <h5 class="fw-bold text-dark mb-4">สถานะการทำงานสะท้อนการประเมินเชิงลึกของนักเรียน</h5>
            
            <div class="row g-4 mb-4">
              <!-- การส่งงานในแต่ละกิจกรรม -->
              <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                  <span class="fs-3">📝</span>
                  <div class="fw-bold text-secondary small mb-1">ส่งแบบปัญหาการเขียน</div>
                  <h4 id="statProblems" class="fw-bold text-primary mb-0">- / -</h4>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                  <span class="fs-3">📋</span>
                  <div class="fw-bold text-secondary small mb-1">ส่งแบบตรวจสอบตนเอง</div>
                  <h4 id="statChecklists" class="fw-bold text-success mb-0">- / -</h4>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                  <span class="fs-3">👥</span>
                  <div class="fw-bold text-secondary small mb-1">ส่งแบบประเมินเพื่อน</div>
                  <h4 id="statPeerReviews" class="fw-bold text-warning mb-0">- / -</h4>
                </div>
              </div>
              <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-3 bg-white text-center rounded-3">
                  <span class="fs-3">💡</span>
                  <div class="fw-bold text-secondary small mb-1">ส่งแบบสะท้อนการเรียนรู้</div>
                  <h4 id="statReflections" class="fw-bold text-info mb-0">- / -</h4>
                </div>
              </div>
            </div>

            <!-- แผงควบคุมการนำเข้าข้อมูลระดับห้องเรียนด้วย Excel (ซ่อน/แสดง) -->
            <div class="mb-4">
              <button class="btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3 py-1" type="button"
                      data-bs-toggle="collapse" data-bs-target="#excelImportPanel"
                      aria-expanded="false" aria-controls="excelImportPanel">
                <i class="bi bi-table"></i> 📊 แผงนำเข้าข้อมูลด้วย Excel (คลิกเพื่อเปิด/ปิด)
              </button>
              <div class="collapse mt-3" id="excelImportPanel">
                <div class="card border-0 shadow-sm p-4 rounded-4 text-start bg-white border-start border-4 border-success">
                  <div class="d-flex align-items-center mb-3">
                    <span class="fs-4 me-2">📊</span>
                    <div>
                      <h6 class="fw-bold text-dark mb-0">แผงควบคุมการนำเข้าข้อมูลสะท้อนคิดทั้งห้องเรียนด้วย Excel (สำหรับครูผู้สอน)</h6>
                      <span class="text-muted small">ดาวน์โหลดเทมเพลตสำหรับทั้งห้องเรียน กรอกข้อมูลออฟไลน์ตามคอลัมน์โดยระบุรหัสนักเรียนเป็นหลัก (Primary Key) คีย์แรก จากนั้นนำเข้าไฟล์เพื่อบันทึกพร้อมกันทันที</span>
                    </div>
                  </div>
                  <div class="row g-3">
                    <!-- 1. ปัญหาการเขียน -->
                    <div class="col-md-6 col-lg-3 col-12">
                      <div class="p-3 border rounded-3 bg-light h-100">
                        <strong class="text-dark small d-block mb-2">📝 1. ปัญหาการเขียน (Obstacles)</strong>
                        <div class="d-flex flex-column gap-2">
                          <button type="button" class="btn btn-outline-success btn-sm fw-bold rounded-pill text-nowrap" onclick="downloadClassObstaclesTemplate()">
                            <i class="bi bi-download"></i> โหลดเทมเพลตทั้งชั้น
                          </button>
                          <label class="btn btn-success btn-sm fw-bold rounded-pill mb-0 cursor-pointer text-white text-nowrap">
                            <i class="bi bi-upload"></i> นำเข้าไฟล์ CSV
                            <input type="file" accept=".csv" style="display: none;" onchange="importClassObstaclesCSV(this)">
                          </label>
                        </div>
                      </div>
                    </div>
                    <!-- 2. เช็คลิสต์ตนเอง -->
                    <div class="col-md-6 col-lg-3 col-12">
                      <div class="p-3 border rounded-3 bg-light h-100">
                        <strong class="text-dark small d-block mb-2">📋 2. ตรวจสอบตนเอง (Checklist)</strong>
                        <div class="d-flex flex-column gap-2">
                          <button type="button" class="btn btn-outline-success btn-sm fw-bold rounded-pill text-nowrap" onclick="downloadClassChecklistTemplate()">
                            <i class="bi bi-download"></i> โหลดเทมเพลตทั้งชั้น
                          </button>
                          <label class="btn btn-success btn-sm fw-bold rounded-pill mb-0 cursor-pointer text-white text-nowrap">
                            <i class="bi bi-upload"></i> นำเข้าไฟล์ CSV
                            <input type="file" accept=".csv" style="display: none;" onchange="importClassChecklistCSV(this)">
                          </label>
                        </div>
                      </div>
                    </div>
                    <!-- 3. สะท้อนการเรียนรู้ -->
                    <div class="col-md-6 col-lg-3 col-12">
                      <div class="p-3 border rounded-3 bg-light h-100">
                        <strong class="text-dark small d-block mb-2">💡 3. บันทึกสะท้อนคิด (Reflection)</strong>
                        <div class="d-flex flex-column gap-2">
                          <button type="button" class="btn btn-outline-success btn-sm fw-bold rounded-pill text-nowrap" onclick="downloadClassReflectionTemplate()">
                            <i class="bi bi-download"></i> โหลดเทมเพลตทั้งชั้น
                          </button>
                          <label class="btn btn-success btn-sm fw-bold rounded-pill mb-0 cursor-pointer text-white text-nowrap">
                            <i class="bi bi-upload"></i> นำเข้าไฟล์ CSV
                            <input type="file" accept=".csv" style="display: none;" onchange="importClassReflectionCSV(this)">
                          </label>
                        </div>
                      </div>
                    </div>
                    <!-- 4. ประเมินโดยเพื่อน (ใช้สำหรับนำเข้าคะแนนการประเมินเพื่อนจาก evaluation.php แบบกลุ่ม) -->
                    <div class="col-md-6 col-lg-3 col-12">
                      <div class="p-3 border rounded-3 bg-light h-100">
                        <strong class="text-dark small d-block mb-2">👥 4. ประเมินผลงานโดยเพื่อน (Peer)</strong>
                        <div class="d-flex flex-column gap-2">
                          <button type="button" class="btn btn-outline-success btn-sm fw-bold rounded-pill text-nowrap" onclick="downloadClassPeerReviewTemplate()">
                            <i class="bi bi-download"></i> โหลดเทมเพลตทั้งชั้น
                          </button>
                          <label class="btn btn-success btn-sm fw-bold rounded-pill mb-0 cursor-pointer text-white text-nowrap">
                            <i class="bi bi-upload"></i> นำเข้าไฟล์ CSV
                            <input type="file" accept=".csv" style="display: none;" onchange="importClassPeerReviewCSV(this)">
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>


            <div class="row g-4">
              <!-- รายการปัญหาที่ส่งล่าสุด -->
              <div class="col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm p-3 bg-white h-100 rounded-3">
                  <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-exclamation-triangle-fill text-warning"></i> อุปสรรคและแผนการแก้ปัญหาการเขียนล่าสุดของนักเรียน</h6>
                  <div id="recentProblemsContainer" class="small text-muted" style="max-height: 350px; overflow-y: auto;">
                    กำลังโหลดอุปสรรคการเขียนล่าสุด...
                  </div>
                </div>
              </div>

              <!-- รายการคำแนะนำและข้อเสนอแนะเพื่อนประเมินล่าสุด -->
              <div class="col-md-6 col-sm-12">
                <div class="card border-0 shadow-sm p-3 bg-white h-100 rounded-3">
                  <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-chat-text-fill text-primary"></i> คำวิจารณ์และข้อเสนอแนะเพื่อนประเมินล่าสุด</h6>
                  <div id="recentCommentsContainer" class="small text-muted" style="max-height: 350px; overflow-y: auto;">
                    กำลังโหลดคำวิจารณ์ล่าสุด...
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. ตรวจสอบรายบุคคล -->
          <div class="tab-pane fade" id="inspector" role="tabpanel" aria-labelledby="inspector-tab">
            <h5 class="fw-bold text-dark mb-3">ตรวจสอบแฟ้มผลงานสะท้อนการประเมินรายนักเรียน</h5>
            
            <div class="card border-0 bg-light p-3 mb-4 rounded-3">
              <div class="row align-items-center">
                <div class="col-md-6 col-12">
                  <label for="inspectorStudentSelect" class="form-label fw-bold text-secondary small text-uppercase mb-1">เลือกนักเรียนที่ต้องการเข้าชมแฟ้มบันทึกคุณภาพ <span class="text-danger">*</span></label>
                  <select id="inspectorStudentSelect" class="form-select border-2 rounded-3">
                    <option value="" disabled selected>-- เลือกรายชื่อนักเรียน --</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- กล่องแสดงรายงานของนักเรียนแต่ละคน (Read-Only Mode) -->
            <div id="inspectorDetailReport" class="d-none">
              <div class="row g-4">
                <!-- 1. ปัญหาการเขียน -->
                <div class="col-12">
                  <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-exclamation-octagon text-danger"></i> 1. แฟ้มบันทึกปัญหาการเขียนของร่างแรก</h6>
                    <div class="table-responsive rounded-3 mt-3">
                      <table class="table table-bordered table-striped align-middle mb-0 small">
                        <thead class="table-danger text-dark">
                          <tr>
                            <th style="width: 25%;">ด้านเกณฑ์</th>
                            <th style="width: 38%;">ปัญหาที่บันทึก</th>
                            <th style="width: 37%;">แนวทางจัดการแก้ไข</th>
                          </tr>
                        </thead>
                        <tbody id="inspectProblemsTbody">
                          <!-- โหลดผ่าน JS -->
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- 2. เช็คลิสต์ตนเอง -->
                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-patch-check text-success"></i> 2. ผลรายการตรวจสอบตนเอง (Self-Checklist Status)</h6>
                    <ul class="list-group list-group-flush small mt-2" id="inspectChecklistList">
                      <!-- โหลดผ่าน JS -->
                    </ul>
                    <div class="mt-3 p-2 bg-light rounded text-muted small" id="inspectChecklistNotes">
                      <!-- โหลดผ่าน JS -->
                    </div>
                  </div>
                </div>

                <!-- 3. ประเมินโดยเพื่อน -->
                <div class="col-md-6 col-sm-12">
                  <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-people text-warning"></i> 3. คำประเมินและข้อเสนอแนะเชิงคุณภาพจากเพื่อน</h6>
                    <div id="inspectPeerReviewsContainer" class="small mt-2">
                      <!-- โหลดผ่าน JS -->
                    </div>
                  </div>
                </div>

                <!-- 4. สะท้อนการเรียนรู้ -->
                <div class="col-12">
                  <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-lightbulb text-info"></i> 4. แบบสะท้อนการเรียนรู้รายไตรมาส (Learning Reflection)</h6>
                    <div class="row g-3 mt-2">
                      <div class="col-md-6 col-sm-12">
                        <div class="p-3 bg-light rounded-3 h-100">
                          <strong class="text-secondary small d-block">ด้านเนื้อหาสาระและองค์ประกอบ</strong>
                          <span class="small d-block text-dark mt-1 text-muted" id="inspectRefContent">-</span>
                        </div>
                      </div>
                      <div class="col-md-6 col-sm-12">
                        <div class="p-3 bg-light rounded-3 h-100">
                          <strong class="text-secondary small d-block">ด้านการใช้สำนวนภาษาและอักขรวิธี</strong>
                          <span class="small d-block text-dark mt-1 text-muted" id="inspectRefLang">-</span>
                        </div>
                      </div>
                      <div class="col-md-6 col-sm-12">
                        <div class="p-3 bg-light rounded-3 h-100">
                          <strong class="text-secondary small d-block">การนำข้อเสนอแนะไปปรับปรุงงาน</strong>
                          <span class="small d-block text-dark mt-1 text-muted" id="inspectRefFeedback">-</span>
                        </div>
                      </div>
                      <div class="col-md-6 col-sm-12">
                        <div class="p-3 bg-light rounded-3 h-100">
                          <strong class="text-secondary small d-block">การประยุกต์ใช้และเป้าหมายในอนาคต</strong>
                          <span class="small d-block text-dark mt-1 text-muted" id="inspectRefFuture">-</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div id="inspectorPlaceholder" class="text-center py-5 text-muted">
              <div class="fs-1 mb-2">🔍</div>
              <p class="mb-0">เลือกรายชื่อนักเรียนด้านบนเพื่อเข้าชมแบบบันทึกคุณภาพสะสมรายบุคคล</p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  let studentDB = {};

  // ตรวจสอบบทบาทจาก Session ฝั่ง PHP
  const userRole = "<?php echo $role; ?>";
  const currentUserId = "<?php echo $sessionUser['id']; ?>";

  // โหลดรายชื่อนักเรียน
  async function loadStudents() {
    try {
      const response = await fetch(`api.php?action=get_students_list&_t=${new Date().getTime()}`);
      const res = await response.json();
      if (res.success) {
        studentDB = res.students;
        populateDropdowns();
      }
    } catch (err) {
      console.error(err);
    }
  }

  function populateDropdowns() {
    const peerSelect = document.getElementById('peerTargetStudent');
    const teacherSelect = document.getElementById('inspectorStudentSelect');
    
    // เคลียร์ค่าเดิมก่อน
    if (peerSelect) peerSelect.innerHTML = '<option value="" disabled selected>-- เลือกเพื่อนผู้ถูกประเมิน --</option>';
    if (teacherSelect) teacherSelect.innerHTML = '<option value="" disabled selected>-- เลือกรายชื่อนักเรียน --</option>';

    const sortedKeys = Object.keys(studentDB).sort();
    
    sortedKeys.forEach(id => {
      // นักเรียนประเมินคนอื่นที่ไม่ใช่ตัวเอง
      if (peerSelect && id !== currentUserId) {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = `${id} - ${studentDB[id]}`;
        peerSelect.appendChild(opt);
      }
      
      // ครูส่องประเมินนักเรียนได้ทุกคน
      if (teacherSelect) {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = `${id} - ${studentDB[id]}`;
        teacherSelect.appendChild(opt);
      }
    });
  }

  // ==========================================
  // สำหรับนักเรียน: โหลดข้อมูลเดิม / บันทึกข้อมูล
  // ==========================================
  
  if (userRole === 'student') {
    // 1. โหลดข้อมูลแบบปัญหาการเขียนเดิม
    async function loadStudentWritingProblems() {
      try {
        const response = await fetch(`api.php?action=get_writing_problems&studentId=${currentUserId}&_t=${new Date().getTime()}`);
        const res = await response.json();
        if (res.success && res.data) {
          const d = res.data;
          const form = document.getElementById('formObstacles');
          Object.keys(d).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) input.value = d[key];
          });
        }
      } catch (err) {
        console.error(err);
      }
    }

    // 2. โหลดข้อมูลเช็คลิสต์ตนเองเดิม
    async function loadStudentChecklist() {
      try {
        const response = await fetch(`api.php?action=get_self_checklist&studentId=${currentUserId}&_t=${new Date().getTime()}`);
        const res = await response.json();
        if (res.success && res.data) {
          const d = res.data;
          const form = document.getElementById('formChecklist');
          
          // โหลดค่าเช็คลิสต์วิทยากร
          const criteriaKeys = ['1_1','1_2','1_3','2_1','2_2','3_1','3_2','3_3','4_1','4_2','4_3'];
          criteriaKeys.forEach(key => {
            const val = d[`check_${key}`];
            const radio = form.querySelector(`[name="chk_${key}"][value="${val}"]`);
            if (radio) radio.checked = true;
          });
          
          const notesText = form.querySelector('[name="notes"]');
          if (notesText && d.notes) notesText.value = d.notes;
        }
      } catch (err) {
        console.error(err);
      }
    }

    // 3. โหลดบันทึกการสะท้อนคิดเดิม
    async function loadStudentReflection() {
      try {
        const response = await fetch(`api.php?action=get_learning_reflection&studentId=${currentUserId}&_t=${new Date().getTime()}`);
        const res = await response.json();
        if (res.success && res.data) {
          const d = res.data;
          const form = document.getElementById('formReflection');
          const fields = ['content_structure', 'language_mechanics', 'feedback_applied', 'future_goals'];
          fields.forEach(field => {
            const textarea = form.querySelector(`[name="${field}"]`);
            if (textarea) textarea.value = d[field];
          });
        }
      } catch (err) {
        console.error(err);
      }
    }

    // Event listeners สำหรับการกดปุ่มส่งแบบบันทึกต่าง ๆ
    document.getElementById('formObstacles').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const problems = {};
      formData.forEach((value, key) => {
        problems[key] = value;
      });

      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'save_writing_problems',
            studentId: currentUserId,
            problems: problems
          })
        });
        const res = await response.json();
        if (res.success) {
          alert('บันทึกอุปสรรคปัญหาการเขียนจากร่างแรกเสร็จสมบูรณ์!');
        } else {
          alert('เกิดข้อผิดพลาด: ' + res.error);
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดทางเทคนิคเครือข่าย');
      }
    });

    document.getElementById('formChecklist').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const checklist = {};
      let notes = '';
      formData.forEach((value, key) => {
        if (key === 'notes') {
          notes = value;
        } else {
          // chk_1_1 -> 1.1
          const realKey = key.substring(4).replace('_', '.');
          checklist[realKey] = value;
        }
      });

      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'save_self_checklist',
            studentId: currentUserId,
            checklist: checklist,
            notes: notes
          })
        });
        const res = await response.json();
        if (res.success) {
          alert('บันทึกแบบรายการตรวจสอบตนเอง (Self-Checklist) เรียบร้อย!');
        } else {
          alert('เกิดข้อผิดพลาด: ' + res.error);
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดทางเทคนิคเครือข่าย');
      }
    });

    document.getElementById('formPeer').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const targetId = document.getElementById('peerTargetStudent').value;
      if (!targetId) {
        alert('กรุณาเลือกชื่อเพื่อนก่อนส่งแบบประเมิน');
        return;
      }
      
      const scores = {};
      let strength = '', improvement = '', encouragement = '';
      formData.forEach((value, key) => {
        if (key === 'strength') strength = value;
        else if (key === 'improvement') improvement = value;
        else if (key === 'encouragement') encouragement = value;
        else if (key.startsWith('peer_')) {
          // peer_1_1 -> 1.1
          const realKey = key.substring(5).replace('_', '.');
          scores[realKey] = value;
        }
      });

      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'save_peer_review',
            studentId: targetId,
            reviewerId: currentUserId,
            scores: scores,
            strength: strength,
            improvement: improvement,
            encouragement: encouragement
          })
        });
        const res = await response.json();
        if (res.success) {
          alert('ส่งการประเมินและสะท้อนข้อมูลกลับไปยังเพื่อนสำเร็จ!');
          e.target.reset();
        } else {
          alert('เกิดข้อผิดพลาด: ' + res.error);
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดทางเทคนิคเครือข่าย');
      }
    });

    document.getElementById('formReflection').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const data = {};
      formData.forEach((value, key) => {
        data[key] = value;
      });

      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'save_learning_reflection',
            studentId: currentUserId,
            content_structure: data.content_structure,
            language_mechanics: data.language_mechanics,
            feedback_applied: data.feedback_applied,
            future_goals: data.future_goals
          })
        });
        const res = await response.json();
        if (res.success) {
          alert('บันทึกแบบสะท้อนการเรียนรู้ฉบับสมบูรณ์เรียบร้อย!');
        } else {
          alert('เกิดข้อผิดพลาด: ' + res.error);
        }
      } catch (err) {
        alert('เกิดข้อผิดพลาดทางเทคนิคเครือข่าย');
      }
    });

    // เรียกใช้ตอนเริ่มต้นสำหรับนักเรียน
    document.addEventListener('DOMContentLoaded', async () => {
      await loadStudents();
      await loadStudentWritingProblems();
      await loadStudentChecklist();
      await loadStudentReflection();
    });
  }

  // ==========================================
  // สำหรับครูผู้สอน: รายงานภาพรวมและตรวจสอบ
  // ==========================================
  
  if (userRole === 'teacher') {
    // 1. ดึงภาพรวมสถิติชั้นเรียน
    async function loadTeacherDashboardSummary() {
      try {
        const response = await fetch(`api.php?action=get_reflection_summary&_t=${new Date().getTime()}`);
        const res = await response.json();
        if (res.success) {
          const stats = res.stats;
          const total = stats.total_students;
          
          document.getElementById('statProblems').textContent = `${stats.problems_completed} / ${total} คน`;
          document.getElementById('statChecklists').textContent = `${stats.checklists_completed} / ${total} คน`;
          document.getElementById('statPeerReviews').textContent = `${stats.peer_reviews_completed} / ${total} คน`;
          document.getElementById('statReflections').textContent = `${stats.reflections_completed} / ${total} คน`;
          
          // แสดงรายการปัญหาล่าสุด
          const probsContainer = document.getElementById('recentProblemsContainer');
          if (res.recent_problems && res.recent_problems.length > 0) {
            let html = '<div class="list-group list-group-flush">';
            res.recent_problems.forEach(row => {
              html += `
                <div class="list-group-item py-3">
                  <div class="d-flex justify-content-between mb-1">
                    <strong class="text-dark">${row.student_id} - ${row.student_name}</strong>
                    <span class="small text-muted">${new Date(row.created_at).toLocaleDateString('th-TH')}</span>
                  </div>
                  <div class="text-danger small mb-1"><strong>ปัญหาหลัก (1.1 ตรงประเด็น):</strong> ${row.prob_1_1 || '-'}</div>
                  <div class="text-success small"><strong>แนวทางแก้ไข:</strong> ${row.sol_1_1 || '-'}</div>
                </div>
              `;
            });
            html += '</div>';
            probsContainer.innerHTML = html;
          } else {
            probsContainer.innerHTML = '<div class="text-center py-4">ไม่มีข้อมูลการบันทึกปัญหา</div>';
          }

          // แสดงรายการคอมเมนต์เพื่อนล่าสุด
          const peersContainer = document.getElementById('recentCommentsContainer');
          if (res.recent_peers && res.recent_peers.length > 0) {
            let html = '<div class="list-group list-group-flush">';
            res.recent_peers.forEach(row => {
              html += `
                <div class="list-group-item py-3">
                  <div class="d-flex justify-content-between mb-1">
                    <strong class="text-dark">${row.reviewer_name} ประเมิน ${row.student_name}</strong>
                    <span class="small text-muted">${new Date(row.created_at).toLocaleDateString('th-TH')}</span>
                  </div>
                  <div class="small text-secondary mb-1"><strong>จุดแข็ง:</strong> ${row.strength || '-'}</div>
                  <div class="small text-secondary mb-1"><strong>จุดปรับปรุง:</strong> ${row.improvement || '-'}</div>
                  <div class="small text-muted font-italic"><strong>ส่งเสริมกำลังใจ:</strong> "${row.encouragement || '-'}"</div>
                </div>
              `;
            });
            html += '</div>';
            peersContainer.innerHTML = html;
          } else {
            peersContainer.innerHTML = '<div class="text-center py-4">ไม่มีข้อมูลคำประเมินจากเพื่อน</div>';
          }
        }
      } catch (err) {
        console.error(err);
      }
    }

    // 2. โหลดข้อมูลรายบุคคลของนักเรียนที่ครูเลือกส่อง
    async function loadIndividualReflectionReport(studentId) {
      const inspectProbsTbody = document.getElementById('inspectProblemsTbody');
      const inspectChecklistList = document.getElementById('inspectChecklistList');
      const inspectChecklistNotes = document.getElementById('inspectChecklistNotes');
      const inspectPeerContainer = document.getElementById('inspectPeerReviewsContainer');
      
      const inspectRefContent = document.getElementById('inspectRefContent');
      const inspectRefLang = document.getElementById('inspectRefLang');
      const inspectRefFeedback = document.getElementById('inspectRefFeedback');
      const inspectRefFuture = document.getElementById('inspectRefFuture');

      // รีเซ็ตค่าเริ่มต้น
      inspectProbsTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">กำลังโหลดปัญหาการเขียน...</td></tr>';
      inspectChecklistList.innerHTML = '<li class="list-group-item text-center text-muted">กำลังโหลดเช็คลิสต์...</li>';
      inspectChecklistNotes.textContent = '';
      inspectPeerContainer.innerHTML = '<p class="text-center text-muted">กำลังโหลดความเห็นเพื่อน...</p>';
      
      inspectRefContent.textContent = '-';
      inspectRefLang.textContent = '-';
      inspectRefFeedback.textContent = '-';
      inspectRefFuture.textContent = '-';

      try {
        // ดึงปัญหารอบแรก
        const resProblems = await (await fetch(`api.php?action=get_writing_problems&studentId=${studentId}&_t=${new Date().getTime()}`)).json();
        if (resProblems.success && resProblems.data) {
          const d = resProblems.data;
          const criteriaLabelMap = {
            '1_1': '1.1 ความตรงประเด็น',
            '1_2': '1.2 แก่นเรื่องชัดเจน',
            '1_3': '1.3 การขยายความและเหตุผล',
            '2_1': '2.1 องค์ประกอบครบ',
            '2_2': '2.2 ลำดับประเด็นเป็นระบบ',
            '3_1': '3.1 ประโยคถูกต้อง',
            '3_2': '3.2 เลือกใช้คำ',
            '3_3': '3.3 ระดับภาษาเหมาะสม',
            '4_1': '4.1 การสะกดคำถูกต้อง',
            '4_2': '4.2 การเว้นวรรค',
            '4_3': '4.3 ความเรียบร้อย'
          };
          
          let html = '';
          Object.keys(criteriaLabelMap).forEach(key => {
            const rawProb = d[`prob_${key}`] ? d[`prob_${key}`].trim() : '';
            const rawSol = d[`sol_${key}`] ? d[`sol_${key}`].trim() : '';
            const hasInfo = rawProb !== '' || rawSol !== '';
            
            if (hasInfo) {
              const displayProb = rawProb || '<span class="text-muted font-italic">- ไม่ได้ระบุปัญหา -</span>';
              const displaySol = rawSol || '<span class="text-muted font-italic">- ไม่ได้ระบุแผนแก้ปัญหา -</span>';
              html += `
                <tr>
                  <td class="fw-bold text-dark">${criteriaLabelMap[key]}</td>
                  <td>${displayProb}</td>
                  <td>${displaySol}</td>
                </tr>
              `;
            } else {
              html += `
                <tr class="text-muted opacity-75">
                  <td class="fw-normal text-secondary">${criteriaLabelMap[key]}</td>
                  <td class="font-italic small">- ไม่ได้ระบุปัญหา -</td>
                  <td class="font-italic small">- ไม่ได้ระบุแผนแก้ปัญหา -</td>
                </tr>
              `;
            }
          });
          inspectProbsTbody.innerHTML = html;
        } else {
          inspectProbsTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">นักเรียนรายนี้ยังไม่มีการบันทึกปัญหาการเขียนร่างแรก</td></tr>';
        }

        // ดึงเช็คลิสต์ตนเอง
        const resChecklist = await (await fetch(`api.php?action=get_self_checklist&studentId=${studentId}&_t=${new Date().getTime()}`)).json();
        if (resChecklist.success && resChecklist.data) {
          const d = resChecklist.data;
          const chkLabelMap = {
            '1_1': '1.1 ความตรงประเด็น',
            '1_2': '1.2 แก่นเรื่องที่ชัดเจน',
            '1_3': '1.3 การขยายความและให้เหตุผล',
            '2_1': '2.1 ความครบถ้วนองค์ประกอบ',
            '2_2': '2.2 ลำดับประเด็นเป็นระบบ',
            '3_1': '3.1 ประโยคถูกต้องตามหลักภาษา',
            '3_2': '3.2 การเลือกใช้คำศัพท์เชื่อมโยง',
            '3_3': '3.3 ระดับภาษาเหมาะสมทางการ',
            '4_1': '4.1 สะกดคำถูกต้องแม่นยำ',
            '4_2': '4.2 การเว้นวรรคตอนประโยค',
            '4_3': '4.3 ความสะอาดเรียบร้อย'
          };
          
          let html = '';
          Object.keys(chkLabelMap).forEach(key => {
            const val = d[`check_${key}`];
            let badgeClass = 'bg-secondary';
            if (val === 'ครบถ้วน') badgeClass = 'bg-success';
            else if (val === 'บางส่วน') badgeClass = 'bg-warning text-dark';
            else if (val === 'ต้องปรับปรุง') badgeClass = 'bg-danger';

            html += `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>${chkLabelMap[key]}</span>
                <span class="badge ${badgeClass} rounded-pill">${val}</span>
              </li>
            `;
          });
          inspectChecklistList.innerHTML = html;
          inspectChecklistNotes.innerHTML = `<strong>บันทึกเพิ่มเติม:</strong><br>${d.notes ? d.notes : '<span class="text-muted">- ไม่มีบันทึกเพิ่มเติม -</span>'}`;
        } else {
          inspectChecklistList.innerHTML = '<li class="list-group-item text-center text-muted py-4">นักเรียนยังไม่ได้ส่งแบบรายการตรวจสอบตนเอง</li>';
          inspectChecklistNotes.textContent = '';
        }

        // ดึงประเมินเพื่อน
        const resPeer = await (await fetch(`api.php?action=get_peer_reviews&studentId=${studentId}&_t=${new Date().getTime()}`)).json();
        if (resPeer.success && resPeer.data && resPeer.data.length > 0) {
          let html = '<div class="accordion" id="peerReviewsInspectAccordion">';
          resPeer.data.forEach((row, index) => {
            html += `
              <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingPeer${index}">
                  <button class="accordion-button collapsed py-2 px-3 fw-bold small bg-light text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePeer${index}" aria-expanded="false" aria-controls="collapsePeer${index}">
                    👩‍🎓 ผู้ประเมิน: ${row.reviewer_name}
                  </button>
                </h2>
                <div id="collapsePeer${index}" class="accordion-collapse collapse" aria-labelledby="headingPeer${index}" data-bs-parent="#peerReviewsInspectAccordion">
                  <div class="accordion-body p-3 bg-white">
                    <div class="row g-2 mb-3">
                      <div class="col-6 small"><strong>1.1 ตรงประเด็น:</strong> ${row.score_1_1}</div>
                      <div class="col-6 small"><strong>1.2 แก่นเรื่อง:</strong> ${row.score_1_2}</div>
                      <div class="col-6 small"><strong>1.3 ขยายความ:</strong> ${row.score_1_3}</div>
                      <div class="col-6 small text-primary"><strong>1.4 เอกภาพของเนื้อหา:</strong> ${row.score_1_4}</div>
                      <div class="col-6 small"><strong>2.1 องค์ประกอบครบ:</strong> ${row.score_2_1}</div>
                      <div class="col-6 small"><strong>2.2 ลำดับเป็นระบบ:</strong> ${row.score_2_2}</div>
                      <div class="col-6 small"><strong>3.1 ประโยคถูกต้อง:</strong> ${row.score_3_1}</div>
                      <div class="col-6 small"><strong>3.2 เลือกใช้คำ:</strong> ${row.score_3_2}</div>
                      <div class="col-6 small"><strong>3.3 ระดับภาษา:</strong> ${row.score_3_3}</div>
                      <div class="col-6 small text-primary"><strong>3.4 การใช้คำเชื่อม:</strong> ${row.score_3_4}</div>
                      <div class="col-6 small"><strong>4.1 สะกดคำ:</strong> ${row.score_4_1}</div>
                      <div class="col-6 small"><strong>4.2 เว้นวรรค:</strong> ${row.score_4_2}</div>
                      <div class="col-6 small"><strong>4.3 เรียบร้อย:</strong> ${row.score_4_3}</div>
                    </div>
                    <div class="p-2 bg-light rounded text-muted small mb-1"><strong>จุดแข็ง:</strong> ${row.strength || '-'}</div>
                    <div class="p-2 bg-light rounded text-muted small mb-1"><strong>จุดปรับปรุง:</strong> ${row.improvement || '-'}</div>
                    <div class="p-2 bg-light rounded text-muted small"><strong>กำลังใจ:</strong> "${row.encouragement || '-'}"</div>
                  </div>
                </div>
              </div>
            `;
          });
          html += '</div>';
          inspectPeerContainer.innerHTML = html;
        } else {
          inspectPeerContainer.innerHTML = '<p class="text-center text-muted py-4">นักเรียนรายนี้ยังไม่มีการประเมินสะท้อนข้อมูลเชิงรับจากเพื่อนร่วมห้อง</p>';
        }

        // ดึงสะท้อนคิดการเรียนรู้
        const resRef = await (await fetch(`api.php?action=get_learning_reflection&studentId=${studentId}&_t=${new Date().getTime()}`)).json();
        if (resRef.success && resRef.data) {
          const d = resRef.data;
          inspectRefContent.textContent = d.content_structure || '-';
          inspectRefLang.textContent = d.language_mechanics || '-';
          inspectRefFeedback.textContent = d.feedback_applied || '-';
          inspectRefFuture.textContent = d.future_goals || '-';
        }
      } catch (err) {
        console.error(err);
      }
    }

    // Event listener เมื่อครูเลือกรายชื่อนักเรียน
    document.getElementById('inspectorStudentSelect').addEventListener('change', (e) => {
      const studentId = e.target.value;
      if (studentId) {
        document.getElementById('inspectorPlaceholder').classList.add('d-none');
        document.getElementById('inspectorDetailReport').classList.remove('d-none');
        loadIndividualReflectionReport(studentId);
      }
    });

    // เรียกใช้ตอนเริ่มต้นสำหรับคุณครู
    document.addEventListener('DOMContentLoaded', async () => {
      await loadStudents();
      await loadTeacherDashboardSummary();
    });
  }

  // ==========================================
  // ระบบดาวน์โหลดเทมเพลตและนำเข้าด้วย Excel (.csv) สำหรับคุณครู (ระดับชั้นเรียน)
  // ==========================================

  // ฟังก์ชันแยกแถว CSV รองรับเครื่องหมายคำพูดครอบข้อความที่มีจุลภาค
  function parseCSVRow(rowText) {
    let fields = [];
    let currentField = '';
    let inQuotes = false;
    for (let i = 0; i < rowText.length; i++) {
      let char = rowText[i];
      if (char === '"') {
        inQuotes = !inQuotes;
      } else if (char === ',' && !inQuotes) {
        fields.push(currentField.trim());
        currentField = '';
      } else {
        currentField += char;
      }
    }
    fields.push(currentField.trim());
    return fields;
  }

  // ตัวช่วยสร้างไฟล์ CSV พร้อม UTF-8 BOM
  function triggerCSVDownload(filename, headers, rows) {
    const escapeCSV = (str) => {
      if (str === null || str === undefined) return '';
      let stringified = String(str);
      if (stringified.includes(',') || stringified.includes('\n') || stringified.includes('"')) {
        stringified = stringified.replace(/"/g, '""');
        return `"${stringified}"`;
      }
      return stringified;
    };
    
    let csvContent = "\uFEFF"; // UTF-8 BOM ให้ Excel เปิดแล้วภาษาไทยไม่เพี้ยน
    csvContent += headers.map(escapeCSV).join(",") + "\n";
    csvContent += rows.map(row => row.map(escapeCSV).join(",")).join("\n");
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  // 1. เทมเพลตปัญหาการเขียน (ทั้งห้องเรียน)
  function downloadClassObstaclesTemplate() {
    const headers = ["รหัสนักเรียน (Primary Key)", "ชื่อนักเรียน", "prob_1_1", "sol_1_1", "prob_1_2", "sol_1_2", "prob_1_3", "sol_1_3", "prob_2_1", "sol_2_1", "prob_2_2", "sol_2_2", "prob_3_1", "sol_3_1", "prob_3_2", "sol_3_2", "prob_3_3", "sol_3_3", "prob_4_1", "sol_4_1", "prob_4_2", "sol_4_2", "prob_4_3", "sol_4_3"];
    const rows = [];
    const sortedKeys = Object.keys(studentDB).sort();
    sortedKeys.forEach(id => {
      rows.push([id, studentDB[id], "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]);
    });
    triggerCSVDownload("class_writing_obstacles_template.csv", headers, rows);
  }

  // ==========================================
  // ระบบดาวน์โหลดเทมเพลตและนำเข้าด้วย Excel (.csv) สำหรับคุณครู (ระดับชั้นเรียน)
  // ==========================================

  // ฟังก์ชันแยกแถว CSV รองรับเครื่องหมายคำพูดครอบข้อความที่มีจุลภาค
  function parseCSVRow(rowText) {
    let fields = [];
    let currentField = '';
    let inQuotes = false;
    for (let i = 0; i < rowText.length; i++) {
      let char = rowText[i];
      if (char === '"') {
        inQuotes = !inQuotes;
      } else if (char === ',' && !inQuotes) {
        fields.push(currentField.trim());
        currentField = '';
      } else {
        currentField += char;
      }
    }
    fields.push(currentField.trim());
    return fields;
  }

  // ตัวช่วยสร้างไฟล์ CSV พร้อม UTF-8 BOM
  function triggerCSVDownload(filename, headers, rows) {
    const escapeCSV = (str) => {
      if (str === null || str === undefined) return '';
      let stringified = String(str);
      if (stringified.includes(',') || stringified.includes('\n') || stringified.includes('"')) {
        stringified = stringified.replace(/"/g, '""');
        return `"${stringified}"`;
      }
      return stringified;
    };
    
    let csvContent = "\uFEFF"; // UTF-8 BOM ให้ Excel เปิดแล้วภาษาไทยไม่เพี้ยน
    csvContent += headers.map(escapeCSV).join(",") + "\n";
    csvContent += rows.map(row => row.map(escapeCSV).join(",")).join("\n");
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  // ตัวช่วยอ่านและตรวจจับรหัสอักขระไฟล์ CSV (UTF-8 หรือ Windows-874 ของ Excel)
  function readCSVFile(file, callback) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const arrayBuffer = e.target.result;
      const uint8Array = new Uint8Array(arrayBuffer);
      
      // ตรวจหา UTF-8 BOM (0xEF, 0xBB, 0xBF)
      let encoding = 'windows-874'; // ใช้เป็นค่าเริ่มต้นสำหรับ Excel ภาษาไทยบน Windows
      if (uint8Array[0] === 0xEF && uint8Array[1] === 0xBB && uint8Array[2] === 0xBF) {
        encoding = 'utf-8';
      } else {
        try {
          // ทดลองถอดรหัสเป็น UTF-8 แบบเคร่งครัด
          const utf8Decoder = new TextDecoder('utf-8', { fatal: true });
          utf8Decoder.decode(uint8Array);
          encoding = 'utf-8';
        } catch (err) {
          encoding = 'windows-874';
        }
      }
      
      const decoder = new TextDecoder(encoding);
      const decodedText = decoder.decode(uint8Array);
      callback(decodedText);
    };
    reader.readAsArrayBuffer(file);
  }

  // 1. เทมเพลตปัญหาการเขียน (ทั้งห้องเรียน)
  function downloadClassObstaclesTemplate() {
    const headers = ["รหัสนักเรียน (Primary Key)", "ชื่อนักเรียน", "prob_1_1", "sol_1_1", "prob_1_2", "sol_1_2", "prob_1_3", "sol_1_3", "prob_2_1", "sol_2_1", "prob_2_2", "sol_2_2", "prob_3_1", "sol_3_1", "prob_3_2", "sol_3_2", "prob_3_3", "sol_3_3", "prob_4_1", "sol_4_1", "prob_4_2", "sol_4_2", "prob_4_3", "sol_4_3"];
    const rows = [];
    const sortedKeys = Object.keys(studentDB).sort();
    sortedKeys.forEach(id => {
      rows.push([id, studentDB[id], "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""]);
    });
    triggerCSVDownload("class_writing_obstacles_template.csv", headers, rows);
  }

  async function importClassObstaclesCSV(input) {
    const file = input.files[0];
    if (!file) return;
    
    readCSVFile(file, async function(text) {
      const lines = text.split(/\r?\n/);
      const list = [];
      
      for (let i = 1; i < lines.length; i++) {
        if (!lines[i].trim()) continue;
        const cols = parseCSVRow(lines[i]);
        if (cols.length < 2) continue;
        
        const studentId = cols[0].trim();
        if (!studentId || studentId.includes('[') || !studentDB[studentId]) continue;
        
        const problems = {
          prob_1_1: cols[2] || '', sol_1_1: cols[3] || '',
          prob_1_2: cols[4] || '', sol_1_2: cols[5] || '',
          prob_1_3: cols[6] || '', sol_1_3: cols[7] || '',
          prob_2_1: cols[8] || '', sol_2_1: cols[9] || '',
          prob_2_2: cols[10] || '', sol_2_2: cols[11] || '',
          prob_3_1: cols[12] || '', sol_3_1: cols[13] || '',
          prob_3_2: cols[14] || '', sol_3_2: cols[15] || '',
          prob_3_3: cols[16] || '', sol_3_3: cols[17] || '',
          prob_4_1: cols[18] || '', sol_4_1: cols[19] || '',
          prob_4_2: cols[20] || '', sol_4_2: cols[21] || '',
          prob_4_3: cols[22] || '', sol_4_3: cols[23] || ''
        };
        
        list.push({ studentId, problems });
      }
      
      if (list.length === 0) {
        alert('ไม่พบข้อมูลนักเรียนที่ถูกต้องสำหรับการนำเข้าในไฟล์ CSV');
        input.value = '';
        return;
      }
      
      if (!confirm(`ยืนยันการนำเข้าข้อมูล ปัญหาการเขียน ของนักเรียนทั้งหมด ${list.length} คน ใช่หรือไม่?`)) {
        input.value = '';
        return;
      }
      
      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'bulk_save_writing_problems',
            list: list
          })
        });
        
        const responseText = await response.text();
        let res;
        try {
          res = JSON.parse(responseText);
        } catch (e) {
          throw new Error('การตอบกลับจากเซิร์ฟเวอร์ไม่ใช่ JSON: ' + responseText.substring(0, 300));
        }
        
        if (res.success) {
          alert(`นำเข้าบันทึกปัญหาการเขียนจำนวน ${list.length} คนสำเร็จ!`);
          loadTeacherDashboardSummary();
        } else {
          alert('ไม่สามารถนำเข้าข้อมูล: ' + res.error);
        }
      } catch (err) {
        console.error(err);
        alert('เกิดข้อผิดพลาด: ' + err.message);
      }
      input.value = '';
    });
  }

  // 2. เทมเพลตตรวจสอบตนเอง (ทั้งห้องเรียน)
  function downloadClassChecklistTemplate() {
    const headers = ["รหัสนักเรียน (Primary Key)", "ชื่อนักเรียน", "check_1_1", "check_1_2", "check_1_3", "check_2_1", "check_2_2", "check_3_1", "check_3_2", "check_3_3", "check_4_1", "check_4_2", "check_4_3", "notes"];
    const rows = [];
    const sortedKeys = Object.keys(studentDB).sort();
    sortedKeys.forEach(id => {
      rows.push([id, studentDB[id], "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", "ครบถ้วน", ""]);
    });
    triggerCSVDownload("class_self_checklist_template.csv", headers, rows);
  }

  async function importClassChecklistCSV(input) {
    const file = input.files[0];
    if (!file) return;
    
    readCSVFile(file, async function(text) {
      const lines = text.split(/\r?\n/);
      const list = [];
      
      for (let i = 1; i < lines.length; i++) {
        if (!lines[i].trim()) continue;
        const cols = parseCSVRow(lines[i]);
        if (cols.length < 3) continue;
        
        const studentId = cols[0].trim();
        if (!studentId || studentId.includes('[') || !studentDB[studentId]) continue;
        
        const checklist = {
          '1.1': cols[2] || 'ต้องปรับปรุง',
          '1.2': cols[3] || 'ต้องปรับปรุง',
          '1.3': cols[4] || 'ต้องปรับปรุง',
          '2.1': cols[5] || 'ต้องปรับปรุง',
          '2.2': cols[6] || 'ต้องปรับปรุง',
          '3.1': cols[7] || 'ต้องปรับปรุง',
          '3.2': cols[8] || 'ต้องปรับปรุง',
          '3.3': cols[9] || 'ต้องปรับปรุง',
          '4.1': cols[10] || 'ต้องปรับปรุง',
          '4.2': cols[11] || 'ต้องปรับปรุง',
          '4.3': cols[12] || 'ต้องปรับปรุง'
        };
        const notes = cols[13] || '';
        
        list.push({ studentId, checklist, notes });
      }
      
      if (list.length === 0) {
        alert('ไม่พบข้อมูลนักเรียนที่ถูกต้องสำหรับการนำเข้าในไฟล์ CSV');
        input.value = '';
        return;
      }
      
      if (!confirm(`ยืนยันการนำเข้าข้อมูล รายการตรวจสอบตนเอง ของนักเรียนทั้งหมด ${list.length} คน ใช่หรือไม่?`)) {
        input.value = '';
        return;
      }
      
      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'bulk_save_self_checklists',
            list: list
          })
        });
        
        const responseText = await response.text();
        let res;
        try {
          res = JSON.parse(responseText);
        } catch (e) {
          throw new Error('การตอบกลับจากเซิร์ฟเวอร์ไม่ใช่ JSON: ' + responseText.substring(0, 300));
        }
        
        if (res.success) {
          alert(`นำเข้าเช็คลิสต์ตรวจสอบตนเองของนักเรียนจำนวน ${list.length} คนสำเร็จ!`);
          loadTeacherDashboardSummary();
        } else {
          alert('ไม่สามารถนำเข้าข้อมูล: ' + res.error);
        }
      } catch (err) {
        console.error(err);
        alert('เกิดข้อผิดพลาด: ' + err.message);
      }
      input.value = '';
    });
  }

  // 3. เทมเพลตประเมินโดยเพื่อน (ทั้งห้องเรียน)
  function downloadClassPeerReviewTemplate() {
    const headers = ["รหัสนักเรียนผู้ถูกประเมิน (Primary Key)", "ชื่อนักเรียนผู้ถูกประเมิน", "รหัสนักเรียนผู้ประเมิน", "score_1_1", "score_1_2", "score_1_3", "score_1_4", "score_2_1", "score_2_2", "score_3_1", "score_3_2", "score_3_3", "score_3_4", "score_4_1", "score_4_2", "score_4_3", "strength", "improvement", "encouragement"];
    const rows = [];
    const sortedKeys = Object.keys(studentDB).sort();
    sortedKeys.forEach(id => {
      rows.push([id, studentDB[id], "[กรอกรหัสเพื่อนผู้ประเมิน]", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "ดี", "", "", ""]);
    });
    triggerCSVDownload("class_peer_review_template.csv", headers, rows);
  }

  async function importClassPeerReviewCSV(input) {
    const file = input.files[0];
    if (!file) return;
    
    readCSVFile(file, async function(text) {
      const lines = text.split(/\r?\n/);
      const list = [];
      
      for (let i = 1; i < lines.length; i++) {
        if (!lines[i].trim()) continue;
        const cols = parseCSVRow(lines[i]);
        if (cols.length < 4) continue;
        
        const studentId = cols[0].trim();
        const reviewerId = cols[2].trim();
        if (!studentId || studentId.includes('[') || !studentDB[studentId]) continue;
        if (!reviewerId || reviewerId.includes('[') || !studentDB[reviewerId]) continue;
        
        const scores = {
          '1.1': cols[3] || 'ปรับปรุง',
          '1.2': cols[4] || 'ปรับปรุง',
          '1.3': cols[5] || 'ปรับปรุง',
          '1.4': cols[6] || 'ปรับปรุง',
          '2.1': cols[7] || 'ปรับปรุง',
          '2.2': cols[8] || 'ปรับปรุง',
          '3.1': cols[9] || 'ปรับปรุง',
          '3.2': cols[10] || 'ปรับปรุง',
          '3.3': cols[11] || 'ปรับปรุง',
          '3.4': cols[12] || 'ปรับปรุง',
          '4.1': cols[13] || 'ปรับปรุง',
          '4.2': cols[14] || 'ปรับปรุง',
          '4.3': cols[15] || 'ปรับปรุง'
        };
        const strength = cols[16] || '';
        const improvement = cols[17] || '';
        const encouragement = cols[18] || '';
        
        list.push({ studentId, reviewerId, scores, strength, improvement, encouragement });
      }
      
      if (list.length === 0) {
        alert('ไม่พบข้อมูลประเมินโดยเพื่อนที่ถูกต้องในไฟล์ CSV');
        input.value = '';
        return;
      }
      
      if (!confirm(`ยืนยันการนำเข้าข้อมูล แบบประเมินผลงานโดยเพื่อน ของนักเรียนทั้งหมด ${list.length} รายการ ใช่หรือไม่?`)) {
        input.value = '';
        return;
      }
      
      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'bulk_save_peer_reviews',
            list: list
          })
        });
        
        const responseText = await response.text();
        let res;
        try {
          res = JSON.parse(responseText);
        } catch (e) {
          throw new Error('การตอบกลับจากเซิร์ฟเวอร์ไม่ใช่ JSON: ' + responseText.substring(0, 300));
        }
        
        if (res.success) {
          alert(`นำเข้าแบบประเมินผลงานโดยเพื่อนจำนวน ${list.length} รายการสำเร็จ!`);
          loadTeacherDashboardSummary();
        } else {
          alert('ไม่สามารถนำเข้าข้อมูล: ' + res.error);
        }
      } catch (err) {
        console.error(err);
        alert('เกิดข้อผิดพลาด: ' + err.message);
      }
      input.value = '';
    });
  }

  // 4. เทมเพลตสะท้อนการเรียนรู้ (ทั้งห้องเรียน)
  function downloadClassReflectionTemplate() {
    const headers = ["รหัสนักเรียน (Primary Key)", "ชื่อนักเรียน", "content_structure", "language_mechanics", "feedback_applied", "future_goals"];
    const rows = [];
    const sortedKeys = Object.keys(studentDB).sort();
    sortedKeys.forEach(id => {
      rows.push([id, studentDB[id], "", "", "", ""]);
    });
    triggerCSVDownload("class_learning_reflection_template.csv", headers, rows);
  }

  async function importClassReflectionCSV(input) {
    const file = input.files[0];
    if (!file) return;
    
    readCSVFile(file, async function(text) {
      const lines = text.split(/\r?\n/);
      const list = [];
      
      for (let i = 1; i < lines.length; i++) {
        if (!lines[i].trim()) continue;
        const cols = parseCSVRow(lines[i]);
        if (cols.length < 3) continue;
        
        const studentId = cols[0].trim();
        if (!studentId || studentId.includes('[') || !studentDB[studentId]) continue;
        
        const content_structure = cols[2] || '';
        const language_mechanics = cols[3] || '';
        const feedback_applied = cols[4] || '';
        const future_goals = cols[5] || '';
        
        list.push({ studentId, content_structure, language_mechanics, feedback_applied, future_goals });
      }
      
      if (list.length === 0) {
        alert('ไม่พบข้อมูลบันทึกการสะท้อนคิดที่ถูกต้องในไฟล์ CSV');
        input.value = '';
        return;
      }
      
      if (!confirm(`ยืนยันการนำเข้าข้อมูล บันทึกการสะท้อนคิด ของนักเรียนทั้งหมด ${list.length} คน ใช่หรือไม่?`)) {
        input.value = '';
        return;
      }
      
      try {
        const response = await fetch('api.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'bulk_save_learning_reflections',
            list: list
          })
        });
        
        const responseText = await response.text();
        let res;
        try {
          res = JSON.parse(responseText);
        } catch (e) {
          throw new Error('การตอบกลับจากเซิร์ฟเวอร์ไม่ใช่ JSON: ' + responseText.substring(0, 300));
        }
        
        if (res.success) {
          alert(`นำเข้าบันทึกการสะท้อนคิดของนักเรียนจำนวน ${list.length} คนสำเร็จ!`);
          loadTeacherDashboardSummary();
        } else {
          alert('ไม่สามารถนำเข้าข้อมูล: ' + res.error);
        }
      } catch (err) {
        console.error(err);
        alert('เกิดข้อผิดพลาด: ' + err.message);
      }
      input.value = '';
    });
  }
</script>

<?php
require_once 'footer.php';
?>
