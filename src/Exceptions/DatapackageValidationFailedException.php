<?php
namespace frictionlessdata\datapackage\Exceptions;

use frictionlessdata\datapackage\Validators\DatapackageValidationError;


class DatapackageValidationFailedException extends \Exception
{
    public $validationErrors;

    public function __construct($validationErrors)
    {
        $this->validationErrors = $validationErrors;
        parent::__construct("DefaultDatapackage validation failed: ".DatapackageValidationError::getErrorMessages($validationErrors));
    }
}
