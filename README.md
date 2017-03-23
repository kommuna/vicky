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
Copy `bot/config.example.php` to `/etc/SlackBot/config.php` and `client/config.example.php` to `/etc/vicky/config.php` and follow the instructions in them to set your values.

The slack bot token can be obtained [here](https://my.slack.com/services/new/bot).

>IMPORTANT: Please make sure that you have the webserver and the slackbot running on different ports. The slackbot port is configurable and you can set it in the `/etc/slackBot/config.php` file. 

#### 2. Jira
Register your [webhooks in JIRA](https://developer.atlassian.com/jiradev/jira-apis/webhooks).

#### 3. Slackbot daemon
The slackbot can be run by by cd-ing to the root folder and then running `php bot/index.php`. That's good enough for local development, but you'll need a more stable way to do this in production.
We suggest installing the [start-stop-daemon](http://manpages.ubuntu.com/manpages/trusty/man8/start-stop-daemon.8.html) and then follow these steps:

 - Copy the `init.d/slackbotservice` script to your init.d folder. 
 - chmod +x the script
 - Configure the `DAEMON` and `DAEMON_OPTS` variables
 - Run `service slackbotservice start` or `/etc/init.d/slackbotservice start`

To stop the service run `service slackbotservice stop` or `/etc/init.d/slackbotservice stop`

>Note: Another way to run the slackbot would be to have [supervisord](http://supervisord.org/) monitor it.  

# Usage
Use the provided `index.example.php` file to see how to setup the listeners for specific JIRA events in your project and how to handle those webhooks.
It already comes loaded with some basic events (listed below), but you can also add your own, following the example of the provided ones.

Let's look at the most basic parts:

```php
    require __DIR__ . '/vendor/autoload.php';
    $config = require '/etc/vicky/config.php';
    
    // Get an instance of the Slackbot so we can send messages to Slack
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

    /*
    |--------------------------------------------------------------------------
    | Register listeners
    |--------------------------------------------------------------------------
    |
    | We're providing a few default listeners that would make sense for most teams.
    | To add your own check out the "Adding custom listeners" section
    |
    */
    
    /**
     * Send message to a user's channel if an issue gets assigned to them
     */
    $jiraWebhook->addListener('jira:issue_updated', function ($e, \JiraWebhook\Models\JiraWebhookData $data)
    {
        $issue = $data->getIssue();
    
        if ($data->isIssueAssigned()) {
            SlackBotSender::getInstance()->toUser(
                $issue->getAssignee()->getName(),
                JiraWebhook::convert('JiraDefaultToSlack', $data)
            );
        }
    });

    //Get the incoming data from the webhook
    $f = fopen('php://input', 'r');
    $data = stream_get_contents($f);

    // Start the webhook processing
    $jiraWebhook->run($data);

```

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

### Adding custom listeners
If you need to handle specific JIRA situations in your own, specific ways, you can register your own listeners. This is how to do it:

```php
    use Vicky\src\modules\Vicky;
    use JiraWebhook\JiraWebhook;
    use JiraWebhook\Models\JiraWebhookData;
    use Vicky\bot\modules\Slack\SlackBotSender
    
    $jiraWebhook->addListener({jira-event-name}, function ($e, JiraWebhookData $data)
    {
        $issue = $data->getIssue();
    
        // Example for getting the username of the user to send the message to in Slack 
        // (in this particular case slack and jira usernames must be the same)
        $slackUsername = $issue->getAssignee()->getName();

        // Example for getting the appropriate Slack channel to send the message to
        // (reffer to the "Jira to Slack mapping" section for details )
        $channelName = Vicky::getChannelByProject($issue->getProjectName())
        
        // Use one of the provided formatters "JiraDefaultToSlack" to convert your 
        // data into a readable, slack-formatted message. You can also create your own.
        $formatted_slack_message = JiraWebhook::convert('JiraDefaultToSlack', $data);

        // Send message to a user
        SlackBotSender::getInstance()->toUser($username, $formatted_slack_message);
        
        // Or send message to a slack channel
        SlackBotSender::getInstance()->toChannel($channelName, $formatted_slack_message);
    });
```

>For more details about JIRA data, JIRA data converters and events check out the [JiraWebhook package](https://github.com/kommuna/jirawebhook)
and [this JIRA doc](https://docs.atlassian.com/jira/REST/cloud/#api/2/issue-getIssue).

If you prefer staying away from static methods you can also instantiate the SlackBotSender class normally:

```php
use Vicky\client\modules\Slack\SlackBotSender;

$config = require '/etc/vicky/config.php';

$botClient = new SlackBotSender($config['slackBot']['url'], $config['slackBot']['auth']);

$botClient->toChannel('#channelName', 'message');
$botClient->toUser('userNickname', 'message');
```

## Jira to Slack mapping
Vicky allows to configure mapping of JIRA projects to Slack channels. This configuration is done in the `/etc/vicky/config.php` file.

For example: to send all tickets of project FOO to Slack channel #foo you need to do: 

```
<?php
return [
   ...
   'jiraToSlackMapping' => [
       'FOO' => '#foo'
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


>Important note: The bot must be invited to the channel in order to be able to send messages to it.