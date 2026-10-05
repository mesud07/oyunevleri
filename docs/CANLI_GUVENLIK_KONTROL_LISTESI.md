# Oyun Evleri canlı güvenlik kontrol listesi

Bu liste sırayla uygulanır. Bir önceki aşama tamamlanmadan yayın yapılmaz.

## 1. Kod ve erişim güvenliği — tamamlandı

- [x] Bütün `/panel` rotaları için merkezi oturum ve yetki kontrolü.
- [x] Kurum/tenant bağlamının varsayılan olarak `0` olması; CLI işlerinde açık kurum kimliği zorunluluğu.
- [x] Oturum kimliğinin girişte ve periyodik olarak yenilenmesi; 30 dakika hareketsizlik ve 12 saat mutlak süre sınırı.
- [x] Parola, rol veya aktiflik değiştiğinde eski oturumların iptal edilmesi.
- [x] Giriş ve MFA denemeleri için veritabanı tabanlı hız sınırlaması.
- [x] Kurucu, sistem yöneticisi ve muhasebe rolleri için TOTP MFA altyapısı.
- [x] Bilinen varsayılan admin hesabının kurulum şemasından kaldırılması.
- [x] İlk kurucuyu güvenli oluşturmak için `php bin/create-admin.php`.

## 2. Kişisel veri ve dış bağlantılar — tamamlandı

- [x] Veli portalında SMS tek kullanımlık kod; kodların düz metin saklanmaması.
- [x] Randevu katılım bağlantılarında süreli, iptal edilebilir ve yalnızca hash olarak saklanan token.
- [x] NES uç noktalarının test/canlı ortamına göre kesin HTTPS allowlist kontrolü.
- [x] TLS sertifika doğrulaması, yönlendirme kapatma ve yanıt boyutu sınırı.
- [x] Fatura görüntüleme, onay, iptal, ayar ve toplu indirme işlemlerinde denetim kaydı.
- [x] Teknik hata ayrıntılarının kullanıcıya ve üretim ekranına sızdırılmaması.

## 3. Sunucu ve dağıtım — tamamlandı

- [x] Kaynak kodu salt okunur, yalnız `storage` alanı uygulama kullanıcısına yazılabilir Docker yapısı.
- [x] `0777/0666` izinlerinin kaldırılması.
- [x] Dizin listeleme, TRACE, sunucu imzası ve hassas dosya erişiminin kapatılması.
- [x] CSP nonce, HSTS, frame/content/referrer/permissions güvenlik başlıkları.
- [x] Geliştirme servislerinin sadece `127.0.0.1` üzerinden açılması.
- [x] CI üzerinde PHP sözdizimi, secret taraması ve yüksek/kritik zafiyet taraması.
- [x] Dağıtımdan önce zorunlu güvenlik kapıları.

## 4. Canlıdan önce işletme sahibinin tamamlayacağı zorunlu işler

- [ ] Sohbette veya başka kanallarda paylaşılmış tüm NES API anahtarlarını NES panelinden iptal edip yenisini üret.
- [ ] Güçlü ve benzersiz (en az 16 karakter) veritabanı parolası tanımla.
- [ ] `APP_KEY` için en az 32 bayt kriptografik rastgele değer oluştur; bu anahtarı parola kasasında sakla.
- [ ] Yedekleme için uygulamadan farklı, en az 24 karakterlik `BACKUP_ENCRYPTION_KEY` tanımla ve ayrı bir kasada tut.
- [ ] Fatura arşivinin bulunduğu disk/birimde beklemede şifrelemeyi etkinleştir ve `STORAGE_ENCRYPTION_CONFIRMED=true` yap.
- [ ] Kurucu, sistem yöneticisi ve muhasebe kullanıcılarının her birinde `/panel/guvenlik/mfa` ekranından MFA kurulumunu tamamla; kurtarma kodlarını çevrimdışı sakla.
- [ ] Yukarıdakiler tamamlandıktan sonra `MFA_ENFORCE=true` ve `KEY_ROTATION_CONFIRMED=true` yap.
- [ ] Canlı alan adına geçerli TLS sertifikası kur; `APP_URL=https://...`, `APP_ENV=production`, `APP_DEBUG=false` yap.
- [ ] Canlıda `SESSION_COOKIE_SECURE=true` yap; ters proxy kullanılıyorsa sadece proxy IP'lerini `TRUSTED_PROXIES` içine yaz.
- [ ] Güvenlik migrasyonunu çalıştır: `php bin/security-migrate.php`.
- [ ] Yayın kapısını çalıştır: `php bin/security-check.php`. Sonuç sıfır hata olmadan yayına çıkma.

## 5. Yedekleme ve geri dönüş testi

- [ ] `BACKUP_DIR` uygulama sunucusundan farklı, erişimi sınırlı bir depolamayı göstermeli.
- [ ] Her gece `bin/encrypted-backup.sh` çalışmalı; yedek dosyası ile `.sha256` dosyası birlikte saklanmalı.
- [ ] En az ayda bir izole ortamda veritabanı ve fatura arşivi geri yükleme testi yapılmalı.
- [ ] `bin/verify-encrypted-backup.sh` ile her yedeğin SHA-256, şifre çözme ve zorunlu içerik kontrolü otomatik çalışmalı.
- [ ] Yedekler için günlük/haftalık/aylık saklama ve otomatik silme politikası belirlenmeli.
- [ ] Sunucu, veritabanı ve NES kesintisi için geri dönüş sorumlusu ve iletişim kanalı belirlenmeli.

## 6. Yayın ve ilk 24 saat

- [ ] Önce bakım penceresi aç, yayın öncesi şifreli yedek al ve SHA-256 doğrula.
- [ ] Migrasyon, uygulama yayını ve `security-check` sırasıyla çalıştır.
- [ ] Giriş, MFA, yetkisiz doğrudan URL, öğrenci/veli kurum izolasyonu, tahsilat ve fatura taslak/onay/iptal/PDF akışlarını test et.
- [ ] Loglarda 401/403/419/429/5xx artışını, NES hata oranını, disk kullanımını ve yedek görevini izle.
- [ ] Kritik sorun halinde önceki uygulama sürümüne dön; veritabanını sadece doğrulanmış geri dönüş planıyla geri al.

## 7. Periyodik bakım

- [ ] İşletim sistemi ve Docker imajlarını aylık yamala; kritik açıkta bekleme.
- [ ] Kullanılmayan hesapları aylık kapat, yetkileri üç ayda bir gözden geçir.
- [ ] NES, SMS, GitHub ve sunucu anahtarlarını düzenli değiştir.
- [ ] Denetim kayıtlarını, başarısız girişleri ve toplu işlemleri haftalık incele.
- [ ] Bağımlılık ve secret taramalarının CI'da başarılı kaldığını doğrula.
