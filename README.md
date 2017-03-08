# What is this?
This is vicky - friendly PHP JIRA to Slack robot.
Created for make easy and comfortable notification of any changes in JIRA to Slack.

This library can receive and parse data from JIRA webhook, convert it into messages and send it to Slack.
It is possible to setup Slack bot for more functionality, by creating new webhooks and commands. And it is possible to
write event listeners for work with received data from JIRA.

# Installation
For installing this library clone the code from GitHub.

Run command `composer install` in .../vicky folder.

Configure config files, use as example bot/config.example.php and client/config.example.php, and put bot config into
/etc/SlackBot folder and client config into /etc/vicky/ folder. Get bot token [here]
(https://my.slack.com/services/new/bot).

Configure Slack bot, for details see [this] (https://github.com/jclg/php-slack-bot).

Configure server that will listen JIRA webhook, as example use this nginx and php-fpm config:
```
server {
        listen          80;
        server_name     your.host.url;
        root            .../vicky/client;
        index           index.php;
        try_files       $uri $uri/ /index.php?$query_string;
        error_page  405 =200 $uri;
        location ~* \.php$ {
                include         fastcgi_params;
                fastcgi_pass    localhost:9000;
                fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }
```

Configure [JIRA webhook] (https://developer.atlassian.com/jiradev/jira-apis/webhooks).

After all things done run Slack bot by running command `php .../vicky/bot/index.php`.

#Usage
##Slack bot
For more details how configure custom webhooks and commands for bot see [this] (https://github.com/jclg/php-slack-bot).

##JiraWebhook
For more details about JIRA data, JIRA data converters and events see [this lib] (https://github.com/kommuna/jirawebhook)
and [this docs] (https://docs.atlassian.com/jira/REST/cloud/#api/2/issue-getIssue).

##Slack bot client
To use a bot client for sending messages to Slack use following example code:

```php
use Vicky\client\modules\Slack\SlackBotSender;

$botClient = new SlackBotSender($botHostURL, $auth);

$botClient->toChannel('#channelName', 'message');
$botClient->toUser('userNickname', 'message');
```

or use static methods:

```php
use Vicky\client\modules\Slack\SlackBotSender;

SlackBotSender::setConfigs($botHostURL, $auth);

SlackBotSender::getInstance()->toChannel('#channelName', 'message');
SlackBotSender::getInstance()->toUser('userNickname', 'message');
```

##Jira to Slack mapping
Vicky allow to configure mapping of JIRA projects to Slack channels. E.g. to send all tickets of project FOO to Slack 
channel #bar:

(/etc/vicky/config.php):

```
<?php
return [
   /* Mapping Jira projects to Slack channels */
   'jiraToSlackMapping' => [
       /* Send tickets by Jira project 'FOO' to Slack channel '#bar' */
       'FOO' => '#bar'
   ]
];
```

To configure default channel use follow config:

```
<?php
return [
   /* Mapping Jira projects to Slack channels */
   'jiraToSlackMapping' => [
       /* Send notifications by tickets by Jira project 'FOO' to Slack channel '#bar' */
       'FOO' => '#bar',
       /* Send notifications by other Jira projects to Slack channel '#channelName'*/
       '*' => '#channelName'
   ]
];
```

To disable notifications for project set value by project key to `false`:

```
<?php
return [
   /* Mapping Jira projects to Slack channels */
   'jiraToSlackMapping' => [
       /* Send notifications by tickets by Jira project 'FOO' to Slack channel '#bar' */
       'FOO' => '#bar',
       /* Don't send notifications by tickets by Jira project 'DUMP' to Slack */
       'DUMB' => false
       /* Send notifications by other Jira projects to Slack channel '#channelName'*/
       '*' => '#channelName'
   ]
];
```

To disable notifications for *other* projects set value by '*' key to `false`:

```
<?php
return [
   /* Mapping Jira projects to Slack channels */
   'jiraToSlackMapping' => [
       /* Send notifications by tickets by Jira project 'FOO' to Slack channel '#bar' */
       'FOO' => '#bar',
       /* Don't send notifications by tickets by Jira project 'DUMP' to Slack */
       'DUMB' => false
       /* Don't send notifications by other Jira projects to Slack */
       '*' => false
   ]
];
```

If project does not have mapping settings and default channel not configured, messages will not send.

Also, to send messages bot must be invited to the channel.