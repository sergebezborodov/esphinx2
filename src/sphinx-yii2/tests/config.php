<?php

return array(
    'id' => 'test-app',
    'basePath' => ROOT,
    'components' => array(
        'db' => array(
            'dsn' => 'mysql:host=localhost;dbname=sphinx_test',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
        ),
        'fixture' => [
            'class' => 'app\common\components\DbFixtureManager',
            'basePath' => __DIR__.'/fixtures',
        ],

    ),
);
