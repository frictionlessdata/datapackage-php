<?php


use PHPUnit\Framework\TestCase;
use frictionlessdata\datapackage\Resource;


class ResourceTest extends TestCase
{
    protected function assertResourceData($expectedData, $resource)
    {
        $actualData = [];
        foreach ($resource as $dataStream) {
            $data = [];
            foreach ($dataStream as $line) {
                $data[] = $line;
            }
            $actualData[] = $data;
        }
        $this->assertEquals($expectedData, $actualData);
    }

    public function testHttpDataSourceShouldNotGetBasePath()
    {
        $this->assertResourceData([["foo"],["foo"]], new MockResource((object)[
            "name" => "resource-name",
            "data" => [
                "mock-http://foo.txt", // basePath will not be added to http source
                "foo.txt" // basePath will be added here
            ]
        ], dirname(__FILE__).DIRECTORY_SEPARATOR."fixtures"));
    }
}


class MockResource extends Resource
{
    protected function _isHttpSource($dataSource)
    {
        return (
            strpos($dataSource, "mock-http://") === 0
            || parent::_isHttpSource($dataSource)
        );
    }

    protected function _normalizeDataSource($dataSource)
    {
        $dataSource = parent::_normalizeDataSource($dataSource);
        if (strpos($dataSource, "mock-http://") === 0) {
            $dataSource = str_replace("mock-http://", "", $dataSource);
            $dataSource = dirname(__FILE__).DIRECTORY_SEPARATOR."fixtures".DIRECTORY_SEPARATOR.$dataSource;
        }
        return $dataSource;
    }
}