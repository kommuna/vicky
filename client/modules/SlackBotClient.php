<?php
namespace Vicky\client\modules;

use Vicky\client\modules\SlackBotSender;
use Vicky\client\modules\JiraWebhook;
use Vicky\client\modules\JiraWebhookData;

class SlackBotClient extends SlackBotSender
{
    private $data;

    public function __construct($slackBotUrl, $auth)
    {
        $receiver = new JiraWebhook();
        $this->data = JiraWebhookData::parseWebhookData($receiver->process());

        parent::__construct($slackBotUrl, $auth);
    }

    /**
     * Forming the message
     *
     * @param $number
     * @param $URL
     * @param $status
     * @param $summary
     * @param $assignee
     * @param $commenterID
     * @param $lastComment
     *
     * @return string
     */
    public function getMessage(
        $number,
        $URL,
        $status,
        $summary,
        $assignee,
        $lastCommenterID,
        $lastComment)
    {
        return "<{$number}> ({$URL}) <{$status}>: <{$summary}> ➠ <@{$assignee}>\n<@{$lastCommenterID}> ➠ <{$lastComment}>";
    }

    /**
     * Receiving data from JIRA, creating message and send it to slack bot
     */
    public function parseData()
    {
        $status = $this->data->getStatus();

        $message = $this->getMessage(
            $this->data->getNumber(),
            $this->data->getURL(),
            $status,
            $this->data->getSummary(),
            $this->data->getAssignee(),
            $this->data->getLastCommenterID(),
            $this->data->getLastComment()
        );

        $priority  = $this->data->getPriority();
        $issueType = $this->data->getIssueType();
        $webhookEvent = $this->data->getWebhookEvent();
        $issueEvent = $this->data->getIssueEvent();

        if ($priority === 'Blocker') {
            $message = '!!! '.$message;
            //$this->toChannel('#general', $message);
            $this->toUser('chewbacca', $message);
        } elseif ($issueType === 'Operations') {
            if ($webhookEvent === 'jira:issue_created' || $status === 'Resolved') {
                $message = '⚙ '.$message;
                $this->toChannel('#general', $message);
            }
        } elseif ($issueType === 'Urgent bug') {
            if ($webhookEvent === 'jira:issue_created' || $status === 'Resolved' || $issueEvent === 'issue_commented') {
                $message = '⚡ '.$message;
                $this->toChannel('#general', $message);
            }
        }
    }
}