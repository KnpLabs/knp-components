#!/usr/bin/env php
<?php

// dependent libraries for test environment

define('VENDOR_PATH', __DIR__ . '/../vendor');

if (!is_dir(VENDOR_PATH)) {
    mkdir(VENDOR_PATH, 0775, true);
}

$deps = array(
    array('Symfony/Component/ClassLoader', 'http://github.com/symfony/ClassLoader.git', 'v2.0.4'),
    array('Symfony/Component/EventDispatcher', 'http://github.com/symfony/EventDispatcher.git', 'aeff4b4'),
    // orm
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', '15562d030e'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', '537de7e'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', 'b3b1e62b1c'),
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = VENDOR_PATH.'/'.$name;
    if (!is_dir($installDir)) {
        system(sprintf('git clone %s %s', $url, $installDir));
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', $installDir, $rev));
}
