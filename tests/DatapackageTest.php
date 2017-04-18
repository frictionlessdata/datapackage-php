<?php
namespace frictionlessdata\datapackage\tests;

use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Datapackage;

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
            function() use ($descriptorArray) { new Datapackage($descriptorArray); }
        );
    }

    public function testNativePHPObjectWithoutBasePathShouldFail()
    {
        $descriptor = $this->simpleDescriptor;
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DataStreamOpenException",
            function() use ($descriptor) { new Datapackage($descriptor); }
        );
    }

    public function testNativePHPObjectWithBasePath()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            new Datapackage($this->simpleDescriptor, $this->fixturesPath)
        );
    }

    public function testJsonStringWithoutBasePathShouldFail()
    {
        $source = json_encode($this->simpleDescriptor);
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DataStreamOpenException",
            function() use ($source) { new Datapackage($source); }
        );
    }

    public function testJsonStringWithBasePath()
    {
        $source = json_encode($this->simpleDescriptor);
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            new Datapackage($source, $this->fixturesPath)
        );
    }

    public function testNonExistantFileShouldFail()
    {
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DatapackageInvalidSourceException",
            function() { new Datapackage("-invalid-"); }
        );
    }

    public function testJsonFileRelativeToBasePath()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            new Datapackage("simple_valid_datapackage.json", $this->fixturesPath)
        );
    }

    public function testJsonFileRelativeToCurrentDirectory()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            new Datapackage("tests/fixtures/simple_valid_datapackage.json")
        );
    }

    public function testHttpSource()
    {
        $this->assertDatapackage(
            (object)[
                "name" => "datapackage-name",
                "resources" => [
                    (object)["name" => "resource-name", "data" => [] ]
                ]
            ], ["resource-name" => []],
            new Mocks\MockDatapackage("mock-http://simple_valid_datapackage_no_data.json")
        );
    }

    public function testMultiDataDatapackage()
    {
        $out = [];
        $datapackage = new Datapackage("tests/fixtures/multi_data_datapackage.json");
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

    /**
     * @param object $expectedDescriptor
     * @param Datapackage $datapackage
     */
    protected function assertDatapackageDescriptor($expectedDescriptor, $datapackage)
    {
        $this->assertEquals($expectedDescriptor, $datapackage->descriptor());
    }

    /**
     * @param array $expectedData
     * @param Datapackage $datapackage
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
