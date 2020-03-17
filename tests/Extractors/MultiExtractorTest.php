<?php

namespace Garbetjie\Laravel\JsonApi\Tests\Extractors;

use Error;
use Garbetjie\Laravel\JsonApi\Extractors\ClosureExtractor;
use Garbetjie\Laravel\JsonApi\Extractors\MultiExtractor;
use Garbetjie\Laravel\JsonApi\Extractors\PassthroughExtractor;
use Garbetjie\Laravel\JsonApi\Extractors\PluckExtractor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function collect;

class MultiExtractorTest extends TestCase
{
    public function testCombination()
    {
        $root = new class extends Model{ protected $table = 'root'; };
        $one = new class extends Model{ protected $table = 'one'; };
        $two = new class extends Model{ protected $table = 'two'; };
        $three = new class extends Model{ protected $table = 'three'; };

        $root->setRelation('one', $one);
        $one->setRelation('two', $two);

        $extractor = new MultiExtractor([
            new PluckExtractor('one'),
            new PluckExtractor('one.two'),
            new ClosureExtractor(
                function (Model $resource) {
                    return collect([$resource->one->two]);
                }
            ),
            new PassthroughExtractor($three)
        ]);


        $collection = $extractor($root);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertEquals([$one, $one, $two, $two, $three], $collection->all());
    }

    public function testErrorThrownConstructor()
    {
        $this->expectException(Error::class);

        new MultiExtractor(new PluckExtractor('one'));
    }
}
