<?php namespace frictionlessdata\datapackage\Validators;

use frictionlessdata\datapackage\Repository;
use frictionlessdata\datapackage\Factory;

/**
 * validate a resource descriptor
 * checks the profile attribute to determine which schema to validate with
 *
 * currently works by wrapping the resource descriptor inside a minimal valid datapackage descriptor
 * then validating the datapackage
 */
class ResourceValidator extends BaseValidator
{
    protected function getSchemaValidationErrorClass()
    {
        return "frictionlessdata\\datapackage\\Validators\\ResourceValidationError";
    }

    protected function getValidationProfile()
    {
        $profile = Repository::getResourceValidationProfile($this->descriptor);
        if ($profile == "tabular-data-resource") {
            $profile = "tabular-data-package";
        } elseif ($profile == "data-resource") {
            $profile = "data-package";
        }
        return $profile;
    }

    protected function getDescriptorForValidation()
    {
        $descriptor = (object)[
            "name" => "dummy-datapackage-name",
            "resources" => [
                $this->descriptor
            ]
        ];
        if ($this->getValidationProfile() == "tabular-data-package") {
            // profile is required for tabular data package
            $descriptor->profile = "tabular-data-package";
        }
        return $descriptor;
    }

    protected function getValidationErrorMessage($error)
    {
        $property = $error['property'];
        // silly hack to only show properties within the resource of the fake datapackage
        $property = str_replace("resources[0].", "", $property);
        return sprintf("[%s] %s", $property, $error['message']);
    }

    protected function getResourceClass()
    {
        return Factory::getResourceClass($this->descriptor);
    }

    protected function validateKeys()
    {
        $resourceClass = $this->getResourceClass();
        foreach ($this->descriptor->data as $dataSource) {
            foreach ($resourceClass::validateDataSource($dataSource, $this->basePath) as $error) {
                $this->errors[] = $error;
            }
        }
    }
}
