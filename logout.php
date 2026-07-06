<?php
require_once __DIR__ . '/portal_auth.php';
logoutUser();
header('Location: client-portal.php');
exit;
