<?php
/**
 * Created by PhpStorm.
 * Author: Elena Kolevska
 * Date: 3/22/17
 * Time: 22:54
 */

namespace Vicky\modules\Helpers;

use Vicky\src\exceptions\SlackBotSenderException;

class CurlWrapper
{
    public function post($url, $fields, $timeout)
    {
        if (!($curl = curl_init())) {
            throw new SlackBotSenderException('Cannot init curl session!');
        }

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($fields),
            CURLOPT_TIMEOUT        => $timeout
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            throw new SlackBotSenderException("cUrl error: {$error}");
        }

        return trim($response);
    }

}