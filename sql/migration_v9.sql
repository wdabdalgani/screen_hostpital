-- v9: update soft_card_medical with hero-intro animation and larger status icon
SET NAMES utf8mb4;

INSERT INTO display_styles (style_key, name, style_type, config_json, css_text, metadata_json, is_system)
VALUES (
  'soft_card_medical',
  'Soft Card Medical (Neumorphism)',
  'card',
  '{"layout":"card","image_behavior":{"doctor":"doctor_hero","department":"department_background"},"overlay":{"gradient":true,"blur":12,"opacity":0.26},"typography":{"name_size":"clamp(1.48rem, 4.5vw, 2.6rem)","spec_size":"clamp(0.98rem, 2.3vw, 1.22rem)","time_size":"clamp(1.02rem, 2.6vw, 1.3rem)","spacing":1.02},"colors":{"primary":"#2fae66","secondary":"#2c7fb8","surface":"#e7edf5","text":"#1f2937"},"animations":{"type":"hero_intro","duration_ms":1300}}',
  '/* Soft Card Medical v2 */ .card-display{background:radial-gradient(80% 60% at 0% 0%,rgba(47,174,102,.16),transparent 58%),radial-gradient(85% 65% at 100% 100%,rgba(44,127,184,.16),transparent 62%),linear-gradient(160deg,#edf3fb 0%,#dbe7f4 100%)} .card-display__card{width:min(92vw,700px);min-height:min(90vh,960px);border-radius:36px;background:linear-gradient(160deg,#eaf1f9,#dde8f5);border:1px solid rgba(255,255,255,.68)} .card-display__hero-img,.card-display__hero-empty{transform-origin:top center;animation:soft-hero-intro 1.35s cubic-bezier(.2,.75,.18,1) forwards} .card-display__info{opacity:0;transform:translateY(18px);animation:soft-info-reveal .65s ease .95s forwards} .card-display__badge svg{width:2.5em;height:2.5em} @keyframes soft-hero-intro{0%{transform:scale(1)}100%{transform:scale(.7)}} @keyframes soft-info-reveal{to{opacity:1;transform:translateY(0)}}',
  '{"version":2,"source":"system","preset":"soft_card_medical"}',
  1
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  style_type = VALUES(style_type),
  config_json = VALUES(config_json),
  css_text = VALUES(css_text),
  metadata_json = VALUES(metadata_json);

INSERT IGNORE INTO schema_migrations (version) VALUES (9);
