<?php

namespace Awescrm\CdnGCS\App;
include_once 'Jnt.php';

class DecodeURL extends Jnt
{

    //Файл настроек
    public $config;
    //Полный путь до превью
    public $thumb;
    public $thumbNext;
    //Полный путь до оригинала
    public $image;

    //Метаинформация
    public $meta;
    public $hash;

    public function __construct($encodeUrl)
    {
        $this->init();
        $this->decodeUrl($encodeUrl);
    }

    /**
     * @param $encodeUrl
     */
    public function decodeUrl($encodeUrl)
    {
        $fileInfo = new \SplFileInfo($encodeUrl);
        $fileName = $fileInfo->getBasename(".{$fileInfo->getExtension()}");

        $this->setMeta($fileName);
        //Полный путь до превью
        $this->thumb = $this->thumbNext = $this->assemblyUrl([
            $this->config['thumb_folder'],
            $fileInfo->getPath(),
            $fileInfo->getBasename()
        ]);
        //Полный путь до оригинала
        $this->image = $this->assemblyUrl([
            $fileInfo->getPath(),
            $this->meta['imageName'] . '.' . $this->getExtId($this->meta['getExtId'], true)
        ]);

    }

    public function setMeta($fileName) {
        $opt = explode('_', $fileName);
        if (count($opt) !== 5) {
            $this->meta = false;
            return '';
        }
        $this->hash = $opt[2];
        $this->meta['key'] = $this->config['secret_key'];
        $this->meta['slug'] = $opt[0];
        $this->meta['imageName'] = $opt[1];
        $this->meta['getExtId'] = $opt[3];
        $this->meta['modify'] = $opt[4];
    }

    public function validate()
    {
        return ($this->meta && ($this->hash == $this->getHashObj($this->meta)));
    }

    //Следующее изображение
    public function getThumbNext() {
        $thumb = $this->thumbNext;

        $fileInfo = new \SplFileInfo($thumb);
        $fileExt = $fileInfo->getExtension();
        //$fileName = preg_replace("/\.{$fileExt}$/", '', $thumb);
        $fileName = $fileInfo->getBasename(".{$fileInfo->getExtension()}");
        $filePath = $fileInfo->getPath();

        //Разбираем
        $oArr = explode('_', $fileName);
        $mArr = explode('--', $oArr[4]);

        //Меняем один за другим
        $op1 = array_shift($mArr);
        array_push($mArr, $op1);

        //Собираем
        $m = implode('--', $mArr);
        $oArr[4] = $m;

        $oArr[2] = $this->getHashObj([
            $this->config['secret_key'],
            $oArr[0], $oArr[1], $oArr[3], $oArr[4]
        ]);

        $this->thumbNext = $filePath .'/'. implode('_', $oArr) . '.' . $fileExt;
        return $thumb;
    }

}
