-- v7: add out-of-the-box premium split style preset
SET NAMES utf8mb4;

INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
VALUES (
  'aurora_split_signature',
  'Aurora Split Signature',
  'split',
  '{"layout":"split","image_behavior":{"doctor":"doctor_hero","department":"department_background"},"overlay":{"gradient":true,"blur":20,"opacity":0.5},"typography":{"name_size":"clamp(1.8rem, 5.5vw, 3.8rem)","spec_size":"clamp(1rem, 2.5vw, 1.35rem)","time_size":"clamp(1.05rem, 2.9vw, 1.45rem)","spacing":1.08},"colors":{"primary":"#2fae66","secondary":"#2c7fb8","surface":"#081425","text":"#eaf3ff"},"animations":{"type":"fade","duration_ms":1000}}',
  '/* Aurora Split Signature */ .card-display{background:radial-gradient(100% 80% at 0% 0%,rgba(47,174,102,.18),transparent 62%),radial-gradient(90% 80% at 100% 100%,rgba(44,127,184,.23),transparent 60%),#050b15}.card-display__card{width:min(98vw,1500px);min-height:min(92vh,980px);border-radius:30px;background:linear-gradient(120deg,rgba(8,20,38,.92),rgba(11,28,50,.92));border:1px solid rgba(170,210,255,.18);box-shadow:0 32px 90px rgba(2,6,23,.58),inset 0 0 0 1px rgba(255,255,255,.04)}',
  '{"version":1,"source":"system","preset":"aurora_split_signature"}',
  1
);

INSERT IGNORE INTO schema_migrations (version) VALUES (7);
