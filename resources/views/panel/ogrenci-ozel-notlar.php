<?php
$kayitlar = $kayitlar ?? [];
$arama = (string) ($arama ?? '');
$durum = (string) ($durum ?? 'aktif');
$aktifSayisi = count(array_filter($kayitlar, static fn(array $kayit): bool => ($kayit['durum'] ?? '') === 'aktif'));
?>

<section class="page-head">
    <div>
        <h1>Öğrenci Özel Notları</h1>
        <p>Önemli öğrenci isteklerini ve takip edilmesi gereken kısa notları tek ekrandan görüntüleyin.</p>
    </div>
    <a class="btn btn-primary" href="/panel/ogrenciler">Öğrenci Ara</a>
</section>

<section class="special-notes-summary" aria-label="Özel not özeti">
    <article>
        <span>Listelenen Not</span>
        <strong><?= e((string) count($kayitlar)) ?></strong>
    </article>
    <article>
        <span>Aktif Öğrenci</span>
        <strong><?= e((string) $aktifSayisi) ?></strong>
    </article>
</section>

<section class="panel-card special-notes-panel">
    <form class="special-notes-filters" method="get" action="/panel/ogrenciler/ozel-notlar">
        <label>
            <span>Öğrenci veya not ara</span>
            <input type="search" name="arama" value="<?= e($arama) ?>" placeholder="Örn. haftada 2 veya öğrenci adı">
        </label>
        <label>
            <span>Öğrenci durumu</span>
            <select name="durum">
                <option value="aktif" <?= $durum === 'aktif' ? 'selected' : '' ?>>Aktif öğrenciler</option>
                <option value="pasif" <?= $durum === 'pasif' ? 'selected' : '' ?>>Pasif öğrenciler</option>
                <option value="tumu" <?= $durum === 'tumu' ? 'selected' : '' ?>>Tümü</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Filtrele</button>
        <?php if ($arama !== '' || $durum !== 'aktif') : ?>
            <a class="btn btn-ghost" href="/panel/ogrenciler/ozel-notlar">Temizle</a>
        <?php endif; ?>
    </form>

    <?php if (!$kayitlar) : ?>
        <div class="empty-state special-notes-empty">
            <strong>Özel not bulunamadı.</strong>
            <span>Seçili filtrelerde kayıt yok veya henüz öğrenci profillerine özel not eklenmemiş.</span>
        </div>
    <?php else : ?>
        <div class="special-notes-list">
            <?php foreach ($kayitlar as $kayit) : ?>
                <?php $adSoyad = trim(($kayit['ad'] ?? '') . ' ' . ($kayit['soyad'] ?? '')); ?>
                <article class="special-note-card">
                    <div class="special-note-student">
                        <span class="special-note-avatar"><?= e(mb_substr((string) ($kayit['ad'] ?? 'Ö'), 0, 1)) ?></span>
                        <div>
                            <a href="/panel/ogrenciler/profil?id=<?= e($kayit['id'] ?? '') ?>"><?= e($adSoyad) ?></a>
                            <span class="status-pill<?= ($kayit['durum'] ?? '') === 'aktif' ? '' : ' is-danger' ?>"><?= e($kayit['durum'] ?? '-') ?></span>
                        </div>
                    </div>
                    <p><?= nl2br(e($kayit['profil_ozel_notu'] ?? '')) ?></p>
                    <a class="btn btn-ghost" href="/panel/ogrenciler/profil?id=<?= e($kayit['id'] ?? '') ?>">Profili Aç</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
