<?php
namespace frictionlessdata\datapackage\Exceptions;

use frictionlessdata\datapackage\Validators\ResourceValidationError;

class ResourceValidationFailedException extends \Exception
{
    public $validationErrors;

    public function __construct($validationErrors)
    {
        $this->validationErrors = $validationErrors;
        parent::__construct("DefaultResource validation failed: ".ResourceValidationError::getErrorMessages($validationErrors));
    }
}
