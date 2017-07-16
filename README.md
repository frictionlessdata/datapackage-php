# Data Package

[![Travis](https://travis-ci.org/frictionlessdata/datapackage-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/datapackage-php)
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/datapackage-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/datapackage-php?branch=master)
[![Scrutinizer-ci](https://scrutinizer-ci.com/g/OriHoch/datapackage-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/OriHoch/datapackage-php/)
[![Packagist](https://img.shields.io/packagist/dm/frictionlessdata/datapackage.svg)](https://packagist.org/packages/frictionlessdata/datapackage)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
[![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Data Package](https://specs.frictionlessdata.io/data-package/) in PHP.

## Features summary and Usage guide

### Installation

```bash
$ composer require frictionlessdata/datapackage
```

### Package

Load a data package conforming to the specs

```php
use frictionlessdata\datapackage\Package;
$package = Package::load("tests/fixtures/multi_data_datapackage.json");
```

Iterate over the resources and the data.

```php
foreach ($datapackage as $resource) {
    $resource->name(); // "first-resource"
    foreach ($resource as $dataStream) {
        foreach ($dataStream as $line) {
            $line;  // ["key"=>"value", .. ]
        }
    }
}
```

All data and schemas are validated and throws exceptions in case of any problems.

You can also validate the data explicitly and get a list of errors

```php
Package::validate("tests/fixtures/simple_invalid_datapackage.json");  // array of validation errors
```

The datapackage object has some useful methods to access and manipulate the resources

```php
$package = Package::load("tests/fixtures/multi_data_datapackage.json");
$package->resources();  // array of resource name => Resource object (see below for Resource class reference)
$package->resoure("resource-name");  // Resource object
$package->deleteResource("resource-name");
$package->resource("resource-name", ["path" => [
    "tests/fixtures/foo.txt", 
    "tests/fixtures/baz.txt"
]]);  // adds or replace resource
```

### Additional functionality

```php
// register custom datapackage or resource classes which can override / extend core classes
// these custom classes run a test against the schema to decide whether to handle a given descriptor or not
Factory::registerDatapackageClass("my\\custom\\DatapackageClass");
Factory::registerResourceClass("my\\custom\\ResourceClass");

// register custom profiles and related schemas for validation
Registry::registerSchema("my-custom-profile-id", "path/to/my-custom-profile.schema.json");

// create a new datapackage from scratch
$datapackage = TabularDatapackage::create("my-tabular-datapackage", [
    TabularResource::create("my-tabular-resource")
]);

// set the tabular data schema
$datapackage->resource("my-tabular-resource")->descriptor()->schema = (object)[
    "fields" => [
        (object)["name" => "id", "type" => "integer"],
        (object)["name" => "data", "type" => "string"],
    ]
];

// add data files
$datapackage->resource("my-tabular-resource")->descriptor()->path[] = "/path/to/file-1.csv";
$datapackage->resource("my-tabular-resource")->descriptor()->path[] = "/path/to/file-2.csv";

// re-validate the new descriptor
$datapackage->revalidate();

// save the datapackage descriptor to a file
$datapackage->saveDescriptor("datapackage.json");


// get and manipulate resources
$resource->name() == "resource-name"
$resource //  BaseResource based object (e.g. DefaultResource / TabularResource)
```


## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
