/** courses.js — Ders CRUD */
let courseList = [];

async function loadCourses() {
  const res = await Api.get('/courses');
  courseList = res.data ?? [];
  renderCourseTable(courseList);
}

function renderCourseTable(courses) {
  const tbody = document.querySelector('#course-table tbody');
  const typeLabel = { theory:'Teorik', lab:'Lab', seminar:'Seminer' };
  tbody.innerHTML = courses.length === 0
    ? '<tr><td colspan="7" style="text-align:center;color:#888;padding:20px">Henüz ders eklenmedi.</td></tr>'
    : courses.map(c => {
        const teacher = teacherList.find(t => t.id === c.teacher_id);
        const group   = groupList.find(g => g.id === c.student_group_id);
        return `<tr>
          <td><strong>${c.code}</strong></td>
          <td>${c.name}</td>
          <td>${teacher?.name ?? '-'}</td>
          <td>${group?.name ?? '-'}</td>
          <td>${c.weekly_hours}</td>
          <td>${typeLabel[c.course_type] ?? c.course_type}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="editCourse(${c.id})">Düzenle</button>
            <button class="btn btn-sm btn-danger" onclick="deleteCourse(${c.id})">Sil</button>
          </td>
        </tr>`;
      }).join('');
}

document.getElementById('btn-add-course')?.addEventListener('click', () => openCourseForm());

function openCourseForm(id = null) {
  const course = id ? courseList.find(c => c.id === id) : null;
  document.getElementById('form-modal-title').textContent = course ? 'Ders Düzenle' : 'Ders Ekle';
  const teacherOptions = teacherList.map(t =>
    `<option value="${t.id}" ${course?.teacher_id === t.id ? 'selected' : ''}>${t.name}</option>`).join('');
  const groupOptions = groupList.map(g =>
    `<option value="${g.id}" ${course?.student_group_id === g.id ? 'selected' : ''}>${g.name}</option>`).join('');
  document.getElementById('entity-form').innerHTML = `
    <div class="form-group"><label>Ders Kodu</label><input name="code" value="${course?.code ?? ''}" required/></div>
    <div class="form-group"><label>Ders Adı</label><input name="name" value="${course?.name ?? ''}" required/></div>
    <div class="form-group"><label>Öğretmen</label><select name="teacher_id">${teacherOptions}</select></div>
    <div class="form-group"><label>Öğrenci Grubu</label><select name="student_group_id">${groupOptions}</select></div>
    <div class="form-group"><label>Haftalık Saat</label><input name="weekly_hours" type="number" min="1" max="9" value="${course?.weekly_hours ?? 2}" required/></div>
    <div class="form-group"><label>Gerekli Kapasite</label><input name="required_capacity" type="number" min="1" value="${course?.required_capacity ?? 25}" required/></div>
    <div class="form-group"><label>Ders Türü</label>
      <select name="course_type">
        <option value="theory"  ${course?.course_type==='theory' ? 'selected':''}>Teorik</option>
        <option value="lab"     ${course?.course_type==='lab'    ? 'selected':''}>Lab</option>
        <option value="seminar" ${course?.course_type==='seminar'? 'selected':''}>Seminer</option>
      </select>
    </div>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" onclick="closeFormModal()">İptal</button>
      <button type="submit" class="btn btn-primary">${course ? 'Güncelle' : 'Ekle'}</button>
    </div>`;
  document.getElementById('entity-form').onsubmit = (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const call = course ? Api.put(`/courses/${id}`, data) : Api.post('/courses', data);
    call.then(() => { closeFormModal(); loadCourses(); showStatus('Ders kaydedildi.', 'success'); })
        .catch(err => alert('Hata: ' + err.message));
  };
  document.getElementById('form-modal').classList.remove('hidden');
}

function editCourse(id)   { openCourseForm(id); }
function deleteCourse(id) {
  if (!confirm('Dersi silmek istediğinizden emin misiniz?')) return;
  Api.delete(`/courses/${id}`)
    .then(() => { loadCourses(); showStatus('Ders silindi.', 'success'); })
    .catch(err => alert('Hata: ' + err.message));
}
