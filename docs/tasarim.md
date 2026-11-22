# Proje Tasarım Belgesi (`docs/tasarim.md`)

## Sunum 1 Sorumluluk Dağılımı
* **Esin Göç:** 1. Giriş, Çekirdek Problem, Roller ve Kullanıcı Hikâyeleri
* **Berk Dinçer:** 2. Mimari, Sınıf Diyagramı ve Tasarım Desenleri
* **Mustafa Nehoş:** 3. Sıralama Diyagramı, ER Diyagramı ve 3NF Gerekçesi
* **Grup Ortak:** 4. Arayüz Taslakları, Risk Listesi ve GitHub Backlog

---

## 1. Mimari

### 1.1. Katman Şeması ve Sorumluluklar

Ders Programı Oluşturucu (DPO) projesi, sorumlulukların ayrılığı (Separation of Concerns) ve esneklik ilkelerine dayalı 5 katmanlı bir mimari üzerinde kurgulanmıştır. Her istek Front Controller (`public/index.php`) ve Router tarafından karşılanır, ilgili Controller'a yönlendirilir.

| Katman | Görevi | Asla Yapmaz | DPO Projesindeki Karşılığı |
| :--- | :--- | :--- | :--- |
| **Router** | URL ve HTTP metoduna göre çalışacak Controller metodunu seçer. | İş mantığı veya doğrulama içermez. | `POST /program/olustur` -> `ScheduleController::generate` |
| **Controller** | `$_POST`/`$_GET` veya JSON verisini alır, ilgili Service sınıfını tetikler, Görünümü (View) veya JSON yanıtını seçer. | SQL yazmaz, kısıt motorunu çalıştırmaz, veri hesaplamaz. | `ScheduleController`, `TeacherController`, `CourseController` |
| **Service** | Çakışmasız çizelgeleme algoritmasını (backtracking), kısıt denetimlerini ve iş kurallarını yürütür. | `$_POST` okumaz, HTML çıktısı üretmez. | `ScheduleSolverService`, `ConflictValidatorService`, `DataImportService` |
| **Repository** | Veriyi saklama ortamından (JSON veya MySQL) okur ve yazar. | Çakışma kontrolü veya iş mantığı yürütmez. | `ScheduleRepositoryInterface` (`JsonScheduleRepository`, `PdoScheduleRepository`) |
| **Model** | Veritabanı/JSON verilerini taşıyan sade nesnelerdir (Entity/DTO). | Veritabanı bağlantısı kurmaz, sorgu çalıştırmaz. | `Teacher`, `Course`, `Room`, `StudentGroup`, `ScheduleEntry`, `Availability` |
| **View** | Kullanıcı arayüzünü ve takvim matrisini gösterir. | Hesap yapmaz, veritabanına erişmez. | `views/schedule/matrix.php`, `views/login.php` |

---

### 1.2. Klasör Yapısı (PSR-4 Uyumlu)

```text
dpo-ders-programi/
├── database/               # Veritabanı SQL dökümleri ve migration dosyaları
├── docs/                   # Proje dokümantasyonu
│   ├── wireframes/         # Ekran taslakları (PNG)
│   └── tasarim.md          # Tasarım belgesi
├── public/                 # Uygulama giriş noktası (Web Root)
│   ├── css/                # Stil dosyaları ve CSS Print kuralları
│   ├── js/                 # Sürükle-bırak takvim ve AJAX betikleri
│   └── index.php           # Front Controller
├── src/                    # PSR-4 Autoload kapsamındaki kaynak kodlar
│   ├── Controller/
│   │   ├── AuthController.php
│   │   ├── CourseController.php
│   │   ├── RoomController.php
│   │   ├── ScheduleController.php
│   │   └── TeacherController.php
│   ├── Model/
│   │   ├── Availability.php
│   │   ├── Course.php
│   │   ├── Room.php
│   │   ├── ScheduleEntry.php
│   │   ├── StudentGroup.php
│   │   └── Teacher.php
│   ├── Repository/
│   │   ├── Contract/
│   │   │   ├── CourseRepositoryInterface.php
│   │   │   ├── RoomRepositoryInterface.php
│   │   │   ├── ScheduleRepositoryInterface.php
│   │   │   └── TeacherRepositoryInterface.php
│   │   ├── Json/
│   │   │   └── JsonScheduleRepository.php
│   │   └── Pdo/
│   │       └── PdoScheduleRepository.php
│   └── Service/
│       ├── Algorithmic/
│       │   └── BacktrackingSolver.php
│       ├── Strategy/
│       │   ├── ConstraintStrategyInterface.php
│       │   ├── TeacherConflictStrategy.php
│       │   ├── RoomTypeAndCapacityStrategy.php
│       │   ├── StudentGroupConflictStrategy.php
│       │   └── TeacherAvailabilityStrategy.php
│       ├── ConflictValidatorService.php
│       ├── DataImportService.php
│       └── ScheduleSolverService.php
├── storage/                # JSON depolama alanı (Aşama 2)
│   ├── courses.json
│   ├── rooms.json
│   ├── schedule.json
│   └── teachers.json
├── tests/                  # PHPUnit birim ve entegrasyon testleri
│   ├── Unit/
│   │   └── ScheduleSolverTest.php
│   └── Integration/
├── vendor/                 # Composer bağımlılıkları (.gitignore içinde)
├── .gitignore
├── composer.json           # PSR-4 ve bağımlılık tanımları
└── README.md




classDiagram
    %% Model Katmanı
    class Teacher {
        +int id
        +string title
        +string firstName
        +string lastName
        +string email
    }

    class Room {
        +int id
        +string name
        +string building
        +string type
        +int capacity
    }

    class StudentGroup {
        +int id
        +string department
        +int gradeLevel
        +string section
        +int studentCount
    }

    class Course {
        +int id
        +string code
        +string name
        +int weeklyHours
        +string roomTypeRequired
        +int teacherId
        +int studentGroupId
    }

    class Availability {
        +int id
        +int teacherId
        +int dayOfWeek
        +string timeSlot
    }

    class ScheduleEntry {
        +int id
        +int courseId
        +int roomId
        +int teacherId
        +int studentGroupId
        +int dayOfWeek
        +string timeSlot
        +string entryType
    }

    %% Repository Katmanı
    class ScheduleRepositoryInterface {
        <<interface>>
        +saveSchedule(array entries) bool
        +getScheduleByGroup(int groupId) array
        +getScheduleByTeacher(int teacherId) array
        +getScheduleByRoom(int roomId) array
        +getOccupancyStatistics() array
        +clearSchedule() bool
    }

    class JsonScheduleRepository {
        -string filePath
        +saveSchedule(array entries) bool
        +getScheduleByGroup(int groupId) array
        +getScheduleByTeacher(int teacherId) array
        +getScheduleByRoom(int roomId) array
        +getOccupancyStatistics() array
        +clearSchedule() bool
    }

    class PdoScheduleRepository {
        -PDO pdo
        +saveSchedule(array entries) bool
        +getScheduleByGroup(int groupId) array
        +getScheduleByTeacher(int teacherId) array
        +getScheduleByRoom(int roomId) array
        +getOccupancyStatistics() array
        +clearSchedule() bool
    }

    %% Service Katmanı (Strategy & Business Logic)
    class ConstraintStrategyInterface {
        <<interface>>
        +validate(ScheduleEntry entry, array currentSchedule) bool
    }

    class TeacherConflictStrategy {
        +validate(ScheduleEntry entry, array currentSchedule) bool
    }

    class RoomTypeAndCapacityStrategy {
        +validate(ScheduleEntry entry, array currentSchedule) bool
    }

    class StudentGroupConflictStrategy {
        +validate(ScheduleEntry entry, array currentSchedule) bool
    }

    class TeacherAvailabilityStrategy {
        +validate(ScheduleEntry entry, array currentSchedule) bool
    }

    class ConflictValidatorService {
        -array strategies
        +addStrategy(ConstraintStrategyInterface strategy) void
        +checkAllConstraints(ScheduleEntry entry, array currentSchedule) array
    }

    class DataImportService {
        +importFromJson(string jsonContent) bool
        +importFromCsv(string csvFilePath) bool
        +validateDataIntegrity(array data) array
    }

    class ScheduleSolverService {
        -ScheduleRepositoryInterface repository
        -ConflictValidatorService validator
        +generateSchedule(string mode) array
        +validateMove(ScheduleEntry entry) array
    }

    %% Controller Katmanı
    class ScheduleController {
        -ScheduleSolverService solverService
        -DataImportService importService
        +generate() void
        +validateMoveApi() void
        +importData() void
        +index() void
    }

    %% İlişkiler
    ScheduleController ..> ScheduleSolverService : kullanır
    ScheduleController ..> DataImportService : kullanır
    ScheduleSolverService --> ScheduleRepositoryInterface : bağımlıdır
    ScheduleSolverService --> ConflictValidatorService : kullanır
    
    JsonScheduleRepository ..|> ScheduleRepositoryInterface : uygular
    PdoScheduleRepository ..|> ScheduleRepositoryInterface : uygular
    
    ConflictValidatorService --> ConstraintStrategyInterface : çalıştırır
    TeacherConflictStrategy ..|> ConstraintStrategyInterface : uygular
    RoomTypeAndCapacityStrategy ..|> ConstraintStrategyInterface : uygular
    StudentGroupConflictStrategy ..|> ConstraintStrategyInterface : uygular
    TeacherAvailabilityStrategy ..|> ConstraintStrategyInterface : uygular

    ScheduleSolverService ..> ScheduleEntry : üretir
    ScheduleRepositoryInterface ..> ScheduleEntry : saklar
    Course --> Teacher : ilişkilidir
    Course --> StudentGroup : ilişkilidir
    ScheduleEntry --> Course : içerir
    ScheduleEntry --> Room : içerir



sequenceDiagram
    autonumber
    actor Y as Yönetici (User)
    participant C as ScheduleController
    participant S as ScheduleSolverService
    participant V as ConflictValidatorService
    participant R as ScheduleRepositoryInterface
    participant Vw as View / Arayüz

    Y->>C: POST /program/olustur ("Program Oluştur" Butonuna Basar)
    activate C
    
    C->>S: generateSchedule("ders")
    activate S
    
    S->>R: getActivePeriodData()
    activate R
    R-->>S: Veri Seti (Dersler, Hocalar, Derslikler, Gruplar, Kısıtlar)
    deactivate R

    Note over S,V: Çakışma Kontrolü ve Backtracking Algoritması Çalışır

    loop Her Ders ve Slot İçi Atama Denemesi
        S->>V: checkAllConstraints(ScheduleEntry, currentSchedule)
        activate V
        V-->>S: ValidationResult (Başarılı / Çakışma Nedeni)
        deactivate V
    end

    alt Çözüm Bulundu (Çakışma Yok / %100 Başarı)
        S->>R: saveSchedule(scheduleEntries)
        activate R
        R-->>S: bool (true)
        deactivate R
        
        S-->>C: ScheduleResult (Success)
        C-->>Vw: render("schedule/matrix", data)
        activate Vw
        Vw-->>Y: Haftalık Çakışmasız Ders Programını Göster (HTTP 200)
        deactivate Vw

    else Kısıt İhlali / Çözümsüzlük Durumu (Darboğaz)
        S-->>C: ScheduleException ("X Hocası/Dersliği için çakışma var")
        C-->>Vw: render("schedule/error", bottleneckReport)
        activate Vw
        Vw-->>Y: Çözümsüzlük Raporunu Göster ("Hoca Z için yeterli boş slot bulunamadı")
        deactivate Vw
    end

    deactivate S
    deactivate C



erDiagram
    USERS ||--o| TEACHERS : "ilişkilendirilir"
    TEACHERS ||--o{ COURSES : "verir"
    STUDENT_GROUPS ||--o{ COURSES : "alır"
    TEACHERS ||--o{ AVAILABILITIES : "müsaitlik_tanımlar"
    COURSES ||--o{ SCHEDULE_ENTRIES : "planlanır"
    ROOMS ||--o{ SCHEDULE_ENTRIES : "tahsis_edilir"
    TEACHERS ||--o{ SCHEDULE_ENTRIES : "görevlendirilir"
    STUDENT_GROUPS ||--o{ SCHEDULE_ENTRIES : "katılır"

    USERS {
        int id PK
        string email UK
        string password_hash
        string role "Admin veya Viewer"
        datetime created_at
    }

    TEACHERS {
        int id PK
        int user_id FK
        string title
        string first_name
        string last_name
        string email UK
    }

    ROOMS {
        int id PK
        string name
        string building
        string type "Amfi, Lab, Standart"
        int capacity
    }

    STUDENT_GROUPS {
        int id PK
        string department
        int grade_level
        string section
        int student_count
    }

    COURSES {
        int id PK
        string code UK
        string name
        int weekly_hours
        string room_type_required
        int teacher_id FK
        int student_group_id FK
    }

    AVAILABILITIES {
        int id PK
        int teacher_id FK
        int day_of_week "1-5"
        string time_slot
    }

    SCHEDULE_ENTRIES {
        int id PK
        int course_id FK
        int room_id FK
        int teacher_id FK
        int student_group_id FK
        int day_of_week
        string time_slot
        string entry_type "Ders veya Sinav"
    }




flowchart LR
    G[Giriş Ekranı - /login] --> RolCheck{Kullanıcı Rolü?}
    
    RolCheck -->|Görüntüleyici| OG_Panel[Görüntüleyici Paneli]
    OG_Panel --> OG_Haftalik[Haftalık Takvim Görünümü]
    OG_Haftalik --> OG_Grup[Öğrenci Grubu Filtresi]
    OG_Haftalik --> OG_Hoca[Hoca Takvimi Filtresi]
    OG_Haftalik --> OG_Derslik[Derslik Doluluk Filtresi]
    OG_Haftalik --> OG_Musaitlik[Hoca Müsait Olmayan Saat Bildirimi]
    OG_Haftalik --> OG_Print[Yazdır / PDF Al - CSS Print]

    RolCheck -->|Sistem Yöneticisi| AD_Panel[Yönetici Paneli]
    AD_Panel --> AD_CRUD[Veri Yönetimi - Hoca, Ders, Derslik, Grup]
    AD_Panel --> AD_Import[JSON / CSV Toplu İçe Aktarma]
    AD_Panel --> AD_Generate[Otomatik Çizelge Oluştur - Backtracking]
    AD_Panel --> AD_Drag[İnteraktif Takvim - AJAX Sürükle-Bırak]
    AD_Panel --> AD_Report[Çözümsüzlük / Darboğaz Analiz Raporu]
    AD_Panel --> AD_Stats[Doluluk & Dağılım İstatistik Dashboard - JOIN]



