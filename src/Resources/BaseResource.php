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
     *
     * @param object      $descriptor
     * @param null|string $basePath
     *
     * @throws ResourceValidationFailedException
     */
    public function __construct($descriptor, $basePath, $skipValidations = false)
    {
        $this->basePath = $basePath;
        $this->descriptor = Utils::objectify($descriptor);
        $this->skipValidations = $skipValidations;
        if (!$this->skipValidations) {
            $validationErrors = $this->validateResource();
            if (count($validationErrors) > 0) {
                throw new ResourceValidationFailedException($validationErrors);
            }
        }
    }

    public static function handlesDescriptor($descriptor)
    {
        return static::handlesProfile(Registry::getResourceValidationProfile($descriptor));
    }

    public function read($readOptions=null)
    {
        $limit = ($readOptions && isset($readOptions["limit"])) ? $readOptions["limit"] : null;
        $rows = [];
        foreach ($this->dataStreams() as $dataStream) {
            if (isset($dataStream->table)) {
                $readOptions["limit"] = $limit;
                foreach ($dataStream->table->read($readOptions) as $row) {
                    $rows[] = $row;
                    if ($limit !== null) {
                        $limit--;
                        if ($limit < 0) break;
                    }
                };
            } else {
                foreach ($dataStream as $row) {
                    $rows[] = $row;
                    if ($limit !== null) {
                        $limit--;
                        if ($limit < 0) break;
                    }
                }
            }
            if ($limit !== null && $limit < 0) break;
        }
        return $rows;
    }

    public function dataStreams()
    {
        if (is_null($this->dataStreams)) {
            $this->dataStreams = [];
            foreach ($this->path() as $path) {
                $this->dataStreams[] = $this->getDataStream($path);
            }
            $data = $this->data();
            if ($data) {
                $this->dataStreams[] = $this->getInlineDataStream($data);
            }
        }

        return $this->dataStreams;
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

    public function path()
    {
        if (isset($this->descriptor()->path)) {
            $path = $this->descriptor()->path;
            if (!is_array($path)) {
                $path = [$path];
            }

            return $path;
        } else {
            return [];
        }
    }

    public function data()
    {
        return isset($this->descriptor()->data) ? $this->descriptor()->data : null;
    }

    // standard iterator functions - to iterate over the data sources
    public function rewind()
    {
        $this->dataStreams = null;
        $this->currentDataStream = 0;
        foreach ($this->dataStreams() as $dataStream) {
            $dataStream->rewind();
        }
    }

    public function current()
    {
        return $this->dataStreams()[$this->currentDataStream]->current();
    }

    public function key()
    {
        return $this->dataStreams()[$this->currentDataStream]->key();
    }

    public function next()
    {
        return $this->dataStreams()[$this->currentDataStream]->next();
    }

    public function valid()
    {
        $dataStreams = $this->dataStreams();
        if ($dataStreams[$this->currentDataStream]->valid()) {
            // current data stream is still valid
            return true;
        } else {
            ++$this->currentDataStream;
            if (isset($dataStreams[$this->currentDataStream])) {
                // current data stream is done, but we have another data stream
                return true;
            } else {
                // no more data and no more data streams
                return false;
            }
        }
    }

    public function getFileExtension()
    {
        return '';
    }

    public function save($baseFilename)
    {
        $dataStreams = $this->dataStreams();
        $numDataStreams = count($dataStreams);
        $fileNames = [];
        $i = 0;
        foreach ($dataStreams as $dataStream) {
            if ($numDataStreams == 1) {
                $filename = $baseFilename.$this->getFileExtension();
            } else {
                $filename = $baseFilename.'-data-'.$i.$this->getFileExtension();
            }
            $fileNames[] = $filename;
            $dataStream->save($filename);
            ++$i;
        }

        return $fileNames;
    }

    public static function validateDataSource($dataSource, $basePath = null)
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
     * used by unit tests to add a mock http source.
     *
     * @param string $dataSource
     * @param string $basePath
     *
     * @return string
     */
    public static function normalizeDataSource($dataSource, $basePath = null)
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
    protected $skipValidations = false;
    protected $currentDataPosition = 0;
    protected $currentDataStream = 0;
    protected $dataStreams = null;

    protected function validateResource()
    {
        return ResourceValidator::validate($this->descriptor(), $this->basePath);
    }

    /**
     * @param string $dataSource
     *
     * @return BaseDataStream
     */
    abstract protected function getDataStream($dataSource);

    abstract protected function getInlineDataStream($data);

    protected static function handlesProfile($profile)
    {
        return false;
    }
}
