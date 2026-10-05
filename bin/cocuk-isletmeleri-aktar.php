<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\CocukIsletmesiAktarimServisi;

try {
    $adet = (new CocukIsletmesiAktarimServisi())->aktar();
    fwrite(STDOUT, "{$adet} çocuk işletmesi yerel veritabanına aktarıldı.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Aktarım başarısız: {$e->getMessage()}\n");
    exit(1);
}
