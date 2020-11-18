<?php
$params = array_merge(
    require __DIR__ . '/../../../common/config/params.php',
    require __DIR__ . '/../../../common/config/params-local.php',
    require __DIR__ . '/../params.php',
    require __DIR__ . '/../params-local.php',
    \common\utility\SiteParamsHelper::getParamsForModule('gorko_ny')
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__).'/..',
    'bootstrap' => ['log', 'gorko_ny'],
    'controllerNamespace' => 'app\modules\gorko_ny\controllers',
    'modules' => [
        'gorko_ny' => [
            'class' => 'app\modules\gorko_ny\Module',
        ],
    ],
    'components' => [
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            //'viewPath' => '@common/mail',
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.yandex.ru',
                'username' => 'send@korporativ-ng.ru',
                'password' => 'sojkxccpvrcgcjbt',
                'port' => '465',
                'encryption' => 'ssl',
            ],
        ],
        'assetManager' => [
            'class' => 'yii\web\AssetManager',
            'forceCopy' => true,          
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@app/views' => '@app/modules/gorko_ny/views',
                ],
            ],
        ],
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=pmn_gorko_ny',
            'username' => 'pmnetwork',
            'password' => 'P2t8wdBQbczLNnVT',
            'charset' => 'utf8mb4',
        ],
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'autodetectCluster' => false,
            'nodes' => [
                ['http_address' => '127.0.0.1:9200'],
                // configure more hosts if you have a cluster
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                ['pattern'=>'robots','route'=>'site/robots', 'suffix'=>'.txt'],
                ['pattern'=>'/ploshhadki/<id:\d+>','route'=>'item/index', 'suffix'=>'/'],
                ['pattern'=>'/ploshhadki/<slice>','route'=>'listing/slice', 'suffix'=>'/'],
                ['pattern'=>'/ploshhadki/','route'=>'listing/index', 'suffix'=>'/'],
                ['pattern'=>'/ajax/filter-main','route'=>'listing/ajax-filter-slice', 'suffix'=>'/'],
                ['pattern'=>'/ajax/filter','route'=>'listing/ajax-filter', 'suffix'=>'/'],
                ['pattern'=>'/ajax/form','route'=>'form/send', 'suffix'=>'/'],
                ['pattern'=>'/ajax/form/','route'=>'form/send', 'suffix'=>'/'],
                ['pattern'=>'/api/map_all/','route'=>'api/mapall', 'suffix'=>'/'],
                ['pattern'=>'/privacy/','route'=>'static/privacy', 'suffix'=>'/'],
                ['pattern'=>'/ajax/sendroom/','route'=>'form/room', 'suffix'=>'/'],
                ['pattern'=>'/blog/<alias>/', 'route' => 'blog/post', 'suffix'=>'/'],
                ['pattern'=>'/blog/preview-post/<id>/', 'route' => 'blog/preview', 'suffix'=>'/'],
            ],
        ],
        
    ],
    'params' => $params,
];
