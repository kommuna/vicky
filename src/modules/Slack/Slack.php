<?php
/**
 * 
 */
namespace kommuna\vicky\modules\Slack;

use Mpociot\BotMan\BotManFactory;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use kommuna\vicky\modules\Slack\JiraIssueToSlackConverter;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require dirname(dirname(__DIR__)).'/vendor/autoload.php';
$config = require '/etc/vicky-test/config.php';

ini_set('log_errors', 'On');
ini_set('error_log', $config['errorLog']);
ini_set('error_reporting', $config['errorReporting']);
ini_set('max_execution_time', 0);
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

$issueService = new IssueService(new ArrayConfiguration(
    [
        'jiraHost'     => $config['jiraClient']['jiraHost'],
        'jiraUser'     => $config['jiraClient']['jiraUser'],
        'jiraPassword' => $config['jiraClient']['jiraPassword']
    ]
));
$jiraIssueToSlackConverter = new JiraIssueToSlackConverter();

$botConfig = [
    'slack_token' => $config['slackBotToken'],
];

$botman = BotManFactory::create($botConfig);

/**
 * I cant change name of $numbers in case of Bot functionality
 */
$botman->hears('(.*?)', function ($bot, $number)
{
    global $issueService;
    global $jiraIssueToSlackConverter;

    preg_match_all('/[A-Z]{1,10}-[0-9]{1,10}/', $number, $matches);
    $matches = $matches[0];

    foreach ($matches as $match) {
        $queryParam = [
            'fields' => [
                'summary',
                'comment',
            ],
        ];

        $issue = $issueService->get($match, $queryParam);

        $bot->reply($jiraIssueToSlackConverter->convert($issue));
    }
});

$botman->listen();

$log->debug("Script finished in ".(microtime(true) - $start)." sec.");