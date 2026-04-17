-- v12: link display content item to doctor
SET NAMES utf8mb4;

ALTER TABLE display_contents ADD COLUMN doctor_id INT UNSIGNED NULL AFTER department_id;
ALTER TABLE display_contents ADD KEY idx_content_doctor (doctor_id);
ALTER TABLE display_contents ADD CONSTRAINT fk_content_doctor FOREIGN KEY (doctor_id) REFERENCES doctors (id) ON DELETE SET NULL;

INSERT IGNORE INTO schema_migrations (version) VALUES (12);
