<?php

namespace Garbetjie\Laravel\JsonApi\Tests\Extractors;

use Error;
use Garbetjie\Laravel\JsonApi\Extractors\ClosureIncludeExtractor;
use Garbetjie\Laravel\JsonApi\Extractors\CombinationIncludeExtractor;
use Garbetjie\Laravel\JsonApi\Extractors\PassthroughIncludeExtractor;
use Garbetjie\Laravel\JsonApi\Extractors\PluckIncludeExtractor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function collect;

class CombinationIncludeExtractorTest extends TestCase
{
    public function testCombination()
    {
        $root = new class extends Model{ protected $table = 'root'; };
        $one = new class extends Model{ protected $table = 'one'; };
        $two = new class extends Model{ protected $table = 'two'; };
        $three = new class extends Model{ protected $table = 'three'; };

        $root->setRelation('one', $one);
        $one->setRelation('two', $two);

        $extractor = new CombinationIncludeExtractor([
            new PluckIncludeExtractor('one'),
            new PluckIncludeExtractor('one.two'),
            new ClosureIncludeExtractor(
                function (Model $resource) {
                    return collect([$resource->one->two]);
                }
            ),
            new PassthroughIncludeExtractor($three)
        ]);

        $this->assertIsCallable($extractor);
        $collection = $extractor($root);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(5, $collection);
        $this->assertEquals([$one, $one, $two, $two, $three], $collection->all());
    }

    public function testErrorThrownConstructor()
    {
        $this->expectException(Error::class);

        new CombinationIncludeExtractor(new PluckIncludeExtractor('one'));
    }
}
