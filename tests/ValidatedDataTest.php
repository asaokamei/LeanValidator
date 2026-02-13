<?php

namespace Wscore\LeanValidator\Tests;

use PHPUnit\Framework\TestCase;
use Wscore\LeanValidator\Validator;
use RuntimeException;

class ValidatedDataTest extends TestCase
{
    public function testGetValidatedDataReturnsOnlyValidatedFields()
    {
        $data = [
            'name' => 'John',
            'age' => 30,
            'extra' => 'not validated'
        ];
        $v = Validator::make($data);
        $v->forKey('name')->required()->string();
        $v->forKey('age')->required()->int();

        $validated = $v->getValidatedData();
        $this->assertEquals([
            'name' => 'John',
            'age' => 30
        ], $validated);
        $this->assertArrayNotHasKey('extra', $validated);
    }

    public function testGetValidatedDataThrowsExceptionOnError()
    {
        $data = ['age' => 'not an int'];
        $v = Validator::make($data);
        $v->forKey('age')->required()->int();

        $this->expectException(RuntimeException::class);
        $v->getValidatedData();
    }

    public function testGetValidatedDataWithArrayApply()
    {
        $data = [
            'tags' => ['php', 'testing', 'validator'],
            'other' => 'hidden'
        ];
        $v = Validator::make($data);
        $v->forKey('tags')->arrayApply('string');

        $validated = $v->getValidatedData();
        $this->assertEquals([
            'tags' => ['php', 'testing', 'validator']
        ], $validated);
    }

    public function testGetValidatedDataWithForEach()
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John', 'extra' => 'hide'],
                ['id' => 2, 'name' => 'Jane', 'extra' => 'hide'],
            ],
            'meta' => 'hide'
        ];
        $v = Validator::make($data);
        $v->forKey('users')->forEach(function(Validator $child) {
            $child->forKey('id')->required()->int();
            $child->forKey('name')->required()->string();
        });

        $validated = $v->getValidatedData();
        $this->assertEquals([
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ]
        ], $validated);
    }
}
