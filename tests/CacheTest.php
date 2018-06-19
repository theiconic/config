<?php

namespace TheIconic\Config;

use PHPUnit\Framework\TestCase;
use TheIconic\Config\Parser\Dummy;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class CacheTest extends TestCase
{
    /**
     * @var vfsStreamDirectory stores the vfsStream root
     */
    protected $root;

    /**
     * generates a dummy config to use as return value from reading the cache file
     *
     * @return array
     */
    protected function getDummyCachedConfig()
    {
        return [
            'cachedkey' => 'cachedvalue',
        ];
    }

    /**
     * generates a dummy config to use as emulated return value from the parser
     *
     * @return array
     */
    protected function getDummyParsedConfig()
    {
        return [
            'all' => [
                'key' => 'value',
            ],
            'staging' => [
                'other' => 'othervalue',
            ],
            'dev' => [
                'key' => 'devvalue',
            ],
        ];
    }

    /**
     * instanciates as dummy parser
     *
     * @return Dummy
     */
    protected function getDummyParser()
    {
        $parser = new Dummy();
        $parser->setContent($this->getDummyParsedConfig());

        return $parser;
    }

    /**
     * builds an array of suitable source config paths
     *
     * @return array
     */
    protected function getSourcePaths()
    {
        return [
            vfsStream::url('cachetest/application.ini'),
            vfsStream::url('cachetest/local.ini'),
            vfsStream::url('cachetest/dev.ini'),
        ];
    }

    /**
     * sets up the vfsStream root
     */
    public function setUp()
    {
        parent::setUp();

        $this->root = vfsStream::setup('cachetest');
    }

    /**
     * tests that Cache::get() retrieves the config from the cache
     * if the cache file is newer than the sources
     *
     * (implicitly tests the isValid() method)
     */
    public function testIsValid()
    {
        $time = time();

        vfsStream::newFile('cachefile.php')
            ->at($this->root)
            ->withContent('<php return ' . var_export($this->getDummyCachedConfig(), true) . ';')
            ->lastModified($time);

        $cache = new Cache(vfsStream::url('cachetest'));

        $this->assertSame(true, $cache->isValid('cachefile', $time - 60));
    }

    /**
     * tests that Cache::get() retrieves the config from the cache
     * if the cache file is newer than the sources
     *
     * (implicitly tests the isValid() method)
     */
    public function testIsNotValid()
    {
        $time = time();

        vfsStream::newFile('cachefile.php')
            ->at($this->root)
            ->withContent('<php return ' . var_export($this->getDummyCachedConfig(), true) . ';')
            ->lastModified($time - 60);

        $cache = new Cache(vfsStream::url('cachetest'));

        $this->assertSame(false, $cache->isValid('cachefile', $time));
    }

    /**
     * test the read() method
     */
    public function testRead()
    {
        $basePath = __DIR__ . '/config';

        $cache = new Cache($basePath);
        
        $this->assertSame($basePath, $cache->getBasePath());
        $this->assertSame($this->getDummyCachedConfig(), $cache->read('cachefile'));
    }

    public function testWrite()
    {
        if (!ini_get('allow_url_fopen')) {
            $this->markTestSkipped('allow_url_fopen is not enabled');
        }

        $basePath = vfsStream::url('cachetest') . '/foo/bar';

        $cache = new Cache($basePath);

        $cache->write('cachefile', $this->getDummyCachedConfig(), $this->getSourcePaths());

        $expected = <<<EOF
<?php
/*
 * this file- is autogenerated by TheIconic\Config\Cache
 * and will be automatically overwritten
 * please edit the source files instead
 * vfs://cachetest/application.ini
 * vfs://cachetest/local.ini
 * vfs://cachetest/dev.ini
*/
return array (
  'cachedkey' => 'cachedvalue',
);
EOF;

        $this->assertSame($expected, file_get_contents(vfsStream::url('cachetest/foo/bar/cachefile.php')));
    }
    
    public function testNonReadable()
    {
        $basePath = vfsStream::url('cachetest');
        $cache = new Cache($basePath);

        $this->assertFalse($cache->isValid('non_existing_cache_key', time()));
    }
}
