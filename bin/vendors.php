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
    // doctrine 2.2.x

    /*array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', 'f6d2b00d5c'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'dd967b1e9d'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', 'b3b1e62b1c'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', '1674e629f2'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '1674e629f2'),
    */
    // doctrine 2.1.x
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', '550fcbc17fc9d927edf3'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'eb80a3797e80fbaa024bb0a1ef01c3d81bb68a76'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', '73b61b50782640358940'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', '4109734e249a951f270c531999871bfe9eeed843'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '8fb97a4740c2c12a2a5a4e7d78f0717847c39691'),

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
