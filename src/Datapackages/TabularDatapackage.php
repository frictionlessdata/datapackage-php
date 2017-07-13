<?php

namespace frictionlessdata\datapackage\Datapackages;

class TabularDatapackage extends DefaultDatapackage
{
    protected static function handlesProfile($profile)
    {
        return $profile == 'tabular-data-package';
    }
}
