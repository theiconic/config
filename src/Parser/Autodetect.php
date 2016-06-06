<?php

namespace TheIconic\Config\Parser;

use TheIconic\Config\Exception\ParserException;

/**
 * auto-detecting parser - invokes specific parser based on config file extension
 *
 * @package Shared\Helper\Config\Parser
 */
class Autodetect extends AbstractParser
{

    /**
     * invokes specific parser based on config file extension
     *
     * @param string $file
     * @return array
     * @throws Exception
     */
    public function parse($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $className = __NAMESPACE__ . '\\' . sprintf(ucfirst(strtolower($extension)));

        if (!class_exists($className)) {
            throw new ParserException(sprintf('No parser found for config file with extension \'%s\'', $extension));
        }

        /** @var AbstractParser $parser */
        $parser = new $className();

        return $parser->parse($file);
    }

}
