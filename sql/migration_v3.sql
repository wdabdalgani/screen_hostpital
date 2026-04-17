-- v3: صورة خلفية لكل قسم (شاشة العرض)
SET NAMES utf8mb4;

ALTER TABLE departments ADD COLUMN banner_image_path VARCHAR(512) NULL AFTER icon;

-- مجلد الرفع: uploads/departments/ (يُنشأ تلقائياً عبر الترحيل البرمجي)

INSERT IGNORE INTO schema_migrations (version) VALUES (3);
