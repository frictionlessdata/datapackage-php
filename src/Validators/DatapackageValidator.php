<?php namespace frictionlessdata\datapackage\Validators;

/**
 * validate a datapackage descriptor
 * checks the profile attribute to determine which schema to validate with
 */
class DatapackageValidator extends BaseValidator
{
    protected function getSchemaValidationErrorClass()
    {
        return DatapackageValidationError::class;
    }
}
