<?php
namespace frictionlessdata\datapackage\tests;

use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Factory;

class FactoryTest extends TestCase
{
    public function testRegisterDatapackageClass()
    {
        Factory::registerDatapackageClass(
            "frictionlessdata\\datapackage\\tests\\Mocks\\MockDefaultDatapackage"
        );
        $datapackage = Factory::datapackage((object)[
            "name" => "my-custom-datapackage",
            "myCustomDatapackage" => true,
            "resources" => [
                (object)["name" => "my-custom-resource", "data" => ["tests/fixtures/foo.txt"]]
            ]
        ]);
        $this->assertEquals(
            "frictionlessdata\\datapackage\\tests\\Mocks\\MockDefaultDatapackage",
            get_class($datapackage)
        );
        Factory::clearRegisteredDatapackageClasses();
    }
}
