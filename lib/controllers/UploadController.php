<?php

namespace extpoint\yii2\file\controllers;

use extpoint\yii2\base\Controller;
use extpoint\yii2\file\FileModule;
use extpoint\yii2\file\models\ImageMeta;
use yii\helpers\Json;

class UploadController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $result = FileModule::getInstance()->upload();
        if (isset($result['errors'])) {
            return Json::encode([
                'error' => implode(', ', $result['errors']),
            ]);
        }

        // Send responses data
        return Json::encode(array_map(
            function ($file) {
                /** @var \extpoint\yii2\file\models\File $file */
                return $file->getExtendedAttributes();
            },
            $result
        ));
    }

    public function actionEditor($CKEditorFuncNum)
    {
        $result = FileModule::getInstance()->upload();
        if (!isset($result['errors'])) {
            $url = ImageMeta::findByProcessor($result[0]->id, FileModule::PROCESSOR_NAME_ORIGINAL)->url;
            return '<script>window.parent.CKEDITOR.tools.callFunction(' . Json::encode($CKEditorFuncNum) . ', ' . Json::encode($url) . ', "");</script>';
        }
    }
}