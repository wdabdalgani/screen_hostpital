-- v11: Dynamic Display Content System
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS content_groups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(191) NOT NULL,
  loop_enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_content_group_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS display_contents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id INT UNSIGNED NOT NULL,
  name VARCHAR(191) NOT NULL,
  content_type ENUM('image','video','gif') NOT NULL DEFAULT 'image',
  file_path VARCHAR(512) NOT NULL,
  department_id INT UNSIGNED DEFAULT NULL,
  duration_seconds TINYINT UNSIGNED NOT NULL DEFAULT 8,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_content_group (group_id),
  KEY idx_content_department (department_id),
  CONSTRAINT fk_content_group FOREIGN KEY (group_id) REFERENCES content_groups (id) ON DELETE CASCADE,
  CONSTRAINT fk_content_department FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE screens ADD COLUMN display_mode ENUM('doctors','content') NOT NULL DEFAULT 'doctors' AFTER display_style;
ALTER TABLE screens ADD COLUMN content_group_id INT UNSIGNED NULL AFTER display_mode;
ALTER TABLE screens ADD CONSTRAINT fk_screens_content_group FOREIGN KEY (content_group_id) REFERENCES content_groups (id) ON DELETE SET NULL;

INSERT IGNORE INTO schema_migrations (version) VALUES (11);
