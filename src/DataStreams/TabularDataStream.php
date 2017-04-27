<?php
namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\datapackage\Exceptions\DataStreamValidationException;
use frictionlessdata\datapackage\Exceptions\DataStreamOpenException;
use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\Table;
use frictionlessdata\tableschema\Exceptions\TableRowValidationException;


class TabularDataStream extends BaseDataStream
{
    public function __construct($dataSource, $schema=null)
    {
        if (empty($schema)) {
            throw new \Exception("schema is required for tabular data stream");
        } else {
            try {
                $dataSource = new CsvDataSource($dataSource);
                $schema = new Schema($schema);
                $this->table = new Table($dataSource, $schema);
            } catch (\Exception $e) {
                throw new DataStreamOpenException("Failed to open tabular data source ".json_encode($dataSource).": ".json_encode($e->getMessage()));
            }
        }
    }

    protected $table;

    public function rewind() {
        $this->table->rewind();
    }

    /**
     * @return array
     * @throws DataStreamValidationException
     */
    public function current() {
        try {
            return $this->table->current();
        } catch (TableRowValidationException $e) {
            throw new DataStreamValidationException($e->getMessage());
        }
    }

    public function key() {
        return $this->table->key();
    }

    public function next() {
        $this->table->next();
    }

    public function valid() {
        return $this->table->valid();
    }
}
