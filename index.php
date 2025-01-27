<?php

namespace ZilverenPeloton;

ini_set("pcre.jit", "0");
require_once './settings.php';
require_once './ZPController.php';
require_once './vendor/autoload.php';

use PhpWebsite\PhpWebsite;
use PhpWebsite\SmartMailer;
use PhpWebsite\StatisticsController;
use PhpWebsite\Database;

$database = Database::setParameters($DB_SERVERNAME, $DB_DATABASE, $DB_USERNAME, $DB_PASSWORD);

$site = new PhpWebsite('Zilveren Peoloton', false);
$site->setMailer(new SmartMailer());
$site->setLanguages(array('nl'));
$site->setDomain('zilverenpeloton.be', 'zlvrnpltn.local');
$site->setSubDir('/', '/');
$site->setStaticContentDir('./content/');
$site->setTemplateDir('./template/');

$stats = new StatisticsController($site, 'zlvrnpltn_stats');
$site->setStatisticsController($stats);


$site->setSectionSeparators(
    array(
        '!<hr />!',
        '!<hr class="fullwidth([^"]*)" />!',
        '!<hr class="([^"]*)" />!',
    ),
    array(
        '</div></div><div class="section"><div class="container">',
        '</div></div><div class="section fullwidth${1}"><div class="container fullwidth${1}">',
        '</div></div><div class="section ${1}"><div class="container">',
    )
);
$site->registerURL('', array('cijfers' => false, 'secret' => false), new ZPController(), 'all');
$site->registerURL('overzicht', array('cijfers' => true, 'secret' => false), new ZPController(), 'all');
$site->registerURL('videos', array('cijfers' => true, 'secret' => true), new ZPController(), 'videoOverview');
$site->registerURL('TgJ1wy6vHMSvdtbC', array('cijfers' => true, 'secret' => true), new ZPController(), 'all');
$site->registerURL('([a-z\-0-9]+)', array('code' => 0, 'domain' => $site->getAbsoluteURL('', true)), new ZPController(), 'video');
$site->registerURL('([a-z\-0-9]+)/admin/([a-z\-0-9]+)', array('code' => 0, 'pass' => 1, 'mode' => 'admin'), new ZPController(), 'overview');
$site->registerURL('([a-z\-0-9]+)/input/([a-z\-0-9]+)', array('code' => 0, 'pass' => 1, 'domain' => $site->getAbsoluteURL('', true)), new ZPController(), 'input');
$site->registerURL('([a-z\-0-9]+)/delete/([a-z\-0-9]+)', array('code' => 0, 'pass' => 1, 'domain' => $site->getAbsoluteURL('', true)), new ZPController(), 'delete');
$site->registerURL('([a-z\-0-9]+)/changevideo/([a-z\-0-9]+)/([a-z\-0-9]+)', array('code' => 0, 'pass' => 1, 'video' => 2), new ZPController(), 'changeVideo');

$site->go();

exit;
