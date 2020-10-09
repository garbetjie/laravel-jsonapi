<?php

namespace Garbetjie\Laravel\JsonApi\Tests;

use Garbetjie\Laravel\JsonApi\ConvertibleToJsonApiResourceInterface;
use Garbetjie\Laravel\JsonApi\JsonApiResource;
use Garbetjie\Laravel\JsonApi\JsonApiResourceInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use JsonSchema\Validator;
use function app;
use function collect;
use function config;

class JsonApiResourceCollectionTest extends TestCase
{
    public function testPaginationParametersAreProduced()
    {
        $stub = $this->createResourceableInterfaceStub();
        $paginator = new LengthAwarePaginator([$stub], 1, 1);
        $json = $this->convertResourceToResponse(JsonApiResource::collection($paginator), new Request());

        $this->assertIsObject($json);
        $this->assertObjectHasAttribute('meta', $json);
        $this->assertObjectHasAttribute('total', $json->meta);
        $this->assertEquals(1, $json->meta->total);

        $this->assertObjectHasAttribute('links', $json);
        $this->assertObjectHasAttribute('first', $json->links);
    }

    public function testPaginationLinkRemoval()
    {
        $stub = $this->createResourceableInterfaceStub();
        $paginator = new LengthAwarePaginator([$stub], 1, 1);

        config()->set('garbetjie-jsonapi.strip_empty_links', true);
        $json = $this->convertResourceToResponse(JsonApiResource::collection($paginator), new Request());
        $this->assertObjectHasAttribute('links', $json);
        $this->assertObjectNotHasAttribute('next', $json->links);

        config()->set('garbetjie-jsonapi.strip_empty_links', false);
        $json = $this->convertResourceToResponse(JsonApiResource::collection($paginator), new Request());
        $this->assertObjectHasAttribute('links', $json);
        $this->assertObjectHasAttribute('next', $json->links);
        $this->assertNull($json->links->next);
    }

    /**
     * @param string $methodName
     * @param mixed $inputValue
     * @param mixed $expectedFirstValue
     * @dataProvider includeLoadersAndExtractorsReceiveCorrectCollectionArgumentProvider
     */
    public function testIncludeLoadersAndExtractorsReceiveCorrectCollectionArgument(string $methodName, $inputValue, $expectedFirstValue)
    {
        $resource = JsonApiResource::collection($inputValue)->$methodName(
            'cow',
            function ($resource) use ($expectedFirstValue) {
                $this->assertInstanceOf(Collection::class, $resource);
                $this->assertSame($resource->first(), $expectedFirstValue);
            }
        );

        $this->convertResourceToResponse($resource, new Request(['include' => 'cow']));
    }

    public function includeLoadersAndExtractorsReceiveCorrectCollectionArgumentProvider()
    {
        $stub = $this->createResourceableInterfaceStub();
        $convertible = new class implements ConvertibleToJsonApiResourceInterface {
            public function convertToJsonApiResource(): JsonApiResourceInterface {
                return $this->stub;
            }
        };
        $convertible->stub = $stub;

        return [
            'LengthAwarePaginator (resource)' => ['withIncludeLoader', new LengthAwarePaginator([$stub], 1, 1, 1), $stub],
            'Array (resource)' => ['withIncludeLoader', [$stub], $stub],
            'Single item (resource)' => ['withIncludeLoader', $stub, $stub],
            'Collection (resource)' => ['withIncludeLoader', new Collection([$stub]), $stub],

            'LengthAwarePaginator (convertible)' => ['withIncludeLoader', new LengthAwarePaginator([$convertible], 1, 1, 1), $convertible],
            'Array (convertible)' => ['withIncludeLoader', [$convertible], $convertible],
            'Single item (convertible)' => ['withIncludeLoader', $convertible, $convertible],
            'Collection (convertible)' => ['withIncludeLoader', new Collection([$convertible]), $convertible],
        ];
    }

    /**
     * Ensures that the output of the JsonApiResource with a collection conforms to the JSON:API spec.
     *
     * @dataProvider structureWithCollectionConformsToSpecProvider
     * @param mixed $stubs
     */
    public function testStructureWithCollectionConformsToSpec($stubs)
    {
        $resource = JsonApiResource::collection($stubs);
        $converted = $this->convertResourceToResponse($resource, app(Request::class));

        $validator = new Validator();
        $validator->validate($converted, ['$ref' => 'file://' . __DIR__ . '/jsonapi.schema.json']);

        $this->assertTrue($validator->isValid());
        $this->assertObjectHasAttribute('data', $converted);
        $this->assertCount(1, $converted->data);
    }

    public function structureWithCollectionConformsToSpecProvider()
    {
        $data = [];

        foreach ((new JsonApiResourceTest())->structureConformsToSpecProvider() as $name => $params) {
            $data["{$name} [array]"] = [[$params[0]]];
            $data["{$name} [collection]"] = [collect([$params[0]])];
            $data["{$name} [length-aware paginator]"] = [new LengthAwarePaginator([$params[0]], 1, 1, 1)];
            $data["{$name} [paginator]"] = [new Paginator([$params[0]], 1, 1)];
        }

        return $data;
    }

}
