/** groups.js — Öğrenci Grubu CRUD */
let groupList = [];

async function loadGroups() {
  const res = await Api.get('/groups');
  groupList = res.data ?? [];
  renderGroupTable(groupList);
}

function renderGroupTable(groups) {
  const tbody = document.querySelector('#group-table tbody');
  tbody.innerHTML = groups.length === 0
    ? '<tr><td colspan="6" style="text-align:center;color:#888;padding:20px">Henüz grup eklenmedi.</td></tr>'
    : groups.map(g => `
        <tr>
          <td>${g.id}</td><td>${g.name}</td><td>${g.grade}. Sınıf</td>
          <td>${g.student_count}</td><td>${g.department}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="editGroup(${g.id})">Düzenle</button>
            <button class="btn btn-sm btn-danger"  onclick="deleteGroup(${g.id})">Sil</button>
          </td>
        </tr>`).join('');
}

document.getElementById('btn-add-group')?.addEventListener('click', () => openGroupForm());

function openGroupForm(id = null) {
  const group = id ? groupList.find(g => g.id === id) : null;
  document.getElementById('form-modal-title').textContent = group ? 'Grup Düzenle' : 'Grup Ekle';
  document.getElementById('entity-form').innerHTML = `
    <div class="form-group"><label>Grup Adı</label><input name="name" value="${group?.name ?? ''}" required/></div>
    <div class="form-group"><label>Sınıf (1-4)</label><input name="grade" type="number" min="1" max="4" value="${group?.grade ?? 1}" required/></div>
    <div class="form-group"><label>Öğrenci Sayısı</label><input name="student_count" type="number" min="1" value="${group?.student_count ?? 25}" required/></div>
    <div class="form-group"><label>Bölüm</label><input name="department" value="${group?.department ?? ''}"/></div>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" onclick="closeFormModal()">İptal</button>
      <button type="submit" class="btn btn-primary">${group ? 'Güncelle' : 'Ekle'}</button>
    </div>`;
  document.getElementById('entity-form').onsubmit = (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const call = group ? Api.put(`/groups/${id}`, data) : Api.post('/groups', data);
    call.then(() => { closeFormModal(); loadGroups(); showStatus('Grup kaydedildi.', 'success'); })
        .catch(err => alert('Hata: ' + err.message));
  };
  document.getElementById('form-modal').classList.remove('hidden');
}

function editGroup(id)   { openGroupForm(id); }
function deleteGroup(id) {
  if (!confirm('Grubu silmek istediğinizden emin misiniz?')) return;
  Api.delete(`/groups/${id}`)
    .then(() => { loadGroups(); showStatus('Grup silindi.', 'success'); })
    .catch(err => alert('Hata: ' + err.message));
}
