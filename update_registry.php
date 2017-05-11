<?php

require("vendor/autoload.php");

use frictionlessdata\datapackage\Registry;

function update()
{
    $numUpdated = 0;
    foreach (Registry::getAllSchemas() as $schema) {
        $filename = realpath(dirname(__FILE__))
            .DIRECTORY_SEPARATOR."src"
            .DIRECTORY_SEPARATOR."Validators"
            .DIRECTORY_SEPARATOR."schemas"
            .DIRECTORY_SEPARATOR.$schema->schema_path;
        $old_schema = file_exists($filename) ? file_get_contents($filename) : "FORCE UPDATE";
        print("downloading schema from {$schema->schema}\n");
        $new_schema = file_get_contents($schema->schema);
        if ($old_schema == $new_schema) {
            print("no update needed\n");
        } else {
            print("schema changed - updating local file\n");
            file_put_contents($filename, $new_schema);
            if ($schema->id == "registry") {
                print("registry was updated, re-running update to fetch latest files from registry\n\n");
                return update();
            }
            $numUpdated++;
        }
    }
    print("\n{$numUpdated} schemas updated\n");
    return 0;
}

exit(update());
