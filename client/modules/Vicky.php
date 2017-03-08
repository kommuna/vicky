<?php
/**
 * This file contains class with JIRA to slack mapping logic
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\client\modules;

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
        self::$config = $config;
    }

    /**
     * Return channel name by issue project name from config file
     *
     * @param string $projectName issue project name
     *
     * @return null
     */
    public static function getChannelByProject($projectName)
    {
        /**
         * Check for the required key in the config
         */
        if(empty(self::$config['jiraToSlackMapping']) || !is_array(self::$config['jiraToSlackMapping'])) {
            return null;
        }

        $jiraToSlackMapping = self::$config['jiraToSlackMapping'];

        $channel = null;

        /**
         * Check for a key corresponding to the name of the project
         */
        if (array_key_exists($projectName, $jiraToSlackMapping)) {
            $channel = $jiraToSlackMapping[$projectName];
        /**
         * Check for a key with a default value
         */
        } elseif (array_key_exists('*', $jiraToSlackMapping)) {
            $channel = $jiraToSlackMapping['*'];
        }

        return $channel;
    }
}