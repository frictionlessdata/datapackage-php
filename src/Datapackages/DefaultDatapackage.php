<?php

namespace frictionlessdata\datapackage\Datapackages;

class DefaultDatapackage extends BaseDatapackage
{
    protected static function handlesProfile($profile)
    {
        return $profile == 'data-package';
    }
}
