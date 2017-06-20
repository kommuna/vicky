<?php
/**
 * Main module of vicky project, that receives data from JIRA webhook
 * https://developer.atlassian.com/jiradev/jira-apis/webhooks), contains jiraWebhook listeners for events, that sends
 * messages to slack by slack client and contains converters declaration.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use kommuna\vicky\modules\Jira\JiraDefaultToSlackConverter;

use kommuna\vicky\modules\Jira\IssueFile;
use kommuna\vicky\modules\Slack\SlackMessageSender;
use kommuna\vicky\modules\Vicky;

use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;
use JiraWebhook\Exceptions\JiraWebhookException;

require __DIR__.'/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['errorLog']);
ini_set('error_reporting', $config['errorReporting']);
date_default_timezone_set($config['timeZone']);

$log = new Logger('vicky');
$log->pushHandler(
    new StreamHandler(
        $config['errorLog'],
        $config['loggerDebugLevel'] ? Logger::DEBUG : Logger::ERROR
    )
);

$start = microtime(true);

$log->debug("The script ".__FILE__." started.");

SlackMessageSender::getInstance(
    $config['slackMessageSender']['webhookUrl'],
    $config['slackMessageSender']['botUsername'],
    $config['slackMessageSender']['unfurl']
);

Vicky::setConfig($config);
$jiraWebhook = new JiraWebhook();

/**
 * Set the converters Vicky will use to "translate" JIRA webhook
 * payload into formatted, human readable Slack messages
 */
JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackConverter());

/*
|--------------------------------------------------------------------------
| Register default listeners
|--------------------------------------------------------------------------
|
| These are just a few default listeners that would make sense for most teams.
| To add your own you should follow instructions in the README.md file
|
*/

$jiraWebhook->addListener('*', function($e, $data)
{
    $issue = $data->getIssue();
    $eventName = $e->getName();

    /**
     * Sets path to folder where would be stores issue files
     */
    IssueFile::setPathToFolder(Vicky::getConfig()['blockersIssues']['folder']);

    /**
     * If file for this issue don't exists creates file with parsed for this issue,
     * or returning data from file
     */
    IssueFile::create($issue->getKey(), $data);

    /**
     * Stores object with parsed data from JIRA and current time
     */
    IssueFile::put(new IssueFile($issue->getKey(), $data));

    /**
     * Delete issue file if issue deleted, issue has status Resolved
     */
    if ($eventName === 'jira:issue_deleted' || $issue->isStatusResolved()) {
        IssueFile::delete($issue->getKey());
    }
});

/**
 * Listener with custom event for notification about issue file with way that you'd like,
 * and updating information about last notification in that issue file
 */
$jiraWebhook->addListener('custom:event_name', function($e, $data)
{
    $issue = $data->getIssue();
    $assigneeName = $issue->getAssignee()->getName();

    if ($assigneeName) {
        SlackMessageSender::getInstance()->toUser($assigneeName, JiraWebhook::convert('JiraDefaultToSlack', $data));
    }

    IssueFile::setPathToFolder(Vicky::getConfig()['blockersIssues']['folder']);
    IssueFile::updateLastNotificationTime($issue->getKey());
});

/**
 * Send a message to the project's channel when issue is created or updated
 */
$jiraWebhook->addListener('*', function($e, JiraWebhookData $data)
{
    if($e->getName() === 'jira:issue_created' || $e->getName() === 'jira:issue_updated') {
        $issue = $data->getIssue();

        SlackMessageSender::getInstance()->toChannel(
            Vicky::getChannelByProject($issue->getProjectKey()),
            JiraWebhook::convert('JiraDefaultToSlack', $data)
        );
    }
});

/**
 * Send message to user if a newly created issue
 * was assigned to them, but if assigned user is not the creator
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, JiraWebhookData $data)
{
    $assigneeName = $data->getIssue()->getAssignee()->getName();
    $userName = $data->getUser()->getName();

    if ($assigneeName && $userName != $assigneeName) {
        SlackMessageSender::getInstance()->toUser($assigneeName, JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to user's channel if an issue gets assigned to them,
 * but if the user has not assigned himself
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $changelog = $data->getChangelog();
    $userName = $data->getUser()->getName();
    $assigneeName = $data->getIssue()->getAssignee()->getName();

    if ($changelog->isIssueAssigned() && $userName != $assigneeName) {
        SlackMessageSender::getInstance()->toUser($assigneeName, JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to user's channel if someone comments on an issue
 * assigned to them, but if the author of the comment is not assigned user
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();
    $assigneeName = $issue->getAssignee()->getName();
    $issueComments = $issue->getIssueComments();
    $commentAuthorName = $issueComments->getComments() ? $issueComments->getLastCommenterName() : '';

    if ($data->isIssueCommented() && $assigneeName && $commentAuthorName != $assigneeName) {
        SlackMessageSender::getInstance()->toUser($assigneeName, JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to user's channel if someone mentions them in a new comment,
 * but if mentioned user not assigned to this issue
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();
        $assigneeName = $issue->getAssignee()->getName();

        foreach ($users as $user) {
            if ($user != $assigneeName) {
                SlackMessageSender::getInstance()->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
            }
        }
    }
});

/**
 * Send message to channel if someone referenced channel label in a new comment
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    if ($data->isIssueCommented()) {
        $commentBody = $data->getIssue()->getIssueComments()->getLastComment()->bodyParsing();
        $labels = JiraWebhookData::getReferencedLabels($commentBody);

        SlackMessageSender::getInstance()->toChannel($labels, JiraWebhook::convert('JiraDefaultToSlack', $data));
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
    $log->error($e->getMessage());
    $log->error($e->getLine());
    $log->error($e->getFile());
    $log->error($e->getCode());
}

$log->debug("Script finished in ".(microtime(true) - $start)." sec.");
