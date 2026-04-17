-- v13: dashboard time-series snapshots for advanced charts
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS dashboard_stat_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  available_count INT UNSIGNED NOT NULL DEFAULT 0,
  unavailable_count INT UNSIGNED NOT NULL DEFAULT 0,
  availability_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY idx_snapshot_captured_at (captured_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations (version) VALUES (13);
