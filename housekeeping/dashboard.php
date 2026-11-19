<?php

require_once __DIR__ . '/../includes/auth_session.php';
require_once __DIR__ . '/../includes/database.php';

$user = authRequireRoles($pdo, ['Housekeeping'], '../pages/login.php', '../pages/auth_account.php');
$dashboardTitle = 'Housekeeping Dashboard';
$dashboardDescription = 'View assigned room tasks and coordinate room readiness.';
$dashboardSections = ['Assigned housekeeping tasks', 'Room readiness'];

require __DIR__ . '/../includes/dashboard_layout.php';
