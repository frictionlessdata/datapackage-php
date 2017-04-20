<?php
namespace frictionlessdata\datapackage\DataStreams;

use frictionlessdata\datapackage\Exceptions\DataStreamValidationException;

class TabularDataStream extends DefaultDataStream
{
    public function __construct($dataSource, $schema)
    {
        parent::__construct($dataSource);
        // TODO: change to use table schema object
        // $this->schemaIterator = $schema->rawDataIterator();
        $this->schemaIterator = (object)[
            "schema" => $schema,
            "lineNum" => 0,
            "sampleLines" => [],
            "numPeekLines" => 10,
            "headerLine" => null
        ];
    }

    public function schemaIterator()
    {
        return $this->schemaIterator;
    }

    protected $schemaIterator;

    protected function processLine($lineNum, $line)
    {
        // TODO: change to use table schema object
        // return $this->schemaIterator->processLine($lineNum, $line);
        if ($lineNum == 1) {
            $this->schemaIterator()->headerLine = str_getcsv($line);
            $this->next();
            if ($this->valid()) {
                return $this->current();
            } else {
                return null;
            }
        } else {
            $line = str_getcsv($line);
            if (count($this->schemaIterator()->headerLine) != count($line)) {
                throw new DataStreamValidationException(
                    "mismatch in header and line number of columns"
                    ." headerLine=".json_encode($this->schemaIterator()->headerLine)
                    ." line=".json_encode($line)
                );
            }
            $res = [];
            $i = 0;
            foreach ($this->schemaIterator()->headerLine as $field) {
                $fieldSchema = $this->schemaIterator()->schema->fields[$i];
                $val = $line[$i];
                if ($fieldSchema->type == "integer" && !is_numeric($val)) {
                    throw new DataStreamValidationException(
                        "invalid value for field {$field}: should be integer, actual: ".json_encode($val)
                    );
                }
                $res[$field] = $val;
                $i++;
            }
            return $res;
        }
    }
}
