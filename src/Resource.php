<?php namespace frictionlessdata\datapackage;


class Resource implements \Iterator
{
    protected $_descriptor;
    protected $_basePath;
    protected $_currentDataPosition = 0;

    public function __construct($descriptor, $basePath)
    {
        $this->_basePath = $basePath;
        $this->_descriptor = $descriptor;
    }

    protected function _isHttpSource($dataSource)
    {
        return Utils::is_http_source($dataSource);
    }

    protected function _normalizeDataSource($dataSource)
    {
        if (!empty($this->_basePath) && !Utils::is_http_source($dataSource)) {
            // TODO: support JSON pointers
            $absPath = $this->_basePath.DIRECTORY_SEPARATOR.$dataSource;
            if (file_exists($absPath)) {
                $dataSource = $absPath;
            }
        }
        return $dataSource;
    }

    protected function _getDataStream($dataSource)
    {
        return new DataStream($this->_normalizeDataSource($dataSource));
    }

    public function descriptor()
    {
        return $this->_descriptor;
    }

    public function name()
    {
        return $this->descriptor()->name;
    }

    // standard iterator functions - to iterate over the data sources
    public function rewind() { $this->_currentDataPosition = 0; }
    public function current() { return $this->_getDataStream($this->descriptor()->data[$this->_currentDataPosition]); }
    public function key() { return $this->_currentDataPosition; }
    public function next() { $this->_currentDataPosition++; }
    public function valid() { return isset($this->descriptor()->data[$this->_currentDataPosition]); }
}
