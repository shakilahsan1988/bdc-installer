<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit50ca3c91c448fb74bacff0907b18396b
{
    public static $prefixLengthsPsr4 = array (
        'B' => 
        array (
            'Bdc\\Installer\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Bdc\\Installer\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit50ca3c91c448fb74bacff0907b18396b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit50ca3c91c448fb74bacff0907b18396b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit50ca3c91c448fb74bacff0907b18396b::$classMap;

        }, null, ClassLoader::class);
    }
}
