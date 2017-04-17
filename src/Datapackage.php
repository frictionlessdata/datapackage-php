<?php namespace frictionlessdata\datapackage;
use Symfony\Component\Config\Definition\Exception\Exception;


/**
 * Datapackage representation, supports loading from the following sources:
 *  - native PHP object containing the descriptor
 *  - JSON encoded object
 *  - URL (must be in either 'http' or 'https' schemes)
 *  - local filesystem (POSIX) path
 */
class Datapackage implements \Iterator
{
    protected $_descriptor;
    protected $_currentResourcePosition = 0;
    protected $_basePath;

    public function __construct($source, $basePath=null)
    {
        if (is_object($source)) {
            $this->_descriptor = $source;
            $this->_basePath = $basePath;
        } elseif (is_string($source)) {
            if (Utils::is_json_string($source)) {
                $this->_descriptor = json_decode($source);
                $this->_basePath = $basePath;
            } elseif (Utils::is_http_source($source)) {
                $this->_descriptor = json_decode(file_get_contents($source));
                // http sources don't allow relative paths, hence basePath should remain null
                $this->_basePath = null;
            } else {
                if (empty($basePath)) {
                    $this->_basePath = dirname($source);
                } else {
                    $this->_basePath = $basePath;
                    $absPath = $this->_basePath.DIRECTORY_SEPARATOR.$source;
                    if (file_exists($absPath)) {
                        $source = $absPath;
                    }
                }
                $this->_descriptor = json_decode(file_get_contents($source));
            }
        } else {
            throw new Exception("Invalid source: ".json_encode($source));
        }
    }

    protected function _initResource($resourceDescriptor)
    {
        return new Resource($resourceDescriptor, $this->_basePath);
    }

    public function descriptor()
    {
        return $this->_descriptor;
    }

    // standard iterator functions - to iterate over the resources
    public function rewind() { $this->_currentResourcePosition = 0; }
    public function current() { return $this->_initResource($this->descriptor()->resources[$this->_currentResourcePosition]); }
    public function key() { return $this->_currentResourcePosition; }
    public function next() { $this->_currentResourcePosition++; }
    public function valid() { return isset($this->descriptor()->resources[$this->_currentResourcePosition]); }
}
