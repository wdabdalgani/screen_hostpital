<?php
declare(strict_types=1);

/** @var string $adminPageTitle */
/** @var string $adminNav dashboard|screens|ready_templates|display_styles|display_content|welcome_screen|doctors|departments|hospital|account */
/** @var string $adminMainClass optional main element classes */

require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once __DIR__ . '/icons.php';
require_once dirname(__DIR__, 2) . '/includes/hospital.php';

$pdo = db();
$hs = hospital_settings($pdo);
$adminProfile = current_admin($pdo);
$adminUser = isset($_SESSION['admin_user']) ? (string) $_SESSION['admin_user'] : 'مسؤول';
$displayName = trim((string) ($adminProfile['full_name'] ?? '')) !== '' ? (string) $adminProfile['full_name'] : $adminUser;
$initial = function_exists('mb_substr') ? mb_substr($displayName, 0, 1) : substr($displayName, 0, 1);
$avatarUrl = !empty($adminProfile['photo_path']) ? url('uploads/admin/' . basename((string) $adminProfile['photo_path'])) : null;
$sidebarBrand = trim((string) ($hs['name'] ?? '')) !== '' ? (string) $hs['name'] : 'مواعيد الأطباء';

?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($adminPageTitle) ?> — لوحة التحكم</title>
  <link rel="stylesheet" href="<?= esc(url('assets/css/admin.css')) ?>?v=7">
</head>
<body class="admin-body admin-app">
  <div class="saas-backdrop" aria-hidden="true"></div>
  <div class="saas-shell">
    <aside class="saas-sidebar" id="saasSidebar">
      <div class="saas-sidebar__brand">
        <span class="saas-sidebar__logo" aria-hidden="true"><?= ic_spark() ?></span>
        <div class="saas-sidebar__titles">
          <span class="saas-sidebar__name"><?= esc($sidebarBrand) ?></span>
          <span class="saas-sidebar__tag">لوحة التحكم</span>
        </div>
      </div>
      <nav class="saas-sidebar__nav" aria-label="التنقل الرئيسي">
        <a class="saas-navlink <?= $adminNav === 'dashboard' ? 'is-active' : '' ?>" href="<?= esc(url('admin/dashboard.php')) ?>">
          <?= ic_dashboard() ?>
          <span>الرئيسية</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'screens' ? 'is-active' : '' ?>" href="<?= esc(url('admin/screens.php')) ?>">
          <?= ic_monitor() ?>
          <span>الشاشات</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'ready_templates' ? 'is-active' : '' ?>" href="<?= esc(url('admin/ready_templates.php')) ?>">
          <?= ic_spark() ?>
          <span>قوالب جاهزة</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'display_styles' ? 'is-active' : '' ?>" href="<?= esc(url('admin/display_styles.php')) ?>">
          <?= ic_spark() ?>
          <span>استايلات العرض</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'display_content' ? 'is-active' : '' ?>" href="<?= esc(url('admin/display_content.php')) ?>">
          <?= ic_media() ?>
          <span>محتوى الشاشات</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'welcome_screen' ? 'is-active' : '' ?>" href="<?= esc(url('admin/welcome_screen.php')) ?>">
          <?= ic_spark() ?>
          <span>شاشة الترحيب</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'doctors' ? 'is-active' : '' ?>" href="<?= esc(url('admin/doctors.php')) ?>">
          <?= ic_users() ?>
          <span>إدارة الأطباء</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'departments' ? 'is-active' : '' ?>" href="<?= esc(url('admin/departments.php')) ?>">
          <?= ic_folder_dept() ?>
          <span>الأقسام</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'hospital' ? 'is-active' : '' ?>" href="<?= esc(url('admin/hospital.php')) ?>">
          <?= ic_building() ?>
          <span>بيانات المستشفى</span>
        </a>
        <a class="saas-navlink <?= $adminNav === 'account' ? 'is-active' : '' ?>" href="<?= esc(url('admin/account.php')) ?>">
          <?= ic_user_circle() ?>
          <span>حسابي</span>
        </a>
      </nav>
      <div class="saas-sidebar__bottom">
        <a class="saas-navlink saas-navlink--muted" href="<?= esc(url('admin/logout.php')) ?>">
          <?= ic_logout() ?>
          <span>تسجيل الخروج</span>
        </a>
      </div>
    </aside>

    <div class="saas-layout">
      <header class="saas-topbar">
        <button type="button" class="saas-topbar__menu" id="saasMenuBtn" aria-expanded="false" aria-controls="saasSidebar" aria-label="فتح القائمة">
          <?= ic_menu() ?>
        </button>
        <div class="saas-topbar__crumb">
          <h1 class="saas-topbar__title"><?= esc($adminPageTitle) ?></h1>
          <p class="saas-topbar__hint">نظام عرض مواعيد الأطباء</p>
        </div>
        <div class="saas-topbar__tools">
          <label class="saas-search" aria-label="بحث">
            <?= ic_search() ?>
            <input type="search" class="saas-search__input" placeholder="بحث سريع…" autocomplete="off" disabled title="قريباً">
          </label>
          <a class="saas-user saas-user--link" href="<?= esc(url('admin/account.php')) ?>">
            <?php if ($avatarUrl): ?>
              <img class="saas-user__avatar-img" src="<?= esc($avatarUrl) ?>" width="38" height="38" alt="">
            <?php else: ?>
              <span class="saas-user__avatar" aria-hidden="true"><?= esc($initial) ?></span>
            <?php endif; ?>
            <span class="saas-user__meta">
              <span class="saas-user__name"><?= esc($displayName) ?></span>
              <span class="saas-user__role">مسؤول</span>
            </span>
          </a>
        </div>
      </header>

      <main class="<?= esc($adminMainClass ?? 'saas-main') ?>">
