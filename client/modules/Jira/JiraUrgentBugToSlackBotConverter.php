<?php
namespace Vicky\client\modules\Jira;

class JiraUrgentBugToSlackBotConverter extends JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     *
     * @param JiraWebhookData $data
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        if (!$data->getLastComment()) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ @%s",
                [
                    $data->getNumber(),
                    $data->getURL(),
                    $data->getStatus(),
                    $data->getSummary(),
                    $data->getAssignee()
                ]
            );
        } elseif (!$data->getAssignee()) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $data->getNumber(),
                    $data->getURL(),
                    $data->getStatus(),
                    $data->getSummary(),
                    $data->getLastCommenterID(),
                    $data->getLastComment()
                ]
            );
        } elseif (!$data->getLastComment() && !$data->getAssignee()) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ Unassigned",
                [
                    $data->getNumber(),
                    $data->getURL(),
                    $data->getStatus(),
                    $data->getSummary()
                ]
            );
        } else {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $data->getNumber(),
                    $data->getURL(),
                    $data->getStatus(),
                    $data->getSummary(),
                    $data->getAssignee(),
                    $data->getLastCommenterID(),
                    $data->getLastComment()
                ]
            );
        }

        return $message;
    }
}