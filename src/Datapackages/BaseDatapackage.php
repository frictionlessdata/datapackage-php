<?php
namespace frictionlessdata\datapackage\Datapackages;

use frictionlessdata\datapackage\Factory;
use frictionlessdata\datapackage\Validators\DatapackageValidator;
use frictionlessdata\datapackage\Exceptions\DatapackageValidationFailedException;

abstract class BaseDatapackage implements \Iterator
{
    /**
     * BaseDatapackage constructor.
     * @param object $descriptor
     * @param null|string $basePath
     * @throws DatapackageValidationFailedException
     */
    public function __construct($descriptor, $basePath=null)
    {
        $this->descriptor = $descriptor;
        $this->basePath = $basePath;
        $validationErrors = $this->datapackageValidate();
        if (count($validationErrors) > 0) {
            throw new DatapackageValidationFailedException($validationErrors);
        }
    }

    /**
     * returns the descriptor as-is, without adding default values or normalizing
     * @return object
     */
    public function descriptor()
    {
        return $this->descriptor;
    }

    // standard iterator functions - to iterate over the resources
    public function rewind() {$this->currentResourcePosition = 0;}
    public function current() { return $this->initResource($this->descriptor()->resources[$this->currentResourcePosition]); }
    public function key() { return $this->currentResourcePosition; }
    public function next() { $this->currentResourcePosition++; }
    public function valid() { return isset($this->descriptor()->resources[$this->currentResourcePosition]); }

    protected $descriptor;
    protected $currentResourcePosition = 0;
    protected $basePath;

    /**
     * called by the resources iterator for each iteration
     *
     * @param object $descriptor
     * @return \frictionlessdata\datapackage\Resources\BaseResource
     */
    protected function initResource($descriptor)
    {
        return Factory::resource($descriptor, $this->basePath);
    }

    protected function datapackageValidate()
    {
        return DatapackageValidator::validate($this->descriptor(), $this->basePath);
    }
}
