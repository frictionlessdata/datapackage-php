<?php

namespace frictionlessdata\datapackage;

class Resource
{

    public static function load($descriptor, $basePath = null, $skipValidations = false)
    {
        return Factory::resource($descriptor, $basePath, $skipValidations);
    }

}
