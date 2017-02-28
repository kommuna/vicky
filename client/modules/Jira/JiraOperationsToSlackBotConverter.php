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

class JiraOperationsToSlackBotConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     *
     * @param  JiraWebhookData $data parsed data from JIRA
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        $issue        = $data->getIssue();
        $assigneeName = $issue->getAssignee()->getName();
        $comment      = $issue->getIssueComments()->getLastComment();
        $authorName   = $comment->getAuthor()->getName();

        /**
         * If issue dont have comments
         */
        if (!$comment) {
            $message = vsprintf(
                "⚙ %s (%s) %s: %s ➠ @%s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assigneeName
                ]
            );
            /**
             * If issue not assigned to any user
             */
        } elseif (!$assigneeName) {
            $message = vsprintf(
                "⚙ %s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
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
             * If issue dont have comments and not assigned to any user
             */
        } elseif (!$comment && !$assigneeName) {
            $message = vsprintf(
                "⚙ %s (%s) %s: %s ➠ Unassigned",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary()
                ]
            );
            /**
             * Default message
             */
        } else {
            $message = vsprintf(
                "⚙ %s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
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