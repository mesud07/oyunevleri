<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

final class VeliOnamPdfServisi
{
    public function olustur(array $kayit): string
    {
        if (!class_exists(Dompdf::class)) {
            throw new \RuntimeException('PDF kütüphanesi kurulu değil.');
        }
        $sablon = BASE_PATH . '/resources/views/pdf/veli-onam.php';
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
        return $dompdf->output();
    }
}
