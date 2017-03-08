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
    private static $botClient;

    /**
     * Slack bot webserver host URL
     *
     * @var
     */
    protected static $slackBotUrl;

    /**
     * Slack bot secret key
     *
     * @var null
     */
    protected static $auth;

    /**
     * SlackWebhookSender constructor.
     * 
     * @param string $slackBotUrl slack bot webserver host url
     * @param null   $auth        slack bot webserver secret key
     */
    public function __construct($slackBotUrl, $auth = null)
    {
        self::$slackBotUrl = $slackBotUrl;
        self::$auth        = $auth;
    }

    /**
     * Set configs like slack bot host url and secret key
     * 
     * @param string $slackBotUrl host URL
     * @param null   $auth        secret key
     */
    public static function setConfigs($slackBotUrl, $auth = null)
    {
        self::$slackBotUrl = $slackBotUrl;
        self::$auth        = $auth;
    }
    
    /**
     * Initialize slack bot client or return if already initialized
     *
     * @return SlackBotSender
     */
    public static function getInstance()
    {
        if (!self::$botClient) {
            self::$botClient = new self(self::$slackBotUrl, self::$auth);
        }
        
        return self::$botClient;
    }

    /**
     * Send HTTP POST request to slack bot to send in $channel
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
            'auth'    => self::$auth,
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
            'auth'    => self::$auth,
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
            CURLOPT_URL            => self::$slackBotUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($slackRequest)
        ]);

        $response = curl_exec($curl);

        $error = curl_error($curl);
        if ($error) {
            throw new SlackBotSenderException($error);
        }

        if (trim($response) != "Ok") {
            throw new SlackBotSenderException($response);
        }

        curl_close($curl);

        return true;
    }
}
