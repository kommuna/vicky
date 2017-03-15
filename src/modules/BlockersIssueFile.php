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
namespace Vicky\src\modules;

use DateTime;
use DateInterval;

class BlockersIssueFile
{
    protected $pathToFolder;

    public function __construct($pathToFolder)
    {
        $this->pathToFolder = $pathToFolder;
    }

    public function get()
    {

    }

    public function put($data)
    {
        $date = (new DateTime())->add(new DateInterval('PT24H'));
        return file_put_contents(
            "{$this->pathToFolder}{$data->getIssue()->getKey()}",
            "{$data->getRawData()} {$date->format('Y-m-d\TH:i:sP')}"
        );
    }

    public function delete()
    {
        
    }
}