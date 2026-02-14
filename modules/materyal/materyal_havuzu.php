<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$materyaller = [];
$gruplu_materyaller = [];
$kullanici_map = [];
$yetkili = materyal_yukleme_yetkili_mi();

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT * FROM materyal_havuzu WHERE kurum_id = :kurum_id ORDER BY yukleme_tarihi DESC");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $materyaller = $stmt->fetchAll();
}

if (!empty($db_master) && $kurum_id > 0) {
    $stmt = $db_master->prepare("SELECT id, kullanici_adi FROM kullanicilar WHERE kurum_id = :kurum_id");
    $stmt->execute(['kurum_id' => $kurum_id]);
    foreach ($stmt->fetchAll() as $row) {
        $kullanici_map[(int) $row['id']] = $row['kullanici_adi'];
    }
}

if (!empty($materyaller)) {
    foreach ($materyaller as $mat) {
        $adi = trim((string) ($mat['materyal_adi'] ?? ''));
        $kazanim = trim((string) ($mat['kazanimlar'] ?? ''));
        $yukleyen_id = (int) ($mat['yukleyen_kullanici_id'] ?? 0);
        $key = strtolower($adi) . '|' . $kazanim . '|' . $yukleyen_id;
        if (!isset($gruplu_materyaller[$key])) {
            $gruplu_materyaller[$key] = [
                'materyal_adi' => $adi,
                'kazanimlar' => $kazanim,
                'yukleyen_id' => $yukleyen_id,
                'yukleyen' => $kullanici_map[$yukleyen_id] ?? '-',
                'yukleme_tarihi' => $mat['yukleme_tarihi'] ?? null,
                'dosyalar' => [],
            ];
        }
        if (!empty($mat['yukleme_tarihi'])) {
            if (empty($gruplu_materyaller[$key]['yukleme_tarihi']) ||
                strtotime($mat['yukleme_tarihi']) > strtotime((string) $gruplu_materyaller[$key]['yukleme_tarihi'])) {
                $gruplu_materyaller[$key]['yukleme_tarihi'] = $mat['yukleme_tarihi'];
            }
        }
        if (!empty($mat['materyal_dosya'])) {
            $gruplu_materyaller[$key]['dosyalar'][] = $mat['materyal_dosya'];
        }
    }
    $gruplu_materyaller = array_values($gruplu_materyaller);
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
                                    <h3>Materyal Havuzu</h3>
                                </div>
                            </div>
                        </div>
                    </header>
                </div>
            </div>

            <?php if ($yetkili) { ?>
            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Materyal Yükle</h6>
                            </div>
                            <form id="materyalForm" class="mt-3" enctype="multipart/form-data">
                                <input type="hidden" name="islem" value="materyal_yukle">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Materyal Adı</label>
                                        <input type="text" class="form-control" name="materyal_adi" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kazanımlar</label>
                                        <input type="text" class="form-control" name="kazanimlar" placeholder="JSON veya metin">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Dosya</label>
                                        <input type="file" class="form-control" name="dosya[]" multiple required>
                                        <small class="text-muted">Birden fazla dosya seçebilirsiniz.</small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" id="materyalKaydetBtn">Yükle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="row layout-top-spacing">
                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <div class="w-info">
                                <h6 class="value">Materyal Listesi</h6>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-striped table-hover" id="zero-config">
                                    <thead>
                                        <tr>
                                            <th>Materyal</th>
                                            <th>Kazanımlar</th>
                                            <th>Yükleyen</th>
                                            <th>Tarih</th>
                                            <th>Dosya</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($gruplu_materyaller)) { ?>
                                        <tr>
                                            <td>Materyal bulunamadı.</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($gruplu_materyaller as $mat) { ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($mat['materyal_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!empty($mat['dosyalar'])) { ?>
                                                        <span class="badge bg-info ms-2"><?php echo count($mat['dosyalar']); ?> dosya</span>
                                                    <?php } ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($mat['kazanimlar'] !== '' ? $mat['kazanimlar'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($mat['yukleyen'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <?php echo !empty($mat['yukleme_tarihi'])
                                                        ? htmlspecialchars(date('d.m.Y H:i', strtotime($mat['yukleme_tarihi'])), ENT_QUOTES, 'UTF-8')
                                                        : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($mat['dosyalar'])) { ?>
                                                        <div class="d-flex flex-column gap-1">
                                                            <?php foreach ($mat['dosyalar'] as $idx => $path) { ?>
                                                                <a href="<?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?>" target="_blank">Dosya <?php echo $idx + 1; ?></a>
                                                            <?php } ?>
                                                        </div>
                                                    <?php } else { ?>
                                                        -
                                                    <?php } ?>
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

<script>
window.addEventListener('load', function () {
    if (!window.jQuery) {
        console.error('jQuery yuklenemedi.');
        return;
    }
    var $ = window.jQuery;

    $('#materyalKaydetBtn').on('click', function () {
        var form = $('#materyalForm')[0];
        var data = new FormData(form);
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Materyal yuklenemedi.');
                }
            }
        });
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
