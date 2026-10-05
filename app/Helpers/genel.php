<?php

declare(strict_types=1);

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return substr($haystack, -strlen($needle)) === $needle;
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function aktif_menu(string $aktif, string $beklenen): string
{
    return $aktif === $beklenen ? ' is-active' : '';
}

function yetki_var(string $yetki): bool
{
    return (new \App\Services\YetkiServisi())->izinliMi($yetki);
}

function fatura_durum_goster(?string $durum): string
{
    return match ((string) $durum) {
        'BOS' => 'Bekliyor',
        'TASLAK', 'DRAFT', 'Draft', 'taslak' => 'Taslak',
        'Waiting', 'WaitingSign', 'WaitingToBeSigned', 'WaitingToBeEnvelopeCreate', 'WaitingToBeSend',
        'EnvelopeIsWaitingToBeProcessedByGib', 'EnvelopeIsWaitingToBeSendedToReceiverByGib',
        'EnvelopeIsBeenWaitingToBeTransferredToReceiverByGib', 'EnvelopeSystemResponseIsBeingWaitedFromReceiver' => 'İşleniyor',
        'Signed', 'Succeed', 'EnvelopeHasBeenTransferredToReceiverSuccessfully' => 'Başarılı',
        'Error', 'XmlFileDoesNotExist', 'XmlParseError', 'FileNotFound', 'UnknownStatus' => 'Hatalı',
        'ARSIV_KAYIT_KUYRUGUNDA', 'GIBE_GONDERILECEK', 'KABUL_KUYRUGUNDA', 'RED_KUYRUGUNDA', 'YANIT_BEKLENIYOR' => 'İşleniyor',
        'GIBE_GONDERILDI' => 'Gönderildi',
        'ALICIYA_ULASTI', 'KABUL', 'ONAYLANDI' => 'Başarılı',
        'RED', 'reddedildi' => 'Reddedildi',
        'HATA', 'hatali' => 'Hatalı',
        'IPTAL_EDILDI', 'Canceled', 'Cancelled', 'canceled', 'cancelled', 'iptal' => 'İptal',
        'olusturuluyor' => 'Oluşturuluyor',
        'gonderildi' => 'Gönderildi',
        'isleniyor' => 'İşleniyor',
        'basarili' => 'Başarılı',
        default => $durum ?: 'Bekliyor',
    };
}
