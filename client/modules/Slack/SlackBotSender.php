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
     * @param      $slackBotUrl callable slack bot webserver host url
     * @param null $auth                 slack bot webserver secret key
     */
    public function __construct($slackBotUrl, $auth = null)
    {
        self::$slackBotUrl = $slackBotUrl;
        self::$auth        = $auth;
    }

    /**
     * Set configs like slack bot host url and secret key
     * 
     * @param      $slackBotUrl callable host URL
     * @param null $auth                 secret key
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
     * @param        $channel  callable slack channel name (with '#' symbol)
     * @param        $message  callable message text
     * @param string $hookName          slack bot hook which accepts requests
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

        $answer = $this->sendRequest($slackRequest);

        return $this->curlAnswerCheck($answer);
    }

    /**
     * Send HTTP POST request to slack bot to send in private chat to user personally
     *
     * @param        $userName callable slack username (without '@' symbol)
     * @param        $message  callable message text
     * @param string $hookName          slack bot hook which accepts requests
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

        $answer = $this->sendRequest($slackRequest);

        return $this->curlAnswerCheck($answer);
    }

    /**
     * Send HTTP request by curl method
     * 
     * @param $slackRequest array with request data
     * 
     * @return bool|mixed
     */
    protected function sendRequest($slackRequest)
    {
        if (!($curl = curl_init())) {
            return $answer = 'Cannot init curl session!';
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL            => self::$slackBotUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($slackRequest)
        ]);

        $answer = curl_exec($curl);

        $error = curl_error($curl);
        if ($error) {
            $answer = $error;
        }

        curl_close($curl);

        return $answer;
    }

    /**
     * Check result $answer of curl executing
     *
     * @param $answer callable response after curl execution
     *
     * @return bool
     *
     * @throws SlackBotSenderException
     */
    protected function curlAnswerCheck($answer)
    {
        if (strpos($answer, "Ok")) {
            throw new SlackBotSenderException($answer);
        }

        return true;
    }
}
