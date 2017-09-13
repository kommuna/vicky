# What is this?
This is Vicky - a friendly PHP JIRA to Slack robot.
It sends notification about JIRA ticket updates to Slack.

This library can receive and parse data from JIRA webhook, convert it into easily readable messages and send them off to the appropriate (configurable) user or channel in Slack.
It comes loaded with a few default listeners for the most common scenarios, but you can extend it with your own custom logic too.

# Installation and configuration
To install this library simply pull it in through composer
```        
"require": {
    "kommuna/vicky": "dev-master",
},
```   
and run `composer install`.

#### 1. Config files  
Copy `src/config.example.php` to `/etc/vicky/config.php` and follow the instructions in them to set your values.

How create a new Slack incoming webhook can be learned from [here](https://api.slack.com/incoming-webhooks).

[//]: # (Bot is not using in last release)
[//]: # (>IMPORTANT: Please make sure that you have the webserver and the slackbot running on different ports.)
[//]: # (>The slackbot port is configurable and you can set it in the `/etc/slackBot/config.php` file.)

#### 2. Jira
How to register a new webhooks in JIRA can be learned from [here](https://developer.atlassian.com/jiradev/jira-apis/webhooks).

#### 3. Slackbot daemon
> NOTE: Vicky currently doesn't implement any bot commands, so if you don't have any yourself either you can safely skip this step.

The slackbot can be run by cd-ing to the root folder and then running `php bot/index.php`.
That's good enough for local development, but you'll need a more stable way to do this in production.
We suggest installing the [start-stop-daemon](http://manpages.ubuntu.com/manpages/trusty/man8/start-stop-daemon.8.html)
and then follow these steps:

 - Copy the `init.d/slackbotservice` script to your init.d folder. 
 - chmod +x the script
 - Configure the `DAEMON` and `DAEMON_OPTS` variables
 - Run `service slackbotservice start` or `/etc/init.d/slackbotservice start`

To stop the service run `service slackbotservice stop` or `/etc/init.d/slackbotservice stop`

>Note: Another way to run slackbot in the background and restart it in case of failure would be to have [supervisord](http://supervisord.org/) monitor it.  

# Usage
Use the provided `index.example.php` file to see how to setup the listeners for specific JIRA events in your project and how to handle those webhooks.
It already comes loaded with some basic events (listed below), but you can also add your own, following the example of the provided ones.

Let's look at the most basic parts:

```php
require __DIR__ . '/vendor/autoload.php';
$config = require '/etc/vicky/config.php';

// Setting up configs for Slack Message sender
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

try {
    /**
     * Get incoming raw data from JIRA webhook
     */
    $f = fopen('php://input', 'r');
    $data = stream_get_contents($f);

    if (!$data) {
        throw new JiraWebhookException('There is no data in the Jira webhook');
    }

    /**
     * Start the raw webhook data processing
     */
    $jiraWebhook->run($data);
} catch (\Exception $e) {
    $log->error($e->getMessage());
}
```

Currently the following default events are set in the index.example.php file:
- Any event:
    - Creating and deleting issue file

- Custom event:
    - Custom listener for notification about issue file with way that you'd like,
    and updating information about last notification in that issue file

- Issue created or updated:
    - Send a message to the project's channel when issue is created or updated

- Issue created:  
    - Send message to user if a newly created issue was assigned to them,
    but if assigned user is not the creator
  
- Issue updated  
    - Send message to user's channel if an issue gets assigned to them,
    but if the user has not assigned himself
        
- Issue commented
    - Send message to user's channel if someone comments on an issue
    assigned to them, but if the author of the comment is not assigned user
    - Send message to user's channel if someone mentions them in a new comment,
    but if mentioned user not assigned to this issue
    - Send message to channel if someone referenced channel label in a new comment

### Adding custom listeners
If you need to handle specific JIRA situations in your own, specific ways, you can register your own listeners. This is how to do it:

```php
use kommuna\vicky\modules\Vicky;
use kommuna\vicky\modules\Slack\SlackMessageSender;

use JiraWebhook\JiraWebhook;
use JiraWebhook\Models\JiraWebhookData;


$jiraWebhook->addListener("jira:event_name", function ($e, JiraWebhookData $data)
{
    $issue = $data->getIssue();

    // Example for getting the username of the user to send the message to in Slack 
    // (in this particular case slack and jira usernames must be the same)
    $slackUsername = $issue->getAssignee()->getName();

    // Example for getting the appropriate Slack channel to send the message to
    // (reffer to the "Jira to Slack mapping" section for details )
    $channelName = Vicky::getChannelByProject($issue->getProjectKey());
    
    // Set up the slack message format:
    // Use one of the provided formatters "JiraDefaultToSlack" to convert your 
    // data into a readable, slack-formatted message. You can also create your own.
    $message = JiraWebhook::convert('JiraDefaultToSlack', $data);
    
    // Send off the message to Slack's user channel
    SlackMessageSender::getInstance()->toUser($slackUsername, $message);

    // Send off the message to Slack's user channel
    SlackMessageSender::getInstance()->toChannel($channelName, $message);
});
```

>For more details about JIRA data, JIRA data converters and events check out the [JiraWebhook package](https://github.com/kommuna/jirawebhook)
and [this JIRA docs](https://docs.atlassian.com/jira/REST/cloud/#api/2/issue-getIssue).

## Jira to Slack mapping
Vicky allows to configure mapping of JIRA projects to Slack channels. This configuration is done in the `/etc/vicky/config.php` file.

For example: to send all tickets of project FOO to Slack channel #foo you need to do: 

```
<?php
return [
   ...
   'jiraToSlackMapping' => [
       'FOOProjectKey' => 'foo'
   ]
   ...
];
```

If you want to disable all notifications for a project just set its mapping key to false instead of a slack channel name:

```
<?php
return [
   'jiraToSlackMapping' => [
       'FOOProjectKey' => 'foo',
       'BARProjectKey' => false,
   ]
];
```

You can also configure a backup channel where all the messages from projects that aren't mapped in Slack would go:

```
<?php
return [
   'jiraToSlackMapping' => [
       'FOOProjectKey' => 'foo',
       'BARProjectKey' => false,
       '*' => 'backupChannel'
   ]
];
```

To disable notifications for all unmapped projects set the '*' mapping key to `false`:

```
<?php
return [
   'jiraToSlackMapping' => [
       'FOOProjectKey' => 'foo',
       'BARProjectKey' => false
       '*' => false
   ]
];
```

Please note that if a project doesn't have any mapping settings and the default channel is not configured, messages will not be sent.

[//]: # (>Important note: The bot must be invited to the channel in order to be able to send messages to it.)