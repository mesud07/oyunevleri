<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class HaftalikTema extends Model
{
    public static function tablolarVarMi(): bool
    {
        foreach (['age_groups', 'weekly_themes', 'weekly_theme_age_groups'] as $tablo) {
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
                    GROUP_CONCAT(DISTINCT ag.name ORDER BY ag.sort_order ASC SEPARATOR ", ") AS age_groups
             FROM weekly_themes wt
             LEFT JOIN weekly_theme_age_groups wtag ON wtag.theme_id = wt.id AND wtag.kurum_id = wt.kurum_id
             LEFT JOIN age_groups ag ON ag.id = wtag.age_group_id
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
        return $stmt->fetchAll();
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

    public static function ogrenciTemaTakibi(string $baslangic, string $bitis, string $katilim = 'tumu'): array
    {
        if (!self::tablolarVarMi()) {
            return [];
        }

        $durumlar = match ($katilim) {
            'geldi' => ['geldi', 'tamamlandi'],
            'gelmedi' => ['gelmedi', 'mazeretli_gelmedi', 'gec_iptal'],
            'bekliyor' => ['planlandi', 'ertelendi'],
            'iptal' => ['kurum_iptali'],
            default => [],
        };
        $where = [
            'r.kurum_id = :kurum_id',
            'r.tarih BETWEEN :baslangic AND :bitis',
        ];
        $params = [
            'kurum_id' => self::kurumId(),
            'baslangic' => $baslangic,
            'bitis' => $bitis,
        ];
        if ($durumlar) {
            $yerTutucular = [];
            foreach ($durumlar as $index => $durum) {
                $anahtar = 'durum_' . $index;
                $yerTutucular[] = ':' . $anahtar;
                $params[$anahtar] = $durum;
            }
            $where[] = 'r.durum IN (' . implode(', ', $yerTutucular) . ')';
        }

        $stmt = self::db()->prepare(
            'SELECT r.id AS randevu_id, r.ogrenci_id, r.tarih, r.baslangic_saati, r.bitis_saati,
                    r.durum, r.tur, o.dogum_tarihi,
                    CONCAT(o.ad, " ", o.soyad) AS ogrenci,
                    COALESCE(g.ad, "Cocuk Etkinlik ve Oyun Evi") AS grup,
                    wt.id AS tema_id, wt.title AS tema, wt.description AS tema_aciklamasi,
                    wt.week_start, wt.week_end,
                    (SELECT GROUP_CONCAT(DISTINCT ag.name ORDER BY ag.sort_order ASC SEPARATOR ", ")
                       FROM weekly_theme_age_groups wtag
                       INNER JOIN age_groups ag ON ag.id = wtag.age_group_id
                      WHERE wtag.theme_id = wt.id AND wtag.kurum_id = wt.kurum_id) AS age_groups
             FROM randevular r
             INNER JOIN ogrenciler o ON o.id = r.ogrenci_id AND o.kurum_id = r.kurum_id
             INNER JOIN weekly_themes wt ON wt.kurum_id = r.kurum_id
                  AND r.tarih BETWEEN wt.week_start AND wt.week_end
             LEFT JOIN gruplar g ON g.id = r.grup_id AND g.kurum_id = r.kurum_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY r.tarih DESC, r.baslangic_saati DESC, o.ad ASC, o.soyad ASC, wt.title ASC
             LIMIT 2000'
        );
        $stmt->execute($params);

        $sonuc = [];
        foreach ($stmt->fetchAll() as $row) {
            if (!self::yasGrubunaUygunMu((string) ($row['dogum_tarihi'] ?? ''), (string) $row['tarih'], (string) ($row['age_groups'] ?? ''))) {
                continue;
            }
            $row['tema_durumu'] = self::temaDurumu((string) $row['durum']);
            $sonuc[] = $row;
        }
        return $sonuc;
    }

    public static function temaDurumu(string $randevuDurumu): string
    {
        if (in_array($randevuDurumu, ['geldi', 'tamamlandi'], true)) {
            return 'islendi';
        }
        if (in_array($randevuDurumu, ['gelmedi', 'mazeretli_gelmedi', 'gec_iptal', 'kurum_iptali'], true)) {
            return 'islenmedi';
        }
        return 'bekliyor';
    }

    private static function yasGrubunaUygunMu(string $dogumTarihi, string $randevuTarihi, string $yasGruplari): bool
    {
        if ($dogumTarihi === '' || $yasGruplari === '') {
            return true;
        }
        try {
            $dogum = new \DateTimeImmutable($dogumTarihi);
            $randevu = new \DateTimeImmutable($randevuTarihi);
            if ($dogum > $randevu) {
                return false;
            }
            $fark = $dogum->diff($randevu);
            $ay = ((int) $fark->y * 12) + (int) $fark->m;
        } catch (\Throwable $e) {
            return true;
        }

        $aralikBulundu = false;
        foreach (explode(',', $yasGruplari) as $yasGrubu) {
            if (preg_match('/(\d+)\s*-\s*(\d+)/u', $yasGrubu, $eslesme) !== 1) {
                continue;
            }
            $aralikBulundu = true;
            if ($ay >= (int) $eslesme[1] && $ay <= (int) $eslesme[2]) {
                return true;
            }
        }
        return !$aralikBulundu;
    }

    private static function tabloVarMi(string $tablo): bool
    {
        $stmt = self::db()->query("SHOW TABLES LIKE " . self::db()->quote($tablo));
        return $stmt && (bool) $stmt->fetchColumn();
    }

    private static function tablolarGerekli(): void
    {
        if (!self::tablolarVarMi()) {
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
