<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class InvokableObjectTest extends TestCase
{
    public function testApplyWithInvokableObject()
    {
        $v = Validator::make(['price' => 100]);
        
        $rule = new class {
            public function __invoke($value, $min)
            {
                return $value >= $min;
            }
        };

        $v->forKey('price')->apply($rule, 50);
        $this->assertTrue($v->isValid());

        $v->forKey('price')->apply($rule, 150);
        $this->assertFalse($v->isValid());
    }
}
