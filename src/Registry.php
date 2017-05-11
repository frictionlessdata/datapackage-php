<?php namespace frictionlessdata\datapackage;
use frictionlessdata\datapackage\Resources\BaseResource;

/**
 * repository of known profiles and the corresponding classes and validation requirements
 */
class Registry
{
    /**
     * @param $descriptor
     * @return BaseResource::class
     */
    public static function getResourceClass($descriptor)
    {
        if (static::getResourceValidationProfile($descriptor) == "tabular-data-resource") {
            $resourceClass = "frictionlessdata\\datapackage\\Resources\\TabularResource";
        } else {
            $resourceClass = "frictionlessdata\\datapackage\\Resources\\DefaultResource";
        }
        /** @var BaseResource $resourceClass */
        return $resourceClass;
    }

    public static function getResourceValidationProfile($descriptor)
    {
        if (isset($descriptor->profile) && $descriptor->profile != "default") {
            return $descriptor->profile;
        } else {
            return "data-resource";
        }
    }

    public static function getDatapackageClass($descriptor)
    {
        return "frictionlessdata\\datapackage\\Datapackages\\DefaultDatapackage";
    }

    public static function getDatapackageValidationProfile($descriptor)
    {
        if (isset($descriptor->profile) && $descriptor->profile != "default") {
            return $descriptor->profile;
        } else {
            return "data-package";
        }
    }

    public static function getDatapackageJsonSchemaFile($profile)
    {
        if (in_array($profile, ["data-package", "tabular-data-package"])) {
            // known profile id
            return realpath(dirname(__FILE__))."/Validators/schemas/".$profile.".json";
        } else {
            // unknown profile / file or url
            return false;
        }
    }

    public static function getResourceJsonSchemaFile($profile)
    {
        if (in_array($profile, ["data-resource", "tabular-data-resource"])) {
            // known profile id
            return realpath(dirname(__FILE__))."/Validators/schemas/".$profile.".json";
        } else {
            // unknown profile / file or url
            return false;
        }
    }

    public static function getAllSchemas()
    {
        // registry schema
        $registrySchemaFilename = dirname(__FILE__)."/Validators/schemas/registry.json";
        $registry = [
            (object)[
                "id" => "registry",
                "schema" => "https://specs.frictionlessdata.io/schemas/registry.json",
                "schema_path" => "registry.json"
            ]
        ];
        // schemas from the registry (currently contains only the datapackage scheams)
        $schemaIds = [];
        if (file_exists($registrySchemaFilename)) {
            foreach (json_decode(file_get_contents($registrySchemaFilename)) as $schema) {
                $schemaIds[] = $schema->id;
                if ($schema->id == "fiscal-data-package") {
                    // fix a bug in the specs, see https://github.com/frictionlessdata/specs/pull/416
                    $schema->schema = "https://specs.frictionlessdata.io/schemas/fiscal-data-package.json";
                }
                $registry[] = $schema;
            };
            // resource schemas - currently not in the registry
            foreach (["data-resource", "tabular-data-resource"] as $id) {
                if (!in_array($id, $schemaIds)) {
                    $registry[] = (object)[
                        "id" => $id,
                        "schema" => "https://specs.frictionlessdata.io/schemas/{$id}.json",
                        "schema_path" => "{$id}.json"
                    ];
                }
            }
        }
        return $registry;
    }
}
