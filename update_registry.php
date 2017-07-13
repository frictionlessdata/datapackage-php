<?php

require 'vendor/autoload.php';

use frictionlessdata\datapackage\Registry;

function update()
{
    $base_filename = realpath(dirname(__FILE__))
        .DIRECTORY_SEPARATOR.'src'
        .DIRECTORY_SEPARATOR.'Validators'
        .DIRECTORY_SEPARATOR.'schemas'
        .DIRECTORY_SEPARATOR;
    $numUpdated = 0;
    foreach (Registry::getAllSchemas() as $schema) {
        $filename = $base_filename.$schema->schema_path;
        $old_schema = file_exists($filename) ? file_get_contents($filename) : 'FORCE UPDATE';
        echo "downloading schema from {$schema->schema}\n";
        $new_schema = file_get_contents($schema->schema);
        if ($old_schema == $new_schema) {
            echo "no update needed\n";
        } else {
            echo "schema changed - updating local file\n";
            file_put_contents($filename, $new_schema);
            file_put_contents($base_filename.'CHANGELOG', "\n\nChanges to {$schema->id} schema\n".date('c')."\n* check the git diff and summarize the spec changes here\n* \n\n", FILE_APPEND);
            if ($schema->id == 'registry') {
                echo "registry was updated, re-running update to fetch latest files from registry\n\n";

                return update();
            }
            ++$numUpdated;
        }
    }
    echo "\n{$numUpdated} schemas updated\n";

    return 0;
}

exit(update());
