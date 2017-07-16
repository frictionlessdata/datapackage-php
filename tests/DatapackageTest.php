<?php

namespace frictionlessdata\datapackage\tests;

use frictionlessdata\datapackage\Validators\DatapackageValidationError;
use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Datapackages\DefaultDatapackage;
use frictionlessdata\datapackage\Exceptions;
use frictionlessdata\datapackage\Package;
use frictionlessdata\datapackage\Resource;
use frictionlessdata\tableschema\InferSchema;
use frictionlessdata\tableschema\Table;
use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\datapackage\Resources\DefaultResource;
use frictionlessdata\datapackage\Resources\TabularResource;

class DatapackageTest extends TestCase
{
    public $simpleDescriptorArray;
    public $simpleDescriptor;
    public $simpleDescriptorExpectedData;
    public $fixturesPath;

    public function setUp()
    {
        $this->simpleDescriptorArray = [
            'name' => 'datapackage-name',
            'resources' => [
                ['name' => 'resource-name', 'path' => ['foo.txt']],
            ],
        ];
        $this->simpleDescriptor = (object) [
            'name' => 'datapackage-name',
            'resources' => [
                (object) ['name' => 'resource-name', 'path' => ['foo.txt']],
            ],
        ];
        $this->simpleDescriptorExpectedData = ['resource-name' => [['foo']]];
        $this->fixturesPath = dirname(__FILE__).'/fixtures';
    }

    public function testNativePHPArrayShouldFail()
    {
        $descriptorArray = $this->simpleDescriptorArray;
        $this->assertDatapackageException(
            'frictionlessdata\\datapackage\\Exceptions\\DatapackageInvalidSourceException',
            'Invalid source: {"name":"datapackage-name","resources":[{"name":"resource-name","path":["foo.txt"]}]}',
            function () use ($descriptorArray) { Package::load($descriptorArray); }
        );
    }

    public function testPHPObjectWithRelativeFilesButNoBasePathShouldFail()
    {
        $descriptor = $this->simpleDescriptor;
        $this->assertDatapackageException(
            'frictionlessdata\\datapackage\\Exceptions\\DatapackageValidationFailedException',
            'Datapackage validation failed: data source file does not exist or is not readable: foo.txt',
            function () use ($descriptor) { Package::load($descriptor); }
        );
    }

    public function testNativePHPObject()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Package::load($this->simpleDescriptor, $this->fixturesPath)
        );
    }

    public function testJsonStringWithRelativeFilesButNoBasePathShouldFail()
    {
        $source = json_encode($this->simpleDescriptor);
        $this->assertDatapackageException(
            'frictionlessdata\\datapackage\\Exceptions\\DatapackageValidationFailedException',
            'Datapackage validation failed: data source file does not exist or is not readable: foo.txt',
            function () use ($source) { Package::load($source); }
        );
    }

    public function testJsonStringWithBasePath()
    {
        $source = json_encode($this->simpleDescriptor);
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Package::load($source, $this->fixturesPath)
        );
    }

    public function testNonExistantFileShouldFail()
    {
        $this->assertDatapackageException(
            'frictionlessdata\\datapackage\\Exceptions\\DatapackageInvalidSourceException',
            'Failed to load source: "-invalid-": '.$this->getFileGetContentsErrorMessage('-invalid-'),
            function () { Package::load('-invalid-'); }
        );
    }

    public function testJsonFileRelativeToBasePath()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Package::load('simple_valid_datapackage.json', $this->fixturesPath)
        );
    }

    public function testJsonFileRelativeToCurrentDirectory()
    {
        $this->assertDatapackage(
            $this->simpleDescriptor, $this->simpleDescriptorExpectedData,
            Package::load('tests/fixtures/simple_valid_datapackage.json')
        );
    }

    public function testHttpSource()
    {
        $this->assertDatapackage(
            (object) [
                'name' => 'datapackage-name',
                'resources' => [
                    (object) [
                        'name' => 'resource-name',
                        'path' => ['mock-http://foo.txt', 'mock-http://foo.txt'],
                    ],
                ],
            ], ['resource-name' => [['foo'], ['foo']]],
            Mocks\MockFactory::datapackage('mock-http://simple_valid_datapackage_mock_http_data.json')
        );
    }

    public function testMultiDataDatapackage()
    {
        $out = [];
        $datapackage = Package::load('tests/fixtures/multi_data_datapackage.json');
        foreach ($datapackage as $resource) {
            $out[] = '-- '.$resource->name().' --';
            $i = 0;
            foreach ($resource as $dataStream) {
                $out[] = '-dataStream '.++$i;
                foreach ($dataStream as $line) {
                    $out[] = $line;
                }
            }
        }
        $this->assertEquals([
            '-- first-resource --',
            '-dataStream 1',
            'foo',
            '-dataStream 2',
            "BAR!\n",
            "bar\n",
            "בר\n",
            '',
            '-dataStream 3',
            "בזבזבז\n",
            'זבזבזב',
            '-- second-resource --',
            '-dataStream 1',
            "BAR!\n",
            "bar\n",
            "בר\n",
            '',
            '-dataStream 2',
            "בזבזבז\n",
            'זבזבזב',
            '-- third-resource --',
            '-dataStream 1',
            "בזבזבז\n",
            'זבזבזב',
        ], $out);
    }

    public function testDatapackageValidation()
    {
        $this->assertEquals([], Package::validate('tests/fixtures/multi_data_datapackage.json'));
    }

    public function testDatapackageValidationFailed()
    {
        $this->assertDatapackageValidation(
            '[resources] The property resources is required',
            'tests/fixtures/simple_invalid_datapackage.json'
        );
    }

    public function testDatapackageValidationFailedShouldPreventConstruct()
    {
        try {
            Package::load((object) ['name' => 'foobar']);
            $caughtException = null;
        } catch (Exceptions\DatapackageValidationFailedException $e) {
            $caughtException = $e;
        }
        $this->assertEquals('Datapackage validation failed: [resources] The property resources is required', $caughtException->getMessage());
    }

    public function testTabularResourceDescriptorValidation()
    {
        $this->assertDatapackageValidation(
            '[schema.fields] The property fields is required',
            'tests/fixtures/invalid_tabular_resource.json'
        );
    }

    public function testDefaultResourceInvalidData()
    {
        $this->assertDatapackageValidation(
            'data source file does not exist or is not readable: --invalid--',
            'tests/fixtures/default_resource_invalid_data.json'
        );
    }

    public function testTabularResourceInvalidData()
    {
        $this->assertDatapackageValidation(
            'resource 1, data stream 2: email: value is not a valid email ("bad.email")',
            'tests/fixtures/tabular_resource_invalid_data.json'
        );
    }

    public function testDatapackageResources()
    {
        // prepare the desriptor, schema and datapackage
        $dataSource = new CsvDataSource('tests/fixtures/simple_tabular_data.csv');
        $schema = new InferSchema();
        Table::validate($dataSource, $schema, 1);
        $descriptor = (object) [
            'name' => 'datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'resource-name', 'path' => ['foo.txt', 'baz.txt'],
                ],
                (object) [
                    'name' => 'another-resource-name',
                    'profile' => 'tabular-data-resource',
                    'path' => ['simple_tabular_data.csv'],
                    'schema' => $schema->fullDescriptor(),
                ],
            ],
        ];
        $basePath = 'tests/fixtures';
        $datapackage = new DefaultDatapackage($descriptor, $basePath);
        // test accessing resources
        $resources = $datapackage->resources();
        $this->assertTrue(is_a(
            $resources['resource-name'],
            'frictionlessdata\\datapackage\\Resources\\DefaultResource'
        ));
        $this->assertTrue(is_a(
            $resources['another-resource-name'],
            'frictionlessdata\\datapackage\\Resources\\TabularResource'
        ));
        // accessing resource by name
        $this->assertTrue(is_a(
            $datapackage->resource('another-resource-name'),
            'frictionlessdata\\datapackage\\Resources\\TabularResource'
        ));
        $this->assertTrue(is_a(
            $datapackage->resource('resource-name'),
            'frictionlessdata\\datapackage\\Resources\\DefaultResource'
        ));
        // delete resource
        $this->assertCount(2, $datapackage->resources());
        $datapackage->deleteResource('resource-name');
        $this->assertCount(1, $datapackage->resources());
        $i = 0;
        foreach ($datapackage as $resource) {
            ++$i;
        }
        $this->assertEquals(1, $i);
        $this->assertEquals((object) [
            'name' => 'datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'another-resource-name',
                    'profile' => 'tabular-data-resource',
                    'path' => ['simple_tabular_data.csv'],
                    'schema' => $schema->fullDescriptor(),
                ],
            ],
        ], $datapackage->descriptor());

        // add a resource
        $this->assertCount(1, $datapackage->resources());
        $datapackage->addResource(Resource::load((object) [
            'name' => 'new-resource', 'path' => ['tests/fixtures/foo.txt', 'tests/fixtures/baz.txt'],
        ]));
        $this->assertCount(2, $datapackage->resources());
        $this->assertEquals((object) [
            'name' => 'datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'another-resource-name',
                    'profile' => 'tabular-data-resource',
                    'path' => ['simple_tabular_data.csv'],
                    'schema' => $schema->fullDescriptor(),
                ],
                (object) [
                    'name' => 'new-resource', 'path' => ['tests/fixtures/foo.txt', 'tests/fixtures/baz.txt'],
                ],
            ],
        ], $datapackage->descriptor());
        $rows = [];
        foreach ($datapackage as $resource) {
            if ($resource->name() == 'new-resource') {
                foreach ($resource as $dataStream) {
                    foreach ($dataStream as $row) {
                        $rows[] = $row;
                    }
                }
            }
        }
        $this->assertEquals(['foo', "בזבזבז\n", 'זבזבזב'], $rows);
    }

    public function testFiscalDatapackage()
    {
        $dp = Package::load('tests/fixtures/fiscal-datapackage/datapackage.json');
        $resources_data = [];
        foreach ($dp as $resource) {
            $resources_data[$resource->name()] = [];
            foreach ($resource as $dataStream) {
                foreach ($dataStream as $row) {
                    $resources_data[$resource->name()][] = trim($row);
                }
            }
        }
        $this->assertEquals(array(
                'budget' => [
                    'pk,budget,budget_date,payee',
                    '1,10000,01/01/2015,1',
                    '2,20000,01/02/2015,1',
                ],
                'entities' => [
                    'id,name,description',
                    '1,Acme 1,They are the first acme company',
                    '2,Acme 2,They are the sceond acme company',
                ],
        ), $resources_data);
    }

    public function testCreateEditDatapackageDescriptor()
    {
        // create static method allows to create a new datapackage or resource without validation
        // with shortcut arguments for common use-cases
        $datapackage = DefaultDatapackage::create('my-datapackage-name', [
            DefaultResource::create('my-default-resource'),
            TabularResource::create('my-tabular-resource'),
        ]);

        // when creating a datapackage or resource with the create method it doesn't validate
        $this->assertEquals((object) [
            'name' => 'my-datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'my-default-resource',
                    'path' => [],
                    'data' => [],
                ],
                (object) [
                    'name' => 'my-tabular-resource',
                    'path' => [],
                    'data' => [],
                ],
            ],
        ], $datapackage->descriptor());

        // you can now modify the descriptor further
        $datapackage->descriptor()->resources[1]->name = 'my-renamed-tabular-resource';

        // when you are done you can revalidate
        try {
            $datapackage->revalidate();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // you can expect errors if you use the datapackage before it validates
        try {
            foreach ($datapackage as $resource) {
            }
        } catch (\Exception $e) {
            $this->assertEquals(
                'resource validation failed: [data] There must be a minimum of 1 items in the array',
                $e->getMessage()
            );
        }

        // you can add data items to the descriptor
        // for both existing and non-existing files which will be written to

        // an existing data item
        $datapackage->resource('my-default-resource')->descriptor()->path[] = dirname(__FILE__).'/fixtures/foo.txt';

        // non-existing data items
        $datapackage->resource('my-default-resource')->descriptor()->path[] = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-').'.csv';
        $datapackage->resource('my-renamed-tabular-resource')->descriptor()->path[] = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-').'.csv';

        // iterate over the data - will yield for first resource but raise exception for 2nd
        foreach ($datapackage as $resource) {
            if ($resource->name() == 'my-default-resource') {
                foreach ($resource as $dataStream) {
                    foreach ($dataStream as $row) {
                        $this->assertEquals('foo', $row);
                    }
                    break;
                }
            } else {
                // but the non-existant resources raise an exception
                try {
                    foreach ($resource as $dataStream) {
                    }
                } catch (Exceptions\DataStreamOpenException $e) {
                    $this->assertContains('Failed to open data source', $e->getMessage());
                }
            }
        }

        // write data to the new simple data source
        // you have to do this yourself, we don't support writing data stream at the moment
        $filename = $datapackage->resource('my-default-resource')->descriptor()->path[1];
        file_put_contents($filename, "testing 改善\n");

        // now you can access the data normally
        $i = 0;
        foreach ($datapackage->resource('my-default-resource') as $data) {
            if ($i == 1) {
                $j = 0;
                foreach ($data as $row) {
                    if ($j == 0) {
                        $this->assertEquals("testing 改善\n", $row);
                    } elseif ($j == 1) {
                        $this->assertFalse($row);
                    } else {
                        $this->fail();
                    }
                    ++$j;
                }
            }
            ++$i;
        }

        // save the descriptor to json file
        $filename = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-');
        $datapackage->saveDescriptor($filename);
        $this->assertEquals((object) [
            'name' => 'my-datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'my-default-resource',
                    'path' => $datapackage->resource('my-default-resource')->descriptor()->path,
                    'data' => [],
                ],
                (object) [
                    'name' => 'my-renamed-tabular-resource',
                    'path' => $datapackage->resource('my-renamed-tabular-resource')->descriptor()->path,
                    'data' => [],
                ],
            ],
        ], json_decode(file_get_contents($filename)));
    }

    protected function assertDatapackageValidation($expectedMessages, $source, $basePath = null)
    {
        $validationErrors = Package::validate($source, $basePath);
        $this->assertEquals(
            $expectedMessages,
            DatapackageValidationError::getErrorMessages($validationErrors)
        );
    }

    /**
     * @param object             $expectedDescriptor
     * @param DefaultDatapackage $datapackage
     */
    protected function assertDatapackageDescriptor($expectedDescriptor, $datapackage)
    {
        $this->assertEquals($expectedDescriptor, $datapackage->descriptor());
    }

    /**
     * @param array              $expectedData
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
     * @param array  $expectedData
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
            $this->fail('expected an exception');
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
            fopen($in, 'r');
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
