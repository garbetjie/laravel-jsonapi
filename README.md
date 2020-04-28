Laravel JSON:API
================

Yet another Laravel package that helps you get up and running with JSON:API.

The idea behind this package is to make it as easy as possible to easily get up & running with JSON:API whilst making use
of [Laravel's Resource](https://laravel.com/docs/7.x/eloquent-resources).

![Build status](https://api.travis-ci.org/garbetjie/laravel-jsonapi.svg?branch=master) ![PHP from Packagist](https://img.shields.io/packagist/php-v/garbetjie/laravel-jsonapi)

## Table of Contents

* [Installation](#installation)
* [Basic Usage](#basic-usage)
    * [Converting to a resource with `JsonApiResourceInterface`](#converting-to-a-resource-with-jsonapiresourceinterface)
    * [Converting to a resource with `ConvertibleToJsonApiResourceInterface`](#converting-to-a-resource-with-convertibletojsonapiresourceinterface)

## Installation

    composer require garbetjie/laravel-jsonapi

## Basic Usage

In order to generate JSON:API resources with this package, simply return an instance of `Garbetjie\Laravel\JsonApi\JsonApiResource`
from your controller method.

This can be done through creating your own resources that extend `JsonApiResource`, or through creating a new instance
of `JsonApiResource` directly, and passing in a value that can be converted to a resource (see the following sections
on how to do this). 

### Converting to a resource with `JsonApiResourceInterface`

When implementing `Garbetjie\Laravel\JsonApi\JsonApiResourceInterface`, the object that is implementing the interface is
directly responsible for representing itself as a JSON:API resource. This means that _any_ object can be used to represent
a JSON:API resource.

It is __recommended__ that an instance of an [Eloquent Resource](https://laravel.com/docs/7.x/eloquent-resources)
implements this interface, as there are a number of helper methods available that make it easier to work with Eloquent
models.

#### Resource definition

```php
<?php

use Garbetjie\Laravel\JsonApi\JsonApiResource;
use Garbetjie\Laravel\JsonApi\JsonApiResourceInterface;
use Illuminate\Http\Resources\MissingValue;

class UserResource extends JsonApiResource implements JsonApiResourceInterface
{
    public function getJsonApiId() {
        return $this->getKey();
    }

    public function getJsonApiLinks($request) {
        return new MissingValue();
    }

    public function getJsonApiMeta($request) {
        return [
            'loginCount' => 1,
            'logoutCount' => new MissingValue(),
        ];
    }

    public function getJsonApiAttributes($request) {
        return [
            'name' => $this->first_name,
            'displayName' => trim("{$this->first_name} {$this->last_name}"),
            'attribute' => $this->has_attribute ? $this->has_attribute : new MissingValue(),
        ];
    }

    public function getJsonApiType() {
        return 'users';
    }

    public function getJsonApiRelationships($request) {
        return [
            'logins' => [
                'data' => [
                    ['type' => 'logins', 'id' => 1]
                ],
                'links' => [
                    'related' => "/users/{$this->getJsonApiId()}/logins"
                ], 
            ],
        ];
    }
}
```

#### Usage

```php

<?php
class UsersController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(1);

        return new UserResource($user);
    }

}
```

### Converting to a resource with `ConvertibleToJsonApiResourceInterface`

When implementing `Garbetjie\Laravel\JsonApi\ConvertibleToJsonApiResourceInterface`, you are essentially delegating the
implementation of the resource conversion to a different object (one that would typically implement the 
`JsonApiResourceInterface` interface).

This is especially useful for your `Model` instances, where you wouldn't want to implement the `JsonApiResourceInterface`
interface directly, but you still want your models to be able to be represented as JSON:API resources.

Building on the previous example, an example is provided below:

#### Definition

```php
<?php

class User extends Model implements ConvertibleToJsonApiResourceInterface
{
    public function convertToJsonApiResource() : JsonApiResourceInterface
    {
        return new UserResource($this);
    }
}
```

## Changelog

* **0.6.0**
    * Refactor pagination config to use strategies.
    * Add `include_mode` configuration, which determines how invalid includes are handled.

* **0.5.0**
    * Renamed multiple classes and interfaces to provide better readability.
    * Update README to provide more usage information.
    
* **0.4.0**
    * Add `JsonApi` facade, as well as `jsonapi()` helper function for extracting filters and pagination parameters.
    * Fix bug where `MissingValue` instances are passed as array members from additional properties.
