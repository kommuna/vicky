<?php
namespace Vicky\client\modules\Jira;

class JiraIssue
{
    private $number;
    private $URL;
    private $status;
    private $summary;
    private $assignee;
    private $priority;
    private $issueType;

    public static function parseWebhookData($data = null)
    {
        $issueData = new self;

        if ($data === null) {
            return $issueData;
        }

        $issueFields = $data['fields'];

        $issueData->setNumber($data['issue']['key']);
        $issueData->setURL($data['issue']['self']);
        $issueData->setStatus($issueFields['status']['name']);
        $issueData->setSummary($issueFields['summary']);
        $issueData->setAssignee($issueFields['assignee']['name']);
        $issueData->setPriority($issueFields['priority']['name']);
        $issueData->setIssueType($issueFields['issuetype']['name']);

        return $issueData;
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

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function setIssueType($issueType)
    {
        $this->issueType = $issueType;
    }

    /**************************************************/

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

    public function getPriority()
    {
        return $this->priority;
    }

    public function getIssueType()
    {
        return $this->issueType;
    }
}