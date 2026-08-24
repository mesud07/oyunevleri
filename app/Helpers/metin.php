<?php

declare(strict_types=1);

function kisalt(string $metin, int $uzunluk = 120): string
{
    $metin = trim($metin);
    if (mb_strlen($metin) <= $uzunluk) {
        return $metin;
    }
    return mb_substr($metin, 0, $uzunluk - 3) . '...';
}
