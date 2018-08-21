<?php

namespace extpoint\yii2\file;

use extpoint\yii2\base\Module;
use extpoint\yii2\file\models\File;
use extpoint\yii2\file\models\ImageMeta;
use extpoint\yii2\file\uploaders\BaseUploader;
use yii\helpers\ArrayHelper;

class FileModule extends Module
{
    const PROCESSOR_NAME_ORIGINAL = 'original';
    const PROCESSOR_NAME_DEFAULT = 'default';

    const SOURCE_FILE = 'file';
    const SOURCE_AMAZONE_S3 = 'amazone_s3';

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

    public $prioritySource = 'file';

    /**
     * @var frostealth\yii2\aws\s3\Service
     */
    public $amazoneStorage;

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

        // Create aws s3 service
        if ($this->prioritySource === self::SOURCE_AMAZONE_S3) {
            $this->amazoneStorage = \Yii::createObject(array_merge(
                [
                    'class' => 'frostealth\yii2\aws\s3\Service',
                    'region' => 'eu-west-1',
                    'credentials' => [
                        'key' => '',
                        'secret' => '',
                    ],
                    'defaultBucket' => 'vindog2',
                    'defaultAcl' => 'public-read',
                ],
                $this->amazoneStorage ?: []
            ));
        }

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
     * @param null $source
     * @return array
     * @throws \extpoint\yii2\exceptions\ModelSaveException
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function upload($uploaderConfig = [], $fileConfig = [], $source = null)
    {
        $source = $source ?: $this->prioritySource;

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
                'folder' => ArrayHelper::getValue($fileConfig, 'folder')
                    ?: str_replace([$this->filesRootPath, $item['name']], '', $item['path']),
                'fileName' => $item['name'],
                'fileMimeType' => $item['type'],
                'fileSize' => $item['bytesTotal'],
            ]);

            if ($source === self::SOURCE_AMAZONE_S3) {
                $file->sourceType = FileModule::SOURCE_AMAZONE_S3;
                $this->uploadToAmazoneS3($file);
            }

            if (!$file->save()) {
                return [
                    'errors' => $file->getFirstErrors(),
                ];
            }

            if (!empty($fileConfig['fixedSize']) && !$file->checkImageFixedSize($fileConfig['fixedSize'])) {
                return [
                    'errors' => $file->getImageMeta(FileModule::PROCESSOR_NAME_ORIGINAL)->getFirstErrors()
                ];
            }

            if ($source === self::SOURCE_AMAZONE_S3) {
                if ($file->isImage()) {
                    $processors = array_keys(FileModule::getInstance()->processors);

                    // Generate and upload thumb images
                    foreach ($processors as $processor) {
                        $imageMeta = $file->getImageMeta($processor);
                        $this->uploadToAmazoneS3($imageMeta);
                        $imageMeta->saveOrPanic(['amazoneS3Url']);
                    }

                    // Delete local files
                    foreach ($processors as $processor) {
                        unlink($file->getImageMeta($processor)->path);
                    }
                } else {
                    // Delete local files
                    unlink($file->path);
                }
            }

            $files[] = $file;
        }

        return $files;
    }

    /**
     * @param File|ImageMeta $file
     * @param null $sourcePath
     */
    public function uploadToAmazoneS3($file, $sourcePath = null)
    {
        $folder = trim($file->folder, '/');

        ob_start();
        $this->amazoneStorage
            ->commands()
            ->upload(($folder ? $folder . '/' : '') . $file->fileName, $sourcePath ?: $file->path)
            ->withContentType($file->fileMimeType)
            ->execute();
        $file->amazoneS3Url = $this->amazoneStorage->getUrl($file->fileName);
        ob_end_clean();
    }

    public function coreMenu()
    {
        return [
            'file' => [
                'label' => 'Модуль загрузки и скачивания файла',
                'visible' => false,
                'items' => [
                    'upload' => [
                        'url' => ["/$this->id/upload/index"],
                        'urlRule' => "$this->id/upload",
                    ],
                    'upload-editor' => [
                        'url' => ["/file/upload/editor"],
                        'urlRule' => "$this->id/upload/editor",
                    ],
                    'download' => [
                        'url' => ["/file/download/index"],
                        'urlRule' => "$this->id/<uid:[a-z0-9-]{36}>/<name>",
                    ],
                ]
            ]
        ];
    }
}