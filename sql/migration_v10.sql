-- v10: enforce soft_card_medical portrait/small-screen doctor image to fixed 70vh
SET NAMES utf8mb4;

INSERT INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
VALUES (
  'soft_card_medical',
  'Soft Card Medical (Neumorphism)',
  'card',
  '{"layout":"card","image_behavior":{"doctor":"doctor_hero","department":"department_background"},"overlay":{"gradient":true,"blur":12,"opacity":0.26},"typography":{"name_size":"clamp(1.48rem, 4.5vw, 2.6rem)","spec_size":"clamp(0.98rem, 2.3vw, 1.22rem)","time_size":"clamp(1.02rem, 2.6vw, 1.3rem)","spacing":1.02},"colors":{"primary":"#2fae66","secondary":"#2c7fb8","surface":"#e7edf5","text":"#1f2937"},"animations":{"type":"hero_intro","duration_ms":1300}}',
  '/* Soft Card Medical v3 */ .card-display__hero-img,.card-display__hero-empty{width:min(100%,460px);height:clamp(38vh,46vh,54vh);animation:soft-hero-intro 1.35s cubic-bezier(.2,.75,.18,1) forwards} @media (max-width:900px),(orientation:portrait){.card-display__hero-img,.card-display__hero-empty{width:100%;max-width:none;height:70vh;animation:none!important;transform:none!important}} .card-display__badge svg{width:2.5em;height:2.5em}',
  '{"version":3,"source":"system","preset":"soft_card_medical"}',
  1
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  style_type = VALUES(style_type),
  config_json = VALUES(config_json),
  css_text = VALUES(css_text),
  metadata_json = VALUES(metadata_json);

INSERT IGNORE INTO schema_migrations (version) VALUES (10);
