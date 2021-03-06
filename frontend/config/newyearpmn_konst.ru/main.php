<?php
$params = array_merge(
    require __DIR__ . '/../../../common/config/params.php',
    require __DIR__ . '/../../../common/config/params-local.php',
    require __DIR__ . '/../params.php',
    require __DIR__ . '/../params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__).'/..',
    'bootstrap' => ['log', 'gorko_ny'],
    'controllerNamespace' => 'app\modules\gorko_ny_konst\controllers',
    'modules' => [
        'gorko_ny' => [
            'class' => 'app\modules\gorko_ny_konst\Module',
        ],
    ],
    'components' => [
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@app/views' => '@app/modules/gorko_ny_konst/views',
                ],
            ],
        ],
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=pmn_gorko_ny',
            'username' => 'root',
            'password' => 'LP_db_',
            'charset' => 'utf8',
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
                ['pattern'=>'/ajax/form','route'=>'form/validate', 'suffix'=>'/'],
                ['pattern'=>'/api/map_all','route'=>'api/mapall', 'suffix'=>'/'],
            ],
        ],
        
    ],
    'params' => $params,
];
