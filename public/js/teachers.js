/** teachers.js — Hoca CRUD */
let teacherList = [];

async function loadTeachers() {
  const res = await Api.get('/teachers');
  teacherList = res.data ?? [];
  renderTeacherTable(teacherList);
}

function renderTeacherTable(teachers) {
  const tbody = document.querySelector('#teacher-table tbody');
  tbody.innerHTML = teachers.length === 0
    ? '<tr><td colspan="5" style="text-align:center;color:#888;padding:20px">Henüz hoca eklenmedi.</td></tr>'
    : teachers.map(t => `
        <tr>
          <td>${t.id}</td>
          <td>${t.name}</td>
          <td>${t.email}</td>
          <td>${t.department}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="editTeacher(${t.id})">Düzenle</button>
            <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${t.id})">Sil</button>
          </td>
        </tr>`).join('');
}

document.getElementById('btn-add-teacher')?.addEventListener('click', () => openTeacherForm());

function openTeacherForm(id = null) {
  const teacher = id ? teacherList.find(t => t.id === id) : null;
  document.getElementById('form-modal-title').textContent = teacher ? 'Hoca Düzenle' : 'Hoca Ekle';
  document.getElementById('entity-form').innerHTML = `
    <div class="form-group"><label>Ad Soyad</label><input name="name" value="${teacher?.name ?? ''}" required/></div>
    <div class="form-group"><label>E-posta</label><input name="email" type="email" value="${teacher?.email ?? ''}" required/></div>
    <div class="form-group"><label>Bölüm</label><input name="department" value="${teacher?.department ?? ''}" required/></div>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" onclick="closeFormModal()">İptal</button>
      <button type="submit" class="btn btn-primary">${teacher ? 'Güncelle' : 'Ekle'}</button>
    </div>`;
  document.getElementById('entity-form').onsubmit = (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const call = teacher ? Api.put(`/teachers/${id}`, data) : Api.post('/teachers', data);
    call.then(() => { closeFormModal(); loadTeachers(); showStatus('Hoca kaydedildi.', 'success'); })
        .catch(err => alert('Hata: ' + err.message));
  };
  document.getElementById('form-modal').classList.remove('hidden');
}

function editTeacher(id)   { openTeacherForm(id); }
function deleteTeacher(id) {
  if (!confirm('Hocayı silmek istediğinizden emin misiniz?')) return;
  Api.delete(`/teachers/${id}`)
    .then(() => { loadTeachers(); showStatus('Hoca silindi.', 'success'); })
    .catch(err => alert('Hata: ' + err.message));
}
