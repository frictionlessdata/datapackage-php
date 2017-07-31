<?php

namespace frictionlessdata\datapackage\tests;

use Alchemy\Zippy\Zippy;
use frictionlessdata\datapackage\Utils;
use frictionlessdata\datapackage\Validators\DatapackageValidationError;
use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Datapackages\DefaultDatapackage;
use frictionlessdata\datapackage\Exceptions;
use frictionlessdata\datapackage\Package;
use frictionlessdata\datapackage\Resource;
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
        $this->simpleDescriptorExpectedData = ['resource-name' => ['foo']];
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
            ], ['resource-name' => ['foo', 'foo']],
            Mocks\MockFactory::datapackage('mock-http://simple_valid_datapackage_mock_http_data.json')
        );
    }

    public function testMultiDataDatapackage()
    {
        $out = [];
        $datapackage = Package::load('tests/fixtures/multi_data_datapackage.json');
        foreach ($datapackage as $resource) {
            $out[] = '-- '.$resource->name().' --';
            foreach ($resource as $line) {
                $out[] = $line;
            }
        }
        $this->assertEquals([
            '-- first-resource --',
            'foo',
            "BAR!\n",
            "bar\n",
            "בר\n",
            '',
            "בזבזבז\n",
            'זבזבזב',
            '-- second-resource --',
            "BAR!\n",
            "bar\n",
            "בר\n",
            '',
            "בזבזבז\n",
            'זבזבזב',
            '-- third-resource --',
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
            'resource 1, line number 4: email: value is not a valid email ("bad.email")',
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
        $datapackage->resource('new-resource', [
            'path' => ['tests/fixtures/foo.txt', 'tests/fixtures/baz.txt'],
        ]);
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
                foreach ($resource as $row) {
                    $rows[] = $row;
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
            foreach ($resource as $row) {
                $resources_data[$resource->name()][] = trim($row);
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
        $package = Package::create([
            'name' => 'my-datapackage-name',
            'resources' => [
                ['name' => 'my-default-resource'],
                ['name' => 'my-tabular-resource', 'profile' => 'tabular-data-resource'],
            ],
        ]);
        // the tabular resource is missing schema, but it doesn't fail
        // when creating a datapackage or resource with the create method it doesn't validate
        $this->assertEquals((object) [
            'name' => 'my-datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'my-default-resource',
                ],
                (object) [
                    'name' => 'my-tabular-resource',
                    'profile' => 'tabular-data-resource',
                ],
            ],
        ], $package->descriptor());
        // you can now modify the descriptor further by editing the descriptor directly
        $package->descriptor()->resources[1]->name = 'my-renamed-tabular-resource';
        $package->descriptor()->resources[1]->schema = [
            'fields' => [
                ['name' => 'id', 'type' => 'integer'],
                ['name' => 'name', 'type' => 'string'],
            ],
        ];
        // when you are done you can revalidate
        try {
            $package->revalidate();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        // you can expect errors if you use the datapackage before it validates
        try {
            foreach ($package as $resource) {
            }
        } catch (\Exception $e) {
            $this->assertEquals(
                'resource validation failed: [data] There must be a minimum of 1 items in the array',
                $e->getMessage()
            );
        }

        $fooFilename = dirname(__FILE__).'/fixtures/foo.txt';
        $package->resource('my-default-resource')->descriptor()->path[] = $fooFilename;

        $defaultSecondPath = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-').'.csv';
        $package->resource('my-default-resource')->descriptor()->path[] = $defaultSecondPath;
        file_put_contents($defaultSecondPath, 'BAHHH');

        $tabularDataFilename = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-').'.csv';
        $package->resource('my-renamed-tabular-resource')->descriptor()->path[] = $tabularDataFilename;

        foreach ($package as $resource) {
            if ($resource->name() == 'my-default-resource') {
                $this->assertEquals([$fooFilename, $defaultSecondPath], $resource->path());
                foreach ($resource as $row) {
                    $this->assertEquals('foo', $row);
                    break;
                }
            } else {
                // but the non-existant resources raise an exception
                try {
                    foreach ($resource as $row) {
                    }
                } catch (Exceptions\DataStreamOpenException $e) {
                    $this->assertContains('Failed to open tabular data source', $e->getMessage());
                }
            }
        }

        // write data to the new simple data source
        // you have to do this yourself, we don't support writing data stream at the moment
        $dataFilename = $package->resource('my-default-resource')->descriptor()->path[1];
        file_put_contents($dataFilename, "testing 改善\n");

        // now you can access the data normally
        $i = 0;
        foreach ($package->resource('my-default-resource') as $row) {
            if ($i == 0) {
                $this->assertEquals('foo', $row);
            } elseif ($i == 1) {
                $this->assertEquals("testing 改善\n", $row);
            } elseif ($i == 2) {
                $this->assertFalse($row);
            } else {
                $this->fail("{$i} - {$row}");
            }
            ++$i;
        }

        // save the descriptor to json file
        $filename = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-');
        $expectedDatapackageDescriptor = (object) [
            'name' => 'my-datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'my-default-resource',
                    'path' => [$fooFilename, $dataFilename],
                ],
                (object) [
                    'name' => 'my-renamed-tabular-resource',
                    'path' => [$tabularDataFilename],
                    'profile' => 'tabular-data-resource',
                    'schema' => (object) [
                        'fields' => [
                            (object) ['name' => 'id', 'type' => 'integer'],
                            (object) ['name' => 'name', 'type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
        $package->saveDescriptor($filename);
        $this->assertEquals($expectedDatapackageDescriptor, json_decode(file_get_contents($filename)));

        file_put_contents($tabularDataFilename, "id,name\n1,\"one\"\n2,\"two\"\n3,\"three\"");
        $this->assertEquals([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ], $package->resource('my-renamed-tabular-resource')->read());

        $filename = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-').".zip";
        $package->save($filename);
        $zippy = Zippy::load();
        $tempdir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'datapackage-php-tests-zipdir';
        if (file_exists($tempdir)) Utils::removeDir($tempdir);
        mkdir($tempdir);
        $archive = $zippy->open($filename);
        $archive->extract($tempdir);
        unlink($filename);
        $tempdir = $tempdir.DIRECTORY_SEPARATOR;
        $this->assertEquals($expectedDatapackageDescriptor, json_decode(file_get_contents($tempdir."datapackage.json")));
        $this->assertEquals("foo", file_get_contents($tempdir."resource-0-data-0"));
        $this->assertEquals("testing 改善\n", file_get_contents($tempdir."resource-0-data-1"));
        $this->assertEquals("id,name\n1,one\n2,two\n3,three\n", file_get_contents($tempdir."resource-1.csv"));
    }

    public function testStringPath()
    {
        $package = Package::create(['resources' => [
            ['name' => '_', 'path' => dirname(__FILE__).'/fixtures/foo.txt'],
        ]]);
        $this->assertEquals(['foo'], $package->resource('_')->read());
    }

    public function testInlineDataRowArrays()
    {
        $resource = Resource::create([
            'name' => '_',
            'profile' => 'tabular-data-resource',
            'schema' => [
                'fields' => [
                    ['name' => 'id', 'type' => 'integer'],
                    ['name' => 'name', 'type' => 'string'],
                ],
            ],
            'data' => [
                ['id', 'name'],
                [1, 'one'],
                [2, 'two'],
                [3, 'three'],
            ],
        ]);
        $this->assertEquals([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ], $resource->read());
    }

    public function testInlineDataRowObjects()
    {
        $resource = Resource::create([
            'name' => '_',
            'profile' => 'tabular-data-resource',
            'schema' => [
                'fields' => [
                    ['name' => 'id', 'type' => 'integer'],
                    ['name' => 'name', 'type' => 'string'],
                ],
            ],
            'data' => [
                ['id' => 1, 'name' => 'one'],
                ['id' => 2, 'name' => 'two'],
                ['id' => 3, 'name' => 'three'],
            ],
        ]);
        $this->assertEquals([
            ['id' => 1, 'name' => 'one'],
            ['id' => 2, 'name' => 'two'],
            ['id' => 3, 'name' => 'three'],
        ], $resource->read());
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
            $resourceData = $resource->read();
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
