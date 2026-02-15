<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class OptionalTest extends TestCase
{
    public function testOptionalWithExistingValue()
    {
        $v = Validator::make(['name' => 'John']);
        $v->forKey('name')->optional('default')->string();
        
        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('John', $data['name']);
    }

    public function testOptionalWithNull()
    {
        $v = Validator::make(['name' => null]);
        $v->forKey('name')->optional('default')->string();
        
        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('default', $data['name']);
    }

    public function testOptionalWithEmptyString()
    {
        $v = Validator::make(['name' => '']);
        $v->forKey('name')->optional('default')->string();
        
        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('default', $data['name']);
    }

    public function testOptionalWithMissingKey()
    {
        $v = Validator::make([]);
        $v->forKey('name')->optional('default')->string();
        
        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('default', $data['name']);
    }

    public function testOptionalStopsFurtherValidation()
    {
        $v = Validator::make(['age' => '']);
        // If optional works, int() validation should be skipped and no error should occur.
        $v->forKey('age')->optional()->int();
        
        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertArrayNotHasKey('age', $data);
    }

    public function testWithoutOptional()
    {
        $v = Validator::make(['age' => '']);
        // If optional works, int() validation should be skipped and no error should occur.
        $v->forKey('age')->int();

        $this->assertFalse($v->isValid());
        $bag = $v->getMessageBag();
        $this->assertEquals('Please check the input value.', $bag->first('age'));
    }

    public function testOptionalWithNoDefaultProvided()
    {
        $v = Validator::make(['name' => '']);
        $v->forKey('name')->optional()->string();
        
        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertArrayNotHasKey('name', $data);
    }
}
