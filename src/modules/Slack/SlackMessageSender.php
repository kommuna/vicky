<?php
/**
 * This class instantiates a slack client object
 * and can returns a slack client Message object (through method getMessage)
 *
 * @credits https://github.com/kommuna
 * @author Miss Lv lv@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vicky\src\modules\Slack;

use Maknz\Slack\Client;
use Vicky\src\exceptions\SlackMessageSenderException;

class SlackMessageSender
{
    private static $config;

    /**
     * Set $config parameter from config file and return it or just return it
     * if it's already been initialised
     *
     * @return array
     */
    public static function getConfig()
    {
        if (!self::$config) {
            self::$config = require '/etc/vicky-test/config.php';
        }

        return self::$config;
    }

    /**
     * Gets the slack incoming webhook url
     *
     * @return string
     * @throws SlackMessageSenderException
     */
    public static function getWebhookUrl()
    {
        if (!isset(self::getConfig()['slackIncomingWebhookUrl'])){
            throw new SlackMessageSenderException("Please specify an URL for the incoming webhook");
        }
        return self::getConfig()['slackIncomingWebhookUrl'];
    }

    /**
     * Gets the slackbot username
     *
     * @return string
     * @throws SlackMessageSenderException
     */
    public static function getBotUsername()
    {
        return isset(self::getConfig()['slackBot']['botName']) ? self::getConfig()['slackBot']['botName'] : '';
    }

    /**
     * Instantiates a slack client and returns its Message object
     *
     * @return \Maknz\Slack\Message
     */
    public static function getMessage()
    {
        $client = new Client(
                                self::getWebhookUrl(),
                                [
                                    'username'=>self::getBotUsername(),
                                    'unfurl_links' => true,
                                    'markdown_in_attachments' => ['text']
                                ]
                            );
        return $client->createMessage();
    }
}