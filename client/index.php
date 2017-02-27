<?php
/**
 * This file has logic of events that cause data obtained from JIRA webhook
 */

namespace Vicky;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Vicky\client\exceptions\SlackBotSenderException;
use Vicky\client\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\client\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\client\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\client\modules\Jira\JiraUrgentBugToSlackBotConverter;
use Vicky\client\modules\Slack\SlackBotSender;
use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require (isset($argv[1])) ? $argv[1] : '/etc/vicky/clientConfig.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
ini_set('max_execution_time', 0);
date_default_timezone_set('Europe/Moscow');

$log = new Logger('vicky');
$log->pushHandler(new StreamHandler($config['error_log'], Logger::DEBUG));

$start = microtime(true);

$log->info("The script {$argv[0]} started.");

$botClient = SlackBotSender::getInstance(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

$jiraWebhook = new JiraWebhook();

JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerToSlack', new JiraBlockerToSlackBotConverter());
JiraWebhook::setConverter('JiraOperationsToSlack', new JiraOperationsToSlackBotConverter());
JiraWebhook::setConverter('JiraUrgentBugToSlack', new JiraUrgentBugToSlackBotConverter());

/**
 * Send message to slack general channel at creating or any change of Blocker issue
 */
$jiraWebhook->addListener('*', function($e, $data) use ($botClient, $log)
{
    if($e->getName() === 'jira:issue_created' || $e->getName() === 'jira:issue_updated') {
        $issue = $data->getIssue();

        if ($issue->isPriorityBlocker()) {
            try {
                $botClient->toChannel('#general', JiraWebhook::convert('JiraBlockerToSlack', $data));
            } catch (SlackBotSenderException $e) {
                $log->error($e->getMessage());
            }
        }
    }
});

/**
 * Send message to slack general channel at creating issue with type 'Operations'
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($issue->isTypeOprations()) {
        try {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraOperationsToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to slack general channel at creating issue with type 'Urgent bug'
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($issue->isTypeUrgentBug()) {
        try {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraUrgentBugToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to user in slack if created issue was assigned to him
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($issue->getAssignee()) {
        try {
            $botClient->toUser($issue->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to slack general channel if issue with type 'Operations'
 * get status 'Resolved'
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($issue->isTypeOprations() && $issue->isStatusResolved()) {
        try {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraOperationsToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to slack general channel if issue with type 'Urgent bug'
 * get status 'Resolved' or get commented
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($issue->isTypeUrgentBug() && ($issue->isStatusResolved() || $data->isIssueCommented())) {
        try {
            $botClient->toChannel('#general', JiraWebhook::convert('JiraUrgentBugToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to user in slack if any issue get assigned to him
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($data->isIssueAssigned()) {
        try {
            $botClient->toUser($issue->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to user in slack if someone create comment in issue that
 * assigned to him
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        try {
            $botClient->toUser($issue->getAssignee(), JiraWebhook::convert('JiraDefaultToSlack', $data));
        } catch (SlackBotSenderException $e) {
            $log->error($e->getMessage());
        }
    }
});

/**
 * Send message to user in slack if someone make reference to him in created comment
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data) use ($botClient, $log)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();

        if (isset($users)) {
            foreach ($users as $user) {
                try {
                    $botClient->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
                } catch (SlackBotSenderException $e) {
                    $log->error($e->getMessage());
                }
            }
        }
    }
});

$jiraWebhook->run();

$log->info("Script finished in ".(microtime(true) - $start)." sec.");
