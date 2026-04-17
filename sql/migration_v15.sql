-- v15: welcome broadcast overlay (all display screens)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS welcome_broadcast (
  id TINYINT UNSIGNED NOT NULL DEFAULT 1,
  is_enabled TINYINT(1) NOT NULL DEFAULT 0,
  show_logo TINYINT(1) NOT NULL DEFAULT 1,
  title VARCHAR(255) NOT NULL DEFAULT '',
  subtitle VARCHAR(512) NOT NULL DEFAULT '',
  image_path VARCHAR(512) DEFAULT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO welcome_broadcast (id) VALUES (1);

INSERT IGNORE INTO schema_migrations (version) VALUES (15);
