<?php
namespace frictionlessdata\datapackage;

class Resource implements \Iterator
{
    public function __construct($descriptor, $basePath)
    {
        $this->basePath = $basePath;
        $this->descriptor = $descriptor;
    }

    public function descriptor()
    {
        return $this->descriptor;
    }

    public function name()
    {
        return $this->descriptor()->name;
    }

    // standard iterator functions - to iterate over the data sources
    public function rewind() { $this->_currentDataPosition = 0; }
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
     * @return bool
     */
    protected function isHttpSource($dataSource)
    {
        return Utils::isHttpSource($dataSource);
    }

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     *
     * @param string $dataSource
     * @return string
     */
    protected function normalizeDataSource($dataSource)
    {
        if (!empty($this->basePath) && !$this->isHttpSource($dataSource)) {
            // TODO: support JSON pointers
            $absPath = $this->basePath.DIRECTORY_SEPARATOR.$dataSource;
            if (file_exists($absPath)) {
                $dataSource = $absPath;
            }
        }
        return $dataSource;
    }

    protected function getDataStream($dataSource)
    {
        return new DataStream($this->normalizeDataSource($dataSource));
    }
}
