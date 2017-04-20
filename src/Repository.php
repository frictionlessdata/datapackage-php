<?php namespace frictionlessdata\datapackage;

/**
 * repository of known profiles and the corresponding classes and validation requirements
 */
class Repository
{
    public static function getResourceClass($descriptor)
    {
        if (static::getResourceValidationProfile($descriptor) == "tabular-data-resource") {
            return "frictionlessdata\\datapackage\\Resources\\TabularResource";
        } else {
            return "frictionlessdata\\datapackage\\Resources\\DefaultResource";
        }
    }

    public static function getResourceValidationProfile($descriptor)
    {
        if (isset($descriptor->profile) && $descriptor->profile != "default") {
            return $descriptor->profile;
        } else {
            return "data-resource";
        }
    }

    public static function getDatapackageClass($descriptor)
    {
        return "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage";
    }

    public static function getDatapackageValidationProfile($descriptor)
    {
        if (isset($descriptor->profile) && $descriptor->profile != "default") {
            return $descriptor->profile;
        } else {
            return "data-package";
        }
    }
}