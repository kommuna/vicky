<?php
namespace Vicky\client\modules\Jira;

class JiraToSlackBotConverter extends JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     * 
     * @param JiraWebhookData $data
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        //Old method, delete this after testing new
        //return "<{$number}> ({$URL}) <{$status}>: <{$summary}> ➠ <@{$assignee}>\n<@{$lastCommenterID}> ➠ <{$lastComment}>";
        
        // TODO need to check comments, if they are missing send message without them

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