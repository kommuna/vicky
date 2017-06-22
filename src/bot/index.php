<?php
/**
 * Main module of slack bot lib, that contains bot configuration (like setting token), loading bot command and webhooks,
 * enabling the web server, that will listen incoming HTTP POST requests and running the bot.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky;

use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PhpSlackBot\Bot;
use kommuna\vicky\bot\modules\ToUserWebhook;
use kommuna\vicky\bot\modules\ToChannelWebhook;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require '/etc/slackBot-test/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['errorLog']);
ini_set('max_execution_time', 0);
date_default_timezone_set($config['timeZone']);

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['errorLog'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);

$bot = new Bot();
$bot->setToken($config['botToken']);
$bot->loadInternalCommands();

try {
    $bot->loadWebhook(new ToUserWebhook());
    $bot->loadWebhook(new ToChannelWebhook());
} catch (Exception $e) {
    $log->error($e->getMessage());
}

$bot->enableWebserver($config['botPort'], $config['botAuth']);

try {
    $bot->run();
} catch (Exception $e) {
    $log->error($e->getMessage());
}
