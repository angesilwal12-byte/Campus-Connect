<?php
session_start();
session_destroy();
header("Location: /Campus_Connect/auth/login.php");
exit;