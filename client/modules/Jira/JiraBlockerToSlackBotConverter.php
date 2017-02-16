<?php
namespace Vicky\client\modules\Jira;

class JiraBlockerToSlackBotConverter extends JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     *
     * @param JiraWebhookData $data
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        if (!$data->getIssue()->getIssueComments()->getComments()) {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ @%s",
                [
                    $data->getIssue()->getKey(),
                    $data->getIssue()->getSelf(),
                    $data->getIssue()->getStatus(),
                    $data->getIssue()->getSummary(),
                    $data->getIssue()->getAssignee()
                ]
            );
        } elseif (!$data->getIssue()->getAssignee()) {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $data->getIssue()->getKey(),
                    $data->getIssue()->getSelf(),
                    $data->getIssue()->getStatus(),
                    $data->getIssue()->getSummary(),
                    $data->getIssue()->getLastCommenterID(),
                    $data->getIssue()->getLastComment()
                ]
            );
        } elseif (!$data->getIssue()->getIssueComments()->getComments() && !$data->getIssue()->getAssignee()) {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ Unassigned",
                [
                    $data->getIssue()->getKey(),
                    $data->getIssue()->getSelf(),
                    $data->getIssue()->getStatus(),
                    $data->getIssue()->getSummary()
                ]
            );
        } else {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $data->getIssue()->getKey(),
                    $data->getIssue()->getSelf(),
                    $data->getIssue()->getStatus(),
                    $data->getIssue()->getSummary(),
                    $data->getIssue()->getAssignee(),
                    $data->getIssue()->getLastCommenterID(),
                    $data->getIssue()->getLastComment()
                ]
            );
        }

        return $message;
    }
}