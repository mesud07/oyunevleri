<?php $faturaIslemiYapabilir=yetki_var('odeme_ekle'); ?>
<section class="page-head">
    <div><h1>Faturalar</h1><p>NES üzerinden düzenlenen e-Fatura ve e-Arşiv kayıtları.</p></div>
    <div class="head-actions">
        <?php if(yetki_var('fatura_entegrasyon_yonet')): ?><a class="btn btn-ghost" href="/panel/faturalar/entegrasyon-ayarlari">Entegrasyon Ayarları</a><?php endif; ?>
    </div>
</section>

<section class="panel-card report-panel">
    <form method="get" action="/panel/faturalar" class="filter-grid">
        <label><span>Başlangıç</span><input type="date" name="baslangic" value="<?= e($filtre['baslangic']??'') ?>"></label>
        <label><span>Bitiş</span><input type="date" name="bitis" value="<?= e($filtre['bitis']??'') ?>"></label>
        <label><span>Arama</span><input name="arama" value="<?= e($filtre['arama']??'') ?>" placeholder="Fatura no, ETTN, müşteri"></label>
        <label><span>Belge</span><select name="belge_turu"><option value="">Tümü</option><option value="EFATURA" <?= ($filtre['belge_turu']??'')==='EFATURA'?'selected':'' ?>>e-Fatura</option><option value="EARSIVFATURA" <?= ($filtre['belge_turu']??'')==='EARSIVFATURA'?'selected':'' ?>>e-Arşiv</option></select></label>
        <label><span>Durum</span><select name="yerel_durum"><option value="">Tümü</option><?php foreach(['taslak'=>'Taslak','olusturuluyor'=>'Oluşturuluyor','gonderildi'=>'Gönderildi','isleniyor'=>'İşleniyor','basarili'=>'Başarılı','hatali'=>'Hatalı','reddedildi'=>'Reddedildi','iptal'=>'İptal'] as $k=>$v): ?><option value="<?= e($k) ?>" <?= ($filtre['yerel_durum']??'')===$k?'selected':'' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
        <div class="record-actions"><button class="btn btn-primary" type="submit">Filtrele</button></div>
    </form>
</section>

<section class="panel-card report-panel">
    <?php if(!empty($sonuc['kayitlar'])): ?>
        <div class="invoice-bulk-toolbar" data-invoice-bulk-toolbar>
            <div class="invoice-bulk-summary"><strong data-invoice-selected-count>0 fatura seçildi</strong><span>Bu sayfadaki kayıtlar üzerinde işlem yapılır.</span></div>
            <div class="record-actions">
                <?php if($faturaIslemiYapabilir): ?><button class="btn btn-primary" type="button" data-invoice-bulk-approve disabled>Toplu Onayla</button><?php endif; ?>
                <?php if($faturaIslemiYapabilir): ?><button class="btn btn-danger" type="button" data-invoice-bulk-cancel disabled>Toplu İptal Et</button><?php endif; ?>
                <button class="btn btn-ghost" type="button" data-invoice-bulk-download disabled>Kesilenleri ZIP İndir</button>
            </div>
        </div>
    <?php endif; ?>
    <div class="table-wrap"><table><thead><tr><th class="invoice-selection"><input type="checkbox" data-invoice-select-all aria-label="Bu sayfadaki tüm faturaları seç"></th><th>Fatura No</th><th>Fatura Tarihi</th><th>Müşteri</th><th>VKN/TCKN</th><th>Belge</th><th>Fatura Tipi</th><th>Tutar</th><th>Para Birimi</th><th>Durum</th><th>Kaynak</th><th>Oluşturulma</th><th>İşlem</th></tr></thead><tbody>
    <?php if(empty($sonuc['kayitlar'])):?><tr><td colspan="13">Fatura kaydı bulunamadı.</td></tr><?php endif; ?>
    <?php foreach(($sonuc['kayitlar']??[]) as $row): ?><?php
        $yerelDurum=(string)($row['yerel_durum']??'');
        $gosterilenDurum=fatura_durum_goster($row['provider_durum']?:($row['mysoft_durum']?:$yerelDurum));
        $durumSinifi=match($yerelDurum){
            'taslak'=>'is-draft',
            'olusturuluyor','gonderildi','isleniyor'=>'is-processing',
            'basarili'=>'is-success',
            'hatali'=>'is-danger',
            'reddedildi'=>'is-rejected',
            'iptal'=>'is-cancelled',
            default=>match($gosterilenDurum){
                'Taslak'=>'is-draft',
                'Oluşturuluyor','Gönderildi','İşleniyor','Bekliyor'=>'is-processing',
                'Başarılı'=>'is-success',
                'Hatalı'=>'is-danger',
                'Reddedildi'=>'is-rejected',
                'İptal'=>'is-cancelled',
                default=>'is-neutral',
            },
        };
        $nesFaturasi=($row['provider']??'')==='nes';
        $onaylanabilir=$nesFaturasi&&$yerelDurum==='taslak';
        $iptalEdilebilir=$nesFaturasi&&($row['belge_turu']??'')==='EARSIVFATURA'&&in_array($yerelDurum,['gonderildi','isleniyor','basarili'],true);
        $indirilebilir=$nesFaturasi&&in_array($yerelDurum,['gonderildi','isleniyor','basarili','iptal'],true);
    ?><tr class="<?= $yerelDurum==='taslak'?'is-draft-row':'' ?>">
        <td class="invoice-selection"><input type="checkbox" value="<?= e($row['id']) ?>" data-invoice-select data-invoice-approvable="<?= $onaylanabilir?'1':'0' ?>" data-invoice-cancelable="<?= $iptalEdilebilir?'1':'0' ?>" data-invoice-downloadable="<?= $indirilebilir?'1':'0' ?>" aria-label="<?= e(($row['fatura_no']?:$row['ettn']).' faturasını seç') ?>"></td>
        <td><?= e($row['fatura_no']?:'-') ?><small class="table-subtext"><?= e($row['ettn']) ?></small></td><td><?= e(tarih_goster($row['fatura_tarihi'])) ?></td><td><?= e($row['alici_adi']) ?></td><td><?= e($row['alici_vkn_tckn']) ?></td>
        <td><?= $row['belge_turu']==='EFATURA'?'e-Fatura':'e-Arşiv' ?></td><td><?= e($row['fatura_tipi']) ?></td><td><?= e(para_goster($row['genel_toplam'])) ?></td><td><?= e($row['para_birimi']) ?></td><td><span class="status-pill invoice-status <?= e($durumSinifi) ?>"><?= e($gosterilenDurum) ?></span></td><td><?= ($row['provider']??'')==='nes'?'NES':'Arşiv' ?></td><td><?= e(tarih_saat_goster($row['olusturulma_tarihi'])) ?></td>
        <td class="invoice-row-action-cell"><div class="invoice-row-actions">
            <button class="invoice-row-menu-toggle" type="button" data-invoice-row-menu-toggle aria-expanded="false" aria-controls="invoice-row-menu-<?= e($row['id']) ?>" title="Fatura işlemleri"><span aria-hidden="true">⋯</span><span class="sr-only">Fatura işlemlerini aç</span></button>
            <div class="invoice-row-menu" id="invoice-row-menu-<?= e($row['id']) ?>" data-invoice-row-menu hidden>
                <a class="invoice-row-menu-item" href="/panel/faturalar/detay?id=<?= e($row['id']) ?>"><?= $yerelDurum==='taslak'?'Taslağı İncele':'Fatura Detayı' ?></a>
                <?php if($nesFaturasi&&$yerelDurum==='taslak'): ?><a class="invoice-row-menu-item" target="_blank" rel="noopener" href="/panel/faturalar/taslak-onizleme?id=<?= e($row['id']) ?>">Geçersiz PDF Önizle</a><?php endif; ?>
                <?php if($nesFaturasi&&$indirilebilir): ?><a class="invoice-row-menu-item" target="_blank" rel="noopener" href="/panel/faturalar/dosya?id=<?= e($row['id']) ?>&format=pdf">PDF Görüntüle</a><a class="invoice-row-menu-item" href="/panel/faturalar/dosya?id=<?= e($row['id']) ?>&format=xml&indir=1">XML İndir</a><?php endif; ?>
                <?php if($faturaIslemiYapabilir&&$onaylanabilir): ?><button class="invoice-row-menu-item is-danger" type="button" data-invoice-approve="<?= e($row['id']) ?>">Onayla ve Faturayı Kes</button><?php endif; ?>
                <?php if($faturaIslemiYapabilir&&$nesFaturasi&&!$onaylanabilir&&$yerelDurum!=='iptal'): ?><button class="invoice-row-menu-item" type="button" data-invoice-status="<?= e($row['id']) ?>">Durumu Güncelle</button><?php endif; ?>
            </div>
        </div></td>
    </tr><?php endforeach; ?></tbody></table></div>
    <?php $paging=$sonuc['sayfalama']??[]; if(($paging['toplam_sayfa']??1)>1): ?><div class="table-pagination"><div class="table-pagination-nav"><?php for($p=1;$p<=(int)$paging['toplam_sayfa'];$p++): ?><a class="mini-btn <?= $p===(int)$paging['sayfa']?'is-active':'' ?>" href="?<?= e(http_build_query(array_merge($filtre,['sayfa'=>$p]))) ?>"><?= $p ?></a><?php endfor; ?></div></div><?php endif; ?>
    <p data-invoice-page-message></p>
</section>

<?php if($faturaIslemiYapabilir): ?><dialog class="appointment-dialog payment-dialog" data-invoice-bulk-cancel-dialog>
    <form method="dialog" class="appointment-dialog-form" data-invoice-bulk-cancel-form>
        <div class="dialog-head"><div><h2>Seçili e-Arşiv Faturalarını İptal Et</h2><p><strong data-invoice-bulk-cancel-count>0</strong> uygun fatura NES sistemine sırayla iletilecek.</p></div><button type="button" data-invoice-bulk-cancel-close aria-label="Kapat">x</button></div>
        <div class="info-box"><strong>Bu işlem resmî faturaları etkiler.</strong><p>e-Faturalar, taslaklar ve daha önce iptal edilmiş kayıtlar işleme alınmaz.</p></div>
        <div class="dialog-grid">
            <label><span>İptal Tarihi</span><input type="date" name="cancel_date" value="<?= e(date('Y-m-d')) ?>" required></label>
            <label><span>İptal Tipi</span><select name="cancel_type" required><option value="GIB">GİB</option><option value="NOTER">Noter</option><option value="KEP">KEP</option><option value="TAAHHUTLUMEKTUP">Taahhütlü Mektup</option><option value="PORTAL">Portal</option></select></label>
            <label class="dialog-wide"><span>İptal Açıklaması</span><textarea name="cancel_note" rows="4" required placeholder="Toplu iptal gerekçesini yazın"></textarea></label>
        </div>
        <div class="record-actions"><span data-form-message></span><button class="btn btn-ghost" type="button" data-invoice-bulk-cancel-close>Vazgeç</button><button class="btn btn-danger" type="submit">Toplu İptale Devam Et</button></div>
    </form>
</dialog><?php endif; ?>

<form method="post" action="/panel/faturalar/toplu-indir" data-invoice-bulk-download-form hidden>
    <input type="hidden" name="csrf" value="<?= e($csrf??'') ?>">
    <input type="hidden" name="onay" value="TOPLU_INDIR">
    <input type="hidden" name="ids" value="[]" data-invoice-bulk-download-ids>
</form>
