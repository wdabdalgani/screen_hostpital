-- v6: add immersive premium fullscreen preset style
SET NAMES utf8mb4;

INSERT IGNORE INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
VALUES (
  'immersive_signature',
  'Immersive Medical Signature',
  'hero',
  '{"layout":"hero","image_behavior":{"doctor":"doctor_hero","department":"department_background"},"overlay":{"gradient":true,"blur":30,"opacity":0.48},"typography":{"name_size":"clamp(1.9rem, 6.2vw, 4rem)","spec_size":"clamp(1rem, 2.7vw, 1.45rem)","time_size":"clamp(1.05rem, 3.1vw, 1.55rem)","spacing":1.05},"colors":{"primary":"#2fae66","secondary":"#2c7fb8","surface":"#0a1628","text":"#ffffff"},"animations":{"type":"fade","duration_ms":950}}',
  '.cinematic-stack__ambient-img { filter: blur(34px) saturate(1.12); opacity: .44; } .cinematic-stack__photo { object-position: center 16%; filter: saturate(1.05) contrast(1.03); } .cinematic-name { letter-spacing: .01em; text-shadow: 0 3px 24px rgba(0, 0, 0, .62); } .cinematic-row--time { background: rgba(8, 16, 28, .52); border: 1px solid rgba(255, 255, 255, .28); } .cinematic-stack--ok .cinematic-status-line { background: linear-gradient(90deg, #2fae66, rgba(47, 174, 102, .55)); } .cinematic-stack--no .cinematic-status-line { background: linear-gradient(90deg, #334155, rgba(71, 85, 105, .72)); }',
  '{"version":1,"source":"system","preset":"immersive_signature"}',
  1
);

INSERT IGNORE INTO schema_migrations (version) VALUES (6);
