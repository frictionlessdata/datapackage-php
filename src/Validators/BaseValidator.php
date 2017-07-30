<?php

namespace frictionlessdata\datapackage\Validators;

use frictionlessdata\datapackage\Registry;
use frictionlessdata\datapackage\Utils;
use frictionlessdata\tableschema\SchemaValidator;
use frictionlessdata\tableschema\SchemaValidationError;

abstract class BaseValidator extends SchemaValidator
{
    public function __construct($descriptor, $basePath = null)
    {
        $this->basePath = $basePath;
        parent::__construct($descriptor);
    }

    /**
     * validate a descriptor.
     *
     * @param object $descriptor
     * @param string $basePath
     *
     * @return \frictionlessdata\tableschema\SchemaValidationError[]
     */
    public static function validate($descriptor, $basePath = null)
    {
        $validator = new static($descriptor, $basePath);

        return $validator->getValidationErrors();
    }

    protected $basePath;

    /**
     * should be implemented properly by extending classes
     * should return the profile used for validation
     * if using the default getValidationSchemaUrl function - this value should correspond to a file in schemas/ directory.
     *
     * @return string
     */
    protected function getValidationProfile()
    {
        return $this->descriptor->profile;
    }

    protected function convertValidationSchemaFilenameToUrl($filename)
    {
        $filename = realpath($filename);
        if (file_exists($filename)) {
            return 'file://'.$filename;
        } else {
            throw new \Exception("failed to find schema file: '{$filename}' for descriptor ".json_encode($this->descriptor));
        }
    }

    protected function getJsonSchemaFileFromRegistry($profile)
    {
        return false;
    }

    /**
     * get the url which the schema for validation can be fetched from.
     *
     * @return string
     */
    protected function getValidationSchemaUrl()
    {
        $profile = $this->getValidationProfile();
        if ($filename = $this->getJsonSchemaFileFromRegistry($profile)) {
            // known profile id in the registry
            return $this->convertValidationSchemaFilenameToUrl($filename);
        } elseif (Utils::isHttpSource($profile)) {
            // url
            return $profile;
        } elseif (file_exists($filename = $this->basePath.DIRECTORY_SEPARATOR.$profile)) {
            // relative path - prefixed with basePath
            return $this->convertValidationSchemaFilenameToUrl($filename);
        } else {
            // absolute path (or relative to current working directory)
            return $this->convertValidationSchemaFilenameToUrl($profile);
        }
    }

    /**
     * Allows to specify different error classes for different validators.
     *
     * @return string
     */
    protected function getSchemaValidationErrorClass()
    {
        return 'frictionlessdata\\tableschema\\SchemaValidationError';
    }

    /**
     * allows extending classes to modify the descriptor before passing to the validator.
     *
     * @return object
     */
    protected function getDescriptorForValidation()
    {
        return $this->descriptor;
    }

    /**
     * conver the validation error message received from JsonSchema to human readable string.
     *
     * @param array $error
     *
     * @return string
     */
    protected function getValidationErrorMessage($error)
    {
        return sprintf('[%s] %s', $error['property'], $error['message']);
    }

    /**
     * does the validation, adds errors to the validator object using _addError method.
     */
    protected function validateSchema()
    {
        $this->validateSchemaUrl($this->getValidationSchemaUrl());
    }

    protected function validateSchemaUrl($url)
    {
        $validator = new \JsonSchema\Validator();
        $descriptor = $this->getDescriptorForValidation();
        $validator->validate($descriptor, (object) ['$ref' => $url]);
        if (!$validator->isValid()) {
            foreach ($validator->getErrors() as $error) {
                $this->addError(
                    SchemaValidationError::SCHEMA_VIOLATION,
                    $this->getValidationErrorMessage($error)
                );
            }
        }
    }

    /**
     * Add an error to the validator object - errors are aggregated and returned by validate function.
     *
     * @param int        $code
     * @param null|mixed $extraDetails
     */
    protected function addError($code, $extraDetails = null)
    {
        // modified from parent function to support changing the error class
        $errorClass = $this->getSchemaValidationErrorClass();
        $this->errors[] = new $errorClass($code, $extraDetails);
    }

    protected function validateKeys()
    {
        // this can be used to do further validations on $this->descriptor
        // it will run only in case validateSchema succeeded
        // so no need to check if attribute exists or in correct type
        // the parent SchemaValidator does some checks specific to table schema - so don't call the parent function
    }
}
