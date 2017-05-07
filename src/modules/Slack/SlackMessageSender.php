<?php
/**
 * Slack message sender class, that sends messages to slack
 *
 * @credits https://github.com/kommuna
 * @author  Miss Lv lv@devadmin.com
 * @author  Chewbacca chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\src\modules\Slack;

use Maknz\Slack\Client;
use Vicky\src\exceptions\SlackMessageSenderException;

class SlackMessageSender
{
    /**
     * Slack incoming webhook URL
     *
     * @var
     */
    protected $webhookUrl;

    /**
     * Bot username in slack
     *
     * @var
     */
    protected $botUsername;

    /**
     * Whether Slack should unfurl text-based URLs
     *
     * @var
     */
    protected $unfurl;

    /**
     * @var
     */
    protected static $messageSenderClient;

    /**
     * SlackMessageSender constructor.
     *
     * @param $webhookUrl
     * @param $botUsername
     * @param $unfurl
     */
    public function __construct($webhookUrl, $botUsername, $unfurl)
    {
        $this->setWebhookUrl($webhookUrl);
        $this->setBotUsername($botUsername);
        $this->setUnfurl($unfurl);
    }

    /**
     * @param $webhookUrl
     */
    public function setWebhookUrl($webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * @param $botUsername
     */
    public function setBotUsername($botUsername)
    {
        $this->botUsername = $botUsername;
    }

    /**
     * @param $unfurl
     */
    public function setUnfurl($unfurl)
    {
        $this->unfurl = $unfurl;
    }

    /**
     * @return mixed
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * @return mixed
     */
    public function getBotUsername()
    {
        return $this->botUsername;
    }

    /**
     * @return mixed
     */
    public function getUnfurl()
    {
        return $this->unfurl;
    }

    /**
     * Initialize slack message sender client or return it if already initialized
     *
     * @param string $webhookUrl
     * @param string $botUsername
     * @param bool   $unfurl
     *
     * @return SlackMessageSender
     * @throws SlackMessageSenderException
     */
    public static function getInstance($webhookUrl = '', $botUsername = '', $unfurl = false)
    {
        if (!self::$messageSenderClient) {
            if (!$webhookUrl || !$botUsername) {
                throw new SlackMessageSenderException('Slack webhook url and slack bot username must be defined!');
            }

            self::$messageSenderClient = new self($webhookUrl, $botUsername, $unfurl);
        }

        return self::$messageSenderClient;
    }

    /**
     * Instantiates a slack client and returns its Message object
     *
     * @return \Maknz\Slack\Message
     */
    protected function getMessage()
    {
        $webhookUrl = $this->webhookUrl;
        $settings   = [
            'username'     => $this->botUsername,
            'unfurl_links' => $this->unfurl
        ];

        return (new Client($webhookUrl, $settings))->createMessage();
    }

    /**
     * Sends message to slack user
     *
     * @param $username
     * @param $message
     */
    public function toUser($username, $message)
    {
        $username = (substr($username, 0, 1) === '@') ? $username : "@{$username}";

        $this->getMessage()->to($username)->send($message);
    }

    /**
     * Sends message to slack channel
     *
     * @param $channel
     * @param $message
     */
    public function toChannel($channel, $message)
    {
        $channel = (substr($channel, 0, 1) === '#') ? $channel : "#{$channel}";

        $this->getMessage()->to($channel)->send($message);
    }
}