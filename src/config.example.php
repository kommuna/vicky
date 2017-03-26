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
    'environment'      => 'local/staging/production',
    'error_log'        => '/path/to/log/file.log',
    'timeZone'         => 'Your/TimeZone',
    'loggerDebugLevel' => true/false,
    'slackBot' => [
        'url'     => 'http://url were you host slack bot:port',
        'auth'    => 'secret key for slack bot if needed',
        'botName'    => 'bot name',
        /* The time-out of requests to the bot is specified in seconds */
        'timeout' => 10
    ],
    'slackIncomingWebhookUrl' => 'incoming webhook url',  // Get it here: https://chewyteamgroup.slack.com/apps/A0F7XDUAZ-incoming-webhooks
    'jiraToSlackMapping' => [
        'ProjectName' => '#channelName',
        '*'           => '#defaultChannelName'
    ]
];