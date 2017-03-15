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
        $this->pathToFolder = substr($pathToFolder, -1) === '/' ? $pathToFolder : "{$pathToFolder}/";
    }

    public function get($pathToFile)
    {
        $data = file_get_contents($pathToFile);
        
        return json_decode($data, true);
    }

    public function put($data)
    {
        $data['nextNotification'] = (new DateTime())->add(new DateInterval('PT24H'))->format('Y-m-d\TH:i:sP');
        $data = json_encode($data);

        return file_put_contents("{$this->pathToFolder}{$data->getIssue()->getKey()}", $data);
    }

    public function delete()
    {
        
    }
}