<?php
namespace Vicky\client\modules;

use Vicky\client\exceptions\BlockerFileException;
use League\Event\Emitter;

class BlockersIssueFile
{
    private static $emitter;
    
    protected $pathToDir;
    protected $botClient;

    /**
     * BlockerIssueFile constructor.
     *
     * @param $pathToDir
     */
    public function __construct($pathToDir)
    {
        if (!file_exists($pathToDir) && !is_readable($pathToDir)) {
            throw new BlockerFileException("{$pathToDir} does not exist or not readable!");
        }

        $this->pathToDir = $pathToDir;

        self::getEmitter();
    }

    /**
     * Initialize emitter
     *
     * @return Emitter
     */
    public static function getEmitter()
    {
        if (!self::$emitter) {
            self::$emitter = new Emitter();
        }

        return self::$emitter;
    }

    /**
     * Add listener for event
     *
     * @param $name - event name
     * @param $listener - listener (it could be function or object (see league/event docs))
     * @param int $priority - listener priority
     */
    public function addListener($name, $listener, $priority = 0)
    {
        self::$emitter->addListener($name, $listener, $priority);
    }

    /**
     * Call listener by event name
     *
     * @param $name - event name
     * @param null $data
     */
    public function on($name, $data = null)
    {
        self::$emitter->emit($name, $data);
    }

    /**
     * Set time of last created comment in file named like JIRA issue key
     *
     * @param $issueKey
     * @param $time
     *
     * @throws BlockerFileException
     */
    public function setCommentTimeToFile($issueKey, $assignee, $time)
    {
        $pathToFile = "{$this->pathToDir}/{$issueKey}";

        $f = fopen($pathToFile, "w+");

        $answer = fwrite($f, "{$assignee} {$time}");

        if (!$answer) {
            throw new BlockerFileException("Cant write to {$pathToFile}!");
        }

        return fclose($f);
    }
    
    public function run()
    {
        $this->on('check.CommentTime', $this->pathToDir);
    }
}