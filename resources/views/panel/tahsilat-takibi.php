<?php
$yaklasanTahsilatlar = $yaklasanTahsilatlar ?? [];
$gecikmisTahsilatlar = $gecikmisTahsilatlar ?? [];
?>

<section class="page-head">
    <div>
        <h1>Yaklasan ve Gecikmis Tahsilatlar</h1>
        <p>Ilk ders odeme vadesine gore alinmasi beklenen paket tahsilatlari.</p>
    </div>
    <a class="btn btn-primary" href="/panel/odemeler/tahsilatlar">Tahsilat Listesi</a>
</section>

<section class="report-grid">
    <article class="panel-card report-panel">
        <h2>Yaklasan Tahsilatlar</h2>
        <p class="report-helper">Ilk ders tarihi onumuzdeki 7 gun icinde olan ve pakette kalan borcu bulunan ogrenciler.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Odeme Vadesi</th>
                        <th>Ogrenci</th>
                        <th>Son Paket</th>
                        <th>Kayit Yenileme</th>
                        <th>Kalan Ders</th>
                        <th>Telafi</th>
                        <th>Paket Tutari</th>
                        <th>Odenen</th>
                        <th>Beklenen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$yaklasanTahsilatlar) : ?><tr><td colspan="9">Yaklasan tahsilat bulunamadi.</td></tr><?php endif; ?>
                    <?php foreach ($yaklasanTahsilatlar as $row) : ?>
                        <tr>
                            <td><?= e(tarih_goster($row['odeme_vade_tarihi'])) ?></td>
                            <td><?= e($row['ogrenci']) ?></td>
                            <td><?= e($row['paket_adi']) ?></td>
                            <td><?= e(tarih_goster($row['kayit_yenileme_tarihi'])) ?></td>
                            <td><?= e($row['kalan_ders']) ?></td>
                            <td><?= e($row['kalan_telafi']) ?></td>
                            <td><?= e(para_goster($row['paket_tutari'])) ?></td>
                            <td><?= e(para_goster($row['mevcut_tahsilat'])) ?></td>
                            <td><strong><?= e(para_goster($row['beklenen_tahsilat'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel-card report-panel">
        <h2>Gecikmis Tahsilatlar</h2>
        <p class="report-helper">Ilk ders tarihi gecmis ve pakette kalan borcu bulunan ogrenciler. Odeme ilk dersten once alinmalidir.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Odeme Vadesi</th>
                        <th>Ogrenci</th>
                        <th>Son Paket</th>
                        <th>Kayit Yenileme</th>
                        <th>Kalan Ders</th>
                        <th>Telafi</th>
                        <th>Paket Tutari</th>
                        <th>Odenen</th>
                        <th>Beklenen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$gecikmisTahsilatlar) : ?><tr><td colspan="9">Gecikmis tahsilat bulunamadi.</td></tr><?php endif; ?>
                    <?php foreach ($gecikmisTahsilatlar as $row) : ?>
                        <tr>
                            <td><?= e(tarih_goster($row['odeme_vade_tarihi'])) ?></td>
                            <td><?= e($row['ogrenci']) ?></td>
                            <td><?= e($row['paket_adi']) ?></td>
                            <td><?= e(tarih_goster($row['kayit_yenileme_tarihi'])) ?></td>
                            <td><?= e($row['kalan_ders']) ?></td>
                            <td><?= e($row['kalan_telafi']) ?></td>
                            <td><?= e(para_goster($row['paket_tutari'])) ?></td>
                            <td><?= e(para_goster($row['mevcut_tahsilat'])) ?></td>
                            <td><strong><?= e(para_goster($row['beklenen_tahsilat'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
