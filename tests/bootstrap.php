<?php

require __DIR__.'/../vendor/autoload.php';

if (class_exists(\Doctrine\Common\Annotations\AnnotationReader::class)) {
    if (method_exists(\Doctrine\Common\Annotations\AnnotationRegistry::class, 'class_exists')) {
        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
    }
    $reader = new \Doctrine\Common\Annotations\AnnotationReader();
    if (class_exists(\Doctrine\Common\Cache\ArrayCache::class)) {
        if (class_exists(\Doctrine\Common\Annotations\CachedReader::class)) {
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
        } else {
            $reader = new \Doctrine\Common\Annotations\PsrCachedReader(
                $reader,
                \Doctrine\Common\Cache\Psr6\CacheAdapter::wrap(new \Doctrine\Common\Cache\ArrayCache()),
            );
        }
    }
} else {
    $reader = null;
}

$_ENV['annotation_reader'] = $reader;
