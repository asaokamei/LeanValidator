<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class HasValueTest extends TestCase
{
    public function testHasValueWithValues()
    {
        $v = Validator::make(['a' => 'v', 'b' => 0, 'c' => false, 'd' => []]);
        
        $this->assertTrue($v->forKey('a')->hasValue());
        $this->assertTrue($v->forKey('b')->hasValue());
        $this->assertTrue($v->forKey('c')->hasValue());
        $this->assertTrue($v->forKey('d')->hasValue()); // empty array is a value
    }

    public function testHasValueWithEmptyValues()
    {
        $v = Validator::make(['a' => '', 'b' => null]);
        
        $this->assertFalse($v->forKey('a')->hasValue());
        $this->assertFalse($v->forKey('b')->hasValue());
        $this->assertFalse($v->forKey('missing')->hasValue());
    }
}
