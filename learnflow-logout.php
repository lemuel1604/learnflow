<?php
session_start();
session_unset();
session_destroy();

// Clear theme cookie
setcookie('theme', '', time() - 3600, '/');

header('Location: learnflow-login.php');
exit;
