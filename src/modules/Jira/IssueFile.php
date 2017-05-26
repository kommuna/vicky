<?php
/**
 * Class that stores in file, contains JiraWebhook data
 * and time of last notification
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\modules\Jira;

use JiraWebhook\Models\JiraWebhookData;
use kommuna\vicky\exceptions\IssueFileException;

class IssueFile
{
    /**
     * Path to folder with blockers issues files
     *
     * @var
     */
    protected static $pathToFolder;

    /**
     * Time between notifications in seconds
     *
     * @var
     */
    protected static $notificationInterval;

    /**
     * Time between last comment and first notification in seconds
     *
     * @var
     */
    protected static $blockerFirstTimeNotificationInterval;

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
     * Time of last notification (stores in seconds)
     *
     * @var
     */
    protected $lastNotification;

    /**
     * Time of last comment (stores in seconds)
     *
     * @var
     */
    protected $lastCommentTime;

    /**
     * IssueFile constructor.
     *
     * @param                      $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null|int             $lastCommentTime in seconds
     * @param null|int             $lastNotification in seconds
     */
    public function __construct(
        $fileName,
        JiraWebhookData $jiraWebhookData,
        $lastCommentTime = null,
        $lastNotification = null)
    {
        $this->setFileName($fileName);
        $this->setJiraWebhookData($jiraWebhookData);
        $this->setLastCommentTime($lastCommentTime);
        $this->setLastNotification($lastNotification);
    }

    /**
     * @param $pathToFolder
     *
     * @throws IssueFileException
     */
    public static function setPathToFolder($pathToFolder)
    {
        if (!is_dir($pathToFolder) && !mkdir($pathToFolder)) {
            throw new IssueFileException("IssueFile: {$pathToFolder} doesn't exists and unable to create.");
        }

        if (!is_writable($pathToFolder) || !is_readable($pathToFolder)) {
            throw new IssueFileException("IssueFile: {$pathToFolder} isn't writable or readable.");
        }

        self::$pathToFolder = substr($pathToFolder, -1) === DIRECTORY_SEPARATOR ? $pathToFolder : $pathToFolder.DIRECTORY_SEPARATOR;
    }

    /**
     * @param int $notificationInterval in seconds
     */
    public static function setNotificationInterval($notificationInterval)
    {
        self::$notificationInterval = $notificationInterval;
    }

    /**
     * @param int $blockerFirstTimeNotificationInterval in seconds
     */
    public static function setBlockerFirstTimeNotificationInterval($blockerFirstTimeNotificationInterval)
    {
        self::$blockerFirstTimeNotificationInterval = $blockerFirstTimeNotificationInterval;
    }
    
    /**
     * @param $fileName
     */
    public function setFileName($fileName)
    {
        self::fileNameCheck($fileName);

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
     * @param int $lastCommentTime in seconds
     */
    public function setLastCommentTime($lastCommentTime = null)
    {
        $this->lastCommentTime = $lastCommentTime ?: time();
    }

    /**
     * @param int $lastNotification in seconds
     */
    public function setLastNotification($lastNotification = null)
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
    public static function getNotificationInterval()
    {
        return self::$notificationInterval;
    }

    /**
     * @return mixed
     */
    public static function getBlockerFirstTimeNotificationInterval()
    {
        return self::$blockerFirstTimeNotificationInterval;
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
     * @return mixed
     */
    public function getLastCommentTime()
    {
        return $this->lastCommentTime;
    }

    /**
     * Create full path to file
     *
     * @param IssueFile $issueFile
     *
     * @return string
     */
    public static function getPathToFile(IssueFile $issueFile)
    {
        return self::getPathToFolder().$issueFile->getFileName();
    }

    /**
     * Check interval between current time and time of last notification
     * with $notificationInterval
     *
     * @param IssueFile $issueFile
     * @param int       $blockerFirstTimeNotificationInterval in seconds
     * @param int       $notificationInterval               in seconds
     *
     * @return bool
     */
    protected static function isExpired(
        IssueFile $issueFile,
        $blockerFirstTimeNotificationInterval,
        $notificationInterval)
    {
        $isFirstIntervalExpired = time() - $issueFile->getLastCommentTime() >= $blockerFirstTimeNotificationInterval;

        $lastNotification = $issueFile->getLastNotification();
        $isNotificationIntervalExpired = time() - $lastNotification >= $notificationInterval;

        return $isFirstIntervalExpired && (!$lastNotification || $isNotificationIntervalExpired);
    }

    /**
     * Checks the file name for the template matching
     *
     * @param $fileName
     *
     * @throws IssueFileException
     *
     */
    protected static function fileNameCheck($fileName)
    {
        if (!preg_match("/^[A-Za-z]{1,10}-[0-9]{1,10}$/", $fileName)) {
            throw new IssueFileException("IssueFile: File name {$fileName} is invalid!");
        }
    }

    /**
     * Check all files in $pathToFolder for expired notification period
     * and use $callback on expired files
     *
     * @param callable $callback                           must be a function that takes over IssueFile
     * @param null|int $blockerFirstTimeNotificationInterval in seconds
     * @param null|int $notificationInterval               in seconds
     */
    public static function filesCheck(
        $callback,
        $blockerFirstTimeNotificationInterval = null,
        $notificationInterval = null)
    {
        $blockerFirstTimeNotificationInterval = $blockerFirstTimeNotificationInterval ?: self::getBlockerFirstTimeNotificationInterval();
        $notificationInterval                 = $notificationInterval ?: self::getNotificationInterval();

        $pathToFolder = self::getPathToFolder();

        foreach (glob("{$pathToFolder}*") as $pathToFile) {
            $issueFile = self::get(basename($pathToFile));

            if (self::isExpired($issueFile, $blockerFirstTimeNotificationInterval, $notificationInterval)) {
                $callback($issueFile);
            }
        }
    }

    /**
     * Updates time of last notification in issue file
     *
     * @param        $fileName
     * @param string $now
     */
    public static function updateLastNotificationTime($fileName, $now = null)
    {
        $now = $now ?: time();

        $issueFile = self::get($fileName);
        $issueFile->setLastNotification($now);
        self::put($issueFile);
    }

    /**
     * Creates a new file if it did not exist,
     * or returns data from the file if it exists
     *
     * @param                      $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null|int             $lastCommentTime in seconds
     * @param null|int             $lastNotification in seconds
     *
     * @return mixed|IssueFile
     */
    public static function create(
        $fileName,
        JiraWebhookData $jiraWebhookData,
        $lastCommentTime = null,
        $lastNotification = null)
    {
        $issueFile = new self($fileName, $jiraWebhookData, $lastCommentTime, $lastNotification);

        $pathToFile = self::getPathToFile($issueFile);

        if (file_exists($pathToFile)) {
            $issueFile = self::get(basename($pathToFile));
        } else {
            self::put($issueFile);
        }

        return $issueFile;
    }

    /**
     * Gets data from $pathToFile
     *
     * @param $fileName
     *
     * @return mixed
     *
     * @throws IssueFileException
     */
    public static function get($fileName)
    {
        $pathToFile = self::getPathToFolder().$fileName;
        $issueFile = unserialize(file_get_contents($pathToFile));

        if (!$issueFile) {
            throw new IssueFileException("IssueFile: Can't be unserialized {$pathToFile}");
        }

        return $issueFile;
    }

    /**
     * Puts data in $pathToFile
     *
     * @param IssueFile $issueFile
     *
     * @return int
     *
     * @throws IssueFileException
     */
    public static function put(IssueFile $issueFile)
    {
        $pathToFile = self::getPathToFile($issueFile);
        $issueFile = serialize($issueFile);

        if (!file_put_contents($pathToFile, $issueFile)) {
            throw new IssueFileException("IssueFile: Can't write in file {$pathToFile}");
        }
    }

    /**
     * Deletes IssueFile
     *
     * @param string|object $issue can be string or exemplar of IssueFile
     *
     * @return bool
     *
     * @throws IssueFileException
     */

    public static function delete($issue)
    {
        if ($issue instanceof IssueFile) {
            $issue = self::getPathToFile($issue);
        } else {
            self::fileNameCheck($issue);

            $issue = self::getPathToFolder().$issue;
        }

        if (!unlink($issue) && file_exists($issue)) {
            throw new IssueFileException("IssueFile: Can't unlink file {$issue}");
        }
    }
}