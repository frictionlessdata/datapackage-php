<?php

namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Utils;

class MockResource
{

    public static function load($descriptor, $basePath = null)
    {
        $descriptor = Utils::objectify($descriptor);
        return MockFactory::resource($descriptor, $basePath);
    }

    public static function create($descriptor, $basePath = null)
    {
        $descriptor = Utils::objectify($descriptor);
        $skipValidations = true;
        return MockFactory::resource($descriptor, $basePath, $skipValidations);
    }

}
