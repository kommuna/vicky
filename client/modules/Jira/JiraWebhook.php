<?php
namespace Vicky\client\modules\Jira;

use Vicky\client\exceptions\JiraWebhookException;
use Vicky\client\exceptions\SlackBotSenderException;
use League\Event\Emitter;

class JiraWebhook
{
    private $rawData;
    private $data;
    protected $callbacks = [];

    private static $converter = [];
    private static $emitter;

    /**
     * Get raw data from JIRA and parsing it
     *
     * @return JiraWebhookData - parsed data
     *
     * @throws JiraWebhookException
     * @throws SlackBotSenderException
     */
    public function extractData()
    {
        $f = fopen('php://input', 'r');
        $data = stream_get_contents($f);

        if ($data) {
            $this->rawData = json_decode($data, true);

            if ($this->rawData === null) {
                throw new JiraWebhookException('This data cannot be decoded from json!');
            }
        } else {
            throw new SlackBotSenderException('No data.');
        }
        
        $this->data = JiraWebhookData::parseWebhookData($this->rawData);

        return $this->data;
    }

    /**
     * Set converter for formatting messages
     *
     * @param $name - convertor name
     * @param $converter - object that extend JiraConverter
     */
    public static function setConverter($name, $converter)
    {
        self::$converter[$name] = $converter;
    }

    /**
     * Converts $data into message by converter
     *
     * @param $name - convertor name
     * @param $data - instance of the class JiraWebhookData
     *
     * @return mixed
     *
     * @throws JiraWebhookException
     */
    public static function convert($name, $data)
    {
        if (!empty(self::$converter[$name] && is_subclass_of(self::$converter[$name], 'JiraConverter'))) {
            return self::$converter[$name]->convert($data);
        } else {
            throw new JiraWebhookException("Converter {$name} is not registered or does not extend JiraConverter!");
        }
    }

    /**
     * Initialize emitter
     *
     * @return Emitter
     */
    public static function getEmitter()
    {
        if (self::$emitter) {
            return self::$emitter;
        }

        return self::$emitter = new Emitter();
    }

    /**
     * Register listener for event
     *
     * @param $name - event name
     * @param $listener - listener (it could be function or object (see docs))
     * @param int $priority - listener priority
     */
    public function registerEvent($name, $listener, $priority = 0)
    {
        self::$emitter->addListener($name, $listener, $priority);
    }

    /**
     * Call listener by event name
     *
     * @param $name - event name
     * @param null $data
     */
    public function on($name, $data = null)
    {
        self::$emitter->emit($name, $data);
    }

    public function run()
    {
        $data = $this->extractData();

        switch ($data->getWebhookEvent()) {
            case 'jira:issue_created':
                if ($data->isPriorityBlocker()) {
                    $this->on('priority.Blocker', $data);
                } elseif ($data->isTypeOprations()) {
                    $this->on('type.Operations', $data);
                } elseif ($data->isTypeUrgentBug()) {
                    $this->on('type.UrgentBug', $data);
                }
            
                if ($data->isAssignee()) {
                    $this->on('Assignee', $data);
                }
            case 'jira:issue_updated':
        }


        /*if ($data->isPriorityBlocker()) {
            $emitter->emit('type.Blocker', $data);


            $message = "!!! {$message}";
            //$this->toChannel('#general', $message);
            $this->toUser('chewbacca', $message);
        } elseif ($issueType === 'Operations') {
            if ($webhookEvent === 'jira:issue_created' || $status === 'Resolved') {
                $message = "⚙ {$message}";
                $this->toChannel('#general', $message);
            }
        } elseif ($issueType === 'Urgent bug') {
            if ($webhookEvent === 'jira:issue_created' || $status === 'Resolved' || $issueEvent === 'issue_commented') {
                $message = "⚡ {$message}";
                $this->toChannel('#general', $message);
            }
        }*/
    }
}
