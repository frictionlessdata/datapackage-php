<?php

namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\TabularDataStream;
use frictionlessdata\datapackage\DataStreams\TabularInlineDataStream;

class TabularResource extends DefaultResource
{
    public function schema()
    {
        // TODO: change to table schema object
        return $this->descriptor()->schema;
    }

    /**
     * @param string $dataSource
     *
     * @return TabularDataStream
     */
    protected function getDataStream($dataSource, $dataSourceOptions = null)
    {
        return new TabularDataStream($this->normalizeDataSource($dataSource, $this->basePath), $this->schema());
    }

    protected function getInlineDataStream($data)
    {
        return new TabularInlineDataStream($data, $this->schema());
    }

    protected static function handlesProfile($profile)
    {
        return $profile == 'tabular-data-resource';
    }
}
