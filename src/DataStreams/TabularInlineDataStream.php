<?php

namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\tableschema\DataSources\NativeDataSource;
use frictionlessdata\datapackage\Exceptions\DataStreamOpenException;

class TabularInlineDataStream extends TabularDataStream
{

    /**
     * @throws \frictionlessdata\datapackage\Exceptions\DataStreamOpenException
     */
    protected function getDataSourceObject()
    {
        $data = json_decode(json_encode($this->dataSource), true);
        if (is_array($data)) {
            $numFields = count($this->schema->fields());
            $objRows = [];
            if (!function_exists('array_is_list')) {
                function array_is_list(array $arr):bool
                {
                    if ($arr === []) {
                        return true;
                    }
                    return array_keys($arr) === range(0, count($arr) - 1);
                }
            }

            if (array_is_list($data[0]) && (count($data[0])) == $numFields) {
                // Row Arrays - convert to Row Objects
                $header = array_shift($data);
                foreach ($data as $row) {
                    $objRow = [];
                    foreach ($header as $fieldOrder => $fieldName) {
                        $objRow[$fieldName] = $row[$fieldOrder];
                    }
                    $objRows[] = $objRow;
                }
            } else {
                // Row Objects - no processing needed
                $objRows = $data;
            }

            return new NativeDataSource($objRows);
        } else {
            throw new DataStreamOpenException('inline tabular data must be an array');
        }
    }
}
