<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$veliler = [];
$ogrenciler = [];
$seanslar = [];
$rezervasyonlar = [];
$iptal_kural_saat = (int) sistem_ayar_get('iptal_kural_saat', $kurum_id, 48);

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, ad_soyad, telefon FROM veliler WHERE kurum_id = :kurum_id ORDER BY ad_soyad");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $veliler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT id, veli_id, ad_soyad, dogum_tarihi FROM ogrenciler WHERE kurum_id = :kurum_id ORDER BY ad_soyad");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $ogrenciler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT s.id, s.seans_baslangic, s.seans_bitis, s.kontenjan, g.grup_adi,
            (SELECT COUNT(*) FROM rezervasyonlar r WHERE r.seans_id = s.id AND r.kurum_id = :kurum_id AND r.durum = 'onayli') AS dolu
        FROM seanslar s
        INNER JOIN oyun_gruplari g ON g.id = s.grup_id
        WHERE s.kurum_id = :kurum_id
          AND s.seans_baslangic >= NOW()
        ORDER BY s.seans_baslangic ASC
        LIMIT 100");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $seanslar = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT r.id, r.durum, r.iptal_onay, r.islem_tarihi, s.seans_baslangic, g.grup_adi,
            a.alan_adi,
            o.ad_soyad AS ogrenci_adi, v.ad_soyad AS veli_adi
        FROM rezervasyonlar r
        INNER JOIN seanslar s ON s.id = r.seans_id
        INNER JOIN oyun_gruplari g ON g.id = s.grup_id
        LEFT JOIN kurum_alanlari a ON a.id = g.alan_id
        INNER JOIN ogrenciler o ON o.id = r.ogrenci_id
        INNER JOIN veliler v ON v.id = o.veli_id
        WHERE r.kurum_id = :kurum_id
        ORDER BY r.islem_tarihi DESC
        LIMIT 50");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $rezervasyonlar = $stmt->fetchAll();
}
?>

<div id="content" class="main-content">
    <div class="layout-px-spacing">
        <div class="middle-content container-xxl p-0">
            <div class="secondary-nav">
                <div class="breadcrumbs-container">
                    <header class="header navbar navbar-expand-sm">
                        <div class="d-flex breadcrumb-content">
                            <div class="page-header">
                                <div class="page-title">
                                    <h3>Rezervasyon Yönetimi</h3>
                                </div>
                            </div>
                        </div>
                    </header>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">İptal Kuralı (Saat)</h6>
                                    <small class="text-muted">Varsayılan: 48 saat</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="number" min="1" class="form-control" id="iptal_kural_saat" value="<?php echo (int) $iptal_kural_saat; ?>" style="max-width: 120px;">
                                    <button class="btn btn-primary" id="iptalKuralKaydetBtn">Kaydet</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Rezervasyonlar</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rezervasyonModal">Yeni Rezervasyon</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Veli</th>
                                            <th>Grup</th>
                                            <th>Alan</th>
                                            <th>Seans</th>
                                            <th>Durum</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($rezervasyonlar)) { ?>
                                        <tr>
                                            <td>Rezervasyon bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($rezervasyonlar as $rez) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rez['ogrenci_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($rez['veli_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($rez['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($rez['alan_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($rez['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <?php
                                                        $durum = $rez['durum'] ?? '';
                                                        $iptal_onay = (int) ($rez['iptal_onay'] ?? 0);
                                                        if ($durum === 'onayli') {
                                                            echo '<span class="badge badge-success">Onaylandı</span>';
                                                        } elseif ($durum === 'iptal') {
                                                            echo $iptal_onay === 0
                                                                ? '<span class="badge badge-warning">Bekleyen</span>'
                                                                : '<span class="badge badge-secondary">İptal Onaylandı</span>';
                                                        } elseif ($durum === 'hak_yandi') {
                                                            echo '<span class="badge badge-danger">Hak Yandı</span>';
                                                        } else {
                                                            echo htmlspecialchars($durum, ENT_QUOTES, 'UTF-8');
                                                        }
                                                    ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($rez['durum'] === 'onayli') { ?>
                                                        <button class="btn btn-sm btn-outline-danger rezervasyon-iptal" data-id="<?php echo (int) $rez['id']; ?>">İptal</button>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="modules/rezervasyon/iptal_listesi.php" class="btn btn-outline-secondary">Bekleyen İptaller</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="rezervasyonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Rezervasyon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rezervasyonForm">
                    <input type="hidden" name="islem" value="rezervasyon_yap">
                    <div class="mb-3">
                        <label class="form-label">Veli</label>
                        <select class="form-select" id="veli_id" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($veliler as $veli) { ?>
                                <option value="<?php echo (int) $veli['id']; ?>"><?php echo htmlspecialchars($veli['ad_soyad'] . ' (' . ($veli['telefon'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Öğrenci</label>
                        <select class="form-select" name="ogrenci_id" id="ogrenci_id" required>
                            <option value="">Önce veli seçiniz</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seans</label>
                        <select class="form-select" name="seans_id[]" id="seans_id" multiple required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($seanslar as $seans) { ?>
                                <option value="<?php echo (int) $seans['id']; ?>">
                                    <?php echo htmlspecialchars($seans['grup_adi'] . ' - ' . date('d.m H:i', strtotime($seans['seans_baslangic'])) . ' (Dolu: ' . (int) $seans['dolu'] . '/' . (int) $seans['kontenjan'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="form-text">Birden fazla seans seçmek için Ctrl/Command tuşunu kullanabilirsiniz.</div>
                        <div class="form-text" id="seans_count">Seçili seans sayısı: 0</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="rezervasyonKaydetBtn">Rezervasyon Yap</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (!window.jQuery) {
        console.error('jQuery yuklenemedi.');
        return;
    }
    var $ = window.jQuery;
    function ogrenciListeGuncelle() {
        var veliId = $('#veli_id').val();
        var seansVal = $('#seans_id').val();
        var seansId = Array.isArray(seansVal) ? (seansVal[0] || '') : seansVal;
        if (!veliId) {
            $('#ogrenci_id').html('<option value=\"\">Önce veli seçiniz</option>');
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'ogrenci_liste', veli_id: veliId, seans_id: seansId },
            success: function (res) {
                if (!res || res.durum !== 'ok') {
                    $('#ogrenci_id').html('<option value=\"\">Öğrenci bulunamadı</option>');
                    return;
                }
                var options = '<option value=\"\">Seçiniz</option>';
                var ogrenciler = res.ogrenciler || [];
                if (ogrenciler.length === 0) {
                    options = '<option value=\"\">Öğrenci bulunamadı</option>';
                } else {
                    ogrenciler.forEach(function (ogr) {
                        options += '<option value=\"' + ogr.id + '\">' + ogr.ad_soyad + '</option>';
                    });
                }
                $('#ogrenci_id').html(options);
            }
        });
    }

    $('#veli_id').on('change', ogrenciListeGuncelle);
    $('#seans_id').on('change', ogrenciListeGuncelle);
    $('#seans_id').on('change', function () {
        var count = ($('#seans_id').val() || []).length;
        $('#seans_count').text('Seçili seans sayısı: ' + count);
    });

    $('#rezervasyonKaydetBtn').on('click', function () {
        var seansIds = $('#seans_id').val() || [];
        var formData = {
            islem: 'rezervasyon_yap',
            ogrenci_id: $('#ogrenci_id').val(),
            seans_id: seansIds
        };
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: formData,
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Rezervasyon kaydedilemedi.');
                }
            }
        });
    });

    $('#iptalKuralKaydetBtn').on('click', function () {
        var saat = parseInt($('#iptal_kural_saat').val() || '0', 10);
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'iptal_kural_kaydet', iptal_kural_saat: saat },
            success: function (res) {
                if (res.durum === 'ok') {
                    alert('İptal kuralı güncellendi.');
                } else {
                    alert(res.mesaj || 'İptal kuralı güncellenemedi.');
                }
            }
        });
    });

    function rezervasyonIptal(rezId, onay) {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'rezervasyon_iptal', rezervasyon_id: rezId, onay: onay ? 1 : 0 },
            success: function (res) {
                if (res.durum === 'uyari') {
                    if (confirm(res.mesaj || 'Hakkınız yanacaktır. Devam etmek istiyor musunuz?')) {
                        rezervasyonIptal(rezId, true);
                    }
                    return;
                }
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Rezervasyon iptal edilemedi.');
                }
            }
        });
    }

    $('.rezervasyon-iptal').on('click', function () {
        var rezId = $(this).data('id');
        if (!confirm('Rezervasyon iptal edilsin mi?')) {
            return;
        }
        rezervasyonIptal(rezId, false);
    });

    $('#rezervasyonModal').on('hidden.bs.modal', function () {
        $('#rezervasyonForm')[0].reset();
        $('#ogrenci_id').html('<option value="">Önce veli seçiniz</option>');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
