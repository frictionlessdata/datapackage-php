<?php

namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Validators\DatapackageValidator;

class MockDatapackageValidator extends DatapackageValidator
{
    protected function resourceValidate($resourceDescriptor)
    {
        return MockResourceValidator::validate($resourceDescriptor, $this->basePath);
    }
}
