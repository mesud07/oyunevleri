<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$kurum = [];
$subeler = [];
$galeri = [];

if (!empty($db_master) && $kurum_id > 0) {
    $stmt = $db_master->prepare("SELECT * FROM kurumlar WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $kurum_id]);
    $kurum = $stmt->fetch() ?: [];

    $stmt = $db_master->prepare("SELECT id, gorsel_yol, sira FROM kurum_galeri WHERE kurum_id = :kurum_id ORDER BY sira ASC, id ASC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $galeri = $stmt->fetchAll();
}

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT * FROM subeler WHERE kurum_id = :kurum_id ORDER BY id DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $subeler = $stmt->fetchAll();
}

function val($arr, $key, $default = '') {
    return isset($arr[$key]) ? htmlspecialchars((string) $arr[$key], ENT_QUOTES, 'UTF-8') : $default;
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
                                    <h3>Kurum & Şube Ayarları</h3>
                                </div>
                            </div>
                        </div>
                    </header>
                </div>
            </div>

            <div class="row layout-top-spacing">
                <div class="col-xl-5 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Kurum Profili</h6>
                            </div>
                            <form id="kurumForm" class="mt-3">
                                <input type="hidden" name="islem" value="kurum_profil_kaydet">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Kurum Kodu</label>
                                        <input type="text" class="form-control" value="<?php echo val($kurum, 'kurum_kodu'); ?>" disabled>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Kurum Adı</label>
                                        <input type="text" class="form-control" name="kurum_adi" value="<?php echo val($kurum, 'kurum_adi'); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Şehir</label>
                                        <input type="text" class="form-control" name="sehir" value="<?php echo val($kurum, 'sehir'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">İlçe</label>
                                        <input type="text" class="form-control" name="ilce" value="<?php echo val($kurum, 'ilce'); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Adres</label>
                                        <textarea class="form-control" name="adres" rows="3"><?php echo val($kurum, 'adres'); ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telefon</label>
                                        <input type="text" class="form-control" name="telefon" value="<?php echo val($kurum, 'telefon'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">E-posta</label>
                                        <input type="email" class="form-control" name="eposta" value="<?php echo val($kurum, 'eposta'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Min Ay</label>
                                        <input type="number" class="form-control" name="min_ay" value="<?php echo val($kurum, 'min_ay'); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Max Ay</label>
                                        <input type="number" class="form-control" name="max_ay" value="<?php echo val($kurum, 'max_ay'); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Hakkımızda</label>
                                        <textarea class="form-control" name="hakkimizda" rows="4" placeholder="Kurum hakkında kısa bilgi"><?php echo val($kurum, 'hakkimizda'); ?></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-2 form-check">
                                        <input class="form-check-input" type="checkbox" name="meb_onay" value="1" id="meb_onay" <?php echo !empty($kurum['meb_onay']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="meb_onay">MEB Onaylı</label>
                                    </div>
                                    <div class="col-md-6 mb-2 form-check">
                                        <input class="form-check-input" type="checkbox" name="aile_sosyal_onay" value="1" id="aile_onay" <?php echo !empty($kurum['aile_sosyal_onay']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="aile_onay">Aile Sosyal Onaylı</label>
                                    </div>
                                    <div class="col-md-6 mb-2 form-check">
                                        <input class="form-check-input" type="checkbox" name="hizmet_bahceli" value="1" id="bahceli" <?php echo !empty($kurum['hizmet_bahceli']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="bahceli">Bahçeli</label>
                                    </div>
                                    <div class="col-md-6 mb-2 form-check">
                                        <input class="form-check-input" type="checkbox" name="hizmet_guvenlik_kamerasi" value="1" id="kamera" <?php echo !empty($kurum['hizmet_guvenlik_kamerasi']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="kamera">Güvenlik Kamerası</label>
                                    </div>
                                    <div class="col-md-6 mb-2 form-check">
                                        <input class="form-check-input" type="checkbox" name="hizmet_ingilizce" value="1" id="ingilizce" <?php echo !empty($kurum['hizmet_ingilizce']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ingilizce">İngilizce Oyun Grubu</label>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="kurumKaydetBtn">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="w-info">
                                    <h6 class="value">Şubeler</h6>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#subeModal">Yeni Şube</button>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Şube</th>
                                            <th>Şehir</th>
                                            <th>İlçe</th>
                                            <th>Adres</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($subeler)) { ?>
                                        <tr>
                                            <td>Şube kaydı bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($subeler as $sube) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($sube['sehir'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($sube['ilce'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($sube['adres'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-outline-primary sube-duzenle"
                                                        data-id="<?php echo (int) $sube['id']; ?>"
                                                        data-adi="<?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-sehir="<?php echo htmlspecialchars($sube['sehir'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-ilce="<?php echo htmlspecialchars($sube['ilce'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-adres="<?php echo htmlspecialchars($sube['adres'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        Düzenle
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger sube-sil" data-id="<?php echo (int) $sube['id']; ?>">Sil</button>
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
                                    <h6 class="value">Kurum Galerisi (Store Görselleri)</h6>
                                </div>
                            </div>
                            <form id="galeriForm" class="row g-2 mt-3" enctype="multipart/form-data">
                                <input type="hidden" name="islem" value="kurum_galeri_ekle">
                                <div class="col-md-8">
                                    <label class="form-label">Görsel Dosya (jpg, jpeg, png)</label>
                                    <input type="file" class="form-control" name="gorsel[]" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple required>
                                    <div class="form-text">Birden fazla görsel seçebilirsiniz.</div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sıra</label>
                                    <input type="number" class="form-control" name="sira" value="0" min="0">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary w-100" id="galeriEkleBtn">Ekle</button>
                                </div>
                            </form>
                            <div class="d-flex align-items-center justify-content-between mt-4">
                                <div class="text-muted">Sürükle bırak ile sıralayın. Sıralama otomatik kaydedilir.</div>
                            </div>
                            <div class="row mt-3" id="galeriGrid">
                                <?php if (empty($galeri)) { ?>
                                    <div class="col-12">
                                        <div class="alert alert-light">Galeride kayıtlı görsel bulunamadı.</div>
                                    </div>
                                <?php } else { ?>
                                    <?php foreach ($galeri as $gorsel) { ?>
                                        <div class="col-md-3 mb-3 galeri-item" data-id="<?php echo (int) $gorsel['id']; ?>">
                                            <div class="border rounded p-2 h-100 d-flex flex-column">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <span class="badge bg-light text-dark">Sıra: <?php echo (int) ($gorsel['sira'] ?? 0); ?></span>
                                                    <span class="text-muted small galeri-handle" style="cursor:grab;">⇅ Sürükle</span>
                                                </div>
                                                <div class="mb-2" style="height:140px; overflow:hidden; border-radius:8px; background:#f1f5f9;">
                                                    <img src="<?php echo htmlspecialchars($gorsel['gorsel_yol'], ENT_QUOTES, 'UTF-8'); ?>" alt="galeri" style="width:100%; height:100%; object-fit:cover;">
                                                </div>
                                                <button class="btn btn-sm btn-outline-danger mt-auto galeri-sil" data-id="<?php echo (int) $gorsel['id']; ?>">Sil</button>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
#galeriGrid .galeri-item { cursor: default; }
#galeriGrid .galeri-placeholder {
    border: 2px dashed #cbd5f5;
    background: #f8fafc;
    border-radius: 12px;
    min-height: 200px;
}
</style>

<div class="modal fade" id="subeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Şube Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="subeForm">
                    <input type="hidden" name="islem" value="sube_kaydet">
                    <input type="hidden" name="sube_id" id="sube_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Şube Adı</label>
                        <input type="text" class="form-control" name="sube_adi" id="sube_adi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şehir</label>
                        <input type="text" class="form-control" name="sehir" id="sube_sehir">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İlçe</label>
                        <input type="text" class="form-control" name="ilce" id="sube_ilce">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea class="form-control" name="adres" id="sube_adres" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="subeKaydetBtn">Kaydet</button>
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

    function ensureSortable(callback) {
        if ($.fn.sortable) {
            callback();
            return;
        }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'theme/src/plugins/src/jquery-ui/jquery-ui.min.css';
        document.head.appendChild(link);

        var script = document.createElement('script');
        script.src = 'theme/src/plugins/src/jquery-ui/jquery-ui.min.js';
        script.onload = callback;
        document.body.appendChild(script);
    }
    $('#kurumKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#kurumForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Kayıt yapılamadı.');
                }
            }
        });
    });

    $('#subeKaydetBtn').on('click', function () {
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $('#subeForm').serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Şube kaydedilemedi.');
                }
            }
        });
    });

    $('#galeriEkleBtn').on('click', function () {
        var form = document.getElementById('galeriForm');
        var fileInput = form.querySelector('input[name="gorsel[]"]');
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            alert('Lütfen görsel seçin.');
            return;
        }
        var formData = new FormData(form);
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Görsel eklenemedi.');
                }
            },
            error: function (xhr) {
                var msg = (xhr && xhr.responseText) ? xhr.responseText : 'Görsel eklenemedi.';
                alert(msg);
            }
        });
    });

    ensureSortable(function () {
        var $grid = $('#galeriGrid');
        if (!$grid.length || !$grid.find('.galeri-item').length) {
            return;
        }
        $grid.sortable({
            items: '.galeri-item',
            handle: '.galeri-handle',
            placeholder: 'galeri-placeholder',
            forcePlaceholderSize: true,
            update: function () {
                var order = [];
                $grid.find('.galeri-item').each(function () {
                    order.push($(this).data('id'));
                });
                $.ajax({
                    url: 'ajax.php',
                    type: 'POST',
                    traditional: true,
                    data: { islem: 'kurum_galeri_sirala', galeri_ids: order },
                    success: function (res) {
                        if (res.durum !== 'ok') {
                            alert(res.mesaj || 'Sıralama kaydedilemedi.');
                            return;
                        }
                        if (window.Snackbar) {
                            Snackbar.show({
                                text: 'Sıra güncellendi',
                                pos: 'bottom-right',
                                backgroundColor: '#00ab55',
                                actionTextColor: '#fff'
                            });
                        }
                    }
                });
            }
        });
    });

    $('.galeri-sil').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('Görsel silinsin mi?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'kurum_galeri_sil', galeri_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Görsel silinemedi.');
                }
            }
        });
    });

    $('.sube-duzenle').on('click', function () {
        $('#sube_id').val($(this).data('id'));
        $('#sube_adi').val($(this).data('adi'));
        $('#sube_sehir').val($(this).data('sehir'));
        $('#sube_ilce').val($(this).data('ilce'));
        $('#sube_adres').val($(this).data('adres'));
        $('#subeModal').modal('show');
    });

    $('.sube-sil').on('click', function () {
        var id = $(this).data('id');
        if (!confirm('Şube silinsin mi?')) {
            return;
        }
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: { islem: 'sube_sil', sube_id: id },
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Şube silinemedi.');
                }
            }
        });
    });

    $('#subeModal').on('hidden.bs.modal', function () {
        $('#subeForm')[0].reset();
        $('#sube_id').val('0');
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
