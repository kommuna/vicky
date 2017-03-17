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
namespace Vicky\src\modules;

use Vicky\src\exceptions\VickyClientException;

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
     * VickyClient constructor.
     * 
     * @param     $vickyUrl     vicky host URL
     * @param int $vickyTimeout timeout of curl request to vicky
     */
    public function __construct($vickyUrl, $vickyTimeout)
    {
        $this->setVickyUrl($vickyUrl);
        $this->setvickyTimeout($vickyTimeout);
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
    public function setvickyTimeout($vickyTimeout)
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
     * Send HTTP request by curl method
     * 
     * @param $data array with data
     * 
     * @return bool
     * 
     * @throws VickyClientException
     */
    public function send($data)
    {
        if (!($curl = curl_init())) {
            throw new VickyClientException('Cannot init curl session!');
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
            throw new VickyClientException("cUrl error: {$error}");
        }

        return true;
    }
}