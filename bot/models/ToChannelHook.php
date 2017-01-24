<?php
namespace Vicky\bot\models;

use PhpSlackBot\Webhook\BaseWebhook;

class ToChannelHook extends BaseWebhook
{
    /**
     *
     */
    public function configure()
    {
        $this->setName('tochannel');
    }

    /**
     * This function send data from recieved HTTP POST request
     * to slack
     *
     * @param $payload
     * @param $context
     */
    public function execute($payload, $context)
    {
        $payload['channel'] = $this->getChannelIdFromChannelName($payload['channel']);
        $this->getClient()->send(json_encode($payload));
    }
}