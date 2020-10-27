<?php
$params = array_merge(
    require __DIR__ . '/../../../common/config/params.php',
    require __DIR__ . '/../../../common/config/params-local.php',
    require __DIR__ . '/../params.php',
    require __DIR__ . '/../params-local.php',
    \common\utility\SiteParamsHelper::getParamsForModule('svadbanaprirode')
);
Yii::setAlias('@module_web', '@frontend/modules/svadbanaprirode/web');

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__).'/..',
    'bootstrap' => ['log','svadbanaprirode'],
    'controllerNamespace' => 'app\modules\svadbanaprirode\controllers',
    'modules' => [
        'svadbanaprirode' => [
            'class' => 'app\modules\svadbanaprirode\Module',
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
                'username' => 'post@smilerooms.ru',
                'password' => 'H[ZWkBS++8.>1',
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
                    '@app/views' => '@app/modules/svadbanaprirode/views',
                ],
            ],
        ],
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=pmn_svadbanaprirode',
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
                ['pattern'=>'/catalog/<id:\d+>','route'=>'item/index', 'suffix'=>'/'],
                ['pattern'=>'/catalog/<slice>','route'=>'listing/slice', 'suffix'=>'/'],
                ['pattern'=>'/catalog/','route'=>'listing/index', 'suffix'=>'/'],
                ['pattern'=>'/ajax/filter-main','route'=>'listing/ajax-filter-slice', 'suffix'=>'/'],
                ['pattern'=>'/ajax/filter','route'=>'listing/ajax-filter', 'suffix'=>'/'],
                ['pattern'=>'/ajax/form','route'=>'form/send', 'suffix'=>'/'],
                ['pattern'=>'/ajax/form/','route'=>'form/send', 'suffix'=>'/'],
                ['pattern'=>'/ajax/sendroom/','route'=>'form/room', 'suffix'=>'/'],
                ['pattern'=>'/test/index','route'=>'test/index', 'suffix'=>'/'],
                ['pattern'=>'/test/test','route'=>'test/test', 'suffix'=>'/'],
                ['pattern'=>'/test/renewelastic','route'=>'test/renewelastic', 'suffix'=>'/'],
                ['pattern'=>'/test/imgload/','route'=>'test/imgload', 'suffix'=>'/'],
                ['pattern'=>'/sitemap/','route'=>'sitemap/index', 'suffix'=>'/'],
                ['pattern'=>'/privacy/','route'=>'static/privacy', 'suffix'=>'/'],
                ['pattern'=>'/robots/','route'=>'static/robots', 'suffix'=>'/'],
                ['pattern'=>'/api/map_all','route'=>'api/mapall', 'suffix'=>'/'],
                ['pattern'=>'/blog/<alias>/', 'route' => 'blog/post', 'suffix'=>'/'],
                ['pattern'=>'/blog/preview-post/<id>/', 'route' => 'blog/preview', 'suffix'=>'/'],
            ],
        ],
        
    ],
    'params' => $params,
];
