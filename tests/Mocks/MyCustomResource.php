<?php

namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Resources\DefaultResource;

class MyCustomResource extends DefaultResource
{
    public static function handlesDescriptor($descriptor)
    {
        return isset($descriptor->goGoPowerRangers);
    }
}
