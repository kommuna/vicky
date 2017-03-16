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

use Vicky\src\modules\BlockersIssueFile;
use DateTime;
use DateInterval;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$blockers = new BlockersIssueFile($config['pathToBlockersIssueFile']);

foreach (glob("{$blockers->getPathToFolder()}*") as $pathToFile) {
    $data = $blockers->get($pathToFile);

    if (strtotime('now') >= strtotime($data['nextNotification'])) {
        //Send request

        $data['nextNotification'] = (new DateTime())->add(new DateInterval("PT6H"))->format('Y-m-d\TH:i:sP');
        $blockers->put($data);
    }

    //echo print_r($blockers->get($pathToFile), true);
}