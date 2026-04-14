<?php
session_start();
session_destroy();
header('Location: /budget_planning_system/index.php');
exit;
