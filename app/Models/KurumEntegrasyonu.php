<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\SecretBox;

final class KurumEntegrasyonu extends Model
{
    public static function provider(string $provider, ?int $kurumId = null): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM kurum_entegrasyonlari WHERE kurum_id = :kurum_id AND provider = :provider LIMIT 1');
        $stmt->execute(['kurum_id' => $kurumId ?: self::kurumId(), 'provider' => strtolower($provider)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function mysoft(?int $kurumId = null): ?array
    {
        return self::provider('mysoft', $kurumId);
    }

    public static function etkinMysoft(?int $kurumId = null): ?array
    {
        $row = self::mysoft($kurumId);
        return $row && (int) $row['aktif'] === 1 ? self::kimlikBilgileriniEkle($row) : null;
    }

    public static function nes(?int $kurumId = null): ?array
    {
        return self::provider('nes', $kurumId);
    }

    public static function etkinNes(?int $kurumId = null): ?array
    {
        $row = self::nes($kurumId);
        return $row && (int) $row['aktif'] === 1 ? self::kimlikBilgileriniEkle($row, 'nes') : null;
    }

    public static function aktifMysoftListesi(): array
    {
        return self::db()->query('SELECT kurum_id FROM kurum_entegrasyonlari WHERE provider="mysoft" AND aktif=1 ORDER BY kurum_id')->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function aktifNesListesi(): array
    {
        return self::db()->query('SELECT kurum_id FROM kurum_entegrasyonlari WHERE provider="nes" AND aktif=1 ORDER BY kurum_id')->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function ayarlariGoster(): array
    {
        $config = require BASE_PATH . '/config/mysoft.php';
        $row = self::mysoft() ?: [];
        return [
            'id' => (int) ($row['id'] ?? 0),
            'base_url' => (string) ($row['base_url'] ?? $config['base_url']),
            'ortam' => (string) ($row['ortam'] ?? $config['environment']),
            'tenant_identifier_number' => (string) ($row['tenant_identifier_number'] ?? $config['tenant_identifier']),
            'client_id' => (string) ($row['client_id'] ?? $config['client_id']),
            'client_secret_var' => !empty($row['client_secret_sifreli']) || trim((string) $config['client_secret']) !== '',
            'efatura_prefix' => (string) ($row['efatura_prefix'] ?? ''),
            'earsiv_prefix' => (string) ($row['earsiv_prefix'] ?? ''),
            'numerator_set_code' => (string) ($row['numerator_set_code'] ?? ''),
            'gb_alias' => (string) ($row['gb_alias'] ?? ''),
            'varsayilan_efatura_profili' => (string) ($row['varsayilan_efatura_profili'] ?? 'TEMELFATURA'),
            'aktif' => (int) ($row['aktif'] ?? 0),
            'otomatik_fatura' => (int) ($row['otomatik_fatura'] ?? 0),
        ];
    }

    public static function nesAyarlariGoster(): array
    {
        $config = require BASE_PATH . '/config/nes.php';
        $row = self::nes() ?: [];
        return [
            'id' => (int) ($row['id'] ?? 0),
            'base_url' => (string) ($row['base_url'] ?? $config['base_url']),
            'ortam' => (string) ($row['ortam'] ?? $config['environment']),
            'tenant_identifier_number' => (string) ($row['tenant_identifier_number'] ?? ''),
            'api_key_var' => !empty($row['client_secret_sifreli']) || trim((string) $config['api_key']) !== '',
            'firma_unvani' => (string) ($row['firma_unvani'] ?? ''),
            'vergi_dairesi' => (string) ($row['vergi_dairesi'] ?? ''),
            'adres_basligi' => (string) ($row['adres_basligi'] ?? ''),
            'adres' => (string) ($row['adres'] ?? ''),
            'il' => (string) ($row['il'] ?? ''),
            'ilce' => (string) ($row['ilce'] ?? ''),
            'posta_kodu' => (string) ($row['posta_kodu'] ?? ''),
            'ulke' => (string) ($row['ulke'] ?? 'TÜRKİYE'),
            'eposta' => (string) ($row['eposta'] ?? ''),
            'telefon' => (string) ($row['telefon'] ?? ''),
            'web_sitesi' => (string) ($row['web_sitesi'] ?? ''),
            'efatura_prefix' => (string) ($row['efatura_prefix'] ?? ''),
            'earsiv_prefix' => (string) ($row['earsiv_prefix'] ?? ''),
            'gb_alias' => (string) ($row['gb_alias'] ?? ''),
            'varsayilan_efatura_profili' => (string) ($row['varsayilan_efatura_profili'] ?? 'TEMELFATURA'),
            'aktif' => (int) ($row['aktif'] ?? 0),
        ];
    }

    public static function nesKaydet(array $data): int
    {
        $mevcut = self::nes();
        $apiKey = trim((string) ($data['api_key'] ?? ''));
        $encrypted = $apiKey !== '' ? SecretBox::encrypt($apiKey) : ($mevcut['client_secret_sifreli'] ?? null);
        $params = [
            'kurum_id'=>self::kurumId(),'base_url'=>rtrim(trim((string)$data['base_url']),'/'),'ortam'=>trim((string)$data['ortam']),
            'tenant'=>preg_replace('/\D+/','',(string)$data['tenant_identifier_number']),'secret'=>$encrypted,
            'firma_unvani'=>trim((string)($data['firma_unvani']??'')),'vergi_dairesi'=>trim((string)($data['vergi_dairesi']??''))?:null,
            'adres_basligi'=>trim((string)($data['adres_basligi']??''))?:null,'adres'=>trim((string)($data['adres']??'')),'il'=>trim((string)($data['il']??'')),'ilce'=>trim((string)($data['ilce']??'')),
            'posta_kodu'=>trim((string)($data['posta_kodu']??''))?:null,'ulke'=>trim((string)($data['ulke']??'TÜRKİYE'))?:'TÜRKİYE','eposta'=>trim((string)($data['eposta']??''))?:null,
            'telefon'=>trim((string)($data['telefon']??''))?:null,'web_sitesi'=>trim((string)($data['web_sitesi']??''))?:null,
            'efatura_prefix'=>strtoupper(trim((string)($data['efatura_prefix']??'')))?:null,'earsiv_prefix'=>strtoupper(trim((string)($data['earsiv_prefix']??'')))?:null,
            'gb_alias'=>trim((string)($data['gb_alias']??''))?:null,'profil'=>(string)($data['varsayilan_efatura_profili']??'TEMELFATURA'),'aktif'=>(int)($data['aktif']??0)===1?1:0,
        ];
        $stmt=self::db()->prepare('INSERT INTO kurum_entegrasyonlari (kurum_id,provider,base_url,ortam,tenant_identifier_number,client_secret_sifreli,firma_unvani,vergi_dairesi,adres_basligi,adres,il,ilce,posta_kodu,ulke,eposta,telefon,web_sitesi,efatura_prefix,earsiv_prefix,gb_alias,varsayilan_efatura_profili,aktif) VALUES (:kurum_id,"nes",:base_url,:ortam,:tenant,:secret,:firma_unvani,:vergi_dairesi,:adres_basligi,:adres,:il,:ilce,:posta_kodu,:ulke,:eposta,:telefon,:web_sitesi,:efatura_prefix,:earsiv_prefix,:gb_alias,:profil,:aktif) ON DUPLICATE KEY UPDATE base_url=VALUES(base_url),ortam=VALUES(ortam),tenant_identifier_number=VALUES(tenant_identifier_number),client_secret_sifreli=VALUES(client_secret_sifreli),firma_unvani=VALUES(firma_unvani),vergi_dairesi=VALUES(vergi_dairesi),adres_basligi=VALUES(adres_basligi),adres=VALUES(adres),il=VALUES(il),ilce=VALUES(ilce),posta_kodu=VALUES(posta_kodu),ulke=VALUES(ulke),eposta=VALUES(eposta),telefon=VALUES(telefon),web_sitesi=VALUES(web_sitesi),efatura_prefix=VALUES(efatura_prefix),earsiv_prefix=VALUES(earsiv_prefix),gb_alias=VALUES(gb_alias),varsayilan_efatura_profili=VALUES(varsayilan_efatura_profili),aktif=VALUES(aktif)');
        $stmt->execute($params);
        return (int)($mevcut['id']??self::db()->lastInsertId());
    }

    public static function kaydet(array $data): int
    {
        $mevcut = self::mysoft();
        $secret = trim((string) ($data['client_secret'] ?? ''));
        $secretEncrypted = $secret !== '' ? SecretBox::encrypt($secret) : ($mevcut['client_secret_sifreli'] ?? null);
        $params = [
            'kurum_id' => self::kurumId(),
            'base_url' => rtrim(trim((string) $data['base_url']), '/'),
            'ortam' => trim((string) $data['ortam']),
            'tenant' => preg_replace('/\D+/', '', (string) $data['tenant_identifier_number']),
            'client_id' => trim((string) $data['client_id']) ?: null,
            'secret' => $secretEncrypted,
            'efatura_prefix' => strtoupper(trim((string) ($data['efatura_prefix'] ?? ''))) ?: null,
            'earsiv_prefix' => strtoupper(trim((string) ($data['earsiv_prefix'] ?? ''))) ?: null,
            'numerator_set_code' => trim((string) ($data['numerator_set_code'] ?? '')) ?: null,
            'gb_alias' => trim((string) ($data['gb_alias'] ?? '')) ?: null,
            'profil' => (string) ($data['varsayilan_efatura_profili'] ?? 'TEMELFATURA'),
            'aktif' => (int) ($data['aktif'] ?? 0) === 1 ? 1 : 0,
            'otomatik' => (int) ($data['otomatik_fatura'] ?? 0) === 1 ? 1 : 0,
        ];
        $stmt = self::db()->prepare(
            'INSERT INTO kurum_entegrasyonlari
             (kurum_id, provider, base_url, ortam, tenant_identifier_number, client_id, client_secret_sifreli, efatura_prefix, earsiv_prefix, numerator_set_code, gb_alias, varsayilan_efatura_profili, aktif, otomatik_fatura)
             VALUES (:kurum_id, "mysoft", :base_url, :ortam, :tenant, :client_id, :secret, :efatura_prefix, :earsiv_prefix, :numerator_set_code, :gb_alias, :profil, :aktif, :otomatik)
             ON DUPLICATE KEY UPDATE base_url=VALUES(base_url), ortam=VALUES(ortam), tenant_identifier_number=VALUES(tenant_identifier_number), client_id=VALUES(client_id), client_secret_sifreli=VALUES(client_secret_sifreli), efatura_prefix=VALUES(efatura_prefix), earsiv_prefix=VALUES(earsiv_prefix), numerator_set_code=VALUES(numerator_set_code), gb_alias=VALUES(gb_alias), varsayilan_efatura_profili=VALUES(varsayilan_efatura_profili), aktif=VALUES(aktif), otomatik_fatura=VALUES(otomatik_fatura)'
        );
        $stmt->execute($params);
        return (int) ($mevcut['id'] ?? self::db()->lastInsertId());
    }

    private static function kimlikBilgileriniEkle(array $row, string $provider = 'mysoft'): array
    {
        $config = require BASE_PATH . '/config/' . $provider . '.php';
        $row['client_id_resolved'] = trim((string) ($row['client_id'] ?? '')) ?: (string) ($config['client_id'] ?? '');
        $row['client_secret_resolved'] = !empty($row['client_secret_sifreli'])
            ? SecretBox::decrypt((string) $row['client_secret_sifreli'])
            : ($provider === 'nes' ? $config['api_key'] : $config['client_secret']);
        return $row;
    }
}
