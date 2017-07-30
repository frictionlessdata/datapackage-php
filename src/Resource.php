<?php

namespace frictionlessdata\datapackage;

class Resource
{
    public static function load($descriptor, $basePath = null)
    {
        $descriptor = Utils::objectify($descriptor);

        return Factory::resource($descriptor, $basePath);
    }

    public static function create($descriptor, $basePath = null)
    {
        $descriptor = Utils::objectify($descriptor);
        $skipValidations = true;

        return Factory::resource($descriptor, $basePath, $skipValidations);
    }
}
