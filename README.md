# What is this?
This is vicky - friendly PHP JIRA to Slack robot.
Created for make easy and comfortable notification of any changes in JIRA to Slack.

This library can receive and parse data from JIRA webhook, convert it into messages and send it to Slack.
It is possible to setup Slack bot for more functionality, by creating new webhooks and commands. And it is possible to
write event listeners for work with received data from JIRA.

# Installation
To install this library clone the code from GitHub.

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
        root            .../vicky/src;
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
Vicky allows to configure mapping of JIRA projects to Slack channels. This configuration is done in the `/etc/vicky/config.php` file.

For example: to send all tickets of project FOO to Slack channel #bar you need to do: 

```
<?php
return [
   ...
   'jiraToSlackMapping' => [
       'FOO' => '#bar'
   ]
   ...
];
```


If you want to disable all notifications for a project just set its mapping key to false instead of a slack channel name:

```
<?php
return [
   'jiraToSlackMapping' => [
       'FOO' => '#foo',
       'BAR' => false,
   ]
];
```


You can also configure a backup channel where all the messages from projects that aren't mapped in Slack would go:

```
<?php
return [
   'jiraToSlackMapping' => [
       'FOO' => '#foo',
       'BAR' => false,
       '*' => '#'
   ]
];
```

To disable notifications for all unmapped projects set the '*' mapping key to `false`:

```
<?php
return [
   'jiraToSlackMapping' => [
       'FOO' => '#bar',
       'DUMP' => false
       '*' => false
   ]
];
```

Please note that if a project doesn't have any mapping settings and the default channel is not configured, messages will not be sent.

Important note: 
>The bot must be invited to the channel in order to be able to send messages to it.

## Events

Currently the following default events are set in the index.php file:

- Issue created:  
        - If the issue is a blocker a message is sent in the project's channel  
        - A message is sent to the user the issue is assigned to.
  
- Issue updated  
        - If the issue is a blocker a message is sent in the project's channel  
        - If an issue gets assigned - a message is sent to the assignee
        
- Comments  
        - Send message to a user's channel if someone mentions them in a new comment  
        - Send a message to user in slack if someone comments on an issue assigned to them
        
## Customizing
You can add your own logic and your own listeners to Vicky. In order to do that you would have to clone the customized vicky skeleton and add your own listeners in there.