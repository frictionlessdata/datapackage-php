<?php namespace frictionlessdata\datapackage\Validators;

use frictionlessdata\tableschema\SchemaValidator;
use frictionlessdata\tableschema\SchemaValidationError;

abstract class BaseValidator extends SchemaValidator
{
    /**
     * validate a descriptor
     * @param object $descriptor
     * @return array of validation error objects
     */
    public static function validate($descriptor)
    {
        $validator = new static($descriptor);
        return $validator->get_validation_errors();
    }

    protected function getValidationProfile()
    {
        if (isset($this->descriptor->profile) && $this->descriptor->profile != "default") {
            return $this->descriptor->profile;
        } else {
            return "data-package";
        }
    }

    /**
     * get the url which the schema for validation can be fetched from
     *
     * @return string
     */
    protected function getValidationSchemaUrl()
    {
        // TODO: use a registry, support loading url to schema file
        return 'file://' . realpath(dirname(__FILE__))."/schemas/".$this->getValidationProfile().".json";
    }

    protected function getSchemaValidationErrorClass()
    {
        return SchemaValidationError::class;
    }

    protected function getDescriptorForValidation()
    {
        return $this->descriptor;
    }

    protected function getValidationErrorMessage($error)
    {
        return sprintf("[%s] %s", $error['property'], $error['message']);
    }

    protected function _validateSchema()
    {
        $validator = new \JsonSchema\Validator();
        $descriptor = $this->getDescriptorForValidation();
        $validator->validate(
            $descriptor,
            (object)[
                "\$ref" => $this->getValidationSchemaUrl()
            ]
        );
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->_addError(
                    ($this->getSchemaValidationErrorClass())::SCHEMA_VIOLATION,
                    $this->getValidationErrorMessage($error)
                );
            }
        }
    }

    protected function _addError($code, $extraDetails=null)
    {
        $errorClass = $this->getSchemaValidationErrorClass();
        $this->errors[] = new $errorClass($code, $extraDetails);
    }
}
