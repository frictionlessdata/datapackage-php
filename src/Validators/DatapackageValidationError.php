<?php namespace frictionlessdata\datapackage\Validators;

/**
 * Represents a validation error encountered when validating a datapackage descriptor
 */
class DatapackageValidationError extends BaseValidationError
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
