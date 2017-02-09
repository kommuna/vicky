<?php
namespace Vicky\client\modules;

class SlackBotClient extends SlackBotSender
{
    // TODO добавить метод setConverter(), который задает конвертер сообщений
    private $data;
    private $converter;

    public function setConverter($converter)
    {
        $this->converter = $converter;
    }
    

    /**
     * Receiving data from JIRA, creating message and send it to slack bot
     */
    public function send($data)
    {
        $message = $this->converter->convert($data);
        
        $status = $data->getStatus();
        $priority  = $data->getPriority();
        $issueType = $data->getIssueType();
        $webhookEvent = $data->getWebhookEvent();
        $issueEvent = $data->getIssueEvent();

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