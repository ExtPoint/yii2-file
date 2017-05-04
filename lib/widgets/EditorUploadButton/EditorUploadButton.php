<?php

namespace extpoint\yii2\file\widgets\EditorUploadButton;

use extpoint\yii2\base\Widget;

class EditorUploadButton extends Widget
{
    public function run()
    {
        $this->view->registerJsFile('@static/assets/bundle-' . $this->getBundleName() . '.js', [
            'depends' => 'dosamigos\ckeditor\CKEditorAsset',
        ]);
        return '';
    }
}