<?php
require_once __DIR__ . '/config.php';

session_unset();
session_destroy();
session_start();
set_flash('flash_success', 'You have been logged out.');
redirect('/event_project/index.php');
?>