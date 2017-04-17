<?php namespace frictionlessdata\datapackage;


class Utils
{

    public static function is_json_string($json)
    {
        return (
            is_string($json)
            && (strpos(ltrim($json), "{") === 0)
        );
    }

    public static function is_http_source($source)
    {
        return (
            is_string($source)
            && (
                strpos($source, "http:") === 0
                || strpos($source, "https:") === 0
            )
        );
    }

}
