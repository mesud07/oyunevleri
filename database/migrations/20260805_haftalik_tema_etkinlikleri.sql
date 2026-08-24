CREATE TABLE IF NOT EXISTS age_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_age_groups_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO age_groups (name, sort_order, created_at, updated_at) VALUES
('18-24 Ay', 10, NOW(), NOW()),
('25-36 Ay', 20, NOW(), NOW()),
('37-48 Ay', 30, NOW(), NOW())
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), updated_at = NOW();

CREATE TABLE IF NOT EXISTS weekly_themes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_weekly_themes_week_start (week_start),
    KEY idx_weekly_themes_week_end (week_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS weekly_theme_age_groups (
    theme_id BIGINT UNSIGNED NOT NULL,
    age_group_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (theme_id, age_group_id),
    CONSTRAINT fk_weekly_theme_age_groups_theme
        FOREIGN KEY (theme_id) REFERENCES weekly_themes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_weekly_theme_age_groups_age
        FOREIGN KEY (age_group_id) REFERENCES age_groups (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_activity_templates_active (is_active),
    UNIQUE KEY uq_activity_templates_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO activity_templates (title, description, is_active, created_at, updated_at) VALUES
('Parmak Boyasi', NULL, 1, NOW(), NOW()),
('Kesme ve Yapistirma', NULL, 1, NOW(), NOW()),
('Duyusal Oyun', NULL, 1, NOW(), NOW()),
('Renk Eslestirme', NULL, 1, NOW(), NOW()),
('Nesne Eslestirme', NULL, 1, NOW(), NOW()),
('Puzzle Calismasi', NULL, 1, NOW(), NOW()),
('Muzik ve Ritim', NULL, 1, NOW(), NOW()),
('Hikaye Zamani', NULL, 1, NOW(), NOW()),
('Blok Calismasi', NULL, 1, NOW(), NOW()),
('Serbest Boyama', NULL, 1, NOW(), NOW()),
('Renk Avi', NULL, 1, NOW(), NOW()),
('Doku Kesfi', NULL, 1, NOW(), NOW()),
('Buyuk-Kucuk Eslestirme', NULL, 1, NOW(), NOW()),
('Dolu-Bos Kavrami', NULL, 1, NOW(), NOW()),
('Ince Motor Calismasi', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE is_active = VALUES(is_active), updated_at = NOW();

CREATE TABLE IF NOT EXISTS theme_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_id BIGINT UNSIGNED NOT NULL,
    activity_template_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_theme_activities_theme (theme_id),
    KEY idx_theme_activities_template (activity_template_id),
    CONSTRAINT fk_theme_activities_theme
        FOREIGN KEY (theme_id) REFERENCES weekly_themes (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_theme_activities_template
        FOREIGN KEY (activity_template_id) REFERENCES activity_templates (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_activity_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    activity_id BIGINT UNSIGNED NOT NULL,
    completed_at DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_activity_records_student_activity (student_id, activity_id),
    KEY idx_student_activity_records_completed (completed_at),
    KEY idx_student_activity_records_activity (activity_id),
    CONSTRAINT fk_student_activity_records_student
        FOREIGN KEY (student_id) REFERENCES ogrenciler (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_activity_records_activity
        FOREIGN KEY (activity_id) REFERENCES theme_activities (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO rol_yetkileri (rol_id, yetki)
SELECT id, 'tema_yonet'
FROM roller
WHERE kod IN ('kurucu', 'yonetici', 'ogretmen');
