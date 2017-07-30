<?php

namespace frictionlessdata\datapackage\Validators;

/**
 * Represents a validation error encountered when validating a datapackage descriptor.
 */
class DatapackageValidationError extends BaseValidationError
{
    const RESOURCE_FAILED_VALIDATION = 100;
    const DATA_STREAM_FAILURE = 101;

    public function getMessage()
    {
        switch ($this->code) {
            case static::RESOURCE_FAILED_VALIDATION:
                return "resource {$this->extraDetails['resource']} failed validation: "
                    .ResourceValidationError::getErrorMessages($this->extraDetails['validationErrors']);
            case static::DATA_STREAM_FAILURE:
                return "resource {$this->extraDetails['resource']}"
                    //."data stream {$this->extraDetails['dataStream']}"
                    .($this->extraDetails['line']?", line number {$this->extraDetails['line']}":"")
                    .': '.$this->extraDetails['error'];
            default:
                return parent::getMessage();
        }
    }
}
