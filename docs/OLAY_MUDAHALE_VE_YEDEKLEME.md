# Olay müdahale ve yedekleme prosedürü

## Şüpheli erişimde ilk işlemler

1. Etkilenen kullanıcıyı pasife al veya parolasını değiştir; oturum sürümü sayesinde açık oturumları kapanır.
2. NES, SMS, veritabanı, GitHub/deploy ve sunucu anahtarlarını iptal edip yenile.
3. Uygulamayı gerekiyorsa bakım moduna al; logları silmeden salt okunur kopyala.
4. Olayın başlangıç/bitiş zamanını, etkilenen kullanıcıları ve görüntülenen/değiştirilen veriyi belirle.
5. Temiz bir sistemde yedek bütünlüğünü SHA-256 ile doğrula ve geri yükleme provası yap.
6. KVKK kapsamında veri ihlali ihtimali varsa hukuk/KVKK sorumlusunu derhal bilgilendir; Kurul ve ilgili kişilere bildirim süresini somut olaya göre takip et.
7. Kök neden kapatılmadan sistemi yeniden açma; açıldıktan sonra MFA ve erişim kayıtlarını doğrula.

## Şifreli yedek

Gerekli ortam değişkenleri: `BACKUP_DIR` ve en az 24 karakterlik `BACKUP_ENCRYPTION_KEY`. Ardından:

```sh
./bin/encrypted-backup.sh
```

Komut veritabanı dökümünü ve yerel fatura arşivini tek pakette AES-256 ile şifreler, ayrıca SHA-256 dosyası üretir. Anahtarı yedekle aynı yerde tutmayın.

Canlı sunucudaki `storage/faturalar` birimi ayrıca sağlayıcı/disk seviyesinde beklemede şifrelenmiş olmalıdır. Bu kontrol doğrulandıktan sonra `STORAGE_ENCRYPTION_CONFIRMED=true` ayarlanır.

## Geri yükleme provası

1. Yedeğin bütünlüğünü ve şifre çözümünü veri tabanına dokunmadan doğrula:

   ```sh
   BACKUP_ENCRYPTION_KEY='parola-kasasindan-alinan-deger' ./bin/verify-encrypted-backup.sh /mutlak/yol/yedek.tar.enc
   ```

2. Üretimden izole, boş bir test veritabanı ve boş depolama dizini hazırla.
3. Yedeği yalnız bellekten/parola kasasından alınan anahtarla çöz.
4. SQL dökümünü test veritabanına yükle, fatura arşivini test depolamasına çıkar.
5. Rastgele seçilmiş faturaların PDF/XML dosyalarını ve kayıt eşleşmesini kontrol et.
6. Sonucu, süreyi, testi yapan kişiyi ve yedek kimliğini kayıt altına al; açık düz metin arşivi güvenli biçimde yok et.
7. Başarılı provadan sonra `BACKUP_RESTORE_TESTED=true` ayarla; her provada tarih ve sorumluyu ayrıca kayıt altına al.

## Log ve kişisel veri ilkeleri

- API anahtarı, parola, OTP, oturum çerezi, tam TCKN/VKN veya kart verisi loglanmaz.
- Uygulama logları dışarıdan erişilemez ve sadece yetkili sistem kullanıcısı tarafından okunur.
- Denetim kayıtlarının saklama süresi iş/hukuk gereksinimine göre belgelenir; süresi dolan kayıtlar kontrollü silinir.
