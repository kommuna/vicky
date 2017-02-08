<?php
namespace Vicky\client\modules;

class JiraWebhookData
{
    private static $webhookData;

    private $number;
    private $URL;
    private $status;
    private $summary;
    private $assignee;
    private $lastCommenterID;
    private $lastComment;
    
    private $priority;
    private $issueType;
    private $webhookEvent;

    private $issueEvent;
    
    public static function parseWebhookData($data = null)
    {
        if ($data === null) {
            return self::$webhookData = new JiraWebhookData();
        } elseif (self::$webhookData) {
            return self::$webhookData;
        }

        self::$webhookData = new JiraWebhookData();

        self::$webhookData->setNumber($data['issue']['key']);
        self::$webhookData->setURL($data['issue']['self']);
        self::$webhookData->setStatus($data['issue']['fields']['status']['name']);
        self::$webhookData->setSummary($data['issue']['fields']['summary']);
        self::$webhookData->setAssignee($data['issue']['fields']['assignee']['name']);
        
        $lastComment = array_pop($data['issue']['fields']['comment']['comments']);
        
        self::$webhookData->setLastCommenterID($lastComment['author']['name']);
        self::$webhookData->setLastComment($lastComment['body']);

        self::$webhookData->setPriority($data['issue']['fields']['priority']['name']);
        self::$webhookData->setIssueType($data['issue']['fields']['issuetype']['name']);
        self::$webhookData->setWebhookEvent($data['webhookEvent']);

        self::$webhookData->setIssueEvent($data['issue_event_type_name']);

        return self::$webhookData;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    public function setURL($URL)
    {
        $this->URL = $URL;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function setAssignee($assignee)
    {
        $this->assignee = $assignee;
    }

    public function setLastCommenterID($lastCommenterID)
    {
        $this->lastCommenterID = $lastCommenterID;
    }

    public function setLastComment($lastComment)
    {
        $this->lastComment = $lastComment;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function setIssueType($issueType)
    {
        $this->issuetype = $issueType;
    }

    public function setWebhookEvent($webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }

    public function setIssueEvent($issueEvent)
    {
        $this->issueEvent = $issueEvent;
    }

    /*******************************************/

    public function getNumber()
    {
        return $this->number;
    }

    public function getURL()
    {
        return $this->URL;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getAssignee()
    {
        return $this->assignee;
    }

    public function getLastCommenterID()
    {
        return $this->lastCommenterID;
    }

    public function getLastComment()
    {
        return $this->lastComment;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getIssueType()
    {
        return $this->issueType;
    }

    public function getWebhookEvent()
    {
        return $this->webhookEvent;
    }
    
    public function getIssueEvent()
    {
        return $this->issueEvent;
    }
}