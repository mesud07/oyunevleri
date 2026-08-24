<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class HaftalikTema extends Model
{
    public static function tablolarVarMi(): bool
    {
        foreach (['age_groups', 'weekly_themes', 'weekly_theme_age_groups', 'theme_activities', 'theme_activity_groups'] as $tablo) {
            $stmt = self::db()->query("SHOW TABLES LIKE " . self::db()->quote($tablo));
            if (!$stmt || !$stmt->fetchColumn()) {
                return false;
            }
        }

        return true;
    }

    public static function yasGruplari(): array
    {
        if (!self::tabloVarMi('age_groups')) {
            return [];
        }

        $stmt = self::db()->prepare('SELECT id, name FROM age_groups ORDER BY sort_order ASC, id ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function grupSecimSablonlari(): array
    {
        if (!self::tabloVarMi('theme_group_presets') || !self::tabloVarMi('theme_group_preset_groups')) {
            return [];
        }

        $stmt = self::db()->prepare(
            'SELECT tgp.id, tgp.title, tgp.created_at, tgp.updated_at,
                    GROUP_CONCAT(DISTINCT g.ad ORDER BY g.ad ASC SEPARATOR ", ") AS groups
             FROM theme_group_presets tgp
             LEFT JOIN theme_group_preset_groups tgpg ON tgpg.preset_id = tgp.id AND tgpg.kurum_id = tgp.kurum_id
             LEFT JOIN gruplar g ON g.id = tgpg.group_id AND g.kurum_id = tgp.kurum_id
             WHERE tgp.kurum_id = :kurum_id
             GROUP BY tgp.id
             ORDER BY tgp.title ASC'
        );
        $stmt->execute(self::kurumParam());
        $sablonlar = $stmt->fetchAll();

        $grupStmt = self::db()->prepare(
            'SELECT preset_id, group_id
             FROM theme_group_preset_groups
             WHERE kurum_id = :kurum_id
             ORDER BY preset_id ASC, group_id ASC'
        );
        $grupStmt->execute(self::kurumParam());
        $gruplar = [];
        foreach ($grupStmt->fetchAll() as $row) {
            $gruplar[(int) $row['preset_id']][] = (int) $row['group_id'];
        }

        foreach ($sablonlar as &$sablon) {
            $sablon['group_ids'] = $gruplar[(int) $sablon['id']] ?? [];
        }
        unset($sablon);

        return $sablonlar;
    }

    public static function grupSecimTablolariVarMi(): bool
    {
        return self::tabloVarMi('theme_group_presets') && self::tabloVarMi('theme_group_preset_groups');
    }

    public static function grupSecimKaydet(int $id, array $veri): int
    {
        self::grupSecimTablolariGerekli();
        $db = self::db();

        try {
            $db->beginTransaction();
            if ($id > 0) {
                $stmt = $db->prepare(
                    'UPDATE theme_group_presets
                     SET title = :title, updated_at = NOW()
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $stmt->execute(['id' => $id, 'title' => $veri['title'], 'kurum_id' => self::kurumId()]);
                $presetId = $id;
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO theme_group_presets (kurum_id, title, created_at, updated_at)
                     VALUES (:kurum_id, :title, NOW(), NOW())'
                );
                $stmt->execute(['kurum_id' => self::kurumId(), 'title' => $veri['title']]);
                $presetId = (int) $db->lastInsertId();
            }

            $db->prepare('DELETE FROM theme_group_preset_groups WHERE preset_id = :preset_id AND kurum_id = :kurum_id')
                ->execute(['preset_id' => $presetId, 'kurum_id' => self::kurumId()]);
            $ekle = $db->prepare(
                'INSERT INTO theme_group_preset_groups (kurum_id, preset_id, group_id)
                 VALUES (:kurum_id, :preset_id, :group_id)'
            );
            foreach ($veri['group_ids'] as $grupId) {
                $ekle->execute(['kurum_id' => self::kurumId(), 'preset_id' => $presetId, 'group_id' => (int) $grupId]);
            }

            $db->commit();
            return $presetId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function grupSecimSil(int $id): bool
    {
        self::grupSecimTablolariGerekli();
        $stmt = self::db()->prepare('DELETE FROM theme_group_presets WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    public static function liste(): array
    {
        if (!self::tablolarVarMi()) {
            return [];
        }

        $stmt = self::db()->prepare(
            'SELECT wt.id, wt.title, wt.description, wt.week_start, wt.week_end,
                    COUNT(DISTINCT ta.id) AS activity_count,
                    GROUP_CONCAT(DISTINCT ag.name ORDER BY ag.sort_order ASC SEPARATOR ", ") AS age_groups,
                    GROUP_CONCAT(DISTINCT g.ad ORDER BY g.ad ASC SEPARATOR ", ") AS groups
             FROM weekly_themes wt
             LEFT JOIN weekly_theme_age_groups wtag ON wtag.theme_id = wt.id AND wtag.kurum_id = wt.kurum_id
             LEFT JOIN age_groups ag ON ag.id = wtag.age_group_id
             LEFT JOIN theme_activities ta ON ta.theme_id = wt.id AND ta.kurum_id = wt.kurum_id
             LEFT JOIN theme_activity_groups tag ON tag.activity_id = ta.id AND tag.kurum_id = wt.kurum_id
             LEFT JOIN gruplar g ON g.id = tag.group_id AND g.kurum_id = wt.kurum_id
             WHERE wt.kurum_id = :kurum_id
             GROUP BY wt.id
             ORDER BY wt.week_start DESC, wt.id DESC'
        );
        $stmt->execute(self::kurumParam());
        return $stmt->fetchAll();
    }

    public static function secenekler(): array
    {
        if (!self::tablolarVarMi()) {
            return [];
        }

        $stmt = self::db()->prepare(
            'SELECT wt.id, wt.title, wt.week_start, wt.week_end,
                    GROUP_CONCAT(DISTINCT ag.name ORDER BY ag.sort_order ASC SEPARATOR ", ") AS age_groups
             FROM weekly_themes wt
             LEFT JOIN weekly_theme_age_groups wtag ON wtag.theme_id = wt.id AND wtag.kurum_id = wt.kurum_id
             LEFT JOIN age_groups ag ON ag.id = wtag.age_group_id
             WHERE wt.kurum_id = :kurum_id
             GROUP BY wt.id
             ORDER BY wt.week_start DESC, wt.title ASC'
        );
        $stmt->execute(self::kurumParam());
        $temalar = $stmt->fetchAll();

        $etkinlikStmt = self::db()->prepare(
            'SELECT id, theme_id, title, description
             FROM theme_activities
             WHERE kurum_id = :kurum_id
             ORDER BY id ASC'
        );
        $etkinlikStmt->execute(self::kurumParam());
        $etkinlikler = [];
        foreach ($etkinlikStmt->fetchAll() as $etkinlik) {
            $etkinlikler[(int) $etkinlik['theme_id']][] = $etkinlik;
        }

        foreach ($temalar as &$tema) {
            $tema['activities'] = $etkinlikler[(int) $tema['id']] ?? [];
        }
        unset($tema);

        return $temalar;
    }

    public static function detay(int $id): ?array
    {
        if (!self::tablolarVarMi()) {
            return null;
        }

        $stmt = self::db()->prepare(
            'SELECT id, title, description, week_start, week_end, created_at, updated_at
             FROM weekly_themes
             WHERE id = :id
               AND kurum_id = :kurum_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $tema = $stmt->fetch();
        if (!$tema) {
            return null;
        }

        $yasStmt = self::db()->prepare('SELECT age_group_id FROM weekly_theme_age_groups WHERE theme_id = :id AND kurum_id = :kurum_id');
        $yasStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $tema['age_group_ids'] = array_map('intval', array_column($yasStmt->fetchAll(), 'age_group_id'));

        $etkinlikStmt = self::db()->prepare(
            'SELECT id, theme_id, activity_template_id, title, description
             FROM theme_activities
             WHERE theme_id = :id
               AND kurum_id = :kurum_id
             ORDER BY id ASC'
        );
        $etkinlikStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $etkinlikler = $etkinlikStmt->fetchAll();

        $grupStmt = self::db()->prepare(
            'SELECT tag.activity_id, tag.group_id
             FROM theme_activity_groups tag
             INNER JOIN theme_activities ta ON ta.id = tag.activity_id AND ta.kurum_id = tag.kurum_id
             WHERE ta.theme_id = :id
               AND ta.kurum_id = :kurum_id'
        );
        $grupStmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        $gruplar = [];
        foreach ($grupStmt->fetchAll() as $row) {
            $gruplar[(int) $row['activity_id']][] = (int) $row['group_id'];
        }
        foreach ($etkinlikler as &$etkinlik) {
            $etkinlik['group_ids'] = $gruplar[(int) $etkinlik['id']] ?? [];
        }
        unset($etkinlik);

        $tema['activities'] = $etkinlikler;

        return $tema;
    }

    public static function kaydet(int $id, array $veri): int
    {
        self::tablolarGerekli();
        $db = self::db();

        try {
            $db->beginTransaction();

            if ($id > 0) {
                $stmt = $db->prepare(
                    'UPDATE weekly_themes
                     SET title = :title, description = :description, week_start = :week_start,
                         week_end = :week_end, updated_at = NOW()
                     WHERE id = :id AND kurum_id = :kurum_id'
                );
                $stmt->execute([
                    'id' => $id,
                    'kurum_id' => self::kurumId(),
                    'title' => $veri['title'],
                    'description' => $veri['description'] ?: null,
                    'week_start' => $veri['week_start'],
                    'week_end' => $veri['week_end'],
                ]);
                $temaId = $id;
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO weekly_themes (kurum_id, title, description, week_start, week_end, created_at, updated_at)
                     VALUES (:kurum_id, :title, :description, :week_start, :week_end, NOW(), NOW())'
                );
                $stmt->execute([
                    'kurum_id' => self::kurumId(),
                    'title' => $veri['title'],
                    'description' => $veri['description'] ?: null,
                    'week_start' => $veri['week_start'],
                    'week_end' => $veri['week_end'],
                ]);
                $temaId = (int) $db->lastInsertId();
            }

            $db->prepare('DELETE FROM weekly_theme_age_groups WHERE theme_id = :theme_id AND kurum_id = :kurum_id')
                ->execute(['theme_id' => $temaId, 'kurum_id' => self::kurumId()]);
            $yasEkle = $db->prepare('INSERT INTO weekly_theme_age_groups (kurum_id, theme_id, age_group_id) VALUES (:kurum_id, :theme_id, :age_group_id)');
            foreach ($veri['age_group_ids'] as $yasId) {
                $yasEkle->execute(['kurum_id' => self::kurumId(), 'theme_id' => $temaId, 'age_group_id' => (int) $yasId]);
            }

            $mevcutIdler = [];
            $guncelle = $db->prepare(
                'UPDATE theme_activities
                 SET activity_template_id = :activity_template_id, title = :title, description = :description, updated_at = NOW()
                 WHERE id = :id AND theme_id = :theme_id AND kurum_id = :kurum_id'
            );
            $ekle = $db->prepare(
                'INSERT INTO theme_activities (kurum_id, theme_id, activity_template_id, title, description, created_at, updated_at)
                 VALUES (:kurum_id, :theme_id, :activity_template_id, :title, :description, NOW(), NOW())'
            );

            foreach ($veri['activities'] as $etkinlik) {
                $etkinlikId = (int) ($etkinlik['id'] ?? 0);
                $params = [
                    'kurum_id' => self::kurumId(),
                    'theme_id' => $temaId,
                    'activity_template_id' => !empty($etkinlik['activity_template_id']) ? (int) $etkinlik['activity_template_id'] : null,
                    'title' => $etkinlik['title'],
                    'description' => $etkinlik['description'] ?: null,
                ];
                if ($etkinlikId > 0) {
                    $guncelle->execute($params + ['id' => $etkinlikId]);
                    $mevcutIdler[] = $etkinlikId;
                } else {
                    $ekle->execute($params);
                    $mevcutIdler[] = (int) $db->lastInsertId();
                }

                $sonEtkinlikId = end($mevcutIdler);
                $db->prepare('DELETE FROM theme_activity_groups WHERE activity_id = :activity_id AND kurum_id = :kurum_id')
                    ->execute(['activity_id' => (int) $sonEtkinlikId, 'kurum_id' => self::kurumId()]);
                $grupEkle = $db->prepare('INSERT INTO theme_activity_groups (kurum_id, activity_id, group_id) VALUES (:kurum_id, :activity_id, :group_id)');
                foreach (($etkinlik['group_ids'] ?? []) as $grupId) {
                    $grupEkle->execute(['kurum_id' => self::kurumId(), 'activity_id' => (int) $sonEtkinlikId, 'group_id' => (int) $grupId]);
                }
            }

            if ($id > 0) {
                if ($mevcutIdler) {
                    $yerTutucu = implode(',', array_fill(0, count($mevcutIdler), '?'));
                    $db->prepare("DELETE FROM theme_activities WHERE kurum_id = ? AND theme_id = ? AND id NOT IN ($yerTutucu)")
                        ->execute(array_merge([self::kurumId(), $temaId], $mevcutIdler));
                } else {
                    $db->prepare('DELETE FROM theme_activities WHERE theme_id = :theme_id AND kurum_id = :kurum_id')
                        ->execute(['theme_id' => $temaId, 'kurum_id' => self::kurumId()]);
                }
            }

            OgrenciEtkinlikKaydi::temaIcinSenkronize($temaId);

            $db->commit();
            return $temaId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function sil(int $id): bool
    {
        self::tablolarGerekli();
        $stmt = self::db()->prepare('DELETE FROM weekly_themes WHERE id = :id AND kurum_id = :kurum_id');
        $stmt->execute(['id' => $id, 'kurum_id' => self::kurumId()]);
        return $stmt->rowCount() > 0;
    }

    private static function tabloVarMi(string $tablo): bool
    {
        $stmt = self::db()->query("SHOW TABLES LIKE " . self::db()->quote($tablo));
        return $stmt && (bool) $stmt->fetchColumn();
    }

    private static function tablolarGerekli(): void
    {
        if (!self::tablolarVarMi() || !EtkinlikSablonu::tabloVarMi()) {
            throw new \RuntimeException('Tema tablolari bulunamadi. Migration calistirilmadan kayit yapilamaz.');
        }
    }

    private static function grupSecimTablolariGerekli(): void
    {
        if (!self::tabloVarMi('theme_group_presets') || !self::tabloVarMi('theme_group_preset_groups')) {
            throw new \RuntimeException('Grup secim sablonu tablolari bulunamadi. Migration calistirilmadan kayit yapilamaz.');
        }
    }
}
