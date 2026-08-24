<?php

declare(strict_types=1);

function tarih_goster(?string $tarih): string
{
    if (!$tarih) {
        return '-';
    }
    $zaman = strtotime($tarih);
    return $zaman ? date('d.m.Y', $zaman) : $tarih;
}
