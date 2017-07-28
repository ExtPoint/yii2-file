<?php

namespace extpoint\yii2\file;

use extpoint\yii2\base\Module;
use extpoint\yii2\file\models\File;
use extpoint\yii2\file\uploaders\BaseUploader;
use yii\helpers\ArrayHelper;

class FileModule extends Module
{
    const PROCESSOR_NAME_ORIGINAL = 'original';
    const PROCESSOR_NAME_DEFAULT = 'default';

    /**
     * Format is jpg or png
     * @var string
     */
    public $thumbFormat = 'jpg';

    /**
     * From 0 to 100 percents
     * @var string
     */
    public $thumbQuality = 90;

    /**
     * Absolute path to root user files dir
     * @var string
     */
    public $filesRootPath;

    /**
     * Absolute url to root user files dir
     * @var string
     */
    public $filesRootUrl;

    /**
     * Absolute url to file icons directory (if exists)
     * @var string
     */
    public $iconsRootUrl;

    /**
     * Absolute path to file icons directory (if exists)
     * @var string
     */
    public $iconsRootPath;

    /**
     * The name of the x-sendfile header
     * @var string
     */
    public $xHeader = false;

    /**
     * Maximum file size limit
     * @var string
     */
    public $fileMaxSize = '200M';

    /**
     * Image settings
     * @var array
     */
    public $processors = [];

    public function init()
    {
        parent::init();

        // Default processors
        $this->processors = ArrayHelper::merge(
            [
                self::PROCESSOR_NAME_ORIGINAL => [
                    'class' => '\extpoint\yii2\file\processors\ImageResize',
                    'width' => 1920,
                    'height' => 1200
                ],
                self::PROCESSOR_NAME_DEFAULT => [
                    'class' => '\extpoint\yii2\file\processors\ImageResize',
                    'width' => 100,
                    'height' => 100
                ]
            ],
            $this->processors
        );

        // Normalize and set default dir
        if ($this->filesRootPath === null) {
            $this->filesRootPath = \Yii::getAlias('@webroot/assets/');
        } else {
            $this->filesRootPath = rtrim($this->filesRootPath, '/') . '/';
        }
        if ($this->filesRootUrl === null) {
            $this->filesRootUrl = \Yii::getAlias('@web', false) . '/assets/';
        } else {
            $this->filesRootUrl = rtrim($this->filesRootUrl, '/') . '/';
        }

        if ($this->iconsRootUrl) {
            $this->iconsRootUrl = \Yii::getAlias($this->iconsRootUrl);
        }
        if ($this->iconsRootPath) {
            $this->iconsRootPath = \Yii::getAlias($this->iconsRootPath);
        }
    }

    /**
     * @param array $uploaderConfig
     * @param array $fileConfig
     * @return \extpoint\yii2\file\models\File[]
     * @throws \yii\base\InvalidConfigException
     */
    public function upload($uploaderConfig = [], $fileConfig = [])
    {
        /** @var BaseUploader $uploader */
        $uploader = \Yii::createObject(ArrayHelper::merge([
            'class' => empty($_FILES) ? '\extpoint\yii2\file\uploaders\PutUploader' : '\extpoint\yii2\file\uploaders\PostUploader',
            'destinationDir' => $this->filesRootPath,
            'maxFileSize' => $this->fileMaxSize,
        ], $uploaderConfig));

        if (!$uploader->upload()) {
            return [
                'errors' => $uploader->getFirstErrors(),
            ];
        }

        $files = [];
        foreach ($uploader->files as $item) {
            $file = new File();
            $file->attributes = ArrayHelper::merge($fileConfig, [
                'uid' => $item['uid'],
                'title' => $item['title'],
                'folder' => str_replace([$this->filesRootPath, $item['name']], '', $item['path']),
                'fileName' => $item['name'],
                'fileMimeType' => $item['type'],
                'fileSize' => $item['bytesTotal'],
            ]);

            if (!$file->save()) {
                return [
                    'errors' => $uploader->getFirstErrors(),
                ];
            }

            $files[] = $file;
        }

        return $files;
    }

    public function coreMenu()
    {
        return [
            'file' => [
                'label' => 'Модуль загрузки и скачивания файла',
                'visible' => false,
                'items' => [
                    [
                        'url' => ["/$this->id/upload/index"],
                        'urlRule' => "$this->id/upload",
                    ],
                    [
                        'url' => ["/file/upload/editor"],
                        'urlRule' => "$this->id/upload/editor",
                    ],
                    [
                        'url' => ["/file/download/index"],
                        'urlRule' => "$this->id/<uid:[a-z0-9-]{36}>/<name>",
                    ],
                ]
            ]
        ];
    }
}