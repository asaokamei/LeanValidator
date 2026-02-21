<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class RequiredIfTest extends TestCase
{
    public function testRequiredIfWhenMatched()
    {
        // When 'type' is 'friend', 'name' is required.
        $v = Validator::make(['type' => 'friend', 'name' => '']);
        $v->forKey('type')->string();
        $v->forKey('name')->requiredIf('type', 'friend')->string();

        $this->assertFalse($v->isValid());
        $this->assertEquals('This field is required.', $v->getErrors()->first('name'));

        $v = Validator::make(['type' => 'friend', 'name' => 'John']);
        $v->forKey('type')->string();
        $v->forKey('name')->requiredIf('type', 'friend')->string();

        $this->assertTrue($v->isValid());
        $this->assertEquals('John', $v->getValidatedData()['name']);
    }

    public function testRequiredIfWhenNotMatched()
    {
        // When 'type' is NOT 'friend', 'name' is optional.
        $v = Validator::make(['type' => 'other', 'name' => '']);
        $v->forKey('type')->string();
        $v->forKey('name')->requiredIf('type', 'friend')->string();

        $this->assertTrue($v->isValid());
        $this->assertArrayNotHasKey('name', $v->getValidatedData());
    }

    public function testRequiredIfWithElseOverwrite()
    {
        // When 'type' is NOT 'friend', 'name' should be 'Guest'.
        $v = Validator::make(['type' => 'other', 'name' => 'John']);
        $v->forKey('type')->string();
        $v->forKey('name')->requiredIf('type', 'friend', null, 'Guest')->string();

        $this->assertTrue($v->isValid());
        $this->assertEquals('Guest', $v->getValidatedData()['name']);
    }

    public function testRequiredIfWithMultipleExpect()
    {
        // When 'type' is 'friend' or 'family', 'name' is required.
        $v = Validator::make(['type' => 'family', 'name' => '']);
        $v->forKey('type')->string();
        $v->forKey('name')->requiredIf('type', ['friend', 'family'])->string();

        $this->assertFalse($v->isValid());

        $v = Validator::make(['type' => 'family', 'name' => 'John']);
        $v->forKey('type')->string();
        $v->forKey('name')->requiredIf('type', ['friend', 'family'])->string();

        $this->assertTrue($v->isValid());
        $this->assertEquals('John', $v->getValidatedData()['name']);
    }

    public function testRequiredIfWhenSkipped()
    {
        // If already error or skipped, requiredIf should do nothing.
        $v = Validator::make(['type' => 'friend', 'name' => '']);
        $v->forKey('name')->message('previous error')->required()->requiredIf('type', 'friend');
        $this->assertEquals('previous error', $v->getErrors()->first('name'));
    }
}
