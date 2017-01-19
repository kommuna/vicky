<?php
namespace Slack_project\bot\models;

use PhpSlackBot\Webhook\BaseWebhook;

class ToUserHook extends BaseWebhook
{
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
        return $userId;
    }
    
    public function configure()
    {
        $this->setName('touser');
    }

    public function execute($payload, $context)
    {
        $userId = $this->getUserIdFromUserName($payload['user']);
        $payload['channel'] = $this->getImIdFromUserId($userId);
        $this->getClient()->send(json_encode($payload));
    }
}