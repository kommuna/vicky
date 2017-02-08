<?php
namespace Vicky;

use PhpSlackBot\Bot;
use Vicky\bot\modules\MyCommand;
use Vicky\bot\modules\ToUserWebhook;
use Vicky\bot\modules\ToChannelWebhook;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/botConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$bot = new Bot();
$bot->setToken($config['botToken']);
$bot->loadCommand(new MyCommand());
$bot->loadInternalCommands();

$bot->loadInternalWebhooks();
$bot->loadWebhook(new ToUserWebhook());
$bot->loadWebhook(new ToChannelWebhook());
$bot->enableWebserver(8080, $config['botAuth']);
$bot->run();
