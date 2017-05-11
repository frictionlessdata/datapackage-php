<?php
namespace frictionlessdata\datapackage\tests;

use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function testHttpDataSourceShouldNotGetBasePath()
    {
        $this->assertResourceData(
            [["foo"],["foo"]],
            Mocks\MockFactory::resource(
                (object)[
                    "name" => "resource-name",
                    "data" => [
                        "mock-http://foo.txt", // basePath will not be added to http source
                        "foo.txt" // basePath will be added here
                    ]
                ],
                dirname(__FILE__).DIRECTORY_SEPARATOR."fixtures"
            )
        );
    }

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
}
