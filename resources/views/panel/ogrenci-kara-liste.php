<?php
$kayitlar = $kayitlar ?? [];
$kategoriler = $kategoriler ?? [];
$tablolarHazir = (bool) ($tablolarHazir ?? false);
$kategoriEtiketi = static fn(string $kategori): string => $kategoriler[$kategori] ?? $kategori;
?>

<section class="page-head">
    <div>
        <h1>Öğrenci Tedbir Listesi</h1>
        <p>Tanisma dersi, habersiz gelmeme ve benzeri takip edilmesi gereken kayitlar.</p>
    </div>
    <a class="btn btn-primary" href="/panel/ogrenciler">Ogrenci Ara</a>
</section>

<section class="panel-card report-panel">
    <?php if (!$tablolarHazir) : ?>
        <div class="info-box compact-info">
            <strong>Migration gerekli</strong>
            <p>Tedbir listesi tablosu henüz veritabanında yok. Migration çalıştırıldıktan sonra kayıt eklenebilir.</p>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ogrenci</th>
                    <th>Kategori</th>
                    <th>Sebep</th>
                    <th>Durum</th>
                    <th>Kaydeden</th>
                    <th>Tarih</th>
                    <th>Islem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$kayitlar) : ?>
                    <tr><td colspan="7">Tedbir listesi kaydı bulunamadı.</td></tr>
                <?php endif; ?>
                <?php foreach ($kayitlar as $kayit) : ?>
                    <tr>
                        <td><a class="table-link" href="/panel/ogrenciler/profil?id=<?= e($kayit['ogrenci_id']) ?>"><?= e($kayit['ogrenci'] ?? '-') ?></a></td>
                        <td><span class="status-pill is-danger"><?= e($kategoriEtiketi((string) ($kayit['kategori'] ?? ''))) ?></span></td>
                        <td><?= nl2br(e($kayit['sebep'] ?? '')) ?></td>
                        <td><?= ((int) ($kayit['aktif'] ?? 0) === 1) ? 'Aktif' : 'Kaldirildi' ?></td>
                        <td><?= e($kayit['kaydeden'] ?? '-') ?></td>
                        <td><?= e(tarih_goster($kayit['olusturulma_tarihi'] ?? null)) ?></td>
                        <td>
                            <?php if ((int) ($kayit['aktif'] ?? 0) === 1) : ?>
                                <button class="btn btn-danger" type="button" data-blacklist-remove="<?= e($kayit['id']) ?>">Kaldir</button>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
