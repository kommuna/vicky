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
        $jiraToSlackMapping = self::$config['jiraToSlackMapping'];

        if (!empty($jiraToSlackMapping[$projectName])) {
            $channel = $jiraToSlackMapping[$projectName];
        } elseif (!empty($jiraToSlackMapping['EVERYTHINGELSE']) || $jiraToSlackMapping['EVERYTHINGELSE'] != 'false') {
            $channel = $jiraToSlackMapping['EVERYTHINGELSE'];
        } else {
            $channel = null;
        }

        return $channel;
    }
}