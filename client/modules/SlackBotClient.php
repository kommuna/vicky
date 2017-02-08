<?php
namespace Vicky\client\modules;

use Vicky\client\modules\SlackBotSender;
use Vicky\client\modules\JiraWebhook;
use Vicky\client\modules\JiraWebhookData;

class SlackBotClient extends SlackBotSender
{
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
        $commenterID,
        $lastComment)
    {
        return "<{$number}> ({$URL}) <{$status}>: <{$summary}> ➠ <@{$assignee}>\n<@{$commenterID}> ➠ <{$lastComment}>";
    }

    /**
     * Receiving data from JIRA, creating message and send it to slack bot
     */
    public function parseData()
    {
        $receiver = new JiraWebhook();
        $data = new JiraWebhookData($receiver->process());

        $status = $data->getStatus();

        $message = $this->getMessage(
            $data->getNumber(),
            $data->getURL(),
            $status,
            $data->getSummary(),
            $data->getAssignee(),
            $data->getCommenterID(),
            $data->getLastComment()
        );

        $priority  = $data->getPriority();
        $issueType = $data->getIssueType();
        $webhookEvent = $data->getWebhookEvent();
        $issueEvent = $data->getIssueEvent();

        if ($priority === 'Blocker') {
            $message = '!!! '.$message;
            $this->toChannel('#general', $message);
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