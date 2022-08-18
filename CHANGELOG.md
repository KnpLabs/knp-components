## 3.6.0

*Released at 2022-08-18*

* changed handling of invalid arguments in paginate

## 3.1.0

*Released at 2021-06-23*

* fixed ORM where with numeric values on string fields
* added option to allow ODM subscriber
* added static analysis with phpstan
* dropped support for old Symfony versions

## 3.0.0

*Released at 2020-11-30*

* dropped support for Symfony 3
* dropped support for PHP 7.2
* fixed deprecation in Symfony 5
* removed deprecated features


## 2.5.0

*Released at 2020-11-22*

* deprecated not passing EventDispatcher to Paginator constructor

## 2.4.0

*Released at 2020-07-13*

* fixed error with older Symfony versions
* added new option to handle out of range page numbers
* deprecated sortFieldWhitelist and filterFieldWhitelist options
  (use sortFieldAllowList and filterFieldAllowList instead)

## 2.3.0

*Released at 2019-11-25*

* allowed Symfony 5 components
* fixed bug MongoDb query
* added coding standard via php-cs-fixer

## 2.2.0

*Released at 2019-09-02*

* added some missing return types

## 2.1.0

*Released at 2019-07-15*

* added new callback pagination
* fixed sorting when using class properties
* made some requirements explicit
* removed some unused code
* allowed for null Request object passed in, to avoid edge cases
* switched from PSR-0 to PSR-4

## 2.0.0

*Released at 2019-06-26*

* increased php minimum version
* added support form mongodb-odm version 2 (and removed support for version 1)
* added getters to PaginationInterface
* removed DBALQueryBuilderSubscriber
* removed deprecations for Symfony event system
* changed signature of ArraySubscriber (and, in general, many signatures that got type hinting)
