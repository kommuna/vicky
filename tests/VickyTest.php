<?php
/**
 * Created by PhpStorm.
 * Author: Elena Kolevska
 * Date: 3/22/17
 * Time: 00:39
 */

namespace Vicky\Tests;

use Vicky\src\modules\Vicky;
use PHPUnit_Framework_TestCase;

class VickyTest extends PHPUnit_Framework_TestCase
{
    /*
     * This method must stay on top, cause $config is a static property
     */
    public function testReturnsNullWhenNotInstantiated()
    {
        // If no config is set than the method should return null
        $projectName = Vicky::getChannelByProject('Foo');
        $this->assertNull($projectName);
    }

    public function testGetsCorrectChannelName()
    {
        $config = [
            'jiraToSlackMapping' => [
                'Foo' => '#bar',
                '*'   => '#jira'
            ]
        ];

        new Vicky($config);

        $projectName = Vicky::getChannelByProject('Foo');
        $this->assertEquals('#bar', $projectName);
    }
    public function testGetsDefaultChannelForUnmappedProjects()
    {
        $config = [
            'jiraToSlackMapping' => [
                'Foo' => '#bar',
                '*'   => '#jira'
            ]
        ];

        new Vicky($config);

        $projectName = Vicky::getChannelByProject('NonExistentProject');
        $this->assertEquals('#jira', $projectName);
    }
}