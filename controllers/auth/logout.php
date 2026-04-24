<?php
// controllers/auth/logout.php

session_start();

// Destroy session
session_unset();
session_destroy();

// Prevent back button caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to home
header('Location: ../../home.php');
exit;