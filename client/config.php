<?php
return [
    'error_log' => dirname(__DIR__).'/logs/php_errors.log',
    'curlOpt'   => [
        'url'   => 'http://localhost:8080',
        'auth'  => 'secret'
    ]
];