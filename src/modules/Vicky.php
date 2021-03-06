<?php
/**
 * JIRA to Slack mapping logic
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\modules;

class Vicky
{
    /**
     * Project config
     *
     * @var
     */
    protected static $config;

    /**
     * Vicky constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        self::setConfig($config);
    }

    /**
     * @param $config
     */
    public static function setConfig($config)
    {
        self::$config = $config;
    }

    /**
     * @return mixed
     */
    public static function getConfig()
    {
        return self::$config;
    }

    /**
     * Return channel name by issue project key from config file
     *
     * @param string $projectKey issue project key
     *
     * @return null
     */
    public static function getChannelByProject($projectKey)
    {
        /**
         * Check for the key in the config
         */
        if(empty(self::$config['jiraToSlackMapping']) || !is_array(self::$config['jiraToSlackMapping'])) {
            return null;
        }

        $jiraToSlackMapping = self::$config['jiraToSlackMapping'];

        $channel = null;

        /**
         * Get Slack channel name by Jira project key (Jira project key can be empty)
         */
        if (array_key_exists($projectKey, $jiraToSlackMapping)) {
            $channel = $jiraToSlackMapping[$projectKey];
        /**
         * Get Slack channel for Jira projects by default
         */
        } elseif (array_key_exists('*', $jiraToSlackMapping)) {
            $channel = $jiraToSlackMapping['*'];
        }

        return $channel;
    }
}