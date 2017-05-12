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
     * IssueFile constructor.
     *
     * @param                      $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null                 $lastNotification in seconds
     */
    public function __construct($fileName, $jiraWebhookData = null, $lastNotification = null)
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
        if (!mkdir($pathToFolder) && !file_exists($pathToFolder)) {
            throw new IssueFileException("{$pathToFolder} don't exists and unable to create.");
        }

        if (!is_writable($pathToFolder) || !is_readable($pathToFolder)) {
            throw new IssueFileException("{$pathToFolder} don't writable or don't readable.");
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
     * @param $fileName
     *
     * @throws IssueFileException
     */
    public function setFileName($fileName)
    {
        self::fileNameCheck($fileName);

        $this->fileName = $fileName;
    }

    /**
     * @param JiraWebhookData $jiraWebhookData
     */
    public function setJiraWebhookData($jiraWebhookData)
    {
        $jiraWebhookData = $jiraWebhookData instanceof JiraWebhookData ? $jiraWebhookData : null;

        $this->jiraWebhookData = $jiraWebhookData;
    }

    /**
     * @param int $lastNotification in seconds
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
    public static function getNotificationInterval()
    {
        return self::$notificationInterval;
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
     * @param int       $notificationInterval in seconds
     *
     * @return bool
     */
    protected static function isExpired(IssueFile $issueFile, $notificationInterval)
    {
        return (time() - $issueFile->getLastNotification()) >= $notificationInterval;
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
            throw new IssueFileException("This file name {$fileName} is invalid!");
        }
    }

    /**
     * Check all files in $pathToFolder for expired notification period
     * and use $callback on expired files
     *
     * @param callable $callback             must be a function that takes over IssueFile
     * @param null|int $notificationInterval in seconds
     *
     * @throws IssueFileException
     */
    public static function filesCheck($callback, $notificationInterval = null)
    {
        $notificationInterval = $notificationInterval ? $notificationInterval : IssueFile::getNotificationInterval();

        $pathToFolder = IssueFile::getPathToFolder();

        foreach (glob("{$pathToFolder}*") as $pathToFile) {
            $issueFile = IssueFile::get(basename($pathToFile));

            if (IssueFile::isExpired($issueFile, $notificationInterval)) {
                $callback($issueFile);
            }
        }
    }

    /**
     * Updates time of last notification in blocker issue file
     *
     * @param        $fileName
     * @param string $now
     *
     * @throws IssueFileException
     */
    public static function updateNotificationTime($fileName, $now = null)
    {
        $now = $now ? $now : time();

        $issueFile = IssueFile::get($fileName);
        $issueFile->setLastNotification($now);
        IssueFile::put($issueFile);
    }

    /**
     * Creates a new file if it did not exist,
     * or returns data from the file if it exists
     *
     * @param                      $fileName
     * @param JiraWebhookData|null $jiraWebhookData
     * @param null|int             $lastNotification in seconds
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
            $issueFile = IssueFile::get(basename($pathToFile));
        } else {
            IssueFile::put($issueFile);
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
        $pathToFile = IssueFile::getPathToFolder().$fileName;
        $issueFile = unserialize(file_get_contents($pathToFile));

        if (!$issueFile) {
            throw new IssueFileException("Can't be unserialized {$pathToFile}");
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
        $pathToFile = IssueFile::getPathToFile($issueFile);
        $issueFile = serialize($issueFile);

        return file_put_contents($pathToFile, $issueFile);
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
            $issue = IssueFile::getPathToFile($issue);
        } else {
            self::fileNameCheck($issue);

            $issue = IssueFile::getPathToFolder().$issue;
        }

        return unlink($issue);
    }
}