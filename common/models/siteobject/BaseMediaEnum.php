<?php

namespace common\models\siteobject;

use common\models\blog\BlogPost;
use common\models\blog\BlogPostBlock;
use common\models\blog\BlogTag;
use common\utility\BaseEnum;
use yii\helpers\Json;

abstract class BaseMediaEnum extends BaseEnum
{
    const PHOTO = 'photo';
    const IMAGE = 'image';
    const HTML = 'html';

    const LABEL_MAP = [
        self::PHOTO => 'Фото',
        self::IMAGE => 'Изображение',
    ];

    /**
     * @return string[]
     * реализация для каждого сайта своя
     */
    public static function getMediaTypes() {
        return [];
    }

    /**
     * @return string[]
     *получаем доступные для объекта алиасы целей прикрепления файлов ['image', 'logo', ...]
     */
    public static function getForSiteObject($model)
    {
        $definedTypes = static::getMediaTypes();
        if(isset($definedTypes[$model::className()])) {
            return $definedTypes[$model::className()];
        }
        switch ($model::className()) {
            case BlogPost::class:
                return [self::IMAGE];
                break;
                
            case BlogTag::class:
                return [self::IMAGE];
                break;
            //у блока поста склеиваем типинпута_slug. Это и будет алиас
            case BlogPostBlock::class:
                $mediatargets = [];
                foreach (Json::decode($model->blogBlock->inputs) as $inputName => $inputs) {
                    if (self::isValidValue($inputName)) {
                        $mediatargets = array_reduce($inputs, function ($acc, $inputinfo) use ($inputName) {
                            if (!empty($inputinfo['slug'])) {
                                $acc[] = $inputName . '_' . $inputinfo['slug'];
                            }
                            return $acc;
                        }, $mediatargets);
                    }
                }
                return $mediatargets;
                break;
            default:
                return [];
                break;
        }
    }

    public static function getLabel($type)
    {
        if (self::isValidValue($type)) {
            return static::LABEL_MAP[$type] ?? self::LABEL_MAP[$type] ?? '???';
        } else return 'Тип не определен';
    }
}
