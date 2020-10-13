<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "filter_items".
 *
 * @property int $id
 * @property int $filter_id
 * @property string $value
 * @property string $text
 * @property string $api_arr
 */
class FilterItems extends \yii\db\ActiveRecord
{
    public $hits;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'filter_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['filter_id', 'value', 'text', 'api_arr'], 'required'],
            [['filter_id'], 'integer'],
            [['value', 'text', 'api_arr'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'filter_id' => 'Filter ID',
            'value' => 'Value',
            'text' => 'Text',
            'api_arr' => 'Api Arr',
        ];
    }

    public function getFilter()
    {
        return $this->hasOne(Filter::className(), ['id' => 'filter_id']);
    }
}
