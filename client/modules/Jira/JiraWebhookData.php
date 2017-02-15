<?php
namespace Vicky\client\modules\Jira;

class JiraWebhookData
{
    private $rawData;
    private $webhookEvent;
    private $issueEvent;

    private $jiraIssue;
    private $jiraIssueComments;
    
    public static function parseWebhookData($data = null)
    {
        // TODO need to add check for every data that parsing here

        $webhookData = new self;
        
        if ($data === null) {
            return $webhookData;
        }
        
        $webhookData->setRawData($data);
        $webhookData->setWebhookEvent($data['webhookEvent']);
        $webhookData->setIssueEvent($data['issue_event_type_name']);
        
        $webhookData->setJiraIssue($data['issue']);
        $webhookData->setJiraIssueComments($data['issue']['fields']['comment']);

        return $webhookData;
    }
    
    public function isPriorityBlocker()
    {
        return $this->jiraIssue->getPriority() === 'Blocker';
    }
    
    public function isTypeOprations()
    {
        return $this->jiraIssue->getIssueType() === 'Operations';
    }

    public function isTypeUrgentBug()
    {
        return $this->jiraIssue->getIssueType() === 'Urgent bug';
    }

    public function isStatusResolved()
    {
        return $this->jiraIssue->getStatus() === 'Resolved';
    }
    
    public function isIssueCommented()
    {
        return $this->issueEvent === 'issue_commented';
    }

    public function isIssueAssigned()
    {
        return $this->issueEvent === 'issue_assigned';
    }

    public function isCommentReference()
    {
        return stripos($this->getLastComment(), '[~');
    }

    /**************************************************/

    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
    }

    public function setWebhookEvent($webhookEvent)
    {
        $this->webhookEvent = $webhookEvent;
    }

    public function setIssueEvent($issueEvent)
    {
        $this->issueEvent = $issueEvent;
    }
    
    public function setJiraIssue($issueData)
    {
        $this->jiraIssue = JiraIssue::parseWebhookData($issueData);
    }
    
    public function setJiraIssueComments($issueCommentsData)
    {
        $this->jiraIssueComments = JiraIssueComments::parseWebhookData($issueCommentsData);
    }

    /**************************************************/

    public function getRawData()
    {
        return $this->rawData;
    }

    public function getWebhookEvent()
    {
        return $this->webhookEvent;
    }

    public function getIssueEvent()
    {
        return $this->issueEvent;
    }
    
    public function getNumber()
    {
        return $this->jiraIssue->getNumber();
    }

    public function getURL()
    {
        return $this->jiraIssue->getURL();
    }

    public function getStatus()
    {
        return $this->jiraIssue->getStatus();
    }

    public function getSummary()
    {
        return $this->jiraIssue->getSummary();
    }

    public function getAssignee()
    {
        return $this->jiraIssue->getAssignee();
    }

    public function getPriority()
    {
        return $this->jiraIssue->getPriority();
    }

    public function getIssueType()
    {
        return $this->jiraIssue->getIssueType();
    }

    public function getLastCommenterID()
    {
        return $this->jiraIssueComments->getLastCommenterID();
    }

    public function getLastComment()
    {
        return $this->jiraIssueComments->getLastComment();
    }

    public function getCommentReference()
    {
        return $this->jiraIssueComments->getCommentReference();
    }
}