<?php
// Redirect old members page to profile page
require_once __DIR__ . '/auth.php';
header('Location: profile.php');
exit;
