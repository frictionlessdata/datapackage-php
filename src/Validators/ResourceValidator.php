<?php namespace frictionlessdata\datapackage\Validators;

/**
 * validate a resource descriptor
 * checks the profile attribute to determine which schema to validate with
 *
 * currently works by wrapping the resource descriptor inside a minimal valid datapackage descriptor
 * then validating the datapackage
 */
class ResourceValidator extends DatapackageValidator
{
    protected function getSchemaValidationErrorClass()
    {
        return ResourceValidationError::class;
    }

    protected function getValidationProfile()
    {
        $profile = parent::getValidationProfile();
        if ($profile == "tabular-data-resource") {
            $profile = "tabular-data-package";
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
        str_replace("resources[0].", "", $property);
        return sprintf("[%s] %s", $property, $error['message']);
    }
}
