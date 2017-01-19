<?php
namespace Slack_project\bot\models;

use PhpSlackBot\Command\BaseCommand;

class MyCommand extends BaseCommand 
{
    protected function configure() 
    {
        $this->setName('mycommand');
    }

    protected function execute($message, $context) 
    {
        $context = $this->getCurrentContext();
        print_r($context);
        $this->send($this->getCurrentChannel(), null, $this->getCurrentContext());
    }
}