<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class ApplyTest extends TestCase
{
    public function testApplyWithInternalMethod()
    {
        $v = Validator::make(['age' => 20]);
        $v->forKey('age')->apply('int', 18, 30);
        $this->assertTrue($v->isCurrentOK());
        $this->assertEquals(['age' => 20], $v->getValidatedData());

        $v = Validator::make(['age' => 10]);
        $v->forKey('age')->apply('int', 18, 30);
        $this->assertTrue($v->isCurrentError());
    }

    public function testApplyWithClosure()
    {
        $v = Validator::make(['name' => 'John']);
        $v->forKey('name')->apply(function($value) {
            return $value === 'John';
        });
        $this->assertTrue($v->isCurrentOK());

        $v->forKey('name')->apply(function($value, $expected) {
            return $value === $expected;
        }, 'Jane', 'Name must be Jane');
        $this->assertTrue($v->isCurrentError());
        $this->assertEquals('Name must be Jane', $v->getErrorsFlat()['name']);
    }

    public function testApplyWithClosureUsingValidator()
    {
        $v = Validator::make(['code' => 'ABC']);
        $v->forKey('code')->apply(function() {
            /** @var Validator $this */
            $this->string()->regex('/^[A-Z]+$/');
        });
        $this->assertTrue($v->isCurrentOK());
        $this->assertEquals(['code' => 'ABC'], $v->getValidatedData());

        $v = Validator::make(['code' => '123']);
        $v->forKey('code')->apply(function() {
            /** @var Validator $this */
            $this->string()->regex('/^[A-Z]+$/');
        });
        $this->assertTrue($v->isCurrentError());
    }

    public function testApplyWithOtherCallable()
    {
        $v = Validator::make(['name' => 'John']);
        // is_string is a callable
        $v->forKey('name')->apply('is_string');
        $this->assertTrue($v->isCurrentOK());

        $v->forKey('name')->apply('is_int', 'Must be int');
        $this->assertTrue($v->isCurrentError());
        $this->assertEquals('Must be int', $v->getErrorsFlat()['name']);
    }
}
