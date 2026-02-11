<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$subeler = [];
$ogrenciler = [];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT * FROM subeler WHERE kurum_id = :kurum_id ORDER BY sube_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $subeler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT o.id, o.ad_soyad, o.dogum_tarihi, o.saglik_notlari,
            v.id AS veli_id, v.ad_soyad AS veli_adi, v.telefon, v.eposta, v.sube_id,
            s.sube_adi
        FROM ogrenciler o
        INNER JOIN veliler v ON v.id = o.veli_id
        LEFT JOIN subeler s ON s.id = v.sube_id
        WHERE o.kurum_id = :kurum_id
        ORDER BY o.id DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $ogrenciler = $stmt->fetchAll();
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
                                    <h3>Öğrenci Listesi</h3>
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
                                    <h6 class="value">Öğrenciler</h6>
                                </div>
                                <button class="btn btn-primary" id="veliOgrenciYeniBtn">Yeni Veli & Öğrenci</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Öğrenci</th>
                                            <th>Veli</th>
                                            <th>Telefon</th>
                                            <th>Şube</th>
                                            <th>Doğum Tarihi</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($ogrenciler)) { ?>
                                        <tr>
                                            <td>Öğrenci bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($ogrenciler as $ogr) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['veli_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['telefon'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($ogr['dogum_tarihi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <?php
                                                        $telefon_raw = (string) ($ogr['telefon'] ?? '');
                                                        $telefon_digits = preg_replace('/\D+/', '', $telefon_raw);
                                                        if (strlen($telefon_digits) === 11 && strpos($telefon_digits, '0') === 0) {
                                                            $telefon_digits = '90' . substr($telefon_digits, 1);
                                                        } elseif (strlen($telefon_digits) === 10) {
                                                            $telefon_digits = '90' . $telefon_digits;
                                                        }
                                                        $whats_link = $telefon_digits !== '' ? 'https://wa.me/' . $telefon_digits : '';
                                                    ?>
                                                    <button class="btn btn-sm btn-outline-primary veli-ogrenci-duzenle"
                                                        data-ogrenci-id="<?php echo (int) $ogr['id']; ?>"
                                                        data-ogrenci-ad="<?php echo htmlspecialchars($ogr['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-dogum="<?php echo htmlspecialchars($ogr['dogum_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-saglik="<?php echo htmlspecialchars($ogr['saglik_notlari'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-id="<?php echo (int) $ogr['veli_id']; ?>"
                                                        data-veli-ad="<?php echo htmlspecialchars($ogr['veli_adi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-telefon="<?php echo htmlspecialchars($ogr['telefon'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-veli-eposta="<?php echo htmlspecialchars($ogr['eposta'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-sube-id="<?php echo (int) ($ogr['sube_id'] ?? 0); ?>">
                                                        Veli/Öğrenci
                                                    </button>
                                                    <?php if ($whats_link !== '') { ?>
                                                        <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars($whats_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">WhatsApp</a>
                                                    <?php } else { ?>
                                                        <button class="btn btn-sm btn-outline-success" type="button" disabled>WhatsApp</button>
                                                    <?php } ?>
                                                    <button class="btn btn-sm btn-outline-danger veli-ogrenci-cikar"
                                                        data-ogrenci-id="<?php echo (int) $ogr['id']; ?>">
                                                        Kurumdan Çıkar
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="veliOgrenciModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Veli & Öğrenci Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="veliOgrenciForm">
                    <input type="hidden" name="islem" value="veli_ogrenci_kaydet">
                    <input type="hidden" name="veli_id" id="veli_id" value="0">
                    <input type="hidden" name="ogrenci_id" id="ogrenci_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Şube</label>
                        <select class="form-select" name="sube_id" id="veli_sube">
                            <option value="">Seçiniz</option>
                            <?php foreach ($subeler as $sube) { ?>
                                <option value="<?php echo (int) $sube['id']; ?>"><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Veli Ad Soyad</label>
                        <input type="text" class="form-control" name="veli_ad" id="veli_ad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Veli Telefon</label>
                        <input type="text" class="form-control" name="veli_telefon" id="veli_telefon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Veli E-posta</label>
                        <input type="email" class="form-control" name="veli_eposta" id="veli_eposta">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Öğrenci Ad Soyad</label>
                        <input type="text" class="form-control" name="ogrenci_ad" id="ogrenci_ad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Doğum Tarihi</label>
                        <input type="date" class="form-control" name="dogum_tarihi" id="ogrenci_dogum">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sağlık Notları</label>
                        <textarea class="form-control" name="saglik_notlari" id="ogrenci_saglik" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="veliOgrenciKaydetBtn">Kaydet</button>
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

    $('#veliOgrenciYeniBtn').on('click', function () {
        $('#veliOgrenciForm')[0].reset();
        $('#veli_id').val('0');
        $('#ogrenci_id').val('0');
        $('#veliOgrenciModal').modal('show');
    });

    $('.veli-ogrenci-duzenle').on('click', function () {
        $('#veli_id').val($(this).data('veli-id'));
        $('#ogrenci_id').val($(this).data('ogrenci-id'));
        $('#veli_sube').val($(this).data('sube-id'));
        $('#veli_ad').val($(this).data('veli-ad'));
        $('#veli_telefon').val($(this).data('veli-telefon'));
        $('#veli_eposta').val($(this).data('veli-eposta'));
        $('#ogrenci_ad').val($(this).data('ogrenci-ad'));
        $('#ogrenci_dogum').val($(this).data('dogum'));
        $('#ogrenci_saglik').val($(this).data('saglik'));
        $('#veliOgrenciModal').modal('show');
    });

    $('#veliOgrenciKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#veliOgrenciForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Veli/ogrenci kaydedilemedi.');
                }
            }
        });
    });

    $('.veli-ogrenci-cikar').on('click', function () {
        var ogrenciId = $(this).data('ogrenci-id');
        if (!ogrenciId) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'veli_ogrenci_cikar', ogrenci_id: ogrenciId },
            success: function (res) {
                if (res.durum === 'uyari') {
                    if (confirm(res.mesaj || 'Hak veya borç var. Yine de çıkarmak istiyor musunuz?')) {
                        $.ajax({
                            url: 'ajax.php',
                            type: 'POST',
                            data: { islem: 'veli_ogrenci_cikar', ogrenci_id: ogrenciId, onay: 1 },
                            success: function (res2) {
                                if (res2.durum === 'ok') {
                                    location.reload();
                                } else {
                                    alert(res2.mesaj || 'İşlem tamamlanamadı.');
                                }
                            }
                        });
                    }
                    return;
                }
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'İşlem tamamlanamadı.');
                }
            }
        });
    });

    $('#veliOgrenciModal').on('hidden.bs.modal', function () {
        $('#veliOgrenciForm')[0].reset();
        $('#veli_id').val('0');
        $('#ogrenci_id').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
