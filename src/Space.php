<?php

namespace TheIconic\Config;

use TheIconic\Config\Exception\ParserException;
use TheIconic\Config\Exception\PreconditionException;
use TheIconic\Config\Parser\AbstractParser;
use TheIconic\Config\Parser\Autodetect as Parser;

/**
 * handler for shared application config
 *
 * @package Shared\Helper
 */
class Space
{

    /**
     * @var null|array stores the config
     */
    protected $config;

    /**
     * @var array the config file paths
     */
    protected $paths = [];

    /**
     * @var Cache the config cache handler
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cachePath;

    /**
     * @var string the environment
     */
    protected $environment;

    /**
     * @var AbstractParser $parser
     */
    protected $parser;

    /**
     * @var string the config namespace
     */
    protected $name;

    /**
     * @var boolean indicates if config should be flattened by environment
     */
    protected $usesEnvironment = true;

    /**
     * @var array placeholders
     */
    protected $placeholders = [];

    /**
     * Set constructor.
     * @param string $name
     * @param string|null $environment
     */
    public function __construct($name, $environment = null)
    {
        $this->name = $name;

        if (null !== $environment) {
            $this->setEnvironment($environment);
        }
    }

    /**
     * get the config value for a given key or the entire config if key is omitted
     * allows specifying a default that is used if key is not present in config
     *
     * @param string|null $key the config key
     * @param mixed|null $default the default value
     * @return array|mixed|null the config value or the entire configuration array
     */
    public function get($key = null, $default = null)
    {
        if (null === $this->config) {
            $cache = $this->getCache();
            $cacheKey = $this->getCacheKey();
            
            if ($cache->isValid($cacheKey, $this->getTimestamp())) {
                $this->config = $cache->read($cacheKey);
            } else {
                $this->config = $this->parse();
                $cache->write($cacheKey, $this->config, $this->getPaths());
            }
        }

        if (null === $key) {
            return $this->config;
        }

        return $this->resolve($key, $default);
    }

    public function flatten()
    {
        return $this->doFlatten($this->get());
    }

    /**
     * allows setting a value for a config key
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        if (null === $this->config) {
            $this->config = $this->get();
        }

        $this->config[$key] = $value;
    }

    /**
     * parses the configs
     *
     * @return array the parsed and flattened config array
     */
    public function parse()
    {
        $parser = $this->getParser();

        $config = [];
        foreach ($this->getReadableSources() as $path) {
            try {
                $config = $this->merge($config, $this->replacePlaceholders($parser->parse($path)));
            } catch (ParserException $e) {
                // ignore parse errors
            }
        }

        if ($this->usesEnvironment) {
            return $this->flattenByEnvironment($config, $this->getEnvironment());
        }

        return $config;
    }

    /**
     * @param array $config
     */
    protected function replacePlaceholders(array $config)
    {
        if (empty($this->placeholders)) {
            return $config;
        }

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->replacePlaceholders($value);
            } else if (is_string($value)) {
                $config[$key] = strtr($value, $this->placeholders);
            }
        }

        return $config;
    }

    /**
     * @return int
     */
    protected function getTimestamp()
    {
        $timestamp = 0;
        foreach ($this->getReadableSources() as $path) {
            $timestamp = max($timestamp, filemtime($path));
            $timestamp = max($timestamp, filemtime(dirname($path)));
        }

        return $timestamp;
    }
    
    /**
     * get the actually readable source paths
     *
     * @return array
     */
    public function getReadableSources()
    {
        $paths = array();

        foreach ($this->getPaths() as $path) {
            if (is_readable($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * flattens the config array for the given environment
     *
     * @param array $config the raw config array
     * @param string $environment the environment
     * @return array the flattened config array
     */
    protected function flattenByEnvironment(array $config, $environment)
    {
        if (empty($environment)) {
            throw new PreconditionException('Environment is not set.');
        }

        if (!isset($config['all'])) {
            $config['all'] = [];
        }

        if (!isset($config[$environment])) {
            $config[$environment] = [];
        }

        return $this->merge($config['all'], $config[$environment]);
    }
    
    /**
     * get the config cache handler
     *
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * set the config cache handler
     *
     * @param Cache $cache
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * get the environment
     *
     * @return null|string
     */
    public function getEnvironment()
    {
        if (!$this->environment) {
            $this->environment = APPLICATION_ENV;
        }

        return $this->environment;
    }

    /**
     * set the environment
     *
     * @param string|null $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    /**
     * get the parser
     *
     * @return Parser
     */
    public function getParser()
    {
        if (null === $this->parser) {
            $this->parser = new Parser();
        }

        return $this->parser;
    }

    /**
     * set the parser
     *
     * @param AbstractParser $parser
     * @return $this
     */
    public function setParser(AbstractParser $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * get configuration file paths
     *
     * @return array an array of configuration file paths
     */
    protected function getPaths()
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function addPath($path)
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * get the path to the cache config file for the current environment
     *
     * @return string the file path
     */
    protected function getCacheKey()
    {
        return strtolower(sprintf('%s_%s', md5(implode('::', $this->getPaths())), $this->getEnvironment()));
    }

    /**
     * @param string $path
     * @param mixed|null $default
     * @return array|mixed|null
     */
    protected function resolve($path, $default = null)
    {
        $segments = explode('.', $path);

        $result = $this->config;
        foreach ($segments as $segment) {
            if (!isset($result[$segment])) {
                return $default;
            }

            $result = $result[$segment];
        }

        return $result;
    }

    /**
     * @param array $config
     * @return array
     */
    protected function doFlatten($config)
    {
        $flattened = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $tmp = $this->doFlatten($value);
                foreach ($tmp as $k => $v) {
                    $flattened[$key . '.' . $k] = $v;
                }
            } else {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }

    /**
     * merge arrays recursively
     * later keys overload earlier keys
     * numeric keys are appended
     *
     * @param array $base
     * @param array $subject
     * 
     * @return array
     */
    public function merge(array $base, array $subject)
    {
        $args = func_get_args();
        $base = array_shift($args);

        while (!empty($args)) {
            $subject = array_shift($args);

            foreach ($subject as $k => $v) {
                if (is_numeric($k)) {
                    $base[] = $v;
                } else if (!array_key_exists($k, $base)) {
                    $base[$k] = $v;
                } else if (is_array($v) || is_array($base[$k])) {
                    $base[$k] = $this->merge((array) $base[$k], (array) $v);
                } else {
                    $base[$k] = $v;
                }
            }
        }

        return $base;
    }

    /**
     * set/get if we should merge by environment
     *
     * @param null $flag
     * @return $this|bool
     */
    public function useEnvironment($flag = null)
    {
        if (null === $flag) {
            return $this->usesEnvironment;
        }

        $this->usesEnvironment = $flag;

        return $this;
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @param array $placeholders
     * @return $this
     */
    public function setPlaceholders(array $placeholders)
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    /**
     * @param $placeholder
     * @param $value
     */
    public function addPlaceholder($placeholder, $value)
    {
        $this->placeholders[$placeholder] = $value;
    }

}
