<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class HasValueTest extends TestCase
{
    public function testHasValueWithValues()
    {
        $v = Validator::make(['a' => 'v', 'b' => 0, 'c' => false, 'd' => []]);
        
        $this->assertTrue($v->field('a')->hasValue());
        $this->assertTrue($v->field('b')->hasValue());
        $this->assertTrue($v->field('c')->hasValue());
        $this->assertTrue($v->field('d')->hasValue()); // empty array is a value
    }

    public function testHasValueWithEmptyValues()
    {
        $v = Validator::make(['a' => '', 'b' => null]);
        
        $this->assertFalse($v->field('a')->hasValue());
        $this->assertFalse($v->field('b')->hasValue());
        $this->assertFalse($v->field('missing')->hasValue());
    }
}
