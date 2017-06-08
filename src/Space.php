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
     * @var Config
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
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function get($key = null, $default = null)
    {
        $this->init();

        return $this->config->get($key, $default);
    }

    /**
     * @return array
     */
    public function flatten(): array
    {
        $this->init();

        return $this->config->flatten();
    }

    /**
     * initialize the config space
     *
     * loads the config from cache or the config files
     */
    protected function init()
    {
        if (null !== $this->config) {
            return;
        }

        $cache = $this->getCache();
        $cacheKey = $this->getCacheKey();

        if ($cache->isValid($cacheKey, $this->getTimestamp())) {
            $config = $cache->read($cacheKey);
        } else {
            $config = $this->parse();
            $cache->write($cacheKey, $config, $this->getPaths());
        }

        $this->config = new Config($config);
    }

    /**
     * parses the configs
     *
     * @return array the parsed and flattened config array
     */
    protected function parse(): array
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
    protected function replacePlaceholders(array $config): array
    {
        if (empty($this->placeholders)) {
            return $config;
        }

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->replacePlaceholders($value);
                continue;
            }

            if (is_string($value)) {
                $config[$key] = strtr($value, $this->placeholders);
            }
        }

        return $config;
    }

    /**
     * @return int
     */
    protected function getTimestamp(): int
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
    protected function getReadableSources(): array
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
    protected function flattenSections(array $config): array
    {
        $merged = [];

        $sections = $this->getSections();

        if (empty($sections)) {
            throw new PreconditionException('No sections have been configured.');
        }

        foreach (array_unique($this->getSections()) as $section) {
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
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * set the config cache handler
     *
     * @param Cache $cache
     * @return Space
     */
    public function setCache(Cache $cache): Space
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * get the environment
     *
     * @return array
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * set the sections to use
     *
     * @param array $sections
     * @return Space
     */
    public function setSections(array $sections): Space
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * @param string $section
     * @return Space
     */
    public function addSection(string $section): Space
    {
        if (!in_array($section, $this->sections)) {
            $this->sections[] = $section;
        }

        return $this;
    }

    /**
     * get the parser
     *
     * @return Parser
     */
    public function getParser(): Parser
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
     * @return Space
     */
    public function setParser(AbstractParser $parser): Space
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
    protected function getCacheKey(): string
    {
        return strtolower(md5(sprintf('%s_%s', implode('::', $this->getPaths()), implode('::', $this->getSections()))));
    }

    /**
     * recursively merges the arrays
     * later keys overload earlier keys
     * numeric keys are appended
     *
     * @param array $base
     * @param array $subject
     *
     * @return array the result of the merge
     */
    protected function merge(array $base, array $subject): array
    {
        foreach ($subject as $k => $v) {
            if (is_numeric($k)) {
                $base[] = $v;
            } elseif (array_key_exists($k, $base) && (is_array($v) || is_array($base[$k]))) {
                $base[$k] = $this->merge((array) $base[$k], (array) $v);
            } else {
                $base[$k] = $v;
            }
        }

        return $base;
    }

    /**
     * @return array
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /**
     * @param array $placeholders
     * @return Space
     */
    public function setPlaceholders(array $placeholders): Space
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    /**
     * @param string $placeholder
     * @param mixed $value
     * @return Space
     */
    public function addPlaceholder($placeholder, $value): Space
    {
        $this->placeholders[$placeholder] = $value;

        return $this;
    }
}
