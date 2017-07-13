<?php

namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\DefaultDataStream;

class DefaultResource extends BaseResource
{
    /**
     * @param string $dataSource
     *
     * @return DefaultDataStream
     */
    protected function getDataStream($dataSource, $dataSourceOptions = null)
    {
        return new DefaultDataStream($this->normalizeDataSource($dataSource, $this->basePath), $dataSourceOptions);
    }

    protected static function handlesProfile($profile)
    {
        return $profile == 'data-resource';
    }
}
