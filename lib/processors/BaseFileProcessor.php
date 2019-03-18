<?php

namespace extpoint\yii2\file\processors;

use extpoint\yii2\file\FileException;
use yii\base\BaseObject;

class BaseFileProcessor extends BaseObject
{
    public $filePath;

    public function run()
    {
        if (!file_exists($this->filePath)) {
            throw new FileException('Not found file `' . $this->filePath . '`');
        }

        $this->runInternal();
    }

    protected function runInternal()
    {
    }
}
