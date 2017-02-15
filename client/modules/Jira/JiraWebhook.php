<?php
namespace Vicky\client\modules\Jira;

use Vicky\client\exceptions\JiraWebhookException;
use Vicky\client\exceptions\SlackBotSenderException;
use League\Event\Emitter;

class JiraWebhook
{
    private static $converter = [];
    private static $emitter;

    protected $callbacks = [];
    
    private $rawData;
    private $data;

    /**
     * Set converter for formatting messages
     *
     * @param $name - convertor name
     * @param $converter - object that extend JiraWebhookDataConverter
     */
    public static function setConverter($name, JiraWebhookDataConverter $converter)
    {
        self::$converter[$name] = $converter;
    }

    /**
     * Converts $data into message (string) by converter
     *
     * @param $name - convertor name
     * @param $data - instance of the class JiraWebhookData
     *
     * @return mixed
     *
     * @throws JiraWebhookException
     */
    public static function convert($name, JiraWebhookData $data)
    {
        if (!empty(self::$converter[$name])) {
            return self::$converter[$name]->convert($data);
        } else {
            throw new JiraWebhookException("Converter {$name} is not registered!");
        }
    }

    /**
     * Initialize emitter
     *
     * @return Emitter
     */
    public static function getEmitter()
    {
        if (!self::$emitter) {
            self::$emitter = new Emitter();
        }

        return self::$emitter;
    }

    /**
     * Add listener for event
     *
     * @param $name - event name
     * @param $listener - listener (it could be function or object (see docs))
     * @param int $priority - listener priority
     */
    public function addListener($name, $listener, $priority = 0)
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
        // TODO add check for $name is callable
        self::$emitter->emit($name, $data);
    }

    /**
     * Main logic that call events
     *
     * @throws JiraWebhookException
     */
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

                if ($data->getAssignee()) {
                    $this->on('issue.Assigned', $data);
                }

                break;
            case 'jira:issue_updated':
                if ($data->isPriorityBlocker()) {
                    $this->on('priority.Blocker', $data);
                } elseif ($data->isTypeOprations() && $data->isStatusResolved()) {
                    $this->on('type.Operations', $data);
                } elseif (($data->isTypeUrgentBug() && $data->isStatusResolved()) || ($data->isTypeUrgentBug() && $data->isIssueCommented())) {
                    $this->on('type.UrgentBug', $data);
                }

                if ($data->isIssueAssigned()) {
                    $this->on('issue.Assigned', $data);
                }

                if ($data->isIssueCommented()) {
                    $this->on('issue.Commented', $data);

                    $refStart = $data->isCommentReference();

                    if (isset($refStart)) {
                        $lastComment = $data->getLastComment();
                        $refStart += 2;
                        $refEnd = stripos($lastComment, ']');
                        $reference = substr($lastComment, $refStart, $refEnd - $refStart);
                        $data->setCommentReference($reference);

                        $this->on('comment.Reference', $data);
                    }
                }

                break;
            case 'jira:issue_deleted':
                break;
        }
    }
    
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

        if (!$data) {
            throw new JiraWebhookException('There is not data in the Jira webhook');
        }

        $this->rawData = json_decode($data, true);
        $jsonError = json_last_error();

        if ($jsonError !== JSON_ERROR_NONE) {
            throw new JiraWebhookException("This data cannot be decoded from json (decode error: $jsonError)!");
        }

        $this->data = JiraWebhookData::parseWebhookData($this->rawData);

        return $this->data;
    }
}
