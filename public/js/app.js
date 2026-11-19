/** app.js — Tab yönetimi + genel başlatma */

// ── Tab sistemi ────────────────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.dataset.tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(`tab-${target}`)?.classList.add('active');

    // Sekme açıldığında ilgili veriyi yükle
    if (target === 'teachers') loadTeachers();
    if (target === 'courses')  loadCourses();
    if (target === 'rooms')    loadRooms();
    if (target === 'groups')   loadGroups();
  });
});

// ── Form Modal kapat ────────────────────────────────────────────────────────
document.getElementById('form-modal-close')?.addEventListener('click', closeFormModal);
document.getElementById('form-modal')?.addEventListener('click', (e) => {
  if (e.target === e.currentTarget) closeFormModal();
});
document.getElementById('conflict-modal')?.addEventListener('click', (e) => {
  if (e.target === e.currentTarget) document.getElementById('conflict-modal').classList.add('hidden');
});

function closeFormModal() {
  document.getElementById('form-modal').classList.add('hidden');
}

// ── Eksik controller'lar için router'a eklenecek gruplar/odalar ──────────────
// (router.php'ye eklenecek route'lar için referans — rooms ve groups için
//  ayrı controller + service yazılabilir; şimdilik ScheduleController ile aynı
//  pattern'ı izleyerek eklenebilir)
