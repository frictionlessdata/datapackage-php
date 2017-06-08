# Data Package

[![Travis](https://travis-ci.org/frictionlessdata/datapackage-php.svg?branch=master)](https://travis-ci.org/frictionlessdata/datapackage-php)
[![Coveralls](http://img.shields.io/coveralls/frictionlessdata/datapackage-php.svg?branch=master)](https://coveralls.io/r/frictionlessdata/datapackage-php?branch=master)
[![Scrutinizer-ci](https://scrutinizer-ci.com/g/OriHoch/datapackage-php/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/OriHoch/datapackage-php/)
[![Packagist](https://img.shields.io/packagist/dm/frictionlessdata/datapackage.svg)](https://packagist.org/packages/frictionlessdata/datapackage)
[![SemVer](https://img.shields.io/badge/versions-SemVer-brightgreen.svg)](http://semver.org/)
[![Gitter](https://img.shields.io/gitter/room/frictionlessdata/chat.svg)](https://gitter.im/frictionlessdata/chat)

A utility library for working with [Data Package](https://specs.frictionlessdata.io/data-package/) in PHP.


## Getting Started

### Installation

```bash
$ composer require frictionlessdata/datapackage
```

### Usage

```php
use frictionlessdata\datapackage;

// get a datapackage object
$datapackage = datapackage\Factory::datapackage("tests/fixtures/multi_data_datapackage.json");

// iterate over the data - it will raise exceptions in case of any problems
foreach ($datapackage as $resource) {
    print("-- ".$resource->name()." --");
    $i = 0;
    foreach ($resource as $dataStream) {
        print("-dataStream ".++$i);
        foreach ($dataStream as $line) {
            print($line);
        }
    }
}

// validate a datapackage descriptor
$validationErrors = datapackage\Factory::validate("tests/fixtures/simple_invalid_datapackage.json");
if (count($validationErrors) == 0) {
    print("descriptor is valid");
} else {
    print(datapackage\Validators\DatapackageValidationError::getErrorMessages($validationErrors));
}

// get and manipulate resources
$resources = $datapackage->resources();
$resources["resource-name"]->name() == "resource-name"
$resources["another-resource-name"] //  BaseResource based object (e.g. DefaultResource / TabularResource)

// get a single resource by name
$datapackage->resource("resource-name")

// delete a resource by name - will raise exception in case of validation failure for the new descriptor
$datapackage->deleteResource("resource-name");

// add a resource - will raise exception in case of validation error for the new descriptor
$resource = Factory::resource((object)[
    "name" => "new-resource", "data" => ["tests/fixtures/foo.txt", "tests/fixtures/baz.txt"]
])
$datapackage->addResource($resource);

// register custom datapackage classes which can override / extend core classes
Factory::registerDatapackageClass("my\\custom\\DatapackageClass");

// register custom profiles and related schema (must conform to the default datapackage schema as well)
Registry::registerSchema("my-custom-profile-id", "path/to/my-custom-profile.schema.json");
```


## Contributing

Please read the contribution guidelines: [How to Contribute](CONTRIBUTING.md)
