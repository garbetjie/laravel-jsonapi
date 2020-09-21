<?php

namespace Garbetjie\Laravel\JsonApi\Tests\Extractors;

use Garbetjie\Laravel\JsonApi\IncludeExtractorInterface;
use Garbetjie\Laravel\JsonApi\Extractors\PluckIncludeExtractor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function collect;

class PluckIncludeExtractorTest extends TestCase
{
    /**
     * @dataProvider valueProvider
     *
     * @param string|array $paths
     * @param Model|Collection $model
     * @param array $expected
     */
    public function testPluckPaths($paths, $model, array $expected)
    {
        $extractor = new PluckIncludeExtractor($paths);
        $collection = $extractor($model);

        $this->assertIsCallable($extractor);
        $this->assertCount(count($expected), $collection);

        // Ensure that the returned values match those in the collection.
        foreach ($expected as $index => $item) {
            $this->assertEquals($item, $collection->get($index));
        }
    }

    public function valueProvider()
    {
        $root = new class extends Model { protected $table = 'root'; };
        $one = new class extends Model { protected $table = 'one'; };
        $two = new class extends Model { protected $table = 'two'; };
        $three_1 = new class extends Model { protected $table = 'three_1'; };
        $three_2 = new class extends Model { protected $table = 'three_2'; };
        $four_1 = new class extends Model { protected $table = 'four_1'; };
        $four_2 = new class extends Model { protected $table = 'four_2'; };

        $root->setRelation('one', $one);
        $one->setRelation('two', $two);
        $two->setRelation('three', $three_1);
        $two->setRelation('threes', collect([$three_1, $three_2]));
        $three_1->setRelation('four', $four_1);
        $three_2->setRelation('four', $four_2);

        return [
            'single string pluck path' => [
                'one.two',
                $root,
                [$one, $two]
            ],

            'array of pluck paths' => [
                ['one.two', 'one.two.three.four'],
                $root,
                [$one, $two, $one, $two, $three_1, $four_1]
            ],

            'pluck path with collection relationships' => [
                ['one', 'one.two.threes'],
                $root,
                [$one, $one, $two, $three_1, $three_2],
            ],

            'pluck from collection' => [
                ['one', 'one.two.threes'],
                collect([$root]),
                [$one, $one, $two, $three_1, $three_2],
            ]
        ];
    }
}
