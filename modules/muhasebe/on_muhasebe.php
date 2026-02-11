<?php
require_once(__DIR__ . '/../../includes/config.php');
require_once(__DIR__ . '/../../includes/functions.php');

giris_zorunlu();
require_once(__DIR__ . '/../../theme/header.php');

$kurum_id = aktif_kurum_id();
$kategori_var = kasa_kategori_var_mi();
$subeler = [];
$veliler = [];
$gelirler = [];
$giderler = [];
$toplam_gelir = 0;
$toplam_gider = 0;

$tarih_bas = trim($_GET['tarih_bas'] ?? '');
$tarih_bit = trim($_GET['tarih_bit'] ?? '');
$filtre_sube = (int) ($_GET['sube_id'] ?? 0);
$filtre_odeme = trim($_GET['odeme_yontemi'] ?? '');

$gider_kategorileri = [
    'Personel Maaşları',
    'SGK ve Vergiler',
    'Kira',
    'Elektrik',
    'Su',
    'Doğalgaz',
    'İnternet/Telefon',
    'Yemek/İkram',
    'Eğitim Materyalleri',
    'Oyuncak ve Etkinlik Malzemeleri',
    'Kırtasiye ve Ofis',
    'Temizlik',
    'Bakım/Onarım',
    'Güvenlik',
    'Sigorta',
    'Danışmanlık',
    'Yazılım/Lisans',
    'Pazarlama/Reklam',
    'Ulaşım/Servis',
    'Demirbaş/Donanım',
    'Eğitmen Eğitimleri',
    'Diğer',
];

if (!empty($db) && $kurum_id > 0) {
    $stmt = $db->prepare("SELECT id, sube_adi FROM subeler WHERE kurum_id = :kurum_id ORDER BY sube_adi");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $subeler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT id, ad_soyad FROM veliler WHERE kurum_id = :kurum_id ORDER BY ad_soyad");
    $stmt->execute(['kurum_id' => $kurum_id]);
    $veliler = $stmt->fetchAll();

    $filters = " WHERE k.kurum_id = :kurum_id";
    $params = ['kurum_id' => $kurum_id];
    if ($filtre_sube > 0) {
        $filters .= " AND k.sube_id = :sube_id";
        $params['sube_id'] = $filtre_sube;
    }
    if ($filtre_odeme !== '' && in_array($filtre_odeme, ['nakit', 'kredi_karti', 'havale'], true)) {
        $filters .= " AND k.odeme_yontemi = :odeme_yontemi";
        $params['odeme_yontemi'] = $filtre_odeme;
    }
    if ($tarih_bas !== '') {
        $filters .= " AND k.tarih >= :tarih_bas";
        $params['tarih_bas'] = $tarih_bas . ' 00:00:00';
    }
    if ($tarih_bit !== '') {
        $filters .= " AND k.tarih <= :tarih_bit";
        $params['tarih_bit'] = $tarih_bit . ' 23:59:59';
    }

    $kategori_select = $kategori_var ? "k.kategori" : "'' AS kategori";
    $sql_gelir = "SELECT k.id, k.tarih, k.tutar, k.odeme_yontemi, k.aciklama, {$kategori_select}, v.ad_soyad
        FROM kasa_hareketleri k
        LEFT JOIN veliler v ON v.id = k.veli_id
        {$filters} AND k.islem_tipi = 'gelir'
        ORDER BY k.tarih DESC";
    $stmt = $db->prepare($sql_gelir);
    $stmt->execute($params);
    $gelirler = $stmt->fetchAll();

    $sql_gider = "SELECT k.id, k.tarih, k.tutar, k.odeme_yontemi, k.aciklama, {$kategori_select}
        FROM kasa_hareketleri k
        {$filters} AND k.islem_tipi = 'gider'
        ORDER BY k.tarih DESC";
    $stmt = $db->prepare($sql_gider);
    $stmt->execute($params);
    $giderler = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT COALESCE(SUM(k.tutar),0) FROM kasa_hareketleri k {$filters} AND k.islem_tipi = 'gelir'");
    $stmt->execute($params);
    $toplam_gelir = (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(k.tutar),0) FROM kasa_hareketleri k {$filters} AND k.islem_tipi = 'gider'");
    $stmt->execute($params);
    $toplam_gider = (float) $stmt->fetchColumn();
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
                                    <h3>Muhasebe / Ön Muhasebe (Gelir-Gider)</h3>
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
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card p-3">
                                        <div class="text-muted">Toplam Gelir</div>
                                        <div class="h5 mb-0"><?php echo number_format($toplam_gelir, 2, ',', '.'); ?> ₺</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card p-3">
                                        <div class="text-muted">Toplam Gider</div>
                                        <div class="h5 mb-0"><?php echo number_format($toplam_gider, 2, ',', '.'); ?> ₺</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card p-3">
                                        <div class="text-muted">Net</div>
                                        <div class="h5 mb-0"><?php echo number_format($toplam_gelir - $toplam_gider, 2, ',', '.'); ?> ₺</div>
                                    </div>
                                </div>
                            </div>
                            <form class="mt-4" method="get">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Şube</label>
                                        <select class="form-select" name="sube_id">
                                            <option value="">Tümü</option>
                                            <?php foreach ($subeler as $sube) { ?>
                                                <option value="<?php echo (int) $sube['id']; ?>" <?php echo $filtre_sube === (int) $sube['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Ödeme Yöntemi</label>
                                        <select class="form-select" name="odeme_yontemi">
                                            <option value="">Tümü</option>
                                            <option value="nakit" <?php echo $filtre_odeme === 'nakit' ? 'selected' : ''; ?>>Nakit</option>
                                            <option value="kredi_karti" <?php echo $filtre_odeme === 'kredi_karti' ? 'selected' : ''; ?>>Kredi Kartı</option>
                                            <option value="havale" <?php echo $filtre_odeme === 'havale' ? 'selected' : ''; ?>>Havale</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tarih (Başlangıç)</label>
                                        <input type="date" class="form-control" name="tarih_bas" value="<?php echo htmlspecialchars($tarih_bas, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Tarih (Bitiş)</label>
                                        <input type="date" class="form-control" name="tarih_bit" value="<?php echo htmlspecialchars($tarih_bit, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Uygula</button>
                                    <a href="modules/muhasebe/on_muhasebe.php" class="btn btn-light">Temizle</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0">Gelir / Gider İşlemleri</h6>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#gelirModal">Gelir Ekle</button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#giderModal">Gider Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <h6 class="mb-3">Gelirler</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="gelir-table">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Veli</th>
                                            <th>Ödeme</th>
                                            <th>Kategori</th>
                                            <th>Tutar</th>
                                            <th>Açıklama</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($gelirler)) { ?>
                                        <tr>
                                            <td>Gelir kaydı bulunamadı.</td>
                                            <td></td><td></td><td></td><td></td><td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($gelirler as $gelir) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($gelir['tarih'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($gelir['ad_soyad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($gelir['odeme_yontemi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($gelir['kategori'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo number_format((float) ($gelir['tutar'] ?? 0), 2, ',', '.'); ?> ₺</td>
                                                <td><?php echo htmlspecialchars($gelir['aciklama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 layout-spacing">
                    <div class="widget widget-card-four">
                        <div class="widget-content">
                            <h6 class="mb-3">Giderler</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="gider-table">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>Kategori</th>
                                            <th>Ödeme</th>
                                            <th>Tutar</th>
                                            <th>Açıklama</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($giderler)) { ?>
                                        <tr>
                                            <td>Gider kaydı bulunamadı.</td>
                                            <td></td><td></td><td></td><td></td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($giderler as $gider) { ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($gider['tarih'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($gider['kategori'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($gider['odeme_yontemi'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo number_format((float) ($gider['tutar'] ?? 0), 2, ',', '.'); ?> ₺</td>
                                                <td><?php echo htmlspecialchars($gider['aciklama'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
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

<div class="modal fade" id="gelirModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gelir Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="gelirForm">
                    <input type="hidden" name="islem" value="gelir_kaydet">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Şube</label>
                            <select class="form-select" name="sube_id">
                                <?php foreach ($subeler as $sube) { ?>
                                    <option value="<?php echo (int) $sube['id']; ?>"><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Veli (opsiyonel)</label>
                            <select class="form-select" name="veli_id">
                                <option value="">Seçiniz</option>
                                <?php foreach ($veliler as $veli) { ?>
                                    <option value="<?php echo (int) $veli['id']; ?>"><?php echo htmlspecialchars($veli['ad_soyad'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" name="odeme_yontemi">
                                <option value="nakit">Nakit</option>
                                <option value="kredi_karti">Kredi Kartı</option>
                                <option value="havale">Havale</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tutar</label>
                            <input type="number" step="0.01" class="form-control" name="tutar" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <input type="text" class="form-control" name="aciklama" placeholder="Gelir açıklaması">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" form="gelirForm" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="giderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gider Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="giderForm">
                    <input type="hidden" name="islem" value="gider_kaydet">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Şube</label>
                            <select class="form-select" name="sube_id">
                                <?php foreach ($subeler as $sube) { ?>
                                    <option value="<?php echo (int) $sube['id']; ?>"><?php echo htmlspecialchars($sube['sube_adi'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="kategori" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($gider_kategorileri as $kategori) { ?>
                                    <option value="<?php echo htmlspecialchars($kategori, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($kategori, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" name="odeme_yontemi">
                                <option value="nakit">Nakit</option>
                                <option value="kredi_karti">Kredi Kartı</option>
                                <option value="havale">Havale</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tutar</label>
                            <input type="number" step="0.01" class="form-control" name="tutar" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <input type="text" class="form-control" name="aciklama" placeholder="Gider açıklaması">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Vazgeç</button>
                <button type="submit" form="giderForm" class="btn btn-primary">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    if (!window.jQuery) {
        return;
    }
    var $ = window.jQuery;

    if ($.fn.DataTable) {
        $('#gelir-table').DataTable({
            order: [[0, 'desc']]
        });
        $('#gider-table').DataTable({
            order: [[0, 'desc']]
        });
    }

    $('#gelirForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Gelir kaydedilemedi.');
                }
            }
        });
    });

    $('#giderForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'ajax.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                if (res.durum === 'ok') {
                    location.reload();
                } else {
                    alert(res.mesaj || 'Gider kaydedilemedi.');
                }
            }
        });
    });

    $('#gelirModal').on('hidden.bs.modal', function () {
        if ($('#gelirForm').length) {
            $('#gelirForm')[0].reset();
        }
    });
    $('#giderModal').on('hidden.bs.modal', function () {
        if ($('#giderForm').length) {
            $('#giderForm')[0].reset();
        }
    });
});
</script>

<?php require_once(__DIR__ . '/../../theme/footer.php'); ?>
