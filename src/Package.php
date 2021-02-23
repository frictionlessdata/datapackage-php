<?php

namespace frictionlessdata\datapackage;

use Exception;
use ZipArchive;

class Package
{

  /**
   * @param $source
   * @param null $basePath
   *
   * @return \frictionlessdata\datapackage\Datapackages\BaseDatapackage
   * @throws \Exception
   * @throws \frictionlessdata\datapackage\Exceptions\DatapackageInvalidSourceException
   */
  public static function load($source, $basePath = null)
    {
        static::isZipPresent();
        return Factory::datapackage($source, $basePath);
    }

  /**
   * @param $source
   * @param null $basePath
   *
   * @return \frictionlessdata\datapackage\Validators\DatapackageValidationError[]
   * @throws \Exception
   */
  public static function validate($source, $basePath = null)
    {
        static::isZipPresent();
        return Factory::validate($source, $basePath);
    }

  /**
   * @param null $descriptor
   * @param null $basePath
   *
   * @return mixed
   * @throws \Exception
   */
  public static function create($descriptor = null, $basePath = null)
    {
        static::isZipPresent();
        $descriptor = Utils::objectify($descriptor);
        if ($descriptor && !isset($descriptor->resources)) {
            $descriptor->resources = [];
        }
        $packageClass = Factory::getDatapackageClass($descriptor);

        return new $packageClass($descriptor, $basePath, true);
    }

  /**
   * @throws \Exception
   */
  private static function isZipPresent() {
        //If ZipArchive is not available throw Exception.
        if (!class_exists('ZipArchive')) {
          throw new Exception('Error: Your PHP version is not compiled with zip support');
        }
    }
}
