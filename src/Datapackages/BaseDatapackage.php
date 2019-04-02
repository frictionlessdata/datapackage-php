<?php

namespace frictionlessdata\datapackage\Datapackages;

use frictionlessdata\datapackage\Factory;
use frictionlessdata\datapackage\Registry;
use frictionlessdata\datapackage\Utils;
use frictionlessdata\datapackage\Validators\DatapackageValidator;
use frictionlessdata\datapackage\Exceptions\DatapackageValidationFailedException;
use Chumper\Zipper\Zipper;

abstract class BaseDatapackage implements \Iterator
{
    /**
     * BaseDatapackage constructor.
     *
     * @param object      $descriptor
     * @param null|string $basePath
     *
     * @throws DatapackageValidationFailedException
     */
    public function __construct($descriptor, $basePath = null, $skipValidations = false)
    {
        $this->descriptor = $descriptor;
        $this->basePath = $basePath;
        $this->skipValidations = $skipValidations;
        if (!$this->skipValidations) {
            $this->revalidate();
        }
    }

    public static function create($name, $resources, $basePath = null)
    {
        $datapackage = new static((object) [
            'name' => $name,
            'resources' => [],
        ], $basePath, true);
        foreach ($resources as $resource) {
            $datapackage->addResource($resource);
        }

        return $datapackage;
    }

    public function revalidate()
    {
        $this->rewind();
        $validationErrors = $this->datapackageValidate();
        if (count($validationErrors) > 0) {
            throw new DatapackageValidationFailedException($validationErrors);
        }
    }

    public static function handlesDescriptor($descriptor)
    {
        return static::handlesProfile(Registry::getDatapackageValidationProfile($descriptor));
    }

    /**
     * returns the descriptor as-is, without adding default values or normalizing.
     *
     * @return object
     */
    public function descriptor()
    {
        return $this->descriptor;
    }

    public function resources()
    {
        $resources = [];
        foreach ($this->descriptor->resources as $resourceDescriptor) {
            $resources[$resourceDescriptor->name] = $this->initResource($resourceDescriptor);
        }

        return $resources;
    }

    public function getResource($name)
    {
        foreach ($this->descriptor->resources as $resourceDescriptor) {
            if ($resourceDescriptor->name == $name) {
                return $this->initResource($resourceDescriptor);
            }
        }
        throw new \Exception("couldn't find matching resource with name =  '{$name}'");
    }

    public function addResource($name, $resource)
    {
        if (is_a($resource, 'frictionlessdata\\datapackage\\Resources\\BaseResource')) {
            $resource = $resource->descriptor();
        } else {
            $resource = Utils::objectify($resource);
        }
        $resource->name = $name;
        $resourceDescriptors = [];
        $gotMatch = false;
        foreach ($this->descriptor->resources as $resourceDescriptor) {
            if ($resourceDescriptor->name == $resource->name) {
                $resourceDescriptors[] = $resource;
                $gotMatch = true;
            } else {
                $resourceDescriptors[] = $resourceDescriptor;
            }
        }
        if (!$gotMatch) {
            $resourceDescriptors[] = $resource;
        }
        $this->descriptor->resources = $resourceDescriptors;
        if (!$this->skipValidations) {
            $this->revalidate();
        }
    }

    // TODO: remove this function and use the getResource / addResource directly (will need to modify a lot of tests code)
    public function resource($name, $resource = null)
    {
        if ($resource) {
            $this->addResource($name, $resource);
        } else {
            return $this->getResource($name);
        }
    }

    public function removeResource($name)
    {
        $resourceDescriptors = [];
        foreach ($this->descriptor->resources as $resourceDescriptor) {
            if ($resourceDescriptor->name != $name) {
                $resourceDescriptors[] = $resourceDescriptor;
            }
        }
        $this->descriptor->resources = $resourceDescriptors;
        if (!$this->skipValidations) {
            $this->revalidate();
        }
    }

    public function saveDescriptor($filename)
    {
        return file_put_contents($filename, json_encode($this->descriptor()));
    }

    // standard iterator functions - to iterate over the resources
    public function rewind()
    {
        $this->currentResourcePosition = 0;
    }

    public function current()
    {
        return $this->initResource($this->descriptor()->resources[$this->currentResourcePosition]);
    }

    public function key()
    {
        return $this->currentResourcePosition;
    }

    public function next()
    {
        ++$this->currentResourcePosition;
    }

    public function valid()
    {
        return isset($this->descriptor()->resources[$this->currentResourcePosition]);
    }

    public function save($zip_filename)
    {
        $zipper = new Zipper();

        $packageCopy = $this->copy();

        $base = tempnam(sys_get_temp_dir(), 'datapackage-zip-');
        $files = [
            'datapackage.json' => $base.'datapackage.json',
        ];
        $ri = 0;

        foreach ($packageCopy->resources() as $resource) {
            $resourceFiles = [];
            $fileNames = $resource->save($base.'resource-'.$ri);
            foreach ($fileNames as $fileName) {
                $relname = str_replace($base.'resource-'.$ri, '', $fileName);
                $files['resource-'.$ri.$relname] = $fileName;
                $resourceFiles[] = 'resource-'.$ri.$relname;
            }
            $resource->descriptor()->path = count($resourceFiles) == 1 ? $resourceFiles[0] : $resourceFiles;
            ++$ri;
        }

        $packageCopy->saveDescriptor($files['datapackage.json']);

        /* @noinspection PhpUnhandledExceptionInspection Never occurs with our args */
        $zipper->make($zip_filename)->add($files)->close();
        foreach (array_values($files) as $file) {
            unlink($file);
        }
    }

    protected function copy()
    {
        return new static($this->descriptor, $this->basePath, true);
    }

    protected $descriptor;
    protected $currentResourcePosition = 0;
    protected $basePath;
    protected $skipValidations = false;

    /**
     * called by the resources iterator for each iteration.
     *
     * @param object $descriptor
     *
     * @return \frictionlessdata\datapackage\Resources\BaseResource
     */
    protected function initResource($descriptor)
    {
        return Factory::resource($descriptor, $this->basePath, $this->skipValidations);
    }

    protected function datapackageValidate()
    {
        return DatapackageValidator::validate($this->descriptor(), $this->basePath);
    }

    protected static function handlesProfile($profile)
    {
        return false;
    }
}
