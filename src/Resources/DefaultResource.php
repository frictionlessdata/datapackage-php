<?php

namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\DefaultDataStream;

class DefaultResource extends BaseResource
{

    /**
     * @param string $dataSource
     *
     * @param null $dataSourceOptions
     *
     * @return DefaultDataStream
     * @throws \frictionlessdata\datapackage\Exceptions\DataStreamOpenException
     */
    protected function getDataStream($dataSource, $dataSourceOptions = null)
    {
        return new DefaultDataStream($this->normalizeDataSource($dataSource, $this->basePath), $dataSourceOptions);
    }

    protected function getInlineDataStream($data)
    {
        return $data;
    }

    protected static function handlesProfile($profile)
    {
        return $profile == 'data-resource';
    }
}
