-- v5: Style Export/Import system
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS display_styles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  style_key VARCHAR(64) NOT NULL,
  name VARCHAR(191) NOT NULL,
  style_type VARCHAR(32) NOT NULL DEFAULT 'custom',
  config_json LONGTEXT NOT NULL,
  css_text LONGTEXT DEFAULT NULL,
  metadata_json LONGTEXT DEFAULT NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_display_style_key (style_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES (5);
