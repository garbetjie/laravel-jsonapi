<?php

namespace Garbetjie\Laravel\JsonApi\Tests\Extractors;

use Closure;
use Garbetjie\Laravel\JsonApi\ExtractorInterface;
use Garbetjie\Laravel\JsonApi\Extractors\ClosureExtractor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function collect;
use function Garbetjie\Laravel\JsonApi\to_collection;
use function is_array;
use function is_scalar;

class ClosureExtractorTest extends TestCase
{
    /**
     * @dataProvider valueProvider
     *
     * @param mixed $value
     * @param int $expectedCount
     */
    public function testClosureCalled($value, $expectedCount)
    {
        $valueAsCollection = to_collection($value);
        $invocationCount = 0;
        $extractor = new ClosureExtractor(
            function($provided) use (&$invocationCount, $value) {
                $invocationCount++;
                $this->assertEquals($value, $provided);

                return $provided;
            }
        );

        $collection = $extractor($value);

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(1, $invocationCount);
        $this->assertEquals($expectedCount, $collection->count());
        $this->assertEquals($valueAsCollection->all(), $collection->all());
    }

    public function valueProvider()
    {
        return [
            'single value' => [
                'resource',
                1,
            ],

            'array of values' => [
                ['resource 1', 'resource 2'],
                2,
            ],

            'collection of values' => [
                collect(['resource 1', 'resource 2', 'resource 3']),
                3,
            ],

            'single model' => [
                new class extends Model {},
                1
            ],

            'array of 1 model' => [
                [new class extends Model{}],
                1
            ],

            'array of many models' => [
                [new class extends Model {}, new class extends Model {}],
                2,
            ]
        ];
    }
}
