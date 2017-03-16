<?php
/**
 * File review
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
    protected $vickyUrl;

    protected $vickyTimeout;

    public function __construct($vickyUrl, $vickyTimeout)
    {
        $this->setVickyUrl($vickyUrl);
        $this->setvickyTimeout($vickyTimeout);
    }

    public function setVickyUrl($vickyUrl)
    {
        $this->vickyUrl = $vickyUrl;
    }

    public function setvickyTimeout($vickyTimeout)
    {
        $this->vickyTimeout = $vickyTimeout;
    }

    public function getVickyUrl()
    {
        return $this->vickyUrl;
    }

    public function getVickyTimeout()
    {
        return $this->vickyTimeout;
    }

    public function send($data)
    {
        if (!($curl = curl_init())) {
            throw new VickyClientException('Cannot init curl session!');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->vickyUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => $this->vickyTimeout
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new VickyClientException("cUrl error: {$error}");
        }

        if (trim($response) != "Ok") {
            throw new VickyClientException("Vicky error response: {$response}");
        }

        return true;
    }
}