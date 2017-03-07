<?php
/**
 * This file is part of vicky.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Vicky\client\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\client\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\client\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\client\modules\Jira\JiraUrgentBugToSlackBotConverter;
use Vicky\client\modules\Slack\SlackBotSender;

use JiraWebhook\JiraWebhook;
use JiraWebhook\Exceptions\JiraWebhookException;
use Vicky\client\modules\Vicky;

require dirname(__DIR__).'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['error_log']);
date_default_timezone_set($config['timeZone']);

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['error_log'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);

$start = microtime(true);

$log->info("The script ".__FILE__." started.");

SlackBotSender::setConfigs(
    $config['curlOpt']['url'],
    $config['curlOpt']['auth']
);

$jiraWebhook = new JiraWebhook();

$vicky = new Vicky($config);

/**
 * Set converters
 */
JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerToSlack', new JiraBlockerToSlackBotConverter());
JiraWebhook::setConverter('JiraOperationsToSlack', new JiraOperationsToSlackBotConverter());
JiraWebhook::setConverter('JiraUrgentBugToSlack', new JiraUrgentBugToSlackBotConverter());

/**
 * Send message to slack general channel at creating or any change of Blocker issue
 */
$jiraWebhook->addListener('*', function($e, $data)
{
    if($e->getName() === 'jira:issue_created' || $e->getName() === 'jira:issue_updated') {
        $issue = $data->getIssue();

        if ($issue->isPriorityBlocker()) {
            SlackBotSender::getInstance()->toChannel(
                Vicky::getChannelByProject($issue->getProjectName()), 
                JiraWebhook::convert('JiraBlockerToSlack', $data)
            );
        }
    }
});

/**
 * Send message to slack general channel at creating issue with type 'Operations'
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($issue->isTypeOprations()) {
        SlackBotSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectName()), 
            JiraWebhook::convert('JiraOperationsToSlack', $data)
        );
    }
});

/**
 * Send message to slack general channel at creating issue with type 'Urgent bug'
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($issue->isTypeUrgentBug()) {
        SlackBotSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectName()), 
            JiraWebhook::convert('JiraUrgentBugToSlack', $data)
        );
    }
});

/**
 * Send message to user in slack if created issue was assigned to him
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data)
{
    $assignee = $data->getIssue()->getAssignee();

    if ($assignee->getName()) {
        SlackBotSender::getInstance()->toUser($assignee->getName(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to slack general channel if issue with type 'Operations'
 * get status 'Resolved'
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($issue->isTypeOprations() && $issue->isStatusResolved()) {
        SlackBotSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectName()), 
            JiraWebhook::convert('JiraOperationsToSlack', $data)
        );
    }
});

/**
 * Send message to slack general channel if issue with type 'Urgent bug'
 * get status 'Resolved' or get commented
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($issue->isTypeUrgentBug() && ($issue->isStatusResolved() || $data->isIssueCommented())) {
        SlackBotSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectName()), 
            JiraWebhook::convert('JiraUrgentBugToSlack', $data)
        );
    }
});

/**
 * Send message to user in slack if any issue get assigned to him
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueAssigned()) {
        SlackBotSender::getInstance()->toUser($issue->getAssignee()->getName(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to user in slack if someone create comment in issue that
 * assigned to him
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        SlackBotSender::getInstance()->toUser($issue->getAssignee()->getName(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to user in slack if someone make reference to him in created comment
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();

        foreach ($users as $user) {
            SlackBotSender::getInstance()->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
        }
    }
});

try {
    /**
     * Get raw data from JIRA webhook
     */
    $f = fopen('php://input', 'r');
    $data = stream_get_contents($f);

    if (!$data) {
        $log->error('There is not data in the Jira webhook');
        throw new JiraWebhookException('There is no data in the Jira webhook');
    }

    $jiraWebhook->run($data);
} catch (\Exception $e) {
    // For convenience in local development show errors on screen directly
    if ($config['environment'] == 'local'){
        var_dump($e->getMessage());
        var_dump($e->getLine());
        var_dump($e->getFile());
        var_dump($e->getCode());
    }
    $log->error($e->getMessage());
}

$log->info("Script finished in ".(microtime(true) - $start)." sec.");
