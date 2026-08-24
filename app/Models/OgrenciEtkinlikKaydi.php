<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class OgrenciEtkinlikKaydi extends Model
{
    public static function tabloVarMi(): bool
    {
        $stmt = self::db()->query("SHOW TABLES LIKE 'student_activity_records'");
        if (!$stmt || !$stmt->fetchColumn()) {
            return false;
        }

        foreach (['source_type', 'randevu_id'] as $kolon) {
            $kolonStmt = self::db()->query("SHOW COLUMNS FROM student_activity_records LIKE " . self::db()->quote($kolon));
            if (!$kolonStmt || !$kolonStmt->fetchColumn()) {
                return false;
            }
        }

        return true;
    }

    public static function ogrenciGecmisi(int $ogrenciId): array
    {
        if (!self::tabloVarMi() || !HaftalikTema::tablolarVarMi()) {
            return [];
        }

        $stmt = self::db()->prepare(
            'SELECT sar.id, sar.student_id, sar.activity_id, sar.completed_at, sar.created_at,
                    ta.title AS activity_title,
                    wt.title AS theme_title, wt.week_start, wt.week_end,
                    GROUP_CONCAT(DISTINCT ag.name ORDER BY ag.sort_order ASC SEPARATOR ", ") AS age_groups
             FROM student_activity_records sar
             INNER JOIN theme_activities ta ON ta.id = sar.activity_id
             INNER JOIN weekly_themes wt ON wt.id = ta.theme_id
             LEFT JOIN weekly_theme_age_groups wtag ON wtag.theme_id = wt.id
             LEFT JOIN age_groups ag ON ag.id = wtag.age_group_id
             WHERE sar.student_id = :student_id
             GROUP BY sar.id, sar.student_id, sar.activity_id, sar.completed_at, sar.created_at,
                      ta.title, wt.title, wt.week_start, wt.week_end
             ORDER BY sar.completed_at DESC, sar.id DESC'
        );
        $stmt->execute(['student_id' => $ogrenciId]);

        return $stmt->fetchAll();
    }

    public static function ekle(int $ogrenciId, int $etkinlikId, string $tamamlanmaTarihi): int
    {
        self::tabloGerekli();
        $stmt = self::db()->prepare(
            'INSERT INTO student_activity_records (student_id, activity_id, completed_at, source_type, randevu_id, created_at)
             VALUES (:student_id, :activity_id, :completed_at, "manual", NULL, NOW())
             ON DUPLICATE KEY UPDATE
                completed_at = VALUES(completed_at),
                source_type = "manual",
                randevu_id = NULL'
        );
        $stmt->execute([
            'student_id' => $ogrenciId,
            'activity_id' => $etkinlikId,
            'completed_at' => $tamamlanmaTarihi,
        ]);

        $id = (int) self::db()->lastInsertId();
        if ($id > 0) {
            return $id;
        }

        $bul = self::db()->prepare(
            'SELECT id FROM student_activity_records WHERE student_id = :student_id AND activity_id = :activity_id LIMIT 1'
        );
        $bul->execute(['student_id' => $ogrenciId, 'activity_id' => $etkinlikId]);
        return (int) $bul->fetchColumn();
    }

    public static function randevudanSenkronize(int $randevuId): void
    {
        if (!self::tabloVarMi() || !HaftalikTema::tablolarVarMi()) {
            return;
        }

        $db = self::db();
        $stmt = $db->prepare(
            'SELECT id, ogrenci_id, grup_id, tarih, durum
             FROM randevular
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $randevuId]);
        $randevu = $stmt->fetch();
        if (!$randevu) {
            return;
        }

        $sil = $db->prepare(
            'DELETE FROM student_activity_records
             WHERE randevu_id = :randevu_id
               AND source_type = "auto_group"'
        );
        $sil->execute(['randevu_id' => $randevuId]);

        if (!in_array((string) $randevu['durum'], ['geldi', 'tamamlandi'], true)) {
            return;
        }
        if (empty($randevu['grup_id']) || empty($randevu['tarih'])) {
            return;
        }

        $aktiviteStmt = $db->prepare(
            'SELECT ta.id
             FROM theme_activities ta
             INNER JOIN weekly_themes wt ON wt.id = ta.theme_id
             INNER JOIN theme_activity_groups tag ON tag.activity_id = ta.id
             WHERE tag.group_id = :group_id
               AND :tarih BETWEEN wt.week_start AND wt.week_end
             ORDER BY ta.id ASC'
        );
        $aktiviteStmt->execute([
            'group_id' => (int) $randevu['grup_id'],
            'tarih' => (string) $randevu['tarih'],
        ]);

        $ekle = $db->prepare(
            'INSERT INTO student_activity_records (student_id, activity_id, completed_at, source_type, randevu_id, created_at)
             VALUES (:student_id, :activity_id, :completed_at, "auto_group", :randevu_id, NOW())
             ON DUPLICATE KEY UPDATE
                completed_at = IF(source_type = "auto_group", VALUES(completed_at), completed_at),
                randevu_id = IF(source_type = "auto_group", VALUES(randevu_id), randevu_id)'
        );
        foreach ($aktiviteStmt->fetchAll() as $aktivite) {
            $ekle->execute([
                'student_id' => (int) $randevu['ogrenci_id'],
                'activity_id' => (int) $aktivite['id'],
                'completed_at' => (string) $randevu['tarih'],
                'randevu_id' => $randevuId,
            ]);
        }
    }

    public static function temaIcinSenkronize(int $temaId): void
    {
        if ($temaId < 1 || !self::tabloVarMi() || !HaftalikTema::tablolarVarMi()) {
            return;
        }

        $db = self::db();
        $sil = $db->prepare(
            'DELETE sar
             FROM student_activity_records sar
             INNER JOIN theme_activities ta ON ta.id = sar.activity_id
             WHERE ta.theme_id = :theme_id
               AND sar.source_type = "auto_group"'
        );
        $sil->execute(['theme_id' => $temaId]);

        $stmt = $db->prepare(
            'SELECT DISTINCT r.id
             FROM randevular r
             INNER JOIN weekly_themes wt ON :theme_id = wt.id
             INNER JOIN theme_activities ta ON ta.theme_id = wt.id
             INNER JOIN theme_activity_groups tag ON tag.activity_id = ta.id AND tag.group_id = r.grup_id
             WHERE r.tarih BETWEEN wt.week_start AND wt.week_end
               AND r.durum IN ("geldi", "tamamlandi")'
        );
        $stmt->execute(['theme_id' => $temaId]);
        foreach ($stmt->fetchAll() as $randevu) {
            self::randevudanSenkronize((int) $randevu['id']);
        }
    }

    public static function sil(int $id, int $ogrenciId): bool
    {
        self::tabloGerekli();
        $stmt = self::db()->prepare('DELETE FROM student_activity_records WHERE id = :id AND student_id = :student_id');
        $stmt->execute(['id' => $id, 'student_id' => $ogrenciId]);
        return $stmt->rowCount() > 0;
    }

    private static function tabloGerekli(): void
    {
        if (!self::tabloVarMi()) {
            throw new \RuntimeException('student_activity_records tablosu bulunamadi. Migration calistirilmadan kayit yapilamaz.');
        }
    }
}
