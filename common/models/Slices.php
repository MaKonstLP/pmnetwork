<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "slices".
 *
 * @property int $id
 * @property string $alias
 * @property string $h1
 * @property string $title
 * @property string $description
 * @property string $params
 * @property string $img_alt
 */
class Slices extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'slices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['alias', 'h1', 'title', 'description', 'params', 'keywords'], 'required'],
            [['alias', 'h1', 'title', 'description', 'params', 'keywords', 'text_top', 'text_bottom', 'img_alt'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'alias' => 'Alias',
            'h1' => 'H1',
            'title' => 'Title',
            'description' => 'Description',
            'params' => 'Params',
            'keywords' => 'keywords',
            'text_top' => 'text_top',
            'text_bottom' => 'text_bottom',
        ];
    }

    public function getFilterParams()
    {
        return json_decode($this->params, true);
    }

    public function getFilterItem($filter_model) {
        if(!($params = json_decode($this->params, true))) return null;
        $filterAlias = array_key_first($params);
        $filterItemValue =  $params[array_key_first($params)];
        $filterItems = ArrayHelper::map($filter_model, 'alias', 'items')[$filterAlias] ?? null;

        if(!$filterItems) return null;

        $filterItem = current(array_filter($filterItems, function($filterItem) use ($filterItemValue) {
            return $filterItem->value == $filterItemValue;
        }));

        return $filterItem;
    }
}
