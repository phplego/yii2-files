<?php

namespace chabberwock\files\controllers;

use chabberwock\files\Module;
use chabberwock\files\UploadEvent;
use yii\web\Controller;
use yii\helpers\Json;
use Yii;
use yii\helpers\Url;


/**
 * Default controller for the `files` module
 */
class UploadController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        header('Content-type: application/json');
        $module = Module::getInstance();
        $sessionId = Yii::$app->request->get('sessionId');
        $session = Module::getInstance()->findSession($sessionId);
        if (!$session) {
            throw new \Exception('No session');
        }
        $file = $session->addFile($_FILES['file']['tmp_name'], [
            'name' => $_FILES['file']['name'],
            'type' => $_FILES['file']['type'],
            'data' => $_POST
        ]);
        
        $event = new UploadEvent;
        $event->session = $session;
        $event->file = $file;
        Yii::$app->trigger('files.upload', $event);
        
        //$path = '/img/temp/' . Yii::$app->session->id . DIRECTORY_SEPARATOR . $fileName;
        $resp = [
            'name' => $file->name,
            'size' => $file->size,
            /*
            "url" => Url::to(['/'.$this->module->uniqueId . '/preview', 'sessionId'=>$session->id, 'fileId'=>$file->id]),
            "thumbnailUrl" => Url::to(['/'.$this->module->uniqueId . '/upload/preview', 'sessionId'=>$session->id, 'fileId'=>$file->id]),
            */
            "deleteUrl" => Url::to(['/' . $this->module->uniqueId . '/upload/delete', 'sessionId'=>$session->id, 'fileId'=>$file->id]),
            "deleteType" => "POST"
        ];
        if ($file->url) {
            $resp['url'] = $file->url;
        }
        return Json::encode([
            'files' => [$resp]
        ]);        
    }
    
    public function actionPreview($sessionId, $fileId)
    {
        $session = Module::getInstance()->findSession($sessionId);
        $file = $session->getFile($fileId);
        header('Content-type: ' . $file->type);
        header('Content-length: ' . $file->size);
        $session->readFile($file);
    }

    public function actionDelete($sessionId, $fileId)
    {
        header('Content-type: application/json');
        $session = Module::getInstance()->findSession($sessionId);
        $file = $session->getFile($fileId);
        if ($file) {
            $event = new UploadEvent;
            $event->session = $session;
            $event->file = $file;
            Yii::$app->trigger('files.delete', $event);
            $session->deleteFile($file);
        }
        $output = [];
        $files = $session->listFiles();
        if (!empty($files)) {
            foreach ($files as $file)
            {
                $output['files'][] = [
                    'name' => $file->name,
                    'size' => $file->size,
                    "deleteUrl" => Url::to(['/' . $this->module->uniqueId . '/upload/delete', 'sessionId'=>$session->id, 'fileId'=>$file->id]),
                    "deleteType" => "POST"
                ];
            }
        }
        return Json::encode($output);
        
    }
    

}
