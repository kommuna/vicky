<?php
/**
 * JiraWebhookData converter of issues that have comment with mentioned user
 * or assigned issues that has been commented into a Slack Client Message Object
 *
 * @credits https://github.com/kommuna
 * @author  Chewbacca chewbacca@devadmin.com
 * @author  Miss Lv lv@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\modules\Jira;

use JiraWebhook\JiraWebhookDataConverter;
use JiraWebhook\Models\JiraWebhookData;

class JiraCommentsToUserConverter implements JiraWebhookDataConverter
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
        $issue    = $data->getIssue();
        $comment  = $issue->getIssueComments()->getLastComment();
        $typeIcon = '';

        if ($issue->isPriorityBlocker()) {
            $typeIcon = ":no_entry_sign:";
        } elseif ($issue->isTypeOperations()) {
            $typeIcon = "⚙";
        } elseif ($issue->isTypeUrgentBug()) {
            $typeIcon = "⚡";
        }

        /**
         * Default message
         */
        $message = vsprintf(
            "%s <%s|%s> %s: %s\n@%s ➠ %s",
            [
                $typeIcon,
                $issue->getUrl(),
                $issue->getKey(),
                $issue->getStatus(),
                $issue->getSummary(),
                $comment->getAuthor()->getName(),
                $comment->getBody()
            ]
        );

        return $message;
    }
}