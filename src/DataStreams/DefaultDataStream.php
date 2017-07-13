<?php

namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\datapackage\Exceptions\DataStreamOpenException;

/**
 * streams the raw data without processing - used for default data package resources.
 */
class DefaultDataStream extends BaseDataStream
{
    /**
     * @param string $dataSource
     *
     * @throws DataStreamOpenException
     */
    public function __construct($dataSource, $dataSourceOptions = null)
    {
        try {
            $this->fopenResource = fopen($dataSource, 'r');
        } catch (\Exception $e) {
            throw new DataStreamOpenException('Failed to open data source '.json_encode($dataSource).': '.json_encode($e->getMessage()));
        }
    }

    public function __destruct()
    {
        fclose($this->fopenResource);
    }

    public function rewind()
    {
        if ($this->currentLineNumber == 0) {
            // starting iterations
            $this->currentLineNumber = 1;
        } else {
            throw new \Exception('DataStream does not support rewinding a stream, sorry');
        }
    }

    public function current()
    {
        return fgets($this->fopenResource);
    }

    public function key()
    {
        return $this->currentLineNumber;
    }

    public function next()
    {
        ++$this->currentLineNumber;
    }

    public function valid()
    {
        return !feof($this->fopenResource);
    }

    protected $currentLineNumber = 0;
    protected $fopenResource;
}
