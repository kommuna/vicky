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
     * Path to folder with blockers issues files
     *
     * @var
     */
    protected static $pathToFolder;

    /**
     * File name
     *
     * @var
     */
    protected $fileName;

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
     * IssueFile constructor.
     *
     * @param                      $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null                 $lastNotification
     * @param int                  $notificationInterval
     */
    public function __construct($fileName, JiraWebhookData $jiraWebhookData = null, $lastNotification = null)
    {
        $this->setFileName($fileName);
        $this->setJiraWebhookData($jiraWebhookData);
        $this->setLastNotification($lastNotification);
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
     * @param $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
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
     * @return mixed
     */
    public static function getPathToFolder()
    {
        return self::$pathToFolder;
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
     *
     *
     * @param IssueFile $data
     *
     * @return string
     */
    public static function getPathToFile(IssueFile $issueFile)
    {
        return self::getPathToFolder().$issueFile->getFileName();
    }

    /**
     * Check all files in $pathToFolder for expired notification period
     *
     * @param $pathToFolder
     * @param $notificationInterval
     * @param $callback
     *
     * @throws IssueFileException
     */
    public static function filesCheck($pathToFolder, $notificationInterval, $callback)
    {
        foreach (glob("{$pathToFolder}*") as $pathToFile) {
            $issueFile = IssueFile::get($pathToFile);

            if (IssueFile::isExpired($issueFile, $notificationInterval)) {
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
    public static function isExpired(IssueFile $issueFile, $notificationInterval)
    {
        return ((time() - $issueFile->getLastNotification()) / 60) >= $notificationInterval;
    }

    /**
     *
     *
     * @param $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null $lastNotification
     *
     * @return mixed|IssueFile
     *
     * @throws IssueFileException
     */
    public static function create($fileName, JiraWebhookData $jiraWebhookData = null, $lastNotification = null)
    {
        $issueFile = new self($fileName, $jiraWebhookData, $lastNotification);

        $pathToFile = IssueFile::getPathToFile($issueFile);

        if (file_exists($pathToFile)) {
            $issueFile = IssueFile::get($pathToFile);
        } else {
            IssueFile::put($issueFile);
        }

        return $issueFile;
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
        $issueFile = json_decode(file_get_contents($pathToFile));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new IssueFileException("Json decode error: ".json_last_error_msg());
        }

        return $issueFile;
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
    public static function put(IssueFile $issueFile)
    {
        $pathToFile = IssueFile::getPathToFile($issueFile);
        $issueFile = json_encode($issueFile);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new IssueFileException("Json encode error: ".json_last_error_msg());
        }

        return file_put_contents($pathToFile, $issueFile);
    }

    /**
     *
     *
     * @param IssueFile $data
     *
     * @return bool
     */
    public static function delete(IssueFile $issueFile)
    {
        return unlink(IssueFile::getPathToFile($issueFile));
    }
}