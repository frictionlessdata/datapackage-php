<?php
namespace frictionlessdata\datapackage;

/**
 * Provides a standard interface for streaming
 *
 * functionality could mostly be replaced by php generators (http://php.net/manual/en/language.generators.syntax.php)
 * however, they are only supported on PHP 5.5 and above
 */
class DataStream implements \Iterator
{
    /**
     * DataStream constructor.
     * @param string $dataSource
     * @throws Exceptions\DataStreamOpenException
     */
    public function __construct($dataSource)
    {
        try {
            $this->fopenResource = fopen($dataSource, "r");
        } catch (\Exception $e) {
            throw new Exceptions\DataStreamOpenException("Failed to open source ".json_encode($dataSource).": ".json_encode($e->getMessage()));
        }
    }

    public function __destruct()
    {
        fclose($this->fopenResource);
    }

    public function rewind() {
        if ($this->currentLineNumber != 0) {
            throw new \Exception("DataStream does not support rewind, sorry");
        }
    }

    public function current() {
        $line = fgets($this->fopenResource);
        if ($line === false) {
            return "";
        } else {
            return $line;
        }
    }

    public function key() {
        return $this->currentLineNumber;
    }

    public function next() {
        $this->currentLineNumber++;
    }

    public function valid() {
        return (!feof($this->fopenResource));
    }

    protected $currentLineNumber = 0;
    protected $fopenResource;
    protected $dataSource;
}
