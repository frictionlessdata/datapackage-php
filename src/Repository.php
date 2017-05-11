<?php namespace frictionlessdata\datapackage;
use frictionlessdata\datapackage\Resources\BaseResource;

/**
 * repository of known profiles and the corresponding classes and validation requirements
 */
class Repository
{
    /**
     * @param $descriptor
     * @return BaseResource::class
     */
    public static function getResourceClass($descriptor)
    {
        if (static::getResourceValidationProfile($descriptor) == "tabular-data-resource") {
            $resourceClass = "frictionlessdata\\datapackage\\Resources\\TabularResource";
        } else {
            $resourceClass = "frictionlessdata\\datapackage\\Resources\\DefaultResource";
        }
        /** @var BaseResource $resourceClass */
        return $resourceClass;
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