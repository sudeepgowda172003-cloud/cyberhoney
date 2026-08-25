<?php
require_once __DIR__ . '/auth.php';
if (Auth::check()) { header('Location: dashboard.php'); exit; }
header('Location: login.php');
