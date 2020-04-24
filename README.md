Laravel JSON:API
================

Yet another Laravel package that helps you get up and running with JSON:API.

The idea behind this package is to make it as easy as possible to easily get up & running with JSON:API whilst making use
of [Laravel's Resource](https://laravel.com/docs/7.x/eloquent-resources).

## Changelog

* **0.4**
    * Add `JsonApi` facade, as well as `jsonapi()` helper function for extracting filters and pagination parameters.
    * Fix bug where `MissingValue` instances are passed as array members from additional properties.
