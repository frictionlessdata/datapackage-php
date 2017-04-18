<?php
namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Datapackage;

class MockDatapackage extends Datapackage
{
    protected function isHttpSource($dataSource)
    {
        return (
            strpos($dataSource, "mock-http://") === 0
            || parent::isHttpSource($dataSource)
        );
    }

    protected function normalizeHttpSource($dataSource)
    {
        $dataSource = parent::normalizeHttpSource($dataSource);
        if (strpos($dataSource, "mock-http://") === 0) {
            $dataSource = str_replace("mock-http://", "", $dataSource);
            $dataSource = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."fixtures".DIRECTORY_SEPARATOR.$dataSource;
        }
        return $dataSource;
    }
}