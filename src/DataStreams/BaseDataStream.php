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
    /**
     * @param string $dataSource
     * @param mixed  $dataSourceOptions
     *
     * @throws \frictionlessdata\datapackage\Exceptions\DataStreamOpenException
     */
    abstract public function __construct($dataSource, $dataSourceOptions = null);

    /**
     * @return mixed
     *
     * @throws \frictionlessdata\datapackage\Exceptions\DataStreamValidationException
     */
    abstract public function current();
}
