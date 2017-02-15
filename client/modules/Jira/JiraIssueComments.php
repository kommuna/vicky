<?php
namespace Vicky\client\modules\Jira;

class JiraIssueComments
{
    private $lastCommenterID;
    private $lastComment;
    private $commentReference;
    
    public static function parseWebhookData($data)
    {
        $issueCommentsData = new self;

        if ($data === null) {
            return $issueCommentsData;
        }

        $lastComment = array_pop($data['comments']);

        $issueCommentsData->setLastCommenterID($lastComment['author']['name']);
        $issueCommentsData->setLastComment($lastComment['body']);

        return $issueCommentsData;
    }

    public function setLastCommenterID($lastCommenterID)
    {
        $this->lastCommenterID = $lastCommenterID;
    }

    public function setLastComment($lastComment)
    {
        $this->lastComment = $lastComment;
    }

    public function setCommentReference($commentreference)
    {
        $this->commentReference = $commentreference;
    }

    /**************************************************/

    public function getLastCommenterID()
    {
        return $this->lastCommenterID;
    }

    public function getLastComment()
    {
        return $this->lastComment;
    }

    public function getCommentReference()
    {
        return $this->commentReference;
    }
}