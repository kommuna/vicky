<?php
/**
 * This file is part of vicky.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    'error_log'        => '/path/to/log/file.log',
    'timeZone'         => 'Europe/Moscow',
    'loggerDebugLevel' => true,
    'curlOpt'   => [
        'url'   => 'http://url were you host slack bot:port',
        'auth'  => 'secret key for slack bot if needed'
    ]
];
