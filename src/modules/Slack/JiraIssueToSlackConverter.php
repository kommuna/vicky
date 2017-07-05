<?php
/**
 * Jira issue converter of default messages into into formatted string message
 *
 * @credits https://github.com/kommuna
 * @author  Chewbacca chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\modules\Slack;

class JiraIssueToSlackConverter
{
    /**
     * Converts $issue into a formatted string message
     *
     * @param $issue
     *
     * @return string
     */
    public function convert($issue)
    {
        $issueFields = $issue->fields;
        $url = parse_url($issue->self);
        $issueUrl = $url['scheme'].'://'.$url['host'].'/browse/'.$issue->key;
        $assigneeName = $issue->fields->assignee->name;

        if (!$assigneeName) {
            $message = vsprintf(
                "<%s|%s> %s: %s ➠ Unassigned",
                [
                    $issueUrl,
                    $issue->key,
                    $issueFields->status->name,
                    $issueFields->summary
                ]
            );
        } else {
            $message = vsprintf(
                "<%s|%s> %s: %s ➠ @%s",
                [
                    $issueUrl,
                    $issue->key,
                    $issueFields->status->name,
                    $issueFields->summary,
                    $assigneeName
                ]
            );
        }

        return $message;
    }
}