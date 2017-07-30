<?php

namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Datapackages\DefaultDatapackage;

class MyCustomDatapackage extends DefaultDatapackage
{
    public static function handlesDescriptor($descriptor)
    {
        return isset($descriptor->myCustomDatapackage);
    }
}
