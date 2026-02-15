<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;

class RequiredWhenTest extends TestCase
{
    public function testRequiredWhenTrue()
    {
        $v = Validator::make(['type' => 'personal', 'name' => '']);
        $v->forKey('name')->requiredWhen(function($data) {
            return ($data['type'] ?? '') === 'personal';
        })->string();

        $this->assertFalse($v->isValid());
        $errors = $v->getErrorsFlat();
        $this->assertArrayHasKey('name', $errors);
    }

    public function testRequiredWhenFalse()
    {
        $v = Validator::make(['type' => 'business', 'name' => '']);
        $v->forKey('name')->requiredWhen(function($data) {
            return ($data['type'] ?? '') === 'personal';
        })->string();

        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertArrayNotHasKey('name', $data);
    }

    public function testRequiredWhenWithElseOverwrite()
    {
        $v = Validator::make(['type' => 'business', 'name' => '']);
        $v->forKey('name')->requiredWhen(
            fn($data) => ($data['type'] ?? '') === 'personal',
            'Name is required',
            'N/A'
        )->string();

        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('N/A', $data['name']);
    }

    public function testRequiredWhenTrueWithValue()
    {
        $v = Validator::make(['type' => 'personal', 'name' => 'John']);
        $v->forKey('name')->requiredWhen(function($data) {
            return ($data['type'] ?? '') === 'personal';
        })->string();

        $this->assertTrue($v->isValid());
        $data = $v->getValidatedData();
        $this->assertEquals('John', $data['name']);
    }
}
