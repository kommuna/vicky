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
    private static $config;

    public function __construct($config)
    {
        self::$config = $config;
    }

    public static function getChannelByProject($projectName)
    {
        $jiraToSlackMapping = self::$config['jiraToSlackMapping'];

        return empty($jiraToSlackMapping[$projectName]) ? '#events' : $jiraToSlackMapping[$projectName];
    }
}