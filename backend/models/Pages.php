<?php

namespace backend\models;

use Yii;
use common\models\siteobject\BaseSiteObject;

/**
 * This is the model class for table "pages".
 *
 * @property int $id
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $keywords
 * @property string $h1
 * @property string $text_top
 * @property string $text_bottom
 * @property string $img_alt
 */
class Pages extends BaseSiteObject
{

    public $breadcrumbs;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'title', 'description', 'keywords', 'h1'], 'required'],
            [['name', 'title', 'description', 'keywords', 'h1', 'text_top', 'text_bottom', 'img_alt'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'title' => 'Title',
            'description' => 'Description',
            'keywords' => 'Keywords',
            'h1' => 'H1',
            'text_top' => 'Text Top',
            'text_bottom' => 'Text Bottom',
        ];
    }
}
