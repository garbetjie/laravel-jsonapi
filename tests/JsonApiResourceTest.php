<?php


namespace Garbetjie\Laravel\JsonApi\Tests;

use Closure;
use Garbetjie\Laravel\JsonApi\Extractors\PassthroughExtractor;
use Garbetjie\Laravel\JsonApi\JsonApiResource;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use stdClass;
use function app;
use function array_keys;
use function collect;
use function json_decode;
use function json_encode;
use function range;
use function value;

class JsonApiResourceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container());
        $request = new Request();

        $app = app();
        $app->instance('request', $request);
        $app->instance(Request::class, $request);
    }

    /**
     * @param JsonApiResource $resource
     * @param Request $request
     * @return stdClass
     */
    private function convertResourceToResponse($resource, $request)
    {
        $resourceResponse = new ResourceResponse($resource);

        $ref = new ReflectionObject($resourceResponse);
        $method = $ref->getMethod('wrap');
        $method->setAccessible(true);

        return json_decode(
            json_encode(
                $method->invoke(
                    $resourceResponse,
                    $resource->resolve($request),
                    $resource->with($request),
                    $resource->additional
                )
            )
        );
    }

    /**
     * @dataProvider singleResourceProvider
     *
     * @param MockResource $resource
     */
    public function testSingleResourceStructure($resource)
    {
        $request = app(Request::class);
        $resource = new JsonApiResource($resource);
        $body = $this->convertResourceToResponse($resource, $request);

        $validator = new Validator();
        $validator->validate($body, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid(), 'json schema validation');
    }

    public function singleResourceProvider()
    {
        return [
            'relationship object' => [
                new MockResource('type', 'id')
            ],
            'attributes' => [
                (new MockResource('type', 'id'))->attributes(['name' => 'Resource name'])
            ],
            'attributes + links' => [
                (new MockResource('type', 'id'))
                    ->attributes(['name' => 'Resource name'])
                    ->links(['link' => 'https://example.org']),
            ],
            'attributes + links + meta' => [
                (new MockResource('type', 'id'))
                    ->attributes(['name' => 'Resource name'])
                    ->links(['link' => 'https://example.org'])
                    ->meta(['someCount' => 12345]),
            ],
            'attributes + links + meta + hasOne relationship' => [
                (new MockResource('type', 'id'))
                    ->attributes(['name' => 'Resource name'])
                    ->links(['link' => 'https://example.org'])
                    ->meta(['someCount' => 12345])
                    ->relationships([
                        'relationshipName' => [
                            'data' => ['type' => 'type', 'id' => 'id'],
                            'links' => [
                                'related' => 'https://example.org',
                            ]
                        ]
                    ]),
            ],
            'attributes + links + meta + hasMany relationship' => [
                (new MockResource('type', 'id'))
                    ->attributes(['name' => 'Resource name'])
                    ->links(['link' => 'https://example.org'])
                    ->meta(['someCount' => 12345])
                    ->relationships([
                        'relationshipName' => [
                            'data' => [
                                ['type' => 'type', 'id' => 'id'],
                            ],
                            'links' => [
                                'related' => 'https://example.org',
                            ],
                        ]
                    ]),
            ]
        ];
    }

    /**
     * @dataProvider multipleResourceProvider
     * @param array|Collection $collection
     * @param MockResource $expectedResource
     */
    public function testCollectionOfResourcesStructure($collection, $expectedResource)
    {
        $request = app(Request::class);
        $resource = new JsonApiResource($collection);
        $body = $this->convertResourceToResponse($resource, $request);

        $validator = new Validator();
        $validator->validate($body, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid());
        $this->assertObjectHasAttribute('data', $body);

        $data = $body->data;
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals((object)['type' => $expectedResource->getJsonApiType(), 'id' => $expectedResource->getJsonApiId()], $data[0]);
    }

    public function multipleResourceProvider()
    {
        $resource = new MockResource('type', 'id');

        return [
            'relationship object (array)' => [
                [$resource],
                $resource,
            ],
            'relationship object (collection)' => [
                collect([$resource]),
                $resource,
            ],
            'relationship object (paginated)' => [
                new Paginator([$resource], 15, 1),
                $resource,
            ],
            'relationship object (length aware paginated)' => [
                new LengthAwarePaginator([$resource], 15, 15, 1),
                $resource,
            ]
        ];
    }

    /**
     * @dataProvider includedResourceProvider
     *
     * @param MockResource|MockResource[]|Collection $resourceOrCollection
     * @param string|null $includes
     * @param MockResource|MockResource[]|Collection $included
     * @param int $expectedIncludeCount
     * @param array $defaultIncludes
     */
    public function testIncludedResources($resourceOrCollection, $includes, $included, $expectedIncludeCount, $defaultIncludes)
    {
        $request = app(Request::class);
        /* @var Request $request */

        if ($includes !== null) {
            $request->query->set('include', $includes);
        }

        $resource = new JsonApiResource($resourceOrCollection);

        $resource->withDefaultIncludes($defaultIncludes);

        $resource->withIncludeLoader(
            'one',
            function ($providedResource) use ($resourceOrCollection) {
                /* @var MockResource|MockResource[]|Collection $resourceOrCollection */

                // Ensure that the include loader is passed the actual resource that was passed into the resource initially.
                $this->assertSame($resourceOrCollection, $providedResource);
            }
        );

        $resource->withIncludeExtractor(
            'one',
            new PassthroughExtractor($included)
        );

        $body = $this->convertResourceToResponse($resource, $request);

        $validator = new Validator();
        $validator->validate($body, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid());
        $this->assertObjectHasAttribute('included', $body);

        $this->assertEquals(
            array_keys($body->included),
            count($body->included) > 0
                ? range(0, count($body->included) - 1)
                : []
        );

        $this->assertCount($expectedIncludeCount, $body->included);
    }

    public function includedResourceProvider()
    {
        return [
            'single resource' => [
                (new MockResource('type', 'id')),
                'one',
                [new MockResource('included_type1', 'included_id1'), new MockResource('included_type2', 'included_id2')],
                2,
                ['one'],
            ],
            'multiple resources with duplicated includes' => [
                [(new MockResource('type1', 'id1')), new MockResource('type2', 'id2')],
                'one',
                [
                    new MockResource('included_type1', 'included_id1'),
                    new MockResource('included_type2', 'included_id2'),
                    new MockResource('included_type1', 'included_id1'),
                ],
                2,
                ['one'],
            ],
            'unregistered include' => [
                new MockResource('type', 'id'),
                'not_found',
                [new MockResource('included_type1', 'included_id1')],
                0,
                ['one'],
            ],
            'default includes' => [
                new MockResource('type', 'id'),
                null,
                [new MockResource('included_type1', 'included_id1')],
                1,
                ['one'],
            ],
        ];
    }

    /**
     * @dataProvider notImplementingInterfaceProvider
     *
     * @param mixed $value
     */
    public function testForNotImplementingInterface($value)
    {
        $this->expectException(InvalidArgumentException::class);

        $resource = new JsonApiResource($value);
        $request = app(Request::class);

        $resource->toArray($request);
    }

    public function notImplementingInterfaceProvider()
    {
        return [
            ['string'],
            [1],
            [null],
            [new stdClass()],
            ['associative' => 'array']
        ];
    }

    /**
     * @dataProvider customCollectionExtractorsProvider
     *
     * @param string $className
     * @param Closure $fn
     * @param mixed $value
     */
    public function testCustomCollectionExtractors($className, $fn, $value)
    {
        $resource = new JsonApiResource($value);
        $resource->withCollectionExtractor($className, $fn);
        $built = $resource->toArray(app(Request::class));

        $this->assertIsArray($built);
        $this->assertEquals(array_keys($built), range(0, count($built) - 1));
    }

    public function customCollectionExtractorsProvider()
    {
        return [
            [
                stdClass::class,
                function (stdClass $obj) {
                    return [new MockResource('type', 'id')];
                },
                new stdClass(),
            ],

            [
                Closure::class,
                function (Closure $fn) {
                    return $fn();
                },
                function () {
                    return [
                        new MockResource('type1', 'id1'),
                        new MockResource('type2', 'id2'),
                    ];
                }
            ]
        ];
    }
}
