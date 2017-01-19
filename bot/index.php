<?php
namespace Slack_project;

use PhpSlackBot\Bot;
use Slack_project\bot\models\MyCommand;
use Slack_project\bot\models\ToUserHook;
use Slack_project\bot\models\ToChannelHook;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : 'config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$bot = new Bot();
$bot->setToken($config['botToken']);
$bot->loadCommand(new MyCommand());
$bot->loadInternalCommands();

$bot->loadInternalWebhooks();
$bot->loadWebhook(new ToUserHook());
$bot->loadWebhook(new ToChannelHook());
$bot->enableWebserver(8080, 'secret');
$bot->run();