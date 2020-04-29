<?php


namespace Garbetjie\Laravel\JsonApi\Tests;

use Garbetjie\Laravel\JsonApi\JsonApiResource;
use Garbetjie\Laravel\JsonApi\JsonApiResourceCollection;
use Garbetjie\Laravel\JsonApi\JsonApiResourceInterface;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceResponse;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use stdClass;
use function app;
use function array_keys;
use function array_unshift;
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
     * @dataProvider providesPropertyIsObject
     * @param string $method
     * @param string $property
     * @param mixed $value
     */
    public function testPropertyIsObject($method, $property, $value)
    {
        $stub = $this->createStub(JsonApiResourceInterface::class);
        $stub->method($method)->willReturn($value);

        $resource = new JsonApiResource($stub);
        $built = $resource->toArray(app(Request::class));

        $this->assertIsArray($built);
        $this->assertArrayHasKey($property, $built);
        $this->assertEquals(new stdClass(), $built[$property]);
    }

    public function providesPropertyIsObject()
    {
        $data = [];

        foreach ([
                     'getJsonApiAttributes' => 'attributes',
                     'getJsonApiMeta' => 'meta',
                     'getJsonApiLinks' => 'links',
                     'getJsonApiRelationships' => 'relationships',
                 ] as $method => $property
        ) {
            $data["empty array [{$property}]"] = [$method, $property, []];
            $data["array with single missing value [{$property}]"] = [$method, $property, ['name' => new MissingValue()]];
            $data["array with multiple missing values [{$property}]"] = [$method, $property, ['name' => new MissingValue(), 'description' => new MissingValue()]];
        }

        return $data;
    }

    /**
     * @dataProvider providesPropertyIsArray
     * @param string $method
     * @param string $property
     * @param array $value
     */
    public function testPropertyIsArray($method, $property, $value)
    {
        $stub = $this->createStub(JsonApiResourceInterface::class);
        $stub->method($method)->willReturn($value);

        $resource = new JsonApiResource($stub);
        $built = $resource->toArray(app(Request::class));

        $this->assertIsArray($built);
        $this->assertArrayHasKey($property, $built);
        $this->assertIsArray($built[$property]);
    }

    public function providesPropertyIsArray()
    {
        $data = [];

        foreach ([
                     'getJsonApiAttributes' => 'attributes',
                     'getJsonApiMeta' => 'meta',
                     'getJsonApiLinks' => 'links',
                     'getJsonApiRelationships' => 'relationships',
                 ] as $method => $property
        ) {
            $data["array with single null [{$property}]"] = [
                $method,
                $property,
                ['name' => null],
            ];

            $data["array with string value [{$property}]"] = [
                $method,
                $property,
                ['name' => ''],
            ];

            $data["array with int value [{$property}]"] = [
                $method,
                $property,
                ['name' => 123],
            ];

            $data["array with nested array [{$property}]"] = [
                $method,
                $property,
                ['images' => ['url' => 'https://example.org']]
            ];
        }

        return $data;
    }

    /**
     * @dataProvider providesPropertyIsReturnedAsGiven
     * @param string $method
     * @param string $property
     * @param mixed $value
     */
    public function testPropertyIsReturnedAsGiven($method, $property, $value)
    {
        $stub = $this->createStub(JsonApiResourceInterface::class);
        $stub->method($method)->willReturn($value);

        $resource = new JsonApiResource($stub);
        $built = $resource->toArray(app(Request::class));

        $this->assertIsArray($built);
        $this->assertArrayHasKey($property, $built);
        $this->assertEquals($value, $built[$property]);
    }

    public function providesPropertyIsReturnedAsGiven()
    {
        $data = [];

        foreach ([
                     'getJsonApiAttributes' => 'attributes',
                     'getJsonApiMeta' => 'meta',
                     'getJsonApiLinks' => 'links',
                     'getJsonApiRelationships' => 'relationships',
                 ] as $method => $property
        ) {
            $data["null [{$property}]"] = [$method, $property, null];
            $data["string [{$property}]"] = [$method, $property, 'a string'];
            $data["int [{$property}]"] = [$method, $property, 123];
            $data["object instance [{$property}]"] = [$method, $property, new class() {}];
        }

        return $data;
    }

    /**
     * @dataProvider providesEncodedStructureValidates
     * @param JsonApiResourceInterface $stub
     */
    public function testEncodedStructureValidates(JsonApiResourceInterface $stub)
    {
        $resource = new JsonApiResource($stub);
        $converted = $this->convertResourceToResponse($resource, app(Request::class));

        $validator = new Validator();
        $validator->validate($converted, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid());
    }

    public function providesEncodedStructureValidates()
    {
        return [
            'type and id only' => [
                $this->createResourceableInterfaceStub(),
            ],
            'with attributes only' => [
                $this->createResourceableInterfaceStub(['name' => ''])
            ],
            'with links only' => [
                $this->createResourceableInterfaceStub(MissingValue::class, [
                    'myLink' => 'https://example.org',
                    'myLinkWithHrefOnly' => [
                        'href' => 'https://example.org',
                    ],
                    'myCompleteLink' => [
                        'href' => 'https://example.org',
                        'meta' => [
                            'count' => 10,
                        ]
                    ]
                ])
            ],
            'with meta only' => [
                $this->createResourceableInterfaceStub(MissingValue::class, MissingValue::class, [
                    'count' => 1538282
                ]),
            ],
            'relationships with link only' => [
                $this->createResourceableInterfaceStub(
                    MissingValue::class,
                    MissingValue::class,
                    MissingValue::class,
                    ['myRelationshipWithoutData' => [
                        'links' => [
                            'related' => 'https://example.org',
                        ]
                    ]]
                ),
            ],
            'relationships with hasMany data' => [
                $this->createResourceableInterfaceStub(
                    MissingValue::class,
                    MissingValue::class,
                    MissingValue::class,
                    ['myRelationshipWithHasManyData' => [
                        'data' => [
                            ['type' => 'resource1', 'id' => 'id'],
                            ['type' => 'resource2', 'id' => 'id'],
                        ],
                        'links' => [
                            'related' => 'https://example.org',
                        ],
                    ]]
                ),
            ],
            'relationships with hasOne data' => [
                $this->createResourceableInterfaceStub(
                    MissingValue::class,
                    MissingValue::class,
                    MissingValue::class,
                    ['myRelationshipWithHasOneData' => [
                        'data' => ['type' => 'resource', 'id' => 'id'],
                        'links' => [
                            'related' => 'https://example.org',
                        ],
                    ]]
                ),
            ],
            'relationships with data only' => [
                $this->createResourceableInterfaceStub(
                    MissingValue::class,
                    MissingValue::class,
                    MissingValue::class,
                    ['myRelationshipWithDataOnly' => [
                        'data' => [
                            'type' => 'resource',
                            'id' => 'id',
                        ],
                    ]]
                )
            ],
        ];
    }

    /**
     * @dataProvider providesEncodedStructureValidatesWithMultiple
     * @param $stubs
     */
    public function testEncodedStructureValidatesWithMultiple($stubs)
    {
        $resource = JsonApiResource::collection($stubs);
        $converted = $this->convertResourceToResponse($resource, app(Request::class));

        $validator = new Validator();
        $validator->validate($converted, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid());
        $this->assertObjectHasAttribute('data', $converted);
        $this->assertCount(1, $converted->data);
    }

    public function providesEncodedStructureValidatesWithMultiple()
    {
        $data = [];

        foreach ($this->providesEncodedStructureValidates() as $name => $params) {
            $data["{$name} [array]"] = [[$params[0]]];
            $data["{$name} [collection]"] = [collect([$params[0]])];
            $data["{$name} [length-aware paginator]"] = [new LengthAwarePaginator([$params[0]], 1, 1, 1)];
            $data["{$name} [paginator]"] = [new Paginator([$params[0]], 1, 1)];
        }

        return $data;
    }

    /**
     * @dataProvider providesExceptionThrownForNonConverteableInterfaces
     * @param $value
     */
    public function testExceptionThrownForNonConverteableInterfaces($value)
    {
        $this->expectException(InvalidArgumentException::class);

        $resource = new JsonApiResource($value);
        $request = app(Request::class);

        $resource->toArray($request);
    }

    public function providesExceptionThrownForNonConverteableInterfaces()
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
     * @param JsonApiResource|JsonApiResourceCollection $resource
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

    private function createResourceableInterfaceStub(
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
}
