<?php
/**
 * Example config code for slack bot.
 *
 * @credits https://github.com/kommuna
 * @author  chewbacca@devadmin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
return [
    'errorLog'         => '/path/to/log/file.log',
    'timeZone'         => 'Your/TimeZone',
    'loggerDebugLevel' => true/false,
    /* Get it here https://my.slack.com/services/new/bot */
    'botToken'         => 'your slack bot token',
    'botAuth'          => 'secret key for bot webserver if needed',
    /* Port that will be used for hosting bot webserver */
    'botPort'          => 8080
];

