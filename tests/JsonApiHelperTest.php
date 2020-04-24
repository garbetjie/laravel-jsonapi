<?php

namespace Garbetjie\Laravel\JsonApi\Tests;

use Garbetjie\Laravel\JsonApi\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use function Garbetjie\Laravel\JsonApi\jsonapi;

class JsonApiHelperTest extends TestCase
{

    public function testHelperFunctionReturnsCorrectInstance()
    {
        $this->assertInstanceOf(Helper::class, jsonapi());
    }

    public function testFilterParameters()
    {
        $request = new Request(['filter' => ['cow' => 'moo']]);

        $this->assertInstanceOf(Collection::class, jsonapi()->filters($request));
        $this->assertCount(1, jsonapi()->filters($request));
    }

    // TODO Add more tests.
}
