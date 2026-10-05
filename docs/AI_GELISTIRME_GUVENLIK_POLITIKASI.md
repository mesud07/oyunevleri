# AI destekli geliştirme güvenlik politikası

AI tarafından üretilmiş kod, insan incelemesi ve otomatik kontroller tamamlanmadan canlıya alınmaz.

1. API anahtarı, gerçek parola, kişisel veri veya üretim verisi AI sohbetlerine ve commitlere yazılmaz. Yanlışlıkla paylaşılırsa sır kabul edilmez ve hemen yenilenir.
2. Her değişiklik en az bir geliştirici tarafından yetkilendirme, kurum izolasyonu, SQL enjeksiyonu, XSS, CSRF, dosya erişimi ve hata sızıntısı açısından incelenir.
3. Yeni endpoint için kimlik doğrulama, gereken en düşük yetki, CSRF ve hız sınırı açıkça belirlenir.
4. Finansal ve geri döndürülemez işlemler kullanıcı onayı, MFA step-up, idempotency ve denetim kaydı olmadan çalışmaz.
5. AI önerisiyle yeni paket eklenirse resmi kaynak, lisans, güncellik ve bilinen zafiyetler doğrulanır.
6. CI sözdizimi/test, secret taraması ve yüksek/kritik zafiyet taramasını geçmeden dağıtım yapılamaz.
7. Üretim ayarları kaynak koddan ayrıdır; `.env`, anahtarlar, yedek anahtarı ve sertifikalar parola kasasında tutulur.
8. Her yayın için geri dönüş adımı, migrasyon etkisi, yedek ve temel fonksiyon testleri kayıt altına alınır.
