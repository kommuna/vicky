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

require dirname(__DIR__).'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$blockers = new BlockersIssueFile($config['pathToBlockersIssueFile']);

foreach (glob("{$config['pathToBlockersIssueFile']}*") as $file) {
    $arr = $blockers->get($file);
    echo $arr[1];
}