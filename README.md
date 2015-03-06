Brick\Geo
=========

A collection of classes to work with GIS geometries.

[![Build Status](https://secure.travis-ci.org/brick/geo.svg?branch=master)](http://travis-ci.org/brick/geo)
[![Coverage Status](https://coveralls.io/repos/brick/geo/badge.svg?branch=master)](https://coveralls.io/r/brick/geo?branch=master)

Introduction
------------

This library is a PHP implementation of the [OpenGIS specification](http://www.opengeospatial.org/standards/sfa).

It is essentially a wrapper around a third-party GIS engine, to which it delegates most of the complexity of the
geometry calculations. Several engines are supported, from native PHP extensions such as GEOS to GIS-compatible databases such as MySQL or PostgreSQL.

Requirements and installation
-----------------------------

This library requires PHP 5.5 or higher. [HHVM](http://hhvm.com/) is officially supported.

We recommended installing it with [Composer](https://getcomposer.org/).
Just define the following requirement in your `composer.json` file:

    {
        "require": {
            "brick/geo": "dev-master"
        }
    }

Then head on to the [Configuration](#configuration) section to configure a GIS geometry engine.

Failure to configure a geometry engine would result in a `GeometryException` when trying to use a method that requires one.

Configuration
-------------

Configuring the library consists in choosing the most convenient `GeometryEngine` implementation for your installation. The following implementations are available:

- `PDOEngine`: communicates with a GIS-compatible database over a `PDO` connection.  
  This engine currently supports the following databases:
  - [MySQL](http://php.net/manual/en/ref.pdo-mysql.php) version 5.6 or greater;  
    Earlier versions only have partial GIS support based on bounding boxes and are not supported.  
    **Note: MySQL currently only supports 2D geometries.**
    
  - [PostgreSQL](http://php.net/manual/en/ref.pdo-pgsql.php) with the [PostGIS](http://postgis.net/install) extension.
- `SQLite3Engine`: communicates with a [SQLite3](http://php.net/manual/en/book.sqlite3.php) database with the [SpatiaLite](https://www.gaia-gis.it/fossil/libspatialite/index) extension.
- `GEOSEngine`: uses the [GEOS](https://github.com/libgeos/libgeos) PHP bindings.

Following is a step-by-step guide for all the possible configurations:

### Using PDO and MySQL 5.6 or greater

- Ensure that your MySQL version is at least `5.6`.
- Use this bootstrap code in your project:

        use Brick\Geo\Engine\GeometryEngineRegistry;
        use Brick\Geo\Engine\PDOEngine;

        $pdo = new PDO('mysql:host=localhost', 'root', '');
        GeometryEngineRegistry::set(new PDOEngine($pdo));

Update the code with your own connection parameters, or re-use your existing `PDO` connection if you have one (recommended).

### Using PDO and PostgreSQL with PostGIS

- Ensure that [PostGIS is installed](http://postgis.net/install/) on your server
- Enable PostGIS on the database server if needed:

        CREATE EXTENSION postgis;

- Use this bootstrap code in your project:

        use Brick\Geo\Engine\GeometryEngineRegistry;
        use Brick\Geo\Engine\PDOEngine;

        $pdo = new PDO('pgsql:host=localhost', 'postgres', '');
        GeometryEngineRegistry::set(new PDOEngine($pdo));

Update the code with your own connection parameters, or re-use your existing `PDO` connection if you have one (recommended).

### Using SQLite3 with SpatiaLite

- Ensure that [SpatiaLite is installed](https://www.gaia-gis.it/fossil/libspatialite/index) on your system.
- Ensure that the SQLite3 extension is enabled in your `php.ini`:

        extension=sqlite3.so

- Ensure that the SQLite3 extension dir where SpatiaLite is installed is configured in your `php.ini`:

        [sqlite3]
        sqlite3.extension_dir = /usr/lib

- Use this bootstrap code in your project:

        use Brick\Geo\Engine\GeometryEngineRegistry;
        use Brick\Geo\Engine\SQLite3Engine;

        $sqlite3 = new SQLite3(':memory:');
        $sqlite3->loadExtension('libspatialite.so.3');
        GeometryEngineRegistry::set(new SQLite3Engine($sqlite3));

Update the `libspatialite` extension name as required. In this example we have created an in-memory database for our GIS calculations, but you can also re-use an existing `SQLite3` connection.

### Using GEOS PHP bindings

- Ensure that the [GEOS is installed](https://github.com/libgeos/libgeos) on your server. GEOS must have been compiled with the `--enable-php` flag to provide the PHP extension.
- Ensure that the GEOS extension is enabled in your `php.ini`:

        extension=geos.so

- Use this bootstrap code in your project:

        use Brick\Geo\Engine\GeometryEngineRegistry;
        use Brick\Geo\Engine\GEOSEngine;

        GeometryEngineRegistry::set(new GEOSEngine());

Usage
-----

To be written.

Functions
---------

All suported spacial functions can be found in the [functions.md](functions.md) file.