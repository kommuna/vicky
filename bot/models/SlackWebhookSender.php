<?php
namespace Slack_project\bot\models;


class SlackWebhookSender
{
    protected $slackBotUrl;
    protected $auth;

    public function __construct($slackBotUrl, $auth = null)
    {
        $this->slackBotUrl = $slackBotUrl;
        $this->auth = $auth;
    }

    public function toChannel($channel, $message, $hookName = 'tochannel')
    {
        $channel = (substr($channel, 0, 1) == '#') ? $channel : '#'.$channel;

        $postFields = [
            'auth' => $this->auth,
            'name' => $hookName,
            'payload' => '{"type": "message", "text": "'.$message.'", "channel": "'.$channel.'"}'
        ];

        $answer = $this->sendRequest($postFields);

        switch ($answer) {
            case 'ok':
                return true;
            case false:
                error_log('Cannot init curl session!');
                return false;
            default:
                error_log($answer);
                return false;
        }
    }

    public function toUser($userName, $message, $hookName = 'touser')
    {
        $postFields = [
            'auth' => $this->auth,
            'name' => $hookName,
            'payload' => '{"type": "message", "text": "'.$message.'", "user": "'.$userName.'"}'
        ];

        $answer = $this->sendRequest($postFields);

        switch ($answer) {
            case 'ok':
                return true;
            case false:
                error_log('Cannot init curl session!');
                return false;
            default:
                error_log($answer);
                return false;
        }
    }

    protected function sendRequest($slackRequest) 
    {
        if ($curl = curl_init()) {
            $options = [
                CURLOPT_URL => $this->slackBotUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($slackRequest)
            ];

            curl_setopt_array($curl, $options);

            $out = curl_exec($curl);
            curl_close($curl);

            return $out;
        }
    }
}