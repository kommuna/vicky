<?php
namespace Vicky;

use Vicky\client\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\client\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\client\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\client\modules\Jira\JiraUrgentBugToSlackBotConverter;
use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;
use Vicky\client\modules\BlockersIssueFile;
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

$botClient = SlackBotSender::getInstance(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

$jiraWebhook = new JiraWebhook();

$fileClient = new BlockersIssueFile($config['pathToBlockerFile']);

JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerToSlack', new JiraBlockerToSlackBotConverter());
JiraWebhook::setConverter('JiraOperationsToSlack', new JiraOperationsToSlackBotConverter());
JiraWebhook::setConverter('JiraUrgentBugToSlack', new JiraUrgentBugToSlackBotConverter());

$jiraWebhook->addListener('jira:issue_updated', function($e, $data) use ($fileClient)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented() && $issue->isPriorityBlocker()) {
        $fileClient->setCommentTimeToFile(
            $issue->getKey(),
            $issue->getAssignee(),
            $issue->getIssueComments()->getLastComment()->getUpdated()
        );
    }
});

$jiraWebhook->addListener('*', function($e, $data) use ($botClient)
{
    if($e->getName() === 'jira:issue_created' || $e->getName() === 'jira:issue_updated') {
        $issue = $data->getIssue();

        if ($issue->isPriorityBlocker()) {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraBlockerToSlack', $data));
        }
    }
});

$jiraWebhook->addListener('jira:issue_created', function ($e, $data) use ($botClient)
{
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

$jiraWebhook->addListener('jira:issue_updated', function ($e, $data) use ($botClient)
{
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

        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();

        if (isset($users)) {
            foreach ($users as $user) {
                $botClient->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
            }
        }
    }
});

$jiraWebhook->run();