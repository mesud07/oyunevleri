<?php
$yanit = (string) ($randevu['katilim_yaniti'] ?? '');
$yanitEtiket = [
    'katilacagim' => 'Katilacagim',
    'katilamayacagim' => 'Katilamayacagim',
];
?>
<main class="attendance-page">
    <section class="attendance-card">
        <div class="attendance-brand">
            <strong>Oyun Evleri Yönetim Sistemi</strong>
            <span>Randevu Katilim Durumu</span>
        </div>

        <?php if (!$randevu) : ?>
            <article class="attendance-notice is-error">
                <h1>Randevu bulunamadi</h1>
                <p>Bu link gecersiz olabilir veya randevu silinmis olabilir.</p>
            </article>
        <?php else : ?>
            <article class="attendance-hero">
                <h1>Sayin <?= e($randevu['ogrenci'] ?? 'Velimiz') ?></h1>
                <p>Randevu detaylarini goruntuleyebilir, derse katilim durumunuzu bize iletebilirsiniz.</p>
            </article>

            <article class="attendance-detail">
                <h2>Randevu Detaylari</h2>
                <div>
                    <span>Uzman</span>
                    <strong><?= e($randevu['uzman'] ?? '-') ?></strong>
                </div>
                <div>
                    <span>Randevu Zamani</span>
                    <strong><?= e(tarih_goster($randevu['tarih'] ?? '')) ?> - <?= e(substr((string) ($randevu['baslangic_saati'] ?? ''), 0, 5)) ?></strong>
                </div>
                <div>
                    <span>Poliklinik / Grup</span>
                    <strong><?= e($randevu['grup'] ?? '-') ?></strong>
                </div>
                <div>
                    <span>Hizmet</span>
                    <strong><?= e($randevu['paket_adi'] ?? $randevu['tur'] ?? '-') ?></strong>
                </div>
            </article>

            <article class="attendance-actions">
                <h2>Katilim Durumu</h2>
                <p>Lutfen randevuya katilim durumunuzu seciniz.</p>
                <?php if ($mesaj !== '') : ?>
                    <div class="attendance-notice"><?= e($mesaj) ?></div>
                <?php endif; ?>
                <?php if ($yanit !== '') : ?>
                    <div class="attendance-current">Mevcut yanit: <strong><?= e($yanitEtiket[$yanit] ?? $yanit) ?></strong></div>
                <?php endif; ?>
                <form method="post" action="/randevu-katilim">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <button class="attendance-button is-join" type="submit" name="yanit" value="katilacagim">Katilacagim</button>
                    <button class="attendance-button is-decline" type="submit" name="yanit" value="katilamayacagim">Katilamayacagim</button>
                </form>
            </article>
        <?php endif; ?>
    </section>
</main>
