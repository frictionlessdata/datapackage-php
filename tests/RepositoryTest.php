<?php
namespace frictionlessdata\datapackage\tests;

use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Repository;

class RepositoryTest extends TestCase
{

    public function testDatapackageWithoutProfile()
    {
        $this->assertDatapackageClassProfile(
            "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage",
            "data-package",
            (object)[]
        );
    }

    public function testDatapackageWithDefaultProfile()
    {
        $this->assertDatapackageClassProfile(
            "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage",
            "data-package",
            (object)["profile" => "default"]
        );
    }

    public function testDatapackageWithDataPackageProfile()
    {
        $this->assertDatapackageClassProfile(
            "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage",
            "data-package",
            (object)["profile" => "data-package"]
        );
    }

    public function testDatapackageWithTabularDataPackageProfile()
    {
        $this->assertDatapackageClassProfile(
            "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage",
            "tabular-data-package",
            (object)["profile" => "tabular-data-package"]
        );
    }

    public function testResourceWithoutProfile()
    {
        $this->assertResourceClassProfile(
            "frictionlessdata\\datapackage\\Resources\\DefaultResource",
            "data-resource",
            (object)[]
        );
    }

    public function testResourceWithDefaultProfile()
    {
        $this->assertResourceClassProfile(
            "frictionlessdata\\datapackage\\Resources\\DefaultResource",
            "data-resource",
            (object)["profile" => "default"]
        );
    }

    public function testResourceWithDataResourceProfile()
    {
        $this->assertResourceClassProfile(
            "frictionlessdata\\datapackage\\Resources\\DefaultResource",
            "data-resource",
            (object)["profile" => "data-resource"]
        );
    }

    public function testResourceWithTabularDataResourceProfile()
    {
        $this->assertResourceClassProfile(
            "frictionlessdata\\datapackage\\Resources\\TabularResource",
            "tabular-data-resource",
            (object)["profile" => "tabular-data-resource"]
        );
    }

    public function testValidate()
    {

    }

    protected function assertDatapackageClassProfile($expectedClass, $expectedProfile, $descriptor)
    {
        $this->assertEquals($expectedClass, Repository::getDatapackageClass($descriptor));
        $this->assertEquals($expectedProfile, Repository::getDatapackageValidationProfile($descriptor));
    }

    protected function assertResourceClassProfile($expectedClass, $expectedProfile, $descriptor)
    {
        $this->assertEquals($expectedClass, Repository::getResourceClass($descriptor));
        $this->assertEquals($expectedProfile, Repository::getResourceValidationProfile($descriptor));
    }

}
