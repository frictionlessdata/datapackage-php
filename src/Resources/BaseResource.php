<?php
namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\BaseDataStream;
use frictionlessdata\datapackage\Validators\ResourceValidator;
use frictionlessdata\datapackage\Exceptions\ResourceValidationFailedException;
use frictionlessdata\datapackage\Utils;

abstract class BaseResource implements \Iterator
{
    /**
     * BaseResource constructor.
     * @param object $descriptor
     * @param null|string $basePath
     * @throws ResourceValidationFailedException
     */
    public function __construct($descriptor, $basePath)
    {
        $this->basePath = $basePath;
        $this->descriptor = $descriptor;
        $validationErrors = ResourceValidator::validate($this->descriptor());
        if (count($validationErrors) > 0) {
            throw new ResourceValidationFailedException($validationErrors);
        }
    }

    /**
     * @return object
     */
    public function descriptor()
    {
        return $this->descriptor;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->descriptor()->name;
    }

    // standard iterator functions - to iterate over the data sources
    public function rewind() { $this->currentDataPosition = 0; }
    public function current() { return $this->getDataStream($this->descriptor()->data[$this->currentDataPosition]); }
    public function key() { return $this->currentDataPosition; }
    public function next() { $this->currentDataPosition++; }
    public function valid() { return isset($this->descriptor()->data[$this->currentDataPosition]); }

    protected $descriptor;
    protected $basePath;
    protected $currentDataPosition = 0;

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     *
     * @param string $dataSource
     * @return string
     */
    protected function normalizeDataSource($dataSource)
    {
        if (!empty($this->basePath) && !Utils::isHttpSource($dataSource)) {
            // TODO: support JSON pointers
            $absPath = $this->basePath.DIRECTORY_SEPARATOR.$dataSource;
            if (file_exists($absPath)) {
                $dataSource = $absPath;
            }
        }
        return $dataSource;
    }

    /**
     * @param string $dataSource
     * @return BaseDataStream
     */
    abstract protected function getDataStream($dataSource);
}
