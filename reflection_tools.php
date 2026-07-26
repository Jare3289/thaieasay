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
              <p class="text-muted small mb-3">คำชี้แจง: ให้ทำเครื่องหมายเลือกเฉพาะด้านที่พบปัญหาจริงจากร่างแรก แล้ววิเคราะห์บันทึกอุปสรรคที่พบพร้อมเสนอแนวทางการแก้ไข ด้านที่ไม่ได้เลือกไม่จำเป็นต้องกรอก</p>

              <!-- ตัวนับจำนวนด้านที่เลือก -->
              <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3" role="status">
                <i class="bi bi-check2-square fs-5"></i>
                <span class="fw-bold">เลือกแล้ว <span id="probSelectedCount">0</span> ด้าน</span>
                <span class="text-muted small">(เลือกเฉพาะด้านที่พบปัญหาจริงเท่านั้น)</span>
              </div>

              <div class="table-responsive rounded-3 border mb-4">
                <table class="table table-striped table-hover align-middle mb-0">
                  <thead class="table-primary text-dark">
                    <tr>
                      <th style="width: 26%;">ด้านการประเมิน</th>
                      <th style="width: 37%;">ปัญหาที่พบ (Obstacle)</th>
                      <th style="width: 37%;">แนวทางแก้ไขปรับปรุง (Action Plan)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- ด้านเนื้อหาสาระ -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">1) ด้านเนื้อหาสาระ</td></tr>
                    <tr data-prob-row="1_1">
                      <td class="ps-3 small">
                        <div class="mb-1">1.1 ความตรงประเด็น</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_1_1" data-row="1_1">
                          <label class="form-check-label small text-primary" for="toggle_1_1">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_1_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_1_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="1_2">
                      <td class="ps-3 small">
                        <div class="mb-1">1.2 แก่นเรื่องที่ชัดเจน</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_1_2" data-row="1_2">
                          <label class="form-check-label small text-primary" for="toggle_1_2">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_1_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_1_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="1_3">
                      <td class="ps-3 small">
                        <div class="mb-1">1.3 การขยายความและเหตุผล</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_1_3" data-row="1_3">
                          <label class="form-check-label small text-primary" for="toggle_1_3">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_1_3" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_1_3" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>

                    <!-- ด้านองค์ประกอบและการลำดับ -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">2) ด้านองค์ประกอบและการลำดับ</td></tr>
                    <tr data-prob-row="2_1">
                      <td class="ps-3 small">
                        <div class="mb-1">2.1 ความครบถ้วนขององค์ประกอบ</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_2_1" data-row="2_1">
                          <label class="form-check-label small text-primary" for="toggle_2_1">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_2_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_2_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="2_2">
                      <td class="ps-3 small">
                        <div class="mb-1">2.2 การลำดับประเด็นเป็นระบบ</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_2_2" data-row="2_2">
                          <label class="form-check-label small text-primary" for="toggle_2_2">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_2_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_2_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>

                    <!-- ด้านการใช้สำนวนภาษา -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">3) ด้านการใช้สำนวนภาษา</td></tr>
                    <tr data-prob-row="3_1">
                      <td class="ps-3 small">
                        <div class="mb-1">3.1 การใช้ประโยคถูกต้อง</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_3_1" data-row="3_1">
                          <label class="form-check-label small text-primary" for="toggle_3_1">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_3_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_3_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="3_2">
                      <td class="ps-3 small">
                        <div class="mb-1">3.2 การเลือกใช้คำ</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_3_2" data-row="3_2">
                          <label class="form-check-label small text-primary" for="toggle_3_2">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_3_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_3_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="3_3">
                      <td class="ps-3 small">
                        <div class="mb-1">3.3 ระดับภาษาเหมาะสม</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_3_3" data-row="3_3">
                          <label class="form-check-label small text-primary" for="toggle_3_3">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_3_3" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_3_3" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>

                    <!-- ด้านอักขรวิธีและกลไกการเขียน -->
                    <tr class="table-light"><td colspan="3" class="fw-bold">4) ด้านอักขรวิธีและกลไกการเขียน</td></tr>
                    <tr data-prob-row="4_1">
                      <td class="ps-3 small">
                        <div class="mb-1">4.1 การสะกดคำถูกต้อง</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_4_1" data-row="4_1">
                          <label class="form-check-label small text-primary" for="toggle_4_1">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_4_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_4_1" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="4_2">
                      <td class="ps-3 small">
                        <div class="mb-1">4.2 การเว้นวรรค</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_4_2" data-row="4_2">
                          <label class="form-check-label small text-primary" for="toggle_4_2">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_4_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_4_2" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
                    </tr>
                    <tr data-prob-row="4_3">
                      <td class="ps-3 small">
                        <div class="mb-1">4.3 ความเรียบร้อยของลายมือและกระดาษ</div>
                        <div class="form-check form-switch">
                          <input class="form-check-input prob-toggle" type="checkbox" role="switch" id="toggle_4_3" data-row="4_3">
                          <label class="form-check-label small text-primary" for="toggle_4_3">พบปัญหาในด้านนี้</label>
                        </div>
                      </td>
                      <td><textarea name="prob_4_3" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุปัญหา..." disabled></textarea></td>
                      <td><textarea name="sol_4_3" class="form-control form-control-sm prob-input" rows="2" placeholder="ระบุแนวทางแก้ไข..." disabled></textarea></td>
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
                    <tr class="table-light"><td colspan="5" class="fw-bold">2. ด้านองค์ประกอบและการลำดับ</td></tr>
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

            <style>
              .hover-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
                border-color: #0ea5e9 !important;
              }
              .sub-tab-btn.active {
                background-color: #0ea5e9 !important;
                color: #fff !important;
              }
              .sub-tab-btn {
                color: #64748b;
                border: 1px solid #cbd5e1;
                border-radius: 50px;
                padding: 0.5rem 1.25rem;
                font-weight: 600;
                transition: all 0.2s;
              }
              .sub-tab-btn:hover {
                background-color: #f1f5f9;
                color: #0f172a;
              }
              .monitoring-text-area {
                font-size: 0.8rem;
                line-height: 1.4;
                max-height: 150px;
                overflow-y: auto;
              }
              /* Word Cloud: คำที่พบบ่อยจะมีขนาดใหญ่ขึ้น */
              .word-cloud {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                gap: 0.4rem 0.85rem;
                padding: 0.5rem 0.25rem;
              }
              .wc-item {
                display: inline-flex;
                align-items: baseline;
                gap: 0.2rem;
                line-height: 1.1;
                font-weight: 700;
                transition: transform 0.15s ease;
                cursor: default;
              }
              .wc-item:hover { transform: scale(1.08); }
              .wc-item .wc-count {
                font-size: 0.7rem;
                font-weight: 600;
                opacity: 0.65;
              }
              .suggest-item {
                border-left: 4px solid #f59e0b;
                background: #ffffff;
              }
              /* ตารางผลรายบุคคล: หมุนลูกศรเมื่อขยายแถว และไม่ให้แถวรายละเอียดมี hover */
              .indiv-caret { transition: transform 0.2s ease; }
              .indiv-row[aria-expanded="true"] .indiv-caret { transform: rotate(180deg); }
              .indiv-row[aria-expanded="true"] { background-color: #eff6ff; }
              .indiv-detail-row:hover > * { background-color: transparent !important; }
            </style>

            <div class="mb-4">
              <ul class="nav nav-pills gap-2 mb-4 justify-content-center" id="monitoringTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link sub-tab-btn active" id="pill-obstacles-tab" data-bs-toggle="pill" data-bs-target="#pill-obstacles" type="button" role="tab" aria-controls="pill-obstacles" aria-selected="true">
                    📝 1. อุปสรรคและแผนการแก้ปัญหา (<span id="countObstacles">0</span> คน)
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link sub-tab-btn" id="pill-checklist-tab" data-bs-toggle="pill" data-bs-target="#pill-checklist" type="button" role="tab" aria-controls="pill-checklist" aria-selected="false">
                    📋 2. การตรวจสอบตนเอง (<span id="countChecklist">0</span> คน)
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link sub-tab-btn" id="pill-reflection-tab" data-bs-toggle="pill" data-bs-target="#pill-reflection" type="button" role="tab" aria-controls="pill-reflection" aria-selected="false">
                    💡 3. การสะท้อนคิดการเรียนรู้ (<span id="countReflection">0</span> คน)
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link sub-tab-btn" id="pill-enabling-tab" data-bs-toggle="pill" data-bs-target="#pill-enabling" type="button" role="tab" aria-controls="pill-enabling" aria-selected="false">
                    🌱 4. ขั้นส่งเสริมการเรียนรู้ (Enabling)
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link sub-tab-btn" id="pill-individual-tab" data-bs-toggle="pill" data-bs-target="#pill-individual" type="button" role="tab" aria-controls="pill-individual" aria-selected="false">
                    👤 5. ผลรายบุคคล (<span id="countIndividual">0</span> คน)
                  </button>
                </li>
              </ul>

              <div class="tab-content" id="monitoringTabContent">
                <!-- Tab 1: อุปสรรคการเขียน -->
                <div class="tab-pane fade show active" id="pill-obstacles" role="tabpanel" aria-labelledby="pill-obstacles-tab">
                  <!-- Keyword word cloud (คำที่พบบ่อยจะใหญ่ขึ้น) -->
                  <div class="card border-0 bg-light p-3 mb-4 rounded-4" id="obstaclesTrendsPanel" style="display: none;">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cloud-fill text-danger"></i> 📊 คลาวด์คำสำคัญที่เป็นอุปสรรคบ่อยในห้องเรียน (Obstacle Keywords)</h6>
                    <p class="text-muted small mb-2" style="font-size: 0.75rem;">แสดงเฉพาะคำที่เกี่ยวข้องกับปัญหา/เกณฑ์การเขียน และคำที่พบบ่อยจะมีขนาดใหญ่ขึ้น</p>
                    <div class="word-cloud" id="obstaclesTrendsContainer"></div>
                  </div>
                  <div class="row g-3" id="gridObstacles">
                    <div class="text-center py-5 text-muted">กำลังโหลดรายงานอุปสรรคการเขียน...</div>
                  </div>
                </div>

                <!-- Tab 2: ตรวจสอบตนเอง -->
                <div class="tab-pane fade" id="pill-checklist" role="tabpanel" aria-labelledby="pill-checklist-tab">
                  <div class="row g-3" id="gridChecklist">
                    <div class="text-center py-5 text-muted">กำลังโหลดรายงานการตรวจสอบตนเอง...</div>
                  </div>
                </div>

                <!-- Tab 3: สะท้อนคิด -->
                <div class="tab-pane fade" id="pill-reflection" role="tabpanel" aria-labelledby="pill-reflection-tab">
                  <!-- Keyword word cloud (คำที่พบบ่อยจะใหญ่ขึ้น) -->
                  <div class="card border-0 bg-light p-3 mb-4 rounded-4" id="reflectionTrendsPanel" style="display: none;">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-cloud-fill text-info"></i> 📊 คลาวด์คำสำคัญในบทสะท้อนคิด แยกตามคำถามแต่ละข้อ (Reflection Keywords)</h6>
                    <p class="text-muted small mb-2" style="font-size: 0.75rem;">แสดงเฉพาะคำที่เกี่ยวข้องกับปัญหา/เกณฑ์การเขียน และคำที่พบบ่อยจะมีขนาดใหญ่ขึ้น</p>
                    <div class="word-cloud" id="reflectionTrendsContainer"></div>
                  </div>
                  <div class="row g-3" id="gridReflection">
                    <div class="text-center py-5 text-muted">กำลังโหลดรายงานสะท้อนคิดการเรียนรู้...</div>
                  </div>
                </div>

                <!-- Tab 4: ขั้นส่งเสริมการเรียนรู้ (Enabling) — รวมข้อเสนอแนะขอบเขตการเรียนรู้เพิ่มเติมทั้งหมด -->
                <div class="tab-pane fade" id="pill-enabling" role="tabpanel" aria-labelledby="pill-enabling-tab">
                  <div class="card border-0 rounded-4 p-3 mb-4" style="background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-blue) 100%);">
                    <h5 class="fw-bold text-white mb-1"><i class="bi bi-lightbulb-fill"></i> ขั้นส่งเสริมการเรียนรู้ (Enabling): ขอบเขตการเรียนรู้เพิ่มเติมที่แนะนำ</h5>
                    <p class="text-white-50 small mb-0">รวบรวมข้อเสนอแนะจากทั้ง 3 แหล่งข้อมูลไว้ในที่เดียว แต่ละส่วนมีบทวิเคราะห์ภาพรวมและรายการที่จัดลำดับความสำคัญจากมากไปน้อย เพื่อให้ครูออกแบบกิจกรรมเสริมได้ตรงจุด</p>
                  </div>

                  <!-- 1) จากอุปสรรคและแผนการแก้ปัญหา -->
                  <div class="card border-0 p-3 mb-4 rounded-4" id="obstaclesSuggestPanel" style="display: none; background: linear-gradient(135deg,#fff7ed,#ffedd5);">
                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-exclamation-octagon text-danger"></i> 1. จากอุปสรรคและแผนการแก้ปัญหา 📝</h6>
                    <p class="text-muted small mb-3" style="font-size: 0.75rem;">แนะนำจากอุปสรรคที่นักเรียนพบบ่อยที่สุด เพื่อออกแบบกิจกรรมเสริมให้ตรงจุด</p>
                    <div id="obstaclesSuggestContainer"></div>
                  </div>

                  <!-- 2) จากการตรวจสอบตนเอง -->
                  <div class="card border-0 p-3 mb-4 rounded-4" id="checklistSuggestPanel" style="display: none; background: linear-gradient(135deg,#fff7ed,#ffedd5);">
                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-patch-check text-success"></i> 2. จากการตรวจสอบตนเอง 📋</h6>
                    <p class="text-muted small mb-3" style="font-size: 0.75rem;">แนะนำจากเกณฑ์ที่นักเรียนประเมินตนเองว่ายัง "ต้องปรับปรุง" หรือ "ทำได้บางส่วน" มากที่สุด</p>
                    <div id="checklistSuggestContainer"></div>
                  </div>

                  <!-- 3) จากบทสะท้อนคิด -->
                  <div class="card border-0 p-3 mb-4 rounded-4" id="reflectionSuggestPanel" style="display: none; background: linear-gradient(135deg,#fff7ed,#ffedd5);">
                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-lightbulb text-info"></i> 3. จากบทสะท้อนการเรียนรู้ 💡</h6>
                    <p class="text-muted small mb-3" style="font-size: 0.75rem;">แนะนำจากประเด็นที่นักเรียนสะท้อนถึงบ่อยที่สุด เพื่อต่อยอดสู่การเรียนรู้ขั้นถัดไป</p>
                    <div id="reflectionSuggestContainer"></div>
                  </div>

                  <!-- แสดงเมื่อยังไม่มีข้อเสนอแนะเลย -->
                  <div id="enablingEmpty" class="text-center py-5 text-muted" style="display: none;">
                    <div class="fs-1 mb-2">🌱</div>
                    <p class="mb-0">ยังไม่มีข้อมูลเพียงพอสำหรับสร้างข้อเสนอแนะขอบเขตการเรียนรู้เพิ่มเติม</p>
                  </div>
                </div>

                <!-- Tab 5: ผลรายบุคคล (แสดงข้อมูลครบทุกคนในที่เดียว) -->
                <div class="tab-pane fade" id="pill-individual" role="tabpanel" aria-labelledby="pill-individual-tab">
                  <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> ตารางผลรายบุคคลของนักเรียนทุกคนที่มีข้อมูล คลิกที่แถวเพื่อขยายดูรายละเอียดทั้งหมด (อุปสรรค + ตรวจสอบตนเอง + สะท้อนคิด) โดยไม่ต้องสลับไปแท็บอื่น</p>
                  <div id="gridIndividual" class="table-responsive">
                    <div class="text-center py-5 text-muted">กำลังโหลดผลรายบุคคล...</div>
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
                    <h6 class="fw-bold text-dark border-bottom pb-2"><i class="bi bi-lightbulb text-info"></i> 4. แบบสะท้อนการเรียนรู้ (Learning Reflection)</h6>
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
  let loadTeacherDashboardSummary = function() {}; // Global placeholder to prevent ReferenceError

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

  // คำสำคัญที่ "สื่อถึงปัญหา/ทักษะการเขียนตามเกณฑ์" เท่านั้น (คัดมาแล้ว ไม่นับคำทั่วไป)
  // เลือกไม่ให้ซ้อนทับกันเป็น substring เพื่อไม่ให้ค่าความถี่บวมเกินจริง
  const PROBLEM_KEYWORDS = [
    // ด้านเนื้อหาสาระ
    'ประเด็น', 'ใจความ', 'แก่นเรื่อง', 'เนื้อหา', 'สาระ',
    'ขยายความ', 'เหตุผล', 'ตัวอย่าง', 'รายละเอียด',
    // ด้านองค์ประกอบและการลำดับ
    'โครงเรื่อง', 'โครงสร้าง', 'องค์ประกอบ', 'คำนำ', 'สรุป', 'ย่อหน้า', 'ลำดับ', 'เชื่อมโยง', 'เรียบเรียง',
    // ด้านสำนวนภาษา
    'ประโยค', 'ไวยากรณ์', 'คำศัพท์', 'คำเชื่อม', 'ระดับภาษา', 'ภาษาพูด', 'สำนวน', 'คำซ้ำ', 'เลือกใช้คำ',
    // ด้านอักขรวิธีและกลไกการเขียน
    'สะกด', 'เว้นวรรค', 'วรรคตอน', 'เครื่องหมาย', 'ลายมือ', 'เรียบร้อย', 'สะอาด',
    // คำที่บ่งชี้ว่าเป็นปัญหา/อุปสรรคโดยตรง
    'ปัญหา', 'อุปสรรค', 'ยาก', 'สับสน', 'ผิดพลาด', 'ไม่เข้าใจ', 'กังวล', 'เวลา'
  ];

  // วิเคราะห์คำสำคัญ: นับเฉพาะคำที่คัดไว้ (substring) ไม่นับคำทั่วไปที่ไม่สื่อถึงปัญหา
  // จึงทำให้ Word Cloud แสดงเฉพาะคำที่เกี่ยวข้องกับปัญหา/เกณฑ์การเขียนจริง
  function analyzeKeywords(texts) {
    const counts = {};
    PROBLEM_KEYWORDS.forEach(kw => counts[kw] = 0);

    (texts || []).forEach(text => {
      if (!text) return;
      const lowerText = text.toLowerCase();
      PROBLEM_KEYWORDS.forEach(kw => {
        let idx = lowerText.indexOf(kw), n = 0;
        while (idx !== -1) { n++; idx = lowerText.indexOf(kw, idx + kw.length); }
        if (n > 0) counts[kw] += n;
      });
    });

    return Object.keys(counts)
      .map(key => ({ keyword: key, count: counts[key] }))
      .filter(item => item.count > 0)
      .sort((a, b) => b.count - a.count)
      .slice(0, 25);
  }

  // แผนที่เกณฑ์ → ขอบเขตการเรียนรู้ที่แนะนำ (ใช้สร้างข้อเสนอแนะขั้น Enabling)
  const criteriaToLearningArea = {
    '1_1': 'ฝึกอ่านวิเคราะห์ขอบเขตหัวข้อและการเขียนให้ตรงประเด็น',
    '1_2': 'ฝึกกำหนดแก่นเรื่องและประโยคใจความสำคัญ (Thesis Statement)',
    '1_3': 'ฝึกการขยายความด้วยเหตุผลและตัวอย่างสนับสนุน',
    '2_1': 'ทบทวนโครงสร้างเรียงความ 3 ส่วน (คำนำ–เนื้อเรื่อง–สรุป)',
    '2_2': 'ฝึกวางโครงเรื่องและลำดับความคิดอย่างเป็นระบบ',
    '3_1': 'ทบทวนหลักไวยากรณ์และการสร้างประโยคที่หลากหลาย',
    '3_2': 'ฝึกการเลือกใช้คำและคำเชื่อมให้เหมาะสม',
    '3_3': 'ฝึกการใช้ระดับภาษาให้เหมาะกับงานเขียนทางการ',
    '4_1': 'ฝึกการสะกดคำให้ถูกต้องตามพจนานุกรม',
    '4_2': 'ทบทวนหลักการเว้นวรรคตอน',
    '4_3': 'เน้นความสะอาดเรียบร้อยและการจัดหน้ากระดาษ'
  };

  // แผนที่คำสำคัญ → ขอบเขตการเรียนรู้ (ใช้แปลงคีย์เวิร์ดในบทสะท้อนคิดเป็นข้อเสนอแนะ)
  const keywordToLearningArea = {
    // ด้านเนื้อหาสาระ
    'ประเด็น': criteriaToLearningArea['1_1'],
    'ใจความ': criteriaToLearningArea['1_1'],
    'เนื้อหา': criteriaToLearningArea['1_1'],
    'สาระ': criteriaToLearningArea['1_1'],
    'แก่นเรื่อง': criteriaToLearningArea['1_2'],
    'ขยายความ': criteriaToLearningArea['1_3'],
    'เหตุผล': criteriaToLearningArea['1_3'],
    'ตัวอย่าง': criteriaToLearningArea['1_3'],
    'รายละเอียด': criteriaToLearningArea['1_3'],
    // ด้านองค์ประกอบและการลำดับ
    'องค์ประกอบ': criteriaToLearningArea['2_1'],
    'โครงสร้าง': criteriaToLearningArea['2_1'],
    'คำนำ': criteriaToLearningArea['2_1'],
    'สรุป': criteriaToLearningArea['2_1'],
    'ย่อหน้า': criteriaToLearningArea['2_1'],
    'โครงเรื่อง': criteriaToLearningArea['2_2'],
    'ลำดับ': criteriaToLearningArea['2_2'],
    'เชื่อมโยง': criteriaToLearningArea['2_2'],
    'เรียบเรียง': criteriaToLearningArea['2_2'],
    // ด้านสำนวนภาษา
    'ประโยค': criteriaToLearningArea['3_1'],
    'ไวยากรณ์': criteriaToLearningArea['3_1'],
    'คำศัพท์': criteriaToLearningArea['3_2'],
    'คำเชื่อม': criteriaToLearningArea['3_2'],
    'สำนวน': criteriaToLearningArea['3_2'],
    'คำซ้ำ': criteriaToLearningArea['3_2'],
    'เลือกใช้คำ': criteriaToLearningArea['3_2'],
    'ระดับภาษา': criteriaToLearningArea['3_3'],
    'ภาษาพูด': criteriaToLearningArea['3_3'],
    // ด้านอักขรวิธีและกลไกการเขียน
    'สะกด': criteriaToLearningArea['4_1'],
    'เว้นวรรค': criteriaToLearningArea['4_2'],
    'วรรคตอน': criteriaToLearningArea['4_2'],
    'เครื่องหมาย': criteriaToLearningArea['4_2'],
    'ลายมือ': criteriaToLearningArea['4_3'],
    'เรียบร้อย': criteriaToLearningArea['4_3'],
    'สะอาด': criteriaToLearningArea['4_3']
  };

  // สร้าง Word Cloud: คำที่พบบ่อยจะมีขนาดตัวอักษรใหญ่ขึ้น
  function renderWordCloud(container, keywords, kind) {
    if (!container) return;
    if (!keywords || keywords.length === 0) { container.innerHTML = '<span class="text-muted small">- ยังไม่มีข้อมูลคำสำคัญ -</span>'; return; }
    const counts = keywords.map(k => k.count);
    const max = Math.max(...counts);
    const min = Math.min(...counts);
    const minSize = 0.9, maxSize = 2.3;
    const palette = (kind === 'obstacle')
      ? ['#b91c1c', '#dc2626', '#ea580c', '#d97706']
      : ['#0e7490', '#0891b2', '#0284c7', '#2563eb'];
    let html = '';
    keywords.forEach((item, i) => {
      const ratio = (max === min) ? 1 : (item.count - min) / (max - min);
      const size = (minSize + ratio * (maxSize - minSize)).toFixed(2);
      const color = palette[i % palette.length];
      html += `<span class="wc-item" style="font-size:${size}rem; color:${color};" title="พบ ${item.count} ครั้ง">${item.keyword}<span class="wc-count">${item.count}</span></span>`;
    });
    container.innerHTML = html;
  }

  // ป้ายกำกับคำถามของบทสะท้อนคิด (ใช้แยกคลาวด์คำสำคัญเป็นบล็อกตามคำถาม)
  const REFLECTION_FIELD_LABELS = {
    'content_structure':  '1. ด้านเนื้อหาสาระและองค์ประกอบ',
    'language_mechanics': '2. ด้านการใช้สำนวนภาษาและอักขรวิธี',
    'feedback_applied':   '3. การนำข้อเสนอแนะไปปรับปรุงงาน',
    'future_goals':       '4. การประยุกต์ใช้และเป้าหมายในอนาคต'
  };

  // แสดงคลาวด์คำสำคัญของบทสะท้อนคิดโดยแยกเป็นบล็อกตามคำถามแต่ละข้อ
  // byField = { content_structure: [{keyword,count}], ... } — คืน true หากมีข้อมูลอย่างน้อยหนึ่งบล็อก
  function renderReflectionCloudByField(container, byField) {
    if (!container) return false;
    let anyData = false;
    let html = '';
    Object.keys(REFLECTION_FIELD_LABELS).forEach(key => {
      const kws = (byField && byField[key]) ? byField[key] : [];
      if (kws.length > 0) anyData = true;
      html += `
        <div class="col-md-6 col-12">
          <div class="p-3 bg-white rounded-3 shadow-sm h-100 border">
            <div class="fw-bold small text-info-emphasis mb-2 border-bottom pb-1">${REFLECTION_FIELD_LABELS[key]}</div>
            <div class="word-cloud" data-field="${key}"></div>
          </div>
        </div>`;
    });
    container.className = 'row g-3';
    container.innerHTML = html;
    Object.keys(REFLECTION_FIELD_LABELS).forEach(key => {
      const el = container.querySelector(`.word-cloud[data-field="${key}"]`);
      const kws = (byField && byField[key]) ? byField[key] : [];
      if (kws.length > 0) renderWordCloud(el, kws, 'reflection');
      else if (el) el.innerHTML = '<span class="text-muted small">- ยังไม่มีคำสำคัญ -</span>';
    });
    return anyData;
  }

  // สร้างบทวิเคราะห์ (narrative) สรุปภาพรวมของข้อมูลในแต่ละด้าน
  // items ต้องเรียงจากสำคัญมากไปน้อยแล้ว และมีฟิลด์ label, count, unit
  function buildEnablingNarrative(kind, items, totalStudents) {
    if (!items || items.length === 0) return '';
    const lead = (kind === 'obstacle')
      ? 'จากการวิเคราะห์แบบบันทึกอุปสรรคการเขียนของนักเรียนทั้งชั้น'
      : (kind === 'checklist')
        ? 'จากผลการประเมินตนเองของนักเรียน (Self-Assessment)'
        : 'จากบทสะท้อนการเรียนรู้ของนักเรียน';
    const top = items.slice(0, 3).map(it => `“${it.label}” (${it.count} ${it.unit})`);
    let body = `${lead} พบว่าประเด็นที่ปรากฏเด่นชัดที่สุดคือ ${top[0]}`;
    if (top.length > 1) body += ` รองลงมาได้แก่ ${top.slice(1).join(' และ ')}`;
    body += ' ';
    if (kind === 'obstacle') {
      body += 'สะท้อนว่านักเรียนจำนวนไม่น้อยยังติดขัดในทักษะกลุ่มเดียวกันนี้ ครูจึงควรออกแบบกิจกรรมในขั้นส่งเสริมการเรียนรู้ (Enabling) ที่มุ่งแก้ปัญหาดังกล่าวเป็นลำดับแรก เพื่อลดช่องว่างก่อนเข้าสู่การเขียนอิสระ';
    } else if (kind === 'checklist') {
      body += 'สะท้อนว่าเกณฑ์เหล่านี้เป็นจุดที่นักเรียนประเมินว่าตนเองยังทำได้ไม่เต็มที่ ควรได้รับการฝึกซ้ำและให้ข้อมูลย้อนกลับอย่างใกล้ชิดในขั้น Enabling';
    } else {
      body += 'สะท้อนว่าเป็นประเด็นที่นักเรียนให้ความสำคัญหรือยังกังวลอยู่ ครูสามารถต่อยอดเป็นหัวข้อเสริมในขั้น Enabling เพื่อเชื่อมสิ่งที่นักเรียนสะท้อนไปสู่การพัฒนาขั้นถัดไป';
    }
    body += ' โดยเรียงลำดับความสำคัญจากมากไปน้อยดังนี้:';
    return body;
  }

  // สร้างข้อเสนอแนะขอบเขตการเรียนรู้เพิ่มเติม (ขั้น Enabling)
  // แสดง "บทวิเคราะห์" ก่อน แล้วจึงแจกแจงเป็นข้อ เรียงจากจำเป็นมากที่สุดไปน้อยที่สุด
  function renderEnablingSuggestions(panel, container, narrative, items) {
    if (!panel || !container) return;
    if (!items || items.length === 0) { panel.style.display = 'none'; container.innerHTML = ''; return; }
    const priorityLabel = (i) => (i === 0) ? 'จำเป็นเร่งด่วนที่สุด' : (i <= 2 ? 'สำคัญมาก' : 'ควรเสริม');
    const priorityClass = (i) => (i === 0) ? 'bg-danger' : (i <= 2 ? 'bg-warning text-dark' : 'bg-secondary');

    let html = '';
    // 1) บทวิเคราะห์เชิงบรรยาย
    if (narrative) {
      html += `<div class="p-3 mb-3 bg-white rounded-3 shadow-sm border-start border-4 border-warning">
                 <div class="fw-bold text-dark small mb-1"><i class="bi bi-journal-richtext text-warning"></i> บทวิเคราะห์ภาพรวม</div>
                 <p class="mb-0 small text-dark" style="line-height: 1.75;">${narrative}</p>
               </div>`;
    }
    // 2) แจกแจงเป็นข้อ เรียงตามลำดับความสำคัญ
    html += '<ol class="list-group list-group-numbered">';
    items.forEach((it, i) => {
      html += `
        <li class="list-group-item d-flex justify-content-between align-items-start border-0 bg-transparent px-0 py-1">
          <div class="ms-2 me-auto">
            <div class="fw-bold text-dark small">${it.area}</div>
            <div class="text-muted" style="font-size: 0.75rem;">${it.reason}</div>
          </div>
          <span class="badge ${priorityClass(i)} rounded-pill flex-shrink-0">${priorityLabel(i)}</span>
        </li>`;
    });
    html += '</ol>';

    container.innerHTML = html;
    panel.style.display = 'block';
  }

  // ==========================================
  // สำหรับนักเรียน: โหลดข้อมูลเดิม / บันทึกข้อมูล
  // ==========================================
  
  if (userRole === 'student') {
    // --- ตัวช่วยจัดการสวิตช์ "พบปัญหาในด้านนี้" ของแบบบันทึกปัญหาการเขียน ---
    const PROB_ROW_KEYS = ['1_1','1_2','1_3','2_1','2_2','3_1','3_2','3_3','4_1','4_2','4_3'];

    // เปิด/ปิดช่องกรอกของแถวตามสถานะสวิตช์
    function setProbRowEnabled(rowKey, enabled) {
      const form = document.getElementById('formObstacles');
      if (!form) return;
      ['prob_' + rowKey, 'sol_' + rowKey].forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (!input) return;
        input.disabled = !enabled;
        if (!enabled) input.value = '';
      });
      const tr = form.querySelector(`tr[data-prob-row="${rowKey}"]`);
      if (tr) tr.classList.toggle('table-warning', enabled);
    }

    // อัปเดตตัวนับจำนวนด้านที่เลือก
    function updateProbSelectedCount() {
      const checked = document.querySelectorAll('#formObstacles .prob-toggle:checked').length;
      const counter = document.getElementById('probSelectedCount');
      if (counter) counter.textContent = checked;
    }

    // ผูก event ให้สวิตช์ทุกตัว
    document.querySelectorAll('#formObstacles .prob-toggle').forEach(cb => {
      cb.addEventListener('change', () => {
        setProbRowEnabled(cb.dataset.row, cb.checked);
        updateProbSelectedCount();
      });
    });

    // 1. โหลดข้อมูลแบบปัญหาการเขียนเดิม
    async function loadStudentWritingProblems() {
      try {
        const response = await fetch(`api.php?action=get_writing_problems&studentId=${currentUserId}&_t=${new Date().getTime()}`);
        const res = await response.json();
        if (res.success && res.data) {
          const d = res.data;
          const form = document.getElementById('formObstacles');
          // เปิดเฉพาะแถวที่มีข้อมูลเดิม แล้วเติมค่า พร้อมติ๊กสวิตช์ให้อัตโนมัติ
          PROB_ROW_KEYS.forEach(rowKey => {
            const probVal = (d['prob_' + rowKey] || '').trim();
            const solVal  = (d['sol_' + rowKey] || '').trim();
            const hasData = probVal !== '' || solVal !== '';
            const toggle = document.getElementById('toggle_' + rowKey);
            if (toggle) toggle.checked = hasData;
            setProbRowEnabled(rowKey, hasData);
            if (hasData) {
              const probInput = form.querySelector(`[name="prob_${rowKey}"]`);
              const solInput  = form.querySelector(`[name="sol_${rowKey}"]`);
              if (probInput) probInput.value = d['prob_' + rowKey] || '';
              if (solInput)  solInput.value  = d['sol_' + rowKey] || '';
            }
          });
          updateProbSelectedCount();
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
      const form = e.target;
      // ส่งเฉพาะแถวที่ติ๊กสวิตช์ไว้และมีข้อมูลจริง แถวที่ไม่ได้เลือกจะถูกล้างค่า (null) ในฐานข้อมูล
      const problems = {};
      PROB_ROW_KEYS.forEach(rowKey => {
        const toggle = document.getElementById('toggle_' + rowKey);
        if (toggle && toggle.checked) {
          const probVal = (form.querySelector(`[name="prob_${rowKey}"]`) || {}).value || '';
          const solVal  = (form.querySelector(`[name="sol_${rowKey}"]`)  || {}).value || '';
          if (probVal.trim() !== '' || solVal.trim() !== '') {
            problems['prob_' + rowKey] = probVal;
            problems['sol_' + rowKey]  = solVal;
          }
        }
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
    loadTeacherDashboardSummary = async function() {
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
          
          // แสดงรายชื่อการ์ดแยกตามหัวข้อการประเมิน (Topics) และกรองเฉพาะคนที่มีข้อมูล
          const gridObstacles = document.getElementById('gridObstacles');
          const gridChecklist = document.getElementById('gridChecklist');
          const gridReflection = document.getElementById('gridReflection');

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

          let obstaclesHtml = '';
          let checklistHtml = '';
          let reflectionHtml = '';

          let countObstaclesStudentsSet = new Set();
          let countChecklistStudentsSet = new Set();
          let countReflectionStudentsSet = new Set();

          const allObstaclesTexts = [];
          const allReflectionTexts = [];
          const reflectionTextsByField = { content_structure: [], language_mechanics: [], feedback_applied: [], future_goals: [] };

          // ข้อมูลสำหรับข้อเสนอแนะขั้น Enabling
          const obstacleCounts = {}; // key เกณฑ์ → จำนวนนักเรียนที่พบอุปสรรค
          const checklistWeak = {};  // key เกณฑ์ → จำนวนนักเรียนที่ยังต้องปรับปรุง/ทำได้บางส่วน

          // ผลรายบุคคล (แสดงครบทุกคนในที่เดียว)
          let individualHtml = '';
          let countIndividualStudentsSet = new Set();

          if (res.students_details && res.students_details.length > 0) {
            // 1. หมวดอุปสรรคการเขียน (จัดกลุ่มตาม 11 เกณฑ์)
            Object.keys(criteriaLabelMap).forEach(key => {
              let topicStudentsHtml = '';
              let topicStudentCount = 0;

              res.students_details.forEach(student => {
                const rawProb = student[`prob_${key}`] ? student[`prob_${key}`].trim() : '';
                const rawSol = student[`sol_${key}`] ? student[`sol_${key}`].trim() : '';

                if (rawProb !== '' || rawSol !== '') {
                  topicStudentCount++;
                  countObstaclesStudentsSet.add(student.student_id);
                  if (rawProb !== '') allObstaclesTexts.push(rawProb);
                  if (rawSol !== '') allObstaclesTexts.push(rawSol);

                  topicStudentsHtml += `
                    <div class="border-bottom pb-2 mb-2">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <a href="javascript:void(0)" onclick="viewStudentDetails('${student.student_id}')" class="fw-bold text-primary small text-decoration-none"><i class="bi bi-person-circle"></i> ${student.student_id} - ${student.student_name}</a>
                      </div>
                      <div class="text-danger ps-2 small" style="font-size: 0.75rem;"><strong>อุปสรรค:</strong> ${rawProb || '-'}</div>
                      <div class="text-success ps-2 small" style="font-size: 0.75rem;"><strong>แผนแก้ปัญหา:</strong> ${rawSol || '-'}</div>
                    </div>
                  `;
                }
              });

              if (topicStudentCount > 0) {
                obstacleCounts[key] = topicStudentCount;
                obstaclesHtml += `
                  <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card h-100 border border-light shadow-sm rounded-4">
                      <div class="card-header bg-danger-subtle border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-danger-emphasis mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${criteriaLabelMap[key]}</h6>
                        <span class="badge bg-danger rounded-pill">${topicStudentCount} คน</span>
                      </div>
                      <div class="card-body py-2">
                        <div class="monitoring-text-area" style="max-height: 250px;">
                          ${topicStudentsHtml}
                        </div>
                      </div>
                    </div>
                  </div>
                `;
              }
            });

            // 2. หมวดตรวจสอบตนเอง (จัดกลุ่มตาม 11 เกณฑ์)
            Object.keys(criteriaLabelMap).forEach(key => {
              let completeList = [];
              let partialList = [];
              let improveList = [];

              res.students_details.forEach(student => {
                const val = student[`check_${key}`];
                if (val && val.trim() !== '') {
                  countChecklistStudentsSet.add(student.student_id);
                  const studentLink = `<a href="javascript:void(0)" onclick="viewStudentDetails('${student.student_id}')" class="badge bg-light text-dark border me-1 mb-1 text-decoration-none fw-normal" style="font-size: 0.7rem;"><i class="bi bi-person"></i> ${student.student_name}</a>`;
                  if (val === 'ครบถ้วน') completeList.push(studentLink);
                  else if (val === 'บางส่วน') partialList.push(studentLink);
                  else if (val === 'ต้องปรับปรุง') improveList.push(studentLink);
                }
              });

              const totalTopicChecklists = completeList.length + partialList.length + improveList.length;
              if (totalTopicChecklists > 0) {
                const weakCount = partialList.length + improveList.length;
                if (weakCount > 0) checklistWeak[key] = weakCount;
                checklistHtml += `
                  <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card h-100 border border-light shadow-sm rounded-4">
                      <div class="card-header bg-success-subtle border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-success-emphasis mb-0"><i class="bi bi-check2-circle me-1"></i> ${criteriaLabelMap[key]}</h6>
                        <span class="badge bg-success rounded-pill">${totalTopicChecklists} คน</span>
                      </div>
                      <div class="card-body py-2">
                        <div class="monitoring-text-area" style="max-height: 250px;">
                          <div class="mb-2">
                            <strong class="text-success small d-block mb-1">✅ ปฏิบัติครบถ้วน (${completeList.length} คน):</strong>
                            <div>${completeList.length > 0 ? completeList.join('') : '<span class="text-muted small font-light">- ไม่มี -</span>'}</div>
                          </div>
                          <div class="mb-2">
                            <strong class="text-warning-emphasis small d-block mb-1">⚠️ ปฏิบัติบางส่วน (${partialList.length} คน):</strong>
                            <div>${partialList.length > 0 ? partialList.join('') : '<span class="text-muted small font-light">- ไม่มี -</span>'}</div>
                          </div>
                          <div class="mb-1">
                            <strong class="text-danger small d-block mb-1">❌ ต้องปรับปรุง (${improveList.length} คน):</strong>
                            <div>${improveList.length > 0 ? improveList.join('') : '<span class="text-muted small font-light">- ไม่มี -</span>'}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                `;
              }
            });

            // 3. หมวดบันทึกสะท้อนคิด (จัดกลุ่มตาม 4 ด้านของคำถาม)
            const reflectionFields = [
              { key: 'content_structure', label: '1. ด้านโครงสร้างและเนื้อหา', color: 'info' },
              { key: 'language_mechanics', label: '2. ด้านภาษาและอักขรวิธี', color: 'primary' },
              { key: 'feedback_applied', label: '3. การปรับแก้ตามคำติชม', color: 'success' },
              { key: 'future_goals', label: '4. การประยุกต์ใช้และเป้าหมายในอนาคต', color: 'warning' }
            ];

            reflectionFields.forEach(f => {
              let topicStudentsHtml = '';
              let topicStudentCount = 0;

              res.students_details.forEach(student => {
                const val = student[f.key] ? student[f.key].trim() : '';
                if (val !== '') {
                  topicStudentCount++;
                  countReflectionStudentsSet.add(student.student_id);
                  allReflectionTexts.push(val);
                  if (reflectionTextsByField[f.key]) reflectionTextsByField[f.key].push(val);

                  topicStudentsHtml += `
                    <div class="border-bottom pb-2 mb-2">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <a href="javascript:void(0)" onclick="viewStudentDetails('${student.student_id}')" class="fw-bold text-${f.color}-emphasis small text-decoration-none"><i class="bi bi-person-circle"></i> ${student.student_id} - ${student.student_name}</a>
                      </div>
                      <div class="text-secondary ps-2 small font-italic">"${val}"</div>
                    </div>
                  `;
                }
              });

              if (topicStudentCount > 0) {
                reflectionHtml += `
                  <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card h-100 border border-light shadow-sm rounded-4">
                      <div class="card-header bg-${f.color}-subtle border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-${f.color}-emphasis mb-0"><i class="bi bi-lightbulb-fill me-1"></i> ${f.label}</h6>
                        <span class="badge bg-${f.color} rounded-pill">${topicStudentCount} คน</span>
                      </div>
                      <div class="card-body py-2">
                        <div class="monitoring-text-area" style="max-height: 250px;">
                          ${topicStudentsHtml}
                        </div>
                      </div>
                    </div>
                  </div>
                `;
              }
            });

            // 4b. ผลรายบุคคล — รวมข้อมูลทั้งหมดของนักเรียนแต่ละคนไว้ในแอคคอร์เดียนเดียว
            const chkBadge = (val) => {
              if (val === 'ครบถ้วน') return '<span class="badge bg-success rounded-pill">ครบถ้วน</span>';
              if (val === 'บางส่วน') return '<span class="badge bg-warning text-dark rounded-pill">บางส่วน</span>';
              if (val === 'ต้องปรับปรุง') return '<span class="badge bg-danger rounded-pill">ต้องปรับปรุง</span>';
              return '<span class="badge bg-light text-muted border rounded-pill">-</span>';
            };
            const esc = (s) => (s == null ? '' : String(s).replace(/</g, '&lt;').replace(/>/g, '&gt;'));

            res.students_details.forEach((student, sIdx) => {
              // ตรวจว่ามีข้อมูลอย่างน้อยหนึ่งประเภทหรือไม่
              let hasObstacle = false, hasChecklist = false, hasReflection = false;
              Object.keys(criteriaLabelMap).forEach(key => {
                if ((student[`prob_${key}`] && student[`prob_${key}`].trim() !== '') ||
                    (student[`sol_${key}`] && student[`sol_${key}`].trim() !== '')) hasObstacle = true;
                if (student[`check_${key}`] && student[`check_${key}`].trim() !== '') hasChecklist = true;
              });
              ['content_structure', 'language_mechanics', 'feedback_applied', 'future_goals'].forEach(f => {
                if (student[f] && student[f].trim() !== '') hasReflection = true;
              });
              if (!hasObstacle && !hasChecklist && !hasReflection) return;
              countIndividualStudentsSet.add(student.student_id);

              // ส่วนอุปสรรค
              let obRows = '';
              Object.keys(criteriaLabelMap).forEach(key => {
                const p = student[`prob_${key}`] ? student[`prob_${key}`].trim() : '';
                const s = student[`sol_${key}`] ? student[`sol_${key}`].trim() : '';
                if (p !== '' || s !== '') {
                  obRows += `<tr><td class="fw-bold text-dark small">${criteriaLabelMap[key]}</td><td class="small text-danger">${esc(p) || '-'}</td><td class="small text-success">${esc(s) || '-'}</td></tr>`;
                }
              });
              const obSection = obRows
                ? `<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead class="table-danger"><tr><th style="width:25%">เกณฑ์</th><th style="width:38%">อุปสรรค</th><th style="width:37%">แผนแก้ปัญหา</th></tr></thead><tbody>${obRows}</tbody></table></div>`
                : '<p class="text-muted small mb-0">- ยังไม่มีการบันทึกอุปสรรคการเขียน -</p>';

              // ส่วนตรวจสอบตนเอง
              let chkItems = '';
              Object.keys(criteriaLabelMap).forEach(key => {
                const val = student[`check_${key}`];
                if (val && val.trim() !== '') {
                  chkItems += `<li class="list-group-item d-flex justify-content-between align-items-center py-1 px-2"><span class="small">${criteriaLabelMap[key]}</span>${chkBadge(val)}</li>`;
                }
              });
              const chkNotes = student.checklist_notes && student.checklist_notes.trim() !== '' ? `<div class="mt-2 p-2 bg-light rounded small text-muted"><strong>บันทึกเพิ่มเติม:</strong> ${esc(student.checklist_notes)}</div>` : '';
              const chkSection = chkItems
                ? `<ul class="list-group list-group-flush border rounded">${chkItems}</ul>${chkNotes}`
                : '<p class="text-muted small mb-0">- ยังไม่มีการตรวจสอบตนเอง -</p>';

              // ส่วนสะท้อนคิด
              const refMap = [
                ['content_structure', 'ด้านเนื้อหาสาระและองค์ประกอบ'],
                ['language_mechanics', 'ด้านการใช้สำนวนภาษาและอักขรวิธี'],
                ['feedback_applied', 'การนำข้อเสนอแนะไปปรับปรุงงาน'],
                ['future_goals', 'การประยุกต์ใช้และเป้าหมายในอนาคต']
              ];
              let refItems = '';
              refMap.forEach(([f, label]) => {
                const v = student[f] ? student[f].trim() : '';
                if (v !== '') {
                  refItems += `<div class="col-md-6 col-12"><div class="p-2 bg-light rounded-3 h-100"><strong class="text-secondary small d-block">${label}</strong><span class="small text-dark">${esc(v)}</span></div></div>`;
                }
              });
              const refSection = refItems
                ? `<div class="row g-2">${refItems}</div>`
                : '<p class="text-muted small mb-0">- ยังไม่มีบทสะท้อนคิด -</p>';

              // ป้ายสถานะย่อ
              const statusBadges = `
                <span class="badge ${hasObstacle ? 'bg-danger' : 'bg-light text-muted border'} rounded-pill">อุปสรรค</span>
                <span class="badge ${hasChecklist ? 'bg-success' : 'bg-light text-muted border'} rounded-pill">ตรวจสอบตนเอง</span>
                <span class="badge ${hasReflection ? 'bg-info' : 'bg-light text-muted border'} rounded-pill">สะท้อนคิด</span>`;

              // แถวสรุป (คลิกเพื่อขยาย) + แถวรายละเอียดที่ซ่อนไว้
              individualHtml += `
                <tr class="indiv-row" data-bs-toggle="collapse" data-bs-target="#indivDetail${sIdx}" role="button" aria-expanded="false" style="cursor: pointer;">
                  <td class="fw-bold text-primary small">${student.student_id}</td>
                  <td class="small text-dark">${student.student_name}</td>
                  <td class="text-nowrap">${statusBadges}</td>
                  <td class="text-end"><i class="bi bi-chevron-down indiv-caret text-secondary"></i></td>
                </tr>
                <tr class="indiv-detail-row">
                  <td colspan="4" class="p-0 border-0">
                    <div id="indivDetail${sIdx}" class="collapse" data-bs-parent="#gridIndividual">
                      <div class="p-3 border-start border-4 border-primary" style="background-color: #f8fafc;">
                        <h6 class="fw-bold text-danger-emphasis"><i class="bi bi-exclamation-octagon"></i> 1. อุปสรรคและแผนการแก้ปัญหา</h6>
                        ${obSection}
                        <h6 class="fw-bold text-success-emphasis mt-3"><i class="bi bi-patch-check"></i> 2. การตรวจสอบตนเอง</h6>
                        ${chkSection}
                        <h6 class="fw-bold text-info-emphasis mt-3"><i class="bi bi-lightbulb"></i> 3. การสะท้อนการเรียนรู้</h6>
                        ${refSection}
                        <div class="text-end mt-3">
                          <a href="javascript:void(0)" onclick="viewStudentDetails('${student.student_id}')" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-folder2-open"></i> เปิดแฟ้มเต็ม (รวมประเมินเพื่อน)</a>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>`;
            });
          }

          // 4. วิเคราะห์คำสำคัญ (Keyword Analytics) — เรียก API ก่อน ถ้าไม่สำเร็จค่อย fallback ฝั่ง client
          let obstacleKeywords = [];
          let reflectionKeywords = [];
          let reflectionByField = null;
          try {
            const kwRes = await (await fetch(`api.php?action=get_reflection_keywords&_t=${new Date().getTime()}`)).json();
            if (kwRes.success) {
              obstacleKeywords = kwRes.obstacles || [];
              reflectionKeywords = kwRes.reflections || [];
              reflectionByField = kwRes.reflections_by_field || null;
            }
          } catch (e) {
            console.warn('ใช้การวิเคราะห์คำสำคัญฝั่ง client แทน:', e);
          }
          if (obstacleKeywords.length === 0) obstacleKeywords = analyzeKeywords(allObstaclesTexts);
          if (reflectionKeywords.length === 0) reflectionKeywords = analyzeKeywords(allReflectionTexts);
          // fallback: สร้างคำสำคัญแยกตามคำถามฝั่ง client หาก API ไม่ส่งมา
          if (!reflectionByField) {
            reflectionByField = {};
            Object.keys(reflectionTextsByField).forEach(k => {
              reflectionByField[k] = analyzeKeywords(reflectionTextsByField[k]);
            });
          }

          // แสดง Word Cloud อุปสรรคการเขียน (คำที่พบบ่อยจะใหญ่ขึ้น)
          const obstaclesTrendsPanel = document.getElementById('obstaclesTrendsPanel');
          const obstaclesTrendsContainer = document.getElementById('obstaclesTrendsContainer');
          if (obstacleKeywords.length > 0) {
            obstaclesTrendsPanel.style.display = 'block';
            renderWordCloud(obstaclesTrendsContainer, obstacleKeywords, 'obstacle');
          } else {
            obstaclesTrendsPanel.style.display = 'none';
          }

          // แสดง Word Cloud บทสะท้อนคิด — แยกเป็นบล็อกตามคำถามแต่ละข้อ
          const reflectionTrendsPanel = document.getElementById('reflectionTrendsPanel');
          const reflectionTrendsContainer = document.getElementById('reflectionTrendsContainer');
          const hasReflectionKw = renderReflectionCloudByField(reflectionTrendsContainer, reflectionByField);
          reflectionTrendsPanel.style.display = hasReflectionKw ? 'block' : 'none';

          // 5. ข้อเสนอแนะขอบเขตการเรียนรู้เพิ่มเติม (ขั้น Enabling) — บทวิเคราะห์ + แจกแจงตามลำดับความสำคัญ
          // 5.1 จากอุปสรรค → เกณฑ์ที่นักเรียนพบปัญหามากที่สุด
          const obstacleSuggestItems = Object.keys(obstacleCounts)
            .sort((a, b) => obstacleCounts[b] - obstacleCounts[a])
            .slice(0, 6)
            .map(key => ({
              area: criteriaToLearningArea[key] || criteriaLabelMap[key],
              label: criteriaLabelMap[key],
              count: obstacleCounts[key],
              unit: 'คน',
              reason: `มีนักเรียน ${obstacleCounts[key]} คนระบุว่าพบอุปสรรคในเกณฑ์ “${criteriaLabelMap[key]}”`
            }));
          renderEnablingSuggestions(
            document.getElementById('obstaclesSuggestPanel'),
            document.getElementById('obstaclesSuggestContainer'),
            buildEnablingNarrative('obstacle', obstacleSuggestItems),
            obstacleSuggestItems
          );

          // 5.2 จากการตรวจสอบตนเอง → เกณฑ์ที่ยังอ่อนที่สุด
          const checklistSuggestItems = Object.keys(checklistWeak)
            .sort((a, b) => checklistWeak[b] - checklistWeak[a])
            .slice(0, 6)
            .map(key => ({
              area: criteriaToLearningArea[key] || criteriaLabelMap[key],
              label: criteriaLabelMap[key],
              count: checklistWeak[key],
              unit: 'คน',
              reason: `มีนักเรียน ${checklistWeak[key]} คนประเมินตนเองว่ายัง “ทำได้บางส่วน/ต้องปรับปรุง” ในเกณฑ์ “${criteriaLabelMap[key]}”`
            }));
          renderEnablingSuggestions(
            document.getElementById('checklistSuggestPanel'),
            document.getElementById('checklistSuggestContainer'),
            buildEnablingNarrative('checklist', checklistSuggestItems),
            checklistSuggestItems
          );

          // 5.3 จากบทสะท้อนคิด → แปลงคำสำคัญเป็นขอบเขตการเรียนรู้ (ตัดซ้ำ)
          const seenRefAreas = new Set();
          const reflectionSuggestItems = [];
          reflectionKeywords.forEach(item => {
            const area = keywordToLearningArea[item.keyword];
            if (area && !seenRefAreas.has(area)) {
              seenRefAreas.add(area);
              reflectionSuggestItems.push({
                area,
                label: item.keyword,
                count: item.count,
                unit: 'ครั้ง',
                reason: `นักเรียนกล่าวถึง “${item.keyword}” ${item.count} ครั้งในบทสะท้อนคิด`
              });
            }
          });
          const reflectionSuggestTop = reflectionSuggestItems.slice(0, 6);
          renderEnablingSuggestions(
            document.getElementById('reflectionSuggestPanel'),
            document.getElementById('reflectionSuggestContainer'),
            buildEnablingNarrative('reflection', reflectionSuggestTop),
            reflectionSuggestTop
          );

          // แสดงข้อความว่าง หากยังไม่มีข้อเสนอแนะจากทั้ง 3 แหล่งเลย
          const enablingEmpty = document.getElementById('enablingEmpty');
          if (enablingEmpty) {
            const hasAnySuggest = obstacleSuggestItems.length > 0 || checklistSuggestItems.length > 0 || reflectionSuggestTop.length > 0;
            enablingEmpty.style.display = hasAnySuggest ? 'none' : 'block';
          }

          // อัปเดตจำนวนนักเรียนในแต่ละกิจกรรมมอนิเตอร์ย่อย (ปุ่มแท็บ)
          document.getElementById('countObstacles').textContent = countObstaclesStudentsSet.size;
          document.getElementById('countChecklist').textContent = countChecklistStudentsSet.size;
          document.getElementById('countReflection').textContent = countReflectionStudentsSet.size;
          const countIndivEl = document.getElementById('countIndividual');
          if (countIndivEl) countIndivEl.textContent = countIndividualStudentsSet.size;

          // แสดงข้อความตกหล่นหรือสถานะว่าง
          gridObstacles.innerHTML = obstaclesHtml || '<div class="text-center py-5 text-muted">ยังไม่มีข้อมูลอุปสรรคการเขียนในกิจกรรมนี้</div>';
          gridChecklist.innerHTML = checklistHtml || '<div class="text-center py-5 text-muted">ยังไม่มีข้อมูลการตรวจสอบตนเองในกิจกรรมนี้</div>';
          gridReflection.innerHTML = reflectionHtml || '<div class="text-center py-5 text-muted">ยังไม่มีข้อมูลการสะท้อนคิดการเรียนรู้ในกิจกรรมนี้</div>';
          const gridIndividual = document.getElementById('gridIndividual');
          if (gridIndividual) {
            gridIndividual.innerHTML = individualHtml
              ? `<table class="table table-hover align-middle mb-0">
                   <thead class="table-light">
                     <tr>
                       <th style="width: 15%;">รหัส</th>
                       <th>ชื่อ-สกุล</th>
                       <th style="width: 38%;">สถานะข้อมูล</th>
                       <th class="text-end" style="width: 10%;">รายละเอียด</th>
                     </tr>
                   </thead>
                   <tbody>${individualHtml}</tbody>
                 </table>`
              : '<div class="text-center py-5 text-muted">ยังไม่มีข้อมูลผลรายบุคคล</div>';
          }
          if (!res.success) {
            console.error("API error:", res.error || "Unknown error");
            alert("เกิดข้อผิดพลาดในการโหลดข้อมูลห้องเรียน: " + (res.error || "Unknown API error"));
          }
        } else {
          alert("ไม่สามารถดึงข้อมูลได้สำเร็จ (res.success !== true)");
        }
      } catch (err) {
        console.error("JS Catch error:", err);
        alert("เกิดข้อผิดพลาดทางเทคนิค: " + err.message);
      }
    };

    window.viewStudentDetails = function(studentId) {
      const triggerEl = document.getElementById('inspector-tab');
      if (triggerEl) {
        triggerEl.click();
      }
      const select = document.getElementById('inspectorStudentSelect');
      if (select) {
        select.value = studentId;
        select.dispatchEvent(new Event('change'));
      }
    };

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
            '1_3': '1.3 การขยายความและเหตุผล',
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


</script>

<?php
require_once 'footer.php';
?>
