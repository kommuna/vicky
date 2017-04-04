<?php
/**
 * Main module of vicky project, that receives data from JIRA webhook
 * (https://developer.atlassian.com/jiradev/jira-apis/webhooks), contains jiraWebhook listeners for events, that sends
 * messages to slack by slack client and contains converters declaration.
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
use DateTime;
use DateInterval;

use Vicky\src\modules\Jira\IssueFile;
use Vicky\src\modules\Jira\JiraBlockerNotificationConverter;
use Vicky\src\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\src\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\src\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\src\modules\Jira\JiraUrgentBugToSlackBotConverter;
use Vicky\src\modules\Slack\SlackBotSender;
use Vicky\src\modules\Vicky;

use JiraWebhook\JiraWebhook;
use JiraWebhook\Exceptions\JiraWebhookException;

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

SlackBotSender::getInstance(
    $config['slackBot']['url'],
    $config['slackBot']['auth'],
    $config['slackBot']['timeout']
);

$jiraWebhook = new JiraWebhook();

$vicky = new Vicky($config);

IssueFile::setPathToFolder($config['blockersIssues']['folder']);

/**
 * Set converters
 */
JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerToSlack', new JiraBlockerToSlackBotConverter());
JiraWebhook::setConverter('JiraOperationsToSlack', new JiraOperationsToSlackBotConverter());
JiraWebhook::setConverter('JiraUrgentBugToSlack', new JiraUrgentBugToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerNotification', new JiraBlockerNotificationConverter());

/**
 * Send message to slack general channel at creating or any change of
 * Blocker issue
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
 * If issue priority updates to blocker and file for this issue don't exists
 * creates file for this issue
 */
$jiraWebhook->addListener('jira:issue_updated', function($e, $data) {
    $issue = $data->getIssue();

    if ($issue->isPriorityBlocker()) {
        IssueFile::create($issue->getKey(), $data, time());
    }
});

/**
 * If issue created with blocker priority or blocker issue commented
 * stores object with parsed data from JIRA and current time
 */
$jiraWebhook->addListener('*', function($e, $data)
{
    $issue = $data->getIssue();

    if (($e->getName() === 'jira:issue_created' && $issue->isPriorityBlocker()) || ($e->getName() === 'jira:issue_updated' && $issue->isPriorityBlocker() && $data->isIssueCommented())) {
        $issueFile = new IssueFile($issue->getKey(), $data, time());
        IssueFile::put($issueFile);
    }
});

/**
 * Delete blockers issue file if issue deleted, issue priority not Blocker anymore,
 * issue has status Resolved or Close
 */
$jiraWebhook->addListener('*', function($e, $data)
{
    $issue = $data->getIssue();

    if ($e->getName() === 'jira:issue_deleted' || !$issue->isPriorityBlocker() || $issue->isStatusResolved() || $issue->isStatusClose()) {
        $issueFile = IssueFile::get(IssueFile::getPathToFolder().$issue->getKey());
        IssueFile::delete($issueFile);
    }
});

/**
 * Send message to assignee user if blockers issue not commented more
 * than 24 hours and stores data with updated notification datatime
 */
$jiraWebhook->addListener('custom:blocker_notification', function($e, $data)
{
    $issue = $data->getIssue();

    SlackBotSender::getInstance()->toUser(
        $issue->getAssignee()->getName(),
        JiraWebhook::convert('JiraBlockerNotification', $data)
    );

    $issueFile = new IssueFile($issue->getKey(), $data, time());
    IssueFile::put($issueFile);
});

/**
 * Custom *
 *
 * Send message to slack general channel at creating issue with type 'Operations'
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($issue->isTypeOperations()) {
        SlackBotSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectName()), 
            JiraWebhook::convert('JiraOperationsToSlack', $data)
        );
    }
});

/**
 * Custom *
 *
 * Send a message to the project's channel when an issue with
 * type 'Urgent bug' was created
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
 * Send a message to the user in slack if a newly created issue
 * was assigned to them
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, $data)
{
    $assignee = $data->getIssue()->getAssignee();

    if ($assignee->getName()) {
        SlackBotSender::getInstance()->toUser($assignee->getName(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Custom *
 *
 * Send a message to the project channel if an issue with type
 * 'Operations' gets status 'Resolved'
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($issue->isTypeOperations() && $issue->isStatusResolved()) {
        SlackBotSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectName()), 
            JiraWebhook::convert('JiraOperationsToSlack', $data)
        );
    }
});

/**
 * Custom *
 *
 * Send a message to slack project channel if an issue with type 'Urgent bug'
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
 * Send a message to user in slack if an issue gets assigned to them
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueAssigned()) {
        SlackBotSender::getInstance()->toUser(
            $issue->getAssignee()->getName(),
            JiraWebhook::convert('JiraDefaultToSlack', $data)
        );
    }
});

/**
 * Send a message to user in slack if someone comments on an issue
 * assigned to them
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        SlackBotSender::getInstance()->toUser(
            $issue->getAssignee()->getName(),
            JiraWebhook::convert('JiraDefaultToSlack', $data)
        );
    }
});

/**
 * Send message to user in slack if someone mentions them in a new comment
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
        $log->error('There is no data in the Jira webhook');
        throw new JiraWebhookException('There is no data in the Jira webhook');
    }

    $jiraWebhook->run($data);
} catch (\Exception $e) {
    // For convenience in local development show errors on screen directly
    if ($config['environment'] === 'local'){
        var_dump($e->getMessage());
        var_dump($e->getLine());
        var_dump($e->getFile());
        var_dump($e->getCode());
    }
    $log->error($e->getMessage());
}

$log->info("Script finished in ".(microtime(true) - $start)." sec.");