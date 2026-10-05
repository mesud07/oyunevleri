# Mysoft e-Belge entegrasyonu

Entegrasyon `swagger.json` içindeki Mysoft.EDocumentApi v8 modellerine göre hazırlanmıştır. İlk kapsam e-Fatura, e-Arşiv, mükellef sorgusu, durum, PDF/XML, e-Arşiv iptali ve giden fatura senkronizasyonudur.

## Kurulum

1. `database/migrations/20260818_mysoft_fatura_entegrasyonu.sql` ve `database/migrations/20260818_hizmet_paket_kdv.sql` migration'larını çalıştırın.
2. `.env` içinde güçlü ve değişmeyecek bir `APP_KEY` tanımlayın. Bu anahtar kurumların Mysoft secret bilgilerini ve kısa ömürlü token cache'ini AES-256-GCM ile korur.
3. Test ortamı için `MYSOFT_BASE_URL=https://edocumentapi.mytest.tr` kullanın.
4. Panelde **Finans → Mysoft Ayarları** ekranından kurum bağlantısını kaydedip bağlantı testini çalıştırın.

Her kurumun bağlantı bilgileri ayrıdır. Veritabanında client secret düz metin olarak saklanmaz. `tenantIdentifierNumber` faturayı kesen kurumu, `invoiceAccount.vknTckn` ise alıcıyı ifade eder.

## Manuel akış

**Finans → Tahsilatlar** ekranındaki **Fatura Kes** işlemi fatura profilini kaydeder, GİB hesabını sorgular, belge türünü otomatik belirler ve `POST /api/InvoiceOutbox/invoiceOutbox` çağrısını yapar. Bir tahsilata yalnızca bir fatura kaydı bağlanabilir. ETTN gönderimden önce üretilir ve `referanceKey` alanında yerel tahsilat referansı da gönderilir.

Tahsilat tutarı KDV dahil toplam kabul edilir. KDV oranı hizmet tanımında tutulur ve paket oluşturulurken pakete kopyalanır; böylece hizmet oranı sonradan değişse bile geçmiş paket korunur. Eski paketlerde oran boşsa fatura penceresi oranı manuel kabul eder. Kaynak kodda sabit oran yoktur ve hesaplama kuruş cinsinden tamsayılarla yapılır.

## Senkronizasyon

Manuel senkronizasyon Faturalar ekranındadır. Liste Swagger'daki `afterValue` ile sayfalanır; yeni Mysoft faturaları içeri alınırken mevcut ETTN kayıtlarının durumları da güncellenir. Periyodik işlem için:

```bash
php cron/mysoft-fatura-senkronizasyonu.php
```

Cron aktif kurumları ayrı ayrı işler ve kesinleşmemiş faturaların durumunu günceller.

## Swagger kaynaklı doğrulama notları

- OAuth token yolu Swagger açıklamasında `/oauth/token` olarak verilmiş, OpenAPI `paths` listesinde yer almamaktadır.
- PDF/XML cevapları `StringResultModel.data` olarak tanımlanmış ve endpoint açıklaması ZIP demektedir; uygulama Base64 ZIP imzasını doğrulamadan dosyayı saklamaz.
- `getInvoiceOutboxList` cevabı `string[]` olarak tanımlıdır. Senkronizasyon yalnızca doğrudan UUID veya içinde Swagger'daki `invoiceETTN` alanı bulunan JSON string kayıtlarını işler; tanımsız bir formatı tahmin etmez.
- `cancelEArchiveInvoice` yalnızca e-Arşiv için kullanılır. Belirsiz e-Fatura iptal/red akışı otomatikleştirilmemiştir.
- Bağlantı testi firma bilgisinin yanında e-Fatura/e-Arşiv kontörlerini, belge numaratörlerini ve yapılandırılan numaratör setini sorgular.
