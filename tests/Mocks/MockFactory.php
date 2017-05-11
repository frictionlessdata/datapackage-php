<?php namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Factory;

class MockFactory extends Factory
{
    protected static function isHttpSource($dataSource)
    {
        return (
            strpos($dataSource, "mock-http://") === 0
            || parent::isHttpSource($dataSource)
        );
    }

    protected static function normalizeHttpSource($dataSource)
    {
        $dataSource = parent::normalizeHttpSource($dataSource);
        if (strpos($dataSource, "mock-http://") === 0) {
            $dataSource = str_replace("mock-http://", "", $dataSource);
            $dataSource = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."fixtures".DIRECTORY_SEPARATOR.$dataSource;
        }
        return $dataSource;
    }

    public static function getDatapackageClass($descriptor)
    {
        $datapackageClass = parent::getDatapackageClass($descriptor);
        if ($datapackageClass == "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage") {
            $datapackageClass = "frictionlessdata\\datapackage\\tests\\Mocks\\MockDefaultDatapackage";
        }
        return $datapackageClass;
    }

    public static function getResourceClass($descriptor)
    {
        $resourceClass = parent::getResourceClass($descriptor);
        if ($resourceClass == "frictionlessdata\\datapackage\\Resources\\DefaultResource") {
            $resourceClass = "frictionlessdata\\datapackage\\tests\\Mocks\\MockDefaultResource";
        }
        return $resourceClass;
    }
}