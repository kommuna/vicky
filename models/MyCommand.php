<?php
namespace Slack_project\models;

use PhpSlackBot\Command\BaseCommand;

class MyCommand extends BaseCommand 
{
    protected function configure() 
    {
        $this->setName('mycommand');
    }

    protected function execute($message, $context) 
    {
        $this->send($this->getCurrentChannel(), null, 'Hello !');
    }
}