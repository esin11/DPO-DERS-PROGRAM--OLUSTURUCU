# **1. Çalışma Oturumu: Katmanlı Mimari ve Klasör Yapısı Doğrulaması** 

- **İstemin Amacı:** Hafta 3 Öğrenci Kılavuzu'na uygun 5 katmanlı mimarinin (Router, Controller, Service, Repository, Model, View) projemize doğru uyarlanıp uyarlanmadığını denetletmek. 

- **Yapay Zekâya Verilen İstem (Prompt):** 

Aşağıda DPO PHP projemin katmanlı mimari yapısı ve klasör hiyerarşisi var. Mimari kurallarım: Controller sadece Service'i çağırır, Service $_POST okumaz ve HTML üretmez, Repository iş kuralı içermez. Klasör yapımda veya katman tablomda PSR-4 ve katman kurallarına aykırı bir hata var mı? Yapıyı inceleyip düzenle. 

- **Yapay Zekânın Yanıtı ve Önerisi:** Yapay zekâ, ScheduleController sınıfının doğrudan ScheduleRepositoryInterface çağrısı yapmasının katman ihlali olduğunu belirtti. Controller ile Repository arasına ScheduleSolverService katmanının konulması gerektiğini önerdi. 

- **Ekip Tarafından Uygulanan Karar:** Öneri kabul edildi. Sınıf hiyerarşisi düzenlenerek ScheduleController $\rightarrow$ ScheduleSolverService $\rightarrow$ ScheduleRepositoryInterface bağımlılık akışı sağlandı. 

# **2. Çalışma Oturumu: Mermaid UML Sınıf Diyagramı Üretimi** 

- **İstemin Amacı:** SRS dokümanındaki 25 iş gereksinimini (FR1 - FR25) karşılayacak en az 12 sınıflık UML sınıf diyagramını Mermaid koduna dönüştürmek. 

- **Yapay Zekâya Verilen İstem (Prompt):** 

SRS belgemdeki gereksinimlere (Hoca, Ders, Derslik, Öğrenci Grubu, Müsaitlik, Çizelgeleme motoru, CSV/JSON içe aktarma ve İstatistik raporlama) uyan Mermaid classDiagram kodunu yaz. Controller'dan Repository'ye doğrudan ok çekilmesin. En az 12 sınıf ve arayüz içersin. 

- **Yapay Zekânın Yanıtı ve Önerisi:** Yapay zekâ 16 sınıftan oluşan, Model, Service, Strategy, Repository ve Controller katmanlarını eksiksiz ilişkilendiren bir Mermaid kodu üretti. 

- **Ekip Tarafından Uygulanan Karar:** Mermaid kodu mermaid.live üzerinde görselleştirildi. Sınav modu ve kısıt stratejilerinin diyagrama doğru yansıdığı kontrol edilerek docs/tasarim.md Bölüm 2'ye eklendi. 

# **3. Çalışma Oturumu: Çekirdek Problemin Sıralama (Sequence) Diyagramı** 

- **İstemin Amacı:** Projenin çekirdek mühendislik problemi olan "Otomatik Çizelge Oluşturma (Backtracking)" akışını ve hata/darboğaz dallarını gösteren Mermaid sıralama diyagramını oluşturmak. 

- **Yapay Zekâya Verilen İstem (Prompt):** 

DPO projemde 'Program Oluştur' butonuna basıldığında çalışan Backtracking akışını Mermaid sequenceDiagram olarak yaz. Katılımcılar soldan sağa: Tarayıcı, ScheduleController, ScheduleSolverService, ConflictValidatorService, ScheduleRepositoryInterface ve View olsun. Çözüm bulunduğunda veritabanına yazıp takvimi çizen, kısıt ihlali olduğunda ise darboğaz analizi raporlayan bir alt (alt...else) bloğu içersin. 

- **Yapay Zekânın Yanıtı ve Önerisi:** Katılımcı yaşam çizgilerini ve alt-else koşullu dallanmalarını içeren, HTTP yanıt kodlarını ve metot çağrılarını gösteren Mermaid kodu üretildi. 

- **Ekip Tarafından Uygulanan Karar:** Katılımcı isimleri projemizdeki somut sınıf isimleriyle eşleştirilerek belgenin 3. bölümüne aktarıldı. 

# **4. Çalışma Oturumu: Veritabanı ER Diyagramı ve 3NF Normalizasyon Analizi** 

- **İstemin Amacı:** Veritabanı varlık-ilişki şemasının 3NF (Üçüncü Normal Form) kurallarına uygunluğunu sorgulatmak ve yazılı gerekçesini oluşturmak. 

- **Yapay Zekâya Verilen İstem (Prompt):** 

DPO projesinin users, teachers, rooms, student_groups, courses, availabilities ve schedule_entries tablolarını 1NF, 2NF ve 3NF açısından incele. Güncelleme anomalilerini önlemek için 3NF gerekçesini 4 cümleyle yaz ve Mermaid erDiagram kodunu üret. 

- **Yapay Zekânın Yanıtı ve Önerisi:** Yapay zekâ, schedule_entries tablosunda hoca adı veya derslik adının saklanmasının 3NF ihlali yaratacağını belirterek sadece yabancı anahtarların (teacher_id, room_id) tutulmasını önerdi. 

- **Ekip Tarafından Uygulanan Karar:** Veritabanı şeması bu doğrultuda güncellendi; raporlama ekranlarında verilerin SQL JOIN sorguları ile dinamikleştirilmesine karar verildi (FR24). 

# **5. Çalışma Oturumu: Tasarım Desenleri Seçimi ve Gerekçelendirme** 

- **İstemin Amacı:** Repository deseni dışında projenin kısıt motoruna en uygun ikinci tasarım desenini belirlemek. 

- **Yapay Zekâya Verilen İstem (Prompt):** 

DPO projemde çakışma kontrolleri (Hoca zaman çakışması, Derslik kapasite çakışması, Müsaitlik kontrolü) için Strategy deseni kullanmayı düşünüyorum. Bu seçim mantıklı mı? Alternatifi nedir? Kılavuzdaki 4 soruluk kalıba göre (Problem, Desen, Alternatif, Neden) gerekçesini yaz. 

- **Yapay Zekânın Yanıtı ve Önerisi:** Strategy deseninin kısıt kurallarını bağımsız sınıflara bölmek için en ideal yöntem olduğu onaylandı. Alternatifi olan karmaşık ifelse zincirine göre Open/Closed ilkesine tam uyum sağladığı belirtildi. 

- **Ekip Tarafından Uygulanan Karar:** Repository ve Strategy desenleri Kılavuzdaki 4'lü formatta belgenin 5. bölümüne eklendi. 

