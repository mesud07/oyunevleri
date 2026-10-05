<?php

declare(strict_types=1);

namespace App\Services;

final class NesEndpointGuvenligi
{
    private const HOSTS = [
        'production' => 'api.nes.com.tr',
        'test' => 'apitest.nes.com.tr',
    ];

    public static function dogrula(string $url, string $ortam): string
    {
        $url = rtrim(trim($url), '/');
        $parcalar = parse_url($url);
        $ortam = strtolower(trim($ortam));
        $beklenenHost = self::HOSTS[$ortam] ?? null;

        if (!is_array($parcalar)
            || ($parcalar['scheme'] ?? '') !== 'https'
            || strtolower((string) ($parcalar['host'] ?? '')) !== $beklenenHost
            || isset($parcalar['user'])
            || isset($parcalar['pass'])
            || isset($parcalar['port'])
            || isset($parcalar['query'])
            || isset($parcalar['fragment'])
            || !in_array((string) ($parcalar['path'] ?? ''), ['', '/'], true)) {
            throw new \InvalidArgumentException('NES servis adresi seçilen ortama ait resmi NES adresi olmalıdır.');
        }

        return 'https://' . $beklenenHost;
    }
}
