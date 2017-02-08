<?php
namespace Vicky\client\modules;

class JiraWebhookData
{
    private $number;
    private $URL;
    private $status;
    private $summary;
    private $assignee;
    private $commenterID;
    private $lastComment;
    
    private $priority;
    private $issueType;
    private $webhookEvent;

    private $issueEvent;

    /**
     * Data constructor.
     *
     * Parsing data from JIRA
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->number       = $data->issue->key;
        $this->URL          = $data->issue->self;
        $this->status       = $data->issue->fields->status->name;
        $this->summary      = $data->issue->fields->summary;
        $this->assignee     = $data->issue->fields->assignee->name;
        
        $n = count($data->issue->fields->comment->comments) - 1;
        
        $this->commenterID  = $data->issue->fields->comment->comments[$n]->author->name;
        $this->lastComment  = $data->issue->fields->comment->comments[$n]->body;
        
        $this->priority     = $data->issue->fields->priority->name;
        $this->issueType    = $data->issue->fields->issuetype->name;
        $this->webhookEvent = $data->webhookEvent;

        $this->issueEvent   = $data->issue_event_type_name;
    }
    
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

    public function getCommenterID()
    {
        return $this->commenterID;
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