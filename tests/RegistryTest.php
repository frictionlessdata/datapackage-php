<?php
namespace frictionlessdata\datapackage\tests;

use frictionlessdata\datapackage\Exceptions\DatapackageValidationFailedException;
use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Registry;
use frictionlessdata\datapackage\Factory;

class RegistryTest extends TestCase
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

    public function testCustomProfileFromJsonSchemaFile()
    {
        $descriptor = (object)[
            "name" => "custom-datapackage",
            "profile" => "test-custom-profile.schema.json",
            "resources" => [
                (object)[
                    "name" => "custom-resource",
                    "profile" => "test-custom-resource-profile.schema.json",
                    "data" => ["foo.txt"]
                ]
            ]
        ];
        try {
            Factory::datapackage($descriptor, "tests/fixtures");
            $this->fail();
        } catch (DatapackageValidationFailedException $e) {
            $this->assertEquals(
                "DefaultDatapackage validation failed: [custom] The property custom is required",
                $e->getMessage()
            );
        }
        $descriptor->resources[0]->custom = [1,2,3];
        try {
            Factory::datapackage($descriptor, "tests/fixtures");
            $this->fail();
        } catch (DatapackageValidationFailedException $e) {
            $this->assertEquals(
                "DefaultDatapackage validation failed: [custom[0]] Integer value found, but a string is required, [custom[1]] Integer value found, but a string is required, [custom[2]] Integer value found, but a string is required",
                $e->getMessage()
            );
        }
        $descriptor->resources[0]->custom = ["1","2","3"];
        $descriptor->foobar = "";
        try {
            Factory::datapackage($descriptor, "tests/fixtures");
            $this->fail("should raise an exception because test-custom-profile requires foobar attribute (array of strings)");
        } catch (DatapackageValidationFailedException $e) {
            $this->assertEquals(
                "DefaultDatapackage validation failed: [foobar] String value found, but an array is required",
                $e->getMessage()
            );
        }
        $descriptor->foobar = ["1","2","3"];
        $datapackage = Factory::datapackage($descriptor, "tests/fixtures");
        $this->assertEquals((object)[
            "name" => "custom-datapackage",
            "profile" => "test-custom-profile.schema.json",
            "foobar" => ["1", "2", "3"],
            "resources" => [
                (object)[
                    "name" => "custom-resource",
                    "profile" => "test-custom-resource-profile.schema.json",
                    "data" => ["foo.txt"],
                    "custom" => ["1", "2", "3"]
                ]
            ]
        ], $datapackage->descriptor());
    }

    protected function assertDatapackageClassProfile($expectedClass, $expectedProfile, $descriptor)
    {
        $this->assertEquals($expectedClass, Registry::getDatapackageClass($descriptor));
        $this->assertEquals($expectedProfile, Registry::getDatapackageValidationProfile($descriptor));
    }

    protected function assertResourceClassProfile($expectedClass, $expectedProfile, $descriptor)
    {
        $this->assertEquals($expectedClass, Registry::getResourceClass($descriptor));
        $this->assertEquals($expectedProfile, Registry::getResourceValidationProfile($descriptor));
    }

}
