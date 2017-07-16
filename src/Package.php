<?php

namespace frictionlessdata\datapackage;

class Package
{

    public static function load($source, $basePath = null)
    {
        return Factory::datapackage($source, $basePath);
    }

    public static function validate($source, $basePath = null)
    {
        return Factory::validate($source, $basePath);
    }

}
