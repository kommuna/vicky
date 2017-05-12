<?php
/**
 * Class that send messages to vicky like JIRA
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace kommuna\vicky\modules;

use kommuna\vicky\exceptions\VickyClientException;

class VickyClient
{
    /**
     * Vicky host URL
     * 
     * @var
     */
    protected $vickyUrl;

    /**
     * Timeout of curl request
     * 
     * @var
     */
    protected $vickyTimeout;

    /**
     * 
     * @var
     */
    protected static $vickyClient;

    /**
     * VickyClient constructor.
     * 
     * @param     $vickyUrl     vicky host URL
     * @param int $vickyTimeout timeout of curl request to vicky
     * 
     * @throws VickyClientException
     */
    public function __construct($vickyUrl, $vickyTimeout = 0)
    {
        if (!$vickyUrl) {
            throw new VickyClientException("VickyClient: Slack bot url must be defined!");
        }
        $this->setVickyUrl($vickyUrl);
        $this->setVickyTimeout($vickyTimeout);
    }

    /**
     * @param $vickyUrl
     */
    public function setVickyUrl($vickyUrl)
    {
        $this->vickyUrl = $vickyUrl;
    }

    /**
     * @param $vickyTimeout
     */
    public function setVickyTimeout($vickyTimeout)
    {
        $this->vickyTimeout = $vickyTimeout;
    }

    /**
     * @return mixed
     */
    public function getVickyUrl()
    {
        return $this->vickyUrl;
    }

    /**
     * @return mixed
     */
    public function getVickyTimeout()
    {
        return $this->vickyTimeout;
    }

    /**
     * Initialize vicky client or return if already initialized
     *
     * @param     $vickyUrl
     * @param int $vickyTimeout
     *
     * @return VickyClient
     */
    public static function getInstance($vickyUrl = '', $vickyTimeout = 0)
    {
        if (!self::$vickyClient) {
            self::$vickyClient = new self($vickyUrl, $vickyTimeout);
        }

        return self::$vickyClient;
    }

    /**
     * Send HTTP request by curl method
     * 
     * @param $data      array with data
     * @param $eventName 
     * 
     * @return bool
     * 
     * @throws VickyClientException
     */
    public function send($data, $eventName = '')
    {
        if ($eventName) {
            $data['webhookEvent'] = $eventName;
        }
        
        if (!($curl = curl_init())) {
            throw new VickyClientException('VickyClient: Cannot init curl session!');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->vickyUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => $this->vickyTimeout
        ]);

        curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new VickyClientException("VickyClient: cUrl error: {$error}");
        }

        return true;
    }
}