<?php
/**
 * JiraWebhookData converter of issues with priority 'Blocker' into a formatted string message.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\src\modules\Jira;

use JiraWebhook\JiraWebhookDataConverter;
use JiraWebhook\Models\JiraWebhookData;
use Maknz\Slack\Message;

class JiraBlockerToSlackBotConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into a slack client message object
     *
     * @param JiraWebhookData $data - Parsed data from JIRA
     * @param Message $clientMessage - Slack Message Object
     *
     * @return Message
     */
    public function convert(JiraWebhookData $data, Message $clientMessage)
    {
        $issue        = $data->getIssue();
        $assigneeName = $issue->getAssignee()->getName();
        $comment      = $issue->getIssueComments()->getLastComment();

        $attachment = [
            "color" => $issue->getColour(),
            "pretext" => $data->getIssueEventDescription(),
            "title" => vsprintf(":no_entry_sign: (%s) %s", [$issue->getKey(), $issue->getSummary()]),
            "title_link" => $issue->getUrl(),

            'fields' => [
                [
                    'title' => 'Status',
                    'value' => $issue->getStatus(),
                    'short' => true // whether the field is short enough to sit side-by-side other fields, defaults to false
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
                ":no_entry_sign: <%s|%s> %s: %s ➠ Unassigned",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary()
                ]
            );

        /**
         * Issue is not assigned to a user, but has comments
         */    
        } elseif (!$assigneeName) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
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
         * Issue doesn't have any comments, but is assigned
         */    
        } elseif (!$comment) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ @%s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
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
;
        /**
         * Default message
         */    
        } else {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
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