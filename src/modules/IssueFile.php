<?php
/**
 * Class that store path to blockers issue files, can put blockers issue
 * data to file, get data from file, and delete file
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\src\modules;

use JiraWebhook\Models\JiraWebhookData;
use DateTime;
use Vicky\src\exceptions\IssueFileException;

class IssueFile
{
    /**
     * File name
     *
     * @var
     */
    protected $fileName;

    /**
     * Path to folder with blockers issues files
     *
     * @var
     */
    protected static $pathToFolder;

    /**
     * Parsed data from JIRA
     *
     * @var
     */
    protected $jiraWebhookData;

    /**
     * Datatime of last notification
     *
     * @var
     */
    protected $lastNotification;

    /**
     * Time interval between notifications in hours
     *
     * @var
     */
    protected $notificationInterval;

    /**
     * IssueFile constructor.
     *
     * @param                      $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null                 $lastNotification
     * @param int                  $notificationInterval
     */
    public function __construct($fileName, JiraWebhookData $jiraWebhookData = null, $lastNotification = null, $notificationInterval = 6)
    {
        $this->setFileName($fileName);
        $this->setJiraWebhookData($jiraWebhookData);
        $this->setLastNotification($lastNotification);
        $this->setNotificationInterval($notificationInterval);
    }

    /**
     * @param $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @param $pathToFolder
     *
     * @throws IssueFileException
     */
    public static function setPathToFolder($pathToFolder)
    {
        if (!file_exists($pathToFolder) && is_writable(dirname($pathToFolder))) {
            mkdir($pathToFolder);
        } else{
            throw new IssueFileException("{$pathToFolder} don't exists and unable to create.");
        }

        if (!is_writable($pathToFolder) || !is_readable($pathToFolder)) {
            throw new IssueFileException("{$pathToFolder} don't writable or don't readable.");
        }

        self::$pathToFolder = substr($pathToFolder, -1) === '/' ? $pathToFolder : "{$pathToFolder}/";;
    }

    /**
     * @param JiraWebhookData $jiraWebhookData
     */
    public function setJiraWebhookData(JiraWebhookData $jiraWebhookData)
    {
        $this->jiraWebhookData = $jiraWebhookData;
    }

    /**
     * @param $lastNotification
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
     * @return mixed
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
     * Checks is file with $issueKey name exists
     * 
     * @param $pathToFolder
     * @param $issueKey
     * 
     * @return bool
     */
    public static function isFileExists($pathToFolder, $issueKey)
    {
        $answer = false;

        foreach (glob("{$pathToFolder}*") as $pathToFile) {
            if (basename($pathToFile) === $issueKey) {
                $answer = true;
            }
        }

        return $answer;
    }

    /**
     * Delete all files in $pathToFolder with names that matches with $issueKey
     *
     * @param        $pathToFolder
     * @param string $issueKey     JIRA issue key
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
     * Check all files in $pathToFolder for expired notification period
     *
     * @param $pathToFolder
     * @param $callback
     *
     * @throws IssueFileException
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
     * Check interval between current datatime and datatime of last notification
     * with $notification interval
     *
     * @param $lastNotification
     * @param $notificationInterval
     *
     * @return bool
     */
    public static function isExpired($lastNotification, $notificationInterval)
    {
        $interval = (new DateTime())->diff(new DateTime($lastNotification));

        return $interval->h >= $notificationInterval;
    }

    /**
     * Gets data from $pathToFile
     *
     * @param $pathToFile
     *
     * @return mixed
     *
     * @throws IssueFileException
     */
    public static function get($pathToFile)
    {
        $data = json_decode(file_get_contents($pathToFile));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new IssueFileException("Json decode error: ".json_last_error_msg());
        }

        return $data;
    }

    /**
     * Puts data in $pathToFile
     *
     * @param           $pathToFile
     * @param           $issueKey
     * @param IssueFile $data
     *
     * @return int
     *
     * @throws IssueFileException
     */
    public static function put($pathToFile, $issueKey, IssueFile $data)
    {
        $data = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new IssueFileException("Json encode error: ".json_last_error_msg());
        }

        return file_put_contents("{$pathToFile}{$issueKey}", $data);
    }

    /**
     * Deletes $pathToFile
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