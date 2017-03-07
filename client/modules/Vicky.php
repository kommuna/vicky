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
namespace Vicky\client\modules;

class Vicky
{
    /**
     * Project config
     *
     * @var
     */
    private static $config;

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
     * @param $projectName issue project name
     *
     * @return null
     */
    public static function getChannelByProject($projectName)
    {
        if(empty(self::$config['jiraToSlackMapping']) || !is_array(self::$config['jiraToSlackMapping'])) {
            return null;
        }

        $jiraToSlackMapping = self::$config['jiraToSlackMapping'];

        $channel = null;

        if (array_key_exists($projectName, $jiraToSlackMapping)) {
            $channel = $jiraToSlackMapping[$projectName];
        } elseif (array_key_exists('*', $jiraToSlackMapping)) {
            $channel = $jiraToSlackMapping['*'];
        }

        return $channel;
    }
}