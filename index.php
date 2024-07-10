<?php
header("Location: mainTemp.php");
session_start();

$failed = false;
$_SESSION['loggedIn'] = false;