<?php
namespace Vicky;

use Vicky\client\modules\Slack\SlackBotSender;
use Vicky\client\modules\BlockersIssueFile;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/clientConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$botClient = SlackBotSender::getInstance(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

$fileClient = new BlockersIssueFile($config['pathToBlockerFile']);

$fileClient->addListener('check.CommentTime', function($e, $pathToDir) use ($botClient)
{
    $files = scandir($pathToDir);
    array_shift($files);
    array_shift($files);

    foreach ($files as $file) {
        $pathToFile = "{$pathToDir}{$file}";

        $f = fopen($pathToFile, "r");

        $str = fread($f, filesize($pathToFile));

        $data = explode(' ', $str);

        $interval = (new \DateTime('NOW'))->diff(new \DateTime($data[1]));

        if ($interval->d >= 1) {
            $botClient->toUser($data[0], 'Blocker issue that assigned to you not commented 24 hours!');
        }
    }
});

$fileClient->run();