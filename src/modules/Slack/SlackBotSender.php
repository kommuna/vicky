<?php
/**
 * Slack bot client class, that sends messages to slack bot
 *
 * @credits https://github.com/kommuna
 * @author  Chewbacca chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\modules\Slack;

use kommuna\vicky\exceptions\SlackBotSenderException;

class SlackBotSender
{
    /**
     * @var
     */
    protected static $botClient;

    /**
     * Slack bot webserver host URL
     *
     * @var
     */
    protected $slackBotUrl;

    /**
     * Slack bot secret key
     *
     * @var null
     */
    protected $authKey;

    /**
     * Timeout of curl request
     *
     * @var
     */
    protected $slackBotTimeout;

    /**
     * SlackWebhookSender constructor.
     * 
     * @param string $slackBotUrl  slackbot webserver host url
     * @param string $authKey      slackbot webserver secret key
     */
    public function __construct($slackBotUrl, $authKey = '', $slackBotTimeout = 0)
    {
        $this->setSlackBotUrl($slackBotUrl);
        $this->setAuthKey($authKey);
        $this->setSlackBotTimeout($slackBotTimeout);
    }

    /**
     * @param $slackBotUrl
     */
    public function setSlackBotUrl($slackBotUrl)
    {
        $this->slackBotUrl = $slackBotUrl;
    }

    /**
     * @param $authKey
     */
    public function setAuthKey($authKey)
    {
        $this->authKey = $authKey;
    }

    /**
     * @param $slackBotTimeout
     */
    public function setSlackBotTimeout($slackBotTimeout)
    {
        $this->slackBotTimeout = $slackBotTimeout;
    }

    /**
     * @return mixed
     */
    public function getSlackBotUrl()
    {
        return $this->slackBotUrl;
    }

    /**
     * @return null
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @return mixed
     */
    public function getSlackBotTimeout()
    {
        return $this->slackBotTimeout;
    }

    /**
     * Initialize slack bot client or return it if already initialized
     *
     * @param string $slackBotUrl
     * @param string $authKey
     * @param int $slackBotTimeout
     * @return SlackBotSender
     * @throws SlackBotSenderException
     */
    public static function getInstance($slackBotUrl = '', $authKey = '', $slackBotTimeout = 0)
    {
        if (!self::$botClient) {
            if (!$slackBotUrl) {
                throw new SlackBotSenderException("Slack bot url must be defined!");
            }

            self::$botClient = new self($slackBotUrl, $authKey, $slackBotTimeout);
        }
        
        return self::$botClient;
    }

    /**
     * Send HTTP POST request to slack bot to send in $channel
     * if $channel is empty then Request will not be sent
     *
     * @param string $channel  slack channel name (including or not the '#' symbol)
     * @param string $message  message text
     * @param string $webhookName slack bot hook which accepts requests
     *
     * @return bool
     */
    public function toChannel($channel, $message, $webhookName = 'tochannel')
    {
        if (!$channel) {
            return false;
        }

        $channel = (substr($channel, 0, 1) === '#') ? $channel : "#{$channel}";

        $slackRequest = [
            'auth'    => $this->authKey,
            'name'    => $webhookName,
            'payload' => json_encode([
                "type"    => "message",
                "text"    => $message,
                "channel" => $channel
            ])
        ];

        return $this->sendRequest($slackRequest);
    }

    /**
     * Send HTTP POST request to slack bot to send in private chat to user directly
     * if $userName is empty then Request will not be sent
     *
     * @param string $userName slack username (without '@' symbol)
     * @param string $message  message text
     * @param string $webhookName slack bot hook which accepts requests
     * 
     * @return bool
     */
    public function toUser($userName, $message, $webhookName = 'touser')
    {
        if (!$userName) {
            return false;
        }

        $slackRequest = [
            'auth'    => $this->authKey,
            'name'    => $webhookName,
            'payload' => json_encode([
                "type"    => "message",
                "text"    => $message,
                "user"    => $userName
            ])
        ];

        return $this->sendRequest($slackRequest);
    }

    /**
     * Send HTTP request by curl method
     * 
     * @param $slackRequest array with request data
     * 
     * @return bool
     * 
     * @throws SlackBotSenderException
     */
    protected function sendRequest($slackRequest)
    {
        if (!($curl = curl_init())) {
            throw new SlackBotSenderException('Cannot init curl session!');
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->slackBotUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($slackRequest),
            CURLOPT_TIMEOUT        => $this->slackBotTimeout
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new SlackBotSenderException("cUrl error: {$error}");
        }

        if (trim($response) != "Ok") {
            throw new SlackBotSenderException("Bot error response: {$response}");
        }

        return true;
    }
}
