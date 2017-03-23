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

use DateTime;
use DateInterval;
use Vicky\src\exceptions\IssueFileException;

class IssueFile
{
    /**
     * @var
     */
    protected $fileName;

    /**
     * @var
     */
    protected static $pathToFolder;

    /**
     * @var
     */
    protected $jiraWebhookData;

    /**
     * @var
     */
    protected $lastNotification;

    /**
     * @var
     */
    protected $notificationInterval;

    /**
     * IssueFile constructor.
     *
     * @param $pathToFile
     * @param int $notificationInterval
     *
     * @throws 
     */
    public function __construct($fileName, $jiraWebhookData = null, $lastNotification = null, $notificationInterval = 6)
    {
        $this->setFileName($fileName);
        $this->setJiraWebhookData($jiraWebhookData);
        $this->setLastNotification($lastNotification);
        $this->setNotificationInterval($notificationInterval);
    }

    /**
     * @param $pathToFolder
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @param $pathToFolder
     */
    public static function setPathToFolder($pathToFolder)
    {
        self::$pathToFolder = substr($pathToFolder, -1) === '/' ? $pathToFolder : "{$pathToFolder}/";;
    }

    /**
     * @param $jiraWebhookData
     */
    public function setJiraWebhookData($jiraWebhookData)
    {
        $this->jiraWebhookData = $jiraWebhookData;
    }

    /**
     * @param $notificationInterval
     */
    public function setLastNotification($lastNotification)
    {
        $this->lastNotification = $lastNotification;
    }

    /**
     * @param $notificationInterval
     */
    public function setNotificationInterval($notificationInterval)
    {
        $this->notificationInterval = $notificationInterval;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return mixed
     */
    public static function getPathToFolder()
    {
        return self::$pathToFolder;
    }

    /**
     * @return mixed
     */
    public function getJiraWebhookData()
    {
        return $this->jiraWebhookData;
    }

    /**
     * @param $notificationInterval
     */
    public function getLastNotification()
    {
        return $this->lastNotification;
    }

    /**
     * @return mixed
     */
    public function getNotificationInterval()
    {
        return $this->notificationInterval;
    }

    /**
     * @param $issueKey
     */
    public static function deleteFileByIssueKey($pathToFolder, $issueKey)
    {
        foreach (glob("{$pathToFolder}*") as $pathToFile) {
            if (basename($pathToFile) === $issueKey) {
                IssueFile::delete($pathToFile);
            }
        }
    }

    /**
     * @param $pathToFolder
     * @param $notificationInterval
     * @param $callback
     */
    public static function filesCheck($pathToFolder, $callback)
    {
        foreach (glob("{$pathToFolder}*") as $pathToFile) {
            $issueFile = IssueFile::get($pathToFile);

            if (IssueFile::isExpired($issueFile->getLastNotification(), $issueFile->getNotificationInterval())) {
                $callback($issueFile->getJiraWebhookData()->getRawData());
            }
        }
    }

    /**
     * @return bool
     */
    public static function isExpired($lastNotification, $notificationInterval)
    {
        $interval = (new DateTime())->diff(new DateTime($lastNotification));

        return $interval->h >= $notificationInterval;
    }

    /**
     * Get data from file
     *
     * @return mixed
     */
    public static function get($pathToFile)
    {
        return json_decode(file_get_contents($pathToFile));
    }

    /**
     * Put data to file
     *
     * @return int
     */
    public static function put($pathToFile, $issueKey, $data)
    {
        return file_put_contents("{$pathToFile}{$issueKey}", json_encode($data));
    }

    /**
     * Delete file
     *
     * @param $pathToFile
     *
     * @return bool
     */
    public static function delete($pathToFile)
    {
        return unlink($pathToFile);
    }
}