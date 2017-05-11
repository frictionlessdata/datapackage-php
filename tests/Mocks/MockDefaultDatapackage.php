<?php
namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Datapackages\DefaultDatapackage;

class MockDefaultDatapackage extends DefaultDatapackage
{
    protected function initResource($resourceDescriptor)
    {
        return new MockDefaultResource($resourceDescriptor, $this->basePath);
    }

    protected function datapackageValidate()
    {
        return MockDatapackageValidator::validate($this->descriptor(), $this->basePath);
    }
}