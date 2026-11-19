# MDB Fakültesi – Ders Programı Oluşturucu

Mühendislik ve Doğa Bilimleri Fakültesi için **kısıt tabanlı (backtracking)** otomatik ders programı üretici.

## Proje Yapısı

```
php_git/
├── data/                        ← JSON veri dosyaları (kalıcı depo)
│   ├── teachers.json
│   ├── rooms.json
│   ├── student_groups.json
│   ├── courses.json
│   ├── availability.json
│   └── schedule_entries.json
├── public/                      ← Web kökü
│   ├── index.html               ← Tek sayfa arayüzü
│   ├── router.php               ← Front controller + routing
│   ├── .htaccess
│   ├── css/style.css
│   └── js/
│       ├── api.js               ← Merkezi fetch wrapper
│       ├── schedule.js          ← Çizelge render + generate
│       ├── teachers.js
│       ├── courses.js
│       ├── rooms.js
│       ├── groups.js
│       ├── todo.js              ← localStorage todo + notlar
│       └── app.js               ← Tab sistemi
├── src/
│   ├── Config/
│   │   └── AppConfig.php        ← Sabitler, dataDir()
│   ├── Entity/                  ← 6 entity (Teacher, Room, StudentGroup,
│   │   └── ...                     Course, Availability, ScheduleEntry)
│   ├── Repository/              ← JSON tabanlı CRUD
│   │   ├── AbstractJsonRepository.php
│   │   └── ...
│   ├── DTO/
│   │   ├── ScheduleEntryDTO.php
│   │   └── ConflictDTO.php
│   ├── Exception/               ← ScheduleException, ConflictException,
│   │   └── ...                     NoSolutionException, RepositoryException
│   ├── Service/
│   │   ├── SchedulerService.php      ← Backtracking algoritması
│   │   ├── ConflictCheckerService.php
│   │   ├── ScheduleViewService.php
│   │   ├── TeacherService.php
│   │   └── CourseService.php
│   └── Controller/
│       ├── BaseController.php
│       ├── TeacherController.php
│       ├── CourseController.php
│       ├── RoomController.php
│       ├── StudentGroupController.php
│       └── ScheduleController.php
├── tests/
│   ├── BaseTestCase.php
│   ├── Service/
│   │   ├── SchedulerServiceTest.php      ← 7 test
│   │   └── ConflictCheckerServiceTest.php ← 8 test
│   └── Repository/
│       └── TeacherRepositoryTest.php     ← 7 test
├── composer.json
└── phpunit.xml
```

## Kurulum

```bash
composer install
```

## Çalıştırma

```bash
# PHP built-in server (geliştirme)
php -S localhost:8000 -t public public/router.php
```

Tarayıcıda: `http://localhost:8000`

## Testleri Çalıştır

```bash
composer test
# veya:
vendor/bin/phpunit
```

## Algoritma

**Backtracking (Özyinelemeli Geri İzleme):**
1. Dersler `weekly_hours × ders sayısı` atama listesine dönüştürülür
2. Az oda seçeneği olan dersler önce atanır (MRV sezgisel)
3. Her atama için tüm `(gün, slot, oda)` kombinasyonları denenir
4. 5 kısıt kontrol edilir: hoca çakışması, oda çakışması, grup çakışması, kapasite, müsaitlik
5. Çözüm bulunamazsa `NoSolutionException` + neden listesi fırlatılır

## API Uç Noktaları

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/schedule` | Haftalık grid |
| POST | `/api/schedule/generate` | Otomatik program üret |
| GET | `/api/schedule/conflicts` | Çakışma raporu |
| POST | `/api/schedule/check` | Anlık çakışma kontrolü (AJAX) |
| GET | `/api/schedule/view?by=teacher&id=1` | Filtreli görünüm |
| PUT | `/api/schedule/entry/{id}` | Manuel düzenleme |
| GET/POST/PUT/DELETE | `/api/teachers` | Hoca CRUD |
| GET/POST/PUT/DELETE | `/api/courses` | Ders CRUD |
| GET/POST/PUT/DELETE | `/api/rooms` | Derslik CRUD |
| GET/POST/PUT/DELETE | `/api/groups` | Öğrenci grubu CRUD |
