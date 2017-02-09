<?php
namespace Vicky\client\modules;

use Vicky\client\modules\JiraWebhookData;
use Vicky\client\exceptions\SlackBotSenderException;

class JiraWebhook
{
    private $rawData;
    private $data;

    /**
     * Get data from Jira webhook
     * 
     * @return null|string
     */
    public function extractData()
    {
        $f = fopen('php://input', 'r');
        $data = stream_get_contents($f);

        if ($data) {
            $this->rawData = json_decode($data, true);
            // TODO если здесь ошибка json_decode то выкидывать эксепшен
        } else {
            throw new SlackBotSenderException('No data.');
        }
        
        $this->data = JiraWebhookData::parseWebhookData($this->rawData);

        return $this->data;
    }
}
