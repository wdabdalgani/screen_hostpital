-- v16: minimal_clear display style (simple layout + large availability icons)
SET NAMES utf8mb4;

INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system) VALUES (
  'minimal_clear',
  'بسيط وواضح (صورة كاملة + متاح/غير متاح)',
  'minimal',
  '{"layout":"minimal","image_behavior":{"doctor":"contain","department":"none"},"overlay":{"gradient":false,"blur":0,"opacity":0},"typography":{"name_size":"clamp(1.8rem, 5.8vw, 3.1rem)","spec_size":"clamp(1.08rem, 3.1vw, 1.55rem)","time_size":"clamp(1rem, 2.8vw, 1.2rem)","spacing":1.08},"colors":{"primary":"#22c55e","secondary":"#f87171","surface":"#0f172a","text":"#f8fafc"},"animations":{"type":"fade","duration_ms":850}}',
  '',
  '{"version":1,"source":"system","preset":"minimal_clear"}',
  1
);

INSERT IGNORE INTO schema_migrations (version) VALUES (16);
