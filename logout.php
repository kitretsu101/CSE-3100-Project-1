<?php
require_once __DIR__ . '/auth.php';
logout_member();
header('Location: login.php');
exit;
