<?php
/**
 * An example index file for the main module of vicky project, that receives data from JIRA webhook
 * https://developer.atlassian.com/jiradev/jira-apis/webhooks), contains jiraWebhook listeners for events, that sends
 * messages to slack by slack client and contains converters declaration.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that is distributed with this source code.
 */
namespace Vicky;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Vicky\src\modules\Jira\JiraBlockerToSlackBotConverter;
use Vicky\src\modules\Jira\JiraDefaultToSlackBotConverter;
use Vicky\src\modules\Jira\JiraOperationsToSlackBotConverter;
use Vicky\src\modules\Jira\JiraUrgentBugToSlackBotConverter;
use Vicky\src\modules\Slack\SlackBotSender;
use Vicky\src\modules\Vicky;

use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;
use JiraWebhook\Exceptions\JiraWebhookException;

require __DIR__ . '/vendor/autoload.php';
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

new Vicky($config);
$jiraWebhook = new JiraWebhook();

/**
 * Set the converters Vicky will use to "translate" JIRA webhook
 * payload into formatted, human readable Slack messages
 */
JiraWebhook::setConverter('JiraDefaultToSlack', new JiraDefaultToSlackBotConverter());
JiraWebhook::setConverter('JiraBlockerToSlack', new JiraBlockerToSlackBotConverter());
JiraWebhook::setConverter('JiraOperationsToSlack', new JiraOperationsToSlackBotConverter());
JiraWebhook::setConverter('JiraUrgentBugToSlack', new JiraUrgentBugToSlackBotConverter());



/*
|--------------------------------------------------------------------------
| Register default listeners
|--------------------------------------------------------------------------
|
| These are just a few default listeners that would make sense for most teams.
| To add your own you should follow instructions in the README.md file
|
*/

/**
 * Send a message to the project's channel when a blocker issue is created or updated
 */
$jiraWebhook->addListener('*', function($e, JiraWebhookData $data)
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
 * Send message to user if a newly created issue
 * was assigned to them
 */
$jiraWebhook->addListener('jira:issue_created', function ($e, JiraWebhookData $data)
{
    $assignee = $data->getIssue()->getAssignee();

    if ($assignee->getName()) {
        SlackBotSender::getInstance()->toUser($assignee->getName(), JiraWebhook::convert('JiraDefaultToSlack', $data));
    }
});

/**
 * Send message to user if an issue gets assigned to them
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
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
 * Send message to user if someone comments on an issue
 * assigned to them
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
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
 * Send message to user if someone mentions them in a new comment
 */
$jiraWebhook->addListener('jira:issue_updated', function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();

    if ($data->isIssueCommented()) {
        $users = $issue->getIssueComments()->getLastComment()->getMentionedUsersNicknames();

        foreach ($users as $user) {
            SlackBotSender::getInstance()->toUser($user, JiraWebhook::convert('JiraDefaultToSlack', $data));
        }
    }
});


/*
|--------------------------------------------------------------------------
| Custom listeners
|--------------------------------------------------------------------------
| ADD YOUR CUSTOM LISTENERS HERE
|
*/


/*
 * ------------------------------------------------------------------------
 * ------------------------------------------------------------------------
 */



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
