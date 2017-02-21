<?php
namespace Vicky\client\modules;

use Vicky\client\exceptions\BlockerFileException;
use League\Event\Emitter;

class BlockersIssueFile
{
    private static $emitter;
    
    protected $pathToDir;

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
     * @return mixed
     */
    public function getPathToDir()
    {
        return $this->pathToDir;
    }

    // This is useful method, but i don't know how to apply him
    /*public function readFromFile($pathToFile)
    {
        $f = fopen($pathToFile, "r");

        if (!$f) {
            throw new BlockerFileException("Cant open file {$pathToFile}");
        }

        $data = fread($f, filesize($pathToFile));

        if (!$data) {
            throw new BlockerFileException("Cant read from {$pathToFile}!");
        }

        fclose($f);

        return $data;
    }*/

    /**
     * Writes data to file
     * 
     * @param $pathToFile
     * @param $data
     * 
     * @return bool
     * 
     * @throws BlockerFileException
     */
    public function writeToFile($pathToFile, $data)
    {
        $f = fopen($pathToFile, "w+");

        if (!$f) {
            throw new BlockerFileException("Cant open file {$pathToFile}");
        }

        $answer = fwrite($f, $data);

        if (!$answer) {
            throw new BlockerFileException("Cant write to {$pathToFile}!");
        }

        return fclose($f);
    }

    /**
     * Set time of last created comment and assignee username 
     * in file named like JIRA issue key
     *
     * @param $issueKey
     * @param $time
     *
     * @throws BlockerFileException
     */
    public function setCommentDataToFile($issueKey, $assignee, $time)
    {
        $pathToFile = "{$this->pathToDir}/{$issueKey}";
        $data = "{$assignee} {$time}";

        return $this->writeToFile($pathToFile, $data);
    }

    /**
     * 
     */
    public function run()
    {
        $this->on('check.CommentTime', $this->pathToDir);
    }
}