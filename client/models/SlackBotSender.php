<?php
namespace Vicky\client\models;

use Vicky\client\models\JiraWebhook;

class SlackBotSender
{
    protected $slackBotUrl;
    protected $auth;

    /**
     * SlackWebhookSender constructor.
     * 
     * @param $slackBotUrl - slack bot webserver host url
     * @param null $auth - slack bot webserver secret key
     */
    public function __construct($slackBotUrl, $auth = null)
    {
        $this->slackBotUrl = $slackBotUrl;
        $this->auth = $auth;
    }

    public function parseData()
    {
        $receiver = new JiraWebhook();
        $data = $receiver->process();

        if ($data->issue->fields->prioruty->name == 'Blocker') {
            $message = '!!! <'.$data->issue->key.'> ('.$data->issue->self.') <'.$data->issue->fields->status.'>: <'.$data->issue->fields->summary.'> ➠ <'.$data->issue->fields->assignee->name.'>';
            $this->toChannel('#general', $message);

            $message = '<'.$data->issue->fields->comment->author->name.'> ➠ <'.$data->issue->fields->comment->body.'>';
            $this->toChannel('#general', $message);
        } elseif ($data->issue->fields->type->name == 'Operations') {
            if ($data->webhookEvent == 'jira:issue_created' || $data->issue->fields->status == 'Resolved') {
                $message = '⚙ <'.$data->issue->key.'> ('.$data->issue->self.') <'.$data->issue->fields->status.'>: <'.$data->issue->fields->summary.'> ➠ <'.$data->issue->fields->assignee->name.'>';
                $this->toChannel('#general', $message);

                $message = '<'.$data->issue->fields->comment->author->name.'> ➠ <'.$data->issue->fields->comment->body.'>';
                $this->toChannel('#general', $message);
            }
        } elseif ($data->issue->fields->type->name == 'Urgent bug') {
            if ($data->webhookEvent == 'jira:issue_created' || $data->issue->fields->status == 'Resolved') {
                $message = '⚡ <'.$data->issue->key.'> ('.$data->issue->self.') <'.$data->issue->fields->status.'>: <'.$data->issue->fields->summary.'> ➠ <'.$data->issue->fields->assignee->name.'>';
                $this->toChannel('#general', $message);

                $message = '<'.$data->issue->fields->comment->author->name.'> ➠ <'.$data->issue->fields->comment->body.'>';
                $this->toChannel('#general', $message);
            }
        }
    }

    /**
     * Send HTTP POST request to slack bot
     * to send in channel
     *
     * @param $channel - slack channel name (with '#" symbol)
     * @param $message - message text
     * @param string $hookName - slack bot hook which accepts requests
     * @return bool
     */
    public function toChannel($channel, $message, $webhookName = 'tochannel')
    {
        $channel = (substr($channel, 0, 1) == '#') ? $channel : '#'.$channel;

        $slackRequest = [
            'auth'    => $this->auth,
            'name'    => $webhookName,
            'payload' => json_encode([
                "type"    => "message",
                "text"    => $message,
                "channel" => $channel
            ])
        ];

        $answer = $this->sendRequest($slackRequest);

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

    /**
     * Send HTTP POST request to slack bot
     * to send in pivate chat to user personally
     *
     * @param $userName - slack username (without '@' symbol)
     * @param $message - message text
     * @param string $hookName - slack bot hook which accepts requests
     * @return bool
     */
    public function toUser($userName, $message, $webhookName = 'touser')
    {
        $slackRequest = [
            'auth'    => $this->auth,
            'name'    => $webhookName,
            'payload' => json_encode([
                "type"    => "message",
                "text"    => $message,
                "user"    => $userName
            ])
        ];

        $answer = $this->sendRequest($slackRequest);

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

    /**
     * Send HTTP request by curl method
     * 
     * @param $slackRequest - text of HTTP request
     * @return bool|mixed
     */
    protected function sendRequest($slackRequest)
    {
        if (!($curl = curl_init())) {
            return false;
        }
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->slackBotUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($slackRequest)
        ]);

        $answer = curl_exec($curl);
        curl_close($curl);

        return $answer;
    }
}