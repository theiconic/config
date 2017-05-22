<?php

namespace TheIconic\Config;

use TheIconic\Config\Exception\PreconditionException;

/**
 * Config Space Factory
 *
 * @package TheIconic\Config
 */
class Factory
{

    /**
     * @var Factory the factory instance
     */
    protected static $instance;

    /**
     * @var array the config spaces
     */
    protected $spaces = [];

    /**
     * @var string the cache base path
     */
    protected $cachePath;

    /**
     * @var Cache the cache
     */
    protected $cache;

    /**
     * @return static
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getSpace($name)
    {
        $name = strtolower($name);

        if (!isset($this->spaces[$name])) {
            $config = new Space($name);
            $config->setCache($this->getCache());

            $this->spaces[$name] = $config;
        }

        return $this->spaces[$name];
    }

    /**
     * @param $path
     * @return $this
     */
    public function setCachePath($path)
    {
        $this->cachePath = $path;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCachePath()
    {
        if (null === $this->cachePath) {
            throw new PreconditionException('Cache path has not been set.');
        }

        return $this->cachePath;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $this->cache = new Cache($this->getCachePath());
        }

        return $this->cache;
    }

    /**
     * @param Cache $cache
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }
}