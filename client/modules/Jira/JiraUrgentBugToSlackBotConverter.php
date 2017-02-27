<?php
/**
 * This file contains a JIRA data converter
 *
 * This file contains class with converter method,
 * that converts data from JIRA to string message for issues with type 'Urgent bug'
 */
namespace Vicky\client\modules\Jira;

use JiraWebhook\JiraWebhookDataConverter;
use JiraWebhook\Models\JiraWebhookData;

class JiraUrgentBugToSlackBotConverter extends JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     *
     * @param  JiraWebhookData $data parsed data from JIRA
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        $issue = $data->getIssue();
        $comment = $issue->getIssueComments()->getLastComment();
        $author = $comment->getAuthor();

        if (!$comment) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ @%s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $issue->getAssignee()
                ]
            );
        } elseif (!$issue->getAssignee()) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $author->getName(),
                    $comment->getBody()
                ]
            );
        } elseif (!$comment && !$issue->getAssignee()) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ Unassigned",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary()
                ]
            );
        } else {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $issue->getAssignee(),
                    $author->getName(),
                    $comment->getBody()
                ]
            );
        }

        return $message;
    }
}