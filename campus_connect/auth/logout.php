<?php

session_start();

$_SESSION = [];

session_destroy();

header("Location: /Campus_Connect/auth/login.php");
exit;