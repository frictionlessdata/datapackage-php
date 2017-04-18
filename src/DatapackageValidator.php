<?php namespace frictionlessdata\datapackage;

// TODO: move the SchemaValidation logic to it's own independent package
use frictionlessdata\tableschema;

class DatapackageValidator extends tableschema\SchemaValidator
{
    public static function validate($descriptor)
    {
        $validator = new self($descriptor);
        return $validator->get_validation_errors();
    }

    protected function _validateSchema()
    {
        // Validate
        $validator = new \JsonSchema\Validator();
        $validator->validate(
            $this->descriptor, (object)[
                '$ref' => 'file://' . realpath(dirname(__FILE__)).'/schemas/data-package.json'
            ]
        );
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->_addError(
                    DatapackageValidationError::SCHEMA_VIOLATION,
                    sprintf("[%s] %s", $error['property'], $error['message'])
                );
            }
        }
    }
}
