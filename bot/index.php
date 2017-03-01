<?php
/**
 * This file is part of vicky.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky;

use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PhpSlackBot\Bot;
use Vicky\bot\modules\MyCommand;
use Vicky\bot\modules\ToUserWebhook;
use Vicky\bot\modules\ToChannelWebhook;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require 'config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$log = new Logger('vicky');
$log->pushHandler(new StreamHandler($config['error_log'], Logger::DEBUG));

$bot = new Bot();
$bot->setToken($config['botToken']);
$bot->loadInternalCommands();

try {
    $bot->loadWebhook(new ToUserWebhook());
    $bot->loadWebhook(new ToChannelWebhook());
} catch (Exception $e) {
    $log->error($e->getMessage());
}

$bot->enableWebserver(8080, $config['botAuth']);

try {
    $bot->run();
} catch (Exception $e) {
    $log->error($e->getMessage());
}
