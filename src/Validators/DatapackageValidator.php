<?php namespace frictionlessdata\datapackage\Validators;

use frictionlessdata\datapackage\Repository;

/**
 * validate a datapackage descriptor
 * checks the profile attribute to determine which schema to validate with
 */
class DatapackageValidator extends BaseValidator
{
    protected function getSchemaValidationErrorClass()
    {
        return "frictionlessdata\\datapackage\\Validators\\DatapackageValidationError";
    }

    protected function getValidationProfile()
    {
        return Repository::getDatapackageValidationProfile($this->descriptor);
    }
}
