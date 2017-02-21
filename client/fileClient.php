<?php
namespace Vicky;

use Vicky\client\exceptions\BlockerFileException;
use Vicky\client\modules\BlockersIssueFile;
use Vicky\client\modules\Slack\SlackBotSender;
use DateTime;

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

        // This block of code should be in method i think
        $f = fopen($pathToFile, "r");

        if (!$f) {
            throw new BlockerFileException("Cant open file {$pathToFile}");
        }
        
        $data = fread($f, filesize($pathToFile));

        if (!$data) {
            throw new BlockerFileException("Cant read from {$pathToFile}!");
        }
        
        fclose($f);
        //***********************************************

        $data = explode(' ', $data);

        $interval = (new DateTime('NOW'))->diff(new DateTime($data[1]));

        if ($interval->d >= 1) {
            $botClient->toUser($data[0], 'Blocker issue that assigned to you not commented at least 24 hours!');
        } else {
            error_log($interval->format("%Y:%M:%D - %H:%I:%S"));
        }
    }
});

$fileClient->run();