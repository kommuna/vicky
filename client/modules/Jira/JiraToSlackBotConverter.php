<?php
namespace Vicky\client\modules\Jira;

class JiraToSlackBotConverter extends JiraConverter
{
    public function convert(JiraWebhookData $data)
    {
        //return "<{$number}> ({$URL}) <{$status}>: <{$summary}> ➠ <@{$assignee}>\n<@{$lastCommenterID}> ➠ <{$lastComment}>";
        return sprintf(
            "<%s> (%s) <%s>: <%s> ➠ <@%s>\n<@%s> ➠ <%s>", 
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
}