<?php
namespace Vicky\client\modules;

use Vicky\client\exceptions\BlockerFileException;

class BlockerIssueFile
{
    protected $pathToDir;

    public function __construct($pathToDir)
    {
        if (!file_exists($pathToDir) && !is_readable($pathToDir)) {
            throw new BlockerFileException('This dir does not exist or not readable!');
        }

        $this->pathToDir = $pathToDir;
    }

    public function setCommentTime($issueKey, $time)
    {
        $f = fopen("{$this->pathToDir}/{$issueKey}", "w+");

        $error = fwrite($f, $time);

        if (!$error) {
            throw new BlockerFileException('Cant write to file!');
        }

        fclose($f);
    }
}