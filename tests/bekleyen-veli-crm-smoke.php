<?php

declare(strict_types=1);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "[FAIL] {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "[OK] {$message}\n");
};

$root = dirname(__DIR__);
$migration = file_get_contents($root . '/database/migrations/20261003_bekleyen_veli_crm.sql') ?: '';
$rollback = file_get_contents($root . '/database/migrations/20261003_bekleyen_veli_crm_rollback.sql') ?: '';
$model = file_get_contents($root . '/app/Models/BekleyenVeli.php') ?: '';
$controller = file_get_contents($root . '/app/Controllers/BekleyenVeliController.php') ?: '';
$packageController = file_get_contents($root . '/app/Controllers/PaketController.php') ?: '';
$ajax = file_get_contents($root . '/public/ajax.php') ?: '';
$view = file_get_contents($root . '/resources/views/panel/bekleyen-veliler.php') ?: '';
$quickAppointment = file_get_contents($root . '/resources/views/partials/hizli-randevu-dialog.php') ?: '';
$js = file_get_contents($root . '/public/assets/js/panel.js') ?: '';
$css = file_get_contents($root . '/public/assets/css/tablolar.css') ?: '';

$assert(
    str_contains($migration, 'next_follow_up_at')
    && str_contains($migration, 'next_action_type')
    && str_contains($migration, 'next_action_note'),
    'Bağımsız takip ve aksiyon alanları migration içinde'
);
$assert(str_contains($migration, 'idx_bekleyen_veliler_crm_takip'), 'CRM takip indeksi tanımlı');
$assert(str_contains($migration, "WHEN 'bekliyor' THEN 'yeni_talep'"), 'Eski durumlar veri kaybetmeden yeni CRM durumlarına eşleniyor');
$assert(
    str_contains($rollback, 'DROP COLUMN next_follow_up_at')
    && str_contains($rollback, "WHEN durum = 'kayit_oldu' THEN 'kayda_donustu'"),
    'CRM migration rollback senaryosu mevcut'
);
$assert(str_contains($model, 'public static function takipGuncelle'), 'Sonraki aksiyon model üzerinden bağımsız güncelleniyor');
$assert(
    !str_contains($model, 'public static function grubaKaydet')
    && !str_contains($controller, 'function grubaKaydet')
    && !str_contains($ajax, "'bekleyen_veli_gruba_kaydet'"),
    'Çok aşamalı gruba kayıt akışı tamamen kaldırıldı'
);
$assert(str_contains($model, 'COUNT(DISTINCT ogrenci_id)') && str_contains($model, 'bos_kontenjan'), 'Kontenjan gerçek randevu doluluğundan hesaplanıyor');
$assert(
    str_contains($view, 'data-waiting-parent-smart-plan')
    && str_contains($js, 'waitingParentPlanScore')
    && str_contains($js, 'row.son_gorusme_sonucu'),
    'Grup müsaitliği ve son görüşmeye göre otomatik aday planı oluşturuluyor'
);
$assert(
    str_contains($js, 'waiting-parent-suitable-groups')
    && str_contains($js, 'group.bos_kontenjan'),
    'Aday kartlarında uygun gruplar ve boş yer sayıları gösteriliyor'
);
$assert(
    str_contains($js, 'waiting-parent-group-preferences')
    && str_contains($js, '<h3>Grup Tercihleri</h3>')
    && str_contains($css, '.waiting-parent-group-preferences'),
    'Aday kartlarında grup tercihleri ayrı ve belirgin bir başlıkla gösteriliyor'
);
$assert(
    str_contains($view, 'data-waiting-parent-plan-groups')
    && str_contains($js, 'data-waiting-parent-plan-group-list')
    && str_contains($js, 'availableGroups')
    && str_contains($js, 'aday_sayisi')
    && str_contains($css, '.waiting-parent-plan-group-list { display: grid; grid-template-columns: 1fr;'),
    'Plan açıldığında müsait gruplar tekilleştirilip adayların üstünde alt alta gösteriliyor'
);
$assert(
    str_contains($js, 'waitingParentCompareGroupsBySchedule')
    && str_contains($js, ').sort(waitingParentCompareGroupsBySchedule)'),
    'Müsait gruplar gün ve başlangıç saatine göre sıralanıyor'
);
$assert(str_contains($model, 'private static function uygunKontenjanlariEkle'), 'Kapasite eşleştirmesi toplu sorgu ile yapılıyor');
$assert(
    str_contains($model, 'TIMESTAMP(tarih, baslangic_saati) >= NOW()')
    && str_contains($model, 'TIMESTAMP(tarih, baslangic_saati) < DATE_ADD(NOW(), INTERVAL 7 DAY)')
    && !str_contains($model, 'YEARWEEK(tarih, 1) = YEARWEEK(CURDATE(), 1)'),
    'CRM müsaitliği takvim haftası yerine bugünden başlayan kayan 7 günlük dönemi kullanıyor'
);
$assert(
    str_contains($model, 'private static function guncelRandevulariEkle')
    && str_contains($model, 'r.ogrenci_id IN ({$yer})')
    && str_contains($model, "r.tarih >= CURDATE()"),
    'Güncel randevular öğrenci bazında toplu sorgulanıyor'
);
$assert(
    str_contains($model, 'AS iletisim_sayisi')
    && str_contains($model, 'bg.kanal <> "diger"'),
    'Sistem kayıtları gerçek veli iletişiminden ayrılıyor'
);
$assert(str_contains($controller, 'BekleyenVeli::aksiyonTipleri()'), 'Aksiyon tipleri sunucu tarafında doğrulanıyor');
$assert(
    str_contains($ajax, "'bekleyen_veli_takip_guncelle'"),
    'Basit takip AJAX ucu bağlı'
);
$assert(
    str_contains($view, 'data-waiting-parent-display="crm"')
    && str_contains($view, 'data-waiting-parent-display="list"'),
    'CRM ve liste görünümleri birlikte korunuyor'
);
$assert(!str_contains($view, 'waiting-parent-kpis'), 'Tekrarlayan KPI kartları kaldırılarak görünüm sadeleştirildi');
$assert(!str_contains($view, 'waiting-parent-more-filters'), 'İkincil filtre aşaması kaldırıldı');
$assert(
    str_contains($view, 'data-kanban-column="new"')
    && str_contains($view, 'data-kanban-column="contacted"')
    && str_contains($view, 'data-kanban-column="appointment"')
    && !str_contains($view, 'data-kanban-column="follow"'),
    'Akış yeni, iletişim ve randevu olmak üzere üç sütuna indirildi'
);
$assert(
    str_contains($js, 'data-quick-appointment-prefill')
    && str_contains($js, '>Randevu Oluştur</button>')
    && !str_contains($js, 'data-waiting-parent-enroll'),
    'Gruba kaydet yerine bilgileri hazır gelen Randevu Oluştur aksiyonu kullanılıyor'
);
$assert(
    str_contains($quickAppointment, 'name="bekleyen_veli_id"')
    && str_contains($model, 'public static function randevuyaBagla')
    && str_contains($packageController, 'BekleyenVeli::randevuyaBagla'),
    'Oluşturulan randevu veli kaydını otomatik olarak randevu sütununa taşıyor'
);
$assert(str_contains($view, 'waiting-parent-drawer'), 'Veli detayı kompakt modal yapısıyla sunuluyor');
$assert(str_contains($view, 'data-waiting-parent-age-filter'), 'Yaş filtresi gerçek grup seçeneklerinden üretiliyor');
$assert(str_contains($js, 'talyaBekleyenVeliGorunumu'), 'Son görünüm tercihi localStorage ile hatırlanıyor');
$assert(str_contains($js, 'https://wa.me/'), 'WhatsApp hızlı aksiyonu normalize numarayı kullanıyor');
$assert(str_contains($js, "if (days >= 30) return '1 ay+'"), 'Bekleme süresi kullanıcı dostu gösteriliyor');
$assert(str_contains($js, "row.takip_durumu === 'gecikmis'") && str_contains($js, "row.takip_durumu === 'bugun'"), 'CRM önceliği gecikmiş ve bugünkü takipleri öne alıyor');
$assert(
    str_contains($js, "return 'appointment'")
    && str_contains($js, "return 'new'")
    && str_contains($js, "return 'contacted'"),
    'Veli kartları randevu ve gerçek iletişim durumuna göre ayrılıyor'
);
$assert(str_contains($css, '@media (max-width: 900px)') || str_contains($css, '@media (max-width: 960px)'), 'CRM görünümü dar ekranlara uyarlanıyor');
$assert(
    str_contains($css, 'grid-auto-columns: calc((100% - 14px) / 2)')
    && str_contains($css, '.waiting-parent-crm-card { min-width: 0;'),
    'Orta ekranlarda iki sütun görünür ve pano sürüklenebilir'
);
$assert(
    str_contains($css, 'grid-auto-flow: column')
    && str_contains($css, 'scroll-snap-type: x mandatory')
    && str_contains($css, 'scroll-snap-stop: always')
    && str_contains($css, 'overscroll-behavior-y: contain'),
    'Mobilde sütunlar yatay kaydırılırken açık sütun kendi içinde dikey kayıyor'
);
$assert(
    str_contains($js, 'initWaitingParentMobileCarousel')
    && str_contains($js, "carousel.addEventListener('touchmove'")
    && str_contains($js, "carousel.addEventListener('pointermove'")
    && str_contains($js, 'event.preventDefault()')
    && str_contains($js, "carousel.scrollTo({ left: columnLeft(items[targetColumn])"),
    'Pano parmakla ve fareyle sürüklenerek diğer sütuna geçiriliyor'
);
$assert(
    str_contains($js, 'initWaitingParentCardDrag')
    && str_contains($js, "carousel.addEventListener('dragstart'")
    && str_contains($js, "carousel.addEventListener('drop'")
    && str_contains($js, "carousel.addEventListener('touchstart'")
    && str_contains($view, 'data-waiting-parent-drop-message'),
    'Kartlar masaüstü ve mobilde sütunlar arasında sürüklenip bırakılabiliyor'
);
$assert(
    str_contains($js, "targetColumn === 'contacted'")
    && str_contains($js, "targetColumn === 'appointment'")
    && str_contains($js, 'data-drag-transition')
    && str_contains($css, '.waiting-parent-kanban-column.is-drop-target'),
    'Bırakılan sütuna göre görüşme veya randevu formu açılıyor'
);
$assert(
    str_contains($css, '.appointment-dialog.waiting-parent-history-dialog.is-drag-flow { position: fixed; inset: 0; width: min(620px')
    && str_contains($css, '.appointment-dialog.quick-appointment-dialog { width: min(680px')
    && str_contains($quickAppointment, 'quick-appointment-dialog'),
    'Sürükleme formları kompakt ve ekran içinde kaydırılabilir açılıyor'
);
$assert(
    str_contains($js, 'data-compact-conversation')
    && str_contains($js, "dialog.classList.toggle('is-drag-flow', compactConversation)"),
    'Kart tıklaması da sağ çekmece yerine ortalanmış kompakt formu açıyor'
);
$assert(
    str_contains($css, '.waiting-parent-drawer { width: min(760px')
    && str_contains($css, 'margin: auto;')
    && str_contains($view, 'waiting-parent-follow-options')
    && str_contains($view, 'rows="3"'),
    'Veli penceresi ortalanıyor ve yalnız temel görüşme alanları açık geliyor'
);
$assert(
    str_contains($js, 'draggable="true" data-waiting-parent-card')
    && !str_contains($js, 'waiting-parent-card-drag-handle')
    && str_contains($css, '.waiting-parent-crm-card:active { cursor: grabbing; }'),
    'Kartın tamamı tutma imleciyle sürükleme alanı olarak kullanılıyor'
);

fwrite(STDOUT, "Bekleyen veli CRM smoke testleri tamamlandı.\n");

