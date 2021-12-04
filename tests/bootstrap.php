<?php

require __DIR__.'/../vendor/autoload.php';

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
$reader = new \Doctrine\Common\Annotations\AnnotationReader();
if (class_exists('Doctrine\Common\Cache\ArrayCache')) {
    $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
}
$_ENV['annotation_reader'] = $reader;
