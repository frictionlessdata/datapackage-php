<?php

namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\TabularDataStream;
use frictionlessdata\datapackage\DataStreams\TabularInlineDataStream;
use frictionlessdata\datapackage\Registry;

class TabularResource extends DefaultResource
{
    public function schema()
    {
        // TODO: change to table schema object
        return $this->descriptor()->schema;
    }

    public function getFileExtension()
    {
        return '.csv';
    }

    /**
     * @param string $dataSource
     *
     * @return TabularDataStream
     */
    protected function getDataStream($dataSource, $dataSourceOptions = null)
    {
        $dataSourceOptions = array_merge([
            'schema' => $this->schema(),
            'dialect' => isset($this->descriptor()->dialect) ? $this->descriptor()->dialect : null,
        ], (array) $dataSourceOptions);

        return new TabularDataStream($this->normalizeDataSource($dataSource, $this->basePath), $dataSourceOptions);
    }

    protected function getInlineDataStream($data)
    {
        return new TabularInlineDataStream($data, [
            'schema' => $this->schema(),
            'dialect' => isset($this->descriptor()->dialect) ? $this->descriptor()->dialect : null,
        ]);
    }

    public static function handlesDescriptor($descriptor)
    {
        return (
            Registry::getResourceValidationProfile($descriptor) == 'tabular-data-resource'
            || (isset($descriptor->format) && in_array($descriptor->format, ['csv', 'tsv']))
        );
    }
}
