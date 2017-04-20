<?php namespace frictionlessdata\datapackage;

/**
 * repository datapackage and resource profiles
 * can be used to get the correct datapackage or resource class given a descriptor of unknown profile
 */
class Repository
{
    public static function getResourceClass($descriptor)
    {
        if (static::getResourceValidationProfile($descriptor) == "tabular-data-resource") {
            return Resources\TabularResource::class;
        } else {
            return Resources\DefaultResource::class;
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
        return Datapackages\DefaultDatapackage::class;
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