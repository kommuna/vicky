<?php
namespace Vicky\client\modules\Jira;

class JiraIssueComments
{
    private $comments = [];

    private $maxResults;
    private $total;
    private $startAt;

    //private $lastComment;
    
    public static function parse($data = null)
    {
        $issueCommentsData = new self;

        if ($data === null) {
            return $issueCommentsData;
        }

        foreach ($data['comments'] as $key => $comment) {
            $issueCommentsData->setComment($key, $comment);
        }

        $issueCommentsData->setMaxResults($data['maxResults']);
        $issueCommentsData->setTotal($data['total']);
        $issueCommentsData->setStartAt($data['startAt']);

        return $issueCommentsData;
    }

    public function setComment($key, $comment)
    {
        $this->comments[$key] = JiraIssueComment::parse($comment);
    }
    
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    public function setTotal($total)
    {
        $this->total = $total;
    }

    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;
    }

    /**************************************************/

    public function getComments()
    {
        return $this->comments;
    }

    public function getMaxResults()
    {
        return $this->maxResults;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function getStartAt()
    {
        return $this->startAt;
    }
    
    public function getLastComment()
    {
        return $this->comments[count($this->comments) - 1];
    }

    public function getLastCommenterName()
    {
        $n = count($this->comments) - 1;

        return $this->comments[$n]->getAuthor()->getName();

        // I think this method is not so good, it is better to address to the last element through an index
        /*if (!$this->lastComment) {
            $this->lastComment = array_pop($this->comments);
        }

        return $this->lastComment->getAuthor()->getName();*/
    }

    public function getLastCommentBody()
    {
        $n = count($this->comments) - 1;

        return $this->comments[$n]->getBody();

        // Same
        /*if (!$this->lastComment) {
            $this->lastComment = array_pop($this->comments);
        }

        return $this->lastComment->getBody();*/
    }
}