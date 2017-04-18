<?php
namespace frictionlessdata\datapackage;

/**
 * Datapackage representation, supports loading from the following sources:
 *  - native PHP object containing the descriptor
 *  - JSON encoded object
 *  - URL (must be in either 'http' or 'https' schemes)
 *  - local filesystem (POSIX) path
 */
class Datapackage implements \Iterator
{
    public function __construct($source, $basePath=null)
    {
        if (is_object($source)) {
            $this->descriptor = $source;
            $this->basePath = $basePath;
        } elseif (is_string($source)) {
            if (Utils::isJsonString($source)) {
                try {
                    $this->descriptor = json_decode($source);
                } catch (\Exception $e) {
                    throw new Exceptions\DatapackageInvalidSourceException(
                        "Failed to load source: ".json_encode($source).": ".$e->getMessage()
                    );
                }
                $this->basePath = $basePath;
            } elseif ($this->isHttpSource($source)) {
                try {
                    $this->descriptor = json_decode(file_get_contents($this->normalizeHttpSource($source)));
                } catch (\Exception $e) {
                    throw new Exceptions\DatapackageInvalidSourceException(
                        "Failed to load source: ".json_encode($source).": ".$e->getMessage()
                    );
                }
                // http sources don't allow relative paths, hence basePath should remain null
                $this->basePath = null;
            } else {
                if (empty($basePath)) {
                    $this->basePath = dirname($source);
                } else {
                    $this->basePath = $basePath;
                    $absPath = $this->basePath.DIRECTORY_SEPARATOR.$source;
                    if (file_exists($absPath)) {
                        $source = $absPath;
                    }
                }
                try {
                    $this->descriptor = json_decode(file_get_contents($source));
                } catch (\Exception $e) {
                    throw new Exceptions\DatapackageInvalidSourceException(
                        "Failed to load source: ".json_encode($source).": ".$e->getMessage()
                    );
                }

            }
        } else {
            throw new Exceptions\DatapackageInvalidSourceException(
                "Invalid source: ".json_encode($source)
            );
        }
    }

    public static function validate($source, $basePath=null)
    {
        try {
            $datapackage = new self($source, $basePath);
            return DatapackageValidator::validate($datapackage->descriptor());
        } catch (\Exception $e) {
            return [new DatapackageValidationError(DatapackageValidationError::LOAD_FAILED, $e->getMessage())];
        }

    }

    /**
     * get the descriptor as a native PHP object
     *
     * @return object
     */
    public function descriptor()
    {
        return $this->descriptor;
    }

    // standard iterator functions - to iterate over the resources
    public function rewind() {$this->currentResourcePosition = 0;}
    public function current() { return $this->initResource($this->descriptor()->resources[$this->currentResourcePosition]); }
    public function key() { return $this->currentResourcePosition; }
    public function next() { $this->currentResourcePosition++; }
    public function valid() { return isset($this->descriptor()->resources[$this->currentResourcePosition]); }

    protected $descriptor;
    protected $currentResourcePosition = 0;
    protected $basePath;

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     *
     * @param string $source
     * @return string
     */
    protected function normalizeHttpSource($source)
    {
        return $source;
    }

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     *
     * @param string $source
     * @return bool
     */
    protected function isHttpSource($source)
    {
        return Utils::isHttpSource($source);
    }

    /**
     * called by the resources iterator for each iteration
     *
     * @param object $resourceDescriptor
     * @return \frictionlessdata\datapackage\Resource
     */
    protected function initResource($resourceDescriptor)
    {
        return new Resource($resourceDescriptor, $this->basePath);
    }
}
