<?php

namespace Awescrm\CdnGCS\Tests;

use Tests\TestCase;
use App\Package\CdnGCS\CdnGCS;
use App\Package\CdnGCS\App\encodeURL;
use App\Package\CdnGCS\App\encode;
use App\Package\CdnGCS\App\decodeURL;

//Mock
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;

class CdnGCSTest extends TestCase
{
    private $class;
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->config  = (include './App/config.php');
        $this->class = new CdnGCS($this->createMock(Cache::class), $this->config, $this->createMock(Disk::class));
    }

//-----------------------------------------------------
// Test section
//-----------------------------------------------------

    /**
     * Проверка генератора на корректность
     */
    public function testHash()
    {
        foreach ($this->listUrls() as $url)
        {
            $uri = $url['helper']['opt1'];
            $opt = $url['helper']['opt2'] ?? false;

            $uriHash = $this->class->testSrc($uri, $opt);
            $image = $this->class->testHtml($uri, $opt);

            $secretHash = (new encode($uri, $opt, $this->config))->testSecretHash();
            $secretHashImg = [
                (new encode($uri, $opt, $this->config))->testSecretHash(-2),
                (new encode($uri, $opt, $this->config))->testSecretHash(2)
            ];

            $this->assertEquals(str_replace('|secretHash|', $secretHash, $url['url']), $uriHash);
            $this->assertEquals(str_replace(['|secretHash|', '|secretHash2|'], $secretHashImg, $url['img']), $image);

            //Сразу проверим декодирование
            $decode = new DecodeURL($url['url']);
            $this->assertEquals($this->config['thumb_folder'].'/'.$url['url'], $decode->thumb);
            //$this->assertEquals($secretHash, $decode->getHashObj());
        }
    }

//-----------------------------------------------------
// Support function
//-----------------------------------------------------

    public function listUrls() {
        return [
            [
                'url' => "images/test_photo-2323_|secretHash|_1_s200-cc.webp",
                'img' => '<img src="images/test_photo-2323_|secretHash|_1_s200-cc--s400-cc.webp" srcset="images/test_photo-2323_|secretHash2|_1_s400-cc--s200-cc.webp 2x" title="Test" alt="Test" />',
                'helper' => [
                    'opt1' => 'images/photo-2323.jpg',
                    'opt2' => [
                        'title' => 'Test',
                        'ext' => 'webp',
                        'modify' => 's200-cc'
                    ]
                ]
            ], [
                'url' => "images/cool-duesseldorf_photo-2323_|secretHash|_1_w100-h500.png",
                'img' => '<img src="images/cool-duesseldorf_photo-2323_|secretHash|_1_w100-h500--w200-h1000.png" srcset="images/cool-duesseldorf_photo-2323_|secretHash2|_1_w200-h1000--w100-h500.png 2x" alt="Cool Düsseldorf" />',
                'helper' => [
                    'opt1' => 'images/photo-2323.jpg',
                    'opt2' => [
                        'alt' => 'Cool Düsseldorf',
                        'ext' => 'png',
                        'modify' => 'w100-h500'
                    ]
                ]
            ], [
                'url' => "_photo-002314_|secretHash|_1_w640.jpg",
                'img' => '<img src="_photo-002314_|secretHash|_1_w640--w1280.jpg" srcset="_photo-002314_|secretHash2|_1_w1280--w640.jpg 2x" alt="photo-002314" />',
                'helper' => [
                    'opt1' => 'photo-002314.jpg',
                    'opt2' => [
                        'modify' => 'w640'
                    ]
                ]
            ], [
                'url' => "images/photo-002314.jpg",
                'url2x' => "images/photo-002314.jpg",
                'img' => '<img src="images/photo-002314.jpg" alt="photo-002314" />',
                'helper' => [
                    'opt1' => 'images/photo-002314.jpg'
                ]
            ],
        ];
    }
}
