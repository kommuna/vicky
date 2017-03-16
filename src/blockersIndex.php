<?php
/**
 * File review
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use DateTime;
use DateInterval;

use Vicky\src\modules\BlockersIssueFile;
use Vicky\src\modules\VickyClient;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['error_log'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);

$start = microtime(true);

$log->info("The script ".__FILE__." started.");

$blockers = new BlockersIssueFile($config['pathToBlockersIssueFile']);

$vickyClient = new VickyClient(
    $config['vickyClient']['url'],
    $config['vickyClient']['timeout']
);

foreach (glob("{$blockers->getPathToFolder()}*") as $pathToFile) {
    $data = $blockers->get($pathToFile);

    if (strtotime('now') >= strtotime($data['nextNotification'])) {
        $data['webhookEvent'] = 'blocker:notification';
        $vickyClient->send($data);

        $data['nextNotification'] = (new DateTime())->add(new DateInterval("PT6H"))->format('Y-m-d\TH:i:sP');
        $blockers->put($data);
    }
}

$log->info("Script finished in ".(microtime(true) - $start)." sec.");