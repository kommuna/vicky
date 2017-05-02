<?php
/**
 * Created by PhpStorm.
 * Author: Elena Kolevska
 * Date: 3/22/17
 * Time: 17:37
 */

namespace kommuna\vicky\Tests;


use JiraWebhook\Models\JiraWebhookData;
use kommuna\vicky\modules\Jira\JiraDefaultToSlackBotConverter;
use kommuna\vicky\Tests\Factories\JiraWebhookPayloadFactory;


class JiraDefaultToSlackBotConverterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->webhookPayload = JiraWebhookPayloadFactory::create();
    }
    public function testFormatMessageIfTicketIsUnassignedAndHasNoComments()
    {
        $this->webhookPayload['issue']['fields']['comment'] = [
            'comments' => [],
            "maxResults" => 0,
            "total" => 0,
            "startAt" => 0
        ];
        $this->webhookPayload['issue']['fields']['assignee'] = [];

        $webhookData = JiraWebhookData::parse($this->webhookPayload);
        $issue = $webhookData->getIssue();

        $response = (new JiraDefaultToSlackBotConverter())->convert($webhookData);

        $expected_message = vsprintf(
            "%s (%s) %s: %s ➠ Unassigned",
            [
                $issue->getKey(),
                $issue->getSelf(),
                $issue->getStatus(),
                $issue->getSummary()
            ]
        );

        $this->assertEquals($expected_message, $response);
    }

    public function testFormatMessageIfTicketIsUnssigned()
    {
        $this->webhookPayload['issue']['fields']['assignee'] = [];

        $webhookData = JiraWebhookData::parse($this->webhookPayload);

        $response = (new JiraDefaultToSlackBotConverter())->convert($webhookData);
        $issue = $webhookData->getIssue();
        $comment = $issue->getIssueComments()->getLastComment();

        $expected_message = vsprintf(
            "%s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
            [
                $issue->getKey(),
                $issue->getSelf(),
                $issue->getStatus(),
                $issue->getSummary(),
                $comment->getAuthor()->getName(),
                $comment->getBody()
            ]
        );

        $this->assertEquals($expected_message, $response);
    }

    public function testFormatMessageIfTicketHasNoComments()
    {
        $this->webhookPayload['issue']['fields']['comment'] = [
            'comments' => [],
            "maxResults" => 0,
            "total" => 0,
            "startAt" => 0
        ];

        $webhookData = JiraWebhookData::parse($this->webhookPayload);

        $response = (new JiraDefaultToSlackBotConverter())->convert($webhookData);
        $issue = $webhookData->getIssue();
        $assigneeName = $issue->getAssignee()->getName();

        $expected_message = vsprintf(
            "%s (%s) %s: %s ➠ @%s",
            [
                $issue->getKey(),
                $issue->getSelf(),
                $issue->getStatus(),
                $issue->getSummary(),
                $assigneeName
            ]
        );

        $this->assertEquals($expected_message, $response);
    }

    public function testFormatMessage()
    {
        $webhookData = JiraWebhookData::parse($this->webhookPayload);

        $response = (new JiraDefaultToSlackBotConverter())->convert($webhookData);
        $issue = $webhookData->getIssue();
        $assigneeName = $issue->getAssignee()->getName();
        $comment = $issue->getIssueComments()->getLastComment();

        $expected_message = vsprintf(
            "%s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
            [
                $issue->getKey(),
                $issue->getSelf(),
                $issue->getStatus(),
                $issue->getSummary(),
                $assigneeName,
                $comment->getAuthor()->getName(),
                $comment->getBody()
            ]
        );

        $this->assertEquals($expected_message, $response);
    }
}
