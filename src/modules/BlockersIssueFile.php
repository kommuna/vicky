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

    public function getPathToFolder()
    {
        return $this->pathToFolder;
    }

    public function get($pathToFile)
    {
        return json_decode(file_get_contents($pathToFile), true);
    }

    public function put($data)
    {
        return file_put_contents("{$this->pathToFolder}{$data['issue']['key']}", json_encode($data));
    }

    public function delete($pathToFile)
    {
        return unlink($pathToFile);
    }
}