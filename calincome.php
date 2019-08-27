<?php
ini_set("memory_limit","-1");
chdir("/www/web/debo/public_html");
$_GET['g'] = "Appapi";
$_GET['m'] = "Pay";
$_GET['a'] = "calculation_income";
require 'index.php';