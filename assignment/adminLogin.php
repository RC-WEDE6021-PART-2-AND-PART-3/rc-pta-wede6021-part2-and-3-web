<?php
// Admin login is now unified with user login via role-based access control
// Users with 'admin' role in tblUser are granted admin privileges
// Redirect to main login page
session_start();
header("Location: login.php");
exit;