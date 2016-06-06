<?php

namespace TheIconic\Config;

use PHPUnit_Framework_TestCase;
use TheIconic\Config\Parser\Dummy;
use org\bovigo\vfs\vfsStream;

/**
 * tests the config cache class
 *
 * @package Shared\Helper\Config
 */
class CacheTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var stores the vfsStream root
     */
    protected $root;

    /**
     * generates a dummy config to use as return value from reading the cache file
     *
     * @return array
     */
    protected function getDummyCachedConfig()
    {
        return array(
            'cachedkey' => 'cachedvalue',
        );
    }

    /**
     * generates a dummy config to use as emulated return value from the parser
     *
     * @return array
     */
    protected function getDummyParsedConfig()
    {
        return array(
            'all' => array(
                'key' => 'value',
            ),
            'staging' => array(
                'other' => 'othervalue',
            ),
            'dev' => array(
                'key' => 'devvalue',
            ),
        );
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
        return array(
            vfsStream::url('cachetest/application.ini'),
            vfsStream::url('cachetest/local.ini'),
            vfsStream::url('cachetest/dev.ini'),
        );
    }

    /**
     * creates the partially mocked config cache instance
     *
     * @param string|null $environment the environment to use
     * @param bool $disableWrite if true, disable the write method on the cache instance
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCache($environment = null, $disableWrite = true)
    {
        if (null === $environment) {
            $environment = APPLICATION_ENV;
        }

        $methods = array(
            'getCacheFile',
        );

        if (!ini_get('allow_url_include')) {
            $methods[] = 'readCacheFile';
        }

        if ($disableWrite) {
            $methods[] = 'write';
        }

        $cache = $this->getMock('Shared\Helper\Config\Cache', $methods, array($this->getSourcePaths(), $environment));
        $cache->expects($this->any())
            ->method('getCacheFile')
            ->will($this->returnValue(vfsStream::url('cachetest/cachefile.php')));

        if (!ini_get('allow_url_include')) {
            $cache->expects($this->any())
                ->method('readCacheFile')
                ->will($this->returnValue($this->getDummyCachedConfig()));
        }

        $cache->setParser($this->getDummyParser());

        return $cache;
    }

    /**
     * sets up the vfsStream root
     */
    public function setUp()
    {
        return $this->markTestSkipped('test needs to be adjusted to Config refactor');

        parent::setUp();

        $this->root = vfsStream::setup('cachetest');
    }

    /**
     * tests the instanciation of the config cache class
     */
    public function testInstantiation()
    {
        $cache = $this->getCache('dev');

        $this->assertSame($this->getSourcePaths(), $cache->getSourcePaths());
        $this->assertSame('dev', $cache->getEnvironment());
    }

    /**
     * tests the @see Cache::getReadableSources()
     */
    public function testGetReadableSources()
    {
        $readable = array(
            vfsStream::url('cachetest/application.ini'),
            vfsStream::url('cachetest/local.ini'),
        );

        foreach ($readable as $path) {
            touch($path);
        }

        $this->assertSame($readable, $this->getCache()->getReadableSources());
    }

    /**
     * tests that Cache::get() retrieves the config from the cache
     * if the cache file is newer than the sources
     *
     * (implicitly tests the isValid() method)
     */
    public function testGetFromCache()
    {
        $time = time();

        vfsStream::newFile('application.ini')
            ->at($this->root)
            ->withContent('')
            ->lastModified($time - 60);

        vfsStream::newFile('location.ini')
            ->at($this->root)
            ->withContent('')
            ->lastModified($time - 60);

        vfsStream::newFile('cachefile.php')
            ->at($this->root)
            ->withContent('<php return ' . var_export($this->getDummyCachedConfig(), true) . ';')
            ->lastModified($time);

        $this->assertSame($this->getDummyCachedConfig(), $this->getCache()->get());
    }

    /**
     * tests that Cache::get() gets the config from the parser
     * if the config file is older than some of the source files
     *
     * (implicitly tests the isValid() and parse() methods)
     */
    public function testGetFromParser()
    {
        $time = time();

        vfsStream::newFile('application.ini')
            ->at($this->root)
            ->withContent('')
            ->lastModified($time);

        vfsStream::newFile('location.ini')
            ->at($this->root)
            ->withContent('')
            ->lastModified($time);

        vfsStream::newFile('cachefile.php')
            ->at($this->root)
            ->withContent('<?php')
            ->lastModified($time - 60);

        $this->assertSame(array('key' => 'devvalue'), $this->getCache('dev')->get());
        $this->assertSame(array('key' => 'value', 'other' => 'othervalue'), $this->getCache('staging')->get());
        $this->assertSame(array('key' => 'value'), $this->getCache('live')->get());
    }

    /**
     * tests that a newer config is successfully written to the cache file
     */
    public function testWrite()
    {
        $cache = $this->getCache('dev', false);

        vfsStream::newFile('application.ini')
            ->at($this->root)
            ->withContent('')
            ->lastModified(time());

        $cache->get();

        $written = file_get_contents(vfsStream::url('cachetest/cachefile.php'));

        $this->assertRegExp('/<\?php/sm', $written);
        $this->assertRegExp('/return array\s*\(\s*\'key\'\s*=>\s*\'devvalue\'\s*,\s*\);/sm', $written);
    }

}
