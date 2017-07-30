<?php

namespace frictionlessdata\datapackage;

/**
 * repository of known profiles and the corresponding schemas.
 */
class Registry
{
    /**
     * get the profile which should be used for validation from the given resource descriptor.
     */
    public static function getResourceValidationProfile($descriptor)
    {
        $descriptor = Utils::objectify($descriptor);
        if (isset($descriptor->profile) && $descriptor->profile != 'default') {
            return $descriptor->profile;
        } else {
            return 'data-resource';
        }
    }

    /**
     * get the profile which should be used for validation from the given datapackage descriptor
     * corresponds to the id from the registry.
     */
    public static function getDatapackageValidationProfile($descriptor)
    {
        if (isset($descriptor->profile) && $descriptor->profile != 'default') {
            return $descriptor->profile;
        } else {
            return 'data-package';
        }
    }

    /**
     * given a normalized profile - get the corresponding schema file for known schema in the registry
     * returns false in case of unknown schema
     * works the same for both datapackage schema and resource schemas.
     */
    public static function getJsonSchemaFile($profile)
    {
        foreach (static::getAllSchemas() as $schema) {
            if ($schema->id != 'registry' && $schema->id == $profile) {
                if (isset($schema->schema_path)) {
                    return realpath(dirname(__FILE__)).'/Validators/schemas/'.$schema->schema_path;
                } else {
                    return $schema->schema_filename;
                }
            }
        }

        return false;
    }

    public static function registerSchema($profile, $filename)
    {
        static::$registeredSchemas[$profile] = ['filename' => $filename];
    }

    public static function clearRegisteredSchemas()
    {
        static::$registeredSchemas = [];
    }

    /**
     * returns array of all known schemas in the registry.
     */
    public static function getAllSchemas()
    {
        // registry schema
        $registrySchemaFilename = dirname(__FILE__).'/Validators/schemas/registry.json';
        $registry = [
            (object) [
                'id' => 'registry',
                'schema' => 'https://specs.frictionlessdata.io/schemas/registry.json',
                'schema_path' => 'registry.json',
            ],
        ];
        // schemas from the registry (currently contains only the datapackage scheams)
        $schemaIds = [];
        if (file_exists($registrySchemaFilename)) {
            foreach (json_decode(file_get_contents($registrySchemaFilename)) as $schema) {
                $schemaIds[] = $schema->id;
                $registry[] = $schema;
            }
            // resource schemas - currently not in the registry
            foreach (['data-resource', 'tabular-data-resource'] as $id) {
                if (!in_array($id, $schemaIds)) {
                    $registry[] = (object) [
                        'id' => $id,
                        'schema' => "https://specs.frictionlessdata.io/schemas/{$id}.json",
                        'schema_path' => "{$id}.json",
                    ];
                }
            }
        }
        // custom registered schemas
        foreach (static::$registeredSchemas as $profile => $schema) {
            $registry[] = (object) [
                'id' => $profile,
                'schema_filename' => $schema['filename'],
            ];
        }

        return $registry;
    }

    protected static $registeredSchemas = [];
}
