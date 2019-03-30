<?php

namespace frictionlessdata\datapackage\tests;

use Chumper\Zipper\Zipper;
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
use Carbon\Carbon;

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

    public function testJsonInvalidSyntaxShouldFail()
    {
        // http://php.net/manual/en/function.json-decode.php
        // trailing commas are not allowed
        $bad_json = '{ "bar": "baz", }';
        json_decode($bad_json); // null
        $this->assertDatapackageException(
            'frictionlessdata\\datapackage\\Exceptions\\DatapackageInvalidSourceException',
            json_last_error_msg().' when loading source: '.json_encode($bad_json),
            function () use ($bad_json) { Package::load($bad_json); }
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
        $datapackage->removeResource('resource-name');
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
        $datapackage->addResource('new-resource', [
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
                $resources_data[$resource->name()][] = $row;
            }
        }
        $this->assertEquals([
            'id' => null, 'amount' => null, 'date' => null, 'payee' => 1,
        ], $resources_data['budget'][1]);
        $this->assertEquals([
            'id' => '1', 'title' => null, 'description' => 'They are the first acme company',
        ], $resources_data['entities'][0]);
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
        $package->getResource('my-renamed-tabular-resource')->descriptor()->path[] = $tabularDataFilename;

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
        foreach ($package->getResource('my-default-resource') as $row) {
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

        $filename = tempnam(sys_get_temp_dir(), 'datapackage-php-tests-').'.zip';
        $package->save($filename);
        $zipper = new Zipper();
        $tempdir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'datapackage-php-tests-zipdir';
        if (file_exists($tempdir)) {
            Utils::removeDir($tempdir);
        }
        mkdir($tempdir);
        $zipper->make($filename)->extractTo($tempdir);
        $zipper->close();
        unlink($filename);
        $tempdir = $tempdir.DIRECTORY_SEPARATOR;

        //after saving to disk, the paths are updated
        $expectedDatapackageDescriptor = (object) [
            'name' => 'my-datapackage-name',
            'resources' => [
                (object) [
                    'name' => 'my-default-resource',
                    'path' => ["resource-0-data-0", "resource-0-data-1"],
                ],
                (object) [
                    'name' => 'my-renamed-tabular-resource',
                    'path' => "resource-1.csv",
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
        $this->assertEquals($expectedDatapackageDescriptor, json_decode(file_get_contents($tempdir.'datapackage.json')));
        $this->assertEquals('foo', file_get_contents($tempdir.'resource-0-data-0'));
        $this->assertEquals("testing 改善\n", file_get_contents($tempdir.'resource-0-data-1'));
        $this->assertEquals("id,name\n1,one\n2,two\n3,three\n", file_get_contents($tempdir.'resource-1.csv'));
    }

    public function testSaveAndLoadZip()
    {
        //create example csv
        file_put_contents('/tmp/example.csv', "name,email\nJohn Doe,john@example.com");

        //create a new datapackage object
        $package = Package::create(['name' => 'csv-example','profile' => 'tabular-data-package']);

        //add a csv file
        $package->addResource('example.csv', [
            "profile" => "tabular-data-resource",
            "schema" => ["fields" => [["name" => "name", "type" => "string"],["name" => "email", "type" => "string"]]],
            "path" => '/tmp/example.csv'
        ]);

        //save the datapackage
        if (is_file('datapackage.zip')) {
            unlink('datapackage.zip');
        }
        $package->save("datapackage.zip");

        //delete example csv
        unlink('/tmp/example.csv');

        //load the new package
        $package2 = Package::load('datapackage.zip');

        //assert you get expected content back out
        $this->assertEquals([['name' => 'John Doe', 'email' => 'john@example.com']], $package2->resource('example.csv')->read());

        unlink('datapackage.zip');
    }

    public function testLoadDatapackageZip()
    {
        $package = Package::load(dirname(__FILE__).'/fixtures/datapackage_zip.zip');
        // $package = Package::load('http://datahub.io/opendatafortaxjustice/eucountrydatawb/r/datapackage_zip.zip');
        $this->assertEquals([[
            'jurisdiction' => 'Austria',
            'population in millions' => 8.7474000000000007,
            'GDP in $Billions' => 386.42779999999999,
            'GDP per cap' => 44176.519999999997,
        ], [
            'jurisdiction' => 'Belgium',
            'population in millions' => 11.3482,
            'GDP in $Billions' => 466.3657,
            'GDP per cap' => 41096.160000000003,
        ]], $package->resource('eucountrydatawb_csv')->read(['limit' => 2]));
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

    public function testDataHubCountryList()
    {
        // $package = Package::load("https://datahub.io/core/country-list/datapackage.json");
        $package = Package::load(dirname(__FILE__).'/fixtures/datahub-country-list/datapackage.json');
        $this->assertEquals('data-package', $package->descriptor()->profile);
        $resources = [];
        foreach ($package as $resource) {
            $resources[$resource->name()] = [];
            foreach ($resource as $row) {
                // note that this is not a tabular data resource, so we return the rows as strings
                $this->assertEquals('data-resource', $resource->descriptor()->profile);
                $resources[$resource->name()][] = $row;
            }
        }
        $this->assertEquals(['data_csv', 'data_json', 'datapackage_zip', 'data'], array_keys($resources));
        $this->assertEquals(['Name' => 'Afghanistan', 'Code' => 'AF'], $resources['data_csv'][0]);

        // now, let's try to load it but get it as tabular data
        $descriptor = json_decode(file_get_contents(dirname(__FILE__).'/fixtures/datahub-country-list/datapackage.json'));
        foreach ($descriptor->resources as $resource) {
            if (!in_array($resource->name, ['datapackage_zip', 'data_json'])) {
                $resource->profile = 'tabular-data-resource';
            }
        }
        $package = Package::load($descriptor, dirname(__FILE__).'/fixtures/datahub-country-list');
        $resources = [];
        foreach ($package as $resource) {
            $resources[$resource->name()] = [];
            foreach ($resource as $row) {
                $resources[$resource->name()][] = $row;
            }
        }
    }

    public function testCommitteesPackage()
    {
        $package = Package::load(dirname(__FILE__).'/fixtures/committees/datapackage.json');
        $resourceNum = 0;
        foreach ($package as $resource) {
            $this->assertEquals(0, $resourceNum);
            $rowNum = 0;
            foreach ($resource as $row) {
                ++$rowNum;
            }
            $this->assertEquals(702, $rowNum);
            ++$resourceNum;
        }
    }

    public function testCsvDialect()
    {
        $package = Package::load(dirname(__FILE__).'/fixtures/committees/datapackage-lolsv.json');
        $resourceNum = 0;
        foreach ($package as $resource) {
            $this->assertEquals(0, $resourceNum);
            $rowNum = 0;
            foreach ($resource as $row) {
                if ($rowNum == 0) {
                    $this->assertEquals(array(
                        'CommitteeID' => 97,
                        'Name' => '"ה""ח המדיניות הכלכלית לשנת הכספים 2004"',
                        'CategoryID' => null,
                        'CategoryDesc' => null,
                        'KnessetNum' => 16,
                        'CommitteeTypeID' => 73,
                        'CommitteeTypeDesc' => 'ועדה  משותפת',
                        'Email' => null,
                        'StartDate' => Carbon::__set_state(array(
                            'date' => '2004-08-12 00:00:00.000000',
                            'timezone_type' => 3,
                            'timezone' => 'UTC',
                        )),
                        'FinishDate' => null,
                        'AdditionalTypeID' => null,
                        'AdditionalTypeDesc' => null,
                        'ParentCommitteeID' => null,
                        'CommitteeParentName' => null,
                        'IsCurrent' => true,
                        'LastUpdatedDate' => Carbon::__set_state(array(
                            'date' => '2015-03-20 12:02:57.000000',
                            'timezone_type' => 3,
                            'timezone' => 'UTC',
                        )),
                    ), $row);
                }
                ++$rowNum;
            }
            $this->assertEquals(2, $rowNum);
            ++$resourceNum;
        }
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
