<?php
session_start();
session_destroy();
header('Location: /wanderstays/index.php');
exit;
