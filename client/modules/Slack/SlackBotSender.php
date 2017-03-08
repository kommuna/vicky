<?php
/**
 * Slack bot client class, that sends messages to slack bot
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Vicky\client\modules\Slack;

use Vicky\client\exceptions\SlackBotSenderException;

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
    protected $curlTimeout;

    /**
     * SlackWebhookSender constructor.
     * 
     * @param string $slackBotUrl slack bot webserver host url
     * @param null   $authKey        slack bot webserver secret key
     */
    public function __construct($slackBotUrl, $authKey = '', $curlTimeout = 0)
    {
        $this->setSlackBotUrl($slackBotUrl);
        $this->setAuthKey($authKey);
        $this->setCurlTimeout($curlTimeout);
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
     * @param $curlTimeout
     */
    public function setCurlTimeout($curlTimeout)
    {
        $this->curlTimeout = $curlTimeout;
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
    public function getCurlTimeout()
    {
        return $this->curlTimeout;
    }
    
    /**
     * Initialize slack bot client or return if already initialized
     *
     * @return SlackBotSender
     */
    public static function getInstance($slackBotUrl = '', $authKey = '', $curlTimeout = 0)
    {
        if (!self::$botClient) {
            if (!$slackBotUrl) {
                throw new SlackBotSenderException("Slack bot url must be defined!");
            }

            self::$botClient = new self($slackBotUrl, $authKey, $curlTimeout);
        }
        
        return self::$botClient;
    }

    /**
     * Send HTTP POST request to slack bot to send in $channel
     * if $channel empty then Request will not be sent
     *
     * @param string $channel  slack channel name (with '#' symbol)
     * @param string $message  message text
     * @param string $hookName slack bot hook which accepts requests
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
     * Send HTTP POST request to slack bot to send in private chat to user personally
     * if $userName empty then Request will not be sent
     *
     * @param string $userName slack username (without '@' symbol)
     * @param string $message  message text
     * @param string $hookName slack bot hook which accepts requests
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
            CURLOPT_CONNECTTIMEOUT => $this->curlTimeout
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
