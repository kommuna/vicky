<?php
namespace Slack_project\bot\models;

use PhpSlackBot\Webhook\BaseWebhook;

class ToUserHook extends BaseWebhook
{
    /**
     * This function get user id in slack by slack username
     *
     * @param $userName
     * @return string
     */
    public function getUserIdFromUserName($userName)
    {
        $context = $this->getCurrentContext();

        $userId = 'unknown';
        foreach ($context['users'] as $user) {
            if ($user['name'] == $userName) {
                $userId = $user['id'];
                break;
            }
        }

        /*
         * Need to add error throwing in cases 
         * not finding the user name
         */

        return $userId;
    }

    /**
     *
     */
    public function configure()
    {
        $this->setName('touser');
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
        $userId = $this->getUserIdFromUserName($payload['user']);
        $payload['channel'] = $this->getImIdFromUserId($userId);
        $this->getClient()->send(json_encode($payload));
    }
}