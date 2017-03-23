<?php
/**
 * Vicky module, that check blockers issue files, send notification to vicky Like JIRA and store next notification time
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\util;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use DateTime;
use DateInterval;

use Vicky\src\modules\IssueFile;
use Vicky\src\modules\VickyClient;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set($config['timeZone']);

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['error_log'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);

$start = microtime(true);

$log->info("The script ".__FILE__." started.");

$vickyClient = new VickyClient(
    $config['vickyClient']['url'],
    $config['vickyClient']['timeout']
);

IssueFile::setPathToFolder($config['blockersIssues']['folder']);

IssueFile::filesCheck(IssueFile::getPathToFolder(), function($data) use ($vickyClient)
{
    $data['webhookEvent'] = 'jira:custom:blocker_notification';
    $vickyClient->send($data);
});

$log->info("Script finished in ".(microtime(true) - $start)." sec.");