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
     * @var array the sections to use
     */
    protected $sections = [];

    /**
     * @var AbstractParser $parser
     */
    protected $parser;

    /**
     * @var string the config namespace
     */
    protected $name;

    /**
     * @var array placeholders
     */
    protected $placeholders = [];

    /**
     * Space constructor.
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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

    /**
     * @return array
     */
    public function flatten()
    {
        return $this->doFlatten($this->get());
    }

    /**
     * parses the configs
     *
     * @return array the parsed and flattened config array
     */
    protected function parse()
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

        return $this->flattenSections($config);
    }

    /**
     * @param array $config
     * @return array
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
    protected function getReadableSources()
    {
        $paths = [];

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
     * @return array the flattened config array
     */
    protected function flattenSections(array $config)
    {
        $merged = [];

        $sections = $this->getSections();

        if (empty($sections)) {
            throw new PreconditionException('No sections have been configured.');
        }

        foreach ($this->getSections() as $section) {
            $section = strtolower($section);

            if (!isset($config[$section])) {
                continue;
            }

            $merged = $this->merge($merged, $config[$section]);
        }

        return $merged;
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
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * set the environment
     *
     * @return $this
     */
    public function setSections()
    {
        $this->sections = func_get_args();

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
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     * @return Space
     */
    public function setPaths(array $paths): Space
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @param string $path
     * @return Space
     */
    public function addPath($path): Space
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
        return strtolower(md5(sprintf('%s_%s', implode('::', $this->getPaths()), implode('::', $this->getSections()))));
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
    protected function merge(array $base, array $subject)
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
