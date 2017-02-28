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

class JiraBlockerToSlackBotConverter implements JiraWebhookDataConverter
{
    /**
     * Converts $data into message (string)
     *
     * @param  JiraWebhookData $data parsed data from JIRA
     * @return string
     */
    public function convert(JiraWebhookData $data)
    {
        $issue    = $data->getIssue();
        $assignee = $issue->getAssignee();
        $comment  = $issue->getIssueComments()->getLastComment();
        $author   = $comment->getAuthor();

        /**
         * If issue dont have comments
         */
        if (!$comment) {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ @%s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assignee->getName()
                ]
            );
        /**
         * If issue not assigned to any user
         */    
        } elseif (!$issue->getAssignee()) {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ Unassigned\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $author->getName(),
                    $comment->getBody()
                ]
            );
        /**
         * If issue dont have comments and not assigned to any user 
         */    
        } elseif (!$comment && !$issue->getAssignee()) {
            $message = vsprintf(
                "!!! %s (%s) %s: %s ➠ Unassigned",
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
                "!!! %s (%s) %s: %s ➠ @%s\n@%s ➠ %s",
                [
                    $issue->getKey(),
                    $issue->getSelf(),
                    $issue->getStatus(),
                    $issue->getSummary(),
                    $assignee->getName(),
                    $author->getName(),
                    $comment->getBody()
                ]
            );
        }

        return $message;
    }
}