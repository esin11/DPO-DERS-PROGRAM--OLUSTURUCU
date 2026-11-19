/** rooms.js — Derslik CRUD */
let roomList = [];

async function loadRooms() {
  const res = await Api.get('/rooms');
  roomList = res.data ?? [];
  renderRoomTable(roomList);
}

function renderRoomTable(rooms) {
  const tbody = document.querySelector('#room-table tbody');
  const typeLabel = { classroom:'Derslik', lab:'Lab', auditorium:'Amfi' };
  tbody.innerHTML = rooms.length === 0
    ? '<tr><td colspan="6" style="text-align:center;color:#888;padding:20px">Henüz derslik eklenmedi.</td></tr>'
    : rooms.map(r => `
        <tr>
          <td>${r.id}</td><td>${r.name}</td><td>${r.capacity}</td>
          <td>${r.building}</td><td>${typeLabel[r.room_type] ?? r.room_type}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="editRoom(${r.id})">Düzenle</button>
            <button class="btn btn-sm btn-danger"  onclick="deleteRoom(${r.id})">Sil</button>
          </td>
        </tr>`).join('');
}

document.getElementById('btn-add-room')?.addEventListener('click', () => openRoomForm());

function openRoomForm(id = null) {
  const room = id ? roomList.find(r => r.id === id) : null;
  document.getElementById('form-modal-title').textContent = room ? 'Derslik Düzenle' : 'Derslik Ekle';
  document.getElementById('entity-form').innerHTML = `
    <div class="form-group"><label>Ad</label><input name="name" value="${room?.name ?? ''}" required/></div>
    <div class="form-group"><label>Kapasite</label><input name="capacity" type="number" min="1" value="${room?.capacity ?? 30}" required/></div>
    <div class="form-group"><label>Bina</label><input name="building" value="${room?.building ?? ''}"/></div>
    <div class="form-group"><label>Tür</label>
      <select name="room_type">
        <option value="classroom" ${room?.room_type==='classroom' ?'selected':''}>Derslik</option>
        <option value="lab"       ${room?.room_type==='lab'       ?'selected':''}>Lab</option>
        <option value="auditorium"${room?.room_type==='auditorium'?'selected':''}>Amfi</option>
      </select>
    </div>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" onclick="closeFormModal()">İptal</button>
      <button type="submit" class="btn btn-primary">${room ? 'Güncelle' : 'Ekle'}</button>
    </div>`;
  document.getElementById('entity-form').onsubmit = (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const call = room ? Api.put(`/rooms/${id}`, data) : Api.post('/rooms', data);
    call.then(() => { closeFormModal(); loadRooms(); showStatus('Derslik kaydedildi.', 'success'); })
        .catch(err => alert('Hata: ' + err.message));
  };
  document.getElementById('form-modal').classList.remove('hidden');
}

function editRoom(id)   { openRoomForm(id); }
function deleteRoom(id) {
  if (!confirm('Dersliği silmek istediğinizden emin misiniz?')) return;
  Api.delete(`/rooms/${id}`)
    .then(() => { loadRooms(); showStatus('Derslik silindi.', 'success'); })
    .catch(err => alert('Hata: ' + err.message));
}
