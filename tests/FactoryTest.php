<?php

namespace frictionlessdata\datapackage\tests;

use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Factory;

class FactoryTest extends TestCase
{
    public function testRegisterDatapackageClass()
    {
        // the MyCustomDatapackage datapackage class checks for a myCustomDatapackage property on the descriptor
        // it is only used when this attribute exists
        Factory::registerDatapackageClass(
            'frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomDatapackage'
        );
        // descriptor without the myCustomDatapackage property
        $descriptor = (object) [
            'name' => 'my-custom-datapackage',
            'resources' => [
                (object) ['name' => 'my-custom-resource', 'data' => ['tests/fixtures/foo.txt']],
            ],
        ];
        // get a datapackage object based on this descriptor
        $datapackage = Factory::datapackage($descriptor);
        // the custom datapackage is not used (because the myCustomDatapackage property didn't exist)
        $this->assertEquals(
            'frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage',
            get_class($datapackage)
        );
        // add the myCustomDatapackage property to the descriptor
        $descriptor->myCustomDatapackage = true;
        // get a datapackage object
        $datapackage = Factory::datapackage($descriptor);
        // voila - we got a MyCustomDatapackage class
        $this->assertEquals(
            'frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomDatapackage',
            get_class($datapackage)
        );
        // make sure to clear the custom datapackage class we registered
        Factory::clearRegisteredDatapackageClasses();
        // create a datapackage object from the descriptor with the myCustomDatapackage property
        $datapackage = Factory::datapackage($descriptor);
        // got the normal default datapackage class (because we cleared the custom registered classes)
        $this->assertEquals(
            'frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage',
            get_class($datapackage)
        );
    }

    public function testRegisterResourceClass()
    {
        // register a custom resource class which is used for resources that have the goGoPowerRangers property
        Factory::registerResourceClass(
            'frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomResource'
        );
        // a descriptor without the goGoPowerRangers property
        $descriptor = (object) ['name' => 'my-custom-resource', 'data' => ['tests/fixtures/foo.txt']];
        // create a resource object based on the descriptor
        $resource = Factory::resource($descriptor);
        // got a normal resource
        $this->assertEquals(
            "frictionlessdata\\datapackage\\Resources\DefaultResource",
            get_class($resource)
        );
        // add the goGoPowerRangers property
        $descriptor->goGoPowerRangers = true;
        $resource = Factory::resource($descriptor);
        // got the custom resource
        $this->assertEquals(
            'frictionlessdata\\datapackage\\tests\\Mocks\\MyCustomResource',
            get_class($resource)
        );
        // clear the registered classes and ensure it's cleared
        Factory::clearRegisteredResourceClasses();
        $resource = Factory::resource($descriptor);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\Resources\DefaultResource",
            get_class($resource)
        );
    }
}
