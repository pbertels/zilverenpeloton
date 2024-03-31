<?php


ini_set("pcre.jit", "0");
require_once './settings.php';
require_once './ZPController.php';
require_once './vendor/pbertels/smartwebsite/src/Database.php';

$database = SmartWebsite\Database::setParameters($DB_SERVERNAME, $DB_DATABASE, $DB_USERNAME, $DB_PASSWORD);

$status = ZilverenPeloton\ZPController::getStatus($_GET['code']);

header('Content-Type: application/json');
$status['acties'] = array_values($status['acties']);
echo \json_encode($status);
