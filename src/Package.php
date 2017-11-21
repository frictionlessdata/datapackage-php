<?php

namespace frictionlessdata\datapackage;

class Package
{
    public static function load($source, $basePath = null, $options = null)
    {
        return Factory::datapackage($source, $basePath, $options);
    }

    public static function validate($source, $basePath = null)
    {
        return Factory::validate($source, $basePath);
    }

    public static function create($descriptor = null, $basePath = null)
    {
        $descriptor = Utils::objectify($descriptor);
        if ($descriptor && !isset($descriptor->resources)) {
            $descriptor->resources = [];
        }
        $packageClass = Factory::getDatapackageClass($descriptor);

        return new $packageClass($descriptor, $basePath, true);
    }
}
