<?php


namespace Garbetjie\Laravel\JsonApi\Tests;

use Garbetjie\Laravel\JsonApi\ConvertibleToJsonApiResourceInterface;
use Garbetjie\Laravel\JsonApi\JsonApiResource;
use Garbetjie\Laravel\JsonApi\JsonApiResourceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use InvalidArgumentException;
use JsonSchema\Validator;
use stdClass;
use function app;
use function func_num_args;

class JsonApiResourceTest extends TestCase
{
    /**
     * This test will test each of the methods on a JSONAPI resource (getJsonApiAttributes, getJsonApiMeta, etc), and
     * ensure the return value matches what is expected.
     *
     * @param string $methodToCall
     * @param string $propertyToTest
     * @param mixed $populatedValue
     * @param mixed $expectedValue
     * @dataProvider jsonApiResourceInterfaceMethodOutputProvider
     */
    public function testJsonApiResourceInterfaceMethodOutputs(string $methodToCall, string $propertyToTest, $populatedValue, $expectedValue = null)
    {
        $stub = $this->createStub(JsonApiResourceInterface::class);
        $stub->method($methodToCall)->willReturn($populatedValue);

        $resource = new JsonApiResource($stub);
        $built = $resource->toArray(app(Request::class));

        $this->assertIsArray($built);

        // If the expected value is not provided, then we'll test to ensure that the property does _not_ exist in the output.
        if (func_num_args() > 3) {
            $this->assertEquals($expectedValue, $built[$propertyToTest]);
        } else {
            $this->assertArrayNotHasKey($propertyToTest, $built);
        }
    }

    public function jsonApiResourceInterfaceMethodOutputProvider()
    {
        $output = [];
        $methodsToProperty = [
            'getJsonApiAttributes' => 'attributes',
            'getJsonApiMeta' => 'meta',
            'getJsonApiLinks' => 'links',
            'getJsonApiRelationships' => 'relationships',
        ];

        foreach ($methodsToProperty as $method => $property) {
            $objectProperty = new stdClass();
            $objectProperty->method = $method;

            $output["Array with single null [{$property}]"] = [
                $method, $property, ['name' => null], ['name' => null]
            ];
            $output["Array with empty string [{$property}]"] = [
                $method, $property, ['name' => ''], ['name' => '']
            ];
            $output["Array with string value [{$property}]"] = [
                $method, $property, ['name' => 'name'], ['name' => 'name']
            ];
            $output["Array with int value [{$property}]"] = [
                $method, $property, ['name' => 123], ['name' => 123]
            ];
            $output["Array with nested array [{$property}]"] = [
                $method, $property, ['name' => ['cow' => 'moo']], ['name' => ['cow' => 'moo']]
            ];
            $output["Array with single missing value [{$property}]"] = [
                $method, $property, ['name' => new MissingValue()], new stdClass()
            ];
            $output["Array with multiple missing values [{$property}]"] = [
                $method, $property, ['name' => new MissingValue(), 'cow' => new MissingValue()], new stdClass()
            ];
            $output["Empty array [{$property}]"] = [
                $method, $property, [], new stdClass()
            ];
            $output["Array with missing value [{$property}]"] = [
                $method, $property, ['name' => new MissingValue()], new stdClass()
            ];
            $output["Single null [{$property}]"] = [
                $method, $property, null, null
            ];
            $output["Single int [{$property}]"] = [
                $method, $property, 123, 123
            ];
            $output["Empty string [{$property}]"] = [
                $method, $property, '', ''
            ];
            $output["Populated string [{$property}]"] = [
                $method, $property, 'name', 'name'
            ];
            $output["Object [{$property}]"] = [
                $method, $property, $objectProperty, $objectProperty
            ];
            $output["Missing value [{$property}]"] = [
                $method, $property, new MissingValue()
            ];
            $output["Multiple missing values [{$property}]"] = [
                $method, $property, [new MissingValue(), new MissingValue()], new stdClass()
            ];
        }

        return $output;
    }

    /**
     * This test ensures that the output of the JsonApiResource conforms to the JSON:API spec.
     *
     * @dataProvider structureConformsToSpecProvider
     * @param JsonApiResourceInterface $stub
     */
    public function testStructureConformsToSpec(JsonApiResourceInterface $stub)
    {
        $resource = new JsonApiResource($stub);
        $converted = $this->convertResourceToResponse($resource, app(Request::class));

        $validator = new Validator();
        $validator->validate($converted, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid());
    }

    public function structureConformsToSpecProvider()
    {
        return [
            'Type & ID' => [
                $this->createResourceableInterfaceStub(),
            ],
            'Attributes' => [
                $this->createResourceableInterfaceStub(['name' => ''])
            ],
            'Links' => [
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
            'Meta' => [
                $this->createResourceableInterfaceStub(MissingValue::class, MissingValue::class, [
                    'count' => 1538282
                ]),
            ],
            'Relationship with links' => [
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
            'Relationship with hasMany data' => [
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
            'Relationship with hasOne data' => [
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
            'Relationship with data only' => [
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
     * Ensures that any value being rendered as a JSON:API resource must implement one of the two interfaces.
     *
     * @dataProvider providesExceptionThrownForNonConvertibleInterfaces
     * @param mixed $value
     */
    public function testExceptionThrownForNonConvertibleInterfaces($value)
    {
        $this->expectException(InvalidArgumentException::class);

        $resource = new JsonApiResource($value);
        $request = app(Request::class);

        $resource->toArray($request);
    }

    public function providesExceptionThrownForNonConvertibleInterfaces()
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
     * @param string $includes
     * @param array $loadersToLoad
     * @param int $expectedCount
     * @param string $methodToCall
     * @throws \ReflectionException
     *
     * @dataProvider includeLoadersAndExtractorsAreCalledCorrectNumberOfTimesProvider
     */
    public function testIncludeLoadersAndExtractorsAreCalledCorrectNumberOfTimes(string $includes, array $loadersToLoad, int $expectedCount, string $methodToCall)
    {
        $actualCount = 0;
        $resource = new JsonApiResource($this->createResourceableInterfaceStub());

        foreach ($loadersToLoad as $animal => $countToAdd) {
            for ($i = 0; $i < $countToAdd; $i++) {
                $resource->$methodToCall($animal, function() use (&$actualCount) {
                    $actualCount++;
                });
            }
        }

        $this->convertResourceToResponse($resource, new Request(['include' => $includes]));
        $this->assertEquals($expectedCount, $actualCount);
    }

    public function includeLoadersAndExtractorsAreCalledCorrectNumberOfTimesProvider()
    {
        return [
            ['cow', ['cow' => 2, 'dog' => 1], 2, 'withIncludeLoader'],
            ['cow,dog', ['cow' => 2], 2, 'withIncludeLoader'],
            ['cow,dog', ['cow' => 2], 2, 'withIncludeLoader'],
            ['cow,dog,cat', ['cow' => 2, 'cat' => 3], 5, 'withIncludeLoader'],
            ['cow,dog,cat', ['dog' => 11, 'cow' => 2, 'cat' => 3], 16, 'withIncludeLoader'],

            ['cow', ['cow' => 2, 'dog' => 1], 2, 'withIncludeExtractor'],
            ['cow,dog', ['cow' => 2], 2, 'withIncludeExtractor'],
            ['cow,dog', ['cow' => 2], 2, 'withIncludeExtractor'],
            ['cow,dog,cat', ['cow' => 2, 'cat' => 3], 5, 'withIncludeExtractor'],
            ['cow,dog,cat', ['dog' => 11, 'cow' => 2, 'cat' => 3], 16, 'withIncludeExtractor'],
        ];
    }

    /**
     * @param string $methodName
     * @param array $methodArgs
     * @dataProvider sameInstanceIsReturnedFromMethodProvider
     */
    public function testSameInstanceIsReturnedFromMethod(string $methodName, array $methodArgs)
    {
        $resource = new JsonApiResource($this->createResourceableInterfaceStub());
        $this->assertSame($resource, $resource->$methodName(...$methodArgs));
    }

    public function sameInstanceIsReturnedFromMethodProvider()
    {
        return [
            ['withIncludeLoader', ['', function() { }]],
            ['withIncludeExtractor', ['', function() { }]],
            ['withDefaultIncludes', [[]]],
        ];
    }

    /**
     * @param string $methodName
     * @param mixed $inputValue
     * @dataProvider includeLoadersAndExtractorsReceiveCorrectResourceArgumentProvider
     */
    public function testIncludeLoadersAndExtractorsReceiveCorrectResourceArgument(string $methodName, $inputValue)
    {
        $resource = (new JsonApiResource($inputValue))->$methodName(
            'cow',
            function($resource) use ($inputValue) {
                $this->assertSame($inputValue, $resource);
            }
        );

        $this->convertResourceToResponse($resource, new Request(['include' => 'cow']));
    }

    public function includeLoadersAndExtractorsReceiveCorrectResourceArgumentProvider()
    {
        $stub = $this->createResourceableInterfaceStub();
        $convertible = new class() implements ConvertibleToJsonApiResourceInterface {
            public function convertToJsonApiResource(): JsonApiResourceInterface {
                return $this->stub;
            }
        };
        $convertible->stub = $stub;

        return [
            ['withIncludeLoader', $stub],
            ['withIncludeExtractor', $stub],

            ['withIncludeLoader', $convertible],
            ['withIncludeExtractor', $convertible],
        ];
    }

    /**
     * @param mixed $resource
     * @dataProvider selfAndResourcePropertyAsInterfaceReturnSameValueProvider
     */
    public function testSelfAndResourcePropertyAsInterfaceReturnSameValue($resource)
    {
        $built = $this->convertResourceToResponse($resource, new Request());

        $this->assertIsObject($built);
        $this->assertObjectHasAttribute('data', $built);
        $this->assertObjectHasAttribute('type', $built->data);
        $this->assertObjectHasAttribute('id', $built->data);
        $this->assertObjectHasAttribute('attributes', $built->data);
        $this->assertObjectHasAttribute('name', $built->data->attributes);
    }

    public function selfAndResourcePropertyAsInterfaceReturnSameValueProvider()
    {
        return [
            [new JsonApiResource($this->createResourceableInterfaceStub(['name' => 'cow']))],
            [new JsonApiResource($this->createConvertibleInterfaceStub(
                $this->createResourceableInterfaceStub(['name' => 'cow'])
            ))],
            [new class('') extends JsonApiResource implements JsonApiResourceInterface {
                public function getJsonApiType() {
                    return 'type';
                }

                public function getJsonApiId() {
                    return 'id';
                }

                public function getJsonApiAttributes($request) {
                    return ['name' => 'cow'];
                }

                public function getJsonApiLinks($request) {
                    return new MissingValue();
                }

                public function getJsonApiMeta($request) {
                    return new MissingValue();
                }

                public function getJsonApiRelationships($request) {
                    return new MissingValue();
                }

            }],
            [(new class('') extends JsonApiResource implements ConvertibleToJsonApiResourceInterface {
                private $stub;
                public function withStub($stub) {
                    $this->stub = $stub;
                    return $this;
                }
                public function convertToJsonApiResource(): JsonApiResourceInterface {
                    return $this->stub;
                }

            })->withStub($this->createResourceableInterfaceStub(['name' => 'cow']))]
        ];
    }
}
