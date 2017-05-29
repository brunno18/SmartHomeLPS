<?php

$loader = new \Phalcon\Loader();

/**
 * We're a registering a set of directories taken from the configuration file
 */
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->formsDir
    ]
);

$loader->registerNamespaces([
    'SmartHomeLPS\Services'   => $config->application->servicesDir,
]);

//$loader->registerFiles([APP_PATH . '/vendor/guzzle/autoloader.php']);

$loader->register();