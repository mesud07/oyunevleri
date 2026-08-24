CREATE TABLE IF NOT EXISTS theme_group_presets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theme_group_presets_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_group_preset_groups (
    preset_id BIGINT UNSIGNED NOT NULL,
    group_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (preset_id, group_id),
    CONSTRAINT fk_theme_group_preset_groups_preset
        FOREIGN KEY (preset_id) REFERENCES theme_group_presets (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_theme_group_preset_groups_group
        FOREIGN KEY (group_id) REFERENCES gruplar (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
