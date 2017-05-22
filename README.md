# theiconic/config

General purpose config manager for file-based configuration.

## Setup
```$xslt
composer require theiconic/name-parser
```

## Concepts

### Spaces
Configuration is grouped into configuration spaces.
Spaces are managed by the configuration factory.

### Multi-file & multi-section configuration
A configuration space can read configuration from several
files. The configuration is merged and then flattened by
section.

### Placeholders
During parsing, pre-defined placeholders will be replaced
with the configured replacement values.

### Example
So the basic setup looks something like this (modified example based on alice):
```
// get the factory instance
$factory = Factory::getInstance();

// set the current environment
$factory->setEnvironment(APPLICATION_ENV);

// configure cache path
$factory->setCachePath(sprintf('%s/config', APPLICATION_TMP));

// instanciate and configure the application config space
$factory->getSpace('application')
    ->setPaths([
        '/etc/iconic.ini',
        $basePath . 'application.ini',
        $basePath . 'application.local.ini',
    ])
    ->setSections([
        'default',
        'production',
    ])
    ->setPlaceholders([
        '%APPLICATION_ENV%' => APPLICATION_ENV,
        '%APPLICATION_ROOT%' => APPLICATION_ROOT,
        '%APPLICATION_TMP%' => APPLICATION_TMP,
    ]);

```

## Multi-section config format

Configuration files must contain configuration sections.
This is the sections in .ini files and the first level array/object
items in .php or .json config files.

No sections are pre-selected by default. You will need to explicitly
state the sections in code, like so:
```$php
$factory->getSpace('myConfig')
    ->setSections(['main', 'development', 'testing']);
```

Sections will be merged in the order specified, i.e. entries in later
sections will override those in earlier sections.

## Caching

All configuration is parsed into multidimensional PHP arrays.
The arrays are then stored in cache files so that expensive
parsing is bypassed.

Cache keys are determined based on
- the list of source files names
- the list on section names.

Cache is automatically validated based on file modification timestamps.
Hence, the cache will automatically update itself whenever
any of the source configuration files changes.

## Parsing

Extendable parsers are used to parse different file formats.
Currently implemented parsers are:
* Ini (for .ini files)
* Json (for .json files)
* Php (for .php files)
* Autodetect (automatically picks the right parser based on extension)
* Dummy (for unit tests etc.)

## Accessing configuration values

Configuration can be accessed via dot-paths.
These paths are dynamically resolved against the internal
array-representation of configuration.
This allows retrieving individual entries as well as
collections of entries.

```
Factory::getInstance()->getSpace('application')->get('redis.retries', 3);
Factory::getInstance()->getSpace('application')->get('redis.hosts', 'localhost');
 
Factory::getInstance()->getSpace('application')->get('redis', ['retries': 3, 'hosts': 'localhost]);
```

You can also retrieve the configuration as a flat array of
dot-path to value mappings:

```
Factory::getInstance()->getSpace('application')->flatten();
```

## Using environment variables

There is no explicit functionality to handle environment variables, however
they can be dynamically used in the configurations via the placeholders mechanism:
```
$space = Factory::getInstance()->getSpace('application');
foreach ($_ENV as $key => $value) {
    $placeholder = sprintf('%%ENV_%s%%', strtoupper(str_replace('%', '_', $key)));
    $space->addPlaceholder($placeholder, $value);
}
```
With these few lines in place, a configuration file could look like this:
```
[main]
user.name = %ENV_USER%
user.home = %ENV_HOME%
```

## License

THE ICONIC Name Parser library for PHP is released under the MIT License.