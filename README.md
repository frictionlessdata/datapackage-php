# Data Package

[![Travis](https://travis-ci.org/frictionlessdata/datapackage-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/datapackage-php)
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/datapackage-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/datapackage-php?branch=master)
[![Scrutinizer-ci](https://scrutinizer-ci.com/g/frictionlessdata/datapackage-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/frictionlessdata/datapackage-php/)
[![Packagist](https://img.shields.io/packagist/dm/frictionlessdata/datapackage.svg)](https://packagist.org/packages/frictionlessdata/datapackage)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
[![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Data Package](https://specs.frictionlessdata.io/data-package/) in PHP.


## Features summary and Usage guide

### Installation

```bash
composer require frictionlessdata/datapackage
```

Optionally, to create zip files you will need the PHP zip extension. On ubuntu it can be enabled with `sudo apt-get install php-zip`

### Package

Load a data package conforming to the specs

```php
use frictionlessdata\datapackage\Package;
$package = Package::load("tests/fixtures/multi_data_datapackage.json");
```

Iterate over the resources and the data

```php
foreach ($package as $resource) {
    echo $resource->name();
    foreach ($resource as $row) {
        echo $row;
    }
}
```

Get all the data as an array (loads all the data into memory, not recommended for large data sets)

```php
foreach ($package as $resource) {
    var_dump($resource->read());
}
```

All data and schemas are validated and throws exceptions in case of any problems.

Validate the data explicitly and get a list of errors

```php
Package::validate("tests/fixtures/simple_invalid_datapackage.json");  // array of validation errors
```

Load a zip file

```php
$package = Package::load('http://datahub.io/opendatafortaxjustice/eucountrydatawb/r/datapackage_zip.zip');
```

Provide read options which are passed through to [tableschema-php](https://github.com/frictionlessdata/tableschema-php) Table::read method

```php
$package = Package::load('http://datahub.io/opendatafortaxjustice/eucountrydatawb/r/datapackage_zip.zip');
foreach ($package as $resource) {
    $resource->read(["cast" => false]);
}
```

The package object has some useful methods to access and manipulate the resources

```php
$package = Package::load("tests/fixtures/multi_data_datapackage.json");
$package->resources();  // array of resource name => Resource object (see below for Resource class reference)
$package->resource("first-resource");  // Resource object matching the given name
$package->deleteResource("first-resource");
// add a tabular resource
$package->resource("tabular-resource-name", [
    "profile" => "tabular-data-resource",
    "schema" => [
        "fields" => [
            ["name" => "id", "type" => "integer"],
            ["name" => "name", "type" => "string"]
        ]
    ],
    "path" => [
        "tests/fixtures/simple_tabular_data.csv",
    ]
]);
```

Create a new package from scratch

```php
$package = Package::create([
    "name" => "datapackage-name",
    "profile" => "tabular-data-package"
]);
// add a resource
$package->resource("resource-name", [
    "profile" => "tabular-data-resource", 
    "schema" => [
        "fields" => [
            ["name" => "id", "type" => "integer"],
            ["name" => "name", "type" => "string"]
        ]
    ],
    "path" => "tests/fixtures/simple_tabular_data.csv"
]);
// save the package descriptor to a file
$package->saveDescriptor("datapackage.json");
```

Save the entire datapackage including any local data to a zip file

```php
$package->save("datapackage.zip");
```

### Resource

Resource objects can be accessed from a Package as described above

```php
$resource = $package->resource("resource-name")
```

or instantiated directly

```php
use frictionlessdata\datapackage\Resource;
$resource = Resource::create([
    "name" => "my-resource",
    "profile" => "tabular-data-resource",
    "path" => "tests/fixtures/simple_tabular_data.csv",
    "schema" => ["fields" => [["name" => "id", "type" => "integer"], ["name" => "name", "type" => "string"]]]
]);
```

Iterating or reading over the resource produces combined rows from all the path or data elements

```php
foreach ($resource as $row) {};  // iterating
$resource->read();  // get all the data as an array
```


## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
