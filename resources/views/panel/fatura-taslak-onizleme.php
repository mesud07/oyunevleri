<?php $pdfUrl='/panel/faturalar/dosya?id='.(int)$fatura['id'].'&format=pdf#toolbar=0&navpanes=0'; ?>
<main class="draft-preview-page">
    <header class="draft-preview-head">
        <div>
            <strong>GEÇERSİZ FATURA — TASLAK ÖNİZLEME</strong>
            <span><?= e(($fatura['fatura_no']?:$fatura['ettn']).' · '.$fatura['alici_adi'].' · '.para_goster($fatura['genel_toplam'])) ?></span>
        </div>
        <a class="btn btn-ghost" href="/panel/faturalar/detay?id=<?= e($fatura['id']) ?>">Önizlemeyi Kapat</a>
    </header>
    <div class="draft-preview-warning">Bu belge yalnızca kontrol içindir; resmî fatura değildir. Resmileştirmek için fatura detayında ayrıca onay vermelisiniz.</div>
    <section class="draft-pdf-shell" aria-label="Geçersiz taslak fatura PDF önizlemesi">
        <iframe title="Taslak fatura PDF" src="<?= e($pdfUrl) ?>"></iframe>
        <div class="draft-pdf-watermark" aria-hidden="true"><span>GEÇERSİZ</span><small>TASLAK — RESMÎ BELGE DEĞİLDİR</small></div>
    </section>
</main>
