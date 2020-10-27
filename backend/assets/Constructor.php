<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * React constructor application asset bundle.
 */
class Constructor extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/constructor.js',
    ];
    public $depends = [
        'backend\assets\AppAsset',
    ];

    public function init() {
        parent::init();
        $this->css = $this->getVersionedFiles($this->css);
        $this->js = $this->getVersionedFiles($this->js);
    }

    public function getVersionedFiles($files)
    {
        $out = [];
        foreach ($files as $file) {
            $filePath = \Yii::getAlias('@webroot/' . $file);
            $out[] = $file . (is_file($filePath) ? '?v=' . filemtime($filePath) : '');
        }
        return $out;
    }
}
