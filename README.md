# theiconic/config

General purpose config manager for file-based configuration.

## Concepts

### Spaces
Configuration is grouped into configuration spaces.
Spaces are managed by the configuration factory.

### Multi-file & multi-environment configuration
A configuration space can read configuration from several
files. The configuration is merged and then flattened by
environment - unless the space is flagged as non-environmental.

Multi-environment configuration can be disabled per space:
```
$configSpace->useEnvironment(false);
```

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
    ->setPlaceholders([
        '%APPLICATION_ENV%' => APPLICATION_ENV,
        '%APPLICATION_ROOT%' => APPLICATION_ROOT,
        '%APPLICATION_TMP%' => APPLICATION_TMP,
    ]);

```

## Multi-environment config format

The main section of the configuration files has to be the
environment. This is the sections in .ini files and the
first level array items in .php config files.

The 'all' section (if present) is used as the common foundation
for all environments and per-environment values are merged on top
of the values in all. The merge is recursive.

## Caching

All configuration is parsed into multidimensional PHP arrays.
The arrays are then split by environment and stored in cache files
so that expensive parsing is bypassed.

Cache is validated based on file modification timestamps.
Hence, the cache will automatically update itself whenever
any of the source configuration files changes.

## Parsing

Extendable parsers are used to parse different file formats.
Currently implemented parsers are:
* Ini (for .ini files)
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