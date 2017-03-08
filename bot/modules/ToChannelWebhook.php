<?php
/**
 * Slack bot webhook class, that receives payload data from HTTP POST request and sed it to slack channel.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @param array $payload data array
     * @param       $context
     */
    public function execute($payload, $context)
    {
        $payload['channel'] = $this->getChannelIdFromChannelName($payload['channel']);
        $this->getClient()->send(json_encode($payload));
    }
}