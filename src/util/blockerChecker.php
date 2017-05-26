<?php
/**
 * Vicky module, that check blockers issue files, send notification 
 * to vicky like JIRA and store next notification time
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\util;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use kommuna\vicky\modules\Jira\IssueFile;
use kommuna\vicky\modules\VickyClient;

require dirname(dirname(__DIR__)).'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['errorLog']);
ini_set('error_reporting', $config['errorReporting']);
ini_set('max_execution_time', 0);
date_default_timezone_set($config['timeZone']);

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['errorLog'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);
if ($config['environment'] === 'local'){
    $log->pushHandler(new StreamHandler('php://output', Logger::DEBUG)); // <<< uses a stream
}

$start = microtime(true);

$log->debug("The script ".__FILE__." started.");

VickyClient::getInstance(
    $config['vickyClient']['url'],
    $config['vickyClient']['timeout']
);

IssueFile::setPathToFolder($config['blockersIssues']['folder']);
IssueFile::setNotificationInterval($config['blockersIssues']['notificationInterval']);
IssueFile::setBlockerFirstTimeNotificationInterval($config['blockerIssues']['blockerFirstTimeNotificationInterval']);

IssueFile::filesCheck(function(IssueFile $issueFile)
{
    VickyClient::getInstance()->send(
        $issueFile->getJiraWebhookData()->getRawData(), 
        'custom:blocker_notification'
    );
});

$log->debug("Script finished in ".(microtime(true) - $start)." sec.");