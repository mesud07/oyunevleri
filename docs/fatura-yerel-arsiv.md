# Yerel fatura arşivi

Kesinleşen NES faturalarının PDF ve XML kopyaları aşağıdaki yapıda saklanır:

`storage/faturalar/{kurum_id}/{yil}/{ettn}.pdf`

`storage/faturalar/{kurum_id}/{yil}/{ettn}.xml`

Dosya yolu, boyutu ve SHA-256 özeti `fatura_arsiv_dosyalari` tablosunda tutulur. Panel belgeyi açarken önce özeti doğrulanmış yerel kopyayı kullanır; kopya yoksa NES'ten indirip arşivler.

Bekleyen belgeleri düzenli tamamlamak için şu komut cron ile çalıştırılmalıdır:

```cron
*/10 * * * * /usr/bin/php /uygulama/cron/nes-fatura-arsivleme.php
```

NES kesintilerine karşı yerel arşiv yeterlidir. Sunucu veya disk kaybına karşı ise veritabanı ile `storage/faturalar/` klasörü aynı yedekleme planına dahil edilmelidir.
