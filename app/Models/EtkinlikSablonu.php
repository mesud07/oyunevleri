<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class EtkinlikSablonu extends Model
{
    public static function tabloVarMi(): bool
    {
        $stmt = self::db()->query("SHOW TABLES LIKE 'activity_templates'");
        return $stmt && (bool) $stmt->fetchColumn();
    }

    public static function liste(bool $sadeceAktif = false): array
    {
        if (!self::tabloVarMi()) {
            return [];
        }

        $sql = 'SELECT id, title, description, is_active, created_at, updated_at FROM activity_templates WHERE kurum_id = :kurum_id';
        if ($sadeceAktif) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY is_active DESC, title ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function kaydet(int $id, array $veri): int
    {
        self::tabloGerekli();
        if ($id > 0) {
            $stmt = self::db()->prepare(
                'UPDATE activity_templates
                 SET title = :title, description = :description, is_active = :is_active, updated_at = NOW()
                 WHERE id = :id AND kurum_id = :kurum_id'
            );
            $stmt->execute([
                'id' => $id,
                'kurum_id' => self::kurumId(),
                'title' => $veri['title'],
                'description' => $veri['description'] ?: null,
                'is_active' => (int) ($veri['is_active'] ?? 1),
            ]);
            return $id;
        }

        $stmt = self::db()->prepare(
            'INSERT INTO activity_templates (kurum_id, title, description, is_active, created_at, updated_at)
             VALUES (:kurum_id, :title, :description, :is_active, NOW(), NOW())'
        );
        $stmt->execute([
            'kurum_id' => self::kurumId(),
            'title' => $veri['title'],
            'description' => $veri['description'] ?: null,
            'is_active' => (int) ($veri['is_active'] ?? 1),
        ]);

        return (int) self::db()->lastInsertId();
    }

    public static function durumDegistir(int $id, bool $aktif): bool
    {
        self::tabloGerekli();
        $stmt = self::db()->prepare('UPDATE activity_templates SET is_active = :aktif, updated_at = NOW() WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'aktif' => $aktif ? 1 : 0, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    public static function sil(int $id): bool
    {
        self::tabloGerekli();
        $stmt = self::db()->prepare('DELETE FROM activity_templates WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    private static function tabloGerekli(): void
    {
        if (!self::tabloVarMi()) {
            throw new \RuntimeException('activity_templates tablosu bulunamadi. Migration calistirilmadan kayit yapilamaz.');
        }
    }
}
