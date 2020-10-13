<?php

namespace common\models;

use common\models\siteobject\BaseSiteObject;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "restaurants".
 *
 * @property int $id
 * @property int $gorko_id
 * @property string $name
 * @property string $address
 * @property int $min_capacity
 * @property int $max_capacity
 * @property int $price
 * @property string $cover_url
 */
class Restaurants extends BaseSiteObject
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'restaurants';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gorko_id', 'name', 'address', 'city_id'], 'required'],
            [['gorko_id', 'min_capacity', 'max_capacity', 'price', 'district', 'parent_district', 'city_id', 'alcohol', 'firework', 'img_count', 'commission', 'active', 'in_elastic', 'parking'], 'integer'],
            [['name', 'address', 'cover_url', 'latitude', 'longitude', 'own_alcohol', 'cuisine', 'extra_services', 'payment', 'special', 'phone', 'location', 'type'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'gorko_id' => 'Gorko ID',
            'name' => 'Name',
            'address' => 'Address',
            'min_capacity' => 'Min Capacity',
            'max_capacity' => 'Max Capacity',
            'price' => 'Price',
            'cover_url' => 'Cover Url',
        ];
    }

    public function getRooms(){
        return $this->hasMany(Rooms::className(), ['restaurant_id' => 'id'])->orderBy(['capacity' => SORT_ASC]);
    }

    public function getImages(){
        return $this->hasMany(Images::className(), ['item_id' => 'id'])->where(['type' => 'restaurant']);
    }

    public function getSubdomen(){
        return $this->hasOne(Subdomen::className(), ['city_id' => 'city_id']);
    }
}
