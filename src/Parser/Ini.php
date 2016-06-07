<?php

namespace TheIconic\Config\Parser;

use TheIconic\Config\Exception\ParserException;

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
        $config = parse_ini_string(file_get_contents($file), true);

        if (false === $config) {
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

            if (is_array($value)) {
                $tmp = $this->expand($value);
            } else if (is_string($value) && in_array($value, ['true', 'false'])) {
                $tmp = ($value === 'false') ? false : true;
            } else {
                $tmp = $value;
            }
        }

        return $expanded;
    }

}
