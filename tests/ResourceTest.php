<?php

namespace frictionlessdata\datapackage\tests;

use frictionlessdata\datapackage\tests\Mocks\MockResource;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{
    public function testHttpDataSourceShouldNotGetBasePath()
    {
        $resource = MockResource::create([
            'name' => 'resource-name',
            'path' => [
                'mock-http://foo.txt', // basePath will not be added to http source
                'foo.txt', // basePath will be added here
            ],
        ], dirname(__FILE__).DIRECTORY_SEPARATOR.'fixtures');
        $this->assertEquals(['foo', 'foo'], $resource->read());
    }
}
