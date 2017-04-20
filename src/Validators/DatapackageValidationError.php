<?php namespace frictionlessdata\datapackage\Validators;

// TODO: move the SchemaValidation logic to it's own independent package
use frictionlessdata\tableschema;

/**
 * Represents a validation error encountered when validating a datapackage descriptor
 */
class DatapackageValidationError extends tableschema\SchemaValidationError
{
    const RESOURCE_FAILED_VALIDATION=100;

    public function getMessage()
    {
        switch ($this->code) {
            case static::RESOURCE_FAILED_VALIDATION:
                return "DefaultResource {$this->extraDetails['resource']} failed validation: "
                    .ResourceValidationError::getErrorMessages($this->extraDetails["validationErrors"]);
            default:
                return parent::getMessage();
        }
    }
}
