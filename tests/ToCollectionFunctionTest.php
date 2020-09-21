<?php

namespace Garbetjie\Laravel\JsonApi\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function Garbetjie\Laravel\JsonApi\to_collection;

class ToCollectionFunctionTest extends TestCase
{
    /**
     * @dataProvider valueProvider

     * @param mixed $input
     * @param array $expected
     */
    public function testWithInputs($input, array $expected)
    {
        $collection = to_collection($input);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals($expected, $collection->all());
    }

    public function valueProvider()
    {
        $model1 = new class extends Model {};
        $model2 = new class extends Model {};

        return [
            'single scalar (int)' => [
                1,
                [1]
            ],

            'single scalar (string)' => [
                'value',
                ['value']
            ],

            'array' => [
                ['one', 'two', 'three'],
                ['one', 'two', 'three']
            ],

            'paginator' => [
                new Paginator(['one', 'two', 'three'], 15, 1),
                ['one', 'two', 'three']
            ],

            'collection (enumerable)' => [
                new Collection(['one', 'two', 'three']),
                ['one', 'two', 'three']
            ],

            'single model' => [
                $model1,
                [$model1]
            ],

            'array of models' => [
                [$model1, $model2],
                [$model1, $model2],
            ],

            'collection of models' => [
                new Collection([$model1, $model2]),
                [$model1, $model2],
            ]
        ];
    }
}
