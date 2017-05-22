<?php

namespace extpoint\yii2\file\widgets\FileInput;

use extpoint\yii2\base\Widget;
use extpoint\yii2\file\models\File;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class FileInput extends Widget
{
    public $url = ['/file/upload/index'];
    public $multiple = false;
    public $options = [];
    public $field;
    public $model;
    public $attribute;
    public $asArrayString = false;

    /**
     * Renders the widget.
     */
    public function run()
    {
        return $this->renderReact(ArrayHelper::merge($this->options, [
            'reduxStateId' => $this->getId(),
            'multiple' => $this->multiple,
            'asArrayString' => $this->asArrayString,
            'name' => Html::getInputName($this->model, $this->attribute),
            'backendUrl' => Url::to($this->url),
            'initialFiles' => $this->getFiles(),
        ]));
    }

    protected function getFiles()
    {
        $attribute = Html::getAttributeName($this->attribute);
        $value = $this->model->$attribute ?: [];
        if (empty($value)) {
            return [];
        }

        if ($this->asArrayString || is_string($value)) {
            $value = StringHelper::explode($value);
        }
        if (!is_array($value)) {
            $value = [$value];
        }

        $value = $this->multiple ? $value : [$value[0]];
        return array_map(function ($fileModel) {
            /** @var File $fileModel */

            return [
                'uid' => $fileModel->uid,
                'path' => $fileModel->title,
                'type' => $fileModel->fileMimeType,
                'bytesUploaded' => $fileModel->fileSize,
                'bytesUploadEnd' => $fileModel->fileSize,
                'bytesTotal' => $fileModel->fileSize,
                'resultHttpMessage' => $fileModel->getExtendedAttributes(),
            ];
        }, File::findAll($value));
    }

}