<?php
namespace Vicky;

use Vicky\client\modules\BlockersIssueFile;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/clientConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$fileClient = new BlockersIssueFile($config['pathToBlockerFile']);

$fileClient->run();