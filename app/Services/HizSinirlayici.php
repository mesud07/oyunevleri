<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Veritabani;
use DateTimeImmutable;
use PDO;

final class HizSinirlayici
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Veritabani::baglan();
    }

    public function engelliMi(string $scope, int $limit, int $windowSeconds): array
    {
        $row = $this->find($this->hash($scope));
        if (!$row) {
            return ['engelli' => false, 'kalan_saniye' => 0];
        }

        $now = new DateTimeImmutable();
        $blockedUntil = !empty($row['blocked_until']) ? new DateTimeImmutable((string) $row['blocked_until']) : null;
        if ($blockedUntil && $blockedUntil > $now) {
            return ['engelli' => true, 'kalan_saniye' => max(1, $blockedUntil->getTimestamp() - $now->getTimestamp())];
        }

        $windowStart = new DateTimeImmutable((string) $row['window_started_at']);
        $windowExpired = ($now->getTimestamp() - $windowStart->getTimestamp()) >= $windowSeconds;
        return ['engelli' => !$windowExpired && (int) $row['attempts'] >= $limit, 'kalan_saniye' => 0];
    }

    public function kaydet(string $scope, int $limit, int $windowSeconds, int $blockSeconds): array
    {
        $hash = $this->hash($scope);
        $now = new DateTimeImmutable();

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM hiz_sinirlari WHERE scope_hash = :scope_hash FOR UPDATE');
            $stmt->execute(['scope_hash' => $hash]);
            $row = $stmt->fetch();

            if (!$row) {
                $attempts = 1;
                $windowStart = $now;
            } else {
                $windowStart = new DateTimeImmutable((string) $row['window_started_at']);
                if (($now->getTimestamp() - $windowStart->getTimestamp()) >= $windowSeconds) {
                    $attempts = 1;
                    $windowStart = $now;
                } else {
                    $attempts = (int) $row['attempts'] + 1;
                }
            }

            $blockedUntil = $attempts >= $limit ? $now->modify('+' . $blockSeconds . ' seconds') : null;
            $stmt = $this->db->prepare(
                'INSERT INTO hiz_sinirlari (scope_hash, attempts, window_started_at, blocked_until, updated_at)
                 VALUES (:scope_hash, :attempts, :window_started_at, :blocked_until, NOW())
                 ON DUPLICATE KEY UPDATE attempts=VALUES(attempts), window_started_at=VALUES(window_started_at),
                                         blocked_until=VALUES(blocked_until), updated_at=NOW()'
            );
            $stmt->execute([
                'scope_hash' => $hash,
                'attempts' => $attempts,
                'window_started_at' => $windowStart->format('Y-m-d H:i:s'),
                'blocked_until' => $blockedUntil?->format('Y-m-d H:i:s'),
            ]);
            $this->db->commit();

            return [
                'engelli' => $blockedUntil !== null,
                'kalan_saniye' => $blockedUntil ? $blockSeconds : 0,
                'deneme' => $attempts,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function temizle(string $scope): void
    {
        $stmt = $this->db->prepare('DELETE FROM hiz_sinirlari WHERE scope_hash = :scope_hash');
        $stmt->execute(['scope_hash' => $this->hash($scope)]);
    }

    private function find(string $hash): ?array
    {
        $stmt = $this->db->prepare('SELECT attempts, window_started_at, blocked_until FROM hiz_sinirlari WHERE scope_hash = :scope_hash LIMIT 1');
        $stmt->execute(['scope_hash' => $hash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function hash(string $scope): string
    {
        $key = (string) Config::get('APP_KEY', 'talya-rate-limit-local-key');
        return hash_hmac('sha256', $scope, $key);
    }
}
