<?php
namespace Slack_project;

use PhpSlackBot\Bot;
use Slack_project\models\MyCommand;

require 'vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : 'config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$bot = new Bot();
$bot->setToken($config['botToken']); // Get your token here https://my.slack.com/services/new/bot
$bot->loadCommand(new MyCommand());
$bot->loadInternalCommands();        // This loads example commands
$bot->run();