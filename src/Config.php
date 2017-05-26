<?php

namespace TheIconic\Config;

class Config
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
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
        if (null === $key) {
            return $this->config;
        }

        return $this->resolve($key, $default);
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
     * @param array|null $config
     * @return array
     */
    public function flatten(array $config = null): array
    {
        $flattened = [];

        if (null === $config) {
            $config = $this->config;
        }

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $tmp = $this->flatten($value);

                foreach ($tmp as $k => $v) {
                    $flattened[$key . '.' . $k] = $v;
                }

                continue;
            }

            $flattened[$key] = $value;
        }

        return $flattened;
    }
}
