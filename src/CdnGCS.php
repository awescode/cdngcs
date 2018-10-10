<?php

namespace Awescrm\CdnGCS;

use TaOs\CdnGCS\App\encode;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;

class CdnGCS
{

    /**
     * The cache instance.
     */
    protected $cache;
    protected $config;
    protected $disk;
    protected $locally;

    /**
     * Create a new repository instance.
     *
     * @param  Cache $cache
     * @param  array $config
     * @param  Disk $disk
     * @param  string $locally
     * @return void
     */
    public function __construct(Cache $cache, array $config, Disk $disk, $locally = '')
    {
        $this->cache = $cache;
        $this->config = $config;
        $this->disk = $disk;
        $this->locally = $locally;
    }

    //======================================================================
    // Core
    //======================================================================

    /**
     * Получить картинку
     *
     * @param $path
     * @param array $options
     * @return mixed
     */
    public function Img($path, $options = []) {
        $imgClass = new encode($path, $options, $this->config);

        //Если ключ есть в кеше, отдает html картинку с 2-х версией для ретины
        $fileKey = $imgClass->getKey();
        if ($cacheHtml = $this->getCache($fileKey)) {
            return $this->res($cacheHtml, 'From cache');
        }

        //Код нииже выполняется заметно медленее

        //Если файл есть на диске, генерируем HTML, записываем в кеш, и отдаем в blade
        $file = (!$imgClass->isOriginal()) ? $this->config['thumb_folder'] . '/' . $imgClass->getPath(2) : $imgClass->getPath();
        if ($this->hasFile($file)) {
            $cacheHtml = $this->getHtml($imgClass, $this->sUrl($imgClass->isOriginal()));
            $this->setCache($fileKey, $cacheHtml);
            return $this->res($cacheHtml, 'From disk, set cache');
        }

        //Проверяем оригинальный файл, и гененриируем динамическую ссылку или noImg
        $original = $imgClass->getOriginal();
        if ($this->hasFile($original)) {
            $cacheHtml = $this->getHtml($imgClass, $this->dUrl());
        } else {
            $cacheHtml = $this->noImg();
        }
        //TODO: создавать кеш с коротким сроком жизни
        return $cacheHtml;
    }

    public function res($out, $info = '') {
        if ($this->config['debug'] && $info) {
            \Debugbar::info($info);
        }
        return $out;
    }

    public function getHtml($imageClass, $domain = '') {
        $title = ($imageClass->options->title ?? false) ? " title=\"{$imageClass->options->title}\"" : "";
        $srcSet = (!$imageClass->isOriginal()) ? " srcset=\"{$domain}{$imageClass->getPath(2)} 2x\"" : "";

        return "<img src=\"{$domain}{$imageClass->getPath(-2)}\"{$srcSet}{$title} alt=\"{$imageClass->getAlt()}\" />";
    }

    public function noImg() {
        return 'No img';
    }

    //======================================================================
    // Support function
    //======================================================================

    /**
     * Статический URL
     *
     * @param bool $original
     * @return string
     */
    private function sUrl($original = false)
    {
        return (!$original)
            ? $this->config['cdn-static'] .'/'. $this->config['thumb_folder'] .'/'
            : $this->config['cdn-static'] .'/';
    }

    /**
     * Динамический URL
     *
     * @return string
     */
    private function dUrl()
    {
        return $this->config['cdn-dynamic'] .'/';
    }

//======================================================================
// Test function
//======================================================================

    /**
     * Вспомогательный метод для тестов
     *
     * @param $path
     * @param $options
     * @return string
     */
    public function testHtml($path, $options) {
        $imgClass = new encode($path, $options, $this->config);
        return $this->getHtml($imgClass);
    }

    /**
     * @param $path
     * @param $options
     * @param bool $p
     * @return string
     */
    public function testSrc($path, $options,  $p = false) {
        $imgClass = new encode($path, $options, $this->config);
        return $imgClass->getPath($p);
    }

//-----------------------------------------------------
// API methods: Disk
//-----------------------------------------------------

    /**
     * @param $path
     * @return bool
     */
    private function hasFile($path)
    {
        return $this->disk->exists($path);
    }

//-----------------------------------------------------
// API methods: Cache
//-----------------------------------------------------

    /**
     * @param $key
     * @return bool
     */
    private function hasCache($key)
    {
        return $this->cache->has($key);
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getCache($key) {
        return $this->cache->get($key);
    }

    /**
     * @param $key
     * @param $val
     */
    private function setCache($key, $val)
    {
        $this->cache->forever($key, $val);
    }

}
