# Canlıya geçiş uygulama sırası ve mevcut durum

Bu belge 20 Ağustos 2026 tarihli teknik denetim sonucudur. `php bin/security-check.php` sıfır hata vermeden canlı dağıtım yapılmaz.

## Tamamlanan işlemler

1. Tüm panel rotalarına merkezi oturum ve rol/yetki kontrolü uygulandı.
2. Parola değişikliği, rol değişikliği ve kullanıcı kapatma işlemlerinde açık oturumlar geçersiz kılındı.
3. Hız sınırlamalı giriş, TOTP MFA, kurtarma kodları ve yüksek riskli işlemlerde yeniden doğrulama eklendi.
4. Veli portalı SMS OTP ile korundu; herkese açık randevu bağlantıları süreli ve iptal edilebilir hale getirildi.
5. Kurumlar arası veri izolasyonu modeller, servisler, SMS kuyruğu ve zamanlanmış görevlerde güçlendirildi.
6. NES adresleri resmî HTTPS allowlist ile sınırlandı; TLS doğrulaması ve güvenli hata yönetimi eklendi.
7. Aktif NES kaydı `production` ortamına ve `https://api.nes.com.tr` adresine geçirildi.
8. Fatura onay/iptal/toplu indirme işlemleri MFA ve denetim kayıtlarıyla korundu; PDF/XML yerel arşiv bütünlük kontrolü eklendi.
9. Oturum çerezi `Secure`, `HttpOnly`, `SameSite=Lax` yapıldı; CSP, HSTS ve diğer tarayıcı güvenlik başlıkları eklendi.
10. Docker, Apache, PHP, dosya izinleri ve public document-root canlı kullanım için sertleştirildi.
11. Şifreli veritabanı + fatura yedeği ve şifre çözme/bütünlük doğrulama komutları eklendi.
12. CI üzerinde PHP/test, gizli bilgi taraması ve yüksek/kritik zafiyet taraması zorunlu hale getirildi.

## Canlı dağıtımdan önce sırayla tamamlanacak dış işlemler

1. NES panelinden sohbet veya başka kanallarda paylaşılmış API anahtarlarını iptal et; yeni canlı anahtarı uygulamadaki NES ayarına kaydet.
2. Bilinen kurulum `admin` parolasını en az 16 karakterlik benzersiz bir parola ile değiştir veya yeni kurucu hesabı oluşturup eski hesabı kapat.
3. Canlı veritabanı kullanıcısına en az 16 karakterlik benzersiz parola ver ve sunucudaki `.env` ile aynı anda güncelle.
4. Kurucu, sistem yöneticisi ve muhasebe hesaplarında `/panel/guvenlik/mfa` üzerinden MFA kur; kurtarma kodlarını çevrimdışı sakla.
5. Uygulama sunucusu dışında şifreli bir `BACKUP_DIR` bağla ve en az 24 karakterlik `BACKUP_ENCRYPTION_KEY` değerini parola kasasından cron görevine ver.
6. `storage/faturalar` biriminin sağlayıcı/disk düzeyinde beklemede şifreli olduğunu doğrula.
7. Bir şifreli yedek al, `bin/verify-encrypted-backup.sh` ile doğrula ve izole test ortamında gerçek geri yükleme provası yap.
8. Başarılı doğrulamalardan sonra sunucuda `MFA_ENFORCE=true`, `KEY_ROTATION_CONFIRMED=true`, `BACKUP_RESTORE_TESTED=true` ve `STORAGE_ENCRYPTION_CONFIRMED=true` ayarla.
9. `php bin/security-migrate.php` ve ardından `php bin/security-check.php` çalıştır; hata sayısı sıfır değilse dağıtımı durdur.
10. GitHub production environment onayıyla dağıt; giriş, MFA, yetkisiz URL, kurum izolasyonu, tahsilat ve fatura taslak/onay/iptal/PDF akışlarını canlıda kontrollü test et.
11. İlk 24 saat 401/403/419/429/5xx oranlarını, NES hatalarını, disk doluluğunu ve yedek cron sonucunu izle.

## Son denetim sonucu

- Başarılı kontrol: 25
- Uyarı: 0
- Engelleyici hata: 8
- Kod sözdizimi, Composer güvenlik testleri ve dağıtım YAML doğrulaması: başarılı
- Canlı dağıtım durumu: engelli; yukarıdaki dış işlemler tamamlanmalı
