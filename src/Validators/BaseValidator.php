<?php
namespace frictionlessdata\datapackage\Validators;

use frictionlessdata\tableschema\SchemaValidator;
use frictionlessdata\tableschema\SchemaValidationError;

abstract class BaseValidator extends SchemaValidator
{
    /**
     * validate a descriptor
     * @param object $descriptor
     * @return SchemaValidationError[]
     */
    public static function validate($descriptor)
    {
        $validator = new static($descriptor);
        return $validator->getValidationErrors();
    }

    /**
     * should be implemented properly by extending classes
     * should return the profile used for validation
     * if using the default getValidationSchemaUrl function - this value should correspond to a file in schemas/ directory
     * @return string
     */
    protected function getValidationProfile()
    {
        return $this->descriptor->profile;
    }

    /**
     * get the url which the schema for validation can be fetched from
     * @return string
     */
    protected function getValidationSchemaUrl()
    {
        // TODO: support loading from url
        return 'file://' . realpath(dirname(__FILE__))."/schemas/".$this->getValidationProfile().".json";
    }

    /**
     * Allows to specify different error classes for different validators
     * @return string
     */
    protected function getSchemaValidationErrorClass()
    {
        return "frictionlessdata\\tableschema\\SchemaValidationError";
    }

    /**
     * allows extending classes to modify the descriptor before passing to the validator
     * @return object
     */
    protected function getDescriptorForValidation()
    {
        return $this->descriptor;
    }

    /**
     * conver the validation error message received from JsonSchema to human readable string
     * @param array $error
     * @return string
     */
    protected function getValidationErrorMessage($error)
    {
        return sprintf("[%s] %s", $error['property'], $error['message']);
    }

    /**
     * does the validation, adds errors to the validator object using _addError method
     */
    protected function validateSchema()
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
                $this->addError(
                    SchemaValidationError::SCHEMA_VIOLATION,
                    $this->getValidationErrorMessage($error)
                );
            }
        }
    }

    /**
     * Add an error to the validator object - errors are aggregated and returned by validate function
     * @param integer $code
     * @param null|mixed $extraDetails
     */
    protected function addError($code, $extraDetails=null)
    {
        $errorClass = $this->getSchemaValidationErrorClass();
        $this->errors[] = new $errorClass($code, $extraDetails);
    }
}
