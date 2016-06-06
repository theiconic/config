<?php

namespace TheIconic\Config;

use TheIconic\Config\Exception\PreconditionException;

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
     * @var string the environment
     */
    protected $environment;

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
        $environment = strtolower($this->getEnvironment());

        $key = $name . '::' . $environment;

        if (!isset($this->spaces[$key])) {
            $config = new Space($name, $environment);
            $config->setCache($this->getCache());

            $this->spaces[$key] = $config;
        }

        return $this->spaces[$key];
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

    /**
     * @return string
     */
    public function getEnvironment()
    {
        if (null === $this->environment) {
            throw new PreconditionException('Environment has not been set.');
        }

        return $this->environment;
    }

    /**
     * @param $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

}