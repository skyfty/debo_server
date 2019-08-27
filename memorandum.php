<?php
ini_set("memory_limit","-1");
chdir("/www/web/debo/public_html");
$_GET['g'] = "Appapi";
$_GET['m'] = "Memorandum";
$_GET['a'] = "complete_memorandum";
require 'index.php';