<?php
/**
 * JiraWebhookData converter of issue with type 'Urgent bug' into a formatted string message.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\src\modules\Jira;

use JiraWebhook\Models\JiraWebhookData;
use JiraWebhook\JiraWebhookDataConverter;
use Maknz\Slack\Message;

class JiraUrgentBugToSlackBotConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     *
     * @param JiraWebhookData $data - Parsed data from JIRA
     * 
     * @return string
     */
    public function convert(JiraWebhookData $data, Message $clientMessage)
    {
        $issue        = $data->getIssue();
        $assigneeName = $issue->getAssignee()->getName();
        $comment      = $issue->getIssueComments()->getLastComment();

        $attachment = [
            "color" => $issue->getColour(),
            "pretext" => $data->getIssueEventDescription(),
            "title" => vsprintf("(%s) %s", [$issue->getKey(), $issue->getSummary()]),
            "title_link" => $issue->getUrl(),

            'fields' => [
                [
                    'title' => 'Status',
                    'value' => $issue->getStatus(),
                    'short' => true // whether the field is short enough to sit side-by-side other fields
                ],
                [
                    'title' => 'Priority',
                    'value' => $issue->getPriority(),
                    'short' => true
                ]
            ],
        ];

        /**
         * Issue doesn't have comments and is not assigned to a user
         */
        if (!$comment && !$assigneeName) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ Unassigned",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary()
                ]
            );
        /**
         * Issue is not assigned to a user
         */
        } elseif (!$assigneeName) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $comment->getAuthor()->getName(),
                    $comment->getBody()
                ]
            );

            if ($data->isIssueCommented){
                $attachment['author_name'] = $comment->getAuthor()->getName() . ' commented on:';
                $attachment['author_icon'] = $comment->getAuthor()->getAvatarUrls()['48x48'];
                $attachment['text'] = '>>>' . $comment->getBody();
            }

            /**
         * Issue doesn't have any comments
         */
        } elseif (!$comment) {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ @%s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assigneeName
                ]
            );

            $attachment['fields'][] = [
                'title' => 'Assigned to:',
                'value' => $assigneeName,
                'short' => true
            ];

            /**
         * Default message
         */
        } else {
            $message = vsprintf(
                "⚡ %s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
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

            if ($data->isIssueCommented()){
                $attachment['author_name'] = $comment->getAuthor()->getName() . ' commented on:';
                $attachment['author_icon'] = $comment->getAuthor()->getAvatarUrls()['48x48'];
                $attachment['text'] = '>>>' . $comment->getBody();
            }

            $attachment['fields'][] = [
                'title' => 'Assigned to:',
                'value' => $assigneeName,
                'short' => true
            ];
        }

        $attachment['fallback'] = $message;
        $clientMessage->attach($attachment);

        return $clientMessage;
    }
}