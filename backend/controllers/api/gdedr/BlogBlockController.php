<?php

namespace backend\controllers\api\gdedr;

/**
 * This is the class for REST controller "BlogBlockController".
 */

use backend\modules\gdedr\models\blog\BlogBlock;

class BlogBlockController extends \yii\rest\ActiveController
{
	public $modelClass = BlogBlock::class;
	public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
}
