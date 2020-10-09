<?php

namespace Garbetjie\Laravel\JsonApi\Tests;

use Garbetjie\Laravel\JsonApi\ConvertibleToJsonApiResourceInterface;
use Garbetjie\Laravel\JsonApi\JsonApiResource;
use Garbetjie\Laravel\JsonApi\JsonApiResourceCollection;
use Garbetjie\Laravel\JsonApi\JsonApiResourceInterface;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Routing\UrlGenerator;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewFinderInterface;
use PHPUnit\Framework\MockObject\Stub;
use ReflectionObject;
use stdClass;
use function app;
use function get_class;
use function json_decode;
use function json_encode;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Container
     */
    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container());
        $request = new Request();
        $this->app = app();
        $this->app->instance('request', $request);
        $this->app->instance(get_class($request), $request);
        $this->app->instance('config', new Repository(require __DIR__ . '/../config.php'));
        $this->app->instance(\Illuminate\Contracts\View\Factory::class, new Factory(new EngineResolver(), new FileViewFinder(new Filesystem(), []), new Dispatcher()));
        $this->app->instance(ViewFinderInterface::class, new FileViewFinder($this->app->make(Filesystem::class), []));
        $this->app->instance(RouteCollectionInterface::class, new RouteCollection());
        $this->app->instance(FileViewFinder::class, new FileViewFinder($this->app->make(Filesystem::class), []));
        $this->app->instance(ResponseFactory::class, $this->app->make(\Illuminate\Routing\ResponseFactory::class));
        $this->app->instance(\Illuminate\Contracts\Events\Dispatcher::class, new Dispatcher());
    }

    /**
     * @param JsonApiResource|JsonApiResourceCollection $resource
     * @param Request $request
     * s
     * @return stdClass
     */
    protected function convertResourceToResponse($resource, Request $request): stdClass
    {
        return json_decode($resource->toResponse($request)->content());
    }

    protected function createResourceableInterfaceStub(
        $attributes = MissingValue::class,
        $links = MissingValue::class,
        $meta = MissingValue::class,
        $relationships = MissingValue::class
    ) {
        $stub = $this->createStub(JsonApiResourceInterface::class);
        $stub->method('getJsonApiType')->willReturn('resource');
        $stub->method('getJsonApiId')->willReturn('id');
        $stub->method('getJsonApiAttributes')->willReturn($attributes === MissingValue::class ? new MissingValue() : $attributes);
        $stub->method('getJsonApiLinks')->willReturn($links === MissingValue::class ? new MissingValue() : $links);
        $stub->method('getJsonApiMeta')->willReturn($meta === MissingValue::class ? new MissingValue() : $meta);
        $stub->method('getJsonApiRelationships')->willReturn($relationships === MissingValue::class ? new MissingValue() : $relationships);

        return $stub;
    }

    /**
     * @param JsonApiResourceInterface|null $stub
     * @return ConvertibleToJsonApiResourceInterface|Stub
     */
    protected function createConvertibleInterfaceStub(?JsonApiResourceInterface $stub = null)
    {
        $convertible = $this->createStub(ConvertibleToJsonApiResourceInterface::class);
        $convertible->method('convertToJsonApiResource')->willReturn($stub ?: $this->createResourceableInterfaceStub());

        return $convertible;
    }
}
