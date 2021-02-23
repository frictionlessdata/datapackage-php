<?php

namespace frictionlessdata\datapackage\tests\Mocks;

use frictionlessdata\datapackage\Resources\DefaultResource;

class MockDefaultResource extends DefaultResource
{

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source.
     *
     * @param string $dataSource
     *
     * @param null $basePath
     *
     * @return string
     */
    public static function normalizeDataSource(string $dataSource, $basePath = null)
    {
        if (strpos($dataSource, 'mock-http://') === 0) {
            $dataSource = str_replace('mock-http://', '', $dataSource);

            return dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.$dataSource;
        } else {
            return parent::normalizeDataSource($dataSource, $basePath);
        }
    }

    public static function validateDataSource($dataSource, $basePath = null)
    {
        if (strpos($dataSource, 'mock-http://') === 0) {
            return [];
        } else {
            return parent::validateDataSource($dataSource, $basePath);
        }
    }

    protected function validateResource()
    {
        return MockResourceValidator::validate($this->descriptor(), $this->basePath);
    }
}
