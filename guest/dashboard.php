<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';

$user = authRequireRoles($pdo, ['Guest'], '../pages/login.php', '../pages/auth_account.php');
$dashboardTitle = 'Guest Dashboard';
$dashboardDescription = 'Manage your stay and view your reservation information.';
$dashboardSections = ['My reservations', 'Account information'];

require __DIR__ . '/../includes/dashboard_layout.php';
