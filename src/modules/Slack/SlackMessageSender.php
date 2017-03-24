<?php
/**
 * Created by PhpStorm.
 * Author: Elena Kolevska
 * Date: 3/24/17
 * Time: 00:49
 */

namespace Vicky\src\modules\Slack;

use Maknz\Slack\Client;
use Vicky\src\exceptions\SlackMessageSenderException;

class SlackMessageSender
{
    private static $config;

    public static function getConfig()
    {
        if (!self::$config) {
            self::$config = require '/etc/vicky/config.php';
        }

        return self::$config;
    }

    public static function getWebhookUrl()
    {
        if (!isset(self::getConfig()['slackIncomingWebhookUrl'])){
            throw new SlackMessageSenderException("Please specify an URL for the incoming webhook");
        }
        return self::getConfig()['slackIncomingWebhookUrl'];
    }
    public static function getBotUsername()
    {
        return isset(self::getConfig()['slackBot']['botName']) ? self::getConfig()['slackBot']['botName'] : '';
    }

    /**
     * @return \Maknz\Slack\Message
     */
    public static function getMessage(
    )
    {
        $client = new Client(self::getWebhookUrl(), ['username'=>self::getBotUsername(), 'unfurl_links' => true, 'markdown_in_attachments' => ['text']]);
        return $client->createMessage();
    }


}