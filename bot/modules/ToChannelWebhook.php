<?php
/**
 * Slack bot webhook
 *
 * This file contains the webhook definition to send messages to channels
 */
namespace Vicky\bot\modules;

use PhpSlackBot\Webhook\BaseWebhook;

class ToChannelWebhook extends BaseWebhook
{
    /**
     * Set configs for webhook
     */
    public function configure()
    {
        $this->setName('tochannel');
    }

    /**
     * Send data from recieved HTTP POST request to slack
     *
     * @param $payload data array
     * @param $context
     */
    public function execute($payload, $context)
    {
        $payload['channel'] = $this->getChannelIdFromChannelName($payload['channel']);
        $this->getClient()->send(json_encode($payload));
    }
}