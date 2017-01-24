<?php
namespace Vicky;

use Vicky\client\models\SlackWebhookSender;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : 'config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$sender = new SlackWebhookSender(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

//$sender->toChannel('#general', 'To channel from php!');
//$sender->toChannel('#privatetry', 'To private channel from php!');
$sender->toUser('chewbacca', 'To user from php!');