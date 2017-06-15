<?php namespace frictionlessdata\datapackage;

use frictionlessdata\datapackage\Datapackages\BaseDatapackage;
use frictionlessdata\datapackage\Resources\BaseResource;

/**
 * datapackage and resource have different classes depending on the corresponding profile
 * this factory interface allows to validate and create object instances without having to check the profile first
 */
class Factory
{
    /**
     * how many lines to validate sample when validating data streams
     */
    const VALIDATE_PEEK_LINES = 10;

    /**
     * load, validate and create a datapackage object
     * supports loading from the following sources:
     *  - native PHP object containing the descriptor
     *  - JSON encoded object
     *  - URL (must be in either 'http' or 'https' schemes)
     *  - local filesystem (POSIX) path
     * @param mixed $source
     * @param null|string $basePath optional, required only if you want to use relative paths
     * @return Datapackages\BaseDatapackage
     * @throws Exceptions\DatapackageInvalidSourceException
     * @throws Exceptions\DatapackageValidationFailedException
     */
    public static function datapackage($source, $basePath=null)
    {
        $source = static::loadSource($source, $basePath);
        $descriptor = $source->descriptor;
        $basePath = $source->basePath;
        $datapackageClass = static::getDatapackageClass($descriptor);
        $datapackage = new $datapackageClass($descriptor, $basePath);
        return $datapackage;
    }

    /**
     * create a resource object
     * @param object $descriptor
     * @param null|string $basePath
     * @param boolean $skipValidations
     * @return Resources\BaseResource
     * @throws Exceptions\ResourceValidationFailedException
     */
    public static function resource($descriptor, $basePath=null, $skipValidations=false)
    {
        $resourceClass = static::getResourceClass($descriptor);
        $resource = new $resourceClass($descriptor, $basePath, $skipValidations);
        return $resource;
    }

    /**
     * validates a given datapackage descriptor
     * will load all resources, and sample 10 lines of data from each data source
     * @param mixed $source datapackage source - same as in datapackage function
     * @param null|string $basePath same as in datapackage function
     * @return Validators\DatapackageValidationError[]
     */
    public static function validate($source, $basePath=null)
    {
        $curResource = 1;
        $curData = null;
        $curLine = null;
        try {
            $datapackage = static::datapackage($source, $basePath);
            foreach ($datapackage as $resource) {
                $curData = 1;
                foreach ($resource as $dataStream) {
                    $curLine = 1;
                    foreach ($dataStream as $line) {
                        if ($curLine == self::VALIDATE_PEEK_LINES) break;
                        $curLine++;
                    }
                    $curData++;
                }
                $curResource++;
            }
            // no validation errors
            return [];
        } catch (Exceptions\DatapackageInvalidSourceException $e) {
            // failed to load the datapackage descriptor
            // return a list containing a single LOAD_FAILED validation error
            return [
                new Validators\DatapackageValidationError(
                    Validators\DatapackageValidationError::LOAD_FAILED, $e->getMessage()
                )
            ];
        } catch (Exceptions\DatapackageValidationFailedException $e) {
            // datapackage descriptor failed validation - return the validation errors
            return $e->validationErrors;
        } catch (Exceptions\ResourceValidationFailedException $e) {
            // resource descriptor failed validation - return the validation errors
            return [
                new Validators\DatapackageValidationError(
                    Validators\DatapackageValidationError::RESOURCE_FAILED_VALIDATION,
                    [
                        "resource" => $curResource,
                        "validationErrors" => $e->validationErrors
                    ]
                )
            ];
        } catch (Exceptions\DataStreamOpenException $e) {
            // failed to open data stream
            return [
                new Validators\DatapackageValidationError(
                    Validators\DatapackageValidationError::DATA_STREAM_FAILURE,
                    [
                        "resource" => $curResource,
                        "dataStream" => $curData,
                        "line" => 0,
                        "error" => $e->getMessage()
                    ]
                )
            ];
        } catch (Exceptions\DataStreamValidationException $e) {
            // failed to validate the data stream
            return [
                new Validators\DatapackageValidationError(
                    Validators\DatapackageValidationError::DATA_STREAM_FAILURE,
                    [
                        "resource" => $curResource,
                        "dataStream" => $curData,
                        "line" => $curLine,
                        "error" => $e->getMessage()
                    ]
                )
            ];
        }
    }

    public static function registerDatapackageClass($datapackageClass)
    {
        static::$registeredDatapackageClasses[] = $datapackageClass;
    }

    public static function clearRegisteredDatapackageClasses()
    {
        static::$registeredDatapackageClasses = [];
    }

    /**
     * @param $descriptor
     * @return BaseDatapackage::class
     */
    public static function getDatapackageClass($descriptor)
    {
        $datapackageClasses = array_merge(
            // custom classes
            static::$registeredDatapackageClasses,
            // core classes
            [
                "frictionlessdata\\datapackage\\Datapackages\TabularDatapackage",
                "frictionlessdata\\datapackage\\Datapackages\DefaultDatapackage",
            ]
        );
        $res = null;
        foreach ($datapackageClasses as $datapackageClass) {
            if (call_user_func([$datapackageClass, "handlesDescriptor"], $descriptor)) {
                $res = $datapackageClass;
                break;
            }
        }
        if (!$res) {
            // not matched by any known classes
            $res = "frictionlessdata\\datapackage\\Datapackages\CustomDatapackage";
        }
        return $res;
    }

    public static function registerResourceClass($resourceClass)
    {
        static::$registeredResourceClasses[] = $resourceClass;
    }

    public static function clearRegisteredResourceClasses()
    {
        static::$registeredResourceClasses = [];
    }

    /**
     * @param $descriptor
     * @return BaseResource::class
     */
    public static function getResourceClass($descriptor)
    {
        $resourceClasses = array_merge(
            // custom classes
            static::$registeredResourceClasses,
            // core classes
            [
                "frictionlessdata\\datapackage\\Resources\\TabularResource",
                "frictionlessdata\\datapackage\\Resources\\DefaultResource"
            ]
        );
        $res = null;
        foreach ($resourceClasses as $resourceClass) {
            if (call_user_func([$resourceClass, "handlesDescriptor"], $descriptor)) {
                $res = $resourceClass;
                break;
            }
        }
        if (!$res) {
            // not matched by any known classes
            $res = "frictionlessdata\\datapackage\\Resources\\CustomResource";
        }
        return $res;
    }

    protected static $registeredDatapackageClasses = [];
    protected static $registeredResourceClasses = [];

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     */
    protected static function normalizeHttpSource($source)
    {
        return $source;
    }

    /**
     * allows extending classes to add custom sources
     * used by unit tests to add a mock http source
     */
    protected static function isHttpSource($source)
    {
        return Utils::isHttpSource($source);
    }

    /**
     * loads the datapackage descriptor from different sources
     * returns an object containing:
     *   - the datapackage descriptor as native php object
     *   - normalized basePath
     * @param $source
     * @param $basePath
     * @return object
     * @throws Exceptions\DatapackageInvalidSourceException
     */
    protected static function loadSource($source, $basePath)
    {
        if (is_object($source)) {
            $descriptor = $source;
        } elseif (is_string($source)) {
            if (Utils::isJsonString($source)) {
                try {
                    $descriptor = json_decode($source);
                } catch (\Exception $e) {
                    throw new Exceptions\DatapackageInvalidSourceException(
                        "Failed to load source: ".json_encode($source).": ".$e->getMessage()
                    );
                }
            } elseif (static::isHttpSource($source)) {
                try {
                    $descriptor = json_decode(file_get_contents(static::normalizeHttpSource($source)));
                } catch (\Exception $e) {
                    throw new Exceptions\DatapackageInvalidSourceException(
                        "Failed to load source: ".json_encode($source).": ".$e->getMessage()
                    );
                }
                // http sources don't allow relative paths, hence basePath should remain null
                $basePath = null;
            } else {
                // not a json string and not a url - assume it's a file path
                if (empty($basePath)) {
                    // no basePath
                    // - assume source is the absolute path of the file
                    // - set it's directory as the basePath
                    $basePath = dirname($source);
                } else {
                    // got a basePath
                    // - try to prepend it to the source and see if such a file exists
                    // - if not - assume it's an absolute path
                    $absPath = $basePath.DIRECTORY_SEPARATOR.$source;
                    if (file_exists($absPath)) {
                        $source = $absPath;
                    }
                }
                try {
                    $descriptor = json_decode(file_get_contents($source));
                } catch (\Exception $e) {
                    throw new Exceptions\DatapackageInvalidSourceException(
                        "Failed to load source: ".json_encode($source).": ".$e->getMessage()
                    );
                }

            }
        } else {
            throw new Exceptions\DatapackageInvalidSourceException(
                "Invalid source: ".json_encode($source)
            );
        }
        return (object)["descriptor" => $descriptor, "basePath" => $basePath];
    }
}