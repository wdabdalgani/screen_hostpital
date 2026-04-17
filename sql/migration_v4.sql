-- v4: دعم عدة استايلات لكل شاشة
SET NAMES utf8mb4;

ALTER TABLE screens
  ADD COLUMN display_style VARCHAR(32) NOT NULL DEFAULT 'hero_medical' AFTER refresh_seconds;

INSERT IGNORE INTO schema_migrations (version) VALUES (4);
