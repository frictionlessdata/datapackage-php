<?php
namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\DefaultDataStream;

class DefaultResource extends BaseResource
{
    /**
     * @param string $dataSource
     * @return DefaultDataStream
     */
    protected function getDataStream($dataSource)
    {
        return new DefaultDataStream($this->normalizeDataSource($dataSource));
    }
}
