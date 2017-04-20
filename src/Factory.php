<?php namespace frictionlessdata\datapackage;
use JsonSchema\Validator;

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
     * @param null|string $basePath
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
     * @return Resources\BaseResource
     * @throws Exceptions\ResourceValidationFailedException
     */
    public static function resource($descriptor, $basePath=null)
    {
        $resourceClass = static::getResourceClass($descriptor);
        $resource = new $resourceClass($descriptor, $basePath);
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

    protected static function getDatapackageClass($descriptor)
    {
        return Repository::getDatapackageClass($descriptor);
    }

    protected static function getResourceClass($descriptor)
    {
        return Repository::getResourceClass($descriptor);
    }

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
                if (empty($basePath)) {
                    $basePath = dirname($source);
                } else {
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