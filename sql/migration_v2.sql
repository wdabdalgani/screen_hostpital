-- ترقية يدوية من v1 إلى v2 (إن لم يُنفَّذ تلقائياً عند فتح الموقع)
-- نفّذ من phpMyAdmin أو mysql CLI بعد النسخ الاحتياطي.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version INT UNSIGNED NOT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ثم شغّل بقية الجداول من schema.sql أو افتح الموقع مرة واحدة ليتم الترحيل تلقائياً عبر includes/migrations.php

SET FOREIGN_KEY_CHECKS = 1;
