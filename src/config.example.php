<?php
/**
 * Example config code for vicky.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    'errorLog'         => '/path/to/log/file.log',
    'errorReporting'   => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED,
    'timeZone'         => 'Your/TimeZone',
    /* Set true for turn on debug messages logging */
    'loggerDebugLevel' => false,
    /* Bot is not using in latest release */
    'slackBot' => [
        'url'     => 'http://url were you host slack bot:port',
        'auth'    => 'secret key for slack bot if needed',
        /* The time-out of requests to the bot is specified in seconds */
        'timeout' => 10,
        'botName' => 'botName'
    ],
    'slackMessageSender' => [
        /* Get it here https://api.slack.com/incoming-webhooks */
        'webhookUrl'  => 'incoming webhook url',
        'botUsername' => 'botName',
        /* Whether Slack should unfurl text-based URLs */
        'unfurl'      => true
    ],
    'vickyClient' => [
        'url'     => 'http://url were you host vicky/',
        /* The time-out of requests to the bot is specified in seconds */
        'timeout' => 10
    ],
    /* JIRA to slack mapping works by JIRA project keys */
    'jiraToSlackMapping' => [
        'ProjectKey' => 'ChannelName',
        /* Use '*' symbol for setting up default channel for notification */
        '*'          => 'DefaultChannelName'
    ],
    'blockersIssues' => [
        'folder'                        => '/path/to/issues/files/folder/',
        /* Time interval between notifications is specified in seconds */
        'notificationInterval'          => 6 * 3600,
        /* Time interval before first notification is specified in seconds */
        'firstTimeNotificationInterval' => 86400
    ]
];
