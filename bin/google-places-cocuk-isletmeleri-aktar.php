<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\GooglePlacesCocukIsletmesiAktarimServisi;

try {
    $adet = (new GooglePlacesCocukIsletmesiAktarimServisi())->aktar();
    fwrite(STDOUT, "{$adet} Google Places çocuk işletmesi 30 günlük yerel önbelleğe aktarıldı.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Aktarım başarısız: {$e->getMessage()}\n");
    exit(1);
}
