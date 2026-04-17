<?php
declare(strict_types=1);

/**
 * App configuration — adjust for your environment.
 */
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'u653871828_db_hariri',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'base_url' => '', // e.g. '/harri' if app is in subfolder; empty = auto-detect
    'session_name' => 'harri_admin',
    'upload_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'doctors',
    'upload_departments_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'departments',
    'upload_content_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content',
    'upload_hospital_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'hospital',
    'upload_welcome_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'welcome',
    'upload_admin_dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'admin',
    'upload_max_kb' => 4096,
    'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
    'allowed_content_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm'],
];
