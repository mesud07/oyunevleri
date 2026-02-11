<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$subeler = [];
$alanlar = [];
$gruplar = [];
$seanslar = [];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, sube_adi FROM subeler WHERE kurum_id = :kurum_id ORDER BY sube_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $subeler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT id, alan_adi FROM kurum_alanlari WHERE kurum_id = :kurum_id AND durum = 1 ORDER BY alan_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $alanlar = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT g.*, s.sube_adi, a.alan_adi
        FROM oyun_gruplari g
        LEFT JOIN subeler s ON s.id = g.sube_id
        LEFT JOIN kurum_alanlari a ON a.id = g.alan_id
        WHERE g.kurum_id = :kurum_id
        ORDER BY g.id DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $gruplar = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT s.*, g.grup_adi, g.sube_id, g.alan_id, sb.sube_adi, a.alan_adi,
            (SELECT COUNT(*) FROM rezervasyonlar r WHERE r.seans_id = s.id AND r.kurum_id = :kurum_id AND r.durum = 'onayli') AS dolu
        FROM seanslar s
        INNER JOIN oyun_gruplari g ON g.id = s.grup_id
        LEFT JOIN subeler sb ON sb.id = g.sube_id
        LEFT JOIN kurum_alanlari a ON a.id = g.alan_id
        WHERE s.kurum_id = :kurum_id
        ORDER BY s.seans_baslangic DESC
        LIMIT 50");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $seanslar = $stmt->fetchAll();
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
                                    <h3>Grup & Seans Yönetimi</h3>
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
                                    <h6 class="value">Oyun Grupları</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#grupModal">Yeni Grup</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Grup</th>
                                            <th>Şube</th>
                                            <th>Alan</th>
                                            <th>Yaş (Ay)</th>
                                            <th>Kapasite</th>
                                            <th>Tekrar</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($gruplar)) { ?>
                                        <tr>
                                            <td>Grup kaydı bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($gruplar as $grup) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($grup['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($grup['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($grup['alan_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(($grup['min_ay'] ?? '-') . ' - ' . ($grup['max_ay'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo (int) ($grup['kapasite'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($grup['tekrar_tipi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary grup-duzenle"
                                                        data-id="<?php echo (int) $grup['id']; ?>"
                                                        data-sube="<?php echo (int) ($grup['sube_id'] ?? 0); ?>"
                                                        data-alan="<?php echo (int) ($grup['alan_id'] ?? 0); ?>"
                                                        data-adi="<?php echo htmlspecialchars($grup['grup_adi'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-min="<?php echo (int) ($grup['min_ay'] ?? 0); ?>"
                                                        data-max="<?php echo (int) ($grup['max_ay'] ?? 0); ?>"
                                                        data-kapasite="<?php echo (int) ($grup['kapasite'] ?? 0); ?>"
                                                        data-tekrar="<?php echo htmlspecialchars($grup['tekrar_tipi'] ?? 'tekil', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-gunler="<?php echo htmlspecialchars($grup['tekrar_gunleri'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-baslangic="<?php echo htmlspecialchars($grup['baslangic_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-bitis="<?php echo htmlspecialchars($grup['bitis_tarihi'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-saat="<?php echo htmlspecialchars($grup['seans_baslangic_saati'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-sure="<?php echo htmlspecialchars($grup['seans_suresi_dk'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        Düzenle
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

            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Seanslar (Son 50)</h6>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#topluSeansModal">Toplu Seans</button>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#seansModal">Yeni Seans</button>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Grup</th>
                                            <th>Şube</th>
                                            <th>Alan</th>
                                            <th>Başlangıç</th>
                                            <th>Bitiş</th>
                                            <th>Dolu</th>
                                            <th>Kont.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($seanslar)) { ?>
                                        <tr>
                                            <td>Seans kaydı bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($seanslar as $seans) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($seans['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($seans['sube_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($seans['alan_adi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($seans['seans_baslangic'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($seans['seans_bitis'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo (int) ($seans['dolu'] ?? 0); ?></td>
                                                <td><?php echo (int) ($seans['kontenjan'] ?? 0); ?></td>
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

<div class="modal fade" id="grupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Grup Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="grupForm">
                    <input type="hidden" name="islem" value="grup_kaydet">
                    <input type="hidden" name="grup_id" id="grup_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Şube</label>
                        <select class="form-select" name="sube_id" id="grup_sube" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($subeler as $sube) { ?>
                                <option value="<?php echo (int) $sube['id']; ?>"><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alan</label>
                        <select class="form-select" name="alan_id" id="grup_alan">
                            <option value="">Seçiniz</option>
                            <?php foreach ($alanlar as $alan) { ?>
                                <option value="<?php echo (int) $alan['id']; ?>"><?php echo htmlspecialchars($alan['alan_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grup Adı</label>
                        <input type="text" class="form-control" name="grup_adi" id="grup_adi" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Min Ay</label>
                            <input type="number" class="form-control" name="min_ay" id="grup_min_ay">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Ay</label>
                            <input type="number" class="form-control" name="max_ay" id="grup_max_ay">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kapasite</label>
                        <input type="number" class="form-control" name="kapasite" id="grup_kapasite" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tekrar Tipi</label>
                        <select class="form-select" name="tekrar_tipi" id="grup_tekrar">
                            <option value="tekil">Tekil</option>
                            <option value="haftalik">Haftalık</option>
                        </select>
                    </div>
                    <div class="mb-3" id="tekrarGunleriWrap" style="display:none;">
                        <label class="form-label">Tekrar Günleri</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php $gunler = ['Pzt','Sal','Car','Per','Cum','Cmt','Paz']; foreach ($gunler as $gun) { ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tekrar_gunleri[]" value="<?php echo $gun; ?>" id="gun_<?php echo $gun; ?>">
                                    <label class="form-check-label" for="gun_<?php echo $gun; ?>"><?php echo $gun; ?></label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Başlangıç Saati</label>
                            <input type="time" class="form-control" name="seans_baslangic_saati" id="grup_saat">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Süresi (dk)</label>
                            <input type="number" class="form-control" name="seans_suresi_dk" id="grup_sure">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="baslangic_tarihi" id="grup_baslangic">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" name="bitis_tarihi" id="grup_bitis">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="grupKaydetBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="seansModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seans Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="seansForm">
                    <input type="hidden" name="islem" value="seans_kaydet">
                    <input type="hidden" name="seans_id" id="seans_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Grup</label>
                        <select class="form-select" name="grup_id" id="seans_grup" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($gruplar as $grup) { ?>
                                <option value="<?php echo (int) $grup['id']; ?>"><?php echo htmlspecialchars($grup['grup_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Başlangıç</label>
                        <input type="datetime-local" class="form-control" name="seans_baslangic" id="seans_baslangic" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bitiş</label>
                        <input type="datetime-local" class="form-control" name="seans_bitis" id="seans_bitis" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kontenjan</label>
                        <input type="number" class="form-control" name="kontenjan" id="seans_kontenjan" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="seansKaydetBtn">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="topluSeansModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Toplu Seans Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="topluSeansForm">
                    <input type="hidden" name="islem" value="seans_toplu_olustur">
                    <div class="mb-3">
                        <label class="form-label">Grup</label>
                        <select class="form-select" name="grup_id" id="toplu_grup" required>
                            <option value="">Seçiniz</option>
                            <?php foreach ($gruplar as $grup) { ?>
                                <option value="<?php echo (int) $grup['id']; ?>"
                                    data-sure="<?php echo (int) ($grup['seans_suresi_dk'] ?? 0); ?>"
                                    data-kapasite="<?php echo (int) ($grup['kapasite'] ?? 0); ?>">
                                    <?php echo htmlspecialchars($grup['grup_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Periyot</label>
                            <select class="form-select" name="periyot" id="toplu_periyot">
                                <option value="haftalik">Haftalık</option>
                                <option value="aylik">Aylık</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="haftaSayisiWrap">
                            <label class="form-label">Hafta Sayısı</label>
                            <input type="number" class="form-control" name="hafta_sayisi" id="toplu_hafta_sayisi" min="1" value="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Günler</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php $gunler = ['Pzt','Sal','Car','Per','Cum','Cmt','Paz']; foreach ($gunler as $gun) { ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="gunler[]" value="<?php echo $gun; ?>" id="toplu_gun_<?php echo $gun; ?>">
                                    <label class="form-check-label" for="toplu_gun_<?php echo $gun; ?>"><?php echo $gun; ?></label>
                                </div>
                            <?php } ?>
                        </div>
                        <small class="text-muted">Aylık seçilirse ay sonuna kadar seçili günlerde oluşturulur.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="baslangic_tarihi" id="toplu_baslangic" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Başlangıç Saati</label>
                            <input type="time" class="form-control" name="baslangic_saat" id="toplu_saat" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Seans Süresi (dk)</label>
                            <input type="number" class="form-control" name="seans_suresi_dk" id="toplu_sure">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kontenjan</label>
                            <input type="number" class="form-control" name="kontenjan" id="toplu_kontenjan">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="topluSeansKaydetBtn">Oluştur</button>
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
    function tekrarGunleriToggle() {
        if ($('#grup_tekrar').val() === 'haftalik') {
            $('#tekrarGunleriWrap').show();
        } else {
            $('#tekrarGunleriWrap').hide();
        }
    }

    $('#grup_tekrar').on('change', tekrarGunleriToggle);
    tekrarGunleriToggle();

    function periyotToggle() {
        if ($('#toplu_periyot').val() === 'aylik') {
            $('#haftaSayisiWrap').hide();
        } else {
            $('#haftaSayisiWrap').show();
        }
    }
    $('#toplu_periyot').on('change', periyotToggle);
    periyotToggle();

    $('#toplu_grup').on('change', function () {
        var opt = $(this).find(':selected');
        if (!$('#toplu_sure').val()) {
            var sure = opt.data('sure');
            if (sure) {
                $('#toplu_sure').val(sure);
            }
        }
        if (!$('#toplu_kontenjan').val()) {
            var kont = opt.data('kapasite');
            if (kont) {
                $('#toplu_kontenjan').val(kont);
            }
        }
    });

    $('#grupKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#grupForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Grup kaydedilemedi.');
                }
            }
        });
    });

    $('#seansKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#seansForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Seans kaydedilemedi.');
                }
            }
        });
    });

    $('#topluSeansKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#topluSeansForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    alert(res.mesaj || 'Seanslar oluşturuldu.');
                    location.reload();
                } else {
                    alert(res.mesaj || 'Seanslar oluşturulamadı.');
                }
            }
        });
    });

    $('.grup-duzenle').on('click', function () {
        $('#grup_id').val($(this).data('id'));
        $('#grup_sube').val($(this).data('sube'));
        $('#grup_alan').val($(this).data('alan'));
        $('#grup_adi').val($(this).data('adi'));
        $('#grup_min_ay').val($(this).data('min'));
        $('#grup_max_ay').val($(this).data('max'));
        $('#grup_kapasite').val($(this).data('kapasite'));
        $('#grup_tekrar').val($(this).data('tekrar'));
        $('#grup_saat').val($(this).data('saat'));
        $('#grup_sure').val($(this).data('sure'));
        $('#grup_baslangic').val($(this).data('baslangic'));
        $('#grup_bitis').val($(this).data('bitis'));

        var gunler = ($(this).data('gunler') || '').split(',');
        $('input[name="tekrar_gunleri[]"]').prop('checked', false);
        gunler.forEach(function (gun) {
            if (gun) {
                $('#gun_' + gun).prop('checked', true);
            }
        });
        tekrarGunleriToggle();
        $('#grupModal').modal('show');
    });

    $('#grupModal').on('hidden.bs.modal', function () {
        $('#grupForm')[0].reset();
        $('#grup_id').val('0');
        $('#grup_alan').val('');
        $('input[name="tekrar_gunleri[]"]').prop('checked', false);
        tekrarGunleriToggle();
    });

    $('#seansModal').on('hidden.bs.modal', function () {
        $('#seansForm')[0].reset();
        $('#seans_id').val('0');
    });

    $('#topluSeansModal').on('hidden.bs.modal', function () {
        $('#topluSeansForm')[0].reset();
        $('input[name="gunler[]"]').prop('checked', false);
        periyotToggle();
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
