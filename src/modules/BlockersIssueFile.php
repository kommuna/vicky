<?php
/**
 * Class that store path to blockers issue files, can put blockers issue data to file, get data from file, and delete
 * file
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\src\modules;

class BlockersIssueFile
{
    /**
     * Path to folder with blockers issue file
     *
     * @var
     */
    protected $pathToFolder;

    /**
     * BlockersIssueFile constructor.
     *
     * @param $pathToFolder
     */
    public function __construct($pathToFolder)
    {
        $this->setPathToFolder($pathToFolder);
    }

    /**
     * Set $pathToFolder value and add adds a '/' to the end if it is missing
     *
     * @param $pathToFolder
     */
    public function setPathToFolder($pathToFolder)
    {
        $this->pathToFolder = substr($pathToFolder, -1) === '/' ? $pathToFolder : "{$pathToFolder}/";
    }

    /**
     * @return mixed
     */
    public function getPathToFolder()
    {
        return $this->pathToFolder;
    }

    /**
     * Get data from file
     *
     * @param $pathToFile
     *
     * @return mixed
     */
    public function get($pathToFile)
    {
        return json_decode(file_get_contents($pathToFile), true);
    }

    /**
     * Put data to file
     *
     * @param $data
     *
     * @return int
     */
    public function put($data)
    {
        return file_put_contents("{$this->pathToFolder}{$data['issue']['key']}", json_encode($data));
    }

    /**
     * Delete file
     *
     * @param $pathToFile
     *
     * @return bool
     */
    public function delete($pathToFile)
    {
        return unlink($pathToFile);
    }
}