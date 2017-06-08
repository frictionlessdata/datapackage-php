<?php
namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\TabularDataStream;

class TabularResource extends DefaultResource
{
    public function __construct($descriptor, $basePath)
    {
        parent::__construct($descriptor, $basePath);
    }

    public function schema()
    {
        // TODO: change to table schema object
        return $this->descriptor()->schema;
    }

    /**
     * @param string $dataSource
     * @return TabularDataStream
     */
    protected function getDataStream($dataSource)
    {
        return new TabularDataStream($this->normalizeDataSource($dataSource, $this->basePath), $this->schema());
    }

    protected static function handlesProfile($profile)
    {
        return ($profile == "tabular-data-resource");
    }
}