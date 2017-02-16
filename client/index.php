<?php
namespace Vicky;

use Vicky\client\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\client\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\client\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\client\modules\Jira\JiraUrgentBugToSlackBotConverter;
use Vicky\client\modules\Jira\JiraWebhook;
use Vicky\client\modules\Slack\SlackBotSender;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/clientConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

// TODO каждый метод должен быть закомментирован, у каждого файла должна быть шапка

// TODO добавить класс который будет записывать время последнего коммента в тикете с приоритетот Blocker

// TODO dependency injector Aura 3
$botClient = new SlackBotSender(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

$jiraWebhook = new JiraWebhook();

JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerToSlack', new JiraBlockerToSlackBotConverter());
JiraWebhook::setConverter('JiraOperationsToSlack', new JiraOperationsToSlackBotConverter());
JiraWebhook::setConverter('JiraUrgentBugToSlack', new JiraUrgentBugToSlackBotConverter());

$jiraWebhook->addListener('webhookEvent.IssueCreated', function ($event, $data)
{
    if ($data->getIssue()->isPriorityBlocker()) {
        $this->on('priority.Blocker', $data);
    } elseif ($data->getIssue()->isTypeOprations()) {
        $this->on('type.Operations', $data);
    } elseif ($data->getIssue()->isTypeUrgentBug()) {
        $this->on('type.UrgentBug', $data);
    }

    if ($data->getIssue()->getAssignee()) {
        $this->on('issue.Assigned', $data);
    } 
});

$jiraWebhook->addListener('webhookEvent.IssueUpdated', function ($event, $data)
{
    if ($data->getIssue()->isPriorityBlocker()) {
        $this->on('priority.Blocker', $data);
    } elseif ($data->getIssue()->isTypeOprations() && $data->getIssue()->isStatusResolved()) {
        $this->on('type.Operations', $data);
    } elseif (($data->getIssue()->isTypeUrgentBug() && $data->getIssue()->isStatusResolved()) || ($data->getIssue()->isTypeUrgentBug() && $data->isIssueCommented())) {
        $this->on('type.UrgentBug', $data);
    }

    if ($data->isIssueAssigned()) {
        $this->on('issue.Assigned', $data);
    }

    if ($data->isIssueCommented()) {
        $this->on('issue.Commented', $data);

        $refStart = $data->getIssue()->getIssueComments()->getLastComment()->isCommentReference();

        if (isset($refStart)) {
            $lastComment = $data->getIssue()->getIssueComments()->getLastCommentBody();
            $refStart += 2;
            $refEnd = stripos($lastComment, ']');
            $reference = substr($lastComment, $refStart, $refEnd - $refStart);
            $data->getIssue()->getIssueComments()->getLastComment()->setCommentReference($reference);

            $this->on('comment.Reference', $data);
        }
    }
});

$jiraWebhook->addListener('priority.Blocker', function($event, $data) use ($botClient)
{
    $this->toChannel('#general', JiraWebhook::convert('JiraBlockerToSlack', $data));
});

$jiraWebhook->addListener('type.Operations', function($event, $data) use ($botClient)
{
    $botClient->toChannel('#general', JiraWebhook::convert('JiraOperationsToSlack', $data));
});

$jiraWebhook->addListener('type.UrgentBug', function($event, $data) use ($botClient)
{
    $botClient->toChannel('#general', JiraWebhook::convert('JiraUrgentBugToSlack', $data));
});

$jiraWebhook->addListener('issue.Assigned', function($event, $data) use ($botClient)
{
    $botClient->toUser($data->getIssue()->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
});

$jiraWebhook->addListener('issue.Commented', function($event, $data) use ($botClient)
{
    $botClient->toUser($data->getIssue()->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
});

$jiraWebhook->addListener('comment.Reference', function($event, $data) use ($botClient)
{
    $botClient->toUser(
        $data->getIssue()->getIssueComments()->getLastComment()->getCommentReference(), 
        JiraWebhook::convert('JiraDefaultToSlack', $data)
    );
});

//$data = $jiraWebhook->extractData();
//error_log(print_r($data->getRawData()));

$jiraWebhook->run();