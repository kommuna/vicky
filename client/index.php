<?php
/**
 * This file has logic of events that cause data obtained from JIRA webhook
 */

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

/**
 * Writes time of creating comment and username assignee to Blocker ticket
 * user to file that have name like key of issue
 */
$jiraWebhook->addListener('jira:issue_updated', function($e, $data) use ($fileClient)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented() && $issue->isPriorityBlocker()) {
        $fileClient->setCommentDataToFile(
            $issue->getKey(),
            $issue->getAssignee(),
            $issue->getIssueComments()->getLastComment()->getCreated()
        );
    }
});

/**
 * Sends alert to slack general channel at any change of Blocker ticket
 */
$jiraWebhook->addListener('*', function($e, $data) use ($botClient)
{
    if($e->getName() === 'jira:issue_created' || $e->getName() === 'jira:issue_updated') {
        $issue = $data->getIssue();

        if ($issue->isPriorityBlocker()) {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraBlockerToSlack', $data));
        }
    }
});

/**
 * Sends alert to slack general channel at creating issue with type 'Operations'
 * or 'Urgent bug'
 *
 * Also send alert to user in slack if issue of this types was assigned to him
 */
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

/**
 * Sends alert to slack general channel if issue with type 'Operations' or 'Urgent bug'
 * get status 'Resolved' or issue with status 'Urgent bug' get commented
 *
 * Also sends alert to user in slack if any issue get assigned to him
 *
 * Also sends alert to user in slack if someone create comment in issue that 
 * assigned to him
 * 
 * And send alert to user in slack if someone make reference to him in created comment
 */
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

        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();

        if (isset($users)) {
            foreach ($users as $user) {
                $botClient->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
            }
        }
    }
});

$jiraWebhook->run();