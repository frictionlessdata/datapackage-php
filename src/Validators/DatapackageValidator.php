<?php namespace frictionlessdata\datapackage\Validators;

use frictionlessdata\datapackage\Registry;
use frictionlessdata\datapackage\Validators\ResourceValidator;

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
        return Registry::getDatapackageValidationProfile($this->descriptor);
    }

    protected function validateKeys()
    {
        foreach ($this->descriptor->resources as $resourceDescriptor) {
            foreach ($this->resourceValidate($resourceDescriptor) as $error) {
                $this->errors[] = $error;
            }
        }
    }

    protected function resourceValidate($resourceDescriptor)
    {
        return ResourceValidator::validate($resourceDescriptor, $this->basePath);
    }

    protected function getJsonSchemaFileFromRegistry($profile)
    {
        if ($filename = Registry::getDatapackageJsonSchemaFile($profile)) {
            return $filename;
        } else {
            return parent::getJsonSchemaFileFromRegistry($profile);
        }
    }
}
