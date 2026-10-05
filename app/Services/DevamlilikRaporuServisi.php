<?php

declare(strict_types=1);

namespace App\Services;

final class DevamlilikRaporuServisi
{
    public const GELISIM_TESTI_BEKLEME_SURESI = 'P1M';

    public static function gelisimTestineUygunMu(?string $ilkPaketBaslangicTarihi, ?\DateTimeImmutable $bugun = null): bool
    {
        $kalanGun = self::gelisimTestineKalanGun($ilkPaketBaslangicTarihi, $bugun);
        return $kalanGun === 0;
    }

    public static function gelisimTestineKalanGun(?string $ilkPaketBaslangicTarihi, ?\DateTimeImmutable $bugun = null): ?int
    {
        if (!$ilkPaketBaslangicTarihi) {
            return null;
        }

        try {
            $baslangic = new \DateTimeImmutable($ilkPaketBaslangicTarihi);
        } catch (\Throwable $e) {
            return null;
        }

        $bugun ??= new \DateTimeImmutable('today');
        $esikTarihi = $baslangic->add(new \DateInterval(self::GELISIM_TESTI_BEKLEME_SURESI));
        if ($esikTarihi <= $bugun) {
            return 0;
        }

        return (int) $bugun->diff($esikTarihi)->format('%a');
    }

    public static function pasifOgrenciMi(?string $sonPaketTarihi, ?\DateTimeImmutable $bugun = null): bool
    {
        if (!$sonPaketTarihi) {
            return false;
        }

        $paketTarihi = \DateTimeImmutable::createFromFormat('!Y-m-d', $sonPaketTarihi);
        if (!$paketTarihi) {
            return false;
        }

        $bugun = ($bugun ?? new \DateTimeImmutable('today'))->setTime(0, 0);
        return $paketTarihi->modify('+7 days') < $bugun;
    }
}
