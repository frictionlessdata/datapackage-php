<?php

namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\datapackage\Exceptions\DataStreamValidationException;
use frictionlessdata\datapackage\Exceptions\DataStreamOpenException;
use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\tableschema\Schema;
use frictionlessdata\tableschema\Table;
use frictionlessdata\tableschema\Exceptions\FieldValidationException;
use frictionlessdata\tableschema\Exceptions\DataSourceException;

class TabularDataStream extends BaseDataStream
{
    public $table;
    public $schema;

    public function __construct($dataSource, $dataSourceOptions = null)
    {
        parent::__construct($dataSource, $dataSourceOptions);
        $schema = $this->dataSourceOptions;
        if (empty($schema)) {
            throw new \Exception('schema is required for tabular data stream');
        } else {
            try {
                $this->schema = new Schema($schema);
                $this->table = new Table($this->getDataSourceObject(), $this->schema);
            } catch (\Exception $e) {
                throw new DataStreamOpenException('Failed to open tabular data source '.json_encode($dataSource).': '.json_encode($e->getMessage()));
            }
        }
    }

    protected function getDataSourceObject()
    {
        return new CsvDataSource($this->dataSource);
    }

    public function rewind()
    {
        $this->table->rewind();
    }

    public function save($filename)
    {
        $this->table->save($filename);
    }

    /**
     * @return array
     *
     * @throws DataStreamValidationException
     */
    public function current()
    {
        try {
            return $this->table->current();
        } catch (DataSourceException $e) {
            throw new DataStreamValidationException($e->getMessage());
        } catch (FieldValidationException $e) {
            throw new DataStreamValidationException($e->getMessage());
        }
    }

    public function key()
    {
        return $this->table->key();
    }

    public function next()
    {
        $this->table->next();
    }

    public function valid()
    {
        return $this->table->valid();
    }
}
