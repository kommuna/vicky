<?php
namespace Vicky\client\models;


class JiraWebhook
{
    private $data;

    /**
     * Get data from Jira webhook
     * 
     * @return null|string
     */
    public function process()
    {
        $f = fopen('php://input', 'r');
        $this->data = stream_get_contents($f);

        if ($this->data) {
            $this->data = json_decode($this->data);
        } else {
            $this->data = null;
        }

        return $this->data;
    }
}
