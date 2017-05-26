<?php

namespace TheIconic\Config\Parser;

use TheIconic\Config\Exception\ParserException;
use Throwable;

/**
 * config file parser for ini files
 *
 * @package Shared\Helper\Config\Parser
 */
class Ini extends AbstractParser
{

    /**
     * parses an ini config file into a config array
     *
     * @param string $file
     * @return array
     */
    public function parse($file)
    {
        try {
            $config = parse_ini_string(file_get_contents($file), true);

            if (false === $config) {
                throw new ParserException(sprintf('Couldn\'t parse config file %s', $file));
            }
        } catch (Throwable $e) {
            throw new ParserException(sprintf('Couldn\'t parse config file %s', $file));
        }

        return $this->expand($config);
    }

    /**
     * @param $config
     * @return array
     */
    protected function expand($config)
    {
        $expanded = [];

        foreach ($config as $key => $value) {
            $segments = explode('.', $key);

            $tmp =& $expanded;

            while ($segment = array_shift($segments)) {
                if (!isset($tmp[$segment])) {
                    $tmp[$segment] = [];
                }
                $tmp =& $tmp[$segment];
            }

            $tmp = $this->expandValue($value);
        }

        return $expanded;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function expandValue($value)
    {
        if (is_array($value)) {
            return $this->expand($value);
        }

        if (is_string($value) && in_array($value, ['true', 'false'])) {
            return ($value !== 'false');
        }

        return $value;
    }
}
