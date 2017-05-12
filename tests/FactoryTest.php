<?php
namespace frictionlessdata\datapackage\tests;

use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Factory;

class FactoryTest extends TestCase
{
    public function testRegisterDatapackageClass()
    {
        $descriptor = (object)[
            "name" => "my-custom-datapackage",
            "resources" => [
                (object)["name" => "my-custom-resource", "data" => ["tests/fixtures/foo.txt"]]
            ]
        ];
        Factory::registerDatapackageClass(
            "frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomDatapackage"
        );
        // descriptor without the custom property
        $datapackage = Factory::datapackage($descriptor);
        $this->assertEquals(
            // custom datapackage is not used
            "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage",
            get_class($datapackage)
        );
        // add the custom property - which is checked by MyCustomDatapackage
        $descriptor->myCustomDatapackage = true;
        $datapackage = Factory::datapackage($descriptor);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomDatapackage",
            get_class($datapackage)
        );
        Factory::clearRegisteredDatapackageClasses();
        $datapackage = Factory::datapackage($descriptor);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage",
            get_class($datapackage)
        );
    }

    public function testRegisterResourceClass()
    {
        $descriptor = (object)["name" => "my-custom-resource", "data" => ["tests/fixtures/foo.txt"]];
        Factory::registerResourceClass(
            "frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomResource"
        );
        $resource = Factory::resource($descriptor);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\Resources\DefaultResource",
            get_class($resource)
        );
        $descriptor->goGoPowerRangers = true;
        $resource = Factory::resource($descriptor);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomResource",
            get_class($resource)
        );
        Factory::clearRegisteredResourceClasses();
        $resource = Factory::resource($descriptor);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\Resources\DefaultResource",
            get_class($resource)
        );
    }
}
