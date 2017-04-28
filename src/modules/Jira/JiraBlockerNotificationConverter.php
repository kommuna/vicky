<?php
/**
 * Data converter of issue with priority 'Blocker',
 * that not commented more than 24 hours into formatted string message.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\src\modules\Jira;

use JiraWebhook\JiraWebhookDataConverter;
use JiraWebhook\Models\JiraWebhookData;

class JiraBlockerNotificationConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into a formatted string message
     *
     * @param JiraWebhookData $data parsed data from JIRA
     *
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        $issue        = $data->getIssue();
        $assigneeName = $issue->getAssignee()->getName();
        $comment      = $issue->getIssueComments()->getLastComment();

        /**
         * Issue doesn't have comments and is not assigned to a user
         */
        if (!$comment && !$assigneeName) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ Unassigned\nThis ticket did not comment more than 24 hours",
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
                ":no_entry_sign: <%s|%s> %s: %s ➠ Unassigned\n@%s ➠ %s\nThis ticket did not comment more than 24 hours",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $comment->getAuthor()->getName(),
                    $comment->getBody()
                ]
            );
            
        /**
         * Issue doesn't have any comments, but is assigned
         */
        } elseif (!$comment) {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ @%s\nThis ticket did not comment more than 24 hours",
                [
                    $issue->getUrl(),
                    $issue->getKey(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assigneeName
                ]
            );
            
        /**
         * Default message
         */
        } else {
            $message = vsprintf(
                ":no_entry_sign: <%s|%s> %s: %s ➠ @%s\n@%s ➠ %s\nThis ticket did not comment more than 24 hours",
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
        }

        return $message;
    }
}