<?php
namespace frictionlessdata\datapackage\tests;

use frictionlessdata\datapackage\Validators\DatapackageValidationError;
use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Datapackages\DefaultDatapackage;
use frictionlessdata\datapackage\Exceptions;
use frictionlessdata\datapackage\Factory;

class DatapackageTest extends TestCase
{
    public $simpleDescriptorArray;
    public $simpleDescriptor;
    public $simpleDescriptorExpectedData;
    public $fixturesPath;

    public function setUp()
    {
        $this->simpleDescriptorArray = [
            "name" => "datapackage-name",
            "resources" => [
                ["name" => "resource-name", "data" => ["foo.txt"] ]
            ]
        ];
        $this->simpleDescriptor = (object)[
            "name" => "datapackage-name",
            "resources" => [
                (object)["name" => "resource-name", "data" => ["foo.txt"] ]
            ]
        ];
        $this->simpleDescriptorExpectedData = ["resource-name" => [["foo"]]];
        $this->fixturesPath = dirname(__FILE__)."/fixtures";
    }

    public function testNativePHPArrayShouldFail()
    {
        $descriptorArray = $this->simpleDescriptorArray;
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DatapackageInvalidSourceException",
            function() use ($descriptorArray) { Factory::datapackage($descriptorArray); }
        );
    }

    public function testNativePHPObjectWithoutBasePathShouldFail()
    {
        $descriptor = $this->simpleDescriptor;
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DataStreamOpenException",
            function() use ($descriptor) { Factory::datapackage($descriptor); }
        );
    }

    public function testNativePHPObjectWithBasePath()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Factory::datapackage($this->simpleDescriptor, $this->fixturesPath)
        );
    }

    public function testJsonStringWithoutBasePathShouldFail()
    {
        $source = json_encode($this->simpleDescriptor);
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DataStreamOpenException",
            function() use ($source) { Factory::datapackage($source); }
        );
    }

    public function testJsonStringWithBasePath()
    {
        $source = json_encode($this->simpleDescriptor);
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Factory::datapackage($source, $this->fixturesPath)
        );
    }

    public function testNonExistantFileShouldFail()
    {
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DatapackageInvalidSourceException",
            function() { Factory::datapackage("-invalid-"); }
        );
    }

    public function testJsonFileRelativeToBasePath()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Factory::datapackage("simple_valid_datapackage.json", $this->fixturesPath)
        );
    }

    public function testJsonFileRelativeToCurrentDirectory()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Factory::datapackage("tests/fixtures/simple_valid_datapackage.json")
        );
    }

    public function testHttpSource()
    {
        $this->assertDatapackage(
            (object)[
                "name" => "datapackage-name",
                "resources" => [
                    (object)[
                        "name" => "resource-name",
                        "data" => ["mock-http://foo.txt", "mock-http://foo.txt"]
                    ]
                ]
            ], ["resource-name" => [["foo"], ["foo"]]],
            Mocks\MockFactory::datapackage("mock-http://simple_valid_datapackage_mock_http_data.json")
        );
    }

    public function testMultiDataDatapackage()
    {
        $out = [];
        $datapackage = Factory::datapackage("tests/fixtures/multi_data_datapackage.json");
        foreach ($datapackage as $resource) {
            $out[] = "-- ".$resource->name()." --";
            $i = 0;
            foreach ($resource as $dataStream) {
                $out[] = "-dataStream ".++$i;
                foreach ($dataStream as $line) {
                    $out[] = $line;
                }
            }
        }
        $this->assertEquals([
            "-- first-resource --",
            "-dataStream 1",
            "foo",
            "-dataStream 2",
            "BAR!\n",
            "bar\n",
            "בר\n",
            "",
            "-dataStream 3",
            "בזבזבז\n",
            "זבזבזב",
            "-- second-resource --",
            "-dataStream 1",
            "BAR!\n",
            "bar\n",
            "בר\n",
            "",
            "-dataStream 2",
            "בזבזבז\n",
            "זבזבזב",
            "-- third-resource --",
            "-dataStream 1",
            "בזבזבז\n",
            "זבזבזב",
        ], $out);
    }

    public function testDatapackageValidation()
    {
        $this->assertEquals([], Factory::validate("tests/fixtures/multi_data_datapackage.json"));
    }

    public function testDatapackageValidationFailed()
    {
        $this->assertDatapackageValidation(
            "[resources] The property resources is required",
            "tests/fixtures/simple_invalid_datapackage.json"
        );
    }

    public function testDatapackageValidationFailedShouldPreventConstruct()
    {
        try {
            Factory::datapackage((object)["name" => "foobar"]);
            $caughtException = null;
        } catch (Exceptions\DatapackageValidationFailedException $e) {
            $caughtException = $e;
        }
        $this->assertEquals("DefaultDatapackage validation failed: [resources] The property resources is required", $caughtException->getMessage());
    }

    public function testTabularResourceDescriptorValidation()
    {
        $this->assertDatapackageValidation(
            "DefaultResource 1 failed validation: [resources[0].schema.fields] The property fields is required",
            "tests/fixtures/invalid_tabular_resource.json"
        );
    }

    protected function assertDatapackageValidation($expectedMessages, $source, $basePath=null)
    {
        $validationErrors = Factory::validate($source, $basePath);
        $this->assertEquals(
            $expectedMessages,
            DatapackageValidationError::getErrorMessages($validationErrors)
        );
    }

    /**
     * @param object $expectedDescriptor
     * @param DefaultDatapackage $datapackage
     */
    protected function assertDatapackageDescriptor($expectedDescriptor, $datapackage)
    {
        $this->assertEquals($expectedDescriptor, $datapackage->descriptor());
    }

    /**
     * @param array $expectedData
     * @param DefaultDatapackage $datapackage
     */
    protected function assertDatapackageData($expectedData, $datapackage)
    {
        $allResourcesData = [];
        foreach ($datapackage as $resource) {
            $resourceData = [];
            foreach ($resource as $dataStream) {
                $data = [];
                foreach ($dataStream as $line) {
                    $data[] = $line;
                }
                $resourceData[] = $data;
            }
            $allResourcesData[$resource->name()] = $resourceData;
        }
        $this->assertEquals($expectedData, $allResourcesData);
    }

    /**
     * @param string $source
     * @param object $expectedDescriptor
     * @param array $expectedData
     */
    protected function assertDatapackage($expectedDescriptor, $expectedData, $datapackage)
    {
        $this->assertDatapackageDescriptor($expectedDescriptor, $datapackage);
        $this->assertDatapackageData($expectedData, $datapackage);
    }

    protected function assertDatapackageException($expectedExceptionClass, $datapackageCallback)
    {
        try {
            $datapackageCallback();
        } catch (\Exception $e) {
            $this->assertEquals($expectedExceptionClass, get_class($e), $e->getMessage());
        }
    }
}
