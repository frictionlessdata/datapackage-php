<?php

namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\tableschema\DataSources\NativeDataSource;
use frictionlessdata\datapackage\Exceptions\DataStreamOpenException;

class TabularInlineDataStream extends TabularDataStream
{
    protected function getDataSourceObject()
    {
        $data = json_decode(json_encode($this->dataSource), true);
        if (is_array($data)) {
            $numFields = count($this->schema->fields());
            $objRows = [];
            if (array_sum(array_keys($data[0])) == array_sum(range(0, $numFields - 1))) {
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
