/**
 * schedule.js — Haftalık çizelge render + generate + conflict
 */
const DAYS  = ['Pazartesi','Salı','Çarşamba','Perşembe','Cuma'];
const SLOTS = { 1:'08:00',2:'09:00',3:'10:00',4:'11:00',5:'12:00',6:'13:00',7:'14:00',8:'15:00',9:'16:00' };

let currentVersion = 1;
let currentGrid    = {};

/* ── Tablonun boş iskeletini oluştur ── */
function buildTableSkeleton() {
  const tbody = document.getElementById('schedule-body');
  tbody.innerHTML = '';
  Object.entries(SLOTS).forEach(([slot, time]) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td class="time-col">${time}</td>` +
      DAYS.map(d => `<td id="cell-${d}-${slot}" class="schedule-cell"></td>`).join('');
    tbody.appendChild(tr);
  });
}

/* ── Grid verisini tabloya yaz ── */
function renderGrid(grid) {
  currentGrid = grid;
  // Önce temizle
  document.querySelectorAll('.schedule-cell').forEach(c => c.innerHTML = '');

  DAYS.forEach(day => {
    Object.keys(SLOTS).forEach(slot => {
      const entries = grid[day]?.[slot] ?? [];
      const cell = document.getElementById(`cell-${day}-${slot}`);
      if (!cell) return;
      entries.forEach(e => {
        const div = document.createElement('div');
        div.className = `cell-entry ${e.type}`;
        div.dataset.entryId = e.entry_id;
        div.innerHTML = `
          <span class="entry-code">${e.course_code}</span>
          <span class="entry-edit" title="Düzenle">✏️</span>
          <div class="entry-teacher">${e.teacher}</div>
          <div class="entry-room">🚪 ${e.room} · 👥 ${e.group}</div>
        `;
        div.querySelector('.entry-edit').addEventListener('click', (ev) => {
          ev.stopPropagation();
          openEditModal(e);
        });
        cell.appendChild(div);
      });
    });
  });
}

/* ── Program Yükle ── */
async function loadSchedule(version = null) {
  try {
    const path = version ? `/schedule?version=${version}` : '/schedule';
    const res  = await Api.get(path);
    if (res.success) {
      currentVersion = res.data.version;
      renderGrid(res.data.grid);
    }
  } catch (err) {
    showStatus('Program yüklenirken hata: ' + err.message, 'error');
  }
}

/* ── Program Oluştur ── */
document.getElementById('btn-generate')?.addEventListener('click', async () => {
  const btn = document.getElementById('btn-generate');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Oluşturuluyor…';
  try {
    const res = await Api.post('/schedule/generate');
    if (res.success) {
      showStatus(`✅ ${res.message} (${res.data.elapsed_s}s, ${res.data.iterations} iterasyon)`, 'success');
      await loadSchedule();
    } else {
      const reasons = res.reasons?.map(r => `• ${r}`).join('\n') ?? '';
      showStatus(`❌ ${res.message}\n${reasons}`, 'error');
    }
  } catch (err) {
    const reasons = err.reasons?.map(r => `• ${r}`).join('\n') ?? '';
    showStatus(`❌ ${err.message}\n${reasons}`, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '⚡ Program Oluştur';
  }
});

/* ── Çakışma Kontrolü ── */
document.getElementById('btn-conflicts')?.addEventListener('click', async () => {
  try {
    const res  = await Api.get(`/schedule/conflicts?version=${currentVersion}`);
    const list = document.getElementById('conflict-list');
    list.innerHTML = '';

    if (!res.data.conflicts?.length) {
      list.innerHTML = '<div class="no-conflict">✅ Çakışma tespit edilmedi!</div>';
    } else {
      res.data.conflicts.forEach(c => {
        const item = document.createElement('div');
        item.className = 'conflict-item';
        item.innerHTML = `<span class="conflict-badge badge-${c.type}">${c.type.toUpperCase()}</span>
                          <span>${c.message}</span>`;
        list.appendChild(item);
      });
    }

    document.getElementById('conflict-modal').classList.remove('hidden');
  } catch (err) {
    showStatus('Çakışma kontrolü hatası: ' + err.message, 'error');
  }
});

document.getElementById('conflict-modal-close')?.addEventListener('click', () => {
  document.getElementById('conflict-modal').classList.add('hidden');
});

/* ── Yazdır ── */
document.getElementById('btn-print')?.addEventListener('click', () => window.print());

/* ── Görünüm filtresi ── */
document.getElementById('view-filter')?.addEventListener('change', async (e) => {
  const by  = e.target.value;
  const sel = document.getElementById('view-filter-id');

  if (by === 'all') {
    sel.style.display = 'none';
    await loadSchedule(currentVersion);
    return;
  }

  // Seçenekleri doldur
  sel.style.display = '';
  sel.innerHTML = '';
  let items = [];
  if (by === 'teacher') items = await Api.get('/teachers').then(r => r.data ?? []);
  if (by === 'group')   items = await Api.get('/groups').then(r => r.data ?? []);
  if (by === 'room')    items = await Api.get('/rooms').then(r => r.data ?? []);
  items.forEach(item => {
    const opt = document.createElement('option');
    opt.value = item.id;
    opt.text  = item.name;
    sel.appendChild(opt);
  });

  sel.dispatchEvent(new Event('change'));
});

document.getElementById('view-filter-id')?.addEventListener('change', async (e) => {
  const by  = document.getElementById('view-filter').value;
  const id  = e.target.value;
  if (!id) return;
  try {
    const res = await Api.get(`/schedule/view?by=${by}&id=${id}&version=${currentVersion}`);
    // Boş grid oluştur, sonra filtered entries'i doldur
    const fakeGrid = {};
    DAYS.forEach(d => { fakeGrid[d] = {}; Object.keys(SLOTS).forEach(s => fakeGrid[d][s] = []); });
    (res.data ?? []).forEach(e => { fakeGrid[e.day]?.[e.slot]?.push(e); });
    renderGrid(fakeGrid);
  } catch(err) {
    showStatus(err.message, 'error');
  }
});

/* ── Düzenleme Modal ── */
function openEditModal(entry) {
  // Basit inline düzenleme — gün ve slot seçtir, AJAX ile çakışma kontrol et
  const newDay  = prompt(`Yeni gün (${DAYS.join('/')})`, entry.day);
  if (!newDay || !DAYS.includes(newDay)) return;
  const newSlot = parseInt(prompt('Yeni saat dilimi (1-9)', entry.slot));
  if (!newSlot || newSlot < 1 || newSlot > 9) return;

  // Anlık çakışma kontrolü
  Api.post('/schedule/check', {
    course_id: entry.entry_id,   // normalde course_id lazım; basit demo için entry_id
    room_id:   1,
    day:       newDay,
    slot:      newSlot,
    version:   currentVersion,
  }).then(res => {
    if (res.data?.has_conflict) {
      alert('⚠️ Çakışma var:\n' + res.data.conflicts.map(c => c.message).join('\n'));
    } else {
      Api.put(`/schedule/entry/${entry.entry_id}`, { day: newDay, slot: newSlot })
        .then(() => { showStatus('Kayıt güncellendi.', 'success'); loadSchedule(currentVersion); })
        .catch(err => alert('Hata: ' + err.message));
    }
  });
}

/* ── Status ── */
function showStatus(msg, type = 'info') {
  const bar = document.getElementById('status-bar');
  bar.textContent = msg;
  bar.className   = `status-bar ${type}`;
  bar.classList.remove('hidden');
  setTimeout(() => bar.classList.add('hidden'), 6000);
}

/* İlk yükleme */
buildTableSkeleton();
loadSchedule();
