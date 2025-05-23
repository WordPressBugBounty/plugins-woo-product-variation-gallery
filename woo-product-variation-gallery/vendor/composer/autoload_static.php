<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb502db0f24f171c143e9c21fd5b166bb
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'Rtwpvg\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Rtwpvg\\' => 
        array (
            0 => __DIR__ . '/../..' . '/app',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Rtwpvg\\Controllers\\Hooks' => __DIR__ . '/../..' . '/app/Controllers/Hooks.php',
        'Rtwpvg\\Controllers\\Install' => __DIR__ . '/../..' . '/app/Controllers/Install.php',
        'Rtwpvg\\Controllers\\Notifications' => __DIR__ . '/../..' . '/app/Controllers/Notifications.php',
        'Rtwpvg\\Controllers\\Offer' => __DIR__ . '/../..' . '/app/Controllers/Offer.php',
        'Rtwpvg\\Controllers\\ProductMeta' => __DIR__ . '/../..' . '/app/Controllers/ProductMeta.php',
        'Rtwpvg\\Controllers\\Review' => __DIR__ . '/../..' . '/app/Controllers/Review.php',
        'Rtwpvg\\Controllers\\ScriptLoader' => __DIR__ . '/../..' . '/app/Controllers/ScriptLoader.php',
        'Rtwpvg\\Controllers\\SettingsAPI' => __DIR__ . '/../..' . '/app/Controllers/SettingsAPI.php',
        'Rtwpvg\\Controllers\\ThemeSupport' => __DIR__ . '/../..' . '/app/Controllers/ThemeSupport.php',
        'Rtwpvg\\Helpers\\Functions' => __DIR__ . '/../..' . '/app/Helpers/Functions.php',
        'Rtwpvg\\Helpers\\Options' => __DIR__ . '/../..' . '/app/Helpers/Options.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb502db0f24f171c143e9c21fd5b166bb::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb502db0f24f171c143e9c21fd5b166bb::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb502db0f24f171c143e9c21fd5b166bb::$classMap;

        }, null, ClassLoader::class);
    }
}
