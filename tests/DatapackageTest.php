<?php


use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Datapackage;


class DatapackageTest extends TestCase
{

    /**
     * @param string $source
     * @return Datapackage
     */
    protected function _getDatapackage($source)
    {
        return new Datapackage($source, dirname(__FILE__)."/fixtures");
    }

    /**
     * @param object $expectedDescriptor
     * @param Datapackage $datapackage
     */
    protected function _assertDatapackageDescriptor($expectedDescriptor, $datapackage)
    {
        $this->assertEquals($expectedDescriptor, $datapackage->descriptor());
    }

    /**
     * @param array $expectedData
     * @param Datapackage $datapackage
     */
    protected function _assertDatapackageData($expectedData, $datapackage)
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
    public function assertDatapackage($source, $expectedDescriptor, $expectedData)
    {
        $datapackage = $this->_getDatapackage($source);
        $this->_assertDatapackageDescriptor($expectedDescriptor, $datapackage);
        $this->_assertDatapackageData($expectedData, $datapackage);
    }

    public function testSimpleValidDatapackage()
    {
        $simpleValidDatapackageDecriptor = (object)[
            "name" => "datapackage-name",
            "resources" => [
                (object)["name" => "resource-name", "data" => ["foo.txt"] ]
            ]
        ];
        $this->assertDatapackage(
            "simple_valid_datapackage.json",
            $simpleValidDatapackageDecriptor,
            ["resource-name" => [["foo"]]]
        );
        $this->assertDatapackage(
            $simpleValidDatapackageDecriptor,
            $simpleValidDatapackageDecriptor,
            ["resource-name" => [["foo"]]]
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

}
