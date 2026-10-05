<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

final class GozlemRaporuPdfServisi
{
    private const BOLUMLER = [
        'genel_gozlem' => 'Genel Gözlem',
        'etkinliklere_katilim' => 'Etkinliklere Katılımı',
        'yonerge_ve_grup_uyumu' => 'Yönerge ve Grup Uyumu',
        'cocuk_gelisimci_gorusu' => 'Çocuk Gelişimci Görüşü',
        'psikolojik_danisman_gorusu' => 'Psikolojik Danışman Görüşü',
        'genel_degerlendirme' => 'Genel Değerlendirme',
    ];

    public function olustur(array $rapor): string
    {
        if (!class_exists(Dompdf::class)) {
            throw new \RuntimeException('PDF kutuphanesi kurulu degil. Sunucuda composer install calistirilmalidir.');
        }

        $rapor['bolum_basliklari'] = self::BOLUMLER;
        $sablon = BASE_PATH . '/resources/views/pdf/gozlem-raporu.php';
        ob_start();
        require $sablon;
        $html = (string) ob_get_clean();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('chroot', BASE_PATH);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(255, 812, 'Sayfa {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.38, 0.45, 0.53]);

        return $dompdf->output();
    }
}
