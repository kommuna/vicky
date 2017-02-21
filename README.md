# What is this?
This is vicky - friendly PHP JIRA - slack robot.
Created for make easy and comfortable notification of any changes in JIRA
to slack.

# Installation
For installing this robot you should clone this repo in any folder you like
in your system.

Next step: you need to run command 'composer install' in project folder.

Next step: you need to configure config (clietnConfig.php and botConfig.php) as
example (bot/config.example.php and client/config.example.php) and put them
into /etc/vicky/ folder.

Next step: you need to configure JIRA webhook (see docs https://developer.atlassian.com/jiradev/jira-apis/webhooks)

After all things done you can run slack bot by running command 'php .../vicky/bot/index.php',
and he will receive data from JIRA

Also if you wnat to configure checking time of creating comment in Blocker issue, you should
configure your /etc/crontab file as example vicky/client/crontab.example (for more details
read https://www.centos.org/docs/5/html/5.2/Deployment_Guide/s2-autotasks-cron-configuring.html)