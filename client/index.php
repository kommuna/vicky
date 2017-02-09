<?php
namespace Vicky;

use Vicky\client\modules\JiraToSlackBotConverter;
use Vicky\client\modules\SlackBotClient;
use Vicky\client\modules\JiraWebhook;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/clientConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$botClient = new SlackBotClient(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

$jiraData = (new JiraWebhook())->extractData();

//$sender->toChannel('#general', 'To channel "from" php!');
//$sender->toChannel('#privatetry', 'To private channel from php!');
//$sender->toUser('chewbacca', 'To user from php!');

//$receiver = new JiraWebhook();
//$data = $receiver->process();

//error_log(print_r($data, 1));

//$sender->toUser('chewbacca', $data->webhookEvent);
//$sender->toUser('chewbacca', $data->issue->fields->comment->comments[0]->body);

$botClient->setConverter(new JiraToSlackBotConverter());
$botClient->send($jiraData);
