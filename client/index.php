<?php
namespace Vicky;

use Vicky\client\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\client\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\client\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\client\modules\Jira\JiraUrgentBugToSlackBotConverter;
use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;
use Vicky\client\modules\Slack\SlackBotSender;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/clientConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

// TODO all methods should be commented out, each file must be a cap of a comment

// TODO add templates for converters

// TODO add a class to test the last time a comment

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

$jiraWebhook->addListener('*', function($e) use ($botClient) {
    if($e->getName() === 'jira:issue_created' || $e->getName() === 'jira:issue_updated') {
        $data = $this->getData();
        $issue = $data->getIssue();

        if ($issue->isPriorityBlocker()) {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraBlockerToSlack', $data));
        }
    }
});

$jiraWebhook->addListener('jira:issue_created', function ($e) use ($botClient)
{
    $data = $this->getData();
    $issue = $data->getIssue();

    if ($issue->isTypeOprations()) {
        $botClient->toChannel('#general', JiraWebhook::convert('JiraOperationsToSlack', $data));
    } elseif ($issue->isTypeUrgentBug()) {
        $botClient->toChannel('#general', JiraWebhook::convert('JiraUrgentBugToSlack', $data));
    }

    if ($issue->getAssignee()) {
        $botClient->toUser($issue->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    } 
});

$jiraWebhook->addListener('jira:issue_updated', function ($e) use ($botClient)
{
    $data = $this->getData();
    $issue = $data->getIssue();

    if ($issue->isTypeOprations() && $issue->isStatusResolved()) {
        $botClient->toChannel('#general', JiraWebhook::convert('JiraOperationsToSlack', $data));
    } elseif ($issue->isTypeUrgentBug() && ($issue->isStatusResolved() || $data->isIssueCommented())) {
        $botClient->toChannel('#general', JiraWebhook::convert('JiraUrgentBugToSlack', $data));
    }

    if ($data->isIssueAssigned()) {
        $botClient->toUser($issue->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    }

    if ($data->isIssueCommented()) {
        $botClient->toUser($issue->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));

        $users = array_pop($data->getIssue()->getIssueComments()->getLastComment()->getMentionedUsersNicknames());

        if ($users) {
            foreach ($users as $user) {
                $botClient->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
            }
        }

        // TODO need rework
        /*$refStart = $issue->getIssueComments()->getLastComment()->isCommentReference();

        if (isset($refStart)) {
            $lastComment = $issue->getIssueComments()->getLastCommentBody();
            $refStart += 2;
            $refEnd = stripos($lastComment, ']');
            $reference = substr($lastComment, $refStart, $refEnd - $refStart);
            $issue->getIssueComments()->getLastComment()->setCommentReference($reference);

            $botClient->toUser(
                $issue->getIssueComments()->getLastComment()->getCommentReference(),
                JiraWebhook::convert('JiraDefaultToSlack', $data)
            );
        }*/
    }
});

$data = $jiraWebhook->extractData();
//error_log(print_r($data->getRawData()), true);
error_log(print_r($data->getIssue()->getIssueComments()->getLastComment()->getMentionedUsersNicknames(), true));

$jiraWebhook->run();