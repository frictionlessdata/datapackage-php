<?php

namespace frictionlessdata\datapackage\DataStreams;

/**
 * Provides a standard interface for streaming from a data stream (Read-only).
 *
 * functionality could mostly be replaced by php generators (http://php.net/manual/en/language.generators.syntax.php)
 * however, they are only supported on PHP 5.5 and above
 */
abstract class BaseDataStream implements \Iterator
{
    public $dataSource;
    public $dataSourceOptions;

    /**
     * @param $dataSource
     * @param mixed $dataSourceOptions
     */
    public function __construct($dataSource, $dataSourceOptions = null)
    {
        $this->dataSource = $dataSource;
        $this->dataSourceOptions = $dataSourceOptions;
    }

    abstract public function save($filename);

    /**
     * @return mixed
     *
     * @throws \frictionlessdata\datapackage\Exceptions\DataStreamValidationException
     */
    abstract public function current();
}
