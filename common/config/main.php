<?php

return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'language' => 'ru-RU',
    'controllerMap' => [
        'blog-post-blocks' => 'backend\controllers\api\BlogPostBlockController',
        'blog-blocks' => 'backend\controllers\api\BlogBlockController',
        'blog-block' => 'backend\controllers\BlogBlockController',
        'blog-post' => 'backend\controllers\BlogPostController',
        'blog-post-block' => 'backend\controllers\BlogPostBlockController',
        'blog-tag' => 'backend\controllers\BlogTagController',
        'media' => 'backend\controllers\MediaController',
        'site-object-seo' => 'backend\controllers\SiteObjectSeoController',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'autodetectCluster' => false,
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // configure more hosts if you have a cluster
            ],
        ],
        'view' => [
            'class' => 'yii\web\View',
            'renderers' => [
                'twig' => [
                    'class' => 'yii\twig\ViewRenderer',
                    'cachePath' => '@runtime/Twig/cache',
                    // Array of twig options:
                    'options' => [
                        'auto_reload' => true,
                        'debug' => true,
                        //'cache' => false,
                    ],
                    'globals' => [
                        'html' => ['class' => '\yii\helpers\Html'],
                        'Declension' => 'frontend\components\Declension',
                        'Img' => 'frontend\components\Img',
                        'ImgFactory' => 'frontend\components\ImgFactory',
                        'YamapFactory' => 'frontend\components\YamapFactory',
                    ],
                    'uses' => ['yii\bootstrap'],
                    'extensions' => ['Twig_Extension_StringLoader', new \Twig_Extension_StringLoader(), new \Twig_Extension_Debug,]
                ],
                // ...
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            //'viewPath' => '@common/mail',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.yandex.ru',
                'username' => 'svadbanaprirode@yandex.ru',
                'password' => 'vitywhbzxzodifdf',
                'port' => '465',
                'encryption' => 'ssl',
            ],
            'messageConfig' => [
                'charset' => 'UTF-8',
            ],
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class'          => 'yii\i18n\PhpMessageSource',
                    'basePath'       => '@backend/translations',
                    'sourceLanguage' => 'en-US',
                    'fileMap'        => [
                        'cruds' => 'cruds.php',
                        'models' => 'models.php',
                    ],
                ],
            ],
        ],
    ],
    'params' => \common\utility\SiteParamsHelper::getGlobalSiteParams()
];
