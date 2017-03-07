<?php
/**
 * This file is part of vicky.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\client\modules\Jira;

use JiraWebhook\JiraWebhookDataConverter;
use JiraWebhook\Models\JiraWebhookData;

class JiraDefaultToSlackBotConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
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
        $authorName   = $comment ? $comment->getAuthor()->getName() : null;

        /**
         * Issue doesn't have comments and is not assigned to a user
         */
        if (!$comment && !$assigneeName) {
            $message = vsprintf(
                "%s (%s) %s: %s ➠ Unassigned",
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
                "%s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $authorName,
                    $comment->getBody()
                ]
            );
            /**
             * Issue doesn't have any comments
             */
        } elseif (!$comment) {
            $message = vsprintf(
                "%s (%s) %s: %s ➠ @%s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
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
                "%s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assigneeName,
                    $authorName,
                    $comment->getBody()
                ]
            );
        }

        return $message;
    }
}