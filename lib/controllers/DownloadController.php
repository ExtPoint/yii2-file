<?php

namespace extpoint\yii2\file\controllers;

use extpoint\yii2\base\Controller;
use extpoint\yii2\file\FileModule;
use extpoint\yii2\file\models\File;
use yii\web\NotFoundHttpException;

class DownloadController extends Controller
{
    public function actionIndex($uid)
    {
        /** @var File $file */
        $file = File::findOne(['uid' => $uid]);
        if (!$file) {
            throw new NotFoundHttpException();
        }

        if (FileModule::getInstance()->xHeader !== false) {
            \Yii::$app->response->xSendFile($file->path, $file->downloadName, [
                'xHeader' => FileModule::getInstance()->xHeader,
            ]);
        } else {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file->getDownloadName() . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file->path));
            readfile($file->path);
        }
    }
}