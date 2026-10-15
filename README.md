# DPO-DERS-PROGRAM--OLUSTURUCU

FR1
    • Title: Kullanıcı Kimlik Doğrulama ve Güvenli Giriş Sistemi
    • ID: FR1
    • Description: Sistem, kullanıcıdan e-posta/kullanıcı adı ve şifre bilgilerini alarak kimlik doğrulaması yapmalı, başarılı girişte oturum başlatmalıdır. 
    • Rationale: Sisteme yetkisiz kişilerin erişmesini engellemek ve rol bazlı erişim denetiminin ilk adımını sağlamak için zorunludur. 
    • Source: Proje Standartları Kılavuzu & Berk Dinçer 
    • Fit Criterion: Şifreler password_hash (BCrypt/Argon2id) ile doğrulanmalı; başarılı girişte session_regenerate_id(true) çağrılarak oturum sabitleme engellenmelidir. Hatalı girişte genel bir hata mesajı dönmelidir. 
    • Dependencies: Yok
    • Priority: Yüksek 
    • Supporting Materials: Giriş Ekranı Prototipi 
    • History: 12.10.2026


FR2
    • Title: Rol Bazlı Yetki Denetimi (RBAC)
    • ID: FR2
    • Description: Sistem, kullanıcıları "Yönetici (Admin)" ve "Görüntüleyici (Hoca/Öğrenci)" olmak üzere en az iki farklı rolde sınıflandırmalı ve işlem bazında yetki kontrolü yapmalıdır. 
    • Rationale: Program oluşturma, veri silme veya düzenleme gibi kritik operasyonların yalnızca yöneticiler tarafından yapılabilmesini güvenceye almak. 
    • Source: Ortak Kalite Standartları 
    • Fit Criterion: Görüntüleyici rolündeki kullanıcı yönetim paneline veya düzenleme uç noktalarına erişmeye çalıştığında HTTP 403 Forbidden hatası almalı ve işlem engellenmelidir.
    • Dependencies: FR1
    • Priority: Yüksek 
    • Supporting Materials: Yetki Matrisi Belgesi
    • History: 12.10.2026
FR3
    • Title: Güvenli Oturum Kapatma (Logout) ve CSRF Koruması
    • ID: FR3
    • Description: Sistem, kullanıcının oturumunu tek tıkla güvenli şekilde sonlandırmalı ve tüm form gönderimlerinde CSRF belirteci (token) doğrulaması yapmalıdır. 
    • Rationale: Oturum hırsızlığını ve siteler arası istek sahteciliği (CSRF) saldırılarını engellemek. 
    • Source: Güvenlik Standartları 
    • Fit Criterion: Oturum kapatıldığında oturum verileri temizlenmeli (session_destroy); CSRF token içermeyen veya token değeri uyuşmayan POST istekleri HTTP 419/400 ile reddedilmelidir. 
    • Dependencies: FR1
    • Priority: Yüksek 
    • Supporting Materials: Güvenlik Protokolü Şeması
    • History: 12.10.2026
FR4
    • Title: Öğretim Elemanı (Hoca) Bilgisi Yönetimi (CRUD)
    • ID: FR4
    • Description: Yönetici sistem üzerinden öğretim elemanı ekleyebilmeli, listeleyebilmeli, bilgilerini güncelleyebilmeli ve silebilmelidir. 
    • Rationale: Ders programı çizelgeleme motorunda derslerle eşleştirilecek eğitmen kaynağının sisteme tanımlanması gerekir. 
    • Source: Proje Tanım Kartı (teachers tablosu) 
    • Fit Criterion: Sistemde en az 5 hoca tanımlanabilmeli; unvan, ad, soyad ve e-posta alanları zorunlu olarak doğrulanmalıdır. 
    • Dependencies: FR2
    • Priority: Yüksek 
    • Supporting Materials: Hoca Yönetim Paneli Tasarımı
    • History: 12.10.2026
FR5
    • Title: Derslik (Oda) ve Kapasite Yönetimi (CRUD)
    • ID: FR5
    • Description: Yönetici, dersliklerin adını, bina bilgisini, türünü (amfi, laboratuvar, standart derslik) ve öğrenci kapasitesini sisteme kaydedebilmeli ve güncelleyebilmelidir. 
    • Rationale: Kısıt motorunun öğrenci mevcuduna uygun kapasitede fiziksel mekan ataması yapabilmesi için mekan havuzunun tanımlı olması zorunludur. 
    • Source: Proje Tanım Kartı (rooms tablosu) 
    • Fit Criterion: Sistemde en az 4 derslik tanımlanabilmeli; kapasite değeri 0'dan büyük bir tam sayı olmalıdır. 
    • Dependencies: FR2
    • Priority: Yüksek 
    • Supporting Materials: Derslik Tanımlama Ekranı
    • History: 12.10.2026
FR6
    • Title: Öğrenci Grubu / Şube Yönetimi (CRUD)
    • ID: FR6
    • Description: Yönetici; bölüm, sınıf düzeyi ve öğrenci mevcudu bilgilerini içeren öğrenci gruplarını (örn. Yazılım Müh. 2. Sınıf A Şubesi) sisteme kaydedebilmelidir. 
    • Rationale: Derslerin atanacağı hedef kitleyi ve dersliğin kapasite sınırını belirleyecek öğrenci sayısını modellemek. 
    • Source: Proje Tanım Kartı (student_groups tablosu) 
    • Fit Criterion: Sistemde en az 3 farklı öğrenci grubu oluşturulabilmeli; her grubun öğrenci mevcudu sayısal olarak doğrulanmalıdır. 
    • Dependencies: FR2
    • Priority: Yüksek 
    • Supporting Materials: Grup Yönetim Ekranı
    • History: 12.10.2026
FR7
    • Title: Ders Tanımlama ve İlişkilendirme (CRUD)
    • ID: FR7
    • Description: Yönetici; ders kodu, ders adı, haftalık ders saati, derslik türü gereksinimi (lab/teorik), sorumlu öğretim elemanı ve ilgili öğrenci grubunu sisteme tanımlayabilmelidir. 
    • Rationale: Çizelgeleme motorunun yerleştireceği temel etkinlik nesnelerini oluşturmak. 
    • Source: Proje Tanım Kartı (courses tablosu) 
    • Fit Criterion: Sistemde en az 12 ders tanımlanabilmeli; her ders tam olarak bir öğretim elemanına ve en az bir öğrenci grubuna bağlanmalıdır. 
    • Dependencies: FR4, FR5, FR6
    • Priority: Yüksek 
    • Supporting Materials: Ders Ekleme/Düzenleme Formu
    • History: 12.10.2026


FR8
    • Title: Hoca Müsaitlik (Availability) Matrisi Tanımlama
    • ID: FR8
    • Description: Yönetici veya ilgili hoca, haftalık zaman dilimlerinde (Pazartesi-Cuma, 08:30-17:30) hangi saatlerde ders veremeyeceğini (müsait değil / blokeli) sisteme işaretleyebilmelidir. 
    • Rationale: Hocalara uygun olmadıkları zaman dilimlerine ders yazılmasını engelleyen sert kısıtı sağlamak. 
    • Source: Proje Tanım Kartı (availability tablosu) 
    • Fit Criterion: Gün ve saat bazında (örneğin 1'er saatlik slotlar) hoca bazlı kısıtlar veritabanına/JSON'a kaydedilmeli ve çizelgeleyicide kısıt olarak işletilmelidir. 
    • Dependencies: FR4
    • Priority: Yüksek 
    • Supporting Materials: Haftalık Müsaitlik Matrisi Grid Arayüzü
    • History: 12.10.2026
FR9
    • Title: JSON / CSV Dosyasından Toplu Veri İçe Aktarma (Import)
    • ID: FR9
    • Description: Sistem; hoca, derslik, grup ve ders verilerini tek tek girmek yerine geçerli bir JSON veya CSV dosyasından toplu olarak içeri aktarabilmelidir. 
    • Rationale: Dönem başında yüzlerce verinin sisteme hızlı ve hatasız yüklenmesini sağlamak. 
    • Source: PHP Standartları Kılavuzu (CSV/JSON İçe Aktarma) 
    • Fit Criterion: Yüklenen dosya sunucuda parse edilmeli; format doğrulanıp en az 12 ders, 5 hoca, 4 derslik tek işlemde başarıyla veritabanına/JSON deposuna yazılmalıdır. 
    • Dependencies: FR2, FR4, FR5, FR6, FR7
    • Priority: Orta 
    • Supporting Materials: Örnek CSV/JSON Şablon Dosyaları
    • History: 12.10.2026
FR10
    • Title: İçe Aktarılan Verilerin Bütünlük ve Format Doğrulaması
    • ID: FR10
    • Description: Sistem, içe aktarılan CSV/JSON dosyasında eksik alan, geçersiz veri türü veya var olmayan bir yabancı anahtar (FK) tespit ederse işlemi durdurmalı ve satır bazlı hata dönmelidir. 
    • Rationale: Bozuk veya tutarsız verilerin çizelgeleme motorunu sonsuz döngüye sokmasını veya veritabanını bozmasını engellemek. 
    • Source: Veri Doğrulama ve Güvenlik Standartları 
    • Fit Criterion: Hatalı satır numarası ve hata sebebi kullanıcıya ekranda listelenmeli; kısmi bozuk yükleme yapılmadan tüm işlem geri alınmalıdır (rollback/iptal). 
    • Dependencies: FR9
    • Priority: Yüksek 
    • Supporting Materials: Hata Bildirim Modal Tasarımı
    • History: 12.10.2026

FR11
    • Title: Otomatik Ders Programı Çizelgeleme Motorunu Çalıştırma
    • ID: FR11
    • Description: Yönetici "Programı Oluştur" butonuna bastığında sistem, kısıt tabanlı geri izleme (backtracking) veya graf boyama algoritması ile haftalık çizelgeyi otomatik üretmelidir. 
    • Rationale: Projenin çekirdek problemini çözerek insan eliyle saatler süren çizelgeleme sürecini saniyeler içinde otomatize etmek. 
    • Source: Proje 14 Çekirdek Mühendislik Problemi 
    • Fit Criterion: Sistem, tüm geçerli veriler sağlandığında en fazla 10 saniye içerisinde tüm dersleri çakışmasız olarak gün-saat-derslik slotlarına atamalıdır. 
    • Dependencies: FR4, FR5, FR6, FR7, FR8
    • Priority: Yüksek 
    • Supporting Materials: Çizelgeleme Çekirdek Algoritma Akış Şeması
    • History: 12.10.2026
FR12
    • Title: Hoca Zaman Çakışması Sert Kısıt (Hard Constraint) Denetimi
    • ID: FR12
    • Description: Çizelgeleme motoru ve manuel düzenleme modülü, aynı öğretim elemanına aynı gün ve zaman diliminde birden fazla ders atamamalıdır. 
    • Rationale: Bir hocanın aynı anda iki farklı sınıfta fiziksel olarak bulunamayacağı fiziksel kısıtını sağlamak. 
    • Source: Proje 14 Çekirdek Kısıtları 
    • Fit Criterion: Üretilen ders programında hiçbir hocanın aynı slotta çakışan 2. bir ders kaydı bulunmamalıdır (%100 çakışmasızlık). 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Kural Motoru Spesifikasyon Belgesi
    • History: 12.10.2026
FR13
    • Title: Derslik Çakışması ve Kapasite Yeterliliği Sert Kısıt Denetimi
    • ID: FR13
    • Description: Sistem, bir dersliğe aynı zaman diliminde yalnızca tek bir ders atamalı ve atanan dersin öğrenci grubu mevcudunun derslik kapasitesinden küçük veya eşit olmasını garanti etmelidir. 
    • Rationale: Fiziksel mekan çakışmasını ve öğrencilerin dersliğe sığmaması problemini engellemek. 
    • Source: Proje 14 Çekirdek Kısıtları 
    • Fit Criterion: Derslik Kapasitesi >= Grup Mevcudu koşulu sağlanmayan hiçbir atama geçerli kabul edilmemeli ve bir salonda aynı saatte 2 ders yer almamalıdır. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Derslik Kapasite Kontrol Fonksiyonu
    • History: 12.10.2026
FR14
    • Title: Öğrenci Grubu Zaman Çakışması Sert Kısıt Denetimi
    • ID: FR14
    • Description: Sistem, aynı öğrenci grubuna / şubesine ait iki farklı dersin aynı gün ve aynı zaman dilimine denk gelmesini engellemelidir. 
    • Rationale: Bir öğrenci grubunun aynı anda iki farklı derse katılamayacağı zorunluluğunu korumak. 
    • Source: Proje 14 Çekirdek Kısıtları 
    • Fit Criterion: Üretilen programda herhangi bir öğrenci grubunun haftalık tablosunda üst üste binen slot bulunmamalıdır. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Grup Çakışma Analiz Testi
    • History: 12.10.2026
FR15
    • Title: Hoca Müsaitlik Durumu Sert Kısıt Denetimi
    • ID: FR15
    • Description: Çizelgeleme motoru, öğretim elemanının müsaitlik matrisinde "kapalı / müsait değil" olarak belirlediği zaman dilimlerine ilgili hocanın derslerini yerleştirmemelidir. 
    • Rationale: Eğitmenlerin idari izin, çalışma günü veya kişisel engellerine saygı duyulması. 
    • Source: Proje 14 Çekirdek Kısıtları 
    • Fit Criterion: availability tablosunda kısıtlı olan zaman dilimlerine ilgili hoca için ders atama oranı %0 olmalıdır. 
    • Dependencies: FR8, FR11
    • Priority: Yüksek 
    • Supporting Materials: Müsaitlik Doğrulama Servisi
    • History: 12.10.2026
FR16
    • Title: Çözümsüzlük / Darboğaz Neden Analizi ve Hata Raporlama
    • ID: FR16
    • Description: Sistem kısıtlar nedeniyle çakışmasız bir ders programı üretemediğinde (backtracking çözüme ulaşamadığında) işlemi kesmeli ve çözümsüzlüğe yol açan kısıtı/darboğazı detaylı olarak raporlamalıdır. 
    • Rationale: Yöneticinin hangi derslik yetersizliği, hoca müsaitlik çakışması veya ders yükü nedeniyle programın tıkandığını anlayıp müdahale edebilmesini sağlamak. 
    • Source: Proje Tanım Kartı (Çözülemez Veri Kümesinde Raporlama) 
    • Fit Criterion: Sistem salt "Hata oluştu" demek yerine "X dersliği kapasite yetersizliği nedeniyle Y grubuna atanamadı" veya "Z hocasının müsaitlik saatleri toplam ders yükünü karşılamıyor" şeklinde açıklayıcı rapor üretmelidir. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Kısıt Çözümleme Hata Analiz Paneli 
    • History: 12.10.2026
FR17
    • Title: AJAX ile Anlık Çakışma Denetimi (Canlı API Uç Noktası)
    • ID: FR17
    • Description: Yönetici takvim üzerinde bir dersi sürükleyip başka bir güne, saate veya dersliğe bırakmak istediğinde, sistem sayfa yenilenmeden arka planda çakışma kontrolü yapmalı ve sonucu JSON formatında dönmelidir. 
    • Rationale: Kullanıcı arayüzünü akıcı hale getirmek ve geçersiz taşıma işlemlerini anında tespit edip engellemek. 
    • Source: PHP Standartları (JSON Döndüren AJAX Uç Noktası) 
    • Fit Criterion: /api/schedule/validate-move uç noktası HTTP POST isteği alıp 200 ms içinde { "valid": false, "reason": "Derslik bu saatte dolu" } veya { "valid": true } şeklinde JSON yanıt vermelidir. 
    • Dependencies: FR12, FR13, FR14, FR15
    • Priority: Yüksek 
    • Supporting Materials: AJAX API İstek/Yanıt Sözleşmesi (Swagger/Postman)
    • History: 12.10.2026
FR18
    • Title: Elle (Manuel) Ders Taşıma ve Takvim Güncelleme
    • ID: FR18
    • Description: Yönetici, üretilmiş programdaki bir dersi onaylı çakışma kontrolü eşliğinde farklı bir zaman dilimine veya dersliğe elle taşıyabilmeli ve programı kaydedebilmelidir. 
    • Rationale: Otomatik yerleşim sonrasında bölüm yönetiminin idari tercih veya özel isteklerine göre programda esnek düzenleme yapabilmesi. 
    • Source: Proje 14 Tanım Kartı 
    • Fit Criterion: Yalnızca çakışma üretmeyen taşımalar veritabanına/JSON dosyasına yansıtılmalı; çakışma durumunda işlem geri alınıp kullanıcıya uyarı gösterilmelidir. 
    • Dependencies: FR17
    • Priority: Yüksek 
    • Supporting Materials: İnteraktif Takvim Düzenleme Ekranı
    • History: 12.10.2026
FR19
    • Title: Öğrenci Grubu Bazlı Haftalık Takvim Görünümü
    • ID: FR19
    • Description: Sistem, seçilen öğrenci grubuna / şubesine göre filtrelenmiş 5 günlük (Pazartesi-Cuma) saatlik haftalık ders programı matrisini listelemelidir. 
    • Rationale: Öğrencilerin ve sınıf temsilcilerinin sadece kendi şubelerine ait ders çizelgesini net olarak görebilmesi. 
    • Source: Proje 14 Görünüm Gereksinimleri 
    • Fit Criterion: Açılır listeden (dropdown) seçilen grubun tüm dersleri, gün ve saat hücrelerinde ders adı, hoca adı ve derslik bilgisiyle eksiksiz listelenmelidir. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Grup Takvimi Ekran Tasarımı
    • History: 12.10.2026
FR20
    • Title: Öğretim Elemanı (Hoca) Bazlı Haftalık Takvim Görünümü
    • ID: FR20
    • Description: Sistem, seçilen öğretim elemanına ait haftalık ders dağılımını gösteren takvim görünümünü sunmalıdır. 
    • Rationale: Hocaların haftalık çalışma programlarını, dersliklerini ve boş günlerini takip edebilmeleri. 
    • Source: Proje 14 Görünüm Gereksinimleri 
    • Fit Criterion: Seçilen hocanın girdiği tüm dersler, ilgili saat dilimlerinde sınıf ve grup bilgileriyle birlikte doğru şekilde gösterilmelidir. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Hoca Takvimi Ekran Tasarımı
    • History: 12.10.2026
FR21
    • Title: Derslik (Oda) Bazlı Haftalık Doluluk Görünümü
    • ID: FR21
    • Description: Sistem, seçilen dersliğin haftalık hangi gün ve saatlerde dolu, hangi saatlerde boş olduğunu gösteren doluluk takvimini sunmalıdır. 
    • Rationale: Fakülte yönetiminin derslik kullanım verimliliğini izleyebilmesi ve boş salonları tespit edebilmesi. 
    • Source: Proje 14 Görünüm Gereksinimleri 
    • Fit Criterion: Derslik bazında seçildiğinde dolu saatlerde ders ve hoca adı, boş saatlerde ise "Boş" ibaresi yer almalıdır. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Derslik Doluluk Matrisi Arayüzü
    • History: 12.10.2026
FR22
    • Title: Yazdırılabilir Çıktı ve PDF Formatı Desteği (CSS Print)
    • ID: FR22
    • Description: Kullanıcı "Yazdır" butonuna bastığında sistem, menü ve butonları gizleyerek A4 kağıt standardına optimize edilmiş temiz bir takvim çıktısı sunmalıdır. 
    • Rationale: Bölüm panolarına asılacak veya idareye teslim edilecek fiziksel ders programı tablolarının kolayca temin edilmesi. 
    • Source: Proje Standartları (Yazdırılabilir Çıktı / CSS Print) 
    • Fit Criterion: CSS @media print kuralları ile tarayıcı yazdırma önizlemesinde sayfa taşması olmadan, yatay/dikey A4 formatına tam oturan tablo çıktısı üretilmelidir. 
    • Dependencies: FR19, FR20, FR21
    • Priority: Orta 
    • Supporting Materials: Baskı Önizleme Şablonu
    • History: 12.10.2026
FR23
    • Title: Ders Programını JSON / Dış Dosya Olarak İhraç Etme (Export)
    • ID: FR23
    • Description: Yönetici, üretilen ders programı sonuçlarını harici sistemlerle entegrasyon veya yedekleme amacıyla JSON formatında bilgisayarına indirebilmelidir. 
    • Rationale: Üretilen verilerin taşınabilirliğini ve üçüncü parti akademik portallarla paylaşılabilirliğini sağlamak. 
    • Source: Veri Değişim Standartları 
    • Fit Criterion: İndirilen JSON dosyası geçerli schema yapısında olmalı; tüm gün, saat, hoca, grup ve derslik eşleşmelerini eksiksiz içermelidir. 
    • Dependencies: FR11
    • Priority: Düşük
    • Supporting Materials: JSON Şema Dosyası
    • History: 12.10.2026
FR24
    • Title: Doluluk ve Dağılım İstatistik Raporu (JOIN Sorgulu Analiz)
    • ID: FR24
    • Description: Sistem; dersliklerin haftalık kullanım oranlarını, hocaların toplam girdiği ders saatlerini ve en yoğun günleri özetleyen istatistiksel bir dashboard raporu sunmalıdır. 
    • Rationale: İdari yönetimin fakülte kaynaklarının etkin kullanılıp kullanılmadığını sayısal olarak denetleyebilmesi. 
    • Source: Asgari Şartlar (En az bir JOIN'li Rapor Sorgusu) 
    • Fit Criterion: Veritabanında schedule_entries, rooms, courses ve teachers tablolarını birleştiren en az bir SQL JOIN sorgusu ile derslik doluluk yüzdeleri hesaplanıp tabloda listelenmelidir. 
    • Dependencies: FR11
    • Priority: Yüksek 
    • Supporting Materials: Yönetici İstatistik ve Rapor Ekranı
    • History: 12.10.2026
FR25
    • Title: Sınav Takvimi Modu (Bonus Çizelgeleme Özelliği)
    • ID: FR25
    • Description: Yönetici vize/final haftası için mod seçtiğinde sistem, aynı öğrenci grubunun sınavlarını aynı güne/saate çakıştırmadan derslik kapasitelerine göre sınav takvimi üretebilmelidir. 
    • Rationale: Ders programı altyapısının sınav organizasyonlarında da kullanılabilmesini sağlayarak sisteme katma değer katmak. 
    • Source: Proje 14 Bonus Özellik Tanımı 
    • Fit Criterion: Sınav modunda aynı grubun aynı gün içinde en fazla 2 sınava girmesi ve sınavların birbiriyle çakışmaması algoritma tarafından güvenceye alınmalıdır. 
    • Dependencies: FR11, FR13, FR14
    • Priority: Düşük
    • Supporting Materials: Sınav Çizelgeleme Kural Dokümanı
    • History: 12.10.2026
NFR1
    • Natlang: Sistem, en az 12 ders, 5 hoca, 4 derslik ve 3 grubun bulunduğu standart bir veri kümesi için çakışmasız haftalık ders programını 10 saniyeden kısa sürede üretmelidir. 
    • Tag: Performans / Etkinlik 
    • Gist: Otomatik çizelgeleme motorunun hesaplama ve algoritma çalışma süresi. 
    • Scale: Çizelgeleme algoritmasının başlangıcı ile sonuç üretimi arasında geçen toplam süre (saniye cinsinden). 
    • Meter: Sunucu üzerinde mikro saniye hassasiyetli zamanlayıcı logları (microtime(true)) ve PHPUnit performans testleri ile ölçülür. 
    • Must: Program üretimi en fazla 10 saniyede tamamlanmalıdır. 
    • Plan: Tipik üretim süresi 4 saniye civarında olmalıdır. 
    • Wish: Çözümün 1,5 saniyenin altında bulunması. 
    • Defined: Kısıt tabanlı optimizasyon problemlerinde kullanıcının HTTP zaman aşımına (timeout) uğramaması ve akıcı bir kullanıcı deneyimi yaşaması için kritik önem taşır. 
    • Source: Proje 14 Şartnamesi (Otomatik üretim < 10 sn) 
NFR2
    • Natlang: Takvim üzerinde yapılan sürükle-bırak veya saat değiştirme işlemlerinde AJAX çakışma denetimi en geç 500 milisaniye içinde JSON yanıtı dönmelidir. 
    • Tag: Performans / Etkinlik 
    • Gist: AJAX tabanlı kural doğrulama servisinin yanıt süresi. 
    • Scale: İstemciden gönderilen HTTP POST isteği ile istemciye ulaşan JSON yanıtı arasındaki gidiş-dönüş süresi (ms cinsinden). 
    • Meter: Tarayıcı geliştirici araçları (Network sekmesi) ve API log analizleri ile ölçülür. 
    • Must: Yanıt süresi 500 ms'yi kesinlikle geçmemelidir. 
    • Plan: Yanıtların ortalama 200 ms altında dönmesi hedeflenir. 
    • Wish: Yanıtın 80 ms altında istemciye ulaşması. 
    • Defined: Kullanıcı dersi elle taşırken arayüzün donmaması veya gecikmeli tepki verip kullanıcıyı bekletmemesi gerekir. 
    • Source: Proje Standartları (AJAX JSON Uç Noktası) 
NFR3
    • Natlang: Sistem veri erişim katmanı Repository tasarım deseni ile soyutlanmalı; Aşama 2'den (JSON) Aşama 3'e (MySQL) geçişte Service, Controller ve View katmanlarında tek bir satır dahi kod değişikliği yapılmamalıdır. 
    • Tag: Mimarlık / Sürdürülebilirlik 
    • Gist: Veri depolama teknolojisi bağımsızlığı ve gevşek bağlılık (loose coupling). 
    • Scale: Veritabanı motoru değiştiğinde iş mantığı sınıflarında (Service/Controller) değiştirilen satır sayısı. 
    • Meter: Git diff analizi ve PHPUnit testlerinin her iki adaptörde de çalıştırılmasıyla ölçülür. 
    • Must: Service ve Controller katmanlarında 0 satır kod değişikliği ile geçiş sağlanmalıdır. 
    • Plan: Repository interface'ine %100 uyum sağlayan iki ayrı repository implementasyonu (JsonScheduleRepository, PdoScheduleRepository). 
    • Wish: Tek bir ortam değişkeni (.env veya config) ile JSON ve MySQL modları arasında anında geçiş yapılabilmesi.
    • Defined: Dersin temel pedagojik hedefi katmanlı mimarinin doğru kurulması ve bağımlılıkların tersine çevrilmesidir (IoC / Dependency Inversion). 
    • Source: Ders Kılavuzu Aşama 2 ve Aşama 3 Geçiş Kuralları 
