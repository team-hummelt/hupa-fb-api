<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit44a9db61a89044435d2d087cbf569d21
{
    public static $files = array (
        '49a1299791c25c6fd83542c6fedacddd' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v4p11.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit44a9db61a89044435d2d087cbf569d21::$classMap;

        }, null, ClassLoader::class);
    }
}