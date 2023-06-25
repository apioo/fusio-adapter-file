<?php

use Fusio\Adapter\File\Action\FileDirectoryGet;
use Fusio\Adapter\File\Action\FileDirectoryGetAll;
use Fusio\Adapter\File\Action\FileEngine;
use Fusio\Adapter\File\Action\FileProcessor;
use Fusio\Adapter\File\Connection\Filesystem;
use Fusio\Adapter\File\Generator\FileDirectory;
use Fusio\Engine\Adapter\ServiceBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = ServiceBuilder::build($container);
    $services->set(Filesystem::class);
    $services->set(FileDirectoryGet::class);
    $services->set(FileDirectoryGetAll::class);
    $services->set(FileProcessor::class);
    $services->set(FileDirectory::class);
};
