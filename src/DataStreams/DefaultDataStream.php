<?php

namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\datapackage\Exceptions\DataStreamOpenException;

/**
 * streams the raw data without processing - used for default data package resources.
 */
class DefaultDataStream extends BaseDataStream
{
    public $fopenResource;

  /**
   * @param string $dataSource
   *
   * @param null $dataSourceOptions
   *
   * @throws \frictionlessdata\datapackage\Exceptions\DataStreamOpenException
   */
    public function __construct(string $dataSource, $dataSourceOptions = null)
    {
        parent::__construct($dataSource, $dataSourceOptions);
        try {
            $this->fopenResource = fopen($this->dataSource, 'r');
        } catch (\Exception $e) {
            throw new DataStreamOpenException('Failed to open data source '.json_encode($this->dataSource).': '.json_encode($e->getMessage()));
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

    public function save($filename)
    {
        $target = fopen($filename, 'w');
        stream_copy_to_stream($this->fopenResource, $target);
        fclose($target);
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
}
