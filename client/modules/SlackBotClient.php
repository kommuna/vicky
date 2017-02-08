<?php
namespace Vicky\client\modules;

use Vicky\client\modules\SlackBotSender;
use Vicky\client\modules\JiraWebhook;
use Vicky\client\modules\JiraWebhookData;

class SlackBotClient extends SlackBotSender
{
    private static $data;

    public function __construct($slackBotUrl, $auth)
    {
        if (!self::$data) {
            $receiver = new JiraWebhook();
            self::$data = JiraWebhookData::parseWebhookData($receiver->process());
        }

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
        $status = self::$data->getStatus();

        $message = $this->getMessage(
            self::$data->getNumber(),
            self::$data->getURL(),
            $status,
            self::$data->getSummary(),
            self::$data->getAssignee(),
            self::$data->getLastCommenterID(),
            self::$data->getLastComment()
        );

        $priority  = self::$data->getPriority();
        $issueType = self::$data->getIssueType();
        $webhookEvent = self::$data->getWebhookEvent();
        $issueEvent = self::$data->getIssueEvent();

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