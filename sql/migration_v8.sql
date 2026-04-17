-- v8: add soft card medical (neumorphism) preset style
SET NAMES utf8mb4;

INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
VALUES (
  'soft_card_medical',
  'Soft Card Medical (Neumorphism)',
  'card',
  '{"layout":"card","image_behavior":{"doctor":"doctor_hero","department":"department_background"},"overlay":{"gradient":true,"blur":12,"opacity":0.26},"typography":{"name_size":"clamp(1.4rem, 4.2vw, 2.4rem)","spec_size":"clamp(0.95rem, 2.2vw, 1.2rem)","time_size":"clamp(1rem, 2.5vw, 1.25rem)","spacing":1},"colors":{"primary":"#2fae66","secondary":"#2c7fb8","surface":"#e7edf5","text":"#1f2937"},"animations":{"type":"fade","duration_ms":850}}',
  '/* Soft Card Medical */ .card-display{background:radial-gradient(80% 60% at 0% 0%,rgba(47,174,102,.16),transparent 58%),radial-gradient(85% 65% at 100% 100%,rgba(44,127,184,.16),transparent 62%),linear-gradient(160deg,#edf3fb 0%,#dbe7f4 100%)} .card-display__card{width:min(92vw,640px);min-height:min(86vh,900px);border-radius:34px;background:linear-gradient(160deg,#eaf1f9,#dde8f5);border:1px solid rgba(255,255,255,.65);box-shadow:16px 16px 34px rgba(158,176,198,.42),-14px -14px 30px rgba(255,255,255,.95),inset 0 1px 0 rgba(255,255,255,.7)}',
  '{"version":1,"source":"system","preset":"soft_card_medical"}',
  1
);

INSERT IGNORE INTO schema_migrations (version) VALUES (8);
