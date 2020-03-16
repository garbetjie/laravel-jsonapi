<?php

namespace Garbetjie\Laravel\JsonApi\Tests\Extractors;

use Garbetjie\Laravel\JsonApi\ExtractorInterface;
use Garbetjie\Laravel\JsonApi\Extractors\PassthroughExtractor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function is_array;
use function mt_rand;
use const PHP_INT_MAX;

class PassthroughExtractorTest extends TestCase
{
    /**
     * @dataProvider valueProvider
     *
     * @param string|array $value
     */
    public function testSimple($value)
    {
        $extractor = new PassthroughExtractor($value);
        $collection = $extractor(mt_rand(0, PHP_INT_MAX));
        $valueAsArray = !is_array($value) ? [$value] : $value;

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(count($valueAsArray), $collection->count());
        $this->assertEquals($valueAsArray, $collection->all());

    }

    public function valueProvider()
    {
        return [
            'single string value' => [
                'resource'
            ],

            'single int value' => [
                mt_rand(0, PHP_INT_MAX),
            ],

            'array of multiple items' => [
                ['one', 'two'],
            ],

            'array of one item' => [
                ['three'],
            ],

            'single model' => [
                new class extends Model {},
            ],

            'array of models' => [
                [new class extends Model {}],
            ],
        ];
    }
}
