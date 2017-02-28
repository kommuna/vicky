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
namespace Vicky\bot\modules;

use PhpSlackBot\Webhook\BaseWebhook;

class ToUserWebhook extends BaseWebhook
{
    /**
     * Get user id in slack by slack username
     *
     * @param  $userName
     *
     * @return string
     */
    public function getUserIdFromUserName($userName)
    {
        $context = $this->getCurrentContext();

        $userId = null;
        foreach ($context['users'] as $user) {
            if ($user['name'] == $userName) {
                $userId = $user['id'];
                break;
            }
        }

        return $userId;
    }

    /**
     * Set configs for webhook
     */
    public function configure()
    {
        $this->setName('touser');
    }

    /**
     * Send data from recieved HTTP POST request to slack
     *
     * @param $payload data array
     * @param $context
     */
    public function execute($payload, $context)
    {
        $userId = $this->getUserIdFromUserName($payload['user']);
        $payload['channel'] = $this->getImIdFromUserId($userId);
        $this->getClient()->send(json_encode($payload));
    }
}