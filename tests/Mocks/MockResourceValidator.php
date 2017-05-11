<?php
namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Validators\ResourceValidator;

class MockResourceValidator extends ResourceValidator
{
    protected function getResourceClass()
    {
        return "\\frictionlessdata\\datapackage\\tests\\Mocks\\MockDefaultResource";
    }
}
