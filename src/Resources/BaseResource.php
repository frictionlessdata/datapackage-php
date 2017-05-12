<?php
namespace frictionlessdata\datapackage\Resources;

use frictionlessdata\datapackage\DataStreams\BaseDataStream;
use frictionlessdata\datapackage\Registry;
use frictionlessdata\datapackage\Validators\ResourceValidationError;
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
        $validationErrors = $this->validateResource();
        if (count($validationErrors) > 0) {
            throw new ResourceValidationFailedException($validationErrors);
        }
    }

    public static function handlesDescriptor($descriptor)
    {
        return static::handlesProfile(Registry::getResourceValidationProfile($descriptor));
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

    public static function validateDataSource($dataSource, $basePath=null)
    {
        $errors = [];
        $dataSource = static::normalizeDataSource($dataSource, $basePath);
        if (!Utils::isHttpSource($dataSource) && !file_exists($dataSource)) {
            $errors[] = new ResourceValidationError(
                ResourceValidationError::SCHEMA_VIOLATION,
                "data source file does not exist or is not readable: {$dataSource}"
            );
        }
        return $errors;
    }

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     * @param string $dataSource
     * @param string $basePath
     * @return string
     */
    public static function normalizeDataSource($dataSource, $basePath=null)
    {
        if (!empty($basePath) && !Utils::isHttpSource($dataSource)) {
            // TODO: support JSON pointers
            $absPath = $basePath.DIRECTORY_SEPARATOR.$dataSource;
            if (file_exists($absPath)) {
                $dataSource = $absPath;
            }
        }
        return $dataSource;
    }

    protected $descriptor;
    protected $basePath;
    protected $currentDataPosition = 0;

    protected function validateResource()
    {
        return ResourceValidator::validate($this->descriptor(), $this->basePath);
    }

    /**
     * @param string $dataSource
     * @return BaseDataStream
     */
    abstract protected function getDataStream($dataSource);

    protected static function handlesProfile($profile)
    {
        return false;
    }
}
