<?php
namespace frictionlessdata\datapackage\tests;

use frictionlessdata\datapackage\Validators\DatapackageValidationError;
use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Datapackages\DefaultDatapackage;
use frictionlessdata\datapackage\Exceptions;
use frictionlessdata\datapackage\Factory;
use frictionlessdata\tableschema\InferSchema;
use frictionlessdata\tableschema\Table;
use frictionlessdata\tableschema\DataSources\CsvDataSource;

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
            'Invalid source: {"name":"datapackage-name","resources":[{"name":"resource-name","data":["foo.txt"]}]}',
            function() use ($descriptorArray) { Factory::datapackage($descriptorArray); }
        );
    }

    public function testNativePHPObjectWithoutBasePathShouldFail()
    {
        $descriptor = $this->simpleDescriptor;
        $this->assertDatapackageException(
            "frictionlessdata\\datapackage\\Exceptions\\DatapackageValidationFailedException",
            'Datapackage validation failed: data source file does not exist or is not readable: foo.txt',
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
            "frictionlessdata\\datapackage\\Exceptions\\DatapackageValidationFailedException",
            'Datapackage validation failed: data source file does not exist or is not readable: foo.txt',
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
            'Failed to load source: "-invalid-": '.$this->getFileGetContentsErrorMessage("-invalid-"),
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
        $this->assertEquals("Datapackage validation failed: [resources] The property resources is required", $caughtException->getMessage());
    }

    public function testTabularResourceDescriptorValidation()
    {
        $this->assertDatapackageValidation(
            "[schema.fields] The property fields is required",
            "tests/fixtures/invalid_tabular_resource.json"
        );
    }

    public function testDefaultResourceInvalidData()
    {
        $this->assertDatapackageValidation(
            'data source file does not exist or is not readable: --invalid--',
            "tests/fixtures/default_resource_invalid_data.json"
        );
    }

    public function testTabularResourceInvalidData()
    {
        $this->assertDatapackageValidation(
            'resource 1, data stream 2: email: value is not a valid email (bad.email)',
            "tests/fixtures/tabular_resource_invalid_data.json"
        );
    }

    public function testDatapackageResources()
    {
        // prepare the desriptor, schema and datapackage
        $dataSource = new CsvDataSource("tests/fixtures/simple_tabular_data.csv");
        $schema = new InferSchema();
        Table::validate($dataSource, $schema, 1);
        $descriptor = (object)[
            "name" => "datapackage-name",
            "resources" => [
                (object)[
                    "name" => "resource-name", "data" => ["foo.txt", "baz.txt"]
                ],
                (object)[
                    "name" => "another-resource-name",
                    "profile" => "tabular-data-resource",
                    "data" => ["simple_tabular_data.csv"],
                    "schema" => $schema->fullDescriptor()
                ],
            ]
        ];
        $basePath = "tests/fixtures";
        $datapackage = new DefaultDatapackage($descriptor, $basePath);
        // test accessing resources
        $resources = $datapackage->resources();
        $this->assertTrue(is_a(
            $resources["resource-name"],
            "frictionlessdata\\datapackage\\Resources\\DefaultResource"
        ));
        $this->assertTrue(is_a(
            $resources["another-resource-name"],
            "frictionlessdata\\datapackage\\Resources\\TabularResource"
        ));
        // accessing resource by name
        $this->assertTrue(is_a(
            $datapackage->resource("another-resource-name"),
            "frictionlessdata\\datapackage\\Resources\\TabularResource"
        ));
        $this->assertTrue(is_a(
            $datapackage->resource("resource-name"),
            "frictionlessdata\\datapackage\\Resources\\DefaultResource"
        ));
        // delete resource
        $this->assertCount(2, $datapackage->resources());
        $datapackage->deleteResource("resource-name");
        $this->assertCount(1, $datapackage->resources());
        $i = 0;
        foreach ($datapackage as $resource) { $i++; };
        $this->assertEquals(1, $i);
        $this->assertEquals((object)[
            "name" => "datapackage-name",
            "resources" => [
                (object)[
                    "name" => "another-resource-name",
                    "profile" => "tabular-data-resource",
                    "data" => ["simple_tabular_data.csv"],
                    "schema" => $schema->fullDescriptor()
                ],
            ]
        ], $datapackage->descriptor());

        // add a resource
        $this->assertCount(1, $datapackage->resources());
        $datapackage->addResource(Factory::resource((object)[
            "name" => "new-resource", "data" => ["tests/fixtures/foo.txt", "tests/fixtures/baz.txt"]
        ]));
        $this->assertCount(2, $datapackage->resources());
        $this->assertEquals((object)[
            "name" => "datapackage-name",
            "resources" => [
                (object)[
                    "name" => "another-resource-name",
                    "profile" => "tabular-data-resource",
                    "data" => ["simple_tabular_data.csv"],
                    "schema" => $schema->fullDescriptor()
                ],
                (object)[
                    "name" => "new-resource", "data" => ["tests/fixtures/foo.txt", "tests/fixtures/baz.txt"]
                ]
            ]
        ], $datapackage->descriptor());
        $rows = [];
        foreach ($datapackage as $resource) {
            if ($resource->name() == "new-resource") {
                foreach ($resource as $dataStream) {
                    foreach ($dataStream as $row) {
                        $rows[] = $row;
                    }
                }
            }
        }
        $this->assertEquals(['foo', "בזבזבז\n", 'זבזבזב'], $rows);
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

    protected function assertDatapackageException($expectedExceptionClass, $expectedMessage, $datapackageCallback)
    {
        try {
            $datapackageCallback();
            $this->fail("expected an exception");
        } catch (\Exception $e) {
            $actualExceptionClass = get_class($e);
            $this->assertEquals(
                $expectedExceptionClass,
                $actualExceptionClass,
                "unexpected exception: {$e->getMessage()}\n{$e->getTraceAsString()}"
            );
            $this->assertEquals($expectedMessage, $e->getMessage());
        }
    }

    protected function getFopenErrorMessage($in)
    {
        try {
            fopen($in, "r");
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        throw new \Exception();
    }

    protected function getFileGetContentsErrorMessage($in)
    {
        try {
            file_get_contents($in);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        throw new \Exception();
    }
}
