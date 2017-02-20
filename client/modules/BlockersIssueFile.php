<?php
namespace Vicky\client\modules;

use Vicky\client\exceptions\BlockerFileException;

class BlockersIssueFile
{
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
        $files = scandir($this->pathToDir);

        foreach ($files as $file) {
            $pathToFile = "{$this->pathToDir}/{$file}";

            $f = fopen($pathToFile, "r");

            $str = fread($f, filesize($pathToFile));

            if (!$str) {
                throw new BlockerFileException("Cant read from {$pathToFile}!");
            }

            $data = explode(' ', $str);

            $dataTime1 = new \DateTime('NOW');
            $dataTime2 = new \DateTime($data[2]);
            
            error_log($dataTime1->diff($dataTime2));
        }
    }
}