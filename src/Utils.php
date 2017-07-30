<?php

namespace frictionlessdata\datapackage;

// TODO: refactor to an independenct package (used by both tableschema and datapackage)
class Utils
{
    public static function isJsonString($json)
    {
        return
            is_string($json)
            && (strpos(ltrim($json), '{') === 0)
        ;
    }

    public static function isHttpSource($source)
    {
        return
            is_string($source)
            && (
                strpos($source, 'http:') === 0
                || strpos($source, 'https:') === 0
            )
        ;
    }

    public static function objectify($val)
    {
        return is_object($val) ? $val : json_decode(json_encode($val));
    }
}
