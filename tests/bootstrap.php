<?php

/**
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 */

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.5') < 0
) {
    die('PHPUnit framework is required, at least 3.5 version');
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.8 version');
}

define('TESTS_PATH', __DIR__);
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

$optionsLoad = array(
    array(
        'autoload' => VENDOR_PATH . '/Symfony/Component/ClassLoader/UniversalClassLoader.php',
        'annotation-orm' => VENDOR_PATH.'/doctrine-orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php',
        'annotation-odm' => VENDOR_PATH.'/doctrine-mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php'
    ),
    array(
        'autoload' => VENDOR_PATH . '/autoload.php',
        'annotation-orm' => VENDOR_PATH.'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php',
        'annotation-odm' => VENDOR_PATH.'/doctrine/mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php'
    )
);

$fileExists = false;
foreach ($optionsLoad as $optionLoad) {
    if (file_exists($optionLoad['autoload'])) {
        $fileExists = true;
        $loader        = $optionLoad['autoload'];
        $annotationOrm = $optionLoad['annotation-orm'];
        $annotationOdm = $optionLoad['annotation-odm'];
        break;
    }
}

if (!$fileExists) {
    die('cannot find vendor, run: php bin/vendors.php or composer.json');
}

require_once $loader;
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader;
$loader->registerNamespaces(array(
    'Symfony'                    => VENDOR_PATH,
    'Knp'                        => __DIR__.'/../src',
    'Test'                       => __DIR__,
    'Doctrine\\Common'           => VENDOR_PATH.'/doctrine-common/lib',
    'Doctrine\\DBAL'             => VENDOR_PATH.'/doctrine-dbal/lib',
    'Doctrine\\ORM'              => VENDOR_PATH.'/doctrine-orm/lib',
    'Doctrine\\MongoDB'          => VENDOR_PATH.'/doctrine-mongodb/lib',
    'Doctrine\\ODM\\MongoDB'     => VENDOR_PATH.'/doctrine-mongodb-odm/lib',
));
$loader->register();

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile($annotationOrm);

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile($annotationOdm);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
$_ENV['annotation_reader'] = $reader;

