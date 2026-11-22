

### Etkileşim 1

**Soru:**
> Repository Pattern'i neden kullanmalıyım, ne işe yarıyor?

**Yanıt:**
Repository Pattern, veri erişim mantığını iş mantığından ayırmanı sağlayan bir tasarım desenidir. Temel fikir şu: Service katmanın, verinin nereden geldiğini bilmemeli. Sen bir `ScheduleRepositoryInterface` tanımlarsın ve bu arayüz `save()`, `findAll()`, `findById()` gibi metodlar içerir. Sonra bu arayüzü implement eden `JsonScheduleRepository` ve `PdoScheduleRepository` sınıflarını ayrı ayrı yazarsın.

Projen için bu özellikle kritik çünkü Faz 2'de JSON ile çalışacaksın, Faz 3'te MySQL'e geçeceksin. Eğer Repository kullanmazsan, geçiş sırasında Service ve Controller katmanlarında onlarca yerde değişiklik yapmak zorunda kalırsın. Repository sayesinde sadece bağımlılık enjeksiyonunda hangi sınıfı geçirdiğini değiştirirsin, başka hiçbir şeye dokunmazsın. Bu aynı zamanda testlerde de işe yarar: gerçek veritabanı yerine sahte (mock) bir repository inject edip birim testlerini bağımsız çalıştırabilirsin.

**Yorum:**
Faz geçişinde sıfır değişiklik fikri ikna edici geldi. Arayüzü buna göre tanımladım, `JsonScheduleRepository`'i önce yazacağım.

---

### Etkileşim 2

**Soru:**
> Backtracking mi yoksa Greedy Graph Coloring mı daha iyi? Hangisini seçeyim?

**Yanıt:**
İkisi de bu problem için geçerli yaklaşımlar ama farklı senaryolarda öne çıkıyorlar.

Backtracking, kısıt ihlali olduğunda bir önceki adıma geri döner ve alternatif bir atama dener. Küçük-orta büyüklükteki problemlerde optimal çözüm bulma garantisi var ancak en kötü senaryoda üstel zamanda çalışır. Senin projen için —12 ders, 5 hoca, 4 derslik— bu genellikle sorun yaratmaz.

Greedy Graph Coloring ise dersleri bir grafa düğüm olarak modelleyip çakışan dersleri farklı renklere (zaman dilimlerine) atar. Çok daha hızlı çalışır ama çözümün optimal olduğunu garanti etmez; bazı durumlarda mevcut olan bir çözümü bile bulamayabilir.

Benim önerin: Strategy Pattern ile ikisini birden yaz. `SchedulingStrategyInterface` tanımla, `BacktrackingSchedulingStrategy` ve `GreedyGraphColoringStrategy` bu arayüzü implement etsin. Çalışma zamanında hangisini kullanacağını `SchedulingService`'e inject ederek seçersin. Bu şekilde hem iki algoritmayı da öğrenmiş olursun hem de sisteme esneklik katmış olursun.

**Yorum:**
İkisini birden yazmak biraz fazla iş gibi görünse de Strategy Pattern sayesinde yapı zaten hazır oluyor. Önce Backtracking'i bitirip ardından Greedy'yi eklemeyi planlıyorum.

---

### Etkileşim 3

**Soru:**
> AJAX ile çakışma kontrolü için endpoint nasıl tasarlamalıyım?

**Yanıt:**
Drag-and-drop gibi anlık kontroller için hafif, odaklı bir endpoint en doğru yaklaşım. Şöyle tasarlayabilirsin:

**Endpoint:** `POST /api/schedule/validate-move`

**İstek gövdesi:**
```json
{
  "lesson_id": 42,
  "new_day": "Pazartesi",
  "new_slot": 3,
  "new_room_id": 7
}
```

**Başarılı yanıt:**
```json
{ "valid": true }
```

**Hata yanıtı:**
```json
{ "valid": false, "reason": "Bu saatte derslikte başka bir ders var." }
```

Controller bu isteği alır, `ConstraintValidator`'ı çağırır ve sonucu JSON olarak döndürür. Hedef yanıt süresi 200ms altı olmalı; JSON deposu kullandığında bunu kolayca karşılarsın. Frontend tarafında ise `fetch()` ile bu endpoint'i çağırıp `valid` değerine göre ders kartını yeşil ya da kırmızıya boyayabilirsin. `reason` alanını da küçük bir tooltip olarak göstermen UX açısından çok işe yarar.

**Yorum:**
`reason` alanını arayüzde gösterme fikri güzeldi, bunu ekledim. Endpoint yapısını olduğu gibi kullandım.

---

### Etkileşim 4

**Soru:**
> Hard constraint'leri tek bir yerde mi tutayım yoksa dağıtık mı?

**Yanıt:**
Kesinlikle tek bir yerde toplamanı öneririm. `ConstraintValidator` adında bir sınıf oluştur ve her kısıtı ayrı bir metot olarak tanımla:

```php
class ConstraintValidator
{
    public function hasTeacherConflict(Schedule $schedule, Lesson $lesson): bool { ... }
    public function hasRoomConflict(Schedule $schedule, Lesson $lesson): bool { ... }
    public function exceedsRoomCapacity(Room $room, StudentGroup $group): bool { ... }
    public function hasGroupConflict(Schedule $schedule, Lesson $lesson): bool { ... }
    public function isTeacherAvailable(Teacher $teacher, string $day, int $slot): bool { ... }
}
```

Dağıtık tutarsan zamanla hangi kısıtın nerede kontrol edildiğini takip edemezsin. Merkezi tutunca yeni bir kısıt eklemen gerektiğinde tek bir dosyaya bakman yeterli. Ayrıca bu yapı PHPUnit testleri için çok elverişli: her metodu izole olarak test edebilirsin, gerçek bir program üretmene gerek kalmaz.

**Yorum:**
Algoritmayı yazarken kısıtları içine gömmüştüm, bu uyarı sayesinde `ConstraintValidator`'a taşıdım. Test yazmak gerçekten kolaylaştı.

---

### Etkileşim 5

**Soru:**
> JSON depolamayı nasıl yapılandırmalıyım, tek dosya mı birden fazla mı?

**Yanıt:**
Birden fazla dosya kullanmanı öneririm. `data/schedules/` klasörü altında her oluşturulan programı ayrı bir JSON dosyasına kaydet. Dosya adı için `schedule_2026-fall-bolum01.json` gibi anlamlı bir şema kullanabilirsin.

Tek bir büyük dosyada tutmak birkaç soruna yol açar: eşzamanlı yazma çakışmaları, geçmiş programlara erişimin zorlaşması ve dosyanın zamanla okunaksız büyüklüğe ulaşması. Ayrı dosyalarla her program bağımsız; eski bir programa geri dönmek ya da karşılaştırma yapmak çok daha kolay.

`JsonScheduleRepository`'in `findAll()` metodu bu klasördeki tüm JSON dosyalarını tarayıp bir koleksiyon döndürebilir. Faz 3'te MySQL'e geçtiğinde `PdoScheduleRepository` aynı `findAll()` metodunu SQL sorgusuyla karşılar, bu klasör ise arşiv olarak kalır.

Bir not: `data/` klasörünü `.gitignore`'a eklemeyi unutma, test verilerin repoya karışmasın.

**Yorum:**
`.gitignore` uyarısını atlamıştım, iyi hatırlattı. Klasör yapısını bu şekilde kurdum.

---

### Etkileşim 6

**Soru:**
> PHPStan Level 5 çok mu katı, daha düşük seviyeyle başlasam olmaz mı?

**Yanıt:**
Baştan Level 5 ile gitmen uzun vadede çok daha az iş çıkarır. Level 5'in getirdiği temel şeyler şunlar: dönüş tiplerinin net tanımlanması, null olabilecek değerlerin `?Type` ile işaretlenmesi ve tip uyumsuzluklarının yakalanması. Bunlar zaten iyi PHP yazmak demek, fazladan kural değil.

Daha düşük seviyeyle başlayıp sonradan Level 5'e yükseltmeye çalışırsan, birikmiş onlarca uyarıyla boğuşmak zorunda kalırsın. Oysa baştan `declare(strict_types=1)` ve PHPStan Level 5 birlikte kullanırsan runtime'da karşılaşabileceğin tip hatalarının büyük çoğunluğunu derleme/analiz aşamasında yakalarsın. Repository ve Service katmanlarında zaten tip bildirimlerini kullanıyorsan ekstra iş neredeyse sıfır.

**Yorum:**
İkna oldum, Level 5'te kaldım. `declare(strict_types=1)` zaten kullanıyordum, gerisi doğal geldi.

---

*Kullanılan AI Modeli: Claude Sonnet 5.5*
